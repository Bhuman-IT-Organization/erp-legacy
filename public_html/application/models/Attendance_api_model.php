<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Attendance API Model
 *
 * Punch-in/punch-out and attendance history for the mobile app.
 *
 * READS AND WRITES tblcheck_in_out - the table the timekeeping screens, the module's
 * reports and the hr_payroll bridge already use. It previously used its own
 * tblattendance_log, which nothing else read: a mobile punch was invisible in the
 * admin screens and never reached payroll. Consolidating on one store is what makes
 * mobile attendance show up in the existing screens.
 *
 * The response shape is unchanged, so the mobile app needs no rework. tblcheck_in_out
 * stores direction as type_check (1 = in, 2 = out) and uses `type` for the module's
 * work code ('W'), so rows are normalised here to the punch_time / punch_date / type
 * (in|out) shape the API has always returned. The work code is exposed separately as
 * work_code.
 */
class Attendance_api_model extends App_Model
{
    private $table;

    public function __construct()
    {
        parent::__construct();

        // The shared punch store, not a private one.
        $this->table = db_prefix() . 'check_in_out';
    }

    /**
     * Record a punch.
     *
     * Delegates to Attendance_geo_model so there is a single write path that also
     * refreshes the day's timesheet through the timesheets module.
     *
     * @param array $data staff_id, type, punch_time, project_id, latitude, longitude,
     *                    device_id, face_similarity, out_of_geofence, reason_id,
     *                    reason_description, source, recorded_by
     *
     * @return int|false Punch id, or false
     */
    public function punch($data)
    {
        $this->load->model('attendance_geo_model');

        $result = $this->attendance_geo_model->record_punch($data);

        return $result['success'] ? $result['punch_id'] : false;
    }

    /**
     * Normalise a tblcheck_in_out row into the shape this API has always returned.
     *
     * @param array $row
     *
     * @return array
     */
    private function normalise($row)
    {
        $out = [
            'id'         => (int) $row['id'],
            'staff_id'   => (int) $row['staff_id'],
            'type'       => (int) $row['type_check'] === 1 ? 'in' : 'out',
            'punch_time' => $row['date'],
            'punch_date' => date('Y-m-d', strtotime($row['date'])),
            // The timesheets work code ('W', 'OT', a leave symbol...), kept distinct
            // from the in/out direction above.
            'work_code'  => isset($row['type']) ? $row['type'] : null,
        ];

        // Context columns, present once application/sql/attendance_phase1_tables.sql
        // has been applied. Null rather than absent so the response shape is stable.
        foreach ([
            'project_id', 'latitude', 'longitude', 'face_similarity', 'device_id',
            'out_of_geofence', 'reason_id', 'reason_description', 'source', 'recorded_by', 'note',
        ] as $column) {
            $out[$column] = array_key_exists($column, $row) ? $row[$column] : null;
        }

        return $out;
    }

    /**
     * @param array $rows
     *
     * @return array
     */
    private function normalise_all($rows)
    {
        $result = [];

        foreach ($rows as $row) {
            $result[] = $this->normalise($row);
        }

        return $result;
    }

    /**
     * Today's attendance status for a staff member.
     *
     * @param int $staff_id
     *
     * @return array
     */
    public function get_today_status($staff_id)
    {
        $today = date('Y-m-d');

        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('date(date)', $today);
        $this->db->order_by('date', 'ASC');
        $this->db->order_by('id', 'ASC');
        $records = $this->normalise_all($this->db->get($this->table)->result_array());

        $first_in      = null;
        $last_out      = null;
        $total_punches = count($records);

        foreach ($records as $r) {
            if ($r['type'] === 'in' && $first_in === null) {
                $first_in = $r['punch_time'];
            }
            if ($r['type'] === 'out') {
                $last_out = $r['punch_time'];
            }
        }

        $current_status = 'not_punched';
        if ($total_punches > 0) {
            $last_record    = end($records);
            $current_status = $last_record['type'] === 'in' ? 'punched_in' : 'punched_out';
        }

        $working_seconds = $this->calculate_working_seconds($records);

        return [
            'date'          => $today,
            'status'        => $current_status,
            'first_in'      => $first_in,
            'last_out'      => $last_out,
            'total_punches' => $total_punches,
            'working_hours' => $this->format_seconds($working_seconds),
            'records'       => $records,
        ];
    }

