
<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Attendance rules master data.
 *
 * Where overtime rates, the comp-off policy and leave entitlements are configured. The
 * application ships none of these values: they are company policy, so they are entered
 * here and read as data. Until they are entered, overtime and comp-off stay inert
 * rather than applying a guessed default to somebody's pay.
 *
 * Tabbed with ?group=, matching the timesheets module's own settings screen.
 */
class Attendance_rules extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('attendance_rules_model');
    }

    /**
     * These are pay-affecting rules, so the gate is deliberately tighter than the
     * attendance screens: admin, or explicit payroll/timesheet settings rights.
     */
    private function check_permission()
    {
        if (is_admin()) {
            return;
        }

        if (has_permission('timesheet_settings', '', 'view')
            || has_permission('hrp_setting', '', 'view')) {
            return;
        }

        access_denied('attendance_rules');
    }

    public function index()
    {
        $this->check_permission();

        $group = $this->input->get('group');
        $valid = ['overtime', 'comp_off', 'leave_entitlement'];

        if (!in_array($group, $valid, true)) {
            $group = 'overtime';
        }

        $data['group'] = $group;
        $data['title'] = _l('attendance_rules');

        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();

        if ($group === 'overtime') {
            $data['rules'] = $this->attendance_rules_model->get_overtime_rules();
        } elseif ($group === 'comp_off') {
            $data['setting'] = $this->attendance_rules_model->get_comp_off_setting();
        } else {
            $year = $this->input->get('year');
            $data['year'] = is_numeric($year) ? (int) $year : (int) date('Y');

            $data['entitlements'] = $this->attendance_rules_model->get_entitlements($data['year']);
            $data['leave_types']  = $this->attendance_rules_model->get_leave_types();
            $data['staff']        = $this->staff_model->get('', ['active' => 1]);
        }

        $this->load->view('admin/attendance_rules/manage', $data);
    }

    /* -------------------------------------------------------------- overtime */

    /**
     * POST admin/attendance_rules/save_overtime
     */
    public function save_overtime()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('attendance_rules?group=overtime'));
        }

        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int) $post['id'] : null;
        unset($post['id']);

        $result = $this->attendance_rules_model->save_overtime_rule($post, $id);

        if ($result) {
            set_alert('success', $id ? _l('attendance_rule_updated') : _l('attendance_rule_added'));
        } else {
            // The model rejects a blank name and a coefficient of zero or less,
            // because either would look configured but pay nothing.
            set_alert('warning', _l('attendance_rule_invalid'));
        }

        redirect(admin_url('attendance_rules?group=overtime'));
    }

    /**
     * GET admin/attendance_rules/delete_overtime/{id}
     */
    public function delete_overtime($id)
    {
        $this->check_permission();

        if (is_numeric($id) && $this->attendance_rules_model->delete_overtime_rule($id)) {
            set_alert('success', _l('deleted'));
        } else {
            set_alert('warning', _l('problem_deleting'));
        }

        redirect(admin_url('attendance_rules?group=overtime'));
    }

    /* -------------------------------------------------------------- comp-off */

    /**
     * POST admin/attendance_rules/save_comp_off
     */
    public function save_comp_off()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('attendance_rules?group=comp_off'));
        }

        if ($this->attendance_rules_model->save_comp_off_setting($this->input->post())) {
            set_alert('success', _l('attendance_comp_off_saved'));
        } else {
            set_alert('warning', _l('attendance_comp_off_invalid'));
        }

        redirect(admin_url('attendance_rules?group=comp_off'));
    }

    /* ----------------------------------------------------- leave entitlement */

    /**
     * POST admin/attendance_rules/save_entitlement
     */
    public function save_entitlement()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('attendance_rules?group=leave_entitlement'));
        }

        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int) $post['id'] : null;
        unset($post['id']);

        $year = !empty($post['period_year']) ? (int) $post['period_year'] : (int) date('Y');

        if ($this->attendance_rules_model->save_entitlement($post, $id)) {
            set_alert('success', $id ? _l('attendance_entitlement_updated') : _l('attendance_entitlement_added'));
        } else {
            // Most likely a missing cut-off date, which the record is meaningless
            // without, or a duplicate for the same employee/type/year.
            set_alert('warning', _l('attendance_entitlement_invalid'));
        }

        redirect(admin_url('attendance_rules?group=leave_entitlement&year=' . $year));
    }

    /**
     * GET admin/attendance_rules/delete_entitlement/{id}
     */
    public function delete_entitlement($id)
    {
        $this->check_permission();

        if (is_numeric($id) && $this->attendance_rules_model->delete_entitlement($id)) {
            set_alert('success', _l('deleted'));
        } else {
            set_alert('warning', _l('problem_deleting'));
        }

        redirect(admin_url('attendance_rules?group=leave_entitlement'));
    }
}
