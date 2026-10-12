<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Face Registration & Verification API
 * 
 * Handles face template (embedding) registration and verification.
 * The mobile app generates 128-dim face embeddings using TFLite MobileFaceNet.
 * Server only stores/compares embeddings — no images stored.
 */
class Face extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('face_model');
    }

    /**
     * POST /api/face/register
     * 
     * Register face embedding for the authenticated user.
     * 
     * Body: {
     *   "embedding": [0.123, -0.456, ...],  // 128-float array
     *   "device_id": "device-uuid"           // optional
     * }
     */
    public function register()
    {
        $this->require_post();
        $token_data = $this->authenticate();

        $embedding = $this->api_input('embedding');
        $device_id = $this->api_input('device_id', '');

        // Validate embedding
        if (empty($embedding) || !is_array($embedding)) {
            $this->json_response([
                'status'  => false,
                'message' => 'Face embedding is required and must be an array.',
            ], 422);
        }

        // Validate embedding dimension (MobileFaceNet = 128, FaceNet = 512)
        $valid_dimensions = [128, 192, 512];
        if (!in_array(count($embedding), $valid_dimensions)) {
            $this->json_response([
                'status'  => false,
                'message' => 'Invalid embedding dimension. Expected 128, 192, or 512 floats.',
            ], 422);
        }

        // Validate all values are numeric
        foreach ($embedding as $val) {
            if (!is_numeric($val)) {
                $this->json_response([
                    'status'  => false,
                    'message' => 'Embedding values must be numeric.',
                ], 422);
            }
        }

        $staff_id = $token_data->staff_id;

        // Register the face
        $result = $this->face_model->register($staff_id, $embedding, $device_id);

        if ($result) {
            log_activity('API: Face Registered [Staff ID: ' . $staff_id . ', IP: ' . $this->input->ip_address() . ']');

            $this->json_response([
                'status'  => true,
                'message' => 'Face registered successfully.',
                'data'    => [
                    'template_id'    => (int) $result,
                    'embedding_size' => count($embedding),
                    'registered_at'  => date('Y-m-d H:i:s'),
                ],
            ], 201);
        }

        $this->json_response([
            'status'  => false,
            'message' => 'Failed to register face. Please try again.',
        ], 500);
    }

    /**
     * POST /api/face/verify
     * POST /api/face/identify   (alias - same handler, clearer name for 1:N)
     *
     * Identify a face. By default this is a GLOBAL 1:N search: the face alone decides
     * who it is, and the matched employee is returned. That is what a shared device
     * needs, because at the point the camera fires nobody has said who they are yet.
     *
     * Global (default) - the body carries nothing but the face:
     *   {
     *     "embedding": [0.123, -0.456, ...],   // required, 128 / 192 / 512 floats
     *     "threshold": 0.6                      // optional, default 0.6
     *   }
     *   -> data.employee holds the matched employee, or matched = false.
     *
     * One-to-one, when you already know who you expect:
     *   { "embedding": [...], "mode": "self" }        // against the token's own face
     *   { "embedding": [...], "staff_id": 42 }        // against a named employee
     *   Checking someone other than yourself needs an admin token, matching delete().
     *
     * A token is still required. The body no longer identifies anybody, but the caller
     * must: an open 1:N face search would let anyone submit embeddings and read back
     * employee names, emails and phone numbers, and probe until something matched. For
     * a shared kiosk, issue it a dedicated staff account and use that account's token.
     */
    public function verify()
    {
        $this->require_post();
        $token_data = $this->authenticate();

        $embedding = $this->api_input('embedding');
        $threshold = $this->api_input('threshold', 0.6);
        $mode      = $this->api_input('mode');
        $staff_id  = $this->api_input('staff_id');

        if (empty($embedding) || !is_array($embedding)) {
            $this->json_response([
                'status'  => false,
                'message' => 'Face embedding is required and must be an array.',
            ], 422);
        }

        foreach ($embedding as $val) {
            if (!is_numeric($val)) {
                $this->json_response([
                    'status'  => false,
                    'message' => 'Embedding values must be numeric.',
                ], 422);
            }
        }

        $threshold = (float) $threshold;

        if ($threshold <= 0 || $threshold > 1) {
            $this->json_response([
                'status'  => false,
                'message' => 'Threshold must be greater than 0 and no more than 1.',
            ], 422);
        }

        // ---- one-to-one, only when the caller asked for it -------------------
        if ($mode === 'self' || !empty($staff_id)) {
            $target = $mode === 'self' ? (int) $token_data->staff_id : (int) $staff_id;

            if ($target !== (int) $token_data->staff_id && !$token_data->is_admin) {
                $this->json_response([
                    'status'  => false,
                    'message' => 'Only administrators can verify another employee\'s face.',
                ], 403);
            }

            $this->verify_one_to_one($target, $embedding, $threshold);
        }

        // ---- global 1:N identification --------------------------------------
        $result = $this->face_model->identify($embedding, $threshold);

        if (isset($result['error'])) {
            $this->json_response([
                'status'  => false,
                'message' => $result['error'],
                'data'    => [
                    'matched'           => false,
                    'probe_dimension'   => $result['probe_dimension'],
                    'total_templates'   => $result['total_templates'],
                    'compared'          => $result['compared'],
                    'skipped_dimension' => $result['skipped_dimension'],
                ],
            ], $result['total_templates'] === 0 ? 404 : 400);
        }

        // Two people too close together to separate. Refused rather than guessed.
        if (!empty($result['ambiguous'])) {
            log_activity('API: Face Identify Ambiguous [Top: ' . $result['staff_id']
                . ', Runner-up: ' . $result['runner_up']['staff_id']
                . ', Margin: ' . $result['margin'] . ', IP: ' . $this->input->ip_address() . ']');

            $this->json_response([
                'status'  => false,
                'message' => 'More than one employee matched this face too closely to tell apart. '
                    . 'Please try again, or verify against a specific employee.',
                'data'    => [
                    'matched'    => false,
                    'ambiguous'  => true,
                    'similarity' => $result['similarity'],
                    'margin'     => $result['margin'],
                    'threshold'  => $threshold,
                    'compared'   => $result['compared'],
                ],
            ], 409);
        }

        if (!$result['matched']) {
            $this->json_response([
                'status'  => true,
                'message' => 'No employee matched this face.',
                'data'    => [
                    'matched'           => false,
                    'employee'          => null,
                    'best_similarity'   => $result['similarity'],
                    'threshold'         => $threshold,
                    'probe_dimension'   => $result['probe_dimension'],
                    'compared'          => $result['compared'],
                    'skipped_dimension' => $result['skipped_dimension'],
                    'total_templates'   => $result['total_templates'],
                ],
            ], 200);
        }

        $employee = $this->employee_payload($result['staff_id']);

        if (!$employee) {
            // The template matched but the staff row has gone or been deactivated
            // between the search and now.
            $this->json_response([
                'status'  => false,
                'message' => 'The matched employee record is no longer available.',
                'data'    => ['matched' => false],
            ], 404);
        }

        log_activity('API: Face Identified [Staff ID: ' . $result['staff_id']
            . ', Similarity: ' . $result['similarity']
            . ', By: ' . $token_data->staff_id
            . ', IP: ' . $this->input->ip_address() . ']');

        $this->json_response([
            'status'  => true,
            'message' => 'Face matched ' . $employee['full_name'] . '.',
            'data'    => [
                'matched'           => true,
                'employee'          => $employee,
                'similarity'        => $result['similarity'],
                'distance'          => $result['distance'],
                'threshold'         => $threshold,
                'margin'            => $result['margin'],
                'registered_from'   => $result['source'],
                'registered_at'     => $result['registered_at'],
                'probe_dimension'   => $result['probe_dimension'],
                'compared'          => $result['compared'],
                'skipped_dimension' => $result['skipped_dimension'],
                'total_templates'   => $result['total_templates'],
            ],
        ], 200);
    }

    /**
     * Alias so callers can be explicit about wanting a 1:N search.
     */
    public function identify()
    {
        $this->verify();
    }

    /**
     * The original one-to-one comparison, unchanged in behaviour.
     *
     * Sends its own response and does not return.
     *
     * @param int   $staff_id
     * @param array $embedding
     * @param float $threshold
     */
    private function verify_one_to_one($staff_id, $embedding, $threshold)
    {
        if (!$this->face_model->has_face($staff_id)) {
            $this->json_response([
                'status'  => false,
                'message' => 'No face registered. Please register your face first.',
                'data'    => ['face_registered' => false],
            ], 404);
        }

        $result = $this->face_model->verify($staff_id, $embedding, $threshold);

        if (isset($result['error'])) {
            $this->json_response([
                'status'  => false,
                'message' => $result['error'],
                'data'    => [
                    'matched'          => false,
                    'stored_dimension' => $result['stored_dimension'] ?? null,
                    'sent_dimension'   => $result['sent_dimension'] ?? null,
                ],
            ], 400);
        }

        $this->json_response([
            'status'  => true,
            'message' => $result['matched'] ? 'Face verified successfully.' : 'Face verification failed.',
            'data'    => [
                'mode'             => 'one_to_one',
                'matched'          => $result['matched'],
                'employee'         => $result['matched'] ? $this->employee_payload($staff_id) : null,
                'similarity'       => $result['similarity'],
                'distance'         => $result['distance'],
                'threshold'        => $threshold,
                'stored_dimension' => $result['stored_dimension'],
                'sent_dimension'   => $result['sent_dimension'],
                'registered_from'  => $result['source'],
            ],
        ], 200);
    }

    /**
     * Employee details for a matched face.
     *
     * Deliberately a fixed list of fields rather than the whole staff row: that row
     * holds the password hash, two factor secrets, passport and Emirates ID numbers and
     * bank account details, none of which belong in a response to a face scan.
     *
     * @param int $staff_id
     *
     * @return array|null Null when the employee is missing or inactive
     */
    private function employee_payload($staff_id)
    {
        $staff_id = (int) $staff_id;

        $this->db->select('staffid, staff_identifi, email, firstname, lastname, phonenumber,'
            . ' profile_image, job_position, role, admin, active, status_work');
        $this->db->where('staffid', $staff_id);
        $staff = $this->db->get(db_prefix() . 'staff')->row();

        if (!$staff || (int) $staff->active !== 1) {
            return null;
        }

        // Role name
        $role_name = null;
        if (!empty($staff->role)) {
            $this->db->select('name');
            $this->db->where('roleid', $staff->role);
            $role = $this->db->get(db_prefix() . 'roles')->row();
            $role_name = $role ? $role->name : null;
        }

        // Job position, guarded because the HR module supplies that table.
        $job_position = null;
        if (!empty($staff->job_position) && $this->db->table_exists(db_prefix() . 'hr_job_position')) {
            $this->db->select('position_name');
            $this->db->where('position_id', $staff->job_position);
            $position = $this->db->get(db_prefix() . 'hr_job_position')->row();
            $job_position = $position ? $position->position_name : null;
        }

        // Departments
        $departments = [];
        $this->db->select(db_prefix() . 'departments.departmentid, ' . db_prefix() . 'departments.name');
        $this->db->join(
            db_prefix() . 'departments',
            db_prefix() . 'departments.departmentid = ' . db_prefix() . 'staff_departments.departmentid',
            'inner'
        );
        $this->db->where('staffid', $staff_id);
        foreach ($this->db->get(db_prefix() . 'staff_departments')->result() as $d) {
            $departments[] = ['id' => (int) $d->departmentid, 'name' => $d->name];
        }

        $payload = [
            'staff_id'      => (int) $staff->staffid,
            // The human-facing employee code, when one has been entered.
            'employee_id'   => !empty($staff->staff_identifi) ? $staff->staff_identifi : null,
            'firstname'     => $staff->firstname,
            'lastname'      => $staff->lastname,
            'full_name'     => trim($staff->firstname . ' ' . $staff->lastname),
            'email'         => $staff->email,
            'phonenumber'   => $staff->phonenumber ?: '',
            'profile_image' => $staff->profile_image ?: null,
            'job_position'  => $job_position,
            'departments'   => $departments,
            'role'          => $role_name,
            'is_admin'      => (bool) $staff->admin,
            'active'        => true,
            'status_work'   => $staff->status_work ?: null,
        ];

        // Today's attendance, so a kiosk knows whether to offer punch in or punch out
        // without a second round trip.
        $this->load->model('attendance_api_model');
        $payload['attendance_today'] = $this->attendance_api_model->get_today_status($staff_id);

        return $payload;
    }

    /**
     * GET /api/face/status
     * 
     * Check if the authenticated user has a registered face.
     */
    public function status()
    {
        $token_data = $this->authenticate();
        $staff_id = $token_data->staff_id;

        $has_face = $this->face_model->has_face($staff_id);
        $template = $has_face ? $this->face_model->get_template($staff_id) : null;

        $this->json_response([
            'status' => true,
            'data'   => [
                'face_registered' => $has_face,
                'registered_at'   => $template ? $template->registered_at : null,
                'device_id'       => $template ? $template->device_id : null,
            ],
        ], 200);
    }

    /**
     * DELETE /api/face/delete
     * 
     * Remove registered face template (admin or self)
     */
    public function delete()
    {
        $token_data = $this->authenticate();
        $staff_id = $token_data->staff_id;

        $target_staff_id = $this->api_input('staff_id');

        // Only admins can delete other users' faces
        if ($target_staff_id && $target_staff_id != $staff_id) {
            if (!$token_data->is_admin) {
                $this->json_response([
                    'status'  => false,
                    'message' => 'Only administrators can delete other users\' face data.',
                ], 403);
            }
            $staff_id = (int) $target_staff_id;
        }

        $deleted = $this->face_model->delete_template($staff_id);

        $this->json_response([
            'status'  => true,
            'message' => $deleted ? 'Face template deleted.' : 'No face template found.',
        ], 200);
    }
}
