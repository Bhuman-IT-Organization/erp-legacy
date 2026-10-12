
<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project / site geolocation.
 *
 * Where the coordinates behind the optional attendance geofence are entered. A project
 * listed here without coordinates is not restricted - attendance against it is accepted
 * from anywhere - which is what keeps the feature opt-in per project.
 *
 * Appears under the existing "Timesheets & Leave" sidebar group rather than as a new
 * top-level area, registered in application/helpers/my_functions_helper.php.
 */
class Attendance_locations extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('attendance_geo_model');
    }

    /**
     * Gate on the same permission the module uses for its own workplace screen, so
     * whoever already administers attendance locations can administer these too.
     */
    private function check_permission()
    {
        if (is_admin()) {
            return;
        }

        if (has_permission('table_workplace_management', '', 'view')
            || has_permission('attendance_management', '', 'view')) {
            return;
        }

        access_denied('attendance_locations');
    }

    /**
     * GET  admin/attendance_locations
     * POST admin/attendance_locations  (save one project's geofence)
     */
    public function index()
    {
        $this->check_permission();

        if ($this->input->post()) {
            $project_id = (int) $this->input->post('project_id');

            $saved = $this->attendance_geo_model->save_project_location($project_id, [
                'latitude'      => $this->input->post('latitude'),
                'longitude'     => $this->input->post('longitude'),
                'radius_metres' => $this->input->post('radius_metres'),
                'address'       => $this->input->post('address'),
                'active'        => $this->input->post('active') ? 1 : 0,
            ]);

            if ($saved) {
                // Clearing the coordinates removes the geofence rather than storing a
                // half-configured one, so say which happened.
                $cleared = $this->input->post('latitude') === '' || $this->input->post('longitude') === '';

                set_alert('success', $cleared
                    ? _l('attendance_location_cleared')
                    : _l('attendance_location_saved'));
            } else {
                set_alert('warning', _l('attendance_location_save_failed'));
            }

            redirect(admin_url('attendance_locations'));
        }

        // Every project, with its geofence where one exists, so the screen doubles as
        // an overview of which sites are covered.
        $this->db->select('id, name, status');
        $this->db->order_by('name', 'ASC');
        $projects = $this->db->get(db_prefix() . 'projects')->result_array();

        $locations = [];
        foreach ($this->attendance_geo_model->get_project_locations() as $row) {
            $locations[(int) $row['project_id']] = $row;
        }

        $data['projects']  = $projects;
        $data['locations'] = $locations;
        $data['title']     = _l('attendance_project_locations');

        $this->load->view('admin/attendance_locations/manage', $data);
    }

    /**
     * GET admin/attendance_locations/delete/{project_id}
     *
     * Removes the geofence, returning the project to unrestricted.
     */
    public function delete($project_id)
    {
        $this->check_permission();

        if (!is_numeric($project_id)) {
            redirect(admin_url('attendance_locations'));
        }

        // An empty coordinate pair is how the model expresses "no geofence".
        $this->attendance_geo_model->save_project_location($project_id, [
            'latitude'  => '',
            'longitude' => '',
        ]);

        set_alert('success', _l('attendance_location_cleared'));
        redirect(admin_url('attendance_locations'));
    }
}
