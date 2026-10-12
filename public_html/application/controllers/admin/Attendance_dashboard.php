
<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Daily attendance dashboard for HR / Admin.
 *
 * Green attended, orange not expected (approved leave or a company holiday), red
 * expected in but nothing recorded. The red list is the reason the screen exists: it
 * has to be obvious the same day, while somebody can still be called.
 *
 * Appears under the existing "Timesheets & Leave" sidebar group.
 */
class Attendance_dashboard extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('attendance_geo_model');
    }

    private function check_permission()
    {
        if (is_admin()) {
            return;
        }

        // Anyone who can see attendance can see the overview of it.
        if (has_permission('attendance_management', '', 'view')
            || has_permission('report_management', '', 'view')) {
            return;
        }

        access_denied('attendance_dashboard');
    }

    /**
     * GET admin/attendance_dashboard?date=Y-m-d&department=ID
     */
    public function index()
    {
        $this->check_permission();

        $date = $this->input->get('date');

        // Guard the input: an unparseable date would otherwise silently become
        // 1970-01-01 and the screen would look like nobody ever attends.
        if (empty($date) || strtotime($date) === false) {
            $date = date('Y-m-d');
        } else {
            $date = date('Y-m-d', strtotime($date));
        }

        $department = $this->input->get('department');
        $department = is_numeric($department) ? (int) $department : null;

        $this->load->model('departments_model');

        $data['overview']    = $this->attendance_geo_model->get_daily_overview($date, $department);
        $data['departments'] = $this->departments_model->get();
        $data['date']        = $date;
        $data['department']  = $department;

        // Anyone still checked in from an earlier day, which is the visible symptom of
        // a forgotten check-out.
        $data['unclosed'] = $this->attendance_geo_model->get_unclosed_punches($date);

        $data['title'] = _l('attendance_dashboard');

        $this->load->view('admin/attendance_dashboard/index', $data);
    }
}
