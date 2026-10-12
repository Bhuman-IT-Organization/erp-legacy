<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Other_merge_fields extends App_merge_fields
{
    public function build()
    {
        $available_for = [
                        'ticket',
                        'client',
                        'staff',
                        'invoice',
                        'estimate',
                        'contract',
                        'tasks',
                        'proposals',
                        'project',
                        'leads',
                        'credit_note',
                        'subscriptions',
                        'gdpr',
                        'estimate_request',
                        'notifications'
                    ];

        $available_for = hooks()->apply_filters('other_merge_fields_available_for', $available_for);

        return [
                [
                    'name'        => 'Logo URL',
                    'key'         => '{logo_url}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
                [
                    'name'        => 'Logo image with URL',
                    'key'         => '{logo_image_with_url}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
                [
                    'name'        => 'Dark logo image with URL',
                    'key'         => '{dark_logo_image_with_url}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
                [
                    'name'        => 'CRM URL',
                    'key'         => '{crm_url}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
                [
                    'name'        => 'Admin URL',
                    'key'         => '{admin_url}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
                [
                    'name'        => 'Main Domain',
                    'key'         => '{main_domain}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
                [
                    'name'        => 'Company Name',
                    'key'         => '{companyname}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
                [
                    'name'        => 'Email Signature',
                    'key'         => '{email_signature}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
                [
                    'name'        => '(GDPR) Terms & Conditions URL',
                    'key'         => '{terms_and_conditions_url}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
                [
                    'name'        => '(GDPR) Privacy Policy URL',
                    'key'         => '{privacy_policy_url}',
                    'fromoptions' => true,
                    'available'   => $available_for,
                ],
            ];
    }

    public function format()
    {
        $fields               = [];
        $fields['{logo_url}'] = base_url('uploads/company/' . get_option('company_logo'));

        $logo_width = hooks()->apply_filters('merge_field_logo_img_width', '');

        $fields['{logo_image_with_url}'] = '<a href="' . site_url() . '" target="_blank"><img src="' . base_url('uploads/company/' . get_option('company_logo')) . '"' . ($logo_width != '' ? ' width="' . e($logo_width) . '"' : '') . '></a>';

        $fields['{dark_logo_image_with_url}'] = '';
        if (get_option('company_logo_dark') != '') {
            $fields['{dark_logo_image_with_url}'] = '<a href="' . site_url() . '" target="_blank"><img src="' . base_url('uploads/company/' . get_option('company_logo_dark')) . '"' . ($logo_width != '' ? ' width="' . e($logo_width) . '"' : '') . '></a>';
        }

        $fields['{crm_url}']     = rtrim(site_url(), '/');
        $fields['{admin_url}']   = admin_url();
        $fields['{main_domain}'] = e(get_option('main_domain'));
        $fields['{companyname}'] = e(get_option('companyname'));

        // Signature resolution, most specific first:
        //
        //   1. the acting staff member's own signature (Profile -> Email Signature)
        //   2. that staff member's first and last name, so a notification is signed by
        //      whoever triggered it rather than by a generic global string
        //   3. the global signature in Setup -> Settings -> Email
        //   4. the company name, for cron and system mail where nobody is logged in
        //
        // Step 2 is the important one: before it existed, every staff member who had not
        // filled in a personal signature fell straight through to the global option, so
        // all outgoing mail was signed with the same placeholder text.
        $signature = '';

        if (is_staff_logged_in() && !is_client_logged_in()) {
            $staff_id = get_staff_user_id();

            $this->ci->db->select('firstname, lastname, email_signature');
            $this->ci->db->where('staffid', $staff_id);
            $staff = $this->ci->db->get(db_prefix() . 'staff')->row();

            // Guarded: row() returns NULL when the staff record has been removed, and
            // reading a property off NULL is a fatal error in PHP 8.
            if ($staff) {
                if (!empty($staff->email_signature)) {
                    $signature = $staff->email_signature;
                } else {
                    $signature = trim($staff->firstname . ' ' . $staff->lastname);
                }
            }
        }

        if ($signature === '' || $signature === null) {
            $signature = (string) get_option('email_signature');
        }

        if (trim($signature) === '') {
            $signature = get_option('companyname');
        }

        $fields['{email_signature}'] = $signature;

        if(!is_html($fields['{email_signature}'])) {
            $fields['{email_signature}'] = nl2br($fields['{email_signature}']);
        }

        $fields['{terms_and_conditions_url}'] = terms_url();
        $fields['{privacy_policy_url}']       = privacy_policy_url();


        return hooks()->apply_filters('other_merge_fields', $fields);
    }
}
