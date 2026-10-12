<?php
/**
 * Hikvision Biometric Attendance Sync — Live Mode
 *
 * Syncs data from MSSQL [dbo].[ivmstable] into NextGen ERP MySQL tables:
 *   - tblhrp_biometric_log          (raw punch log — UNIQUE on hik_id+punch_datetime)
 *   - tbltimesheets_timekeeper_data (raw punches — UNIQUE on staff_identifi+time+type)
 *   - tblcheck_in_out               (check-in/out — UNIQUE on staff_id+date+type_check)
 *   - tbltimesheets_timesheet       (daily work entries — upsert by staff_id+date_work)
 *   - tblhrp_employees_timesheets   (monthly summary — upsert by staff_id+month)
 *
 * USAGE:
 *   Today only (default):  sync_hikvision_attendance.php
 *   Date range:            sync_hikvision_attendance.php?from_date=2026-01-01&to_date=2026-08-18
 *   Last N days:           sync_hikvision_attendance.php?days=30
 *
 * CLI:
 *   php sync_hikvision_attendance.php                    → today only
 *   php sync_hikvision_attendance.php 30                 → last 30 days
 *   php sync_hikvision_attendance.php from_date 2026-01-01 2026-08-18  → date range
 *
 * Deduplication: every table uses INSERT IGNORE + unique keys OR upsert logic,
 * so running this script multiple times per day is safe — no duplicate rows.
 *
 * Run via cron (every 5 min):  php sync_hikvision_attendance.php
 */

// ── Bootstrap ────────────────────────────────────────────────────────────────
define('BASEPATH', __DIR__ . '/system/');
define('APPPATH',  __DIR__ . '/application/');
require_once APPPATH . 'config/app-config.php';

// Default behavior:
// - /sync_hikvision_attendance.php/2026-07-01/2026-08-17  → date range (firewall-safe)
// - /sync_hikvision_attendance.php/30                      → last 30 days
// - /sync_hikvision_attendance.php                         → today only
// - ?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD              → query string (if allowed)
// - ?days=N                                                → last N days
// - CLI: php sync_hikvision_attendance.php 2026-07-01 2026-08-17
// - CLI: php sync_hikvision_attendance.php 30

$days      = null;
$sync_from = null;
$sync_to   = date('Y-m-d');

// Method 1: Parse URL path after the script name
// e.g. /sync_hikvision_attendance.php/2026-07-01/2026-08-17
$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$path_info = '';
if (!empty($request_uri) && !empty($script_name)) {
    $uri_path = parse_url($request_uri, PHP_URL_PATH);
    if (strpos($uri_path, $script_name) === 0 && strlen($uri_path) > strlen($script_name)) {
        $path_info = trim(substr($uri_path, strlen($script_name)), '/');
    }
}
// Also check PHP PATH_INFO
if (empty($path_info) && isset($_SERVER['PATH_INFO'])) {
    $path_info = trim($_SERVER['PATH_INFO'], '/');
}

$path_parts = !empty($path_info) ? explode('/', $path_info) : [];

if (!empty($path_parts)) {
    if (count($path_parts) >= 2 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $path_parts[0])) {
        // /2026-07-01/2026-08-17
        $sync_from = $path_parts[0];
        $sync_to   = $path_parts[1];
    } elseif (count($path_parts) == 1 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $path_parts[0])) {
        // /2026-07-01 (single date = from that date to today)
        $sync_from = $path_parts[0];
        $sync_to   = date('Y-m-d');
    } elseif (count($path_parts) == 1 && is_numeric($path_parts[0])) {
        // /30 (last N days)
        $days = max(1, min(3650, (int)$path_parts[0]));
        $sync_from = date('Y-m-d', strtotime("-{$days} days"));
        $sync_to   = date('Y-m-d');
    } elseif ($path_parts[0] === 'from' && isset($path_parts[1])) {
        // /from/2026-07-01/to/2026-08-17
        $sync_from = $path_parts[1];
        $sync_to = (isset($path_parts[2]) && $path_parts[2] === 'to' && isset($path_parts[3])) ? $path_parts[3] : date('Y-m-d');
    } elseif ($path_parts[0] === 'days' && isset($path_parts[1]) && is_numeric($path_parts[1])) {
        // /days/30
        $days = max(1, min(3650, (int)$path_parts[1]));
        $sync_from = date('Y-m-d', strtotime("-{$days} days"));
        $sync_to   = date('Y-m-d');
    }
}

