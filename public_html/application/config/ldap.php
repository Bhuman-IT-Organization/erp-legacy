<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * LDAP / Active Directory Configuration
 * 
 * Set ldap_enabled to true to enable Active Directory login.
 * Users will be authenticated against AD first, then fall back to local DB if AD fails.
 */

// Enable/Disable LDAP Authentication
$config['ldap_enabled'] = true; // Set to true to enable AD login

// LDAP Server Settings
$config['ldap_host'] = 'ldap://your-ad-server.domain.com'; // e.g., ldap://192.168.1.100 or ldaps://ad.company.com
$config['ldap_port'] = 389; // 389 for LDAP, 636 for LDAPS
$config['ldap_base_dn'] = 'DC=domain,DC=com'; // Base Distinguished Name e.g., DC=company,DC=com
$config['ldap_domain'] = 'DOMAIN'; // NetBIOS domain name for DOMAIN\user format

// LDAP Search Settings (for looking up user details after authentication)
$config['ldap_search_user'] = 'CN=ServiceAccount,OU=Service Accounts,DC=domain,DC=com'; // Service account DN for searching
$config['ldap_search_password'] = ''; // Service account password
$config['ldap_user_search_filter'] = '(&(objectClass=user)(mail=%s))'; // %s will be replaced with email
$config['ldap_user_search_base'] = 'OU=Users,DC=domain,DC=com'; // OU to search for users

// LDAP Attribute Mapping
$config['ldap_attr_email'] = 'mail';
$config['ldap_attr_firstname'] = 'givenName';
$config['ldap_attr_lastname'] = 'sn';
$config['ldap_attr_phone'] = 'telephoneNumber';
$config['ldap_attr_username'] = 'sAMAccountName';

// Authentication Mode
// 'ad_only'    - Only authenticate against AD (local password ignored)
// 'ad_first'   - Try AD first, fall back to local DB password
// 'ad_or_local' - User can login with either AD or local password
$config['ldap_auth_mode'] = 'ad_first';

// Auto-create staff member if authenticated via AD but not found in local DB
$config['ldap_auto_create_staff'] = false;

// Default role ID for auto-created staff (set to your desired role ID)
$config['ldap_default_role'] = 0;

// Use TLS (STARTTLS)
$config['ldap_use_tls'] = false;

// LDAP Protocol Version (usually 3)
$config['ldap_version'] = 3;

// Login format: 'email', 'upn' (user@domain.com), 'backslash' (DOMAIN\user)
$config['ldap_login_format'] = 'upn';
