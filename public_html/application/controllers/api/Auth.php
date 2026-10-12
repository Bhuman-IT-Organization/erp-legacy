<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API Authentication Controller
 * 
 * Handles login for mobile/Angular/frontend applications.
 * Returns JWT token + user details + role + permissions on successful login.
 */
class Auth extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Authentication_model');
        $this->load->model('staff_model');
        $this->load->model('roles_model');
    }

    /**
     * POST /api/auth/login
     * 
     * Accepts: email, password (JSON body or form-data)
     * Returns: token, user details, role, permissions
     */
    public function login()
    {
        $this->require_post();

        $email = $this->api_input('email');
        $password = $this->api_input('password');

        // Validate required fields
        if (empty($email) || empty($password)) {
            $this->json_response([
                'status'  => false,
                'message' => 'Email and password are required.',
            ], 422);
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json_response([
                'status'  => false,
                'message' => 'Invalid email format.',
            ], 422);
        }

        // Attempt authentication against staff table
        $table = db_prefix() . 'staff';
        $this->db->where('email', $email);
        $user = $this->db->get($table)->row();

        if (!$user) {
            log_activity('API: Non Existing User Tried to Login [Email: ' . $email . ', IP: ' . $this->input->ip_address() . ']');
            $this->json_response([
                'status'  => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Check password
        if (!$user->password || !app_hasher()->CheckPassword($password, $user->password)) {
            log_activity('API: Failed Login Attempt [Email: ' . $email . ', IP: ' . $this->input->ip_address() . ']');
            $this->json_response([
                'status'  => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Check if account is active
        if ($user->active == 0) {
            log_activity('API: Inactive User Tried to Login [Email: ' . $email . ', IP: ' . $this->input->ip_address() . ']');
            $this->json_response([
                'status'  => false,
                'message' => 'Your account is inactive. Please contact the administrator.',
            ], 403);
        }

        // Get full staff data with permissions
        $staff = $this->staff_model->get($user->staffid);

        // Get role details
        $role = null;
        if (!empty($staff->role)) {
            $role = $this->roles_model->get($staff->role);
        }

        // Build permissions array
        $permissions = [];
        if (!empty($staff->permissions) && is_array($staff->permissions)) {
            foreach ($staff->permissions as $perm) {
                $feature = $perm['feature'];
                if (!isset($permissions[$feature])) {
                    $permissions[$feature] = [];
                }
                $permissions[$feature][] = $perm['capability'];
            }
        }

        // Generate JWT token
        $token_payload = [
            'staff_id' => $staff->staffid,
            'email'    => $staff->email,
            'is_admin' => $staff->admin == 1,
        ];

        $token = $this->api_token->generateToken($token_payload);

        if (!$token) {
            $this->json_response([
                'status'  => false,
                'message' => 'Failed to generate authentication token.',
            ], 500);
        }

        // Update last login info
        $this->db->where('staffid', $staff->staffid);
        $this->db->update(db_prefix() . 'staff', [
            'last_ip'       => $this->input->ip_address(),
            'last_login'    => date('Y-m-d H:i:s'),
            'last_activity' => date('Y-m-d H:i:s'),
        ]);

        log_activity('API: Staff Login Success [ID: ' . $staff->staffid . ', Email: ' . $email . ', IP: ' . $this->input->ip_address() . ']');

        // Build response
        $this->json_response([
            'status'  => true,
            'message' => 'Login successful.',
            'data'    => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $this->config->item('token_expire_time', 'jwt'),
                'user' => [
                    'staff_id'    => (int) $staff->staffid,
                    'email'       => $staff->email,
                    'firstname'   => $staff->firstname,
                    'lastname'    => $staff->lastname,
                    'full_name'   => $staff->firstname . ' ' . $staff->lastname,
                    'phonenumber' => $staff->phonenumber ?? '',
                    'profile_image' => $staff->profile_image ?? null,
                    'is_admin'    => (bool) $staff->admin,
                    'last_login'  => date('Y-m-d H:i:s'),
                    'active'      => (bool) $staff->active,
                ],
                'role' => $role ? [
                    'id'   => (int) $role->roleid,
                    'name' => $role->name,
                ] : null,
                'permissions' => $permissions,
            ],
        ], 200);
    }

    /**
     * POST /api/auth/refresh
     * 
     * Refresh an existing valid token to get a new one
     * Requires: Authorization header with valid token
     */
    public function refresh()
    {
        $this->require_post();

        $token_data = $this->authenticate();

        // Generate new token
        $token_payload = [
            'staff_id' => $token_data->staff_id,
            'email'    => $token_data->email,
            'is_admin' => $token_data->is_admin,
        ];

        $token = $this->api_token->generateToken($token_payload);

        if (!$token) {
            $this->json_response([
                'status'  => false,
                'message' => 'Failed to refresh token.',
            ], 500);
        }

        $this->json_response([
            'status'  => true,
            'message' => 'Token refreshed successfully.',
            'data'    => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_in' => $this->config->item('token_expire_time', 'jwt'),
            ],
        ], 200);
    }

    /**
     * GET /api/auth/me
     * 
     * Get current authenticated user details
     * Requires: Authorization header with valid token
     */
    public function me()
    {
        $token_data = $this->authenticate();

        $staff = $this->staff_model->get($token_data->staff_id);

        if (!$staff) {
            $this->json_response([
                'status'  => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Get role details
        $role = null;
        if (!empty($staff->role)) {
            $role = $this->roles_model->get($staff->role);
        }

        // Build permissions array
        $permissions = [];
        if (!empty($staff->permissions) && is_array($staff->permissions)) {
            foreach ($staff->permissions as $perm) {
                $feature = $perm['feature'];
                if (!isset($permissions[$feature])) {
                    $permissions[$feature] = [];
                }
                $permissions[$feature][] = $perm['capability'];
            }
        }

        $this->json_response([
            'status' => true,
            'data'   => [
                'user' => [
                    'staff_id'    => (int) $staff->staffid,
                    'email'       => $staff->email,
                    'firstname'   => $staff->firstname,
                    'lastname'    => $staff->lastname,
                    'full_name'   => $staff->firstname . ' ' . $staff->lastname,
                    'phonenumber' => $staff->phonenumber ?? '',
                    'profile_image' => $staff->profile_image ?? null,
                    'is_admin'    => (bool) $staff->admin,
                    'last_login'  => $staff->last_login,
                    'active'      => (bool) $staff->active,
                ],
                'role' => $role ? [
                    'id'   => (int) $role->roleid,
                    'name' => $role->name,
                ] : null,
                'permissions' => $permissions,
            ],
        ], 200);
    }
}
