<?php defined('BASEPATH') or exit('No direct script access allowed');

class Sales_returns extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sales_returns_model');
    }

    public function index()
    {
        $data['title'] = 'Sales Returns';
        $data['returns'] = $this->sales_returns_model->get_all();
        $this->load->view('admin/sales_returns/manage', $data);
    }

    public function create($id = '')
    {
        if($this->input->post()){
            $post = $this->input->post();
            if(empty($id)){
                $result = $this->sales_returns_model->add($post);
                if($result){
                    set_alert('success', _l('added_successfully'));
                    redirect(admin_url('sales_returns/view/' . $result));
                }
            } else {
                $result = $this->sales_returns_model->update($post, $id);
                if($result){
                    set_alert('success', _l('updated_successfully'));
                    redirect(admin_url('sales_returns/view/' . $id));
                }
            }
            set_alert('danger', 'Failed to save');
            redirect(admin_url('sales_returns'));
        }

        $data['title'] = empty($id) ? 'New Sales Return' : 'Edit Sales Return';
        if(!empty($id)){
            $data['return'] = $this->sales_returns_model->get($id);
            $data['return_items'] = $this->sales_returns_model->get_items($id);
        }

        // Load estimates (sales orders) that are accepted
        $this->db->where('status', 4);
        $this->db->where('(is_deleted = 0 OR is_deleted IS NULL)');
        $this->db->order_by('id', 'desc');
        $data['estimates'] = $this->db->get(db_prefix().'estimates')->result_array();

        $this->load->model('clients_model');
        $data['customers'] = $this->clients_model->get();

        $this->load->view('admin/sales_returns/create', $data);
    }

    public function view($id)
    {
        $data['title'] = 'Sales Return';
        $data['return'] = $this->sales_returns_model->get($id);
        if(!$data['return']){
            show_404();
        }
        $data['return_items'] = $this->sales_returns_model->get_items($id);
        $this->load->view('admin/sales_returns/view', $data);
    }

    public function delete($id)
    {
        $this->sales_returns_model->delete($id);
        set_alert('success', 'Sales return deleted');
        redirect(admin_url('sales_returns'));
    }

    /**
     * AJAX: Get delivery vouchers by estimate
     */
    public function get_gdv_by_estimate($estimate_id)
    {
        if(!is_numeric($estimate_id)){
            echo json_encode(['html' => '']);
            die;
        }
        $this->db->where('estimate_id', $estimate_id);
        $this->db->where('approval', 1);
        $this->db->where('(is_deleted = 0 OR is_deleted IS NULL)');
        $this->db->order_by('id', 'desc');
        $gdvs = $this->db->get(db_prefix().'goods_delivery')->result_array();

        $html = '';
        foreach($gdvs as $gdv){
            $html .= '<option value="'.$gdv['id'].'">'.$gdv['goods_delivery_code'].'</option>';
        }
        echo json_encode(['html' => $html]);
        die;
    }

    /**
     * AJAX: Get items from selected GDVs
     */
    public function get_items_by_gdv()
    {
        $gdv_ids = $this->input->post('gdv_ids');
        $estimate_id = $this->input->post('estimate_id');
        if(empty($gdv_ids) || !is_array($gdv_ids)){
            echo json_encode(['items' => []]);
            die;
        }

        $this->db->select('commodity_code, commodity_name, SUM(quantities) as delivered_qty, unit_price, unit_id, tax_id, tax_rate');
        $this->db->from(db_prefix().'goods_delivery_detail');
        $this->db->where_in('goods_delivery_id', $gdv_ids);
        $this->db->group_by('commodity_code');
        $items = $this->db->get()->result_array();

        // Get already returned
        $returned = [];
        if(is_numeric($estimate_id)){
            $this->db->select('sr.item_code, SUM(sr.qty) as returned_qty');
            $this->db->from(db_prefix().'sales_return_items sr');
            $this->db->join(db_prefix().'sales_returns s', 's.id = sr.sales_return_id');
            $this->db->where('s.estimate_id', $estimate_id);
            $this->db->where('s.status !=', 'canceled');
            $this->db->group_by('sr.item_code');
            $ret_rows = $this->db->get()->result_array();
            foreach($ret_rows as $r) $returned[$r['item_code']] = (float)$r['returned_qty'];
        }

        $result = [];
        foreach($items as $item){
            $code = $item['commodity_code'];
            $delivered = (float)$item['delivered_qty'];
            $already = isset($returned[$code]) ? $returned[$code] : 0;
            $available = $delivered - $already;
            if($available <= 0) continue;

            $name = $item['commodity_name'];
            if(empty($name) && is_numeric($code)){
                $this->db->where('id', $code);
                $db_item = $this->db->get(db_prefix().'items')->row();
                if($db_item) $name = $db_item->description;
            }

            $result[] = [
                'item_code' => $code,
                'description' => $name,
                'delivered_qty' => $delivered,
                'available_qty' => $available,
                'unit_price' => (float)$item['unit_price'],
                'unit_id' => $item['unit_id'],
                'tax_id' => $item['tax_id'] ?? '',
                'tax_rate' => $item['tax_rate'] ?? '',
            ];
        }

        echo json_encode(['items' => $result]);
        die;
    }

    /**
     * AJAX: Get customer info by estimate
     */
    public function get_estimate_info($estimate_id)
    {
        if(!is_numeric($estimate_id)){
            echo json_encode([]);
            die;
        }
        $this->db->where('id', $estimate_id);
        $est = $this->db->get(db_prefix().'estimates')->row();
        if(!$est){
            echo json_encode([]);
            die;
        }
        $this->load->model('clients_model');
        $client = $this->clients_model->get($est->clientid);
        echo json_encode([
            'clientid' => $est->clientid,
            'client_name' => $client ? $client->company : '',
            'total' => $est->total,
        ]);
        die;
    }
}
