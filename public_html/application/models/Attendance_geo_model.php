<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Attendance geolocation and punch recording.
 *
 * This is the bridge between the mobile/face API and the attendance system that the
 * admin screens and payroll already use. Punches are written to tblcheck_in_out -
 * the table the timekeeping grid, the timesheet generator and the hr_payroll bridge
 * all read - so a mobile punch shows up in the existing screens with no new UI.
 *
 * WHY THIS LIVES IN application/models AND NOT IN modules/timesheets
 * The timesheets module is third-party (GreenTech, v1.2.3). Editing its model would
 * work, but the next vendor update would overwrite it. Everything here therefore sits
 * in the application layer and calls into the module's public methods -
 * compute_distance() and add_check_in_out_value_to_timesheet() - so the module stays
 * upgradeable and the distance maths is not duplicated.
 */
class Attendance_geo_model extends App_Model
{
    /** Mirrors Timesheets_model::check_attendance_by_coordinates() error codes. */
    const GEO_INSIDE  = 1;
    const GEO_OUTSIDE = 2;
    const GEO_UNKNOWN = 3;

    private $location_table;
    private $reason_table;
    private $punch_table;

    public function __construct()
    {
        parent::__construct();

        $this->location_table = db_prefix() . 'attendance_project_location';
        $this->reason_table   = db_prefix() . 'attendance_reason';
        $this->punch_table    = db_prefix() . 'check_in_out';
    }

    /* ------------------------------------------------------------ locations */

    /**
     * The geofence configured for a project, if any.
     *
     * @param int $project_id
     *
     * @return object|null Null when the project has no location, which means no
     *                     geofence applies - the feature is opt-in per project.
     */
    public function get_project_location($project_id)
    {
        if (empty($project_id) || !is_numeric($project_id)) {
            return null;
        }

        if (!$this->db->table_exists($this->location_table)) {
            return null;
        }

        $this->db->where('project_id', (int) $project_id);
        $row = $this->db->get($this->location_table)->row();

        // Coordinates are mandatory for a geofence to mean anything, and an
        // administrator can suspend one without losing the coordinates.
        if (!$row || (int) $row->active !== 1 || $row->latitude === null || $row->longitude === null) {
            return null;
        }

        return $row;
    }

    /**
     * Every configured project location, with the project name joined on.
     *
     * @return array
     */
    public function get_project_locations()
    {
        if (!$this->db->table_exists($this->location_table)) {
            return [];
        }

        $this->db->select($this->location_table . '.*, ' . db_prefix() . 'projects.name as project_name');
        $this->db->join(
            db_prefix() . 'projects',
            db_prefix() . 'projects.id = ' . $this->location_table . '.project_id',
            'left'
        );
        $this->db->order_by(db_prefix() . 'projects.name', 'ASC');

        return $this->db->get($this->location_table)->result_array();
    }

    /**
     * Create or replace a project's geofence.
     *
     * Upsert on project_id, because the table allows one location per project.
     * Passing empty coordinates removes the geofence, which is how a project is
     * returned to being unrestricted.
     *
     * @param int   $project_id
     * @param array $data latitude, longitude, radius_metres, address, active
     *
     * @return bool
     */
    public function save_project_location($project_id, $data)
    {
        $project_id = (int) $project_id;

        if ($project_id <= 0) {
            return false;
        }

        $latitude  = isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null;

        // No coordinates means "no geofence for this project" - remove the row
        // rather than storing a half-configured one that reads as enforced.
        if ($latitude === null || $longitude === null) {
            $this->db->where('project_id', $project_id);
            $this->db->delete($this->location_table);

            return true;
        }

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return false;
        }

        $radius = isset($data['radius_metres']) ? (int) $data['radius_metres'] : 200;
        if ($radius <= 0) {
            $radius = 200;
        }

