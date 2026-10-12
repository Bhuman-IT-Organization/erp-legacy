<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Attendance API Controller
 * 
 * Handles punch-in/out with face verification, attendance status, and history.
 * Mobile app must verify face THEN call punch endpoint.
 */
class Attendance extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('attendance_api_model');
        $this->load->model('face_model');
        $this->load->model('staff_model');
    }

    /**
     * GET /api/attendance/employees
     * 
     * Get list of all active employees for dropdown selection.
     * Returns staff_id, name, email, face_registered status.
     * Only admins can see all employees.
     */
    public function employees()
    {
        $token_data = $this->authenticate();

        // Only admins can list all employees
        if (!$token_data->is_admin) {
            $this->json_response([
                'status'  => false,
                'message' => 'Only administrators can access the employee list.',
            ], 403);
        }

        $staff_members = $this->staff_model->get('', ['active' => 1, 'is_not_staff' => 0]);

        $employees = [];
        foreach ($staff_members as $member) {
            $employees[] = [
                'staff_id'        => (int) $member['staffid'],
                'firstname'       => $member['firstname'],
                'lastname'        => $member['lastname'],
                'full_name'       => $member['firstname'] . ' ' . $member['lastname'],
                'email'           => $member['email'],
                'phonenumber'     => $member['phonenumber'] ?? '',
                'profile_image'   => $member['profile_image'] ?? null,
                'face_registered' => $this->face_model->has_face($member['staffid']),
            ];
        }

        $this->json_response([
            'status' => true,
            'data'   => [
                'total'     => count($employees),
                'employees' => $employees,
            ],
        ], 200);
    }

    /**
     * POST /api/attendance/punch_for
     * 
     * Admin marks attendance (login/logout) for a selected employee.
     * Used when admin selects employee from dropdown and punches on their behalf.
     * Allows N number of logins and logouts per day.
     *
     * Face verification gates the punch: the record is only inserted when the
     * supplied embedding matches the employee's registered face (cosine
     * similarity >= 0.6). A mismatch returns status false and inserts nothing.
     * 
     * Body: {
     *   "staff_id": 5,                       // required - employee to punch for
     *   "type": "in",                        // required - "in" or "out"
     *   "embedding": [0.123, -0.456, ...],   // required - face embedding, must match
     *   "project_id": 3,                     // optional - project they are working on
     *   "punch_time": "2026-08-15 09:15:00", // optional - custom time, defaults to now
     *   "latitude": 12.345678,               // optional GPS
     *   "longitude": 78.901234,              // optional GPS
     *   "device_id": "device-uuid",          // optional
     *   "note": "Marked by admin"            // optional
     * }
     */
    public function punch_for()
    {
        $this->require_post();
        $token_data = $this->authenticate();

        // Only admins can punch for others
        if (!$token_data->is_admin) {
            $this->json_response([
                'status'  => false,
                'message' => 'Only administrators can mark attendance for other employees.',
            ], 403);
        }

        $staff_id   = $this->api_input('staff_id');
        $type       = $this->api_input('type');
        $embedding  = $this->api_input('embedding');
        $project_id = $this->api_input('project_id');
        $punch_time = $this->api_input('punch_time');
        $latitude   = $this->api_input('latitude');
        $longitude  = $this->api_input('longitude');
        $device_id  = $this->api_input('device_id', '');
        $note       = $this->api_input('note', '');

        // Validate staff_id
        if (empty($staff_id) || !is_numeric($staff_id)) {
            $this->json_response([
                'status'  => false,
                'message' => 'Employee staff_id is required.',
            ], 422);
        }

        // Validate the employee exists and is active
        $employee = $this->staff_model->get((int) $staff_id);
        if (!$employee || $employee->active == 0) {
            $this->json_response([
                'status'  => false,
                'message' => 'Employee not found or inactive.',
            ], 404);
        }

        // Validate punch type
        if (!in_array($type, ['in', 'out'])) {
            $this->json_response([
                'status'  => false,
                'message' => 'Punch type must be "in" or "out".',
            ], 422);
        }

        // Face embedding is mandatory - the punch is gated on it
        if (empty($embedding) || !is_array($embedding)) {
            $this->json_response([
                'status'  => false,
                'message' => 'Face embedding is required for attendance.',
            ], 422);
        }

        // Resolve project if provided - an unknown project is ignored rather than rejected
        $resolved_project_id = null;
        if (!empty($project_id) && is_numeric($project_id)) {
            $this->load->model('projects_model');
            if ($this->projects_model->get((int) $project_id)) {
                $resolved_project_id = (int) $project_id;
            }
        }

        // Fall back to the current time if punch_time is missing or unparseable
        if (!empty($punch_time) && strtotime($punch_time) === false) {
            $punch_time = null;
        }

        // The employee must have a registered face to verify against
        if (!$this->face_model->has_face($staff_id)) {
            $this->json_response([
                'status'  => false,
                'message' => 'No face registered for this employee. Please register their face first.',
                'data'    => [
                    'matched'         => false,
                    'face_registered' => false,
                ],
            ], 400);
        }

        // Verify the face. A mismatch blocks the punch - nothing is inserted.
        $face_result = $this->face_model->verify($staff_id, $embedding, 0.6);

        if (!$face_result['matched']) {
            log_activity('API: Attendance Rejected - Face Not Matched [Staff ID: ' . $staff_id . ', Similarity: ' . $face_result['similarity'] . ', By Admin: ' . $token_data->staff_id . ', IP: ' . $this->input->ip_address() . ']');

            $this->json_response([
                'status'  => false,
                'message' => $face_result['error'] ?? 'Face did not match. Attendance not recorded.',
                'data'    => [
                    'matched'          => false,
                    'similarity'       => $face_result['similarity'],
                    'distance'         => $face_result['distance'] ?? null,
                    'threshold'        => 0.6,
                    'stored_dimension' => $face_result['stored_dimension'] ?? null,
                    'sent_dimension'   => $face_result['sent_dimension'] ?? null,
                    'registered_from'  => $face_result['source'] ?? null,
                ],
            ], 403);
        }

        // Project geofence, same rules as the self-service punch. A supervisor
        // recording attendance at a site still has to account for being away from it.
        $this->load->model('attendance_geo_model');
        $geo = $this->attendance_geo_model->check_project_geofence($resolved_project_id, $latitude, $longitude);

        $out_of_geofence = false;

        if ($geo->enforced && !$geo->inside) {
            $out_of_geofence = true;

            $reason_id          = $this->api_input('reason_id');
            $reason_description = $this->api_input('reason_description', '');
            $reason             = $this->attendance_geo_model->get_reason($reason_id);

            if (!$reason) {
                $this->json_response([
                    'status'  => false,
                    'message' => 'This punch is away from the project site. Choose a reason to continue.',
                    'data'    => [
                        'requires_reason' => true,
                        'out_of_geofence' => true,
                        'distance_metres' => $geo->distance_metres,
                        'radius_metres'   => $geo->radius_metres,
                        'reasons'         => $this->attendance_geo_model->get_reasons(),
                    ],
                ], 422);
            }

            if ((int) $reason->requires_description === 1 && trim((string) $reason_description) === '') {
                $this->json_response([
                    'status'  => false,
                    'message' => 'A description is required for "' . $reason->name . '".',
                    'data'    => [
                        'requires_reason'      => true,
                        'requires_description' => true,
                        'reason_id'            => (int) $reason->id,
                        'out_of_geofence'      => true,
                    ],
                ], 422);
            }
        }

        // Face matched - record the punch (no cooldown, N logins/logouts allowed).
        // recorded_by carries the supervisor, so attendance taken on someone's behalf
        // is distinguishable from a self-service punch.
        $punch_id = $this->attendance_api_model->punch([
            'staff_id'           => $staff_id,
            'project_id'         => $resolved_project_id,
            'type'               => $type,
            'punch_time'         => $punch_time ?: null,
            'latitude'           => $latitude,
            'longitude'          => $longitude,
            'device_id'          => $device_id,
            'face_similarity'    => $face_result['similarity'],
            'note'               => $note,
            'out_of_geofence'    => $out_of_geofence,
            'reason_id'          => $out_of_geofence ? (int) $this->api_input('reason_id') : null,
            'reason_description' => $out_of_geofence ? $this->api_input('reason_description', '') : null,
            'source'             => 'supervisor',
            'recorded_by'        => $token_data->staff_id,
        ]);

        if (!$punch_id) {
            $this->json_response([
                'status'  => false,
                'message' => 'Failed to record attendance.',
            ], 500);
        }

        log_activity('API: Admin Attendance Punch [Employee: ' . $staff_id . ', Type: ' . $type . ', Project: ' . ($resolved_project_id ?: 'N/A') . ', By Admin: ' . $token_data->staff_id . ', Similarity: ' . $face_result['similarity'] . ', IP: ' . $this->input->ip_address() . ']');

        $actual_punch_time = $punch_time ?: date('Y-m-d H:i:s');

        $this->json_response([
            'status'  => true,
            'message' => 'Attendance recorded for ' . trim($employee->firstname . ' ' . $employee->lastname) . '. Punch ' . $type . ' at ' . date('H:i:s', strtotime($actual_punch_time)) . '.',
            'data'    => [
                'punch_id'   => (int) $punch_id,
                'staff_id'   => (int) $staff_id,
                'type'       => $type,
                'punch_time' => $actual_punch_time,
                'matched'    => true,
                'similarity' => $face_result['similarity'],
            ],
        ], 201);
    }

    /**
     * GET /api/attendance/projects
     * 
     * Get list of active projects for dropdown selection.
     * Returns project id, name, and status.
     */
    public function projects()
    {
        $token_data = $this->authenticate();

        $this->load->model('projects_model');

        // Get all active projects (status: not finished/cancelled)
        // Status: 1=Not Started, 2=In Progress, 3=On Hold, 4=Finished, 5=Cancelled
        $this->db->select('id, name, status, start_date, deadline, clientid');
        $this->db->where_in('status', [1, 2, 3]); // Active projects only
        $this->db->order_by('name', 'ASC');
        $all_projects = $this->db->get(db_prefix() . 'projects')->result_array();

        // Site coordinates, so the app can show the fence and warn before punching
        // rather than being rejected afterwards. A project with no location has
        // geofenced = false and is never restricted.
        $this->load->model('attendance_geo_model');

        $projects = [];
        foreach ($all_projects as $p) {
            $location = $this->attendance_geo_model->get_project_location($p['id']);

            $projects[] = [
                'project_id' => (int) $p['id'],
                'name'       => $p['name'],
                'status'     => (int) $p['status'],
                'start_date' => $p['start_date'],
                'deadline'   => $p['deadline'],
                'geofenced'  => $location ? true : false,
                'location'   => $location ? [
                    'latitude'      => (float) $location->latitude,
                    'longitude'     => (float) $location->longitude,
                    'radius_metres' => (int) $location->radius_metres,
                    'address'       => $location->address,
                ] : null,
            ];
        }

        $this->json_response([
            'status' => true,
            'data'   => [
                'total'    => count($projects),
                'projects' => $projects,
            ],
        ], 200);
    }

    /**
     * POST /api/attendance/punch
     * 
     * Record attendance punch (in or out) with face verification.
     * Allows N number of logins/logouts per day.
     * 
     * Body: {
     *   "type": "in",                        // "in" or "out"
     *   "embedding": [0.123, -0.456, ...],   // face embedding for verification
     *   "project_id": 3,                     // optional - project being worked on
     *   "punch_time": "2026-08-15 09:15:00", // optional - defaults to now
     *   "latitude": 12.345678,               // optional GPS
     *   "longitude": 78.901234,              // optional GPS
     *   "device_id": "device-uuid",          // optional
     *   "note": "Working from office"        // optional
     * }
     */
    public function punch()
    {
        $this->require_post();
        $token_data = $this->authenticate();

        $staff_id   = $token_data->staff_id;
        $type       = $this->api_input('type');
        $embedding  = $this->api_input('embedding');
        $project_id = $this->api_input('project_id');
        $punch_time = $this->api_input('punch_time');
        $latitude   = $this->api_input('latitude');
        $longitude  = $this->api_input('longitude');
        $device_id  = $this->api_input('device_id', '');
        $note       = $this->api_input('note', '');
        $reason_id          = $this->api_input('reason_id');
        $reason_description = $this->api_input('reason_description', '');

        // Validate punch type
        if (!in_array($type, ['in', 'out'])) {
            $this->json_response([
                'status'  => false,
                'message' => 'Punch type must be "in" or "out".',
            ], 422);
        }

        // Validate face embedding is provided
        if (empty($embedding) || !is_array($embedding)) {
            $this->json_response([
                'status'  => false,
                'message' => 'Face embedding is required for attendance.',
            ], 422);
        }

        // Validate project if provided
        if (!empty($project_id)) {
            $this->load->model('projects_model');
            $project = $this->projects_model->get($project_id);
            if (!$project) {
                $this->json_response([
                    'status'  => false,
                    'message' => 'Project not found.',
                ], 404);
            }
        }

        // Validate punch_time format if provided
        if (!empty($punch_time)) {
            $parsed_time = strtotime($punch_time);
            if ($parsed_time === false) {
                $this->json_response([
                    'status'  => false,
                    'message' => 'Invalid punch_time format. Use Y-m-d H:i:s.',
                ], 422);
            }
        }

        // Check if user has registered face
        if (!$this->face_model->has_face($staff_id)) {
            $this->json_response([
                'status'  => false,
                'message' => 'No face registered. Please register your face before punching.',
                'data'    => ['face_registered' => false],
            ], 400);
        }

        // Verify face (threshold 0.6)
        $face_result = $this->face_model->verify($staff_id, $embedding, 0.6);

        if (!$face_result['matched']) {
            log_activity('API: Face Verification Failed for Attendance [Staff ID: ' . $staff_id . ', Similarity: ' . $face_result['similarity'] . ', IP: ' . $this->input->ip_address() . ']');

            $this->json_response([
                'status'  => false,
                'message' => $face_result['error'] ?? 'Face verification failed. Please try again.',
                'data'    => [
                    'face_matched'     => false,
                    'similarity'       => $face_result['similarity'],
                    'distance'         => $face_result['distance'] ?? null,
                    'stored_dimension' => $face_result['stored_dimension'] ?? null,
                    'sent_dimension'   => $face_result['sent_dimension'] ?? null,
                ],
            ], 403);
        }

        // Project geofence. Only applies when the project has coordinates configured -
        // a project without them is never location-restricted, which is what keeps the
        // feature optional. Outside the fence the punch is still accepted, but only
        // with a reason and a description, so off-site work stays attributed to the
        // project instead of being blocked or silently mis-recorded.
        $this->load->model('attendance_geo_model');
        $geo = $this->attendance_geo_model->check_project_geofence($project_id, $latitude, $longitude);

        $out_of_geofence = false;

        if ($geo->enforced && !$geo->inside) {
            $out_of_geofence = true;

            $reason = $this->attendance_geo_model->get_reason($reason_id);

            if (!$reason) {
                $this->json_response([
                    'status'  => false,
                    'message' => $geo->code === Attendance_geo_model::GEO_UNKNOWN
                        ? 'This project has a site location, so your position is required. '
                            . 'Enable location and try again, or choose a reason for working away from site.'
                        : 'You are away from the project site. Choose a reason to continue.',
                    'data'    => [
                        'requires_reason'  => true,
                        'out_of_geofence'  => true,
                        'distance_metres'  => $geo->distance_metres,
                        'radius_metres'    => $geo->radius_metres,
                        'reasons'          => $this->attendance_geo_model->get_reasons(),
                    ],
                ], 422);
            }

            // Every seeded reason requires a description, and the master table lets HR
            // change that per reason.
            if ((int) $reason->requires_description === 1 && trim((string) $reason_description) === '') {
                $this->json_response([
                    'status'  => false,
                    'message' => 'A description is required for "' . $reason->name . '".',
                    'data'    => [
                        'requires_reason'      => true,
                        'requires_description' => true,
                        'reason_id'            => (int) $reason->id,
                        'out_of_geofence'      => true,
                        'distance_metres'      => $geo->distance_metres,
                        'radius_metres'        => $geo->radius_metres,
                    ],
                ], 422);
            }
        }

        // Record the punch (no cooldown - N number of logins/logouts allowed).
        // Written to tblcheck_in_out, so it appears in the existing timekeeping
        // screens and flows to payroll through the module's timesheet generation.
        $punch_id = $this->attendance_api_model->punch([
            'staff_id'           => $staff_id,
            'project_id'         => !empty($project_id) ? (int) $project_id : null,
            'type'               => $type,
            'punch_time'         => $punch_time ?: null,
            'latitude'           => $latitude,
            'longitude'          => $longitude,
            'device_id'          => $device_id,
            'face_similarity'    => $face_result['similarity'],
            'note'               => $note,
            'out_of_geofence'    => $out_of_geofence,
            'reason_id'          => $out_of_geofence ? (int) $reason_id : null,
            'reason_description' => $out_of_geofence ? $reason_description : null,
            'source'             => 'mobile',
        ]);

        if (!$punch_id) {
            $this->json_response([
                'status'  => false,
                'message' => 'Failed to record attendance. Please try again.',
            ], 500);
        }

        $actual_punch_time = $punch_time ?: date('Y-m-d H:i:s');

        log_activity('API: Attendance Punch [Staff ID: ' . $staff_id . ', Type: ' . $type . ', Project: ' . ($project_id ?: 'N/A') . ', Similarity: ' . $face_result['similarity'] . ', IP: ' . $this->input->ip_address() . ']');

        // Return updated status
        $today_status = $this->attendance_api_model->get_today_status($staff_id);

        $this->json_response([
            'status'  => true,
            'message' => 'Attendance recorded. Punch ' . $type . ' at ' . date('H:i:s', strtotime($actual_punch_time)) . '.',
            'data'    => [
                'punch_id'       => (int) $punch_id,
                'type'           => $type,
                'punch_time'     => $actual_punch_time,
                'project_id'     => !empty($project_id) ? (int) $project_id : null,
                'face_similarity'=> $face_result['similarity'],
                'geofence'       => [
                    'enforced'        => $geo->enforced,
                    'inside'          => $geo->inside,
                    'distance_metres' => $geo->distance_metres,
                    'radius_metres'   => $geo->radius_metres,
                ],
                'out_of_geofence' => $out_of_geofence,
                'today'          => $today_status,
            ],
        ], 201);
    }

    /**
     * GET /api/attendance/reasons
     *
     * Reasons the app must offer when someone punches away from a project site.
     * Driven by tblattendance_reason, so HR can add or retire one without an app
     * release. requires_description tells the app to make the note mandatory.
     */
    public function reasons()
    {
        $this->authenticate();

        $this->load->model('attendance_geo_model');

        $reasons = [];
        foreach ($this->attendance_geo_model->get_reasons() as $reason) {
            $reasons[] = [
                'reason_id'            => (int) $reason['id'],
                'name'                 => $reason['name'],
                'slug'                 => $reason['slug'],
                'requires_description' => (bool) $reason['requires_description'],
            ];
        }

        $this->json_response([
            'status' => true,
            'data'   => [
                'total'   => count($reasons),
                'reasons' => $reasons,
            ],
        ], 200);
    }

    /**
     * GET /api/attendance/status
     * 
     * Get today's attendance status for the authenticated user
     */
    public function status()
    {
        $token_data = $this->authenticate();
        $staff_id = $token_data->staff_id;

        $today_status = $this->attendance_api_model->get_today_status($staff_id);

        $this->json_response([
            'status' => true,
            'data'   => $today_status,
        ], 200);
    }

    /**
     * GET /api/attendance/history
     * 
     * Get attendance history for the authenticated user
     * 
     * Query params: from_date, to_date, limit, offset
     */
    public function history()
    {
        $token_data = $this->authenticate();
        $staff_id = $token_data->staff_id;

        $from_date = $this->input->get('from_date');
        $to_date   = $this->input->get('to_date');
        $limit     = (int) ($this->input->get('limit') ?: 30);
        $offset    = (int) ($this->input->get('offset') ?: 0);

        // Cap limit
        if ($limit > 100) $limit = 100;

        $history = $this->attendance_api_model->get_history($staff_id, $from_date, $to_date, $limit, $offset);

        $this->json_response([
            'status' => true,
            'data'   => $history,
        ], 200);
    }

    /**
     * GET /api/attendance/summary
     * 
     * Get monthly summary for the authenticated user
     * 
     * Query params: month (Y-m format, e.g., 2026-08)
     */
    public function summary()
    {
        $token_data = $this->authenticate();
        $staff_id = $token_data->staff_id;

        $month = $this->input->get('month') ?: date('Y-m');

        // Validate month format
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->json_response([
                'status'  => false,
                'message' => 'Invalid month format. Use Y-m (e.g., 2026-08).',
            ], 422);
        }

        $from_date = $month . '-01';
        $to_date = date('Y-m-t', strtotime($from_date));

        $daily_summary = $this->attendance_api_model->get_daily_summary($staff_id, $from_date, $to_date);

        // Calculate monthly totals
        $total_days_present = count($daily_summary);
        $total_working_seconds = 0;

        foreach ($daily_summary as &$day) {
            $parts = explode(':', $day['working_hours']);
            $total_working_seconds += ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }

        $total_hours = floor($total_working_seconds / 3600);
        $total_minutes = floor(($total_working_seconds % 3600) / 60);

        $this->json_response([
            'status' => true,
            'data'   => [
                'month'              => $month,
                'total_days_present' => $total_days_present,
                'total_working_time' => sprintf('%02d:%02d', $total_hours, $total_minutes),
                'average_per_day'    => $total_days_present > 0
                    ? sprintf('%02d:%02d', floor(($total_working_seconds / $total_days_present) / 3600), floor((($total_working_seconds / $total_days_present) % 3600) / 60))
                    : '00:00',
                'daily'              => $daily_summary,
            ],
        ], 200);
    }
}
