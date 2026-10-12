<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
* --------------------------------------------------------------------------
* Base Site URL
* --------------------------------------------------------------------------
*
* URL to your CodeIgniter root. Typically this will be your base URL,
* WITH a trailing slash:
*
*   http://example.com/
*
* If this is not set then CodeIgniter will try guess the protocol, domain
* and path to your installation. However, you should always configure this
* explicitly and never rely on auto-guessing, especially in production
* environments.
*
*/
//define('APP_BASE_URL', 'https://pltechsolutions.com/');
//define('APP_BASE_URL', 'http://localhost/nextgenlive/');
//define('APP_BASE_URL', 'http://localhost/nextgenlive/');
//define('APP_BASE_URL', 'https://172.168.20.61/nextgen/'); 

// Dynamic base URL - works with any hostname, IP, port, or domain
$_app_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$_app_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
// Auto-detect subfolder from script path
$_app_script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$_app_subfolder = substr($_app_script_name, 0, strpos($_app_script_name, '/index.php'));
if(empty($_app_subfolder)) $_app_subfolder = '';
define('APP_BASE_URL', $_app_protocol . $_app_host . $_app_subfolder . '/');

/*
* --------------------------------------------------------------------------
* Encryption Key
* IMPORTANT: Do not change this ever!
* --------------------------------------------------------------------------
*
* If you use the Encryption class, you must set an encryption key.
* See the user guide for more info.
*
* http://codeigniter.com/user_guide/libraries/encryption.html
*
* Auto added on install
*/
define('APP_ENC_KEY', 'REMOVED_USE_LEGACY_LOCAL');

/**
 * Database Credentials
 * The hostname of your database server
 */
define('APP_DB_HOSTNAME', 'localhost');

/**
 * The username used to connect to the database
 */
define('APP_DB_USERNAME', 'cubepltech_cube');

/**
 * The password used to connect to the database
 */
define('APP_DB_PASSWORD', 'REMOVED_USE_LEGACY_LOCAL');

/**
 * The name of the database you want to connect to
 */
define('APP_DB_NAME', 'cubepltech_cube');

/**
 * @since  2.3.0
 * Database charset
 */
define('APP_DB_CHARSET', 'utf8mb4');

/**
 * @since  2.3.0
 * Database collation
 */
define('APP_DB_COLLATION', 'utf8mb4_unicode_ci');

/**
 *
 * Session handler driver
 * By default the database driver will be used.
 *
 * For files session use this config:
 * define('SESS_DRIVER', 'files');
 * define('SESS_SAVE_PATH', NULL);
 * In case you are having problem with the SESS_SAVE_PATH consult with your hosting provider to set "session.save_path" value to php.ini
 *
 */
define('SESS_DRIVER', 'database');
define('SESS_SAVE_PATH', 'sessions');
define('APP_SESSION_COOKIE_SAME_SITE', 'Lax');

/**
 * Session timeout in seconds.
 * This is the PHP/CI session lifetime and is now the ONLY logout mechanism -
 * there is no JavaScript idle timer or keep-alive heartbeat.
 * The user is logged out after this many seconds without a request.
 * 1800 = 30 minutes, 3600 = 1 hour, 28800 = 8 hours
 * Set to 0 for browser-close expiry.
 */
define('APP_SESSION_EXPIRATION', 28800);

/**
 * How often (seconds) CI regenerates the session ID.
 * Set to 0 to DISABLE session ID regeneration - prevents random logouts.
 */
define('APP_SESSION_TIME_TO_UPDATE', 0);

/**
 * Password expiry in days (default: 30 days)
 * Set to 0 to disable password expiry
 */
define('APP_PASSWORD_EXPIRY_DAYS', 0);

/**
 * Enables CSRF Protection
 */
define('APP_CSRF_PROTECTION', true);