<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Employee personal documents and expiry tracking (requirement 13b).
 *
 * Before this, passport / visa / Emirates ID / labour-card numbers and expiries were
 * plain TEXT columns on tblstaff, employee files were untyped rows in tblfiles, and
 * nothing tracked an expiry or gave HR anything to review. This adds typed documents
 * with expiries, an employee-uploads / HR-approves workflow, and, on approval, writes
 * the number and expiry back onto the tblstaff columns the rest of the system already
 * reads - which is what keeps the "master list" current.
 *
 * The write-back only ever touches the columns the document type maps to, and those
 * are whitelisted in Hr_masters_model, so a document can never update an arbitrary
 * staff column.
 */
class Employee_document_model extends App_Model
{
    private $doc_table;
    private $type_table;
    private $alert_table;

    /** Where the files live on disk. */
    private $upload_root;

    public function __construct()
    {
        parent::__construct();

        $this->doc_table   = db_prefix() . 'emp_document';
        $this->type_table  = db_prefix() . 'emp_document_type';
        $this->alert_table = db_prefix() . 'emp_document_alert';
        $this->upload_root = FCPATH . 'uploads/emp_documents/';

        $this->load->model('hr_masters_model');
    }

    /* ==================================================================== read */

    /**
     * Documents for one employee. By default only the live and pending records, so the
     * folder shows the current state, not the whole history.
     *
     * @param int  $staff_id
     * @param bool $include_history
     *
     * @return array
     */
    public function get_for_staff($staff_id, $include_history = false)
    {
        if (!$this->db->table_exists($this->doc_table)) {
            return [];
        }

        $this->db->select($this->doc_table . '.*,'
            . ' ' . $this->type_table . '.name as type_name,'
            . ' ' . $this->type_table . '.has_number, ' . $this->type_table . '.has_expiry');
        $this->db->join($this->type_table,
            $this->type_table . '.id = ' . $this->doc_table . '.document_type_id', 'left');
        $this->db->where($this->doc_table . '.staff_id', (int) $staff_id);

        if (!$include_history) {
            $this->db->where_in($this->doc_table . '.status', ['current', 'pending_approval']);
        }

        $this->db->order_by($this->type_table . '.sort_order', 'ASC');
        $this->db->order_by($this->doc_table . '.uploaded_at', 'DESC');

        return $this->db->get($this->doc_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get($id)
    {
        $this->db->select($this->doc_table . '.*,'
            . ' ' . $this->type_table . '.name as type_name,'
            . ' ' . $this->type_table . '.staff_number_field, ' . $this->type_table . '.staff_expiry_field,'
            . ' ' . $this->type_table . '.requires_hr_approval,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as staff_name');
        $this->db->join($this->type_table,
            $this->type_table . '.id = ' . $this->doc_table . '.document_type_id', 'left');
        $this->db->join(db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->doc_table . '.staff_id', 'left');
        $this->db->where($this->doc_table . '.id', (int) $id);

        return $this->db->get($this->doc_table)->row();
    }

    /**
     * The documents awaiting HR review, for the review queue.
     *
     * @return array
     */
    public function get_pending_reviews()
    {
        $this->db->select($this->doc_table . '.*,'
            . ' ' . $this->type_table . '.name as type_name,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as staff_name');
        $this->db->join($this->type_table,
            $this->type_table . '.id = ' . $this->doc_table . '.document_type_id', 'left');
        $this->db->join(db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->doc_table . '.staff_id', 'left');
        $this->db->where($this->doc_table . '.status', 'pending_approval');
        $this->db->order_by($this->doc_table . '.uploaded_at', 'ASC');

        return $this->db->get($this->doc_table)->result_array();
    }

    /* ================================================================== upload */

    /**
     * Record an uploaded document.
     *
     * A type that needs no HR approval becomes current immediately (and supersedes the
     * previous current record). One that does is held pending_approval until HR acts,
     * so the master list is never changed on an employee's say-so.
     *
     * @param int   $staff_id
     * @param array $data document_type_id, document_number, issue_date, expiry_date, note
     * @param array $file $_FILES entry, already moved, as [file_name, original_name, file_type]
     *
     * @return int|bool
     */
    public function save($staff_id, $data, $file = null)
    {
        $staff_id = (int) $staff_id;
        $type_id  = (int) ($data['document_type_id'] ?? 0);

        if ($staff_id <= 0 || $type_id <= 0) {
            return false;
        }

        $type = $this->hr_masters_model->get_document_type($type_id);

        if (!$type || !$type->active) {
            return false;
        }

        $requires_approval = (int) $type->requires_hr_approval === 1;

        $row = [
            'staff_id'         => $staff_id,
            'document_type_id' => $type_id,
            'document_number'  => $type->has_number ? ($data['document_number'] ?? null) : null,
            'issue_date'       => !empty($data['issue_date']) ? to_sql_date($data['issue_date']) : null,
            'expiry_date'      => $type->has_expiry && !empty($data['expiry_date'])
                ? to_sql_date($data['expiry_date']) : null,
            'note'             => $data['note'] ?? null,
            'status'           => $requires_approval ? 'pending_approval' : 'current',
            'uploaded_by'      => get_staff_user_id() ?: null,
            'uploaded_at'      => date('Y-m-d H:i:s'),
        ];

        if ($file) {
            $row['file_name']     = $file['file_name'];
            $row['original_name'] = $file['original_name'];
            $row['file_type']     = $file['file_type'];
        }

        $this->db->insert($this->doc_table, $row);
        $doc_id = $this->db->insert_id();

        if (!$doc_id) {
            return false;
        }

        if ($requires_approval) {
            // Route through the shared approval engine so it lands in HR's queue, and
            // notify. The engine records the chain; approval flows back through
            // on_approval_complete.
            $this->load->model('approval_flow_model');
            $result = $this->approval_flow_model->submit(
                Approval_flow_model::TYPE_DOCUMENT, $doc_id, $staff_id
            );

            // If no document-change flow is configured, fall back to a direct HR
            // notification so the review is not silently lost.
            if (!$result['success']) {
                $this->notify_hr_of_pending($doc_id);
            }
        } else {
            // No approval needed: it is current now, so retire the previous current one
            // and push the values to the master record if the type maps any.
            $this->make_current($doc_id);
        }

        return $doc_id;
    }

    /* ============================================================ HR decision */

    /**
     * HR approves a pending document (direct path, not via the engine).
     *
     * @param int $id
     *
     * @return bool
     */
    public function approve($id)
    {
        $doc = $this->get($id);

        if (!$doc || $doc->status !== 'pending_approval') {
            return false;
        }

        $this->make_current($id);

        $this->db->where('id', (int) $id);
        $this->db->update($this->doc_table, [
            'reviewed_by' => get_staff_user_id() ?: null,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);

        // Tell the employee their update is live.
        add_notification([
            'description' => _l('empdoc_update_approved', [$doc->type_name]),
            'touserid'    => (int) $doc->staff_id,
            'link'        => 'employee_documents/mine',
            'fromcompany' => 1,
        ]);

        return true;
    }

    /**
     * @param int    $id
     * @param string $reason
     *
     * @return bool
     */
    public function reject($id, $reason = '')
    {
        $doc = $this->get($id);

        if (!$doc || $doc->status !== 'pending_approval') {
            return false;
        }

        $this->db->where('id', (int) $id);
        $this->db->update($this->doc_table, [
            'status'        => 'rejected',
            'reviewed_by'   => get_staff_user_id() ?: null,
            'reviewed_at'   => date('Y-m-d H:i:s'),
            'reject_reason' => $reason ?: null,
        ]);

        add_notification([
            'description' => _l('empdoc_update_rejected', [$doc->type_name])
                . ($reason ? ': ' . $reason : ''),
            'touserid'    => (int) $doc->staff_id,
            'link'        => 'employee_documents/mine',
            'fromcompany' => 1,
        ]);

        return true;
    }

    /**
     * Called by the shared engine when a document-change approval finishes.
     *
     * @param int    $doc_id
     * @param string $status
     */
    public function on_approval_complete($doc_id, $status)
    {
        if ($status === 'approved') {
            $doc = $this->get($doc_id);

            if ($doc && $doc->status === 'pending_approval') {
                $this->make_current($doc_id);
                $this->db->where('id', (int) $doc_id);
                $this->db->update($this->doc_table, [
                    'reviewed_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } else {
            $this->db->where('id', (int) $doc_id);
            $this->db->where('status', 'pending_approval');
            $this->db->update($this->doc_table, ['status' => 'rejected', 'reviewed_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * Promote a document to the current record for its staff member and type, retire
     * the one it replaces, and write its values back to the master staff record.
     *
     * @param int $id
     */
    private function make_current($id)
    {
        $doc = $this->get($id);

        if (!$doc) {
            return;
        }

        // Supersede the previous current record of the same type.
        $this->db->where('staff_id', (int) $doc->staff_id);
        $this->db->where('document_type_id', (int) $doc->document_type_id);
        $this->db->where('status', 'current');
        $this->db->where('id !=', (int) $id);
        $this->db->update($this->doc_table, ['status' => 'superseded']);

        $this->db->where('id', (int) $id);
        $this->db->update($this->doc_table, ['status' => 'current', 'applied_to_master' => 1]);

        $this->write_back_to_staff($doc);
    }

    /**
     * Update the tblstaff columns this document type is mapped to.
     *
     * The column names come from the document type, and the type screen only lets an
     * administrator choose from a whitelist, so the column names reaching this UPDATE
     * are always safe. A type that maps nothing (e.g. an insurance card) writes back
     * nothing.
     *
     * @param object $doc
     */
    private function write_back_to_staff($doc)
    {
        $update  = [];
        $allowed = $this->hr_masters_model->staff_document_fields();

        if (!empty($doc->staff_number_field)
            && isset($allowed[$doc->staff_number_field])
            && $doc->document_number !== null) {
            $update[$doc->staff_number_field] = $doc->document_number;
        }

        if (!empty($doc->staff_expiry_field)
            && isset($allowed[$doc->staff_expiry_field])
            && $doc->expiry_date !== null) {
            $update[$doc->staff_expiry_field] = $doc->expiry_date;
        }

        if (count($update) === 0) {
            return;
        }

        $this->db->where('staffid', (int) $doc->staff_id);
        $this->db->update(db_prefix() . 'staff', $update);
    }

    /* ================================================================ expiry */

    /**
     * Documents expiring within a window, or already expired, for the HR dashboard.
     *
     * Reads the current record of each type. reminder_days is per type, so a passport
     * can warn 90 days out while an insurance card warns 30.
     *
     * @param int $default_window_days used when a type has no reminder set
     *
     * @return array
     */
    public function get_expiring($default_window_days = 90)
    {
        if (!$this->db->table_exists($this->doc_table)) {
            return [];
        }

        $this->db->select($this->doc_table . '.*,'
            . ' ' . $this->type_table . '.name as type_name, ' . $this->type_table . '.reminder_days,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as staff_name,'
            . ' DATEDIFF(' . $this->doc_table . '.expiry_date, CURDATE()) as days_left');
        $this->db->join($this->type_table,
            $this->type_table . '.id = ' . $this->doc_table . '.document_type_id', 'left');
        $this->db->join(db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->doc_table . '.staff_id', 'left');
        $this->db->where($this->doc_table . '.status', 'current');
        $this->db->where($this->doc_table . '.expiry_date IS NOT NULL', null, false);

        // Inside the type's own reminder window, or already past.
        $this->db->where('DATEDIFF(' . $this->doc_table . '.expiry_date, CURDATE()) <= '
            . 'COALESCE(' . $this->type_table . '.reminder_days, ' . (int) $default_window_days . ')', null, false);

        $this->db->order_by($this->doc_table . '.expiry_date', 'ASC');

        return $this->db->get($this->doc_table)->result_array();
    }

    /**
     * Send expiry reminders. Called from the cron.
     *
     * Each employee gets one reminder per document per stage (upcoming, then expired),
     * deduped through tblemp_document_alert keyed on the expiry date - so renewing a
     * document re-arms the reminder, and re-running the cron does not re-send.
     *
     * @param array $hr_recipient_ids staff ids to copy, e.g. HR
     *
     * @return int reminders sent
     */
    public function send_expiry_reminders($hr_recipient_ids = [])
    {
        $due  = $this->get_expiring(90);
        $sent = 0;

        foreach ($due as $doc) {
            $stage = (int) $doc['days_left'] < 0 ? 'expired' : 'upcoming';

            if ($this->already_alerted($doc, $stage)) {
                continue;
            }

            $message = $stage === 'expired'
                ? _l('empdoc_alert_expired', [$doc['type_name'], _d($doc['expiry_date'])])
                : _l('empdoc_alert_upcoming', [$doc['type_name'], (int) $doc['days_left'], _d($doc['expiry_date'])]);

            // The employee, so they can renew and upload the new one themselves.
            add_notification([
                'description' => $message,
                'touserid'    => (int) $doc['staff_id'],
                'link'        => 'employee_documents/mine',
                'fromcompany' => 1,
            ]);

            // HR, so they can chase it.
            foreach ($hr_recipient_ids as $hr_id) {
                if (!is_numeric($hr_id)) {
                    continue;
                }

                add_notification([
                    'description' => $doc['staff_name'] . ' - ' . $message,
                    'touserid'    => (int) $hr_id,
                    'link'        => 'employee_documents/expiring',
                    'fromcompany' => 1,
                ]);
            }

            $this->mark_alerted($doc, $stage);
            $sent++;
        }

        return $sent;
    }

    /**
     * @param array  $doc
     * @param string $stage
     *
     * @return bool
     */
    private function already_alerted($doc, $stage)
    {
        $this->db->where('staff_id', (int) $doc['staff_id']);
        $this->db->where('document_type_id', (int) $doc['document_type_id']);
        $this->db->where('expiry_date', $doc['expiry_date']);
        $this->db->where('alert_stage', $stage);

        return (int) $this->db->count_all_results($this->alert_table) > 0;
    }

    /**
     * @param array  $doc
     * @param string $stage
     */
    private function mark_alerted($doc, $stage)
    {
        $this->db->insert($this->alert_table, [
            'staff_id'         => (int) $doc['staff_id'],
            'document_type_id' => (int) $doc['document_type_id'],
            'document_id'      => (int) $doc['id'],
            'expiry_date'      => $doc['expiry_date'],
            'alert_stage'      => $stage,
            'sent_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    /* ================================================================ helpers */

    /**
     * @param int $staff_id
     *
     * @return string the disk folder, created if missing
     */
    public function upload_path($staff_id)
    {
        $path = $this->upload_root . (int) $staff_id . '/';

        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete($id)
    {
        $doc = $this->get($id);

        if (!$doc) {
            return false;
        }

        // Remove the file from disk if there is one.
        if (!empty($doc->file_name)) {
            $path = $this->upload_path($doc->staff_id) . $doc->file_name;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->db->where('id', (int) $id)->delete($this->doc_table);

        return true;
    }

    /**
     * @param int $doc_id
     */
    private function notify_hr_of_pending($doc_id)
    {
        $doc = $this->get($doc_id);

        if (!$doc) {
            return;
        }

        // Staff who can review HR records.
        $this->db->select('staffid');
        $this->db->where('admin', 1);
        $this->db->where('active', 1);
        $admins = $this->db->get(db_prefix() . 'staff')->result_array();

        foreach ($admins as $admin) {
            add_notification([
                'description' => _l('empdoc_pending_review', [$doc->staff_name, $doc->type_name]),
                'touserid'    => (int) $admin['staffid'],
                'link'        => 'employee_documents/expiring',
                'fromcompany' => 1,
            ]);
        }
    }
}
