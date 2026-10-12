<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cost_centers_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'cost_centers')->row();
        }
        $this->db->order_by('id', 'desc');
        return $this->db->get(db_prefix() . 'cost_centers')->result_array();
    }

    public function add($data)
    {
        $data['created_by'] = get_staff_user_id();
        $data['datecreated'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'cost_centers', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'cost_centers', $data);
        return $this->db->affected_rows() > 0;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'cost_centers');
        return $this->db->affected_rows() > 0;
    }
}
