<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * LDAP Authentication Library
 * 
 * Handles Active Directory / LDAP authentication for staff login.
 */
class Ldap_auth
{
    private $CI;
    private $connection;
    private $last_error = '';

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->config->load('ldap', true);
    }

    /**
     * Get a config value from ldap config
     */
    private function get_config($key)
    {
        return $this->CI->config->item($key, 'ldap');
    }

    /**
     * Check if LDAP authentication is enabled
     * 
     * @return bool
     */
    public function is_enabled()
    {
        return $this->get_config('ldap_enabled') === true;
    }

    /**
     * Authenticate user against Active Directory
     * 
     * @param string $email    User email address
     * @param string $password User password
     * @return array|false     Returns user attributes array on success, false on failure
     */
    public function authenticate($email, $password)
    {
        if (!$this->is_enabled()) {
            return false;
        }

        if (!function_exists('ldap_connect')) {
            $this->last_error = 'PHP LDAP extension is not installed';
            log_activity('LDAP Error: PHP LDAP extension not installed');
            return false;
        }

        if (empty($email) || empty($password)) {
            return false;
        }

        $host = $this->get_config('ldap_host');
        $port = $this->get_config('ldap_port');

        // Connect to LDAP server
        $this->connection = @ldap_connect($host, $port);

        if (!$this->connection) {
            $this->last_error = 'Cannot connect to LDAP server';
            log_activity('LDAP Error: Cannot connect to ' . $host);
            return false;
        }

        // Set LDAP options
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->get_config('ldap_version'));
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($this->connection, LDAP_OPT_NETWORK_TIMEOUT, 10);

        // Start TLS if configured
        if ($this->get_config('ldap_use_tls')) {
            if (!@ldap_start_tls($this->connection)) {
                $this->last_error = 'Failed to start TLS: ' . ldap_error($this->connection);
                log_activity('LDAP Error: Failed to start TLS');
                $this->disconnect();
                return false;
            }
        }

        // Build the login username based on format
        $login_username = $this->build_login_username($email);

        // Attempt to bind (authenticate) with user credentials
        $bind = @ldap_bind($this->connection, $login_username, $password);

        if (!$bind) {
            $this->last_error = 'Authentication failed: ' . ldap_error($this->connection);
            log_activity('LDAP Auth Failed [Email: ' . $email . ', Error: ' . ldap_error($this->connection) . ']');
            $this->disconnect();
            return false;
        }

        // Authentication successful - now search for user details
        $user_info = $this->search_user($email);

        $this->disconnect();

        if ($user_info) {
            log_activity('LDAP Auth Success [Email: ' . $email . ']');
            return $user_info;
        }

        // Auth succeeded but couldn't fetch details - still return success
        log_activity('LDAP Auth Success (no details) [Email: ' . $email . ']');
        return [
            'email'     => $email,
            'firstname' => '',
            'lastname'  => '',
            'phone'     => '',
        ];
    }

    /**
     * Search for user details in LDAP directory
     * 
     * @param string $email User email
     * @return array|false  User attributes or false
     */
    private function search_user($email)
    {
        $search_filter = str_replace('%s', ldap_escape($email, '', LDAP_ESCAPE_FILTER), $this->get_config('ldap_user_search_filter'));
        $search_base = $this->get_config('ldap_user_search_base') ?: $this->get_config('ldap_base_dn');

        $attributes = [
            $this->get_config('ldap_attr_email'),
            $this->get_config('ldap_attr_firstname'),
            $this->get_config('ldap_attr_lastname'),
            $this->get_config('ldap_attr_phone'),
            $this->get_config('ldap_attr_username'),
        ];

        $search = @ldap_search($this->connection, $search_base, $search_filter, $attributes);

        if (!$search) {
            return false;
        }

        $entries = ldap_get_entries($this->connection, $search);

        if ($entries['count'] == 0) {
            return false;
        }

        $entry = $entries[0];

        $attr_email     = strtolower($this->get_config('ldap_attr_email'));
        $attr_firstname = strtolower($this->get_config('ldap_attr_firstname'));
        $attr_lastname  = strtolower($this->get_config('ldap_attr_lastname'));
        $attr_phone     = strtolower($this->get_config('ldap_attr_phone'));
        $attr_username  = strtolower($this->get_config('ldap_attr_username'));

        return [
            'email'     => isset($entry[$attr_email][0]) ? $entry[$attr_email][0] : $email,
            'firstname' => isset($entry[$attr_firstname][0]) ? $entry[$attr_firstname][0] : '',
            'lastname'  => isset($entry[$attr_lastname][0]) ? $entry[$attr_lastname][0] : '',
            'phone'     => isset($entry[$attr_phone][0]) ? $entry[$attr_phone][0] : '',
            'username'  => isset($entry[$attr_username][0]) ? $entry[$attr_username][0] : '',
        ];
    }

    /**
     * Build the login username based on configured format
     * 
     * @param string $email User email
     * @return string       Formatted login string
     */
    private function build_login_username($email)
    {
        $format = $this->get_config('ldap_login_format') ?: 'upn';

        switch ($format) {
            case 'upn':
                // user@domain.com format (User Principal Name)
                return $email;

            case 'backslash':
                // DOMAIN\username format
                $username = strstr($email, '@', true);
                return $this->get_config('ldap_domain') . '\\' . $username;

            case 'dn':
                // Full DN format - requires search first with service account
                return $this->get_user_dn($email);

            case 'email':
            default:
                return $email;
        }
    }

    /**
     * Get user DN by searching with service account
     * 
     * @param string $email User email
     * @return string       User DN
     */
    private function get_user_dn($email)
    {
        $search_user = $this->get_config('ldap_search_user');
        $search_pass = $this->get_config('ldap_search_password');

        // Bind with service account to search
        if (!empty($search_user)) {
            @ldap_bind($this->connection, $search_user, $search_pass);

            $filter = str_replace('%s', ldap_escape($email, '', LDAP_ESCAPE_FILTER), $this->get_config('ldap_user_search_filter'));
            $search = @ldap_search($this->connection, $this->get_config('ldap_base_dn'), $filter, ['dn']);

            if ($search) {
                $entries = ldap_get_entries($this->connection, $search);
                if ($entries['count'] > 0) {
                    return $entries[0]['dn'];
                }
            }
        }

        // Fallback to email
        return $email;
    }

    /**
     * Disconnect from LDAP server
     */
    private function disconnect()
    {
        if ($this->connection) {
            @ldap_unbind($this->connection);
            $this->connection = null;
        }
    }

    /**
     * Get the last error message
     * 
     * @return string
     */
    public function get_last_error()
    {
        return $this->last_error;
    }
}
