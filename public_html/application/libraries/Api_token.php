<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'third_party/php-jwt/JWT.php';
require_once APPPATH . 'third_party/php-jwt/BeforeValidException.php';
require_once APPPATH . 'third_party/php-jwt/ExpiredException.php';
require_once APPPATH . 'third_party/php-jwt/SignatureInvalidException.php';

use App\Firebase\JWT\JWT;

/**
 * API Token Library
 * Handles JWT token generation and validation for REST API authentication
 */
class Api_token
{
    protected $CI;
    protected $token_key;
    protected $token_algorithm;
    protected $token_header;
    protected $token_expire_time;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->config->load('jwt', true);

        $this->token_key = $this->CI->config->item('jwt_key', 'jwt');
        $this->token_algorithm = $this->CI->config->item('jwt_algorithm', 'jwt');
        $this->token_header = $this->CI->config->item('token_header', 'jwt');
        $this->token_expire_time = $this->CI->config->item('token_expire_time', 'jwt');
    }

    /**
     * Generate a JWT token
     *
     * @param array $data Payload data to encode in the token
     * @return string|false
     */
    public function generateToken($data = null)
    {
        if ($data && is_array($data)) {
            $time = time();
            $data['iat'] = $time;
            $data['exp'] = $time + $this->token_expire_time;

            try {
                return JWT::encode($data, $this->token_key, $this->token_algorithm);
            } catch (\Exception $e) {
                log_message('error', 'JWT Token Generation Error: ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Validate token from request headers
     *
     * @return array ['status' => bool, 'data' => object|null, 'message' => string]
     */
    public function validateToken()
    {
        $token = $this->getBearerToken();

        if (!$token) {
            return ['status' => false, 'message' => 'Token not provided'];
        }

        try {
            $decoded = JWT::decode($token, $this->token_key, [$this->token_algorithm]);

            if (!empty($decoded) && is_object($decoded)) {
                return ['status' => true, 'data' => $decoded];
            }

            return ['status' => false, 'message' => 'Invalid token data'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Extract Bearer token from Authorization header
     * Supports multiple fallback methods for cPanel/shared hosting compatibility
     *
     * @return string|null
     */
    private function getBearerToken()
    {
        // Method 1: CI's request_headers()
        $headers = $this->CI->input->request_headers();

        foreach ($headers as $header_name => $header_value) {
            if (strtolower(trim($header_name)) === strtolower(trim($this->token_header))) {
                if (preg_match('/Bearer\s(\S+)/', $header_value, $matches)) {
                    return $matches[1];
                }
                return $header_value;
            }
        }

        // Method 2: Check HTTP_AUTHORIZATION (set by .htaccess RewriteRule)
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                return $matches[1];
            }
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Method 3: Check REDIRECT_HTTP_AUTHORIZATION (some cPanel/CGI setups)
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s(\S+)/', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
                return $matches[1];
            }
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // Method 4: apache_request_headers() if available
        if (function_exists('apache_request_headers')) {
            $apache_headers = apache_request_headers();
            foreach ($apache_headers as $header_name => $header_value) {
                if (strtolower(trim($header_name)) === 'authorization') {
                    if (preg_match('/Bearer\s(\S+)/', $header_value, $matches)) {
                        return $matches[1];
                    }
                    return $header_value;
                }
            }
        }

        // Method 5: getallheaders() fallback
        if (function_exists('getallheaders')) {
            $all_headers = getallheaders();
            foreach ($all_headers as $header_name => $header_value) {
                if (strtolower(trim($header_name)) === 'authorization') {
                    if (preg_match('/Bearer\s(\S+)/', $header_value, $matches)) {
                        return $matches[1];
                    }
                    return $header_value;
                }
            }
        }

        // Method 6: Custom header "X-Token" (cPanel-safe, never stripped)
        foreach ($headers as $header_name => $header_value) {
            if (strtolower(trim($header_name)) === 'x-token') {
                return $header_value;
            }
        }
        if (isset($_SERVER['HTTP_X_TOKEN'])) {
            return $_SERVER['HTTP_X_TOKEN'];
        }

        // Method 7: Query parameter ?token=xxx (last resort fallback)
        $query_token = $this->CI->input->get('token');
        if (!empty($query_token)) {
            return $query_token;
        }

        return null;
    }
}
