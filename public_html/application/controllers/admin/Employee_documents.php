<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Employee personal documents (requirement 13b).
 *
 * Two audiences:
 *   - the employee, who sees their own folder at /mine, uploads a renewed document and
 *     enters the new number and expiry;
 *   - HR, who review pending updates and watch the soon-to-expire list.
 *
 * On approval the number and expiry are written back to the staff master record, and
 * the expiry cron reminds both the employee and HR ahead of time.
 */
class Employee_documents extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('employee_document_model');
        $this->load->model('hr_masters_model');
    }

    /**
     * HR / admin rights, for the review queue and the expiring list.
     */
    private function is_hr()
    {
        return is_admin()
            || has_permission('hrm_hr_records', '', 'view')
            || has_permission('hrm_hr_records', '', 'edit')
            || has_permission('hrp_setting', '', 'view');
    }

    /* ============================================================ my folder */

    /**
     * The logged-in employee's own document folder. HR viewing someone else passes
     * ?staff_id=.
     */
    public function mine()
    {
        $staff_id = (int) get_staff_user_id();

        if ($this->input->get('staff_id') && $this->is_hr()) {
            $staff_id = (int) $this->input->get('staff_id');
        }

        $data['staff_id']       = $staff_id;
        $data['is_own']         = $staff_id === (int) get_staff_user_id();
        $data['documents']      = $this->employee_document_model->get_for_staff($staff_id, true);
        $data['document_types'] = $this->hr_masters_model->get_document_types(true);
        $data['staff_name']     = get_staff_full_name($staff_id);
        $data['title']          = _l('empdoc_my_documents');

        $this->load->view('admin/employee_documents/mine', $data);
    }

    /**
     * Handle an upload from the folder.
     */
    public function upload()
    {
        if (!$this->input->post()) {
            redirect(admin_url('employee_documents/mine'));
        }

        $target_staff = (int) get_staff_user_id();

        // HR may upload on behalf of an employee.
        if ($this->input->post('staff_id') && $this->is_hr()) {
            $target_staff = (int) $this->input->post('staff_id');
        }

        // An employee may only upload for themselves.
        if ($target_staff !== (int) get_staff_user_id() && !$this->is_hr()) {
            access_denied('employee_documents');
        }

        $type_id = (int) $this->input->post('document_type_id');
        $type    = $this->hr_masters_model->get_document_type($type_id);

        if (!$type) {
            set_alert('warning', _l('empdoc_invalid_type'));
            redirect(admin_url('employee_documents/mine?staff_id=' . $target_staff));
        }

        // An employee cannot upload a type restricted to HR.
        if (!$type->employee_can_upload && !$this->is_hr()) {
            access_denied('employee_documents');
        }

        $file = $this->handle_file($target_staff);

        $doc_id = $this->employee_document_model->save($target_staff, $this->input->post(), $file);

        if ($doc_id) {
            set_alert('success', $type->requires_hr_approval && !$this->is_hr()
                ? _l('empdoc_uploaded_pending')
                : _l('empdoc_uploaded'));
        } else {
            set_alert('warning', _l('empdoc_upload_failed'));
        }

        redirect(admin_url('employee_documents/mine' . ($target_staff !== (int) get_staff_user_id()
            ? '?staff_id=' . $target_staff : '')));
    }

    /**
     * Move an uploaded file into the employee's folder under a safe name.
     *
     * @param int $staff_id
     *
     * @return array|null
     */
    private function handle_file($staff_id)
    {
        if (empty($_FILES['document']['name'])) {
            return null;
        }

        $original = $_FILES['document']['name'];
        $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        // Only sensible document/image types.
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'webp'];

        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $path = $this->employee_document_model->upload_path($staff_id);

        // Sanitised, collision-proof name on disk; the real name is kept in the row.
        $safe = 'doc_' . $staff_id . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

        if (!@move_uploaded_file($_FILES['document']['tmp_name'], $path . $safe)) {
            return null;
        }

        return [
            'file_name'     => $safe,
            'original_name' => $original,
            'file_type'     => $_FILES['document']['type'] ?? $ext,
        ];
    }

    /**
     * Download a document file. Employee may fetch their own; HR any.
     */
    public function download($id)
    {
        $doc = $this->employee_document_model->get($id);

        if (!$doc || empty($doc->file_name)) {
            show_404();
        }

        if ((int) $doc->staff_id !== (int) get_staff_user_id() && !$this->is_hr()) {
            access_denied('employee_documents');
        }

        $path = $this->employee_document_model->upload_path($doc->staff_id) . $doc->file_name;

        if (!is_file($path)) {
            show_404();
        }

        $this->load->helper('download');
        force_download($doc->original_name ?: $doc->file_name, file_get_contents($path));
    }

    public function delete($id)
    {
        $doc = $this->employee_document_model->get($id);

        if (!$doc) {
            show_404();
        }

        // An employee may remove their own pending upload; HR may remove any.
        $own_pending = (int) $doc->staff_id === (int) get_staff_user_id()
            && $doc->status === 'pending_approval';

        if (!$own_pending && !$this->is_hr()) {
            access_denied('employee_documents');
        }

        $this->employee_document_model->delete($id);
        set_alert('success', _l('empdoc_deleted'));

        redirect(admin_url('employee_documents/mine'
            . ((int) $doc->staff_id !== (int) get_staff_user_id() ? '?staff_id=' . $doc->staff_id : '')));
    }

    /* ============================================================ HR review */

    /**
     * The pending-review queue.
     */
    public function review($id = '')
    {
        if (!$this->is_hr()) {
            access_denied('employee_documents');
        }

        // Acting on one.
        if ($id !== '' && $this->input->post()) {
            $decision = $this->input->post('decision');

            if ($decision === 'approve') {
                $this->employee_document_model->approve($id);
                set_alert('success', _l('empdoc_approved'));
            } else {
                $this->employee_document_model->reject($id, (string) $this->input->post('reason'));
                set_alert('success', _l('empdoc_rejected'));
            }

            redirect(admin_url('employee_documents/review'));
        }

        $data['pending'] = $this->employee_document_model->get_pending_reviews();
        $data['title']   = _l('empdoc_review_queue');
        $this->load->view('admin/employee_documents/review', $data);
    }

    /**
     * The HR dashboard list of soon-to-expire documents.
     */
    public function expiring()
    {
        if (!$this->is_hr()) {
            access_denied('employee_documents');
        }

        $data['expiring'] = $this->employee_document_model->get_expiring(90);
        $data['title']    = _l('empdoc_expiring_soon');
        $this->load->view('admin/employee_documents/expiring', $data);
    }
}