        $row = [
            'latitude'      => $latitude,
            'longitude'     => $longitude,
            'radius_metres' => $radius,
            'address'       => isset($data['address']) ? $data['address'] : null,
            'active'        => isset($data['active']) ? (int) $data['active'] : 1,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->where('project_id', $project_id);
        $exists = $this->db->get($this->location_table)->row();

        if ($exists) {
            $this->db->where('id', $exists->id);
            $this->db->update($this->location_table, $row);

            return true;
        }

        $row['project_id'] = $project_id;
        $row['created_at'] = date('Y-m-d H:i:s');
        $row['created_by'] = get_staff_user_id() ?: null;

        $this->db->insert($this->location_table, $row);

        return (bool) $this->db->insert_id();
    }

    /* -------------------------------------------------------------- reasons */

    /**
     * Reasons an employee may give for punching away from a project site.
     *
     * @param bool $active_only
     *
     * @return array
     */
    public function get_reasons($active_only = true)
    {
        if (!$this->db->table_exists($this->reason_table)) {
            return [];
        }

        if ($active_only) {
            $this->db->where('active', 1);
        }

        $this->db->order_by('sort_order', 'ASC');

        return $this->db->get($this->reason_table)->result_array();
    }

    /**
     * @param int $reason_id
     *
     * @return object|null
     */
    public function get_reason($reason_id)
    {
        if (empty($reason_id) || !is_numeric($reason_id)) {
            return null;
        }

        $this->db->where('id', (int) $reason_id);
        $this->db->where('active', 1);

        return $this->db->get($this->reason_table)->row();
    }

    /* ------------------------------------------------------------- geofence */

    /**
     * Is this position acceptable for this project?
     *
     * Distance is computed by the timesheets module's own compute_distance(), so the
     * geofence behaves identically to the module's existing workplace and route-point
     * checks. Returns enforced = false when the project has no geofence, which is the
     * normal case and must be treated as a pass.
     *
     * @param int        $project_id
     * @param float|null $latitude
     * @param float|null $longitude
     *
     * @return object enforced, inside, code, distance_metres, radius_metres, location
     */
    public function check_project_geofence($project_id, $latitude, $longitude)
    {
        $result = new stdClass();
        $result->enforced        = false;
        $result->inside          = true;
        $result->code            = self::GEO_INSIDE;
        $result->distance_metres = null;
        $result->radius_metres   = null;
        $result->location        = null;

        $location = $this->get_project_location($project_id);

        if (!$location) {
            // Optional by design: a project without coordinates is never restricted.
            return $result;
        }

        $result->enforced      = true;
        $result->location      = $location;
        $result->radius_metres = (int) $location->radius_metres;

        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            // A geofence exists but the device sent no position. Not treated as
            // inside - that would make the fence trivially bypassable by omitting
            // the coordinates.
            $result->inside = false;
            $result->code   = self::GEO_UNKNOWN;

            return $result;
        }

        $result->distance_metres = round($this->compute_distance(
            (float) $location->latitude,
            (float) $location->longitude,
            (float) $latitude,
            (float) $longitude
        ), 2);

        $result->inside = $result->distance_metres <= $result->radius_metres;
        $result->code   = $result->inside ? self::GEO_INSIDE : self::GEO_OUTSIDE;

