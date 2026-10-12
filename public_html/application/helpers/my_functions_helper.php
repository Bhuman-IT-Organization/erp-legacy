<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Local application extensions.
 *
 * application/config/autoload.php autoloads this file when it exists, which makes it
 * the supported place to hook in behaviour without editing core files or the
 * third-party modules. Everything here is additive.
 *
 * Currently registers:
 *   - the two attendance screens, inside the existing "Timesheets & Leave" sidebar group
 *   - the forgot-to-check-out job, on the existing cron
 *   - language strings for both screens
 */

/* -------------------------------------------------------------------- menu */

if (!function_exists('attendance_extensions_menu_items')) {
    /**
     * Add the attendance screens as children of the timesheets module's existing
     * sidebar group, so they sit with the attendance features people already use
     * rather than creating a second place to look.
     *
     * Guarded on the parent existing: if the timesheets module is disabled the items
     * are registered at top level instead, so they do not silently disappear.
     */
    function attendance_extensions_menu_items()
    {
        $CI = &get_instance();

        if (!is_admin()
            && !has_permission('attendance_management', '', 'view')
            && !has_permission('table_workplace_management', '', 'view')
            && !has_permission('report_management', '', 'view')) {
            return;
        }

        $parent = 'timesheets';
        $has_parent = false;

        // app_menu keeps the registered sidebar items; check the module's group is there.
        if (method_exists($CI->app_menu, 'get_sidebar_menu_items')) {
            $items = $CI->app_menu->get_sidebar_menu_items();
            $has_parent = isset($items[$parent]);
        }

        if (!$has_parent) {
            $CI->app_menu->add_sidebar_menu_item('attendance_extensions', [
                'name'     => _l('attendance'),
                'icon'     => 'fa fa-regular fa-pen-to-square',
                'position' => 31,
            ]);
            $parent = 'attendance_extensions';
        }

        if (is_admin()
            || has_permission('attendance_management', '', 'view')
            || has_permission('report_management', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item($parent, [
                'slug'     => 'attendance_dashboard',
                'name'     => _l('attendance_dashboard'),
                'href'     => admin_url('attendance_dashboard'),
                'icon'     => 'fa fa-regular fa-calendar-check',
                'position' => 6,
            ]);
        }

        if (is_admin()
            || has_permission('table_workplace_management', '', 'view')
            || has_permission('attendance_management', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item($parent, [
                'slug'     => 'attendance_locations',
                'name'     => _l('attendance_project_locations'),
                'href'     => admin_url('attendance_locations'),
                'icon'     => 'fa fa-solid fa-map-location-dot',
                'position' => 7,
            ]);
        }

        // Pay-affecting rules, so a tighter gate than the attendance screens.
        if (is_admin()
            || has_permission('timesheet_settings', '', 'view')
            || has_permission('hrp_setting', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item($parent, [
                'slug'     => 'attendance_rules',
                'name'     => _l('attendance_rules'),
                'href'     => admin_url('attendance_rules'),
                'icon'     => 'fa fa-solid fa-sliders',
                'position' => 8,
            ]);
        }

        // HR master data: categories, document types, lending rules, the increment
        // split and the approval chains. Same gate as the pay-affecting rules above,
        // because these rows decide who signs for money.
        if (is_admin()
            || has_permission('hrp_setting', '', 'view')
            || has_permission('timesheet_settings', '', 'view')
            || has_permission('hrm_hr_records', '', 'edit')) {
            $CI->app_menu->add_sidebar_children_item($parent, [
                'slug'     => 'hr_masters',
                'name'     => _l('hr_masters'),
                'href'     => admin_url('hr_masters'),
                'icon'     => 'fa fa-solid fa-table-list',
                'position' => 9,
            ]);
        }

        // Loans: every staff member may apply for and see their own, so this is not
        // gated - the controller scopes the list to the viewer when they have no
        // HR/payroll right.
        $CI->app_menu->add_sidebar_children_item($parent, [
            'slug'     => 'loans',
            'name'     => _l('loan_applications'),
            'href'     => admin_url('loans'),
            'icon'     => 'fa fa-solid fa-hand-holding-dollar',
            'position' => 10,
        ]);

        // Increments: raised by managers and HR only.
        if (is_admin()
            || has_permission('hrp_setting', '', 'view')
            || has_permission('hrm_hr_records', '', 'view')
            || has_permission('hrm_hr_records', '', 'edit')
            || has_permission('staff', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item($parent, [
                'slug'     => 'increments',
                'name'     => _l('increment_requests'),
                'href'     => admin_url('increments'),
                'icon'     => 'fa fa-solid fa-arrow-trend-up',
                'position' => 11,
            ]);
        }

        // My Documents: every employee has a personal folder, so ungated.
        $CI->app_menu->add_sidebar_children_item($parent, [
            'slug'     => 'employee_documents_mine',
            'name'     => _l('empdoc_my_documents'),
            'href'     => admin_url('employee_documents/mine'),
            'icon'     => 'fa fa-solid fa-folder-open',
            'position' => 12,
        ]);

        // HR-only: the review queue and the soon-to-expire list.
        if (is_admin()
            || has_permission('hrm_hr_records', '', 'view')
            || has_permission('hrm_hr_records', '', 'edit')
            || has_permission('hrp_setting', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item($parent, [
                'slug'     => 'employee_documents_expiring',
                'name'     => _l('empdoc_expiring_soon'),
                'href'     => admin_url('employee_documents/expiring'),
                'icon'     => 'fa fa-solid fa-hourglass-half',
                'position' => 13,
            ]);

            $CI->app_menu->add_sidebar_children_item($parent, [
                'slug'     => 'employee_documents_review',
                'name'     => _l('empdoc_review_queue'),
                'href'     => admin_url('employee_documents/review'),
                'icon'     => 'fa fa-solid fa-clipboard-check',
                'position' => 14,
            ]);
        }
    }

    hooks()->add_action('admin_init', 'attendance_extensions_menu_items');
}

/* ---------------------------------------------------- project scope tab */

if (!function_exists('project_scope_register_tab')) {
    /**
     * Add the "Scope / Activities" tab to the project view.
     *
     * Registered on admin_init, the same hook core uses for app_init_project_tabs, and
     * guarded on app_tabs being present so it never fatals if the projects feature is
     * not loaded on a given request.
     */
    function project_scope_register_tab()
    {
        $CI = &get_instance();

        if (!isset($CI->app_tabs) || !method_exists($CI->app_tabs, 'add_project_tab')) {
            return;
        }

        $CI->app_tabs->add_project_tab('project_scope', [
            'name'     => _l('pscope_tab_name'),
            'icon'     => 'fa fa-solid fa-list-check',
            'view'     => 'admin/project_scope/tab',
            'position' => 22,
        ]);
    }

    hooks()->add_action('admin_init', 'project_scope_register_tab');
}

/* --------------------------------------------------- forgot to check out */

if (!function_exists('attendance_unclosed_punch_alerts')) {
    /**
     * Notify about check-ins that were never closed.
     *
     * Runs on the application's existing cron (after_cron_run), so there is nothing
     * extra to schedule on the server.
     *
     * Two options control it, both created on first run:
     *   attendance_unclosed_threshold_hours - how long a check-in may stay open before
     *       it is treated as forgotten. Default 12, which clears a long shift without
     *       flagging it.
     *   attendance_unclosed_notify_staff - comma separated staff ids to copy, normally
     *       HR or the site supervisor. Empty means notify only the employee.
     *
     * Each forgotten punch is alerted once, recorded in tblattendance_alert_log. The
     * cron runs every few minutes, and without that record the same person would be
     * reminded about the same punch on every run.
     */
    function attendance_unclosed_punch_alerts()
    {
        $CI = &get_instance();

        $threshold = (float) get_option('attendance_unclosed_threshold_hours');

        if ($threshold <= 0) {
            $threshold = 12;
        }

        $CI->load->model('attendance_geo_model');

        // Yesterday as well as today: a check-in left open overnight is exactly the
        // case this is for, and by the time it is noticed the date has rolled over.
        $dates = [date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))];

        $recipients = array_filter(array_map('trim',
            explode(',', (string) get_option('attendance_unclosed_notify_staff'))));

        $sent = 0;

        foreach ($dates as $date) {
            $due = $CI->attendance_geo_model->get_unalerted_unclosed_punches($threshold, $date);

            foreach ($due as $punch) {
                $staff_name = get_staff_full_name($punch['staff_id']);
                $message    = $staff_name . ' checked in at ' . _dt($punch['date'])
                    . ' and has not checked out (' . $punch['hours_open'] . ' hours).';

                // The employee, so they can fix it themselves.
                add_notification([
                    'description' => 'You have not checked out since ' . _dt($punch['date'])
                        . '. Please record your check out or ask HR to correct it.',
                    'touserid'    => (int) $punch['staff_id'],
                    'link'        => 'timesheets/timekeeping',
                    'fromcompany' => 1,
                ]);

                foreach ($recipients as $recipient) {
                    if (!is_numeric($recipient)) {
                        continue;
                    }

                    add_notification([
                        'description' => $message,
                        'touserid'    => (int) $recipient,
                        'link'        => 'attendance_dashboard',
                        'fromcompany' => 1,
                    ]);
                }

                $CI->attendance_geo_model->mark_alerted($punch);
                $sent++;
            }
        }

        if ($sent > 0) {
            log_activity('Attendance: ' . $sent . ' forgotten check-out alert(s) sent.');
        }
    }

    hooks()->add_action('after_cron_run', 'attendance_unclosed_punch_alerts');
}

/* --------------------------------------------------- vacation extensions */

if (!function_exists('vacation_after_add_leave_request')) {
    /**
     * Runs on the timesheets module's own ts_after_add_leave_request action, just after
     * a leave row is inserted. Does the three things the module does not:
     *
     *   - a server-side balance check: an over-balance annual-leave request is reversed
     *     and the submitter told, because the module only checked this in the browser;
     *   - an overlap warning naming a same-department colleague already on leave;
     *   - the high-balance notice for staff whose balance is over the category threshold.
     *
     * Reversal rather than prevention because the action fires post-insert and the
     * module exposes no pre-insert filter - editing the licensed module would be lost
     * on its next release.
     *
     * @param int $leave_id
     */
    function vacation_after_add_leave_request($leave_id)
    {
        $CI = &get_instance();
        $CI->load->model('vacation_model');

        $leave = $CI->vacation_model->get_leave($leave_id);

        if (!$leave || (int) $leave->rel_type !== 1) {
            // Only leave requisitions (rel_type 1), not late/go-out/business-trip.
            return;
        }

        $staff_id = (int) $leave->staff_id;
        $days     = (float) $leave->number_of_leaving_day;
        $start    = date('Y-m-d', strtotime($leave->start_time));
        $end      = date('Y-m-d', strtotime($leave->end_time));

        // 1. Balance. Reverse an over-balance annual-leave request outright.
        $balance = $CI->vacation_model->check_balance($staff_id, (int) $leave->type_of_leave, $days);

        if (!$balance['ok']) {
            $CI->vacation_model->reverse_leave($leave_id);

            add_notification([
                'description' => _l('vacation_over_balance_reversed', [
                    $days,
                    $balance['available'],
                ]),
                'touserid'    => $staff_id,
                'fromcompany' => 1,
            ]);

            // Nothing else to do - the request no longer exists.
            return;
        }

        // 2. Overlap. Warn the submitter about same-department colleagues on leave.
        $overlaps = $CI->vacation_model->department_overlaps($staff_id, $start, $end, $leave_id);

        if (count($overlaps) > 0) {
            $names = [];
            foreach ($overlaps as $o) {
                $names[] = $o['name'] . ' (' . _d($o['start_time']) . ' - ' . _d($o['end_time']) . ')';
            }

            // Tell whoever raised it, which may be a supervisor acting for the employee.
            $recipient = get_staff_user_id() ?: $staff_id;

            add_notification([
                'description' => _l('vacation_overlap_notice') . ' ' . implode('; ', $names),
                'touserid'    => (int) $recipient,
                'link'        => 'timesheets/requisition_detail/' . (int) $leave_id,
                'fromcompany' => 1,
            ]);
        }

        // 3. High-balance notice. Staff over the threshold, since encashment is not
        //    offered.
        $threshold = $CI->vacation_model->balance_alert_threshold($staff_id);

        if ($threshold !== null) {
            $available = $CI->vacation_model->available_days($staff_id, 8);

            if ($available > $threshold) {
                add_notification([
                    'description' => _l('vacation_high_balance_notice', [$available, $threshold]),
                    'touserid'    => $staff_id,
                    'fromcompany' => 1,
                ]);
            }
        }
    }

    hooks()->add_action('ts_after_add_leave_request', 'vacation_after_add_leave_request');
}

if (!function_exists('vacation_sync_ledger')) {
    /**
     * Keep the leave ledger in step with approved annual leave in the module.
     *
     * The module has no approval hook, so rather than edit it, this reconciles: every
     * approved annual leave (status 1, type 8) without a 'taken' ledger entry gets one,
     * and any 'taken' entry whose leave has since been rejected or deleted is removed.
     * Balance is then always SUM(ledger) and reflects only approved leave.
     *
     * Runs on admin_init (cheap - two indexed lookups) and on the cron.
     */
    function vacation_sync_ledger()
    {
        $CI = &get_instance();

        $leave_table  = db_prefix() . 'timesheets_requisition_leave';
        $ledger_table = db_prefix() . 'attendance_leave_ledger';

        if (!$CI->db->table_exists($leave_table) || !$CI->db->table_exists($ledger_table)) {
            return;
        }

        $CI->load->model('vacation_model');

        // Approved annual leaves with no 'taken' entry yet -> record them.
        $CI->db->where('rel_type', 1);
        $CI->db->where('type_of_leave', 8);
        $CI->db->where('status', 1);
        $CI->db->where('id NOT IN (SELECT ref_id FROM ' . $ledger_table
            . ' WHERE ref_type = "requisition_leave" AND entry_type = "taken")', null, false);
        $to_record = $CI->db->get($leave_table)->result();

        foreach ($to_record as $leave) {
            $CI->vacation_model->record_taken($leave);
        }

        // 'taken' entries whose leave is gone or no longer approved -> remove them.
        $CI->db->select($ledger_table . '.id, ' . $ledger_table . '.ref_id');
        $CI->db->from($ledger_table);
        $CI->db->join($leave_table, $leave_table . '.id = ' . $ledger_table . '.ref_id', 'left');
        $CI->db->where($ledger_table . '.ref_type', 'requisition_leave');
        $CI->db->where($ledger_table . '.entry_type', 'taken');
        $CI->db->where('(' . $leave_table . '.id IS NULL OR ' . $leave_table . '.status != 1)', null, false);
        $stale = $CI->db->get()->result();

        foreach ($stale as $row) {
            $CI->db->where('id', $row->id)->delete($ledger_table);
        }
    }

    hooks()->add_action('admin_init', 'vacation_sync_ledger');
    hooks()->add_action('after_cron_run', 'vacation_sync_ledger');
}

/* ------------------------------------------------ approval outcome routing */

if (!function_exists('hr_extensions_on_approval_complete')) {
    /**
     * When the shared approval engine finishes a chain, hand the outcome to the
     * feature that owns the request. The engine stays generic - it does not know that
     * an approved loan needs a schedule or an approved increment needs a split - and
     * each feature keeps its own logic. Only the routing lives here.
     *
     * @param array $payload request_type, rel_id, staff_id, status
     */
    function hr_extensions_on_approval_complete($payload)
    {
        $CI = &get_instance();

        $type   = $payload['request_type'] ?? '';
        $rel_id = (int) ($payload['rel_id'] ?? 0);
        $status = $payload['status'] ?? '';

        if ($rel_id <= 0) {
            return;
        }

        switch ($type) {
            case 'loan_application':
                $CI->load->model('loan_model');
                $CI->loan_model->on_approval_complete($rel_id, $status);
                break;

            case 'increment_request':
                if (file_exists(APPPATH . 'models/Increment_model.php')) {
                    $CI->load->model('increment_model');
                    $CI->increment_model->on_approval_complete($rel_id, $status);
                }
                break;

            case 'document_change':
                if (file_exists(APPPATH . 'models/Employee_document_model.php')) {
                    $CI->load->model('employee_document_model');
                    $CI->employee_document_model->on_approval_complete($rel_id, $status);
                }
                break;
        }
    }

    hooks()->add_action('approval_request_completed', 'hr_extensions_on_approval_complete');
}

if (!function_exists('attendance_extensions_default_options')) {
    /**
     * Create the options the cron reads, once, so they are visible and editable
     * rather than being invisible magic numbers.
     */
    function attendance_extensions_default_options()
    {
        if (get_option('attendance_unclosed_threshold_hours') === '') {
            add_option('attendance_unclosed_threshold_hours', 12, 1);
        }

        if (get_option('attendance_unclosed_notify_staff') === '') {
            add_option('attendance_unclosed_notify_staff', '', 1);
        }
    }

    hooks()->add_action('admin_init', 'attendance_extensions_default_options');
}

/* ------------------------------------------------ document expiry reminders */

if (!function_exists('employee_document_expiry_cron')) {
    /**
     * Remind employees and HR about documents nearing expiry.
     *
     * Runs on the application's existing cron. Reminder lead time is per document type
     * (a passport warns further out than an insurance card); the model dedupes so a
     * reminder is sent once per stage rather than every cron run. HR recipients come
     * from the same option the attendance alerts use.
     */
    function employee_document_expiry_cron()
    {
        $CI = &get_instance();

        if (!$CI->db->table_exists(db_prefix() . 'emp_document')) {
            return;
        }

        $CI->load->model('employee_document_model');

        $recipients = array_filter(array_map('trim',
            explode(',', (string) get_option('attendance_unclosed_notify_staff'))));

        $sent = $CI->employee_document_model->send_expiry_reminders($recipients);

        if ($sent > 0) {
            log_activity('Employee documents: ' . $sent . ' expiry reminder(s) sent.');
        }
    }

    hooks()->add_action('after_cron_run', 'employee_document_expiry_cron');
}

/* -------------------------------------------------------- loan status label */

if (!function_exists('loan_status_label')) {
    /**
     * Coloured status label for a loan, shared by the list and detail views.
     *
     * @param string $status
     *
     * @return string
     */
    function loan_status_label($status)
    {
        $map = [
            'draft'     => 'default',
            'pending'   => 'warning',
            'approved'  => 'success',
            'disbursed' => 'info',
            'closed'    => 'primary',
            'rejected'  => 'danger',
            'cancelled' => 'default',
        ];
        $type = isset($map[$status]) ? $map[$status] : 'default';

        return '<span class="label label-' . $type . '">' . _l('loan_status_' . $status) . '</span>';
    }
}

if (!function_exists('empdoc_status_label')) {
    /**
     * Coloured status label for an employee document.
     *
     * @param string $status
     *
     * @return string
     */
    function empdoc_status_label($status)
    {
        $map = [
            'current'          => 'success',
            'pending_approval' => 'warning',
            'superseded'       => 'default',
            'rejected'         => 'danger',
        ];
        $type = isset($map[$status]) ? $map[$status] : 'default';

        return '<span class="label label-' . $type . '">' . _l('empdoc_status_' . $status) . '</span>';
    }
}

if (!function_exists('increment_status_label')) {
    /**
     * Coloured status label for an increment request.
     *
     * @param string $status
     *
     * @return string
     */
    function increment_status_label($status)
    {
        $map = [
            'draft'     => 'default',
            'pending'   => 'warning',
            'approved'  => 'info',
            'applied'   => 'success',
            'rejected'  => 'danger',
            'cancelled' => 'default',
        ];
        $type = isset($map[$status]) ? $map[$status] : 'default';

        return '<span class="label label-' . $type . '">' . _l('increment_status_' . $status) . '</span>';
    }
}

/* ------------------------------------------------------------- menu icons */

if (!function_exists('app_font_awesome_v4_shim')) {
    /**
     * Load Font Awesome's v4 compatibility shim in the admin head.
     *
     * The bundled Font Awesome is version 6, but only fontawesome/solid/regular/brands
     * are enqueued - not v4-shims. Around 95 icon references in this codebase still use
     * version 4 names (fa-clock-o, fa-file-text-o, fa-money, fa-pencil-square-o and
     * others), and version 6 dropped those names, so each one renders as an empty box.
     *
     * The shim maps the old names onto the new glyphs, which fixes them all at once -
     * including the ones inside third-party modules, which is why this is done here
     * rather than by editing those modules and losing the change on their next update.
     */
    function app_font_awesome_v4_shim()
    {
        $file = FCPATH . 'assets/plugins/font-awesome/css/v4-shims.min.css';

        if (!file_exists($file)) {
            return;
        }

        // filemtime busts the browser cache if the file is ever replaced.
        echo '<link rel="stylesheet" type="text/css" href="'
            . base_url('assets/plugins/font-awesome/css/v4-shims.min.css') . '?v=' . filemtime($file)
            . '">' . PHP_EOL;
    }

    hooks()->add_action('app_admin_head', 'app_font_awesome_v4_shim');
}

if (!function_exists('app_menu_icon_defaults')) {
    /**
     * Give every top level menu item an icon.
     *
     * The sidebar and setup views print <i class="{icon} menu-icon"> for top level items
     * without checking whether an icon was set, so an item registered without one gets an
     * empty 18px box and text that looks misaligned. Fifteen of the Setup items in the
     * application, plus a couple in modules, are registered that way.
     *
     * Filling the gaps here, through the menu filters, keeps it out of both the core
     * helper and the third-party modules, and also covers any module added later.
     *
     * Child items are deliberately left alone: those views only print an icon when one is
     * set, so a sub-item without an icon is the intended look, not a defect.
     *
     * @param array  $items
     * @param string $group sidebar|setup
     *
     * @return array
     */
    function app_menu_icon_defaults($items, $group)
    {
        // Slugs that ship with no icon, matched to what the screen actually does.
        $known = [
            // Setup
            'staff'             => 'fa fa-solid fa-user-tie',
            'customers'         => 'fa fa-solid fa-building',
            'support'           => 'fa fa-solid fa-headset',
            'leads'             => 'fa fa-solid fa-filter',
            'finance'           => 'fa fa-solid fa-coins',
            'contracts'         => 'fa fa-solid fa-file-signature',
            'tasks_masters'     => 'fa fa-solid fa-list-check',
            'modules'           => 'fa fa-solid fa-puzzle-piece',
            'custom-fields'     => 'fa fa-solid fa-table-columns',
            'gdpr'              => 'fa fa-solid fa-shield-halved',
            'roles'             => 'fa fa-solid fa-user-shield',
            'api'               => 'fa fa-solid fa-code',
            'settings'          => 'fa fa-solid fa-gears',
            'email-templates'   => 'fa fa-regular fa-envelope-open',
            'estimate_request'  => 'fa fa-solid fa-file-invoice',
            'menu-options'      => 'fa fa-solid fa-bars',
            'theme-style'       => 'fa fa-solid fa-palette',
        ];

        // Anything not listed above still gets something rather than an empty box.
        $fallback = $group === 'setup' ? 'fa fa-solid fa-gear' : 'fa fa-regular fa-circle-dot';

        foreach ($items as $slug => $item) {
            if (!empty($item['icon'])) {
                continue;
            }

            $items[$slug]['icon'] = isset($known[$slug]) ? $known[$slug] : $fallback;
        }

        return $items;
    }

    hooks()->add_filter('sidebar_menu_items', function ($items) {
        return app_menu_icon_defaults($items, 'sidebar');
    });

    hooks()->add_filter('setup_menu_items', function ($items) {
        return app_menu_icon_defaults($items, 'setup');
    });
}

/* --------------------------------------------------------------- language */

if (!function_exists('attendance_extensions_lang')) {
    /**
     * Strings for the two screens.
     *
     * Added at runtime rather than to application/language/english/english_lang.php,
     * because that file is part of the application and is rewritten by CRM upgrades -
     * a merge in this repository has already dropped ~180 keys from it once.
     */
    function attendance_extensions_lang()
    {
        $CI = &get_instance();

        $strings = [
            'attendance_dashboard'              => 'Attendance Dashboard',
            'attendance_project_locations'      => 'Project Site Locations',
            'attendance_project_locations_help' => 'Set the coordinates of a project site to require '
                . 'employees to be there when they mark attendance. This is optional: a project with no '
                . 'coordinates is never location restricted. When someone is away from a site with '
                . 'coordinates, they must choose a reason and describe it, and the time is still '
                . 'recorded against the project.',
            'attendance_no_projects'            => 'There are no projects yet.',
            'attendance_geofence'               => 'Geofence',
            'attendance_geofence_on'            => 'On',
            'attendance_geofence_off'           => 'Not set',
            'attendance_geofence_suspended'     => 'Suspended',
            'attendance_geofence_active'        => 'Geofence active',
            'attendance_latitude'               => 'Latitude',
            'attendance_longitude'              => 'Longitude',
            'attendance_radius_metres'          => 'Accepted radius (metres)',
            'attendance_set_site_location'      => 'Set site location',
            'attendance_clear_geofence'         => 'Clear geofence',
            'attendance_clear_to_disable_help'  => 'Leave latitude and longitude empty and save to '
                . 'remove the geofence, returning this project to unrestricted.',
            'attendance_location_saved'         => 'Site location saved.',
            'attendance_location_cleared'       => 'Geofence removed. This project is no longer '
                . 'location restricted.',
            'attendance_location_save_failed'   => 'The site location could not be saved. Check the '
                . 'latitude and longitude are valid.',
            'attendance_daily_overview'         => 'Daily overview',
            'attendance_attended'               => 'Attended',
            'attendance_not_marked'             => 'Not marked',
            'attendance_on_leave_or_holiday'    => 'Leave or holiday',
            'attendance_total_employees'        => 'Employees',
            'attendance_employee_id'            => 'Employee ID',
            'attendance_first_punch'            => 'First punch',
            'attendance_last_punch'             => 'Last punch',
            'attendance_punches'                => 'Punches',
            'attendance_no_checkout'            => 'No check out',
            'attendance_is_holiday'             => 'This date is a company holiday',
            'attendance_still_checked_in'       => 'Still checked in',
            'attendance_still_checked_in_help'  => 'These employees checked in and never checked out. '
                . 'The hours cannot be counted until the day is closed.',
            'attendance_checked_in_at'          => 'Checked in at',
            'attendance_hours_open'             => 'Hours open',

            // Rules master data
            'attendance_rules'                  => 'Attendance Rules',
            'attendance_rules_help'             => 'Overtime rates, compensatory off and leave '
                . 'entitlements are company policy, so they are held here as data rather than in code. '
                . 'Until a rule is entered it is not applied, so nothing is calculated from a guessed '
                . 'default.',
            'attendance_overtime_rules'         => 'Overtime Rules',
            'attendance_overtime_rule'          => 'Overtime rule',
            'attendance_overtime_help'          => 'The first matching rule wins, in priority order '
                . '(lowest number first). Coefficient is the multiplier on the hourly rate: 1.5 is time '
                . 'and a half, 2 is double time. No rule means no overtime premium is applied.',
            'attendance_no_overtime_rules'      => 'No overtime rules yet. Overtime is not being '
                . 'costed until at least one rule is added.',
            'attendance_add_rule'               => 'Add rule',
            'attendance_priority'               => 'Priority',
            'attendance_day_type'               => 'Applies on',
            'attendance_day_any'                => 'Any day',
            'attendance_day_working_day'        => 'Working day',
            'attendance_day_weekly_off'         => 'Weekly off / rest day',
            'attendance_day_public_holiday'     => 'Public holiday',
            'attendance_time_window'            => 'Time window',
            'attendance_any_time'               => 'Any time',
            'attendance_overnight'              => 'overnight',
            'attendance_from_time'              => 'From time',
            'attendance_to_time'                => 'To time',
            'attendance_time_window_help'       => 'Leave both blank to apply at any time. For a night '
                . 'premium that runs past midnight, set From later than To, for example 22:00 to 06:00.',
            'attendance_min_minutes'            => 'Minimum minutes',
            'attendance_max_hours_per_day'      => 'Max hours per day (0 = no cap)',
            'attendance_effective'              => 'Effective',
            'attendance_effective_from'         => 'Effective from',
            'attendance_effective_to'           => 'Effective to',
            'attendance_limit_departments'      => 'Limit to departments',
            'attendance_scope_help'             => 'Leave empty to apply to everyone.',
            'attendance_rule_added'             => 'Overtime rule added.',
            'attendance_rule_updated'           => 'Overtime rule updated.',
            'attendance_rule_invalid'           => 'The rule needs a name and a coefficient greater '
                . 'than zero.',

            'attendance_comp_off'               => 'Compensatory Off',
            'attendance_comp_off_definition'    => 'Compensatory off is paid time off granted instead '
                . 'of overtime pay, for hours worked beyond normal hours or on a rest day or holiday.',
            'attendance_comp_off_inactive_warning' => 'Compensatory off is currently disabled. Nothing '
                . 'accrues until the policy below is confirmed and enabled.',
            'attendance_accrual_ratio'          => 'Accrual ratio',
            'attendance_accrual_ratio_help'     => 'Hours off granted per hour of extra work. 1 = hour '
                . 'for hour; 1.5 = an hour and a half off per hour worked.',
            'attendance_expiry_days'            => 'Expires after (days)',
            'attendance_expiry_days_help'       => '0 means earned comp-off never expires.',
            'attendance_min_minutes_to_accrue'  => 'Minimum minutes to accrue',
            'attendance_election_mode'          => 'Who decides',
            'attendance_election_employee'      => 'Employee requests it',
            'attendance_election_employer'      => 'Employer grants it',
            'attendance_earn_on'                => 'Earn compensatory off on',
            'attendance_earn_working_day'       => 'Extra hours on a normal working day',
            'attendance_earn_weekly_off'        => 'Work on a weekly off / rest day',
            'attendance_earn_public_holiday'    => 'Work on a public holiday',
            'attendance_exclude_from_overtime'  => 'Exclude comp-off hours from overtime pay',
            'attendance_exclude_from_overtime_help' => 'Strongly recommended. With this off, the same '
                . 'hours can be both paid as overtime and taken as time off.',
            'attendance_comp_off_enable'        => 'Enable compensatory off',
            'attendance_comp_off_saved'         => 'Compensatory off policy saved.',
            'attendance_comp_off_invalid'       => 'The accrual ratio must be greater than zero.',

            'attendance_leave_entitlement'      => 'Leave Entitlement',
            'attendance_entitlement_help'       => 'One record per employee, per leave type, per year. '
                . 'The cut-off date is what makes going live mid-year safe: the opening balance is '
                . 'taken as true as at that date and nothing before it is recalculated.',
            'attendance_no_entitlements'        => 'No entitlements recorded for this year yet.',
            'attendance_add_entitlement'        => 'Add entitlement',
            'attendance_leave_type'             => 'Leave type',
            'attendance_all_types'              => 'All types',
            'attendance_period_year'            => 'Leave year',
            'attendance_cut_off_date'           => 'Cut-off date (balance true as at)',
            'attendance_cut_off_help'           => 'Required. The date the opening balance was taken '
                . 'from the previous system.',
            'attendance_opening_balance'        => 'Opening balance (days)',
            'attendance_entitlement_days'       => 'Entitlement (days)',
            'attendance_carried_forward'        => 'Carried forward (days)',
            'attendance_carry_forward_expires'  => 'Carried forward expires on',
            'attendance_total_available'        => 'Total',
            'attendance_entitlement_added'      => 'Entitlement added.',
            'attendance_entitlement_updated'    => 'Entitlement updated.',
            'attendance_entitlement_invalid'    => 'An employee and a cut-off date are required, and '
                . 'only one record may exist per employee, leave type and year.',

            /* ------------------------------------------------- HR master data */
            'hr_masters'      => 'HR Master Data',
            'hr_masters_help' => 'Employee categories, visa and document types, lending rules, how a '
                . 'salary increment is split, and who approves what. Loans and increments stay inactive '
                . 'until the rules below are entered, so nothing is decided by a default.',

            'hr_employee_categories' => 'Employee Categories',
            'hr_employee_category'   => 'Employee Category',
            'hr_add_category'        => 'Add Category',
            'hr_categories_help'     => 'The category decides how many approval levels a request passes '
                . 'through and how an increment is split. Nothing in the application distinguished staff '
                . 'from labour before, so every rule that depends on it reads these rows.',
            'hr_approval_levels'      => 'Approval levels',
            'hr_approval_levels_help' => 'How many people sign off a request from this category. Used as '
                . 'the default when an approval flow does not define its own levels.',
            'hr_is_labour'      => 'Labour category',
            'hr_is_labour_help' => 'Ticked means an increment goes entirely to allowances instead of '
                . 'being split, and vacation encashment warnings do not apply.',
            'hr_vacation_alert_days'      => 'Vacation balance alert (days)',
            'hr_vacation_alert_days_help' => 'Notify the employee when their vacation balance goes above '
                . 'this. Leave blank for no alert.',
            'hr_no_alert'         => 'No alert',
            'hr_sort_order'       => 'Sort order',
            'hr_category_invalid' => 'A name and at least one approval level are required.',
            'hr_master_in_use'    => 'This record is in use and cannot be deleted. Deactivate it instead.',

            'hr_visa_types'      => 'Visa Types',
            'hr_visa_type'       => 'Visa Type',
            'hr_add_visa_type'   => 'Add Visa Type',
            'hr_visa_types_help' => 'The staff record already holds a visa expiry but never held a visa '
                . 'type. These are labels only, so rename or deactivate them freely.',
            'hr_visa_type_invalid' => 'A name is required.',

            'hr_document_types'     => 'Document Types',
            'hr_document_type'      => 'Document Type',
            'hr_add_document_type'  => 'Add Document Type',
            'hr_document_types_help' => 'Which documents an employee holds, how much notice is given '
                . 'before each expires, and which of them the employee may update themselves.',
            'hr_has_number'            => 'Has a number',
            'hr_has_expiry'            => 'Has an expiry',
            'hr_reminder_days'         => 'Reminder (days before expiry)',
            'hr_reminder_days_help'    => 'A passport usually needs far more notice than an insurance card.',
            'hr_employee_can_upload'   => 'Employee may upload',
            'hr_requires_hr_approval'  => 'HR must approve',
            'hr_updates_master_fields' => 'Updates staff record',
            'hr_staff_number_field'    => 'Staff record number field',
            'hr_staff_expiry_field'    => 'Staff record expiry field',
            'hr_master_field_help'     => 'When an update is approved these fields on the staff record '
                . 'are overwritten from the document, which is what keeps the master list current. '
                . 'Leave blank for documents with no matching field, such as an insurance card.',
            'hr_not_mapped'            => 'Not mapped',
            'hr_document_type_invalid' => 'A name is required, and the reminder must be between 0 and 3650 days.',

            'hr_loan_policy'      => 'Loan Policy',
            'hr_add_loan_policy'  => 'Add Loan Policy',
            'hr_no_loan_policy'   => 'No lending rules yet. Until one is added and activated, loan '
                . 'applications are accepted without any limit being checked.',
            'hr_loan_policy_help' => 'Your ceilings, minimum service and instalment limits. Nothing is '
                . 'seeded here on purpose: a guessed maximum would either block legitimate requests or '
                . 'wave through ones that should have been refused.',
            'hr_your_numbers_needed' => 'Your numbers are needed.',
            'hr_max_amount'          => 'Maximum amount',
            'hr_max_instalments'     => 'Maximum instalments',
            'hr_min_service_months'  => 'Minimum service (months)',
            'hr_max_concurrent_loans' => 'Maximum concurrent loans',
            'hr_max_instalment_percent' => 'Maximum instalment (% of salary)',
            'hr_any_category'           => 'Any category',
            'hr_loan_policy_active_help' => 'An active policy must set at least one limit. A policy that '
                . 'limits nothing looks like a control while enforcing none.',
            'hr_loan_policy_invalid'     => 'A policy already exists for that category, or it is active '
                . 'with no limit set at all.',

            'hr_increment_split'      => 'Increment Split',
            'hr_increment_split_help' => 'How an approved increment is distributed. The 50/30/20 '
                . 'percentages are already recorded, but the allowance rows the 30 and the 20 land on '
                . 'cannot be guessed. Pick the allowance types, then activate. Until each category adds '
                . 'up to 100% no increment is applied automatically.',
            'hr_add_split_rule'    => 'Add Split Rule',
            'hr_split_rule'        => 'Split Rule',
            'hr_slot'              => 'Slot',
            'hr_component'         => 'Component',
            'hr_component_basic'   => 'Basic salary',
            'hr_component_allowance' => 'Allowance',
            'hr_allowance_type'    => 'Allowance type',
            'hr_pick_allowance_type' => 'Pick an allowance type',
            'hr_no_allowance_types'  => 'No allowance types exist yet. Add them in HR Profile first, '
                . 'otherwise an allowance slice has nowhere to put the money.',
            'hr_percent'             => 'Percentage',
            'hr_label'               => 'Label',
            'hr_split_not_configured' => 'Not configured',
            'hr_split_must_total_100' => 'must total 100%',
            'hr_split_active_help'    => 'An active allowance slice must name an allowance type.',
            'hr_split_rule_invalid'   => 'A category and a percentage between 0 and 100 are required, '
                . 'and an active allowance slice must name an allowance type.',

            'hr_approval_flows'      => 'Approval Flows',
            'hr_approval_flow'       => 'Approval Flow',
            'hr_add_approval_flow'   => 'Add Approval Flow',
            'hr_no_approval_flows'   => 'No approval flows yet. Requests cannot be submitted until one '
                . 'exists, which is deliberate: nobody signing for money is a configuration mistake, '
                . 'not a shortcut.',
            'hr_approval_flows_help' => 'Who approves each type of request, in order. A flow for a '
                . 'specific employee category wins over the "any category" one, which is how labour '
                . 'gets three levels and staff two.',
            'hr_request_type'        => 'Request type',
            'hr_flow_category_help'  => 'Leave as "any category" for a single chain covering everyone.',
            'hr_levels'              => 'levels',
            'hr_flow_has_no_levels'  => 'No levels - nothing can be submitted',
            'hr_add_level'           => 'Add Level',
            'hr_approval_level'      => 'Approval Level',
            'hr_approver'            => 'Approver',
            'hr_can_edit_amounts'    => 'May set the approved amount',
            'hr_can_edit_amounts_help' => 'Tick for the level that decides the approved loan amount and '
                . 'instalment, rather than accepting what was requested.',
            'hr_sla_days'            => 'Days allowed',
            'hr_approval_flow_invalid' => 'A name and request type are required, and only one flow may '
                . 'exist per request type and category.',
            'hr_approval_level_invalid' => 'A level number is required, it must be unique within the '
                . 'flow, and a specific-person or role level must name one.',

            'hr_assignments'      => 'Employee Assignment',
            'hr_assignments_help' => 'Give each employee a category. Every rule that depends on staff '
                . 'versus labour reads this, so an employee with no category falls back to the "any '
                . 'category" flow and the general loan policy.',
            'hr_employee_profile'   => 'Employee HR Profile',
            'hr_employee_number'    => 'Employee number',
            'hr_joining_date'       => 'Joining date',
            'hr_joining_date_help'  => 'Leave blank to use the date the staff record was created. Set it '
                . 'for anyone brought over from another system, where that date is not when they joined.',
            'hr_from_record_created' => '(record created)',
            'hr_probation_end_date'  => 'Probation ends',
            'hr_salary_on_joining'   => 'Salary on joining',
            'hr_not_set'             => 'Not set',
            'hr_employees_without_category' => 'employees have no category yet.',
            'hr_bulk_pick_category'   => 'Pick a category',
            'hr_assign_to_selected'   => 'Assign to selected',
            'hr_employees_assigned'   => 'employees updated.',
            'hr_bulk_assign_nothing_selected' => 'Pick a category and at least one employee.',
            'hr_profile_save_failed'  => 'Could not save the profile.',

            /* -------------------------------------------------------- loans */
            'loan_applications'        => 'Loan Applications',
            'loan_application'         => 'Loan Application',
            'loan_new_application'     => 'New Loan Application',
            'loan_edit_application'    => 'Edit Loan Application',
            'loan_your_applications'   => 'your applications',
            'loan_none_yet'            => 'No loan applications yet.',
            'loan_employee'            => 'Employee',
            'loan_on_behalf_help'      => 'Leave as yourself, or pick another employee to apply on their behalf.',
            'loan_reason'              => 'Reason for the loan',
            'loan_requested_amount'    => 'Amount requested',
            'loan_proposed_instalment' => 'Proposed monthly instalment',
            'loan_first_instalment_date' => 'First instalment date',
            'loan_service_years'       => 'Years of service',
            'loan_outstanding'         => 'Outstanding balance',
            'loan_outstanding_at_request' => 'Outstanding at time of request',
            'loan_current_salary'      => 'Current salary',
            'loan_no_salary_on_file'   => 'No salary on file',
            'loan_reference'           => 'Reference',
            'loan_approved_amount'     => 'Approved amount',
            'loan_approved_instalment' => 'Approved instalment',
            'loan_repayment_schedule'  => 'Repayment schedule',
            'loan_instalment_no'       => '#',
            'loan_due_date'            => 'Due date',
            'loan_amount'              => 'Amount',
            'loan_instalments'         => 'instalments',
            'loan_instalments_max'     => 'instalments max',
            'loan_months_min_service'  => 'months minimum service',
            'loan_last_on'             => 'last on',
            'loan_due_by'              => 'due by',
            'loan_save_draft'          => 'Save draft',
            'loan_submit_for_approval' => 'Submit for approval',
            'loan_draft_saved'         => 'Draft saved.',
            'loan_submitted'           => 'Loan application submitted for approval.',
            'loan_save_failed'         => 'The application could not be saved.',
            'loan_deleted'             => 'Loan application deleted.',
            'loan_cancelled'           => 'Loan application cancelled.',
            'loan_cancel_request'      => 'Cancel request',
            'loan_only_drafts_deletable' => 'Only a draft may be deleted.',
            'loan_policy_summary'      => 'Policy',
            'loan_max'                 => 'up to',
            'loan_no_limits_set'       => 'no limits set',
            'loan_no_active_policy_notice' => 'No lending policy is active, so no limits are being '
                . 'checked. Ask HR to configure one under HR Master Data.',
            'loan_status_draft'        => 'Draft',
            'loan_status_pending'      => 'Pending approval',
            'loan_status_approved'     => 'Approved',
            'loan_status_disbursed'    => 'Disbursed',
            'loan_status_closed'       => 'Closed',
            'loan_status_rejected'     => 'Rejected',
            'loan_status_cancelled'    => 'Cancelled',
            'loan_instalment_status_scheduled' => 'Scheduled',
            'loan_instalment_status_deducted'  => 'Deducted',
            'loan_instalment_status_waived'    => 'Waived',
            'loan_instalment_status_skipped'   => 'Skipped',
            'loan_approval_progress'   => 'Approval progress',
            'loan_not_submitted_yet'   => 'Not submitted for approval yet.',
            'loan_your_decision'       => 'Your decision',
            'loan_you_set_the_amounts' => 'You set the approved amount and instalment at this level.',
            'loan_approval_note'       => 'Note (optional)',
            'loan_approve'             => 'Approve',
            'loan_reject'              => 'Reject',
            'loan_level_approved'      => 'Approved. Passed to the next level, or completed if this was the last.',
            'loan_rejected'            => 'Loan application rejected.',
            'loan_confirm_reject'      => 'Reject this loan application?',
            'loan_action_failed'       => 'The action could not be recorded.',
            'loan_no_flow_configured'  => 'No approval flow is configured for loans for this employee. '
                . 'Ask HR to set one up under HR Master Data before submitting.',
            'loan_flow_no_levels'      => 'The loan approval flow has no levels defined.',
            'loan_approver_not_resolved' => 'An approver in the chain could not be identified - check the '
                . 'employee has a direct manager or department head set.',
            'loan_submit_failed'       => 'The application could not be submitted.',
            'loan_error_amount_required'   => 'An amount greater than zero is required',
            'loan_error_instalment_required' => 'A monthly instalment greater than zero is required',
            'loan_error_instalment_exceeds_amount' => 'The instalment cannot be larger than the loan',
            'loan_error_over_max_amount'   => 'The amount is over the policy maximum of',
            'loan_error_min_service'       => 'This requires minimum service of months:',
            'loan_error_max_concurrent'    => 'The employee already has the maximum number of active loans',
            'loan_error_instalment_over_salary_percent' => 'The instalment is over the policy limit of',
            'loan_error_too_many_instalments' => 'That amount and instalment exceed the maximum instalments of',

            /* --------------------------------------------------- increments */
            'increment_requests'       => 'Increment Requests',
            'increment_request'        => 'Increment Request',
            'increment_new'            => 'New Increment Request',
            'increment_edit'           => 'Edit Increment Request',
            'increment_none_yet'       => 'No increment requests yet.',
            'increment_reason'         => 'Reason for the increment',
            'increment_amount'         => 'Increment amount',
            'increment_approved_amount' => 'Approved amount',
            'increment_effective_from' => 'Effective from',
            'increment_designation'    => 'Designation',
            'increment_joining_date'   => 'Joining date',
            'increment_service_years'  => 'Years of service',
            'increment_visa_type'      => 'Visa type',
            'increment_visa_expiry'    => 'Visa expiry',
            'increment_salary_on_joining' => 'Salary on joining',
            'increment_current_salary'    => 'Current salary',
            'increment_last_date'      => 'Last increment date',
            'increment_last_amount'    => 'Last increment amount',
            'increment_evaluation'     => 'Evaluation result',
            'increment_no_evaluation'  => 'No evaluation on file',
            'increment_automatic_context' => 'Automatically gathered',
            'increment_split_not_ready' => 'The increment split for this employee\'s category is not '
                . 'configured to total 100%. The increment can be submitted, but it will not apply to '
                . 'pay until the split is completed under HR Master Data.',
            'increment_full_history'   => 'Full increment history',
            'increment_change_type'    => 'Type',
            'increment_old'            => 'From',
            'increment_new_val'        => 'To',
            'increment_change'         => 'Change',
            'increment_type_joining'   => 'Joining',
            'increment_type_increment' => 'Increment',
            'increment_type_revision'  => 'Revision',
            'increment_type_promotion' => 'Promotion',
            'increment_type_correction' => 'Correction',
            'increment_save_failed'    => 'The request could not be saved. An amount greater than zero '
                . 'and an employee are required.',
            'increment_draft_saved'    => 'Draft saved.',
            'increment_submitted'      => 'Increment request submitted for approval.',
            'increment_deleted'        => 'Increment request deleted.',
            'increment_cancelled'      => 'Increment request cancelled.',
            'increment_only_drafts_deletable' => 'Only a draft may be deleted.',
            'increment_status_draft'     => 'Draft',
            'increment_status_pending'   => 'Pending approval',
            'increment_status_approved'  => 'Approved',
            'increment_status_applied'   => 'Applied to salary',
            'increment_status_rejected'  => 'Rejected',
            'increment_status_cancelled' => 'Cancelled',
            'increment_approval_progress' => 'Approval progress',
            'increment_you_set_amount'   => 'You may adjust the approved amount at this level.',
            'increment_level_approved'   => 'Approved. Passed to the next level.',
            'increment_rejected'         => 'Increment request rejected.',
            'increment_confirm_reject'   => 'Reject this increment request?',
            'increment_action_failed'    => 'The action could not be recorded.',
            'increment_approved_and_applied' => 'Approved and applied to the salary. Salary history updated.',
            'increment_approved_not_applied_alert' => 'Approved, but not yet applied to pay - the split '
                . 'for this category needs to total 100% under HR Master Data.',
            'increment_approved_not_applied' => 'An approved increment could not be applied to pay',
            'increment_apply_split_not_100' => 'the increment split for the employee\'s category does not total 100%',
            'increment_apply_no_contract'  => 'the employee has no valid salary contract to apply it to',
            'increment_apply_no_category'  => 'the employee has no category assigned',
            'increment_apply_failed_generic' => 'the increment could not be applied automatically',
            'increment_no_flow_configured' => 'No approval flow is configured for increments. Ask HR to '
                . 'set one up under HR Master Data before submitting.',
            'increment_flow_no_levels'     => 'The increment approval flow has no levels defined.',
            'increment_approver_not_resolved' => 'An approver could not be identified - check the '
                . 'employee has a direct manager or department head set.',
            'increment_submit_failed'      => 'The request could not be submitted.',

            /* ---------------------------------------------------- vacation */
            'vacation_over_balance_reversed' => 'Your leave request for %s day(s) was not accepted: '
                . 'only %s day(s) are available. Please adjust the dates and try again.',
            'vacation_overlap_notice'  => 'Note: colleagues in the same department are already on leave '
                . 'over this period -',
            'vacation_high_balance_notice' => 'Your leave balance is %s days, over the %s day guideline. '
                . 'Please plan to take leave, as encashment is not available.',
            'vacation_available_days'  => 'Available leave days',
            'vacation_cumulated_days'  => 'Cumulated days',

            /* ------------------------------------------- employee documents */
            'empdoc_my_documents'   => 'My Documents',
            'empdoc_documents'      => 'Documents',
            'empdoc_folder_help'    => 'Your personal documents. Upload a renewed passport, visa, ID or '
                . 'certificate here and enter its new number and expiry - HR is notified to confirm, '
                . 'and once approved the company records are updated automatically.',
            'empdoc_upload'         => 'Upload Document',
            'empdoc_none'           => 'No documents yet.',
            'empdoc_type'           => 'Type',
            'empdoc_number'         => 'Number',
            'empdoc_issue_date'     => 'Issue date',
            'empdoc_expiry_date'    => 'Expiry date',
            'empdoc_file'           => 'File',
            'empdoc_file_help'      => 'PDF or image. Optional, but recommended for HR to verify.',
            'empdoc_expired'        => 'Expired',
            'empdoc_expiring'       => 'Expiring soon',
            'empdoc_uploaded'       => 'Document saved.',
            'empdoc_uploaded_pending' => 'Uploaded. HR will review it before the company records update.',
            'empdoc_upload_failed'  => 'The document could not be saved. Check the file type and try again.',
            'empdoc_invalid_type'   => 'That document type is not available.',
            'empdoc_deleted'        => 'Document removed.',
            'empdoc_status_current'          => 'Current',
            'empdoc_status_pending_approval' => 'Pending HR approval',
            'empdoc_status_superseded'       => 'Replaced',
            'empdoc_status_rejected'         => 'Rejected',
            'empdoc_review_queue'   => 'Document Reviews',
            'empdoc_review_help'    => 'Employee document updates waiting for confirmation. Approving one '
                . 'makes it the current record and updates the master staff record.',
            'empdoc_nothing_to_review' => 'Nothing waiting for review.',
            'empdoc_uploaded_col'   => 'Uploaded',
            'empdoc_approve'        => 'Approve',
            'empdoc_reject'         => 'Reject',
            'empdoc_reject_reason'  => 'Reason (if rejecting)',
            'empdoc_approved'       => 'Document approved and the staff record updated.',
            'empdoc_rejected'       => 'Document update rejected.',
            'empdoc_expiring_soon'  => 'Expiring Documents',
            'empdoc_expiring_help'  => 'Documents due to expire soon, or already expired, across all staff.',
            'empdoc_nothing_expiring' => 'No documents are expiring soon.',
            'empdoc_days_left'      => 'Time left',
            'empdoc_update_approved' => 'Your %s update was approved and the company records are updated.',
            'empdoc_update_rejected' => 'Your %s update was not approved',
            'empdoc_pending_review'  => '%s uploaded a %s that needs your review.',
            'empdoc_alert_upcoming'  => 'Your %s expires in %s day(s), on %s. Please renew it and upload '
                . 'the new document.',
            'empdoc_alert_expired'   => 'Your %s expired on %s. Please renew it and upload the new document.',
            'empdoc_expired_ago'     => 'expired %s day(s) ago',
            'empdoc_in_days'         => 'in %s day(s)',

            /* --------------------------------------------- project scope */
            'pscope_tab_name'          => 'Scope / Activities',
            'pscope_overall_completion' => 'Overall completion',
            'pscope_items'             => 'items',
            'pscope_weighted'          => 'weighted by size',
            'pscope_budget'            => 'Budget',
            'pscope_material_budget'   => 'Material budget',
            'pscope_actual_spent'      => 'Actual (expenses)',
            'pscope_remaining'         => 'Remaining',
            'pscope_add_activity'      => 'Add Activity',
            'pscope_none'              => 'No scope activities yet. Break the project into items to track '
                . 'progress and budget item by item.',
            'pscope_code'              => 'Code',
            'pscope_activity'          => 'Activity',
            'pscope_responsible'       => 'Responsible',
            'pscope_department'        => 'Department',
            'pscope_weight'            => 'Weight',
            'pscope_progress'          => 'Progress',
            'pscope_quantity'          => 'Quantity',
            'pscope_quantity_done'     => 'Quantity done',
            'pscope_unit'              => 'Unit',
            'pscope_planned_start'     => 'Planned start',
            'pscope_planned_end'       => 'Planned end',
            'pscope_requires_approval' => 'Completion needs sign-off',
            'pscope_needs_signoff'     => 'sign-off required',
            'pscope_will_need_approval' => 'This update will be held for a manager to approve before it '
                . 'counts toward completion.',
            'pscope_saved'             => 'Activity saved.',
            'pscope_save_failed'       => 'The activity could not be saved. A name is required.',
            'pscope_deleted'           => 'Activity removed.',
            'pscope_update_progress'   => 'Update progress',
            'pscope_progress_percent'  => 'Progress (%)',
            'pscope_record'            => 'Record',
            'pscope_progress_recorded' => 'Progress updated.',
            'pscope_progress_pending'  => 'Progress recorded and sent for sign-off.',
            'pscope_progress_failed'   => 'The progress update could not be recorded.',
            'pscope_updates_awaiting'  => 'progress update(s) awaiting your sign-off.',
            'pscope_pending_signoff'   => 'Updates awaiting sign-off',
            'pscope_from_to'           => 'Change',
            'pscope_by'                => 'By',
            'pscope_reject_reason'     => 'Reason',
            'pscope_approve'           => 'Approve',
            'pscope_reject'            => 'Reject',
            'pscope_progress_approved' => 'Progress approved.',
            'pscope_progress_rejected' => 'Progress rejected.',
            'pscope_progress_awaiting_approval' => 'A progress update on "%s" needs your sign-off.',
            'pscope_approvals'         => 'Activity Sign-offs',
            'pscope_approvals_help'    => 'Progress updates across all projects waiting for a manager to '
                . 'confirm before they count toward completion.',
            'pscope_nothing_pending'   => 'Nothing waiting for sign-off.',

            /* ------------------------------------------------ approval engine */
            'approval_type_loan_application'    => 'Loan application',
            'approval_type_increment_request'   => 'Increment request',
            'approval_type_vacation_request'    => 'Vacation request',
            'approval_type_document_change'     => 'Document update',
            'approval_type_activity_completion' => 'Activity completion',
            'approval_level'                    => 'Level',
            'approval_awaiting_your_approval'   => 'awaiting your approval',
            'approval_was_approved'             => 'was approved',
            'approval_was_rejected'             => 'was rejected',
        ];

        foreach ($strings as $key => $value) {
            // Never override a string that already exists, so a translation or a future
            // official key always wins over these defaults.
            if (!isset($CI->lang->language[$key])) {
                $CI->lang->language[$key] = $value;
            }
        }
    }

    // app_init fires for every context including CLI, which matters because the cron
    // and the API also read these strings. admin_init and app_admin_head are kept so
    // the strings are present even if app_init has already run by the time a request
    // reaches the admin area.
    hooks()->add_action('app_init', 'attendance_extensions_lang');
    hooks()->add_action('app_admin_head', 'attendance_extensions_lang');
    hooks()->add_action('admin_init', 'attendance_extensions_lang');
}