// Method 2: Query string parameters (fallback — works on localhost/port 5225)
if ($sync_from === null && ((isset($_GET['from_date']) && !empty($_GET['from_date'])) || (isset($argv[2]) && !empty($argv[2])))) {
    $sync_from = isset($_GET['from_date']) ? $_GET['from_date'] : (isset($argv[2]) ? $argv[2] : null);
    $sync_to   = isset($_GET['to_date']) && !empty($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
} elseif ($sync_from === null && (isset($_GET['days']) || (isset($argv[1]) && is_numeric($argv[1])))) {
    $days = isset($_GET['days']) ? (int)$_GET['days'] : (int)$argv[1];
    $days = max(1, min(3650, $days));
    $sync_from = date('Y-m-d', strtotime("-{$days} days"));
    $sync_to   = date('Y-m-d');
}

// Method 3: CLI positional args
if ($sync_from === null && isset($argv[1])) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[1])) {
        $sync_from = $argv[1];
        $sync_to = isset($argv[2]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[2]) ? $argv[2] : date('Y-m-d');
    } elseif (is_numeric($argv[1])) {
        $days = max(1, min(3650, (int)$argv[1]));
        $sync_from = date('Y-m-d', strtotime("-{$days} days"));
        $sync_to = date('Y-m-d');
    }
}

// Final default: sync TODAY only
if ($sync_from === null) {
    $sync_from = date('Y-m-d');
    $sync_to   = date('Y-m-d');
}

// Validate date formats
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sync_from)) {
    die("Invalid from_date format. Use YYYY-MM-DD\n");
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sync_to)) {
    die("Invalid to_date format. Use YYYY-MM-DD\n");
}
$db_prefix = defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl';

header('Content-Type: text/plain; charset=utf-8');
echo "=== Hikvision Attendance Sync ===\n";

// ── MySQL connection ──────────────────────────────────────────────────────────
$mysql = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($mysql->connect_error) {
    die("MySQL connection failed: " . $mysql->connect_error . "\n");
}
$mysql->set_charset('utf8mb4');
echo "MySQL connected.\n";

