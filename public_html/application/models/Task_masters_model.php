<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Manages the Task "Repeat Every" and "Related To" master lists.
 */
class Task_masters_model extends App_Model
{
    const REPEAT_TABLE   = 'task_repeat_master';
    const RELATED_TABLE  = 'task_related_master';
    const PRIORITY_TABLE = 'task_priority_master';

    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================
    // Repeat Every
    // =========================================================

    /**
     * @param  mixed $id
     * @return mixed  row when $id passed, otherwise array of rows
     */
    public function get_repeat($id = '', $activeOnly = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . self::REPEAT_TABLE)->row();
        }

        if ($activeOnly) {
            $this->db->where('active', 1);
        }

        $this->db->order_by('sort_order', 'asc');

        return $this->db->get(db_prefix() . self::REPEAT_TABLE)->result_array();
    }

    public function add_repeat($data)
    {
        $this->db->insert(db_prefix() . self::REPEAT_TABLE, [
            'name'           => $data['name'],
            'repeat_every'   => (int) $data['repeat_every'],
            'recurring_type' => $data['recurring_type'],
            'sort_order'     => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'active'         => isset($data['active']) ? 1 : 0,
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Task Repeat Option Added [' . $data['name'] . ']');

            return $insert_id;
        }

        return false;
    }

    public function update_repeat($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . self::REPEAT_TABLE, [
            'name'           => $data['name'],
            'repeat_every'   => (int) $data['repeat_every'],
            'recurring_type' => $data['recurring_type'],
            'sort_order'     => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'active'         => isset($data['active']) ? 1 : 0,
        ]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Task Repeat Option Updated [' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    public function delete_repeat($id)
    {
        $row = $this->get_repeat($id);

        if (!$row) {
            return false;
        }

        // Guard: don't delete while tasks still use this combination
        $inUse = total_rows(db_prefix() . 'tasks', [
            'repeat_every'   => $row->repeat_every,
            'recurring_type' => $row->recurring_type,
            'recurring'      => 1,
        ]);

        if ($inUse > 0) {
            return ['referenced' => true];
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . self::REPEAT_TABLE);

        if ($this->db->affected_rows() > 0) {
            log_activity('Task Repeat Option Deleted [' . $row->name . ']');

            return true;
        }

        return false;
    }

    // =========================================================
    // Related To
    // =========================================================

    public function get_related($id = '', $activeOnly = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . self::RELATED_TABLE)->row();
        }

        if ($activeOnly) {
            $this->db->where('active', 1);
        }

        $this->db->order_by('sort_order', 'asc');

        return $this->db->get(db_prefix() . self::RELATED_TABLE)->result_array();
    }

    public function add_related($data)
    {
        // Defensive: the UNIQUE index on key_name would otherwise raise a DB error
        if (empty($data['key_name']) || $this->key_name_exists($data['key_name'])) {
            return false;
        }

        $this->db->insert(db_prefix() . self::RELATED_TABLE, [
            'name'       => $data['name'],
            'key_name'   => $data['key_name'],
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'active'     => isset($data['active']) ? 1 : 0,
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Task Related Option Added [' . $data['name'] . ']');

            return $insert_id;
        }

        return false;
    }

    public function update_related($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . self::RELATED_TABLE, [
            'name'       => $data['name'],
            'key_name'   => $data['key_name'],
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'active'     => isset($data['active']) ? 1 : 0,
        ]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Task Related Option Updated [' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    public function delete_related($id)
    {
        $row = $this->get_related($id);

        if (!$row) {
            return false;
        }

        // Guard: don't delete while tasks are still linked to this rel_type
        $inUse = total_rows(db_prefix() . 'tasks', ['rel_type' => $row->key_name]);

        if ($inUse > 0) {
            return ['referenced' => true];
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . self::RELATED_TABLE);

        if ($this->db->affected_rows() > 0) {
            log_activity('Task Related Option Deleted [' . $row->name . ']');

            return true;
        }

        return false;
    }

    /**
     * key_name uniqueness check for form validation
     */
    public function key_name_exists($key_name, $id = '')
    {
        $this->db->where('key_name', $key_name);

        if (is_numeric($id)) {
            $this->db->where('id !=', $id);
        }

        return $this->db->count_all_results(db_prefix() . self::RELATED_TABLE) > 0;
    }

    // =========================================================
    // Priority
    // =========================================================

    /**
     * @param  mixed $id
     * @return mixed  row when $id passed, otherwise array of rows
     */
    public function get_priority($id = '', $activeOnly = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . self::PRIORITY_TABLE)->row();
        }

        if ($activeOnly) {
            $this->db->where('active', 1);
        }

        $this->db->order_by('sort_order', 'asc');

        return $this->db->get(db_prefix() . self::PRIORITY_TABLE)->result_array();
    }

    public function add_priority($data)
    {
        if (empty($data['name']) || $this->priority_name_exists($data['name'])) {
            return false;
        }

        $this->db->insert(db_prefix() . self::PRIORITY_TABLE, [
            'name'       => $data['name'],
            'color'      => $this->sanitize_color($data['color'] ?? ''),
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'active'     => isset($data['active']) ? 1 : 0,
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Task Priority Added [' . $data['name'] . ']');

            return $insert_id;
        }

        return false;
    }

    public function update_priority($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . self::PRIORITY_TABLE, [
            'name'       => $data['name'],
            'color'      => $this->sanitize_color($data['color'] ?? ''),
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'active'     => isset($data['active']) ? 1 : 0,
        ]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Task Priority Updated [' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    public function delete_priority($id)
    {
        $row = $this->get_priority($id);

        if (!$row) {
            return false;
        }

        // Guard: tbltasks.priority stores this id — deleting it would orphan tasks
        $inUse = total_rows(db_prefix() . 'tasks', ['priority' => $row->id]);

        if ($inUse > 0) {
            return ['referenced' => true];
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . self::PRIORITY_TABLE);

        if ($this->db->affected_rows() > 0) {
            log_activity('Task Priority Deleted [' . $row->name . ']');

            return true;
        }

        return false;
    }

    /**
     * Name uniqueness check — the table has a UNIQUE index on name
     */
    public function priority_name_exists($name, $id = '')
    {
        $this->db->where('name', $name);

        if (is_numeric($id)) {
            $this->db->where('id !=', $id);
        }

        return $this->db->count_all_results(db_prefix() . self::PRIORITY_TABLE) > 0;
    }

    /**
     * Colours are rendered straight into inline style attributes, so only
     * accept a hex value. Anything else falls back to the neutral default.
     */
    private function sanitize_color($color)
    {
        $color = trim((string) $color);

        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color) ? $color : '#333333';
    }
}