        return $result;
    }

    /**
     * Distance in metres between two points.
     *
     * Delegates to the timesheets module so there is one implementation. The local
     * haversine is only a fallback for when that module is unavailable, so a punch
     * does not fail outright because a module was disabled.
     *
     * @return float metres
     */
    public function compute_distance($lat1, $lng1, $lat2, $lng2)
    {
        if (file_exists(APPPATH . '../modules/timesheets/models/Timesheets_model.php')) {
            try {
                $this->load->model('timesheets/timesheets_model');

                if (method_exists($this->timesheets_model, 'compute_distance')) {
                    return (float) $this->timesheets_model->compute_distance($lat1, $lng1, $lat2, $lng2);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Attendance_geo_model: could not use the timesheets '
                    . 'distance function, falling back. ' . $e->getMessage());
            }
        }

        $earth = 6378137;
        $rad   = M_PI / 180;

        $dLat = ($lat2 - $lat1) * $rad;
        $dLng = ($lng2 - $lng1) * $rad;

        $a = sin($dLat / 2) ** 2
            + cos($lat1 * $rad) * cos($lat2 * $rad) * sin($dLng / 2) ** 2;

        return (float) ($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /* --------------------------------------------------------------- punches */

    /**
     * Record a punch in the existing attendance table and refresh the timesheet.
     *
     * Writing to tblcheck_in_out rather than a table of our own is the whole point:
     * the timekeeping grid, the module's reports and the hr_payroll bridge already
     * read it, so the punch appears in the existing screens immediately.
     *
     * After inserting, the module's own add_check_in_out_value_to_timesheet() is
     * called. That pairs the day's in/out punches, works out the hours minus the
     * shift's lunch break, and upserts the tbltimesheets_timesheet row that both the
     * timekeeping grid and payroll read.
     *
     * @param array $data staff_id, type (in|out), punch_time, project_id, latitude,
     *                    longitude, device_id, face_similarity, out_of_geofence,
     *                    reason_id, reason_description, source, recorded_by
     *
     * @return array ['success' => bool, 'punch_id' => int|null, 'message' => string,
     *                'timesheet_updated' => bool]
     */
    public function record_punch($data)
    {
        $staff_id = (int) $data['staff_id'];
        $type     = $data['type'] === 'out' ? 'out' : 'in';

        // tblcheck_in_out stores direction as type_check: 1 = in, 2 = out.
        $type_check = $type === 'in' ? 1 : 2;

        $punch_time = !empty($data['punch_time']) ? $data['punch_time'] : date('Y-m-d H:i:s');
        $punch_date = date('Y-m-d', strtotime($punch_time));

        $row = [
            'staff_id'   => $staff_id,
            'date'       => $punch_time,
            'type_check' => $type_check,
            // 'W' is the module's work code; the payroll bridge counts it as an
            // actual workday via integration_actual_workday.
            'type'       => 'W',
        ];

        // workplace_id is NOT NULL DEFAULT 0 in the existing table.
        $row['workplace_id'] = 0;

        // Columns added by application/sql/attendance_phase1_tables.sql. Guarded so a
        // database that has not had that file applied still records the punch rather
        // than failing outright.
        $optional = [
            'project_id'         => isset($data['project_id']) && $data['project_id'] !== '' ? (int) $data['project_id'] : null,
            'latitude'           => isset($data['latitude']) && $data['latitude'] !== '' ? $data['latitude'] : null,
            'longitude'          => isset($data['longitude']) && $data['longitude'] !== '' ? $data['longitude'] : null,
            'face_similarity'    => isset($data['face_similarity']) ? $data['face_similarity'] : null,
            'device_id'          => isset($data['device_id']) ? $data['device_id'] : null,
            'out_of_geofence'    => !empty($data['out_of_geofence']) ? 1 : 0,
            'reason_id'          => !empty($data['reason_id']) ? (int) $data['reason_id'] : null,
            'reason_description' => isset($data['reason_description']) ? $data['reason_description'] : null,
            'source'             => isset($data['source']) ? $data['source'] : 'mobile',
            'recorded_by'        => !empty($data['recorded_by']) ? (int) $data['recorded_by'] : null,
            'note'               => isset($data['note']) ? $data['note'] : null,
        ];

        foreach ($optional as $column => $value) {
            if ($this->db->field_exists($column, $this->punch_table)) {
                $row[$column] = $value;
            }
        }

        $this->db->insert($this->punch_table, $row);
        $punch_id = $this->db->insert_id();

        if (!$punch_id) {
            // The sync added a unique key on (staff_id, date, type_check), so the
            // same punch arriving twice in the same second lands here. Report it
            // rather than pretending a new punch was created.
            return [
                'success'           => false,
                'punch_id'          => null,
                'message'           => 'This punch was already recorded.',
                'timesheet_updated' => false,
            ];
        }

        // Regenerate the day's timesheet through the module, so the hours are worked
        // out exactly as they are for a biometric punch.
        $timesheet_updated = $this->refresh_timesheet($staff_id, $punch_date);

        return [
            'success'           => true,
            'punch_id'          => (int) $punch_id,
            'message'           => 'Punch recorded.',
            'timesheet_updated' => $timesheet_updated,
        ];
    }

    /**
     * Recalculate one staff member's day in tbltimesheets_timesheet.
     *
     * The module's own generator is tried first, so that where shifts are configured
     * the hours are worked out its way - including the shift's lunch break - and a
     * mobile punch behaves exactly like a biometric one.
     *
     * It is then verified rather than trusted, and a fallback fills the gap. The
     * module's calculator resolves the employee's shift for the date, and in this
     * install it cannot: tblwork_shift_detail rows carry date = NULL and the only
     * roster is 'repeat_periodically', so nothing matches a specific day and the
     * calculation comes back as zero hours. That is also why the live Hikvision sync
     * (application/controllers/Sync_data.php) writes tbltimesheets_timesheet directly
     * instead of calling the module. Without the fallback, a mobile punch would be
     * recorded but produce no hours, so it would appear in the raw punch data and be
     * silently worth nothing to the timekeeping grid and payroll.
     *
     * Once shifts are properly allocated the first path starts succeeding and the
     * fallback stops being used, with no code change.
     *
     * @param int    $staff_id
     * @param string $date Y-m-d
     *
     * @return bool True when a timesheet row now reflects the day's hours
     */
    public function refresh_timesheet($staff_id, $date)
    {
        $staff_id = (int) $staff_id;

        if (file_exists(APPPATH . '../modules/timesheets/models/Timesheets_model.php')) {
            // Catch Throwable, not Exception. The module's generator reaches
            // get_shift_work_staff_by_date(), which uses $this->staff_model and
            // $this->departments_model. Those are loaded by the module's own
            // controllers but not by an API request, and the resulting
            // "call to a member function on null" is a PHP Error, not an Exception -
            // so catching Exception let it escape as a 500 with the punch already
            // written. They are pre-loaded below, and Throwable is the backstop for
            // anything else the vendor path expects that an API context lacks.
            try {
                $this->load->model('staff_model');
                $this->load->model('departments_model');
                $this->load->model('timesheets/timesheets_model');

                if (method_exists($this->timesheets_model, 'add_check_in_out_value_to_timesheet')) {
                    $this->timesheets_model->add_check_in_out_value_to_timesheet($staff_id, $date);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Attendance_geo_model::refresh_timesheet - the timesheets '
                    . 'module generator failed, falling back to deriving hours from the '
                    . 'punches. ' . $e->getMessage());
            }
        }

        // Did that produce hours?
        $existing = $this->get_timesheet_row($staff_id, $date);

        if ($existing && (float) $existing->value > 0) {
            return true;
        }

        return $this->write_timesheet_from_punches($staff_id, $date, $existing);
    }

    /**
     * The 'W' timesheet row for a staff member on a date, if any.
     *
     * @param int    $staff_id
     * @param string $date
     *
     * @return object|null
     */
    private function get_timesheet_row($staff_id, $date)
    {
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('date_work', $date);
        $this->db->where('type', 'W');

        return $this->db->get(db_prefix() . 'timesheets_timesheet')->row();
    }

    /**
     * Derive the day's hours from the punch pairs and upsert the timesheet row.
     *
     * Deliberately the same shape the module and the Hikvision sync write, so the
     * timekeeping grid and the hr_payroll bridge cannot tell the difference: type 'W',
     * value in decimal hours, one row per staff/day.
     *
     * @param int         $staff_id
     * @param string      $date
     * @param object|null $existing
     *
     * @return bool
     */
    private function write_timesheet_from_punches($staff_id, $date, $existing = null)
    {
        $derived = $this->get_project_hours_by_date($staff_id, $date);

        $hours = 0.0;
        foreach ($derived['projects'] as $project_hours) {
            $hours += (float) $project_hours;
        }

        $hours = round($hours, 2);

        if ($hours <= 0) {
            // A lone check-in with no check-out yet. Not an error - there is simply
            // nothing to record until the day is closed.
            return false;
        }

        if ($existing) {
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'timesheets_timesheet', ['value' => $hours, 'type' => 'W']);

            return true;
        }

        $this->db->insert(db_prefix() . 'timesheets_timesheet', [
            'staff_id'  => $staff_id,
            'date_work' => $date,
            'type'      => 'W',
            'value'     => $hours,
            // The module records who the entry came from; for a self-service punch
            // that is the employee.
            'add_from'  => get_staff_user_id() ?: $staff_id,
        ]);

        return (bool) $this->db->insert_id();
    }

    /**
     * Today's punches for a staff member, newest last.
     *
     * @param int    $staff_id
     * @param string $date Y-m-d, defaults to today
     *
     * @return array
     */
    public function get_punches($staff_id, $date = null)
    {
        $date = $date ?: date('Y-m-d');

        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('date(date)', $date);
        $this->db->order_by('date', 'ASC');
        $this->db->order_by('id', 'ASC');

        return $this->db->get($this->punch_table)->result_array();
    }

    /**
     * Hours worked per project on one day, derived from the punch pairs.
     *
     * Derived rather than stored. tbltimesheets_timesheet stays a single aggregate row
     * per staff/day, which is what the timekeeping grid and payroll expect, so adding
     * a per-project row there would change totals those screens already rely on. The
     * punches carry the project, so per-project man-hours can be calculated when a job
     * costing report asks for them, with nothing to keep in step.
     *
     * An unclosed check-in contributes nothing and is reported separately, which is
     * what requirement "if an employee forgets to check out" needs to detect.
     *
     * @param int    $staff_id
     * @param string $date Y-m-d
     *
     * @return array ['projects' => [project_id => hours], 'unclosed' => array]
     */
    public function get_project_hours_by_date($staff_id, $date)
    {
        $punches = $this->get_punches($staff_id, $date);

        $projects = [];
        $unclosed = [];
        $open     = null;

        foreach ($punches as $punch) {
            if ((int) $punch['type_check'] === 1) {
                // A second check-in without an intervening check-out means the
                // previous one was never closed.
                if ($open !== null) {
                    $unclosed[] = $open;
                }
                $open = $punch;

                continue;
            }

            if ((int) $punch['type_check'] === 2 && $open !== null) {
                $seconds = strtotime($punch['date']) - strtotime($open['date']);

                if ($seconds > 0) {
                    // The project is taken from the check-in: that is where the work
                    // was done, whatever the employee had selected by the time they
                    // checked out.
                    $key = !empty($open['project_id']) ? (int) $open['project_id'] : 0;

                    if (!isset($projects[$key])) {
                        $projects[$key] = 0;
                    }

                    $projects[$key] += $seconds / 3600;
                }

                $open = null;
            }
        }

        if ($open !== null) {
            $unclosed[] = $open;
        }

        foreach ($projects as $key => $hours) {
            $projects[$key] = round($hours, 2);
        }

        return ['projects' => $projects, 'unclosed' => $unclosed];
    }

    /* ------------------------------------------------------------- dashboard */

    /**
     * Daily attendance overview for HR.
     *
     * green  - the employee has at least one punch on the date
     * orange - the employee is not expected: approved leave, or a company holiday
     * red    - expected in and nothing recorded
     *
     * The point of the screen is the red list: who has not checked in, visible the
     * same day while it can still be chased.
     *
     * @param string $date          Y-m-d, defaults to today
     * @param int    $department_id Optional filter
     *
     * @return array ['date', 'summary' => [...], 'rows' => [...]]
     */
    public function get_daily_overview($date = null, $department_id = null)
    {
        $date = $date ?: date('Y-m-d');

        $staff_table = db_prefix() . 'staff';

        $this->db->select($staff_table . '.staffid, ' . $staff_table . '.firstname, '
            . $staff_table . '.lastname, ' . $staff_table . '.staff_identifi, '
            . $staff_table . '.email, ' . $staff_table . '.profile_image');
        $this->db->where($staff_table . '.active', 1);
        // is_not_staff marks non-employee logins, which have no attendance.
        $this->db->where('(' . $staff_table . '.is_not_staff = 0 OR ' . $staff_table . '.is_not_staff IS NULL)');

        if (!empty($department_id)) {
            $this->db->join(
                db_prefix() . 'staff_departments',
                db_prefix() . 'staff_departments.staffid = ' . $staff_table . '.staffid',
                'inner'
            );
            $this->db->where(db_prefix() . 'staff_departments.departmentid', (int) $department_id);
        }

        $this->db->order_by($staff_table . '.firstname', 'ASC');
        $staff = $this->db->get($staff_table)->result_array();

        $punched  = $this->get_punched_staff_ids($date);
        $on_leave = $this->get_staff_on_leave($date);
        $holiday  = $this->get_holiday($date);

        $rows = [];
        $summary = ['present' => 0, 'absent' => 0, 'leave' => 0, 'total' => 0];

        foreach ($staff as $member) {
            $sid = (int) $member['staffid'];

            if (isset($punched[$sid])) {
                $status = 'present';
                $colour = 'green';
                $label  = 'Attended';
                $detail = $punched[$sid];
            } elseif (isset($on_leave[$sid])) {
                $status = 'leave';
                $colour = 'orange';
                $label  = $on_leave[$sid]['label'];
                $detail = null;
            } elseif ($holiday) {
                $status = 'leave';
                $colour = 'orange';
                $label  = 'Holiday: ' . $holiday;
                $detail = null;
            } else {
                $status = 'absent';
                $colour = 'red';
                $label  = 'Not marked';
                $detail = null;
            }

            $summary[$status]++;
            $summary['total']++;

            $rows[] = [
                'staff_id'    => $sid,
                'employee_id' => $member['staff_identifi'] ?: null,
                'name'        => trim($member['firstname'] . ' ' . $member['lastname']),
                'email'       => $member['email'],
                'status'      => $status,
                'colour'      => $colour,
                'label'       => $label,
                'punches'     => $detail,
            ];
        }

        return ['date' => $date, 'summary' => $summary, 'rows' => $rows, 'holiday' => $holiday];
    }

    /**
     * Staff who punched on a date, with their first in / last out.
     *
     * @param string $date
     *
     * @return array keyed by staff_id
     */
    private function get_punched_staff_ids($date)
    {
        $this->db->select('staff_id, MIN(date) as first_punch, MAX(date) as last_punch,'
            . ' COUNT(*) as total, SUM(CASE WHEN type_check = 2 THEN 1 ELSE 0 END) as outs');
        $this->db->where('date(date)', $date);
        $this->db->group_by('staff_id');
        $rows = $this->db->get($this->punch_table)->result_array();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['staff_id']] = [
                'first_punch'  => $row['first_punch'],
                'last_punch'   => $row['last_punch'],
                'total'        => (int) $row['total'],
                'checked_out'  => (int) $row['outs'] > 0,
            ];
        }

        return $result;
    }

    /**
     * Staff on approved leave on a date.
     *
     * Reads both places the timesheets module records leave: the daily marks in
     * tbltimesheets_leave, and approved requests in tbltimesheets_requisition_leave
     * (status 1 = approved). Both are consulted because a request can be approved
     * before the daily marks are written.
     *
     * @param string $date
     *
     * @return array keyed by staff_id
     */
    private function get_staff_on_leave($date)
    {
        $result = [];

        $leave_table = db_prefix() . 'timesheets_leave';
        if ($this->db->table_exists($leave_table)) {
            $this->db->where('date_work', $date);
            foreach ($this->db->get($leave_table)->result_array() as $row) {
                $result[(int) $row['staff_id']] = ['label' => 'On leave'];
            }
        }

        $req_table = db_prefix() . 'timesheets_requisition_leave';
        if ($this->db->table_exists($req_table)) {
            // rel_type 1 = leave. status 1 = approved.
            $this->db->where('status', 1);
            $this->db->where('date(start_time) <=', $date);
            $this->db->where('date(end_time) >=', $date);
            foreach ($this->db->get($req_table)->result_array() as $row) {
                $label = 'On approved leave';

                if (!empty($row['type_of_leave_text'])) {
                    $label = 'On leave: ' . $row['type_of_leave_text'];
                }

                $result[(int) $row['staff_id']] = ['label' => $label];
            }
        }

        return $result;
    }

    /**
     * The company holiday falling on a date, if any.
     *
     * @param string $date
     *
     * @return string|null Reason
     */
    private function get_holiday($date)
    {
        $table = db_prefix() . 'day_off';

        if (!$this->db->table_exists($table)) {
            return null;
        }

        $this->db->where('break_date', $date);
        $row = $this->db->get($table)->row();

        return $row ? $row->off_reason : null;
    }

    /* ---------------------------------------------------------------- alerts */

    /**
     * Unclosed check-ins that are old enough to alert on and have not been alerted.
     *
     * @param int    $threshold_hours
     * @param string $date Y-m-d, defaults to today
     *
     * @return array
     */
    public function get_unalerted_unclosed_punches($threshold_hours = 12, $date = null)
    {
        $open = $this->get_unclosed_punches($date);

        if (!$open) {
            return [];
        }

        $log_table = db_prefix() . 'attendance_alert_log';
        $has_log   = $this->db->table_exists($log_table);

        $due = [];

        foreach ($open as $punch) {
            if ((float) $punch['hours_open'] < (float) $threshold_hours) {
                continue;
            }

            if ($has_log) {
                $this->db->where('punch_id', (int) $punch['id']);
                $this->db->where('alert_type', 'unclosed_punch');

                if ($this->db->count_all_results($log_table) > 0) {
                    // Already reminded about this one.
                    continue;
                }
            }

            $due[] = $punch;
        }

        return $due;
    }

    /**
     * Record that an alert was sent, so the cron does not repeat it.
     *
     * @param array $punch
     *
     * @return bool
     */
    public function mark_alerted($punch)
    {
        $log_table = db_prefix() . 'attendance_alert_log';

        if (!$this->db->table_exists($log_table)) {
            return false;
        }

        $this->db->insert($log_table, [
            'punch_id'   => (int) $punch['id'],
            'staff_id'   => (int) $punch['staff_id'],
            'alert_type' => 'unclosed_punch',
            'punch_date' => date('Y-m-d', strtotime($punch['date'])),
            'hours_open' => isset($punch['hours_open']) ? $punch['hours_open'] : null,
            'alerted_at' => date('Y-m-d H:i:s'),
        ]);

        return (bool) $this->db->insert_id();
    }

    /**
     * Check-ins with no matching check-out, for the forgot-to-check-out alerts.
     *
     * @param string $date     Y-m-d
     * @param int    $staff_id Optional, limits to one employee
     *
     * @return array One row per employee left checked in, with hours_open
     */
    public function get_unclosed_punches($date = null, $staff_id = null)
    {
        $date = $date ?: date('Y-m-d');

        $this->db->where('date(date)', $date);

        if ($staff_id !== null) {
            $this->db->where('staff_id', (int) $staff_id);
        }

        $this->db->order_by('staff_id', 'ASC');
        $this->db->order_by('date', 'ASC');
        $rows = $this->db->get($this->punch_table)->result_array();

        $by_staff = [];
        foreach ($rows as $row) {
            $by_staff[(int) $row['staff_id']][] = $row;
        }

        $open = [];

        foreach ($by_staff as $sid => $punches) {
            $current = null;

            foreach ($punches as $punch) {
                if ((int) $punch['type_check'] === 1) {
                    $current = $punch;
                } elseif ((int) $punch['type_check'] === 2) {
                    $current = null;
                }
            }

            if ($current !== null) {
                $current['hours_open'] = round((time() - strtotime($current['date'])) / 3600, 2);
                $open[] = $current;
            }
        }

        return $open;
    }
}