// ── Create sync_history table if not exists ──────────────────────────────────
$mysql->query("CREATE TABLE IF NOT EXISTS `{$db_prefix}hikvision_sync_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sync_from` DATE NOT NULL,
    `sync_to` DATE NOT NULL,
    `records_fetched` INT DEFAULT 0,
    `new_punches` INT DEFAULT 0,
    `synced_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// ── Determine sync_from if not manually set ──────────────────────────────────
echo "Sync period: {$sync_from} to {$sync_to}\n\n";

// ── MSSQL connection ──────────────────────────────────────────────────────────
$mssql_host = '172.168.20.45';
$mssql_port = '1433';
$mssql_db   = 'IVMSDB';
$mssql_user = 'att_user';
$mssql_pass = 'Ivms@sql';

$dsn = "sqlsrv:Server={$mssql_host},{$mssql_port};Database={$mssql_db};TrustServerCertificate=1;Encrypt=no;LoginTimeout=10";

try {
    $mssql = new PDO($dsn, $mssql_user, $mssql_pass);
    $mssql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "MSSQL connected.\n";
} catch (PDOException $e) {
    die("MSSQL connection FAILED: " . $e->getMessage() . "\n");
}
// ── Ensure tables + unique keys exist ────────────────────────────────────────
ensure_tables($mysql, $db_prefix);

// ── Ensure default work shift exists ─────────────────────────────────────────
ensure_work_shift_setup($mysql, $db_prefix);

// ── Fetch from MSSQL (date range — no TOP limit for full sync) ───────────────
$stmt = $mssql->prepare(
    "SELECT
         id, hik_id, auth_datetime, auth_date, auth_time,
         direction, device_name, device_serial, person_name, card_no
     FROM dbo.ivmstable
     WHERE auth_date >= :from_date AND auth_date <= :to_date
     ORDER BY hik_id, auth_date, auth_time"
);
$stmt->execute([':from_date' => $sync_from, ':to_date' => $sync_to]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "MSSQL rows fetched: " . count($records) . "\n\n";

if (empty($records)) {
    echo "No records to sync.\n";
    $mysql->close();
    die;
}

// ── Staff ID cache ────────────────────────────────────────────────────────────
$staff_cache = [];
function get_staff_id_cached($mysql, $db_prefix, $code, &$cache) {
    if (isset($cache[$code])) return $cache[$code];
    $esc = $mysql->real_escape_string($code);
    $r   = $mysql->query("SELECT staffid FROM {$db_prefix}staff
                          WHERE staff_identifi = '{$esc}' LIMIT 1");
    $id  = ($r && $r->num_rows > 0) ? (int)$r->fetch_assoc()['staffid'] : null;
    $cache[$code] = $id;
    return $id;
}

// ── Step 1: Insert raw punches (INSERT IGNORE — safe to re-run) ──────────────
echo "--- Step 1: Raw biometric log ---\n";
$new_punches = 0;
foreach ($records as $r) {
    $hik_id        = $mysql->real_escape_string($r['hik_id'] ?? '');
    $auth_datetime = $mysql->real_escape_string($r['auth_datetime'] ?? ($r['auth_date'] . ' ' . $r['auth_time']));
    $auth_date     = $mysql->real_escape_string($r['auth_date'] ?? '');
    $auth_time     = $mysql->real_escape_string($r['auth_time'] ?? '');
    $direction     = $mysql->real_escape_string($r['direction'] ?? '');
    $device_name   = $mysql->real_escape_string($r['device_name'] ?? '');
    $device_serial = $mysql->real_escape_string($r['device_serial'] ?? '');
    $person_name   = $mysql->real_escape_string($r['person_name'] ?? '');
    $card_no       = $mysql->real_escape_string($r['card_no'] ?? '');
    $staff_id      = get_staff_id_cached($mysql, $db_prefix, $r['hik_id'] ?? '', $staff_cache);
    $sid_sql       = $staff_id ? $staff_id : 'NULL';

    // UNIQUE KEY uk_punch (hik_id, punch_datetime) prevents duplicates
    $mysql->query("INSERT IGNORE INTO {$db_prefix}hrp_biometric_log
        (staff_id, staff_code, hik_id, card_no, punch_datetime, punch_date, punch_time,
         direction, device_name, device_serial, person_name, synced_at)
        VALUES
        ({$sid_sql}, '{$hik_id}', '{$hik_id}', '{$card_no}', '{$auth_datetime}',
         '{$auth_date}', '{$auth_time}', '{$direction}', '{$device_name}',
         '{$device_serial}', '{$person_name}', NOW())");

    if ($mysql->affected_rows > 0) $new_punches++;
}
echo "New raw punches inserted: {$new_punches}\n\n";

// ── Step 2: Group by staff_code → date → [times] ─────────────────────────────
$grouped = [];
foreach ($records as $r) {
    $code = $r['hik_id'] ?? '';
    $date = $r['auth_date'] ?? '';
    $time = $r['auth_time'] ?? '';
    if (!$code || !$date || !$time) continue;
    $grouped[$code][$date][] = $time;
}

// ── Step 3: Process each staff + date ────────────────────────────────────────
echo "--- Step 2: Processing attendance ---\n";
$att_updated = 0;
$ts_updated  = 0;
$skipped     = 0;

foreach ($grouped as $staff_code => $dates) {
    $staff_id = get_staff_id_cached($mysql, $db_prefix, $staff_code, $staff_cache);

    if (!$staff_id) {
        echo "  SKIP: staff_code '{$staff_code}' not found in {$db_prefix}staff.staff_identifi\n";
        $skipped++;
        continue;
    }

    foreach ($dates as $date => $times) {
        sort($times);
        $first = $times[0];
        $last  = end($times);

        $in_ts  = strtotime($date . ' ' . $first);
        $out_ts = strtotime($date . ' ' . $last);
        $hours  = ($out_ts > $in_ts) ? round(($out_ts - $in_ts) / 3600, 2) : 0;

        $in_dt  = $mysql->real_escape_string($date . ' ' . $first);
        $out_dt = $mysql->real_escape_string($date . ' ' . $last);
        $code_esc = $mysql->real_escape_string($staff_code);

        // ── 3a. tblcheck_in_out ───────────────────────────────────────────────
        // UNIQUE KEY uk_checkinout (staff_id, date, type_check) prevents duplicates
        $mysql->query("INSERT IGNORE INTO {$db_prefix}check_in_out
            (staff_id, date, type_check, type)
            VALUES ({$staff_id}, '{$in_dt}', 1, 'W')
            ON DUPLICATE KEY UPDATE date = '{$in_dt}'");

        if ($first !== $last) {
            $mysql->query("INSERT INTO {$db_prefix}check_in_out
                (staff_id, date, type_check, type)
                VALUES ({$staff_id}, '{$out_dt}', 2, 'W')
                ON DUPLICATE KEY UPDATE date = '{$out_dt}'");
        }

        // ── 3b. tbltimesheets_timekeeper_data ─────────────────────────────────
        // UNIQUE KEY uk_tkdata (staff_identifi, time, type) prevents duplicates
        $mysql->query("INSERT IGNORE INTO {$db_prefix}timesheets_timekeeper_data
            (staff_identifi, time, type) VALUES ('{$code_esc}', '{$in_dt}', 'check_in')");
        if ($first !== $last) {
            $mysql->query("INSERT IGNORE INTO {$db_prefix}timesheets_timekeeper_data
                (staff_identifi, time, type) VALUES ('{$code_esc}', '{$out_dt}', 'check_out')");
        }

        // ── 3c. tbltimesheets_timesheet — upsert daily hours ─────────────────
        $date_esc = $mysql->real_escape_string($date);
        $existing_ts = $mysql->query("SELECT id FROM {$db_prefix}timesheets_timesheet
                                      WHERE staff_id = {$staff_id}
                                        AND date_work = '{$date_esc}'
                                        AND type = 'W' LIMIT 1");
        if ($existing_ts && $existing_ts->num_rows > 0) {
            $mysql->query("UPDATE {$db_prefix}timesheets_timesheet
                           SET value = '{$hours}'
                           WHERE staff_id = {$staff_id}
                             AND date_work = '{$date_esc}'
                             AND type = 'W'");
        } else {
            $mysql->query("INSERT INTO {$db_prefix}timesheets_timesheet
                           (staff_id, date_work, value, type, add_from)
                           VALUES ({$staff_id}, '{$date_esc}', '{$hours}', 'W', {$staff_id})");
        }
        $ts_updated++;

        // ── 3d. tblhrp_employees_timesheets — upsert monthly summary ──────────
        $month     = date('Y-m-01', strtotime($date));
        $day_num   = (int)date('j', strtotime($date));
        $day_col   = 'day_' . $day_num;
        $month_esc = $mysql->real_escape_string($month);

        $existing_hrp = $mysql->query("SELECT id FROM {$db_prefix}hrp_employees_timesheets
                                       WHERE staff_id = {$staff_id}
                                         AND month = '{$month_esc}'
                                         AND (rel_type = 'none' OR rel_type IS NULL)
                                       LIMIT 1");
        if ($existing_hrp && $existing_hrp->num_rows > 0) {
            $mysql->query("UPDATE {$db_prefix}hrp_employees_timesheets
                           SET {$day_col} = {$hours}
                           WHERE staff_id = {$staff_id}
                             AND month = '{$month_esc}'
                             AND (rel_type = 'none' OR rel_type IS NULL)");
        } else {
            $mysql->query("INSERT INTO {$db_prefix}hrp_employees_timesheets
                           (staff_id, month, {$day_col}, rel_type)
                           VALUES ({$staff_id}, '{$month_esc}', {$hours}, 'none')");
        }
        $att_updated++;
    }
}

// ── Summary ───────────────────────────────────────────────────────────────────
echo "\n=== Sync Complete ===\n";
echo "MSSQL rows fetched    : " . count($records) . "\n";
echo "New raw punches       : {$new_punches}\n";
echo "Timesheets updated    : {$ts_updated}\n";
echo "Attendance updated    : {$att_updated}\n";
echo "Staff not found       : {$skipped}\n";
echo "Synced at             : " . date('Y-m-d H:i:s') . "\n";

if ($skipped > 0) {
    echo "\nNOTE: {$skipped} staff code(s) not matched.\n";
    echo "Fix: Staff profile → set 'Staff Identifi' to match hik_id from Hikvision.\n";
}

// ── Log successful sync ───────────────────────────────────────────────────────
$mysql->query("INSERT INTO `{$db_prefix}hikvision_sync_log`
    (sync_from, sync_to, records_fetched, new_punches, synced_at)
    VALUES ('{$mysql->real_escape_string($sync_from)}', '{$mysql->real_escape_string($sync_to)}',
            " . count($records) . ", {$new_punches}, NOW())");
echo "\nSync logged successfully.\n";

$mysql->close();

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Ensure all required tables exist and have the correct unique keys
 * for deduplication. Safe to run on every sync iteration.
 */
function ensure_tables($mysql, $db_prefix) {

    // ── tblhrp_biometric_log ─────────────────────────────────────────────────
    $mysql->query("CREATE TABLE IF NOT EXISTS `{$db_prefix}hrp_biometric_log` (
        `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id`       INT DEFAULT NULL,
        `staff_code`     VARCHAR(50) DEFAULT NULL,
        `hik_id`         VARCHAR(50) DEFAULT NULL,
        `card_no`        VARCHAR(100) DEFAULT NULL,
        `punch_datetime` DATETIME DEFAULT NULL,
        `punch_date`     DATE DEFAULT NULL,
        `punch_time`     TIME DEFAULT NULL,
        `direction`      VARCHAR(20) DEFAULT NULL,
        `device_name`    VARCHAR(200) DEFAULT NULL,
        `device_serial`  VARCHAR(200) DEFAULT NULL,
        `person_name`    VARCHAR(200) DEFAULT NULL,
        `synced_at`      DATETIME DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_punch` (`hik_id`, `punch_datetime`),
        KEY `idx_staff_id`   (`staff_id`),
        KEY `idx_punch_date` (`punch_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ── tblcheck_in_out: add unique key if missing ───────────────────────────
    // Prevents duplicate check-in/out rows on repeated syncs
    $ck = $mysql->query("SELECT COUNT(*) as cnt
                         FROM information_schema.STATISTICS
                         WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME   = '{$db_prefix}check_in_out'
                           AND INDEX_NAME   = 'uk_checkinout'");
    if ($ck && $ck->fetch_assoc()['cnt'] == 0) {
        // No prefix lengths — date/datetime columns don't support them
        @$mysql->query("ALTER TABLE `{$db_prefix}check_in_out`
                        ADD UNIQUE KEY `uk_checkinout` (`staff_id`, `date`, `type_check`)");
    }

    // ── tbltimesheets_timekeeper_data: add unique key if missing ─────────────
    $tk = $mysql->query("SELECT COUNT(*) as cnt
                         FROM information_schema.STATISTICS
                         WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME   = '{$db_prefix}timesheets_timekeeper_data'
                           AND INDEX_NAME   = 'uk_tkdata'");
    if ($tk && $tk->fetch_assoc()['cnt'] == 0) {
        // time is DATETIME — no prefix. staff_identifi and type are VARCHAR — no prefix needed.
        @$mysql->query("ALTER TABLE `{$db_prefix}timesheets_timekeeper_data`
                        ADD UNIQUE KEY `uk_tkdata` (`staff_identifi`, `time`, `type`)");
    }
}

/**
 * Ensure default work shift setup exists.
 * Creates shift types, work shifts, and day details if missing.
 * 
 * Required Shift Schedules:
 *   - Office Staff: 8:00 AM – 5:00 PM
 *   - Day Shift:    7:00 AM – 7:00 PM
 *   - Night Shift:  7:00 PM – 7:00 AM
 */
function ensure_work_shift_setup($mysql, $db_prefix) {

    // Fix rel_type: migrate 'attendance' → 'none'
    $mysql->query("UPDATE `{$db_prefix}hrp_employees_timesheets`
                   SET rel_type = 'none' WHERE rel_type = 'attendance'");
    $fixed = $mysql->affected_rows;
    if ($fixed > 0) {
        echo "  Migrated {$fixed} record(s): rel_type 'attendance' → 'none'\n";
    }

    // ── Define required shift types ──────────────────────────────────────────
    $required_shifts = [
        [
            'name'        => 'Office Staff',
            'color'       => '#4CAF50',
            'time_start'  => '08:00',
            'time_end'    => '17:00',
            'lunch_start' => '12:00',
            'lunch_end'   => '13:00',
            'description' => 'Office Staff shift: 8:00 AM – 5:00 PM (9 hrs, 1 hr lunch)',
        ],
        [
            'name'        => 'Day Shift',
            'color'       => '#2196F3',
            'time_start'  => '07:00',
            'time_end'    => '19:00',
            'lunch_start' => '12:00',
            'lunch_end'   => '13:00',
            'description' => 'Day Shift: 7:00 AM – 7:00 PM (12 hrs)',
        ],
        [
            'name'        => 'Night Shift',
            'color'       => '#9C27B0',
            'time_start'  => '19:00',
            'time_end'    => '07:00',
            'lunch_start' => '00:00',
            'lunch_end'   => '01:00',
            'description' => 'Night Shift: 7:00 PM – 7:00 AM (12 hrs, crosses midnight)',
        ],
    ];

    $shift_type_ids = [];
    foreach ($required_shifts as $shift) {
        $name_esc = $mysql->real_escape_string($shift['name']);
        $r = $mysql->query("SELECT id FROM `{$db_prefix}shift_type` WHERE shift_type_name = '{$name_esc}' LIMIT 1");
        if ($r && $r->num_rows > 0) {
            $shift_type_ids[$shift['name']] = (int)$r->fetch_assoc()['id'];
        } else {
            $desc_esc = $mysql->real_escape_string($shift['description']);
            $mysql->query("INSERT INTO `{$db_prefix}shift_type`
                (shift_type_name, color, time_start_work, time_end_work,
                 start_lunch_break_time, end_lunch_break_time, description, datecreated, add_from)
                VALUES ('{$name_esc}', '{$shift['color']}', '{$shift['time_start']}', '{$shift['time_end']}',
                 '{$shift['lunch_start']}', '{$shift['lunch_end']}', '{$desc_esc}', NOW(), 1)");
            $shift_type_ids[$shift['name']] = (int)$mysql->insert_id;
            echo "  Created shift type: {$shift['name']} ({$shift['time_start']}–{$shift['time_end']})\n";
        }
    }

    // Use Office Staff as the default shift for work shift assignment
    $default_shift_id = $shift_type_ids['Office Staff'];

    // Step 2: Create default work shift if none exists
    $r   = $mysql->query("SELECT COUNT(*) as cnt FROM `{$db_prefix}work_shift`");
    $cnt = $r ? $r->fetch_assoc()['cnt'] : 0;
    if ($cnt == 0) {
        $detail = json_encode([
            'Mon' => $default_shift_id, 'Tue' => $default_shift_id, 'Wed' => $default_shift_id,
            'Thu' => $default_shift_id, 'Fri' => $default_shift_id, 'Sat' => 0, 'Sun' => 0
        ]);
        $detail_esc = $mysql->real_escape_string($detail);
        $mysql->query("INSERT INTO `{$db_prefix}work_shift`
            (shift_code, shift_name, shift_type, add_from, date_create,
             from_date, to_date, shifts_detail, type_shiftwork)
            VALUES ('WS001', 'Office Staff Schedule', '{$default_shift_id}', 1, CURDATE(),
             '2024-01-01', '2030-12-31', '{$detail_esc}', 'by_number_day')");
        echo "  Created default work shift: Office Staff Schedule (Mon-Fri)\n";
    }
    $r = $mysql->query("SELECT id FROM `{$db_prefix}work_shift` ORDER BY id ASC LIMIT 1");
    $work_shift_id = $r ? (int)$r->fetch_assoc()['id'] : 1;

    // Step 3: Populate number_day details (Mon=1 to Fri=5)
    $r   = $mysql->query("SELECT COUNT(*) as cnt FROM `{$db_prefix}work_shift_detail_number_day`
                          WHERE work_shift_id = {$work_shift_id}");
    $cnt = $r ? $r->fetch_assoc()['cnt'] : 0;
    if ($cnt == 0) {
        for ($day = 1; $day <= 5; $day++) {
            $mysql->query("INSERT IGNORE INTO `{$db_prefix}work_shift_detail_number_day`
                (staff_id, number, shift_id, work_shift_id)
                VALUES (0, {$day}, {$default_shift_id}, {$work_shift_id})");
        }
        echo "  Created work shift day details: Mon-Fri\n";
    }

    // Step 4: Populate day_name details
    $r   = $mysql->query("SELECT COUNT(*) as cnt FROM `{$db_prefix}work_shift_detail_day_name`
                          WHERE work_shift_id = {$work_shift_id}");
    $cnt = $r ? $r->fetch_assoc()['cnt'] : 0;
    if ($cnt == 0) {
        foreach (['Mon','Tue','Wed','Thu','Fri'] as $dn) {
            $mysql->query("INSERT IGNORE INTO `{$db_prefix}work_shift_detail_day_name`
                (staff_id, day_name, shift_id, work_shift_id)
                VALUES (0, '{$dn}', {$default_shift_id}, {$work_shift_id})");
        }
        echo "  Created work shift day_name details: Mon-Fri\n";
    }

    // Step 5: Assign all active staff to the work shift (if not already assigned)
    $r = $mysql->query("SELECT staffid FROM `{$db_prefix}staff` WHERE active = 1");
    $assigned = 0;
    while ($row = $r->fetch_assoc()) {
        $sid    = (int)$row['staffid'];
        $exists = $mysql->query("SELECT id FROM `{$db_prefix}work_shift_detail`
                                 WHERE staff_id = {$sid} AND work_shift_id = {$work_shift_id} LIMIT 1");
        if ($exists && $exists->num_rows == 0) {
            $mysql->query("INSERT INTO `{$db_prefix}work_shift_detail`
                (staff_id, date, shift_id, work_shift_id)
                VALUES ({$sid}, NULL, {$default_shift_id}, {$work_shift_id})");
            $assigned++;
        }
    }
    if ($assigned > 0) {
        echo "  Assigned {$assigned} staff to default work shift\n";
    }
}
