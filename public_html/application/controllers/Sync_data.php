<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync_data Controller
 * 
 * Syncs Hikvision biometric attendance data from MSSQL (ivmstable)
 * into NextGen ERP MySQL tables:
 *   - tblhrp_biometric_log          (raw punch log)
 *   - tbltimesheets_timekeeper_data (raw biometric punches for timesheets)
 *   - tblcheck_in_out               (check-in/out events)
 *   - tbltimesheets_timesheet       (daily work entries)
 *   - tblhrp_employees_timesheets   (monthly attendance summary for HR Payroll)
 *
 * URL: admin/sync_data/attendance
 * URL: admin/sync_data/attendance?days=30   (sync last 30 days)
 * URL: admin/sync_data/status               (check last sync info)
 */
class Sync_data extends CI_Controller {

    /** How many days back to sync by default — 1 = today only (live mode) */
    const DEFAULT_SYNC_DAYS = 1;

    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================================
    // PUBLIC ENDPOINTS
    // =========================================================================

    /**
     * Main sync endpoint
     * GET admin/sync_data/attendance?days=7
     */
    public function attendance()
    {
        $days = (int)($this->input->get('days') ?: self::DEFAULT_SYNC_DAYS);
        if ($days < 1 || $days > 365) $days = self::DEFAULT_SYNC_DAYS;

        $result = $this->_run_sync($days);

        if ($this->input->is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            echo '<pre>';
            echo "=== Hikvision Attendance Sync ===\n";
            echo "Sync period : last {$days} days\n";
            echo "From        : {$result['sync_from']}\n";
            echo "To          : {$result['sync_to']}\n";
            echo "MSSQL rows  : {$result['mssql_rows']}\n";
            echo "New punches : {$result['new_punches']}\n";
            echo "Attendance  : {$result['attendance_updated']}\n";
            echo "Timesheets  : {$result['timesheets_updated']}\n";
            echo "Errors      : " . implode(', ', $result['errors'] ?: ['none']) . "\n";
            echo "Status      : {$result['status']}\n";
            echo '</pre>';
        }
    }

