<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Quality model
 */
class Quality_model extends App_Model
{
	/**
     * construct
     */
    public function __construct()
    {
        parent::__construct();
    }

	/**
     * delete quanlity permission
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_quanlity_permission($id)
    {
        $str_permissions ='';
        foreach (list_quanlity_permisstion() as $per_key =>  $per_value) {
            if(new_strlen($str_permissions) > 0){
                $str_permissions .= ",'".$per_value."'";
            }else{
                $str_permissions .= "'".$per_value."'";
            }
        }

        $sql_where = " feature IN (".$str_permissions.") ";

        $this->db->where('staff_id', $id);
        $this->db->where($sql_where);
        $this->db->delete(db_prefix() . 'staff_permissions');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * get quality_team
     * @param  boolean $id     
     * @param  boolean $active 
     * @param  array   $where  
     * @return [type]          
     */
    public function get_quality_team($id = false, $active = false, $where = []) {

        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'quality_teams')->row();
        }
        if ($id == false) {
            if($active){
                $this->db->where('status', 1);
            }
            if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
                $this->db->where($where);
            }
            $this->db->order_by('name', 'ASC');
            return $this->db->get(db_prefix() . 'quality_teams')->result_array();
        }
    }

    /**
     * add quality_team
     * @param [type] $data 
     */
    public function add_quality_team($data)
    {

        $data['status'] = 1;

        $this->db->insert(db_prefix().'quality_teams',$data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            hooks()->do_action('after_quality_team_added', $insert_id);

            return $insert_id;
        }
        return false;
    }

    /**
     * update quality_team
     * @param  [type] $data 
     * @param  [type] $id   
     * @return [type]       
     */
    public function update_quality_team($data, $id)
    {
        $affected_rows=0;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_teams', $data);
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('after_quality_team_updated', $id);

            return true;
        }

        return false;  
    }

    /**
     * delete quality_team
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_quality_team($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'quality_teams');
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    /**
     * change quality_team status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_quality_team_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_teams', [
            'status' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    /**
     * update prefix number
     * @param  [type] $data 
     * @return [type]       
     */
    public function update_prefix_number($data)
    {
        $affected_rows=0;
        foreach ($data as $key => $value) {
            if (update_option($key, $value)){
                $affected_rows++;
            }
        }

        if($affected_rows > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * delete quality permission
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_quality_permission($id)
    {
        $str_permissions ='';
        foreach (list_quality_permisstion() as $per_key =>  $per_value) {
            if(new_strlen($str_permissions) > 0){
                $str_permissions .= ",'".$per_value."'";
            }else{
                $str_permissions .= "'".$per_value."'";
            }
        }

        $sql_where = " feature IN (".$str_permissions.") ";

        $this->db->where('staff_id', $id);
        $this->db->where($sql_where);
        $this->db->delete(db_prefix() . 'staff_permissions');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * get control_point
     * @param  boolean $id     
     * @param  boolean $active 
     * @param  array   $where  
     * @return [type]          
     */
    public function get_control_point($id = false, $where = []) {

        if (is_numeric($id)) {
            $this->db->where('id', $id);
            $control_point = $this->db->get(db_prefix() . 'quality_control_points')->row();
            if(!$control_point){
                return $control_point;
            }
            return $control_point;
        }
        if ($id == false) {

            if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
                $this->db->where($where);
            }
            $this->db->order_by('id', 'DESC');

            return $this->db->get(db_prefix() . 'quality_control_points')->result_array();
        }
    }

    /**
     * add control_point
     * @param [type] $data 
     */
    public function add_control_point($data)
    {
        $data['staffid'] = get_staff_user_id();
        $data['hash'] = app_generate_hash();
        $data['prefix'] = get_option('quality_control_point_prefix');
        $data['number_format'] = get_option('quality_control_point_number_format');
        $data['status'] = 1;

        if($data['datecreated'] != ''){
            $data['datecreated'] = to_sql_date($data['datecreated'], true);
        }else{
            $data['datecreated'] = null;
        }
        if(isset($data['failure_location_ids'])){

            $data['failure_location_ids'] = implode(',', $data['failure_location_ids']);
        }else{
            $data['failure_location_ids'] = null;
        }
        if(isset($data['partial_transfer_test'])){

            $data['partial_transfer_test'] = 1;
        }else{
            $data['partial_transfer_test'] = 0;
            $data['percentage'] = 0;
        }

        $this->db->insert(db_prefix().'quality_control_points',$data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update next repair job number in settings
            $this->db->where('name', 'quality_control_point_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            hooks()->do_action('after_quality_control_point_added', $insert_id);

            return $insert_id;
        }
        return false;
    }

    /**
     * update control_point
     * @param  [type] $data 
     * @param  [type] $id   
     * @return [type]       
     */
    public function update_control_point($data, $id)
    {
        $affected_rows=0;
        if($data['datecreated'] != ''){
            $data['datecreated'] = to_sql_date($data['datecreated'], true);
        }else{
            $data['datecreated'] = null;
        }
        if(isset($data['failure_location_ids'])){

            $data['failure_location_ids'] = implode(',', $data['failure_location_ids']);
        }else{
            $data['failure_location_ids'] = null;
        }
        if(isset($data['partial_transfer_test'])){

            $data['partial_transfer_test'] = 1;
        }else{
            $data['partial_transfer_test'] = 0;
            $data['percentage'] = 0;
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_control_points', $data);
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('after_quality_control_point_updated', $id);

            $affected_rows++;
        }

        if($affected_rows > 0){
            return true;
        }

        return false;  
    }

    /**
     * delete control_point
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_control_point($id)
    {
        hooks()->do_action('before_quality_control_point_deleted', $id);

        $affected_rows = 0;
        
        if (is_dir(CONTROL_POINT_FOLDER. $id)) {
                // okey only index.html so we can delete the folder also
            delete_dir(CONTROL_POINT_FOLDER. $id);
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'quality_control_points');
        if ($this->db->affected_rows() > 0) {

            hooks()->do_action('after_quality_control_point_deleted', $id);

            $affected_rows++;
        }

        if($affected_rows > 0){
            return true;
        }
        return false;
    }

    /**
     * get attachment file
     * @param  [type] $rel_id   
     * @param  [type] $rel_type 
     * @return [type]           
     */
    public function get_attachment_file($rel_id, $rel_type){
        $this->db->order_by('dateadded', 'desc');
        $this->db->where('rel_id', $rel_id);
        $this->db->where('rel_type', $rel_type);

        return $this->db->get(db_prefix() . 'files')->result_array();
    }

    /**
     * delete quality file
     * @param  [type] $attachment_id 
     * @param  [type] $folder_name   
     * @return [type]                
     */
    public function delete_quality_file($attachment_id, $folder_name)
    {
        $deleted    = false;
        $attachment = $this->misc_model->get_file($attachment_id);
        if ($attachment) {
            if (empty($attachment->external)) {
                if (file_exists($folder_name .$attachment->rel_id.'/'.$attachment->file_name)) {
                    unlink($folder_name .$attachment->rel_id.'/'.$attachment->file_name);
                }
                if (file_exists($folder_name .$attachment->rel_id.'/small_'.$attachment->file_name)) {
                    unlink($folder_name .$attachment->rel_id.'/small_'.$attachment->file_name);
                }
                if (file_exists($folder_name .$attachment->rel_id.'/thumb_'.$attachment->file_name)) {
                    unlink($folder_name .$attachment->rel_id.'/thumb_'.$attachment->file_name);
                }
            }
            
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Quality Attachment Deleted [ID: ' . $attachment->rel_id . '] folder name: '.$folder_name);
            }

            if (is_dir($folder_name .$attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files($folder_name .$attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir($folder_name .$attachment->rel_id);
                }
            }
        }
        return $deleted;
    }

    /**
     * get operation by product
     * @param  boolean $product_id 
     * @return [type]              
     */
    public function get_operation_by_product($product_id = false)
    {   
        $this->db->select('DISTINCT('.db_prefix().'mrp_routing_details.id), CONCAT("[",'.db_prefix().'mrp_routings.routing_name,"] ",'.db_prefix().'mrp_routing_details.operation) as operation, CONCAT('.db_prefix().'mrp_routings.routing_name," ",'.db_prefix().'mrp_routing_details.operation) as name');

        $this->db->from(db_prefix() . 'mrp_bill_of_materials');
        $this->db->where(db_prefix() . 'mrp_bill_of_materials.status = "active"');
        $this->db->join(db_prefix() . 'mrp_routings', db_prefix() . 'mrp_bill_of_materials.routing_id = ' . db_prefix() . 'mrp_routings.id', 'left');
        $this->db->join(db_prefix() . 'mrp_routing_details', db_prefix() . 'mrp_routings.id = ' . db_prefix() . 'mrp_routing_details.routing_id', 'left');

        if($product_id){
            $this->db->join(db_prefix() . 'items', db_prefix() . 'mrp_bill_of_materials.product_id = ' . db_prefix() . 'items.id', 'left');
            $this->db->where(db_prefix() . 'mrp_bill_of_materials.product_id', $product_id);
            $this->db->or_where(db_prefix() . 'mrp_bill_of_materials.product_variant_id', $product_id);
        }
        $routing_details = $this->db->get()->result_array();
        return $routing_details;
    }

    /**
     * get work order by product
     * @param  boolean $product_id 
     * @return [type]              
     */
    public function get_work_order_by_product($product_id = false)
    {   
        $this->db->select('DISTINCT('.db_prefix().'mrp_work_orders.id), CONCAT("[",'.db_prefix().'mrp_manufacturing_orders.manufacturing_order_code,"] ",'.db_prefix().'mrp_work_orders.operation_name) as operation, CONCAT('.db_prefix().'mrp_manufacturing_orders.manufacturing_order_code," ",'.db_prefix().'mrp_work_orders.operation_name) as name');

        $this->db->from(db_prefix() . 'mrp_work_orders');
        $this->db->where(db_prefix() . 'mrp_manufacturing_orders.status != "draft"');
        $this->db->join(db_prefix() . 'mrp_manufacturing_orders', db_prefix() . 'mrp_work_orders.manufacturing_order_id = ' . db_prefix() . 'mrp_manufacturing_orders.id', 'left');

        if($product_id && $product_id != 0 && !is_null($product_id) && $product_id != 'null'){
            $this->db->join(db_prefix() . 'items', db_prefix() . 'mrp_manufacturing_orders.product_id = ' . db_prefix() . 'items.id', 'left');
            $this->db->where(db_prefix() . 'mrp_manufacturing_orders.product_id', $product_id);
        }
        $routing_details = $this->db->get()->result_array();
        return $routing_details;
    }

    /**
     * get quality_check
     * @param  boolean $id     
     * @param  boolean $active 
     * @param  array   $where  
     * @return [type]          
     */
    public function get_quality_check($id = false, $where = []) {

        if (is_numeric($id)) {
            $this->db->where('id', $id);
            $quality_check = $this->db->get(db_prefix() . 'quality_checks')->row();
            if($quality_check){
                $this->db->where('quality_check_id', $id);
                $details = $this->db->get(db_prefix() . 'quality_check_details')->result_array();
                if($details){
                    $quality_check->details = $details;
                }
            }
            return $quality_check;
        }
        if ($id == false) {

            if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
                $this->db->where($where);
            }
            $this->db->order_by('id', 'DESC');

            return $this->db->get(db_prefix() . 'quality_checks')->result_array();
        }
    }

    /**
     * add quality_check
     * @param [type] $data 
     */
    public function add_quality_check($data)
    {
        $data['staffid'] = get_staff_user_id();
        $data['hash'] = app_generate_hash();
        $data['prefix'] = get_option('quality_check_prefix');
        $data['number_format'] = get_option('quality_check_number_format');
        if(!isset($data['status'])){
            $data['status'] = 'draft';
        }

        if($data['datecreated'] != ''){
            $data['datecreated'] = to_sql_date($data['datecreated'], true);
        }else{
            $data['datecreated'] = null;
        }
        if(isset($data['failure_location_ids'])){

            $data['failure_location_ids'] = implode(',', $data['failure_location_ids']);
        }else{
            $data['failure_location_ids'] = null;
        }

        if(isset($data['partial_transfer_test'])){

            $data['partial_transfer_test'] = 1;
        }else{
            $data['partial_transfer_test'] = 0;
        }

        $this->db->insert(db_prefix().'quality_checks',$data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update next repair job number in settings
            $this->db->where('name', 'quality_check_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            if(is_numeric($data['control_point_id']) && $data['control_point_id'] > 0 ){
                $this->copy_control_point_attachment($data['control_point_id'], $insert_id);
            }

            hooks()->do_action('after_quality_check_added', $insert_id);

            return $insert_id;
        }
        return false;
    }

    /**
     * update quality_check
     * @param  [type] $data 
     * @param  [type] $id   
     * @return [type]       
     */
    public function update_quality_check($data, $id)
    {
        $affected_rows=0;
        if($data['datecreated'] != ''){
            $data['datecreated'] = to_sql_date($data['datecreated'], true);
        }else{
            $data['datecreated'] = null;
        }
        if(isset($data['failure_location_ids'])){

            $data['failure_location_ids'] = implode(',', $data['failure_location_ids']);
        }else{
            $data['failure_location_ids'] = null;
        }

        if(isset($data['partial_transfer_test'])){

            $data['partial_transfer_test'] = 1;
        }else{
            $data['partial_transfer_test'] = 0;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_checks', $data);
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('after_quality_check_updated', $id);

            $affected_rows++;
        }

        if($affected_rows > 0){
            return true;
        }

        return false;  
    }

    /**
     * delete quality_check
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_quality_check($id)
    {
        hooks()->do_action('before_quality_check_deleted', $id);

        $affected_rows = 0;
        
        if (is_dir(QUALITY_CHECK_FOLDER. $id)) {
                // okey only index.html so we can delete the folder also
            delete_dir(QUALITY_CHECK_FOLDER. $id);
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'quality_checks');
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('after_quality_check_deleted', $id);

            $affected_rows++;
        }

        if($affected_rows > 0){
            return true;
        }
        return false;
    }

    /**
     * quality check status mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @param  string $type   
     * @return [type]         
     */
    public function quality_check_status_mark_as($status, $id)
    {
        $status_f = false;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_checks', ['status' => $status]);
        if ($this->db->affected_rows() > 0) {
            if($status == 'to_do'){

                $quality_check_detail = $this->get_quality_check_detail(false, $id);
                foreach ($quality_check_detail as $key => $value) {
                    if (is_dir(QUALITY_CHECK_DETAIL_FOLDER. $value['id'])) {
                        // okey only index.html so we can delete the folder also
                        delete_dir(QUALITY_CHECK_DETAIL_FOLDER. $value['id']);
                    }

                    $this->db->where('id', $value['id']);
                    $this->db->delete(db_prefix() . 'quality_check_details');
                }

                $this->create_quality_check_detail($id);
                $this->send_quality_check_to_staff($id, false, '', true);
            }
            return true;
        }
        return false;
    }

    /**
     * create quality check detail
     * @param  [type] $quality_check_id 
     * @return [type]                   
     */
    public function create_quality_check_detail($quality_check_id)
    {
        $quality_check = $this->get_quality_check($quality_check_id);
        $items = [];

        if($quality_check){
            switch ($quality_check->control_per) {
                case 'operation':
                    $this->create_quality_check_detail_per_operation($quality_check);
                    break;

                case 'product':
                    $this->create_quality_check_detail_per_product($quality_check);
                    break;

                case 'quantity':
                    $this->create_quality_check_detail_per_quantity($quality_check);
                    break;
                    
                default:
                    // code...
                    break;
            }
        }


    }

    /**
     * create quality check detail per operation
     * @param  [type] $quality_check 
     * @return [type]                
     */
    public function create_quality_check_detail_per_operation($quality_check)
    {
        $datecreated = date('Y-m-d H:i:s');
        $staffid = get_staff_user_id();

        $quality_check_detail_data = [];
        switch ($quality_check->operation) {
            case 'receipts':
                if(quality_get_status_modules('warehouse')){
                    $this->load->model('warehouse/warehouse_model');

                    $goods_receipt = $this->warehouse_model->get_goods_receipt($quality_check->goods_receipt_id);
                    $goods_receipt_detail = $this->warehouse_model->get_goods_receipt_detail($quality_check->goods_receipt_id);
                    if($goods_receipt){
                        $quality_check_id = $quality_check->id;
                        $description = _l('quality_check_inventory_receipt').' <a href="'.admin_url('warehouse/manage_purchase/'.$quality_check->goods_receipt_id).'" target="_blank">'.$goods_receipt->goods_receipt_code.'</a>';

                        $status = 'to_do';
                        foreach ($goods_receipt_detail as $key => $value) {
                            if(!is_null($quality_check->item_id) && $quality_check->item_id > 0 && $quality_check->item_id != $value['commodity_code']){
                                continue;
                            }

                            $description .= '<br><b>'.$value['commodity_name'];
                            $description .= ' - '._l('quality_quantity').': '.$value['quantities'].'</b>';
                        }
                        $quality_check_detail_data[] = [
                            'quality_check_id' => $quality_check_id,
                            'description' => $description,
                            'status' => $status,
                            'datecreated' => $datecreated,
                            'staffid' => $staffid,
                        ];
                    }
                }

                break;
            case 'internal_transfers':
                if(quality_get_status_modules('warehouse')){
                    $this->load->model('warehouse/warehouse_model');

                    $internal_delivery = $this->warehouse_model->get_internal_delivery($quality_check->internal_delivery_id);
                    $internal_delivery_detail = $this->warehouse_model->get_internal_delivery_detail($quality_check->internal_delivery_id);
                    if($internal_delivery){
                        $quality_check_id = $quality_check->id;
                        $description = _l('quality_check_inventory_internal_delivery').' <a href="'.admin_url('warehouse/manage_internal_delivery/'.$quality_check->internal_delivery_id).'" target="_blank">'.$internal_delivery->internal_delivery_code.'</a>';
                        $status = 'to_do';
                        foreach ($internal_delivery_detail as $key => $value) {
                            if(!is_null($quality_check->item_id) && $quality_check->item_id > 0 && $quality_check->item_id != $value['commodity_code']){
                                continue;
                            }

                            $description .= '<br><b>'.$value['commodity_name'];
                            $description .= ' - '._l('quality_quantity').': '.$value['quantities'].'</b>';
                        }
                        $quality_check_detail_data[] = [
                            'quality_check_id' => $quality_check_id,
                            'description' => $description,
                            'status' => $status,
                            'datecreated' => $datecreated,
                            'staffid' => $staffid,
                        ];
                    }
                }
                break;
            case 'delivery_orders':
                if(quality_get_status_modules('warehouse')){
                    $this->load->model('warehouse/warehouse_model');

                    $goods_delivery = $this->warehouse_model->get_goods_delivery($quality_check->goods_delivery_id);
                    $goods_delivery_detail = $this->warehouse_model->get_goods_delivery_detail($quality_check->goods_delivery_id);
                    if($goods_delivery){
                        $quality_check_id = $quality_check->id;
                        $description = _l('quality_check_inventory_delivery').' <a href="'.admin_url('warehouse/manage_delivery/'.$quality_check->goods_delivery_id).'" target="_blank">'.$goods_delivery->goods_delivery_code.'</a>';
                        $status = 'to_do';
                        foreach ($goods_delivery_detail as $key => $value) {
                            if(!is_null($quality_check->item_id) && $quality_check->item_id > 0 && $quality_check->item_id != $value['commodity_code']){
                                continue;
                            }

                            $description .= '<br><b>'.$value['commodity_name'];
                            $description .= ' - '._l('quality_quantity').': '.$value['quantities'].'</b>';
                        }
                        $quality_check_detail_data[] = [
                            'quality_check_id' => $quality_check_id,
                            'description' => $description,
                            'status' => $status,
                            'datecreated' => $datecreated,
                            'staffid' => $staffid,
                        ];
                    }
                }
                break;
            case 'manufacturing':
                if(quality_get_status_modules('manufacturing')){
                    $this->load->model('manufacturing/manufacturing_model');

                    $work_order = $this->manufacturing_model->get_work_order($quality_check->work_order_id);
                    if($work_order){
                        $quality_check_id = $quality_check->id;
                        $description = _l('quality_check_work_order').' <a href="'.admin_url('manufacturing/view_work_order/'.$quality_check->work_order_id.'/'.$work_order->manufacturing_order_id).'" target="_blank">'.$work_order->operation_name.'</a>';
                        $status = 'to_do';

                        $quality_check_detail_data[] = [
                            'quality_check_id' => $quality_check_id,
                            'check_work_order_id' => $quality_check->work_order_id,
                            'description' => $description,
                            'status' => $status,
                            'datecreated' => $datecreated,
                            'staffid' => $staffid,
                        ];
                    }
                }
                break;
            
            default:
                // code...
                break;
        }

        if(count($quality_check_detail_data) > 0){
            $affectedRows = $this->db->insert_batch(db_prefix(). 'quality_check_details', $quality_check_detail_data);
            if($affectedRows > 0){
                return true;
            }
        }
        return false;
    }

    /**
     * create quality check detail per product
     * @param  [type] $quality_check 
     * @return [type]                
     */
    public function create_quality_check_detail_per_product($quality_check)
    {
        $datecreated = date('Y-m-d H:i:s');
        $staffid = get_staff_user_id();

        $quality_check_detail_data = [];
        $_item_ids = [];

        switch ($quality_check->operation) {
            case 'receipts':
                if(quality_get_status_modules('warehouse')){
                    $this->load->model('warehouse/warehouse_model');

                    $goods_receipt = $this->warehouse_model->get_goods_receipt($quality_check->goods_receipt_id);
                    $goods_receipt_detail = $this->warehouse_model->get_goods_receipt_detail($quality_check->goods_receipt_id);
                    if($goods_receipt){
                        $quality_check_id = $quality_check->id;

                        $status = 'to_do';
                        foreach ($goods_receipt_detail as $key => $value) {
                            if(!is_null($quality_check->item_id) && $quality_check->item_id > 0 && $quality_check->item_id != $value['commodity_code']){
                                continue;
                            }

                            if(!in_array($value['commodity_code'], $_item_ids)){
                                $description = _l('quality_check_for').' <b>'.$value['commodity_name'].' - '._l('quality_quantity').': '.$value['quantities'].'</b>';
                                $quality_check_detail_data[] = [
                                    'quality_check_id' => $quality_check_id,
                                    'check_item_id' => $value['commodity_code'],
                                    'description' => $description,
                                    'status' => $status,
                                    'datecreated' => $datecreated,
                                    'staffid' => $staffid,
                                ];
                                $_item_ids[] = $value['commodity_code'];
                            }
                        }
                    }
                }

                break;
            case 'internal_transfers':
                if(quality_get_status_modules('warehouse')){
                    $this->load->model('warehouse/warehouse_model');

                    $internal_delivery = $this->warehouse_model->get_internal_delivery($quality_check->internal_delivery_id);
                    $internal_delivery_detail = $this->warehouse_model->get_internal_delivery_detail($quality_check->internal_delivery_id);
                    if($internal_delivery){
                        $quality_check_id = $quality_check->id;
                        $status = 'to_do';
                        $_temp_items = [];
                        foreach ($internal_delivery_detail as $key => $_value) {
                            if(!is_null($quality_check->item_id) && $quality_check->item_id > 0 && $quality_check->item_id != $value['commodity_code']){
                                continue;
                            }

                            if(isset($_temp_items[$_value['commodity_code']])){
                                $_temp_items[$_value['commodity_code']]['quantities'] += (float)$_value['quantities'];
                            }else{
                                $_temp_items[$_value['commodity_code']]['commodity_name'] = quality_get_item_description($_value['commodity_code']);
                                $_temp_items[$_value['commodity_code']]['quantities'] = (float)$_value['quantities'];
                                $_temp_items[$_value['commodity_code']]['commodity_code'] = $_value['commodity_code'];
                            }
                        }

                        foreach ($_temp_items as $key => $value) {
                            $description = _l('quality_check_for').' <b>' .$value['commodity_name'].' - '._l('quality_quantity').': '.$value['quantities'].'</b>';
                            $quality_check_detail_data[] = [
                                'quality_check_id' => $quality_check_id,
                                'check_item_id' => $value['commodity_code'],
                                'description' => $description,
                                'status' => $status,
                                'datecreated' => $datecreated,
                                'staffid' => $staffid,
                            ];
                            $_item_ids[] = $value['commodity_code'];
                        }

                    }
                }
                break;
            case 'delivery_orders':
                if(quality_get_status_modules('warehouse')){
                    $this->load->model('warehouse/warehouse_model');

                    $goods_delivery = $this->warehouse_model->get_goods_delivery($quality_check->goods_delivery_id);
                    $goods_delivery_detail = $this->warehouse_model->get_goods_delivery_detail($quality_check->goods_delivery_id);
                    if($goods_delivery){
                        $quality_check_id = $quality_check->id;
                        $status = 'to_do';
                        $_temp_items = [];

                        foreach ($goods_delivery_detail as $key => $_value) {
                            if(!is_null($quality_check->item_id) && $quality_check->item_id > 0 && $quality_check->item_id != $_value['commodity_code']){
                                continue;
                            }

                            if(isset($_temp_items[$_value['commodity_code']])){
                                $_temp_items[$_value['commodity_code']]['quantities'] += (float)$_value['quantities'];
                            }else{
                                $_temp_items[$_value['commodity_code']]['commodity_name'] = quality_get_item_description($_value['commodity_code']);
                                $_temp_items[$_value['commodity_code']]['quantities'] = (float)$_value['quantities'];
                                $_temp_items[$_value['commodity_code']]['commodity_code'] = $_value['commodity_code'];
                            }
                        }

                        foreach ($_temp_items as $key => $value) {
                            $description = _l('quality_check_for').' <b>' .$value['commodity_name'].' - '._l('quality_quantity').': '.$value['quantities'].'</b>';
                            $quality_check_detail_data[] = [
                                'quality_check_id' => $quality_check_id,
                                'check_item_id' => $value['commodity_code'],
                                'description' => $description,
                                'status' => $status,
                                'datecreated' => $datecreated,
                                'staffid' => $staffid,
                            ];
                            $_item_ids[] = $value['commodity_code'];
                        }
                    }
                }
                break;
            case 'manufacturing':
                if(quality_get_status_modules('manufacturing')){
                    $this->load->model('manufacturing/manufacturing_model');

                    $work_order = $this->manufacturing_model->get_work_order($quality_check->work_order_id);
                    if($work_order){
                        $quality_check_id = $quality_check->id;
                        $description = _l('quality_check_work_order').' <a href="'.admin_url('manufacturing/view_work_order/'.$quality_check->work_order_id.'/'.$work_order->manufacturing_order_id).'" target="_blank">'.$work_order->operation_name.'</a>';
                        $status = 'to_do';

                        $quality_check_detail_data[] = [
                            'quality_check_id' => $quality_check_id,
                            'check_work_order_id' => $quality_check->work_order_id,
                            'description' => $description,
                            'status' => $status,
                            'datecreated' => $datecreated,
                            'staffid' => $staffid,
                        ];
                    }
                }
                break;
            
            default:
                // code...
                break;
        }

        if(count($quality_check_detail_data) > 0){
            $affectedRows = $this->db->insert_batch(db_prefix(). 'quality_check_details', $quality_check_detail_data);
            if($affectedRows > 0){
                return true;
            }
        }
        return false;
    }

    /**
     * create quality check detail per quantity
     * @param  [type] $quality_check 
     * @return [type]                
     */
    public function create_quality_check_detail_per_quantity($quality_check)
    {
        $datecreated = date('Y-m-d H:i:s');
        $staffid = get_staff_user_id();

        $quality_check_detail_data = [];
        $_item_ids = [];
        if($quality_check->partial_transfer_test == 1 && $quality_check->percentage > 0){
            $quality_check_for_label = _l('quality_check_for').' '.$quality_check->percentage.' ('._l('quality_percentage_symbol').') '. _l('quality_of');
        }else{
            $quality_check_for_label = _l('quality_check_for');
        }

        switch ($quality_check->operation) {
            case 'receipts':
                if(quality_get_status_modules('warehouse')){
                    $this->load->model('warehouse/warehouse_model');

                    $goods_receipt = $this->warehouse_model->get_goods_receipt($quality_check->goods_receipt_id);
                    $goods_receipt_detail = $this->warehouse_model->get_goods_receipt_detail($quality_check->goods_receipt_id);

                    if($goods_receipt){
                        $quality_check_id = $quality_check->id;

                        $status = 'to_do';
                        $_temp_items = [];
                        foreach ($goods_receipt_detail as $key => $_value) {
                            if(!is_null($quality_check->item_id) && $quality_check->item_id > 0 && $quality_check->item_id != $_value['commodity_code']){
                                continue;
                            }

                            if(isset($_temp_items[$_value['commodity_code']])){
                                $_temp_items[$_value['commodity_code']]['quantities'] += (float)$_value['quantities'];
                            }else{
                                $_temp_items[$_value['commodity_code']]['commodity_name'] = quality_get_item_description($_value['commodity_code']);
                                $_temp_items[$_value['commodity_code']]['quantities'] = (float)$_value['quantities'];
                                $_temp_items[$_value['commodity_code']]['commodity_code'] = $_value['commodity_code'];
                            }

                        }
                        foreach ($_temp_items as $key => $value) {
                            $description = $quality_check_for_label.' <b>'.$value['commodity_name'].' - '._l('quality_quantity').': '.$value['quantities'].'</b>';
                            $quality_check_detail_data[] = [
                                'quality_check_id' => $quality_check_id,
                                'check_item_id' => $value['commodity_code'],
                                'description' => $description,
                                'status' => $status,
                                'datecreated' => $datecreated,
                                'staffid' => $staffid,
                            ];
                            $_item_ids[] = $value['commodity_code'];
                        }
                    }
                }

                break;
            case 'internal_transfers':
                if(quality_get_status_modules('warehouse')){
                    $this->load->model('warehouse/warehouse_model');

                    $internal_delivery = $this->warehouse_model->get_internal_delivery($quality_check->internal_delivery_id);
                    $internal_delivery_detail = $this->warehouse_model->get_internal_delivery_detail($quality_check->internal_delivery_id);
                    if($internal_delivery){
                        $quality_check_id = $quality_check->id;
                        $status = 'to_do';
                        $_temp_items = [];
                        foreach ($internal_delivery_detail as $key => $_value) {
                            if(!is_null($quality_check->item_id) && $quality_check->item_id > 0 && $quality_check->item_id != $_value['commodity_code']){
                                continue;
                            }

                            if(isset($_temp_items[$_value['commodity_code']])){
                                $_temp_items[$_value['commodity_code']]['quantities'] += (float)$_value['quantities'];
                            }else{
                                $_temp_items[$_value['commodity_code']]['commodity_name'] = quality_get_item_description($_value['commodity_code']);
                                $_temp_items[$_value['commodity_code']]['quantities'] = (float)$_value['quantities'];
                                $_temp_items[$_value['commodity_code']]['commodity_code'] = $_value['commodity_code'];
                            }

                        }
                        foreach ($_temp_items as $key => $value) {
                            $description = $quality_check_for_label.' <b>' .$value['commodity_name'].' - '._l('quality_quantity').': '.$value['quantities'].'</b>';
                            $quality_check_detail_data[] = [
                                'quality_check_id' => $quality_check_id,
                                'check_item_id' => $value['commodity_code'],
                                'description' => $description,
                                'status' => $status,
                                'datecreated' => $datecreated,
                                'staffid' => $staffid,
                            ];
                            $_item_ids[] = $value['commodity_code'];
                        }
                    }
                }
                break;
            case 'delivery_orders':
                if(quality_get_status_modules('warehouse')){
                    $this->load->model('warehouse/warehouse_model');

                    $goods_delivery = $this->warehouse_model->get_goods_delivery($quality_check->goods_delivery_id);
                    $goods_delivery_detail = $this->warehouse_model->get_goods_delivery_detail($quality_check->goods_delivery_id);
                    if($goods_delivery){
                        $quality_check_id = $quality_check->id;
                        $status = 'to_do';
                        $_temp_items = [];
                        foreach ($goods_delivery_detail as $key => $_value) {
                            if(!is_null($quality_check->item_id) && $quality_check->item_id > 0 && $quality_check->item_id != $_value['commodity_code']){
                                continue;
                            }

                            if(isset($_temp_items[$_value['commodity_code']])){
                                $_temp_items[$_value['commodity_code']]['quantities'] += (float)$_value['quantities'];
                            }else{
                                $_temp_items[$_value['commodity_code']]['commodity_name'] = quality_get_item_description($_value['commodity_code']);
                                $_temp_items[$_value['commodity_code']]['quantities'] = (float)$_value['quantities'];
                                $_temp_items[$_value['commodity_code']]['commodity_code'] = $_value['commodity_code'];
                            }
                        }
                        
                        foreach ($_temp_items as $key => $value) {
                            $description = $quality_check_for_label.' <b>' .$value['commodity_name'].' - '._l('quality_quantity').': '.$value['quantities'].'</b>';
                            $quality_check_detail_data[] = [
                                'quality_check_id' => $quality_check_id,
                                'check_item_id' => $value['commodity_code'],
                                'description' => $description,
                                'status' => $status,
                                'datecreated' => $datecreated,
                                'staffid' => $staffid,
                            ];
                            $_item_ids[] = $value['commodity_code'];
                        }
                    }
                }
                break;
            case 'manufacturing':
                if(quality_get_status_modules('manufacturing')){
                    $this->load->model('manufacturing/manufacturing_model');

                    $work_order = $this->manufacturing_model->get_work_order($quality_check->work_order_id);
                    if($work_order){
                        $quality_check_id = $quality_check->id;
                        $description = _l('quality_check_work_order').' <a href="'.admin_url('manufacturing/view_work_order/'.$quality_check->work_order_id.'/'.$work_order->manufacturing_order_id).'" target="_blank">'.$work_order->operation_name.'</a>';
                        $status = 'to_do';

                        $quality_check_detail_data[] = [
                            'quality_check_id' => $quality_check_id,
                            'check_work_order_id' => $quality_check->work_order_id,
                            'description' => $description,
                            'status' => $status,
                            'datecreated' => $datecreated,
                            'staffid' => $staffid,
                        ];
                    }
                }
                break;
            
            default:
                // code...
                break;
        }

        if(count($quality_check_detail_data) > 0){
            $affectedRows = $this->db->insert_batch(db_prefix(). 'quality_check_details', $quality_check_detail_data);
            if($affectedRows > 0){
                return true;
            }
        }
        return false;
    }

    /**
     * copy control point attachment
     * @param  [type] $control_point    
     * @param  [type] $quality_check_id 
     * @return [type]                   
     */
    public function copy_control_point_attachment($control_point, $quality_check_id)
    {   

        $attachments = $this->get_attachment_file($control_point, 'q_control_point');

        if (is_dir(CONTROL_POINT_FOLDER . $control_point)) {
            xcopy(CONTROL_POINT_FOLDER . $control_point, QUALITY_CHECK_FOLDER . $quality_check_id);
        }
        foreach ($attachments as $at) {

            $_at      = [];
            $_at[]    = $at;
            $external = false;
            if (!empty($at['external'])) {
                $external       = $at['external'];
                $_at[0]['name'] = $at['file_name'];
                $_at[0]['link'] = $at['external_link'];
                if (!empty($at['thumbnail_link'])) {
                    $_at[0]['thumbnailLink'] = $at['thumbnail_link'];
                }
            }

            $this->misc_model->add_attachment_to_database($quality_check_id,'q_check', $_at, $external);
        }   

        return true;
    }

    /**
     * quality get inventory receipt
     * @param  boolean $item_id 
     * @return [type]           
     */
    public function quality_get_inventory_receipt($item_id = false)
    {
        $this->db->select( 'DISTINCT('.db_prefix().'goods_receipt.id), '.db_prefix().'goods_receipt.description as description, '.db_prefix().'goods_receipt.goods_receipt_code, '.db_prefix().'goods_receipt.goods_receipt_code as name');
        $this->db->where('approval', 1);
        if( is_numeric($item_id) && $item_id > 0 ){
            $this->db->join(db_prefix() . 'goods_receipt_detail', db_prefix() . 'goods_receipt_detail.goods_receipt_id = ' . db_prefix() . 'goods_receipt.id', 'left');
            $this->db->where(db_prefix() . 'goods_receipt_detail.commodity_code', $item_id);
        }
        $this->db->order_by('id', 'DESC');
        $goods_receipts = $this->db->get(db_prefix() . 'goods_receipt')->result_array();
        return $goods_receipts;
    }

    /**
     * quality get inventory delivery
     * @param  boolean $item_id 
     * @return [type]           
     */
    public function quality_get_inventory_delivery($item_id= false)
    {
        $this->db->select('DISTINCT('.db_prefix().'goods_delivery.id), '.db_prefix().'goods_delivery.description as description, '.db_prefix().'goods_delivery.goods_delivery_code, '.db_prefix().'goods_delivery.goods_delivery_code as name');
        $this->db->where('approval', 1);
        if( is_numeric($item_id) && $item_id > 0 ){
            $this->db->join(db_prefix() . 'goods_delivery_detail', db_prefix() . 'goods_delivery_detail.goods_delivery_id = ' . db_prefix() . 'goods_delivery.id', 'left');
            $this->db->where(db_prefix() . 'goods_delivery_detail.commodity_code', $item_id);
        }
        $this->db->order_by('id', 'DESC');

        $goods_deliverys = $this->db->get(db_prefix() . 'goods_delivery')->result_array();
        return $goods_deliverys;
    }

    /**
     * quality get inventory internal delivery
     * @param  boolean $item_id 
     * @return [type]           
     */
    public function quality_get_inventory_internal_delivery($item_id= false)
    {
        $this->db->select('DISTINCT('.db_prefix().'internal_delivery_note.id), '.db_prefix().'internal_delivery_note.description as description, '.db_prefix().'internal_delivery_note.internal_delivery_code, '.db_prefix().'internal_delivery_note.internal_delivery_code as name');
        $this->db->where('approval', 1);
        if( is_numeric($item_id) && $item_id > 0 ){
            $this->db->join(db_prefix() . 'internal_delivery_note_detail', db_prefix() . 'internal_delivery_note_detail.internal_delivery_id = ' . db_prefix() . 'internal_delivery_note.id', 'left');
            $this->db->where(db_prefix() . 'internal_delivery_note_detail.commodity_code', $item_id);
        }
        $this->db->order_by('id', 'DESC');

        $internal_delivery_notes = $this->db->get(db_prefix() . 'internal_delivery_note')->result_array();
        return $internal_delivery_notes;
    }

    /**
     * quality check detail status mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @return [type]         
     */
    public function quality_check_detail_status_mark_as($status, $id)
    {
        $status_f = false;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_check_details', ['status' => $status, 'completion_date' => date('Y-m-d H:i:s')]);
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    /**
     * get quality check detail
     * @param  boolean $id 
     * @return [type]      
     */
    public function get_quality_check_detail($id = false, $quality_check_id = false, $where = [] )
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            $details = $this->db->get(db_prefix() . 'quality_check_details')->row();

            return $details;
        }

        if(is_numeric($quality_check_id)){
            if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
                $this->db->where($where);
            }
            $this->db->where('quality_check_id', $quality_check_id);
            $details = $this->db->get(db_prefix() . 'quality_check_details')->result_array();

            return $details;
        }
    }

    /**
     * update quality check detail
     * @param  [type] $data 
     * @param  [type] $id   
     * @return [type]       
     */
    public function update_quality_check_detail($data, $id)
    {
        $affected_rows=0;

        $_data_update = [];
        $_data_update['actual_size'] =  isset($data['actual_size']) ? $data['actual_size'] : 0.00;
        $_data_update['quality_check_results'] = $data['quality_check_results'];
        $_data_update['status'] = $data['quality_check'];
        if($data['quality_check'] != 'validate'){
            $_data_update['completion_date'] = date('Y-m-d H:i:s');
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_check_details', $_data_update);
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('after_quality_check_detail_updated', $id);

            $affected_rows++;
        }

        if($affected_rows > 0){
            return true;
        }

        return false;  
    }

    /**
     * should quality check
     * @param  [type] $id   
     * @param  [type] $type 
     * @return [type]       
     */
    public function handle_create_quality_check($id, $type)
    {
        $quality_control_points = [];
        if($type == 'manufacturing'){
            if(quality_get_status_modules('manufacturing')){
                $this->load->model('manufacturing/manufacturing_model');
                $work_order = $this->manufacturing_model->get_work_order($id);
                if($work_order){
                    $routing_detail_id = $work_order->routing_detail_id;
                    $quality_control_points = $this->get_control_point(false, 'operation = "'.$type.'" AND mrp_routing_detail_id = '.$routing_detail_id.' AND status = 1');
                }
            }
        }else{
            $quality_control_points = $this->get_control_point(false, 'operation = "'.$type.'" AND status = 1');
        }

        if(count($quality_control_points) > 0){
            $quality_check_number = get_option('quality_check_number');
            foreach ($quality_control_points as $key => $value) {
                if($this->should_quality_check($value)){
                    $new_quality_check = [];

                    $new_quality_check['number'] = $quality_check_number;
                    $new_quality_check['control_per'] = $value['control_per'];
                    $new_quality_check['control_point_id'] = $value['id'];
                    $new_quality_check['name'] = $value['name'];
                    $new_quality_check['item_id'] = $value['item_id'];
                    $new_quality_check['operation'] = $type;
                    $new_quality_check['team_id'] = $value['team_id'];
                    $new_quality_check['partial_transfer_test'] = $value['partial_transfer_test'];
                    $new_quality_check['percentage'] = $value['percentage'];
                    $new_quality_check['type'] = $value['type'];
                    $new_quality_check['type_of_unit_of_measurement'] = $value['type_of_unit_of_measurement'];
                    $new_quality_check['norm'] = $value['norm'];
                    $new_quality_check['unit_of_measurement'] = $value['unit_of_measurement'];
                    $new_quality_check['tolerance_from'] = $value['tolerance_from'];
                    $new_quality_check['tolerance_to'] = $value['tolerance_to'];
                    $new_quality_check['instructions'] = $value['instructions'];
                    $new_quality_check['message_if_failure'] = $value['message_if_failure'];
                    $new_quality_check['note'] = $value['note'];
                    $new_quality_check['datecreated'] = _dt(date('Y-m-d H:i:s'));
                    $new_quality_check['failure_location_ids'] = explode(',', $value['failure_location_ids'] ?? '');

                    if($type == 'receipts'){
                        $new_quality_check['goods_receipt_id'] = $id;
                    }elseif($type == 'internal_transfers'){
                        $new_quality_check['internal_delivery_id'] = $id;
                    }elseif($type == 'delivery_orders'){
                        $new_quality_check['goods_delivery_id'] = $id;
                    }elseif($type == 'manufacturing'){
                        $new_quality_check['work_order_id'] = $id;
                    }

                    $quality_check_id = $this->add_quality_check($new_quality_check);
                    $this->quality_check_status_mark_as('to_do', $quality_check_id);
                    $quality_check_number++;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * should quality check
     * @param  [type] $quality_control_point 
     * @return [type]                        
     */
    public function should_quality_check($quality_control_point)
    {
        if($quality_control_point['control_frequency'] == 'all'){
            return true;
        }elseif($quality_control_point['control_frequency'] == 'randomly'){
            return mt_rand(1, 100) <= $quality_control_point['every_percent_of_transfer'];
        }elseif($quality_control_point['control_frequency'] == 'periodically'){
            // Check if is first recurring
            if (!$quality_control_point['last_recurring_date']) {
                $last_recurring_date = date('Y-m-d', strtotime($quality_control_point['datecreated']));
            } else {
                $last_recurring_date = date('Y-m-d', strtotime($quality_control_point['last_recurring_date']));
            }

            $re_create_at = date('Y-m-d', strtotime('+' . $quality_control_point['periodically_every_custom'] . ' ' . strtoupper($quality_control_point['periodically_type_custom']), strtotime($last_recurring_date)));
            if ((date('Y-m-d') >= $re_create_at) || (date('Y-m-d') == $last_recurring_date)) {

                // Update last recurring date to this quality control points
                if (date('Y-m-d') > $re_create_at) {
                    $this->db->where('id', $quality_control_point['id']);
                    $this->db->update(db_prefix() . 'quality_control_points', [
                        'last_recurring_date' => $re_create_at,
                    ]);
                }

                return true;
            }
        }
        return false;
    }

    /**
     * change quality control status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_quality_control_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_control_points', [
            'status' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    /**
     * get quality_alert
     * @param  boolean $id     
     * @param  boolean $active 
     * @param  array   $where  
     * @return [type]          
     */
    public function get_quality_alert($id = false, $where = []) {

        if (is_numeric($id)) {
            $this->db->where('id', $id);
            $quality_alert = $this->db->get(db_prefix() . 'quality_alerts')->row();
            return $quality_alert;
        }
        if ($id == false) {

            if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
                $this->db->where($where);
            }
            $this->db->order_by('id', 'DESC');

            return $this->db->get(db_prefix() . 'quality_alerts')->result_array();
        }
    }

    /**
     * add quality_alert
     * @param [type] $data 
     */
    public function add_quality_alert($data)
    {
        $data['staffid'] = get_staff_user_id();
        $data['hash'] = app_generate_hash();
        $data['prefix'] = get_option('quality_alert_prefix');
        $data['number_format'] = get_option('quality_alert_number_format');
        if(!isset($data['status'])){
            $data['status'] = 'new';
        }

        if($data['datecreated'] != ''){
            $data['datecreated'] = to_sql_date($data['datecreated'], true);
        }else{
            $data['datecreated'] = null;
        }

        if(isset($data['tags']) && $data['tags'] != ''){
            $tags = $data['tags'];
            unset($data['tags']);
        }else{
            $tags = '';
            if(isset($data['tags'])){
                unset($data['tags']);
            }
        }

        $this->db->insert(db_prefix().'quality_alerts',$data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update next repair job number in settings
            $this->db->where('name', 'quality_alert_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');
            
            handle_tags_save($tags, $insert_id, 'quality_alert');
            $this->send_quality_alert_to_staff($insert_id, false, '', true);

            hooks()->do_action('after_quality_alert_added', $insert_id);

            return $insert_id;
        }
        return false;
    }

    /**
     * update quality_alert
     * @param  [type] $data 
     * @param  [type] $id   
     * @return [type]       
     */
    public function update_quality_alert($data, $id)
    {
        $affected_rows=0;
        if($data['datecreated'] != ''){
            $data['datecreated'] = to_sql_date($data['datecreated'], true);
        }else{
            $data['datecreated'] = null;
        }

        if (array_key_exists('tags', $data)) {
            if (handle_tags_save($tags = $data['tags'], $id, 'quality_alert')) {
                $updated = true;
            }
        }

        if(isset($data['tags']) && $data['tags'] != ''){
            $tags = isset($data['tags']);
            unset($data['tags']);
        }else{
            $tags = '';
             if(isset($data['tags'])){
                unset($data['tags']);
            }
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_alerts', $data);
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('after_quality_alert_updated', $id);

            $affected_rows++;
        }

        if($affected_rows > 0){
            return true;
        }

        return false;  
    }

    /**
     * delete quality_alert
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_quality_alert($id)
    {
        hooks()->do_action('before_quality_alert_deleted', $id);

        $affected_rows = 0;
        
        if (is_dir(QUALITY_ALERT_FOLDER. $id)) {
                // okey only index.html so we can delete the folder also
            delete_dir(QUALITY_ALERT_FOLDER. $id);
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'quality_alerts');
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('after_quality_alert_deleted', $id);

            $affected_rows++;
        }

        if($affected_rows > 0){
            return true;
        }
        return false;
    }

    /**
     * quality alert status mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @return [type]         
     */
    public function quality_alert_status_mark_as($status, $id)
    {
        $status_f = false;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_alerts', ['status' => $status]);
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    /**
     * quality alert priority mark as
     * @param  [type] $priority 
     * @param  [type] $id       
     * @return [type]           
     */
    public function quality_alert_priority_mark_as($priority, $id)
    {
        $status_f = false;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_alerts', ['priority' => $priority]);
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    /**
     * quality alert root cause mark as
     * @param  [type] $root_cause 
     * @param  [type] $id         
     * @return [type]             
     */
    public function quality_alert_root_cause_mark_as($root_cause, $id)
    {
        $status_f = false;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quality_alerts', ['root_cause' => $root_cause]);
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    /**
     * get failure location
     * @return [type] 
     */
    public function get_failure_location()
    {
        $failure_location_ids = [];
        $sql_where = '
        SELECT DISTINCT(failure_location_ids) FROM `'.db_prefix().'quality_control_points`
        WHERE failure_location_ids is not null
        UNION
        SELECT DISTINCT(failure_location_ids) FROM `'.db_prefix().'quality_checks`
        WHERE failure_location_ids is not null
        ';
        $failure_location = $this->db->query($sql_where)->result_array();
        if(count($failure_location) > 0){
            foreach ($failure_location as $key => $value) {
                if($value['failure_location_ids'] != ''){
                    $location_ids = explode(',', $value['failure_location_ids']);
                    foreach ($location_ids as $key => $location_id) {
                        if(!in_array($location_id, $failure_location_ids)){
                            $failure_location_ids[]  = $location_id;
                        }
                    }
                }
            }
        }
        if(count($failure_location_ids) > 0){
            $failure_location_ids = implode(',', $failure_location_ids);
        }else{
            $failure_location_ids = "";
        }
        return $failure_location_ids;
    }

    /**
     * get quality check statistics by teams
     * @return [type] 
     */
    public function get_quality_check_statistics_by_teams()
    {
        $quality_check_by_teams = [];
        $quality_alert_by_teams = [];

        // total quality check by teams
        $sql_where = 'SELECT '.db_prefix().'quality_teams.name, team_id, '.db_prefix().'quality_checks.status, count('.db_prefix().'quality_checks.id) as total_check FROM `'.db_prefix().'quality_checks`
        LEFT JOIN '.db_prefix().'quality_teams on '.db_prefix().'quality_teams.id = '.db_prefix().'quality_checks.team_id
        GROUP BY team_id, '.db_prefix().'quality_checks.status';
        $total_quality_check_by_teams = $this->db->query($sql_where)->result_array();

        // total quality alert by teams
        $alert_by_team_sql_where = 'SELECT team_id, count(id) as total_alert FROM `tblquality_alerts`
        WHERE `status` != "resolved"
        GROUP BY team_id';
        $total_quality_alert_by_teams = $this->db->query($alert_by_team_sql_where)->result_array();

        foreach ($total_quality_alert_by_teams as $key => $value) {
            $quality_alert_by_teams[$value['team_id']] = $value['total_alert'];
        }

        foreach ($total_quality_check_by_teams as $key => $value) {
            if(isset($quality_check_by_teams[$value['team_id']])){
                $quality_check_by_teams[$value['team_id']][$value['status']] = $value['total_check'];
            }else{
                $quality_check_by_teams[$value['team_id']]['team_name'] = $value['name'];
                $quality_check_by_teams[$value['team_id']][$value['status']] = $value['total_check'];
                if(isset($quality_alert_by_teams[$value['team_id']])){
                    $quality_check_by_teams[$value['team_id']]['alert'] = $quality_alert_by_teams[$value['team_id']];
                }else{
                    $quality_check_by_teams[$value['team_id']]['alert'] = 0;
                }
            }
        }

        return $quality_check_by_teams;
    }

    /**
     * get report quality check
     * @param  [type] $from_date 
     * @param  [type] $to_date   
     * @return [type]            
     */
    public function get_report_quality_check($from_date, $to_date)
    {   
        $chart=[];
        $chart['total_check'] = [];
        $chart['categories'] = [];
        $sql_where="SELECT  status, count(id) as total_check FROM ".db_prefix()."quality_checks
            where date_format(datecreated, '%Y-%m-%d') >= '".$from_date."' AND date_format(datecreated, '%Y-%m-%d') <= '".$to_date."'
            group by status
            order by status desc
            ";

        $quality_checks = $this->db->query($sql_where)->result_array();

        foreach ($quality_checks as $key => $value) {
            $chart['total_check'][] = (float)$value['total_check'];
            $chart['categories'][] = _l('quality_'.$value['status']);
        }
        return $chart;
    }

    /**
     * get report quality alert
     * @param  [type] $from_date 
     * @param  [type] $to_date   
     * @return [type]            
     */
    public function get_report_quality_alert($from_date, $to_date)
    {   
        $chart=[];
        $chart['total_alert'] = [];
        $chart['categories'] = [];
        $sql_where="SELECT  status, count(id) as total_alert FROM ".db_prefix()."quality_alerts
            where date_format(datecreated, '%Y-%m-%d') >= '".$from_date."' AND date_format(datecreated, '%Y-%m-%d') <= '".$to_date."'
            group by status
            order by status desc
            ";

        $quality_alerts = $this->db->query($sql_where)->result_array();

        foreach ($quality_alerts as $key => $value) {
            $chart['total_alert'][] = (float)$value['total_alert'];
            $chart['categories'][] = _l('quality_'.$value['status']);
        }
        return $chart;
    }

    /**
     * send quality check to staff
     * @param  [type]  $id        
     * @param  boolean $attachpdf 
     * @param  string  $cc        
     * @param  boolean $manually  
     * @return [type]             
     */
    public function send_quality_check_to_staff($id, $attachpdf = true, $cc = '', $manually = false)
    {
        $quality_check = $this->get_quality_check($id);

        $template_name = 'quality_check_send_to_staff';
       
        $quality_check_number = format_quality_check_number($quality_check->id);

        $emails_sent = [];
        $send_to = $this->input->post('sent_to_staff');

        if($manually == true){
            $send_to = [];
            $quality_team = get_quality_team_name($quality_check->team_id, false);
            if($quality_team){
                $send_to[] = $quality_team->team_member;
            }
        }

        if (is_array($send_to) && count($send_to) > 0) {
            $i = 0;

            if ($attachpdf) {
                $_pdf_quality_check = $this->get_quality_check($quality_check->id);
                set_mailing_constant();
                $pdf = quality_check_pdf($_pdf_quality_check);

                $attach = $pdf->Output($quality_check_number . '.pdf', 'S');
            }

            foreach ($send_to as $staffid) {
                if ($staffid != '') {
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $staff = $this->staff_model->get($staffid);

                    if (!$staff) {
                        continue;
                    }

                    // send notification
                    $additional_data = '';
                    $mes = 'quality_notify_send_quality_check';
                    $link = 'quality/quality_check_detail/' . $quality_check->id . '?tab=detail/' . $quality_check->hash;
                    if(get_staff_user_id() != $staffid){
                        $notified = add_notification([
                            'description' => $mes,
                            'touserid' => $staff->staffid,
                            'link' => $link,
                            'additional_data' => serialize([
                                $additional_data,
                            ]),
                        ]);
                        if ($notified) {
                            pusher_trigger_notification([$staff->staffid]);
                        }
                    }

                    $template = mail_template($template_name, 'quality', $quality_check, $staff, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_quality_check_to_staff_file_name', [
                            'file_name' => str_replace('/', '-', $quality_check_number . '.pdf'),
                            'quality_check'  => $_pdf_quality_check,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        array_push($emails_sent, $staff->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if (count($emails_sent) > 0) {
            hooks()->do_action('quality_check_sent', $id);

            return true;
        }

        return false;
    }

    /**
     * send quality alert to staff
     * @param  [type]  $id        
     * @param  boolean $attachpdf 
     * @param  string  $cc        
     * @param  boolean $manually  
     * @return [type]             
     */
    public function send_quality_alert_to_staff($id, $attachpdf = true, $cc = '', $manually = false)
    {
        $quality_alert = $this->get_quality_alert($id);

        $template_name = 'quality_alert_send_to_staff';
       
        $quality_alert_number = format_quality_alert_number($quality_alert->id);

        $emails_sent = [];
        $send_to = $this->input->post('sent_to_staff');

        if($manually == true){
            $send_to = [];
            $quality_team = get_quality_team_name($quality_alert->team_id, false);
            if($quality_team){
                $send_to[] = $quality_team->team_member;
            }
        }

        if (is_array($send_to) && count($send_to) > 0) {
            $i = 0;

            if ($attachpdf) {
                $_pdf_quality_alert = $this->get_quality_alert($quality_alert->id);
                set_mailing_constant();
                $pdf = quality_alert_pdf($_pdf_quality_alert);

                $attach = $pdf->Output($quality_alert_number . '.pdf', 'S');
            }

            foreach ($send_to as $staffid) {
                if ($staffid != '') {
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $staff = $this->staff_model->get($staffid);

                    if (!$staff) {
                        continue;
                    }

                    // send notification
                    $additional_data = '';
                    $mes = 'quality_notify_send_quality_alert';
                    $link = 'quality/quality_alert_detail/' . $quality_alert->id . '?tab=detail/' . $quality_alert->hash;
                    if(get_staff_user_id() != $staffid){
                        $notified = add_notification([
                            'description' => $mes,
                            'touserid' => $staff->staffid,
                            'link' => $link,
                            'additional_data' => serialize([
                                $additional_data,
                            ]),
                        ]);
                        if ($notified) {
                            pusher_trigger_notification([$staff->staffid]);
                        }
                    }

                    $template = mail_template($template_name, 'quality', $quality_alert, $staff, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_quality_alert_to_staff_file_name', [
                            'file_name' => str_replace('/', '-', $quality_alert_number . '.pdf'),
                            'quality_alert'  => $_pdf_quality_alert,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        array_push($emails_sent, $staff->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if (count($emails_sent) > 0) {
            hooks()->do_action('quality_alert_sent', $id);

            return true;
        }

        return false;
    }

    /**
     * get quality check with code
     * @return [type] 
     */
    public function get_quality_check_with_code() {

        $this->db->where('status != "draft" AND status != "cancelled"');
        $this->db->order_by('id', 'DESC');
        $quality_checks = $this->db->get(db_prefix() . 'quality_checks')->result_array();
        foreach ($quality_checks as $key => $value) {
            $quality_checks[$key]['code'] = sales_number_format($value['number'], $value['number_format'], $value['prefix'], $value['datecreated']);
        }
        return $quality_checks;
    }

    /**
     * create internal delivery from detail checklist
     * @param  [type] $data 
     * @return [type]       
     */
    public function create_internal_delivery_from_detail_checklist($data){
        $products = $data['products'];
        $new_internal_delivery = [];
        $items = [];
        $item_index = 1;
        $internal_delivery_name = 'INTERNAL_DELIVERY' . date('YmdHi');
        $date_c = _d(date('Y-m-d'));
        $date_add = _d(date('Y-m-d'));
        $staff_id = get_staff_user_id();
        $total_amount = 0;
        $description = _l('quality_internal_delivery_related_qc').' '.format_quality_check_number($data['quality_check_id']);
        if(quality_get_status_modules('warehouse')){
            $this->load->model('warehouse/warehouse_model');
        }

        foreach ($products as $key => $value) {
            if(isset($value['7']) && is_array($value['7'])){
                $arr_serial = $value['7'];
                foreach ($arr_serial as $key => $serial) {
                    $available_quantity = 0;
                    $get_available_quantity = $this->warehouse_model->get_quantity_inventory($value['3'], $value['0']);
                    if ($get_available_quantity != null) {
                        $available_quantity = get_object_vars($get_available_quantity)['inventory_number'];
                    } 
                    $into_money = (float)$value['4'] * (float)$value['5'] ;
                    $total_amount += (float)$into_money;

                    $items[] = [
                        'order' => $item_index,
                        'commodity_name' => $value['1'],
                        'from_stock_name' => $value['3'],
                        'note' => null,
                        'to_stock_name' => $value['6'],
                        'available_quantity' => $available_quantity,
                        'unit_name' => null,
                        'quantities' => 1,
                        'unit_price' => $value['5'],
                        'commodity_code' => $value['0'],
                        'unit_id' => $value['2'],
                        'into_money' => $into_money,
                        'serial_number' => $serial,
                    ];
                    $item_index++;
                }
            }else{
                $available_quantity = 0;
                $get_available_quantity = $this->warehouse_model->get_quantity_inventory($value['3'], $value['0']);
                if ($get_available_quantity != null) {
                    $available_quantity = get_object_vars($get_available_quantity)['inventory_number'];
                } 
                $into_money = (float)$value['4'] * (float)$value['5'] ;
                $total_amount += (float)$into_money;
                $items[] = [
                    'order' => $item_index,
                    'commodity_name' => $value['1'],
                    'from_stock_name' => $value['3'],
                    'note' => null,
                    'to_stock_name' => $value['6'],
                    'available_quantity' => $available_quantity,
                    'unit_name' => null,
                    'quantities' => $value['4'],
                    'unit_price' => $value['5'],
                    'commodity_code' => $value['0'],
                    'unit_id' => $value['2'],
                    'into_money' => $into_money,
                    'serial_number' => null,
                ];

                $item_index++;
            }
        }

        $new_internal_delivery['internal_delivery_name'] = $internal_delivery_name;
        $new_internal_delivery['date_c'] = $date_c;
        $new_internal_delivery['date_add'] = $date_add;
        $new_internal_delivery['staff_id'] = $staff_id;
        $new_internal_delivery['total_amount'] = $total_amount;
        $new_internal_delivery['description'] = $description;
        $new_internal_delivery['newitems'] = $items;

        if(quality_get_status_modules('warehouse')){
            $this->load->model('warehouse/warehouse_model');
            $location_id = $this->warehouse_model->add_internal_delivery($new_internal_delivery);
            if($location_id){
                $this->db->where('id', $data['quality_check_id']);
                $this->db->update(db_prefix() . 'quality_checks', ['send_product_failure_location' => $location_id]);
                return true;
            }
        }
        return false;

    }

    /**
     * create inventory receipt from detail checklist
     * @return [type] 
     */
    public function create_inventory_receipt_from_detail_checklist($data){
        $products = $data['products'];
        $new_inventory_receipt = [];
        $items = [];
        $item_index = 1;

        $save_and_send_request = true;
        $currency = 0;
        $currency_exchange_rate = 0;
        $date_c = _d(date('Y-m-d'));
        $date_add = _d(date('Y-m-d'));
        $pr_order_id = null;
        $supplier_code = null;
        $supplier_name = null;
        $buyer_id = 0;
        $project = 0;
        $type = null;
        $department = null;
        $requester = null;
        $deliver_name = null;
        $expiry_date_m = _d(date('Y-m-d'));
        $invoice_no = '';
        $total_goods_money = 0;
        $value_of_inventory = 0;
        $total_tax_money = 0;
        $total_money = 0;
        $description = _l('quality_inventory_receipt_related_qc').' '.format_quality_check_number($data['quality_check_id']);
        if(quality_get_status_modules('warehouse')){
            $this->load->model('warehouse/warehouse_model');
        }

        foreach ($products as $key => $value) {
            $str_serial = null;
            $lot_number = null;
            $expiry_date = null;
            if(isset($value['7']) && is_array($value['7'])){
                $str_serial = implode(',', $value['7']);
            }
            if(isset($value['8']) && $value['8'] != ''){
                $expiry_date = $value['8'];
            }
            if(isset($value['9']) && $value['9'] != ''){
                $arr_lot_quantity = new_explode(',', $value['9']);
                if(isset($arr_lot_quantity[0])){
                    $lot_number = $arr_lot_quantity[0];
                }
            }

            $into_money = (float)$value['4'] * (float)$value['5'] ;
            $total_goods_money += (float)$into_money;
            $value_of_inventory += (float)$into_money;
            $total_money += (float)$into_money;

            $items[] = [
                'order' => $item_index,
                'commodity_name' => $value['1'],
                'note' => null,
                'warehouse_id' => $value['6'],
                'date_manufacture' => null,
                'quantities' => $value['4'],
                'unit_price' => $value['5'],
                'commodity_code' => $value['0'],
                'lot_number' => $lot_number,
                'unit_id' => $value['2'],
                'serial_number' => $str_serial,
                'expiry_date' => $expiry_date,
            ];
            $item_index++;
        }

        $new_inventory_receipt['save_and_send_request'] = $save_and_send_request;
        $new_inventory_receipt['currency'] = $currency;
        $new_inventory_receipt['currency_exchange_rate'] = $currency_exchange_rate;
        $new_inventory_receipt['date_c'] = $date_c;
        $new_inventory_receipt['date_add'] = $date_add;
        $new_inventory_receipt['pr_order_id'] = $pr_order_id;
        $new_inventory_receipt['supplier_code'] = $supplier_code;
        $new_inventory_receipt['supplier_name'] = $supplier_name;
        $new_inventory_receipt['buyer_id'] = $buyer_id;
        $new_inventory_receipt['project'] = $project;
        $new_inventory_receipt['type'] = $type;
        $new_inventory_receipt['department'] = $department;
        $new_inventory_receipt['requester'] = $requester;
        $new_inventory_receipt['deliver_name'] = $deliver_name;
        $new_inventory_receipt['expiry_date_m'] = $expiry_date_m;
        $new_inventory_receipt['invoice_no'] = $invoice_no;
        $new_inventory_receipt['total_goods_money'] = $total_goods_money;
        $new_inventory_receipt['value_of_inventory'] = $value_of_inventory;
        $new_inventory_receipt['total_tax_money'] = $total_tax_money;
        $new_inventory_receipt['total_money'] = $total_money;
        $new_inventory_receipt['description'] = $description;
        $new_inventory_receipt['newitems'] = $items;

        if(quality_get_status_modules('warehouse')){
            $this->load->model('warehouse/warehouse_model');
            $location_id = $this->warehouse_model->add_goods_receipt($new_inventory_receipt);
            if($location_id){
                $this->db->where('id', $data['quality_check_id']);
                $this->db->update(db_prefix() . 'quality_checks', ['send_product_failure_location' => $location_id]);
                return true;
            }
        }
        return false;

    }


/*end file*/
}
