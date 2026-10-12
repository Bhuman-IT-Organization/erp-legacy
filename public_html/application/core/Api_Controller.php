<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Base API Controller
 * Provides common functionality for all API endpoints.
 * Extends App_Controller to get full framework initialization (DB, helpers, etc.)
 */
class Api_Controller extends App_Controller
{
    protected $json_input = [];

    public function __construct()
    {
        parent::__construct();

        // Set CORS headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Content-Type: application/json; charset=UTF-8");

        // Handle preflight OPTIONS request
        if ($this->input->method() === 'options') {
            http_response_code(200);
            exit;
        }

        // Parse JSON input
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $this->json_input = json_decode($input, true) ?: [];
        }

        // Load the API token library
        $this->load->library('api_token');
    }

    /**
     * Send JSON response
     *
     * @param array $data Response data
     * @param int   $status_code HTTP status code
     */
    protected function json_response($data, $status_code = 200)
    {
        http_response_code($status_code);
        echo json_encode($data);
        exit;
    }

    /**
     * Validate that request method is POST
     */
    protected function require_post()
    {
        if ($this->input->method() !== 'post') {
            $this->json_response([
                'status'  => false,
                'message' => 'Method not allowed. Use POST.',
            ], 405);
        }
    }

    /**
     * Validate that request method is GET
     */
    protected function require_get()
    {
        if ($this->input->method() !== 'get') {
            $this->json_response([
                'status'  => false,
                'message' => 'Method not allowed. Use GET.',
            ], 405);
        }
    }

    /**
     * Validate token and return decoded user data
     *
     * @return object Token payload data
     */
    protected function authenticate()
    {
        $result = $this->api_token->validateToken();

        if ($result['status'] === false) {
            $this->json_response([
                'status'  => false,
                'message' => 'Unauthorized: ' . $result['message'],
            ], 401);
        }

        return $result['data'];
    }

    /**
     * Get input value from JSON body or POST data
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function api_input($key, $default = null)
    {
        if (isset($this->json_input[$key])) {
            return $this->json_input[$key];
        }

        $value = $this->input->post($key);
        return $value !== null ? $value : $default;
    }
}