    /**
     * Status endpoint — shows last sync info
     * GET admin/sync_data/status
     */
    public function status()
    {
        $mysql = $this->load->database('default', TRUE);

        $last = $mysql->query("SELECT MAX(synced_at) as last_sync, COUNT(*) as total 
                               FROM " . db_prefix() . "hrp_biometric_log")->row();

        $staff_count = $mysql->query("SELECT COUNT(DISTINCT staff_id) as cnt 
                                      FROM " . db_prefix() . "hrp_biometric_log 
                                      WHERE staff_id IS NOT NULL")->row();

        echo '<pre>';
        echo "Last sync    : " . ($last->last_sync ?? 'Never') . "\n";
        echo "Total punches: " . ($last->total ?? 0) . "\n";
        echo "Staff synced : " . ($staff_count->cnt ?? 0) . "\n";
        echo "\nTo run sync: " . site_url('admin/sync_data/attendance') . "\n";
        echo "To sync 30 days: " . site_url('admin/sync_data/attendance?days=30') . "\n";
        echo '</pre>';
    }

    // =========================================================================
    // CORE SYNC LOGIC
    // =========================================================================

    private function _run_sync($days)
    {
        $result = [
            'status'              => 'error',
            'sync_from'           => '',
            'sync_to'             => '',
            'mssql_rows'          => 0,
            'new_punches'         => 0,
            'attendance_updated'  => 0,
            'timesheets_updated'  => 0,
            'errors'              => [],
        ];

        $sync_from = ($days == 1) ? date('Y-m-d') : date('Y-m-d', strtotime("-{$days} days"));
        $sync_to   = date('Y-m-d');
        $result['sync_from'] = $sync_from;
        $result['sync_to']   = $sync_to;

        // ── Connect to MSSQL ─────────────────────────────────────────────────
        try {
            $mssql = $this->load->database('mssql_db', TRUE);
        } catch (Exception $e) {
            $result['errors'][] = 'MSSQL connection failed: ' . $e->getMessage();
            return $result;
        }

        // ── Connect to MySQL ──────────────────────────────────────────────────
        $mysql = $this->load->database('default', TRUE);

        // ── Ensure biometric log table exists ─────────────────────────────────
        $this->_ensure_biometric_log_table($mysql);

        // ── Ensure unique keys on check_in_out and timekeeper_data ────────────
        $this->_ensure_unique_keys($mysql);

        // ── Ensure default work shift exists (required for timekeeping page) ──
        $this->_ensure_work_shift_setup($mysql);

        // ── Fetch from MSSQL ──────────────────────────────────────────────────
        $sql = "SELECT TOP 10000 
                    id, hik_id, auth_datetime, auth_date, auth_time,
                    direction, device_name, device_serial, person_name, card_no
                FROM dbo.ivmstable
                WHERE auth_date >= '{$sync_from}' AND auth_date <= '{$sync_to}'
                ORDER BY hik_id, auth_date, auth_time";

        $query = $mssql->query($sql);
        if (!$query) {
            $result['errors'][] = 'MSSQL query failed';
            return $result;
        }

        $records = $query->result_array();
        $result['mssql_rows'] = count($records);

        if (empty($records)) {
            $result['status'] = 'ok';
            return $result;
        }

        // ── Step 1: Insert raw punches ────────────────────────────────────────
        $new_punches = $this->_insert_raw_punches($mysql, $records);
        $result['new_punches'] = $new_punches;

        // ── Step 2: Group by staff + date ─────────────────────────────────────
        $grouped = $this->_group_by_staff_date($records);

        // ── Step 3: Update attendance tables ─────────────────────────────────
        foreach ($grouped as $staff_code => $dates) {
            // Resolve staff_id from staff_identifi or card_no
            $staff_id = $this->_get_staff_id($mysql, $staff_code);

            foreach ($dates as $date => $punches) {
                sort($punches);
                $first_punch = $punches[0];
                $last_punch  = end($punches);

                // Calculate hours worked
                $hours = $this->_calc_hours($date, $first_punch, $last_punch);

                // 3a. tblcheck_in_out — raw check-in/out
                $this->_upsert_check_in_out($mysql, $staff_id, $date, $first_punch, $last_punch);

                // 3b. tbltimesheets_timekeeper_data — biometric raw data
                $this->_upsert_timekeeper_data($mysql, $staff_code, $date, $first_punch, $last_punch);

                if ($staff_id) {
                    // 3c. tbltimesheets_timesheet — daily work entry
                    $this->_upsert_timesheet($mysql, $staff_id, $date, $hours);
                    $result['timesheets_updated']++;

                    // 3d. tblhrp_employees_timesheets — monthly summary
                    $this->_upsert_hrp_timesheet($mysql, $staff_id, $date, $hours);
                    $result['attendance_updated']++;
                }
            }
        }

        $result['status'] = 'ok';
        return $result;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /** Ensure tblhrp_biometric_log exists */
    private function _ensure_biometric_log_table($mysql)
    {
        $mysql->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "hrp_biometric_log` (
            `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `staff_id`      INT DEFAULT NULL,
            `staff_code`    VARCHAR(50) DEFAULT NULL,
            `hik_id`        VARCHAR(50) DEFAULT NULL,
            `card_no`       VARCHAR(100) DEFAULT NULL,
            `punch_datetime` DATETIME DEFAULT NULL,
            `punch_date`    DATE DEFAULT NULL,
            `punch_time`    TIME DEFAULT NULL,
            `direction`     VARCHAR(20) DEFAULT NULL,
            `device_name`   VARCHAR(200) DEFAULT NULL,
            `device_serial` VARCHAR(200) DEFAULT NULL,
            `person_name`   VARCHAR(200) DEFAULT NULL,
            `synced_at`     DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_punch` (`hik_id`, `punch_datetime`),
            KEY `idx_staff_id`   (`staff_id`),
            KEY `idx_punch_date` (`punch_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    /**
     * Ensure unique keys exist on check_in_out and timekeeper_data
     * so INSERT IGNORE / ON DUPLICATE KEY UPDATE work correctly.
     * Safe to call on every sync — checks before altering.
     */
    private function _ensure_unique_keys($mysql)
    {
        // tblcheck_in_out: unique on (staff_id, date, type_check)
        $ck = $mysql->query("SELECT COUNT(*) as cnt
                             FROM information_schema.STATISTICS
                             WHERE TABLE_SCHEMA = DATABASE()
                               AND TABLE_NAME   = '" . db_prefix() . "check_in_out'
                               AND INDEX_NAME   = 'uk_checkinout'");
        if ($ck && (int)$ck->row()->cnt === 0) {
            @$mysql->query("ALTER TABLE `" . db_prefix() . "check_in_out`
                            ADD UNIQUE KEY `uk_checkinout` (`staff_id`, `date`, `type_check`)");
        }

        // tbltimesheets_timekeeper_data: unique on (staff_identifi, time, type)
        $tk = $mysql->query("SELECT COUNT(*) as cnt
                             FROM information_schema.STATISTICS
                             WHERE TABLE_SCHEMA = DATABASE()
                               AND TABLE_NAME   = '" . db_prefix() . "timesheets_timekeeper_data'
                               AND INDEX_NAME   = 'uk_tkdata'");
        if ($tk && (int)$tk->row()->cnt === 0) {
            @$mysql->query("ALTER TABLE `" . db_prefix() . "timesheets_timekeeper_data`
                            ADD UNIQUE KEY `uk_tkdata` (`staff_identifi`, `time`, `type`)");
        }
    }

    /**
     * Ensure default work shift setup exists.
     * Creates shift type, work shift, day details, assigns all active staff.
     * Also migrates rel_type='attendance' → 'none' for HR Payroll compatibility.
     */
    private function _ensure_work_shift_setup($mysql)
    {
        // Fix rel_type: 'attendance' → 'none' (HR Payroll queries WHERE rel_type='none')
        $mysql->query("UPDATE `" . db_prefix() . "hrp_employees_timesheets`
                       SET rel_type = 'none' WHERE rel_type = 'attendance'");

        // Step 1: Create default shift type if none exists
        $r = $mysql->query("SELECT COUNT(*) as cnt FROM `" . db_prefix() . "shift_type`");
        $cnt = ($r && $r->num_rows() > 0) ? (int)$r->row()->cnt : 0;
        if ($cnt == 0) {
            $mysql->query("INSERT INTO `" . db_prefix() . "shift_type`
                (shift_type_name, color, time_start_work, time_end_work,
                 start_lunch_break_time, end_lunch_break_time, description, datecreated, add_from)
                VALUES ('Standard Shift', '#4CAF50', '08:00', '17:00',
                 '12:00', '13:00',
                 'Default 8-hour work shift (auto-created by biometric sync)', NOW(), 1)");
        }
        $r = $mysql->query("SELECT id FROM `" . db_prefix() . "shift_type` ORDER BY id ASC LIMIT 1");
        $shift_type_id = ($r && $r->num_rows() > 0) ? (int)$r->row()->id : 1;

        // Step 2: Create default work shift if none exists
        $r = $mysql->query("SELECT COUNT(*) as cnt FROM `" . db_prefix() . "work_shift`");
        $cnt = ($r && $r->num_rows() > 0) ? (int)$r->row()->cnt : 0;
        if ($cnt == 0) {
            $detail = json_encode(['Mon'=>$shift_type_id,'Tue'=>$shift_type_id,'Wed'=>$shift_type_id,'Thu'=>$shift_type_id,'Fri'=>$shift_type_id,'Sat'=>0,'Sun'=>0]);
            $mysql->query("INSERT INTO `" . db_prefix() . "work_shift`
                (shift_code, shift_name, shift_type, add_from, date_create,
                 from_date, to_date, shifts_detail, type_shiftwork)
                VALUES ('WS001', 'Default Work Shift', '{$shift_type_id}', 1, CURDATE(),
                 '2024-01-01', '2030-12-31', '" . $mysql->escape_str($detail) . "', 'by_number_day')");
        }
        $r = $mysql->query("SELECT id FROM `" . db_prefix() . "work_shift` ORDER BY id ASC LIMIT 1");
        $work_shift_id = ($r && $r->num_rows() > 0) ? (int)$r->row()->id : 1;

        // Step 3: Populate number_day details (Mon=1 to Fri=5)
        $r = $mysql->query("SELECT COUNT(*) as cnt FROM `" . db_prefix() . "work_shift_detail_number_day`
                            WHERE work_shift_id = {$work_shift_id}");
        $cnt = ($r && $r->num_rows() > 0) ? (int)$r->row()->cnt : 0;
        if ($cnt == 0) {
            for ($day = 1; $day <= 5; $day++) {
                $mysql->query("INSERT IGNORE INTO `" . db_prefix() . "work_shift_detail_number_day`
                    (staff_id, number, shift_id, work_shift_id)
                    VALUES (0, {$day}, {$shift_type_id}, {$work_shift_id})");
            }
        }

        // Step 4: Populate day_name details
        $r = $mysql->query("SELECT COUNT(*) as cnt FROM `" . db_prefix() . "work_shift_detail_day_name`
                            WHERE work_shift_id = {$work_shift_id}");
        $cnt = ($r && $r->num_rows() > 0) ? (int)$r->row()->cnt : 0;
        if ($cnt == 0) {
            foreach (['Mon','Tue','Wed','Thu','Fri'] as $dn) {
                $mysql->query("INSERT IGNORE INTO `" . db_prefix() . "work_shift_detail_day_name`
                    (staff_id, day_name, shift_id, work_shift_id)
                    VALUES (0, '{$dn}', {$shift_type_id}, {$work_shift_id})");
            }
        }

        // Step 5: Assign all active staff to the work shift
        $staffs = $mysql->query("SELECT staffid FROM `" . db_prefix() . "staff` WHERE active = 1");
        if ($staffs) {
            foreach ($staffs->result_array() as $s) {
                $sid = (int)$s['staffid'];
                $exists = $mysql->query("SELECT id FROM `" . db_prefix() . "work_shift_detail`
                                         WHERE staff_id = {$sid} AND work_shift_id = {$work_shift_id} LIMIT 1");
                if ($exists && $exists->num_rows() == 0) {
                    $mysql->query("INSERT INTO `" . db_prefix() . "work_shift_detail`
                        (staff_id, date, shift_id, work_shift_id)
                        VALUES ({$sid}, NULL, {$shift_type_id}, {$work_shift_id})");
                }
            }
        }
    }

    private function _insert_raw_punches($mysql, $records)
    {
        $inserted = 0;
        foreach ($records as $r) {
            $hik_id        = $mysql->escape_str($r['hik_id'] ?? '');
            $auth_datetime = $mysql->escape_str($r['auth_datetime'] ?? ($r['auth_date'] . ' ' . $r['auth_time']));
            $auth_date     = $mysql->escape_str($r['auth_date'] ?? '');
            $auth_time     = $mysql->escape_str($r['auth_time'] ?? '');
            $direction     = $mysql->escape_str($r['direction'] ?? '');
            $device_name   = $mysql->escape_str($r['device_name'] ?? '');
            $device_serial = $mysql->escape_str($r['device_serial'] ?? '');
            $person_name   = $mysql->escape_str($r['person_name'] ?? '');
            $card_no       = $mysql->escape_str($r['card_no'] ?? '');

            // Resolve staff_id
            $staff_id = $this->_get_staff_id($mysql, $hik_id) ?: 'NULL';

            $mysql->query("INSERT IGNORE INTO " . db_prefix() . "hrp_biometric_log
                (staff_id, staff_code, hik_id, card_no, punch_datetime, punch_date, punch_time,
                 direction, device_name, device_serial, person_name, synced_at)
                VALUES
                ({$staff_id}, '{$hik_id}', '{$hik_id}', '{$card_no}', '{$auth_datetime}',
                 '{$auth_date}', '{$auth_time}', '{$direction}', '{$device_name}',
                 '{$device_serial}', '{$person_name}', NOW())");

            if ($mysql->affected_rows() > 0) $inserted++;
        }
        return $inserted;
    }

    /** Group records by staff_code → date → [times] */
    private function _group_by_staff_date($records)
    {
        $grouped = [];
        foreach ($records as $r) {
            $code = $r['hik_id'] ?? '';
            $date = $r['auth_date'] ?? '';
            $time = $r['auth_time'] ?? '';
            if (!$code || !$date || !$time) continue;
            $grouped[$code][$date][] = $time;
        }
        return $grouped;
    }

    /** Get MySQL staff_id from hik_id (matches staff_identifi or card_no) */
    private function _get_staff_id($mysql, $code)
    {
        if (!$code) return null;
        $code_esc = $mysql->escape_str($code);
        $row = $mysql->query("SELECT staffid FROM " . db_prefix() . "staff 
                              WHERE staff_identifi = '{$code_esc}' 
                                 OR staff_identifi = '{$code_esc}'
                              LIMIT 1")->row();
        return $row ? $row->staffid : null;
    }

    /** Calculate hours between first and last punch */
    private function _calc_hours($date, $first, $last)
    {
        $in  = strtotime($date . ' ' . $first);
        $out = strtotime($date . ' ' . $last);
        if ($out > $in) {
            return round(($out - $in) / 3600, 2);
        }
        return 0;
    }

    /** Insert check-in and check-out into tblcheck_in_out */
    private function _upsert_check_in_out($mysql, $staff_id, $date, $first, $last)
    {
        if (!$staff_id) return;
        $sid = (int)$staff_id;

        // Check-in (type_check = 1) — update time if already exists for this day
        $in_dt = $mysql->escape_str($date . ' ' . $first);
        $mysql->query("INSERT INTO " . db_prefix() . "check_in_out
            (staff_id, date, type_check, type)
            VALUES ({$sid}, '{$in_dt}', 1, 'W')
            ON DUPLICATE KEY UPDATE date = '{$in_dt}'");

        // Check-out (type_check = 2) — update to latest punch of the day
        if ($first !== $last) {
            $out_dt = $mysql->escape_str($date . ' ' . $last);
            $mysql->query("INSERT INTO " . db_prefix() . "check_in_out
                (staff_id, date, type_check, type)
                VALUES ({$sid}, '{$out_dt}', 2, 'W')
                ON DUPLICATE KEY UPDATE date = '{$out_dt}'");
        }
    }

    /** Insert raw punches into tbltimesheets_timekeeper_data */
    private function _upsert_timekeeper_data($mysql, $staff_code, $date, $first, $last)
    {
        $code = $mysql->escape_str($staff_code);

        $in_dt  = $mysql->escape_str($date . ' ' . $first);
        $out_dt = $mysql->escape_str($date . ' ' . $last);

        $mysql->query("INSERT IGNORE INTO " . db_prefix() . "timesheets_timekeeper_data
            (staff_identifi, time, type) VALUES ('{$code}', '{$in_dt}', 'check_in')");

        if ($first !== $last) {
            $mysql->query("INSERT IGNORE INTO " . db_prefix() . "timesheets_timekeeper_data
                (staff_identifi, time, type) VALUES ('{$code}', '{$out_dt}', 'check_out')");
        }
    }

    /** Upsert daily work entry in tbltimesheets_timesheet */
    private function _upsert_timesheet($mysql, $staff_id, $date, $hours)
    {
        $sid   = (int)$staff_id;
        $dt    = $mysql->escape_str($date);
        $hrs   = (float)$hours;

        $exists = $mysql->query("SELECT id FROM " . db_prefix() . "timesheets_timesheet
                                 WHERE staff_id = {$sid} AND date_work = '{$dt}' AND type = 'W'
                                 LIMIT 1")->row();
        if ($exists) {
            $mysql->query("UPDATE " . db_prefix() . "timesheets_timesheet
                           SET value = '{$hrs}'
                           WHERE staff_id = {$sid} AND date_work = '{$dt}' AND type = 'W'");
        } else {
            $mysql->query("INSERT INTO " . db_prefix() . "timesheets_timesheet
                           (staff_id, date_work, value, type, add_from)
                           VALUES ({$sid}, '{$dt}', '{$hrs}', 'W', {$sid})");
        }
    }

    /** Upsert monthly attendance summary in tblhrp_employees_timesheets */
    private function _upsert_hrp_timesheet($mysql, $staff_id, $date, $hours)
    {
        $sid        = (int)$staff_id;
        $month      = date('Y-m-01', strtotime($date));
        $day_num    = (int)date('j', strtotime($date));
        $day_col    = 'day_' . $day_num;
        $hrs        = (float)$hours;
        $month_esc  = $mysql->escape_str($month);

        $exists = $mysql->query("SELECT id FROM " . db_prefix() . "hrp_employees_timesheets
                                 WHERE staff_id = {$sid} AND month = '{$month_esc}'
                                 AND (rel_type = 'none' OR rel_type IS NULL)
                                 LIMIT 1")->row();
        if ($exists) {
            $mysql->query("UPDATE " . db_prefix() . "hrp_employees_timesheets
                           SET {$day_col} = {$hrs}
                           WHERE staff_id = {$sid} AND month = '{$month_esc}'
                           AND (rel_type = 'none' OR rel_type IS NULL)");
        } else {
            $mysql->query("INSERT INTO " . db_prefix() . "hrp_employees_timesheets
                           (staff_id, month, {$day_col}, rel_type)
                           VALUES ({$sid}, '{$month_esc}', {$hrs}, 'none')");
        }
    }
}