    /**
     * Attendance history for a staff member.
     *
     * @param int    $staff_id
     * @param string $from_date Y-m-d
     * @param string $to_date   Y-m-d
     * @param int    $limit
     * @param int    $offset
     *
     * @return array
     */
    public function get_history($staff_id, $from_date = null, $to_date = null, $limit = 30, $offset = 0)
    {
        if (!$from_date) {
            $from_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$to_date) {
            $to_date = date('Y-m-d');
        }

        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('date(date) >=', $from_date);
        $this->db->where('date(date) <=', $to_date);
        $this->db->order_by('date', 'DESC');
        $this->db->limit($limit, $offset);
        $records = $this->normalise_all($this->db->get($this->table)->result_array());

        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('date(date) >=', $from_date);
        $this->db->where('date(date) <=', $to_date);
        $total = $this->db->count_all_results($this->table);

        $daily_summary = $this->get_daily_summary($staff_id, $from_date, $to_date);

        return [
            'records'       => $records,
            'daily_summary' => $daily_summary,
            'total'         => $total,
            'from_date'     => $from_date,
            'to_date'       => $to_date,
        ];
    }

    /**
     * First in, last out and hours per day.
     *
     * @param int    $staff_id
     * @param string $from_date
     * @param string $to_date
     *
     * @return array
     */
    public function get_daily_summary($staff_id, $from_date, $to_date)
    {
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('date(date) >=', $from_date);
        $this->db->where('date(date) <=', $to_date);
        $this->db->order_by('date', 'ASC');
        $all_records = $this->normalise_all($this->db->get($this->table)->result_array());

        $grouped = [];
        foreach ($all_records as $r) {
            $grouped[$r['punch_date']][] = $r;
        }

        $summary = [];
        foreach ($grouped as $date => $records) {
            $first_in = null;
            $last_out = null;

            foreach ($records as $r) {
                if ($r['type'] === 'in' && $first_in === null) {
                    $first_in = $r['punch_time'];
                }
                if ($r['type'] === 'out') {
                    $last_out = $r['punch_time'];
                }
            }

            $working_seconds = $this->calculate_working_seconds($records);

            $summary[] = [
                'date'          => $date,
                'first_in'      => $first_in,
                'last_out'      => $last_out,
                'working_hours' => $this->format_seconds($working_seconds),
                'total_punches' => count($records),
            ];
        }

        return $summary;
    }

    /**
     * The most recent punch today, optionally of one direction.
     *
     * @param int    $staff_id
     * @param string $type 'in' or 'out'
     *
     * @return object|null
     */
    public function get_last_punch_today($staff_id, $type = null)
    {
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('date(date)', date('Y-m-d'));

        if ($type) {
            $this->db->where('type_check', $type === 'in' ? 1 : 2);
        }

        $this->db->order_by('date', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get($this->table)->row_array();

        return $row ? (object) $this->normalise($row) : null;
    }

    /**
     * Total working seconds from punch pairs: in -> out, in -> out, ...
     *
     * @param array $records Normalised, sorted by punch_time ASC
     *
     * @return int
     */
    private function calculate_working_seconds($records)
    {
        $total   = 0;
        $last_in = null;

        foreach ($records as $r) {
            if ($r['type'] === 'in') {
                $last_in = strtotime($r['punch_time']);
            } elseif ($r['type'] === 'out' && $last_in !== null) {
                $total += strtotime($r['punch_time']) - $last_in;
                $last_in = null;
            }
        }

        // Still checked in - count up to now, so the app can show a running total.
        if ($last_in !== null) {
            $total += time() - $last_in;
        }

        return $total;
    }

    /**
     * @param int $seconds
     *
     * @return string H:i:s
     */
    private function format_seconds($seconds)
    {
        $hours   = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs    = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
