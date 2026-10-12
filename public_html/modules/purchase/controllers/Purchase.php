<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * This class describes a purchase.
 */
class purchase extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_model');
        hooks()->do_action('purchase_init');        
    }

    public function index(){
        if(is_staff_logged_in()){
            redirect(admin_url('purchase/reports'));
        }

        if(is_vendor_logged_in()){

            redirect(site_url('purchase/authentication_vendor'));
        }
    }

    /**
     * { vendors }
     */
    public function vendors(){
    	purchase_token();
        $data['title']          = _l('vendor');
        $data['vendor_categorys'] = $this->purchase_model->get_vendor_category();
        purchase_init();
        $this->load->view('vendors/manage', $data);
    }

    /**
     * { table vendor }
     */
    public function table_vendor()
    {

        $this->app->get_table_data(module_views_path('purchase', 'vendors/table_vendor'));
    }

    /**
     * { vendor }
     *
     * @param      string  $id     The vendor
     * @return      view
     */
    public function vendor($id = '')
    {
        
        if ($this->input->post() && !$this->input->is_ajax_request()) {
            if ($id == '') {
                

                $data = $this->input->post();

                $save_and_add_contact = false;
                if (isset($data['save_and_add_contact'])) {
                    unset($data['save_and_add_contact']);
                    $save_and_add_contact = true;
                }
                $id = $this->purchase_model->add_vendor($data);
                if (!has_permission('purchase_vendors', '', 'view')) {
                    $assign['customer_admins']   = [];
                    $assign['customer_admins'][] = get_staff_user_id();
                    $this->purchase_model->assign_vendor_admins($assign, $id);
                }
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('vendor')));
                    if ($save_and_add_contact == false) {
                        redirect(admin_url('purchase/vendor/' . $id));
                    } else {
                        redirect(admin_url('purchase/vendor/' . $id . '?group=contacts&new_contact=true'));
                    }
                }
            } else {

                if(has_permission('purchase_vendors', '', 'view_own') && !is_admin()){
                    if(!is_vendor_admin($id, get_staff_user_id())){
                        access_denied('purchase');
                    }
                }
                
                $success = $this->purchase_model->update_vendor($this->input->post(), $id);
                if ($success == true) {
                    set_alert('success', _l('updated_successfully', _l('vendor')));
                }
                redirect(admin_url('purchase/vendor/' . $id));
            }
        }

        $group         = !$this->input->get('group') ? 'profile' : $this->input->get('group');
        $data['group'] = $group;

        if ($group != 'contacts' && $contact_id = $this->input->get('contactid')) {
            redirect(admin_url('purchase/vendor/' . $id . '?group=contacts&contactid=' . $contact_id));
        }

        
        

        if ($id == '') {
            $title = _l('add_new', _l('vendor_lowercase'));
        } else {
            if(has_permission('purchase_vendors', '', 'view_own') && !is_admin()){
                if(!is_vendor_admin($id, get_staff_user_id())){
                    access_denied('purchase');
                }
            }

            $client                = $this->purchase_model->get_vendor($id);
            $data['customer_tabs'] = get_customer_profile_tabs();

            if (!$client) {
                show_404();
            }

            $data['contacts'] = $this->purchase_model->get_contacts($id);
            
            $data['payments'] = $this->purchase_model->get_payment_invoices_by_vendor($id);

            $data['group'] = $this->input->get('group');

            $data['vendor_contacts'] = $this->purchase_model->get_contacts($id);

	        $data['title']                 = _l('setting');
	        $data['tab'][] = ['name' => 'profile', 'icon' => '<i class="fa fa-user-circle menu-icon"></i>'];
	        $data['tab'][] = ['name' => 'contacts','icon' => '<i class="fa fa-users menu-icon"></i>'];
            $data['tab'][] = ['name' => 'quotations','icon' => '<i class="fa fa-file-powerpoint menu-icon"></i>'];
            $data['tab'][] = ['name' => 'contracts', 'icon' => '<i class="fa fa-file-text menu-icon"></i>'];
            $data['tab'][] = ['name' => 'purchase_order', 'icon' => '<i class="fa fa-cart-plus menu-icon"></i>'];
            $data['tab'][] = ['name' => 'purchase_invoice', 'icon' => '<i class="fa fa-clipboard menu-icon"></i>'];
            $data['tab'][] = ['name' => 'debit_notes', 'icon' => '<i class="fa fa-credit-card menu-icon"></i>'];
            $data['tab'][] = ['name' => 'purchase_statement', 'icon' => '<i class="fa fa-building menu-icon"></i>'];
            $data['tab'][] = ['name' => 'payments', 'icon' => '<i class="fa fa-usd menu-icon"></i>']; 
            $data['tab'][] = ['name' => 'expenses', 'icon' => '<i class="fa fa-tasks menu-icon"></i>']; 
            $data['tab'][] = ['name' => 'notes', 'icon' => '<i class="fa fa-sticky-note menu-icon"></i>'];
            $data['tab'][] = ['name' => 'attachments', 'icon' => '<i class="fa fa-paperclip menu-icon"></i>'];
	        
	        if($data['group'] == ''){
	            $data['group'] = 'profile';
	        }
	        $data['tabs']['view'] = 'vendors/groups/'.$data['group'];
            // Fetch data based on groups
            if ($data['group'] == 'profile') {
               $data['customer_admins'] = $this->purchase_model->get_vendor_admins($id);
            }  elseif ($group == 'estimates') {
                $this->load->model('estimates_model');
                $data['estimate_statuses'] = $this->estimates_model->get_statuses();
            } elseif ($group == 'notes') {
               
                $data['user_notes'] = $this->misc_model->get_notes($id, 'pur_vendor');
            } elseif ($group == 'payments') {
                $this->load->model('payment_modes_model');
                $data['payment_modes'] = $this->payment_modes_model->get();
            } elseif ($group == 'attachments') {
                $data['attachments'] = get_all_pur_vendor_attachments($id);
            } elseif ($group == 'expenses') {
                $this->load->model('expenses_model');
                $data['expenses'] = $this->expenses_model->get('', [ 'vendor' =>  $id ]);
            } elseif ($group == 'purchase_invoice') {
                // Expenses bought on credit are vendor bills too - the vendor is owed
                // the amount and it sits on their payable account - so they belong on
                // this tab alongside purchase invoices. They live in tblexpenses, not
                // tblpur_invoices, so they cannot join the datatable and are listed
                // separately below it. Only credit expenses carry an expinv_number.
                $this->load->model('expenses_model');

                // Same call the expenses tab makes, then filtered in PHP. Kept out
                // of SQL deliberately: an IS NOT NULL test has no reliable array
                // form in CI's where() and a raw condition string would be run
                // through protect_identifiers. The row count per vendor is small.
                $data['expense_invoices'] = array_values(array_filter(
                    $this->expenses_model->get('', ['vendor' => $id]),
                    function ($expense) {
                        return !empty($expense['expinv_number']) && empty($expense['is_deleted']);
                    }
                ));
            }

            $data['staff'] = $this->staff_model->get('', ['active' => 1]);

            $data['client'] = $client;
            $title          = $client->company;

            // Get all active staff members (used to add reminder)
            $data['members'] = $data['staff'];

            if (!empty($data['client']->company)) {
                // Check if is realy empty client company so we can set this field to empty
                // The query where fetch the client auto populate firstname and lastname if company is empty
                if (is_empty_vendor_company($data['client']->userid)) {
                    $data['client']->company = '';
                }
            }
        }

        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        if ($id != '') {
            $customer_currency = $data['client']->default_currency;

            foreach ($data['currencies'] as $currency) {
                if ($customer_currency != 0) {
                    if ($currency['id'] == $customer_currency) {
                        $customer_currency = $currency;

                        break;
                    }
                } else {
                    if ($currency['isdefault'] == 1) {
                        $customer_currency = $currency;

                        break;
                    }
                }
            }

            if (is_array($customer_currency)) {
                $customer_currency = (object) $customer_currency;
            }

            $data['customer_currency'] = $customer_currency;

            
        }

        $data['bodyclass'] = 'customer-profile dynamic-create-groups';
        $data['vendor_categories'] = $this->purchase_model->get_vendor_category();
        $data['prefix_codes'] = $this->purchase_model->get_vendor_prefix_codes_arr();

        $data['title']     = $title;

        $this->load->view('vendors/vendor', $data);
    }

    /**
     * { setting }
     */
    public function setting(){
        purchase_token();
    	if (!is_admin() && !has_permission('purchase_settings', '', 'edit')) {
            access_denied('purchase');
        }
        purchase_init();
        $data['group'] = $this->input->get('group');
        $data['unit_tab'] = $this->input->get('tab');

        $data['title']                 = _l('setting');
       
		$this->db->where('module_name','warehouse');
        $module = $this->db->get(db_prefix().'modules')->row();
        $data['tab'][] = 'purchase_order_setting';
        $data['tab'][] = 'purchase_options';
        $data['tab'][] = 'units';
        $data['tab'][] = 'approval';
        $data['tab'][] = 'commodity_group';
        $data['tab'][] = 'sub_group';
        $data['tab'][] = 'vendor_category';
        $data['tab'][] = 'permissions';
        $data['tab'][] = 'order_return';
        $data['tab'][] = 'currency_rates';

        if($data['group'] == ''){
            $data['group'] = 'purchase_order_setting';
        }else if($data['group'] == 'units'){
            $data['unit_types'] = $this->purchase_model->get_unit_type();
        }

        if($data['group'] == 'currency_rates'){
            $this->load->model('currencies_model');
            $this->purchase_model->check_auto_create_currency_rate();

            $data['currencies'] = $this->currencies_model->get();
            if($data['unit_tab'] == ''){
                $data['unit_tab'] = 'general';
            }
        }


        $data['tabs']['view'] = 'includes/'.$data['group'];
        $data['commodity_group_types'] = $this->purchase_model->get_commodity_group_type();
        $data['sub_groups'] = $this->purchase_model->get_sub_group();
        $data['item_group'] = $this->purchase_model->get_item_group();
        $data['approval_setting'] = $this->purchase_model->get_approval_setting();
        $data['vendor_categories'] = $this->purchase_model->get_vendor_category();
        $data['staffs'] = $this->staff_model->get('', ['active' => 1]); 
        
        $this->load->view('manage_setting', $data);
    }

    /**
     * Terms - List all terms
     */
    public function terms(){
        $data['title'] = _l('pur_terms');
        $data['terms'] = $this->purchase_model->get_terms();
        $this->load->view('terms/manage', $data);
    }

    /**
     * Add or edit a term
     */
    public function term($id = ''){
        if($this->input->post()){
            $post_data = $this->input->post();
            $post_data['content'] = $this->input->post('content', false);
            if($id == ''){
                $success = $this->purchase_model->add_term($post_data);
                if($success){
                    set_alert('success', _l('added_successfully', _l('pur_term')));
                }
            } else {
                $success = $this->purchase_model->update_term($id, $post_data);
                if($success){
                    set_alert('success', _l('updated_successfully', _l('pur_term')));
                }
            }
            redirect(admin_url('purchase/terms'));
        }
        $data['title'] = $id == '' ? _l('add_new_term') : _l('edit_term');
        if($id != ''){
            $data['term'] = $this->purchase_model->get_term($id);
        }
        $this->load->view('terms/term', $data);
    }

    /**
     * Delete a term
     */
    public function delete_term($id){
        $success = $this->purchase_model->delete_term($id);
        if($success){
            set_alert('success', _l('deleted', _l('pur_term')));
        }
        redirect(admin_url('purchase/terms'));
    }

    /**
     * Get term content by ID (AJAX)
     */
    public function get_term_content($id = ''){
        if(empty($id)){
            echo json_encode(['content' => '']);
            die;
        }
        $term = $this->purchase_model->get_term($id);
        if($term){
            echo json_encode(['content' => $term->content]);
        } else {
            echo json_encode(['content' => '']);
        }
        die;
    }

    // ============================================================
    // Request Types CRUD
    // ============================================================

    public function request_types(){
        $data['title'] = _l('request_types');
        $data['request_types'] = $this->purchase_model->get_request_types();
        $this->load->view('request_types/manage', $data);
    }

    public function request_type($id = ''){
        if($this->input->post()){
            $post_data = $this->input->post();
            if($id == ''){
                $success = $this->purchase_model->add_request_type($post_data);
                if($success){
                    set_alert('success', _l('added_successfully', _l('request_type')));
                }
            } else {
                $success = $this->purchase_model->update_request_type($id, $post_data);
                if($success){
                    set_alert('success', _l('updated_successfully', _l('request_type')));
                }
            }
            redirect(admin_url('purchase/request_types'));
        }
        $data['title'] = $id == '' ? _l('add_request_type') : _l('edit_request_type');
        if($id != ''){
            $data['request_type'] = $this->purchase_model->get_request_type($id);
        }
        $data['expense_accounts'] = $this->db->query('SELECT id, name, number, key_name FROM '.db_prefix().'acc_accounts WHERE active = 1 ORDER BY number ASC, name ASC')->result_array();
        $this->load->view('request_types/form', $data);
    }

    public function delete_request_type($id){
        $success = $this->purchase_model->delete_request_type($id);
        if($success){
            set_alert('success', _l('deleted', _l('request_type')));
        }
        redirect(admin_url('purchase/request_types'));
    }

    /**
     * Get ledger account by request type ID (AJAX)
     */
    public function get_ledger_by_request_type($id = ''){
        if(empty($id)){
            $expense_accounts = $this->db->query('SELECT id, name, number, key_name FROM '.db_prefix().'acc_accounts WHERE account_type_id IN (14, 15) AND active = 1 ORDER BY number ASC, name ASC')->result_array();
            echo json_encode(['ledger_account' => '', 'type_code' => '', 'accounts' => $expense_accounts]);
            die;
        }
        $rt = $this->purchase_model->get_request_type($id);
        
        if($rt && !empty($rt->ledger_account)){
            // Ledger mapped - return only the mapped account
            $this->db->where('id', $rt->ledger_account);
            $accounts = $this->db->get(db_prefix().'acc_accounts')->result_array();
            echo json_encode([
                'ledger_account' => $rt->ledger_account,
                'type_code' => $rt->type_code,
                'accounts' => $accounts,
            ]);
        } else {
            // No ledger mapped - return expense accounts only and clear selection
            $expense_accounts = $this->db->query('SELECT id, name, number, key_name FROM '.db_prefix().'acc_accounts WHERE account_type_id IN (14, 15) AND active = 1 ORDER BY number ASC, name ASC')->result_array();
            echo json_encode([
                'ledger_account' => '',
                'type_code' => $rt ? $rt->type_code : '',
                'accounts' => $expense_accounts,
            ]);
        }
        die;
    }

    /**
     * Get account label by ID (AJAX helper)
     */
    public function get_account_label($id = ''){
        if(empty($id)){
            echo json_encode(['label' => '']);
            die;
        }
        $this->db->where('id', $id);
        $acc = $this->db->get(db_prefix().'acc_accounts')->row();
        $label = '';
        if($acc){
            if(!empty($acc->number)) $label .= $acc->number . ' - ';
            if(!empty($acc->name)) $label .= $acc->name;
            elseif(!empty($acc->key_name)) $label .= _l($acc->key_name);
            else $label .= 'Account #' . $acc->id;
        }
        echo json_encode(['label' => $label]);
        die;
    }

    /**
     * Get all items for PR dropdown (non-asset types)
     */
    public function get_all_items_for_pr($type_id = ''){
        if(is_numeric($type_id) && $type_id > 0){
            // Filter items by commodity_type matching the purchase type (includes both inventory and purchase items)
            $this->db->where('commodity_type', $type_id);
            $this->db->order_by('description', 'ASC');
            $all_items = $this->db->get(db_prefix().'items')->result_array();
            
            $html = '<option value=""></option>';
            foreach($all_items as $item){
                $subtext = strip_tags(mb_substr($item['long_description'] ?? '', 0, 200)).'...';
                $html .= '<option value="'.$item['id'].'" data-subtext="'.htmlspecialchars($subtext).'">('.app_format_number($item['purchase_price']).') '.$item['description'].'</option>';
            }
        } else {
            // No type filter - show all items
            $this->db->order_by('description', 'ASC');
            $all_items = $this->db->get(db_prefix().'items')->result_array();
            $html = '<option value=""></option>';
            foreach($all_items as $item){
                $subtext = strip_tags(mb_substr($item['long_description'] ?? '', 0, 200)).'...';
                $html .= '<option value="'.$item['id'].'" data-subtext="'.htmlspecialchars($subtext).'">('.app_format_number($item['purchase_price']).') '.$item['description'].'</option>';
            }
        }
        echo json_encode(['html' => $html]);
        die;
    }

    /**
     * Get asset_uid from PO detail by detail ID
     */
    public function get_po_detail_asset_uid($detail_id = ''){
        if(!is_numeric($detail_id)){
            echo json_encode(['asset_uid' => '']);
            die;
        }
        $this->db->select('asset_uid');
        $this->db->where('id', $detail_id);
        $row = $this->db->get(db_prefix().'pur_order_detail')->row();
        echo json_encode(['asset_uid' => ($row && $row->asset_uid) ? $row->asset_uid : '']);
        die;
    }

    /**
     * Get asset creation items for PR dropdown when type is Asset
     */
    public function get_asset_creation_items(){
        $this->db->order_by('id', 'desc');
        $assets = $this->db->get(db_prefix().'fe_asset_creation')->result_array();
        $html = '<option value=""></option>';
        foreach($assets as $a){
            $label = $a['asset_uid'] . ' - ' . $a['asset_title'];
            if($a['descriptions'] != '') $label .= ' (' . $a['descriptions'] . ')';
            $html .= '<option value="'.$a['id'].'" data-subtext="'.$a['code'].'">'.$label.'</option>';
        }
        echo json_encode(['html' => $html]);
        die;
    }

    /**
     * Get asset creation item by ID for PR preview
     */
    public function get_asset_creation_item_by_id($id = ''){
        if(!is_numeric($id)){
            echo json_encode([]);
            die;
        }
        $this->db->where('id', $id);
        $asset = $this->db->get(db_prefix().'fe_asset_creation')->row();
        if($asset){
            $description = $asset->asset_title;
            if($asset->descriptions != '') $description .= ' - ' . $asset->descriptions;
            if($asset->manufacturer_name != '') $description .= ' | ' . $asset->manufacturer_name;
            echo json_encode([
                'itemid' => $asset->id,
                'description' => $description,
                'qty' => $asset->qty,
                'asset_uid' => $asset->asset_uid,
                'code' => $asset->code,
            ]);
        } else {
            echo json_encode([]);
        }
        die;
    }

    /**
     * { assign vendor admins }
     *
     * @param      string  $id     The identifier
     * @return      redirect
     */
    public function assign_vendor_admins($id)
    {
        if (!has_permission('purchase_vendors', '', 'create') && !has_permission('purchase_vendors', '', 'edit')) {
            access_denied('vendors');
        }
        $success = $this->purchase_model->assign_vendor_admins($this->input->post(), $id);
        if ($success == true) {
            set_alert('success', _l('updated_successfully', _l('vendor')));
        }

        redirect(admin_url('purchase/vendor/' . $id . '?tab=vendor_admins'));
    }

    /**
     * { delete vendor }
     *
     * @param      <type>  $id     The identifier
     * @return      redirect
     */
   	public function delete_vendor($id){
   		if (!has_permission('purchase_vendors', '', 'delete')) {
            access_denied('vendors');
        }
        if (!$id) {
            redirect(admin_url('purchase/vendors'));
        }
        $response = $this->purchase_model->delete_vendor($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('customer_delete_transactions_warning', _l('invoices') . ', ' . _l('estimates') . ', ' . _l('credit_notes')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('client')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('client_lowercase')));
        }
        redirect(admin_url('purchase/vendors'));
   	}

    /**
     * { form contact }
     *
     * @param      <type>  $customer_id  The customer identifier
     * @param      string  $contact_id   The contact identifier
     */
   	public function form_contact($customer_id, $contact_id = '')
    {
        if (!has_permission('purchase_vendors', '', 'view') && !has_permission('purchase_vendors', '', 'view_own')) {
            if (!is_vendor_admin($customer_id)) {
                echo _l('access_denied');
                die;
            }
        }
        $data['customer_id'] = $customer_id;
        $data['contactid']   = $contact_id;
        if ($this->input->post()) {
            $data             = $this->input->post();
            $data['password'] = $this->input->post('password', false);

            unset($data['contactid']);
            if ($contact_id == '') {
                if (!has_permission('purchase_vendors', '', 'create')) {
                    if (!is_vendor_admin($customer_id)) {
                        header('HTTP/1.0 400 Bad error');
                        echo json_encode([
                            'success' => false,
                            'message' => _l('access_denied'),
                        ]);
                        die;
                    }
                }
                $id      = $this->purchase_model->add_contact($data, $customer_id);
                $message = '';
                $success = false;
                if ($id) {
                   
                    $success = true;
                    $message = _l('added_successfully', _l('contact'));
                }
                echo json_encode([
                    'success'             => $success,
                    'message'             => $message,
                    'has_primary_contact' => (total_rows(db_prefix().'pur_contacts', ['userid' => $customer_id, 'is_primary' => 1]) > 0 ? true : false),
                    'is_individual'       => is_empty_vendor_company($customer_id) && total_rows(db_prefix().'pur_contacts', ['userid' => $customer_id]) == 1,
                ]);
                die;
            }
            if (!has_permission('purchase_vendors', '', 'edit')) {
                if (!is_vendor_admin($customer_id)) {
                    header('HTTP/1.0 400 Bad error');
                    echo json_encode([
                            'success' => false,
                            'message' => _l('access_denied'),
                        ]);
                    die;
                }
            }
            $original_contact = $this->purchase_model->get_contact($contact_id);
            $success          = $this->purchase_model->update_contact($data, $contact_id);
            $message          = '';
            $proposal_warning = false;
            $original_email   = '';
            $updated          = false;
            if (is_array($success)) {
                if (isset($success['set_password_email_sent'])) {
                    $message = _l('set_password_email_sent_to_client');
                } elseif (isset($success['set_password_email_sent_and_profile_updated'])) {
                    $updated = true;
                    $message = _l('set_password_email_sent_to_client_and_profile_updated');
                }
            } else {
                if ($success == true) {
                    $updated = true;
                    $message = _l('updated_successfully', _l('contact'));
                }
            }
       
            echo json_encode([
                    'success'             => $success,
                    'proposal_warning'    => $proposal_warning,
                    'message'             => $message,
                    'original_email'      => $original_email,
                    'has_primary_contact' => (total_rows(db_prefix().'pur_contacts', ['userid' => $customer_id, 'is_primary' => 1]) > 0 ? true : false),
                ]);
            die;
        }
        if ($contact_id == '') {
            $title = _l('add_new', _l('contact_lowercase'));
        } else {
            $data['contact'] = $this->purchase_model->get_contact($contact_id);

            if (!$data['contact']) {
                header('HTTP/1.0 400 Bad error');
                echo json_encode([
                    'success' => false,
                    'message' => 'Contact Not Found',
                ]);
                die;
            }
            $title = $data['contact']->firstname . ' ' . $data['contact']->lastname;
        }

        
        $data['title']                = $title;
        $this->load->view('vendors/modals/contact', $data);
    }

    /**
     * { vendor contacts }
     *
     * @param      <type>  $client_id  The client identifier
     */
    public function vendor_contacts($client_id)
    {
        $this->app->get_table_data(module_views_path('purchase', 'vendors/table_contacts'), [
            'client_id' => $client_id,
        ]);
    }

    /**
     * Determines if contact email exists.
     */
    public function contact_email_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if the email is the same
                $userid = $this->input->post('userid');
                if ($userid != '') {
                    $this->db->where('id', $userid);
                    $_current_email = $this->db->get(db_prefix() . 'pur_contacts')->row();
                    if ($_current_email->email == $this->input->post('email')) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('email', $this->input->post('email'));
                $total_rows = $this->db->count_all_results(db_prefix() . 'pur_contacts');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * { delete vendor contact }
     *
     * @param      string  $customer_id  The customer identifier
     * @param      <type>  $id           The identifier
     * @return     redirect
     */
    public function delete_vendor_contact($customer_id, $id)
    {
        if (!has_permission('purchase_vendors', '', 'delete')) {
            if (!is_customer_admin($customer_id)) {
                access_denied('vendors');
            }
        }

        $this->purchase_model->delete_contact($id);
        
        redirect(admin_url('purchase/vendor/' . $customer_id . '?group=contacts'));
    }


    /**
     * { all contacts }
     * @return     view
     */
    public function all_contacts()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('purchase', 'vendors/table_all_contacts'));
        }

        if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') {
            $this->load->model('gdpr_model');
            $data['consent_purposes'] = $this->gdpr_model->get_consent_purposes();
        }

        $data['title'] = _l('customer_contacts');
        $this->load->view('vendors/all_contacts', $data);
    }

    /**
     * { purchase request }
     * @return     view
     */
    public function purchase_request(){
        purchase_token();
        $this->load->model('departments_model');
        purchase_init();
        $data['title'] = _l('purchase_request');
        $data['vendors'] = [];
        $data['departments'] = $this->departments_model->get();
        $data['vendor_contacts'] = [];
        
        $this->load->view('purchase_request/manage', $data);
    }

    /**
     * { add update purchase request }
     *
     * @param      string  $id     The identifier
     * @return    redirect, view
     */
    public function pur_request($id = ''){
    	$this->load->model('departments_model');
        $this->load->model('staff_model');
        $this->load->model('projects_model');
        $this->load->model('currencies_model');
    	if($id == ''){
    		
    		if($this->input->post()){
    			$add_data = $this->input->post();
                // Server-side: ledger_account and cost_center are mandatory
                if(empty($add_data['ledger_account'])){
                    set_alert('danger', _l('ledger_account') . ' ' . _l('is_required'));
                    redirect(admin_url('purchase/pur_request'));
                }
                if(empty($add_data['cost_center'])){
                    set_alert('danger', _l('cost_center') . ' ' . _l('is_required'));
                    redirect(admin_url('purchase/pur_request'));
                }
    			$id = $this->purchase_model->add_pur_request($add_data);
    			if($id){
    				set_alert('success',_l('added_pur_request'));
    			}
    			redirect(admin_url('purchase/purchase_request'));
    		}

    		$data['title'] = _l('add_new');
    	}else{
    		if($this->input->post()){
    			$edit_data = $this->input->post();
                // Server-side: ledger_account and cost_center are mandatory
                if(empty($edit_data['ledger_account'])){
                    set_alert('danger', _l('ledger_account') . ' ' . _l('is_required'));
                    redirect(admin_url('purchase/pur_request/' . $id));
                }
                if(empty($edit_data['cost_center'])){
                    set_alert('danger', _l('cost_center') . ' ' . _l('is_required'));
                    redirect(admin_url('purchase/pur_request/' . $id));
                }
    			$success = $this->purchase_model->update_pur_request($edit_data,$id);
    			if($success == true){
    				set_alert('success',_l('updated_pur_request'));
    			}
    			redirect(admin_url('purchase/purchase_request'));
    		}

    		$data['pur_request_detail'] = json_encode($this->purchase_model->get_pur_request_detail($id));
    		$data['pur_request'] = $this->purchase_model->get_purchase_request($id);
            $data['taxes_data'] = $this->purchase_model->get_html_tax_pur_request($id);
    		$data['title'] = _l('edit');
    	}

        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $purchase_request_row_template = $this->purchase_model->create_purchase_request_row_template();

        if($id != ''){
            $data['pur_request_detail'] = $this->purchase_model->get_pur_request_detail($id);
            $currency_rate = 1;
            if($data['pur_request']->currency != 0 && $data['pur_request']->currency_rate != null){
                $currency_rate = $data['pur_request']->currency_rate;
            }

            $to_currency = $data['base_currency']->name;
            if($data['pur_request']->currency != 0 && $data['pur_request']->to_currency != null) {
                $to_currency = $data['pur_request']->to_currency;
            }

            if (count($data['pur_request_detail']) > 0) { 
                $index_request = 0;
                foreach ($data['pur_request_detail'] as $request_detail) {
                    $index_request++;
                    $unit_name = pur_get_unit_name($request_detail['unit_id']);
                    $taxname = '';
                    $item_text = $request_detail['item_text'];

                    if(strlen($item_text) == 0){
                        $item_text = pur_get_item_variatiom($request_detail['item_code']);
                    }

                    $purchase_request_row_template .= $this->purchase_model->create_purchase_request_row_template('items[' . $index_request . ']', $request_detail['item_code'], $item_text, $request_detail['unit_price'], $request_detail['quantity'], $unit_name, $request_detail['unit_id'], $request_detail['into_money'], $request_detail['prd_id'], $request_detail['tax_value'], $request_detail['total'], $request_detail['tax_name'], $request_detail['tax_rate'], $request_detail['tax'], true, $currency_rate, $to_currency, (isset($request_detail['description']) ? $request_detail['description'] : ''));
                }
            }
        }

        $data['currencies'] = $this->currencies_model->get();

        $data['vendors'] = $this->purchase_model->get_vendor();
        $data['purchase_request_row_template'] = $purchase_request_row_template;
        $data['invoices'] = $this->purchase_model->get_invoice_for_pr();
        $data['salse_estimates'] = $this->purchase_model->get_sale_estimate_for_pr();
        
        $data['taxes'] = $this->purchase_model->get_taxes();
        $data['projects'] = $this->projects_model->get();
        $data['staffs'] = $this->staff_model->get();
    	$data['departments'] = $this->departments_model->get();
    	$data['units'] = $this->purchase_model->get_units();

        // Old script  $data['items'] = $this->purchase_model->get_items();
    	$data['ajaxItems'] = false;

        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->purchase_model->pur_get_grouped('can_be_purchased');
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
    	
        $this->load->view('purchase_request/pur_request', $data);
    }

    /**
     * { view pur request }
     *
     * @param      <type>  $id     The identifier
     * @return view
     */
    public function view_pur_request($id){
        if(!has_permission('purchase_request', '', 'view') && !has_permission('purchase_request', '', 'view_own')){
            access_denied('purchase');
        }

    	$this->load->model('departments_model');
        $this->load->model('currencies_model');

        $send_mail_approve = $this->session->userdata("send_mail_approve");
        if((isset($send_mail_approve)) && $send_mail_approve != ''){
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata("send_mail_approve");
        }
        $data['pur_request'] = $this->purchase_model->get_purchase_request($id);

        if(has_permission('purchase_request', '', 'view_own') && !is_admin()){
            $staffid = get_staff_user_id();
            $in_vendor = false;

            if( $data['pur_request']->send_to_vendors != null &&  $data['pur_request']->send_to_vendors != ''){
                $send_to_vendors_ids = explode(',', $data['pur_request']->send_to_vendors);

                $list_vendor = get_vendor_admin_list($staffid);
                foreach($list_vendor as $vendor_id){
                    if(in_array($vendor_id, $send_to_vendors_ids)){
                        $in_vendor = true;
                    }
                }

            }

            $approve_access = total_rows(db_prefix().'pur_approval_details', ['staffid' => $staffid, 'rel_type' => 'pur_request', 'rel_id' => $id]);

            if( $data['pur_request']->requester != $staffid && $in_vendor == false && $approve_access == 0){
                access_denied('purchase');
            }
        }

        if(!$data['pur_request']){
            show_404();
        }

    	$data['pur_request_detail'] = $this->purchase_model->get_pur_request_detail($id);
		$data['title'] = $data['pur_request']->pur_rq_name;
		$data['departments'] = $this->departments_model->get();
    	$data['units'] = $this->purchase_model->get_units();
    	$data['items'] = $this->purchase_model->get_items();
    	$data['taxes_data'] = $this->purchase_model->get_html_tax_pur_request($id);
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        // $id passed explicitly so the request's own department chain is matched.
        // Without it this fell through to the arbitrary "any setting" branch, which is
        // what made the same record read as approved on one host and still awaiting
        // approval on another.
        $data['check_appr'] = $this->purchase_model->get_approve_setting('pur_request', '', $id);

        $data['appr_setting'] = $this->purchase_model->get_approve_setting_new('pur_request');
        $data['get_staff_sign'] = $this->purchase_model->get_staff_sign($id,'pur_request');

        $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'pur_request');

        if(isset($data['appr_setting']->approval_type) && $data['appr_setting']->approval_type == 1){

            $data['check_approve_status'] = $this->purchase_model->check_approval_details_new($id,'pur_request');
        }else{

            $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'pur_request');
        }



        $data['list_approve_status'] = $this->purchase_model->get_list_approval_details($id,'pur_request');
        $data['taxes'] = $this->purchase_model->get_taxes();
        $data['pur_request_attachments'] = $this->purchase_model->get_purchase_request_attachments($id);

        $this->load->view('purchase_request/view_pur_request', $data);

    }

    /**
     * { approval setting }
     * @return redirect
     */
    public function approval_setting()
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            if ($data['approval_setting_id'] == '') {
                $message = '';
                $success = $this->purchase_model->add_approval_setting($data);
                if ($success) {
                    $message = _l('added_successfully', _l('approval_setting'));
                }
                set_alert('success', $message);
                redirect(admin_url('purchase/setting?group=approval'));
            } else {
                $message = '';
                $id = $data['approval_setting_id'];
                $success = $this->purchase_model->edit_approval_setting($id, $data);
                if ($success) {
                    $message = _l('updated_successfully', _l('approval_setting'));
                }
                set_alert('success', $message);
                redirect(admin_url('purchase/setting?group=approval'));
            }
        }
    }

    /**
     * { delete approval setting }
     *
     * @param      <type>  $id     The identifier
     * @return redirect
     */
    public function delete_approval_setting($id)
    {
        if (!$id) {
            redirect(admin_url('purchase/setting?group=approval'));
        }
        $response = $this->purchase_model->delete_approval_setting($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('approval_setting')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('approval_setting')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('approval_setting')));
        }
        redirect(admin_url('purchase/setting?group=approval'));
    }

    /**
     * { items change event}
     *
     * @param      <type>  $val    The value
     * @return      json
     */
    public function items_change($val){
        purchase_token();
        $value = $this->purchase_model->items_change($val);
        purchase_init();
        echo json_encode([
            'value' => $value
        ]);
        die;
    }

    /**
     * { table pur request }
     */
    public function table_pur_request(){
    	 $this->app->get_table_data(module_views_path('purchase', 'purchase_request/table_pur_request'));
    }

    /**
     * { delete pur request }
     *
     * @param      <type>  $id     The identifier
     * @return     redirect
     */
    /**
     * AJAX: Check if a PR has any active (non-deleted) POs linked to it
     */
    public function check_pr_po($id){
        if(!is_numeric($id)){
            echo json_encode(['can_delete' => true, 'message' => '', 'po_count' => 0]);
            die;
        }

        // Block if already soft-deleted
        $this->db->select('is_deleted, pur_rq_code');
        $this->db->where('id', $id);
        $pr = $this->db->get(db_prefix().'pur_request')->row();
        if($pr && $pr->is_deleted == 1){
            echo json_encode([
                'can_delete' => false,
                'po_count'   => 0,
                'message'    => 'Purchase Request ' . $pr->pur_rq_code . ' has already been deleted.',
            ]);
            die;
        }

        // Find active POs that reference this PR (exclude soft-deleted)
        $this->db->where('pur_request', $id);
        $this->db->where('is_deleted', 0);
        $pos = $this->db->get(db_prefix().'pur_orders')->result_array();

        if(!empty($pos)){
            $po_codes = array_column($pos, 'pur_order_code');
            $count    = count($pos);
            echo json_encode([
                'can_delete' => false,
                'po_count'   => $count,
                'message'    => 'Cannot delete this Purchase Request. It has ' . $count . ' linked Purchase Order(s): '
                              . implode(', ', $po_codes) . '. Please delete the PO(s) first.',
            ]);
        } else {
            echo json_encode(['can_delete' => true, 'message' => '', 'po_count' => 0]);
        }
        die;
    }

    public function delete_pur_request($id){
    	if (!$id) {
            redirect(admin_url('purchase/purchase_request'));
        }

        // Guard: block if already soft-deleted
        $this->db->select('is_deleted, pur_rq_code');
        $this->db->where('id', $id);
        $pr_check = $this->db->get(db_prefix().'pur_request')->row();
        if($pr_check && $pr_check->is_deleted == 1){
            set_alert('warning', 'Purchase Request ' . $pr_check->pur_rq_code . ' has already been deleted.');
            redirect(admin_url('purchase/purchase_request'));
        }

        // Server-side guard: block if active POs exist for this PR
        $this->db->where('pur_request', $id);
        $this->db->where('is_deleted', 0);
        $active_po_count = $this->db->count_all_results(db_prefix().'pur_orders');
        if($active_po_count > 0){
            set_alert('danger', 'Cannot delete Purchase Request. It has ' . $active_po_count . ' linked Purchase Order(s). Please delete the PO(s) first.');
            redirect(admin_url('purchase/purchase_request'));
        }

        $response = $this->purchase_model->delete_pur_request($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('purchase_request')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('purchase_request')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('purchase_request')));
        }
        redirect(admin_url('purchase/purchase_request'));
    }

    /**
     * { change status pur request }
     *
     * @param      <type>  $status  The status
     * @param      <type>  $id      The identifier
     * @return     json
     */
    public function change_status_pur_request($status,$id){
    	$change = $this->purchase_model->change_status_pur_request($status,$id);
        if($change == true){
            
            $message = _l('change_status_pur_request').' '._l('successfully');
            echo json_encode([
                'result' => $message,
            ]);
        }else{
            $message = _l('change_status_pur_request').' '._l('fail');
            echo json_encode([
                'result' => $message,
            ]);
        }
    }

    /**
     * { quotations }
     *
     * @param      string  $id     The identifier
     * @return     view
     */
    public function quotations($id = ''){
        purchase_token();
    	if (!has_permission('purchase_quotations', '', 'view') && !is_admin() && !has_permission('purchase_quotations', '', 'view_own')) {
            access_denied('quotations');
        }
        purchase_init();
            // Pipeline was initiated but user click from home page and need to show table only to filter
        if ($this->input->get('status') || $this->input->get('filter') && $isPipeline) {
            $this->pipeline(0, true);
        }

        $data['estimateid']            = $id;
        $data['pur_request'] = $this->purchase_model->get_purchase_request();
        $data['vendors'] = [];
        $data['title']                 = _l('estimates');
        $data['bodyclass']             = 'estimates-total-manual';
        
        $this->load->view('quotations/manage', $data);
    
    }

    /**
     * { function_description }
     *
     * @param      string  $id     The identifier
     * @return     redirect
     */
    public function estimate($id = '')
    {
        $this->load->model('currencies_model');
        if ($this->input->post()) {
            $estimate_data = $this->input->post();
            $estimate_data['terms'] = $this->input->post('terms', false);
            if ($id == '') {
                if (!has_permission('purchase_quotations', '', 'create')) {
                    access_denied('quotations');
                }
                $id = $this->purchase_model->add_estimate($estimate_data);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('estimate')));
                    
                    redirect(admin_url('purchase/quotations/' . $id));
                    
                }
            } else {
                if (!has_permission('purchase_quotations', '', 'edit')) {
                    access_denied('quotations');
                }
                $success = $this->purchase_model->update_estimate($estimate_data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('estimate')));
                }
                redirect(admin_url('purchase/quotations/' . $id));
                
            }
        }
        if ($id == '') {
            $title = _l('create_new_estimate');
        } else {
            $estimate = $this->purchase_model->get_estimate($id);

            $data['tax_data'] = $this->purchase_model->get_html_tax_pur_estimate($id);
            
            $data['estimate'] = $estimate;
            $data['edit']     = true;
            $title            = _l('edit', _l('estimate_lowercase'));
        }
        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $pur_quotation_row_template = $this->purchase_model->create_quotation_row_template();

        if($id != ''){
            $data['estimate_detail'] = $this->purchase_model->get_pur_estimate_detail($id);
            $currency_rate = 1;
            if($data['estimate']->currency != 0 && $data['estimate']->currency_rate != null){
                $currency_rate = $data['estimate']->currency_rate;
            }

            $to_currency = $data['base_currency']->name;
            if($data['estimate']->currency != 0 && $data['estimate']->to_currency != null) {
                $to_currency = $data['estimate']->to_currency;
            }


            if (count($data['estimate_detail']) > 0) { 
                $index_quote = 0;
                foreach ($data['estimate_detail'] as $quote_detail) { 
                    $index_quote++;
                    $unit_name = pur_get_unit_name($quote_detail['unit_id']);
                    $taxname = $quote_detail['tax_name'];
                    $item_name = $quote_detail['item_name'];

                    if(strlen($item_name) == 0){
                        $item_name = pur_get_item_variatiom($quote_detail['item_code']);
                    }

                    $pur_quotation_row_template .= $this->purchase_model->create_quotation_row_template('items[' . $index_quote . ']',  $item_name, $quote_detail['quantity'], $unit_name, $quote_detail['unit_price'], $taxname, $quote_detail['item_code'], $quote_detail['unit_id'], $quote_detail['tax_rate'],  $quote_detail['total_money'], $quote_detail['discount_%'], $quote_detail['discount_money'], $quote_detail['total'], $quote_detail['into_money'], $quote_detail['tax'], $quote_detail['tax_value'], $quote_detail['id'], true, $currency_rate, $to_currency);
                }
            }

        }

        $data['pur_quotation_row_template'] = $pur_quotation_row_template;

        $this->load->model('taxes_model');
        $data['taxes'] = $this->purchase_model->get_taxes();
        
        $data['currencies'] = $this->currencies_model->get();

        $this->load->model('invoice_items_model');

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->purchase_model->pur_get_grouped('can_be_purchased');
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['staff']             = $this->staff_model->get('', ['active' => 1]);
        $data['vendors'] = [];
        $data['ven'] = $this->input->get('vendor');
        $data['pur_request'] = $this->purchase_model->get_pur_request_by_status(2);
        $data['units'] = $this->purchase_model->get_units();
       
        $data['title']             = $title;
        $this->load->view('quotations/estimate', $data);
    }

    /**
     * { validate estimate number }
     */
    public function validate_estimate_number()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        // if (total_rows(db_prefix().'pur_estimates', [
        //     'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
        //     'number' => $number,
        // ]) > 0) {
        //     echo 'false';
        // } else {
        //     echo 'true';
        // }
        echo 'true';
    }

    /**
     * { table estimates }
     */
    public function table_estimates(){
        $this->app->get_table_data(module_views_path('purchase', 'quotations/table_estimates'));
    }

    /**
     * Gets the estimate data ajax.
     *
     * @param      <type>   $id         The identifier
     * @param      boolean  $to_return  To return
     *
     * @return     <type>   view.
     */
    public function get_estimate_data_ajax($id, $to_return = false)
    {
        if (!has_permission('purchase_quotations', '', 'view') && !is_admin() && !has_permission('purchase_quotations', '', 'view_own')) {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die('No estimate found');
        }

        $estimate = $this->purchase_model->get_estimate($id);

        if(has_permission('purchase_quotations', '', 'view_own') && !is_admin()){
            $staffid = get_staff_user_id();

            $approve_access = total_rows(db_prefix().'pur_approval_details', ['staffid' => $staffid, 'rel_type' => 'pur_quotation', 'rel_id' => $id]);

            if($estimate->buyer != $staffid && $estimate->addedfrom != $staffid && !is_vendor_admin($estimate->vendor->userid) && $approve_access == 0){
                echo _l('access_denied');
                die;
            }
        }

        $estimate->date       = _d($estimate->date);
        $estimate->expirydate = _d($estimate->expirydate);
    

        if ($estimate->sent == 0) {
            $template_name = 'estimate_send_to_customer';
        } else {
            $template_name = 'estimate_send_to_customer_already_sent';
        }

        $data['pur_estimate_attachments'] = $this->purchase_model->get_purchase_estimate_attachments($id);
        $data['estimate_detail'] = $this->purchase_model->get_pur_estimate_detail($id);
        $data['estimate']          = $estimate;
        $data['members']           = $this->staff_model->get('', ['active' => 1]);
        $data['vendor_contacts'] = $this->purchase_model->get_contacts($estimate->vendor->userid);
        $send_mail_approve = $this->session->userdata("send_mail_approve");
        if((isset($send_mail_approve)) && $send_mail_approve != ''){
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata("send_mail_approve");
        }
        $data['check_appr'] = $this->purchase_model->get_approve_setting('pur_quotation');
        $data['get_staff_sign'] = $this->purchase_model->get_staff_sign($id,'pur_quotation');
        $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'pur_quotation');

        $data['appr_setting'] = $this->purchase_model->get_approve_setting_new('pur_quotation');
        if(isset($data['appr_setting']->approval_type) && $data['appr_setting']->approval_type == 1){

            $data['check_approve_status'] = $this->purchase_model->check_approval_details_new($id,'pur_quotation');
        }else{

            $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'pur_quotation');
        }


        $data['list_approve_status'] = $this->purchase_model->get_list_approval_details($id,'pur_quotation');
        $data['tax_data'] = $this->purchase_model->get_html_tax_pur_estimate($id);
        
        if ($to_return == false) {
            $this->load->view('quotations/estimate_preview_template', $data);
        } else {
            return $this->load->view('quotations/estimate_preview_template', $data, true);
        }
    }

    /**
     * { delete estimate }
     *
     * @param      <type>  $id     The identifier
     * @return     redirect
     */
    public function delete_estimate($id)
    {
        if (!has_permission('purchase_quotations', '', 'delete')) {
            access_denied('estimates');
        }
        if (!$id) {
            redirect(admin_url('purchase/quotations'));
        }
        $success = $this->purchase_model->delete_estimate($id);
        if (is_array($success)) {
            set_alert('warning', _l('is_invoiced_estimate_delete_error'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('estimate')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('estimate_lowercase')));
        }
        redirect(admin_url('purchase/quotations'));
    }

    /**
     * { tax change event }
     *
     * @param      <type>  $tax    The tax
     * @return   json
     */
    public function tax_change($tax){
        $this->load->model('taxes_model');

        $taxes = explode('%7C', $tax);
        $total_tax = $this->purchase_model->get_total_tax($taxes);
        $tax_arr = [];
        foreach($taxes as $t){
            
            $tax_if = $this->taxes_model->get($t);
            if($tax_if){
                $tax_arr[$tax_if->id] = $tax_if->taxrate;
            }
            
        }
        
        echo json_encode([
            'total_tax' => $total_tax,
            'taxes' => $tax_arr
        ]);
    }

    /**
     * { coppy pur request }
     *
     * @param      <type>  $pur_request  The purchase request id
     * @return json
     */
    public function coppy_pur_request($pur_request){
        $this->load->model('currencies_model');

        $pur_request_detail = $this->purchase_model->get_pur_request_detail_in_estimate($pur_request);
        $purchase_request = $this->purchase_model->get_purchase_request($pur_request);

        $base_currency = $this->currencies_model->get_base_currency();
        $taxes = [];
        $tax_val = [];
        $tax_name = [];
        $subtotal = 0;
        $total = 0;
        $data_rs = [];
        $tax_html = '';
        
        if(count($pur_request_detail) > 0){
            foreach($pur_request_detail as $key => $item){
                $subtotal += $item['into_money'];
                $total += $item['total'];
            }
        }

        $list_item = $this->purchase_model->create_quotation_row_template();

        $currency_rate = 1;
        $to_currency = $base_currency->id;
        if($purchase_request->currency != 0){
            $to_currency = $purchase_request->currency;
            if($purchase_request->currency_rate != null && (float)$purchase_request->currency_rate > 0){
                $currency_rate = $purchase_request->currency_rate;
            }
        }

        if(count($pur_request_detail) > 0){
            $index_quote = 0;
            foreach($pur_request_detail as $key => $item){
                $index_quote++;
                $unit_name = pur_get_unit_name($item['unit_id']);
                $taxname = $item['tax_name'];
                $item_name = $item['item_text'];

                if(strlen($item_name) == 0){
                    $item_name = pur_get_item_variatiom($item['item_code']);
                }

                $list_item .= $this->purchase_model->create_quotation_row_template('newitems[' . $index_quote . ']',  $item_name, $item['quantity'], $unit_name, $item['unit_price'], $taxname, $item['item_code'], $item['unit_id'], $item['tax_rate'],  $item['total'], '', '', $item['total'], $item['into_money'], $item['tax'], $item['tax_value'], $index_quote, true, $currency_rate, $to_currency);
            }
        }
        

        $taxes_data = $this->purchase_model->get_html_tax_pur_request($pur_request);
        $tax_html = $taxes_data['html'];

        echo json_encode([
            'result' => $pur_request_detail,
            'subtotal' => app_format_money(round($subtotal,2),''),
            'total' => app_format_money(round($total, 2),''),
            'tax_html' => $tax_html,
            'taxes' => $taxes,
            'list_item' => $list_item,
            'currency' => $to_currency,
            'currency_rate' => $currency_rate,
            'cost_center' => isset($purchase_request->cost_center) ? $purchase_request->cost_center : '',
            'ledger_account' => isset($purchase_request->ledger_account) ? $purchase_request->ledger_account : '',
            'department' => isset($purchase_request->department) ? $purchase_request->department : '',
            'project' => isset($purchase_request->project) ? $purchase_request->project : '',
        ]);
    }

    /**
     * { coppy pur request }
     *
     * @param      <type>  $pur_request  The purchase request id
     * @return json
     */
    public function coppy_pur_request_for_po($pur_request, $vendor = ''){

        $this->load->model('currencies_model');

        $pur_request_detail = $this->purchase_model->get_pur_request_detail_in_po($pur_request);
        $purchase_request = $this->purchase_model->get_purchase_request($pur_request);

        $base_currency = $this->currencies_model->get_base_currency();
        $taxes = [];
        $tax_val = [];
        $tax_name = [];
        $subtotal = 0;
        $total = 0;
        $data_rs = [];
        $tax_html = '';
        $estimate_html = '';
        $type = '';

        $estimate_html .= $this->purchase_model->get_estimate_html_by_pr_vendor($pur_request, $vendor);
        
        if(count($pur_request_detail) > 0){
            foreach($pur_request_detail as $key => $item){
                $subtotal += $item['into_money'];
                $total += $item['total'];
            }
        }

        $list_item = $this->purchase_model->create_purchase_order_row_template();

        $currency_rate = 1;
        $to_currency = $base_currency->id;
        if($purchase_request->currency != 0){
            // Always carry forward the currency from PR
            $to_currency = $purchase_request->currency;
            // Only use the rate if it's valid (> 0), otherwise keep rate as 1
            if($purchase_request->currency_rate != null && (float)$purchase_request->currency_rate > 0){
                $currency_rate = $purchase_request->currency_rate;
            }
        }

        if(isset($purchase_request->type)){
            $type = $purchase_request->type;
        }


        if(count($pur_request_detail) > 0){
            $index_quote = 0;
            foreach($pur_request_detail as $key => $item){
                $index_quote++;
                $unit_name = pur_get_unit_name($item['unit_id']);
                $taxname = $item['tax_name'];
                $item_name = $item['item_text'];

                if(strlen($item_name) == 0){
                    $item_name = pur_get_item_variatiom($item['item_code']);
                }

                $list_item .= $this->purchase_model->create_purchase_order_row_template('newitems[' . $index_quote . ']',  $item_name, (isset($item['description']) ? $item['description'] : ''), $item['quantity'], $unit_name, $item['unit_price'], $taxname, $item['item_code'], $item['unit_id'], $item['tax_rate'],  $item['total'], '', '', $item['total'], $item['into_money'], $item['tax'], $item['tax_value'], $index_quote, true, $currency_rate, $to_currency);
            }
        }

        $taxes_data = $this->purchase_model->get_html_tax_pur_request($pur_request);
        $tax_html = $taxes_data['html'];

        // Get vendor payment terms
        $vendor_payment_terms = '';
        if(!empty($vendor) && is_numeric($vendor)){
            $this->db->select('payment_terms');
            $this->db->where('userid', $vendor);
            $v_row = $this->db->get(db_prefix().'pur_vendor')->row();
            if($v_row && !empty($v_row->payment_terms)){
                $vendor_payment_terms = $v_row->payment_terms;
            }
        }

        echo json_encode([
            'result' => $pur_request_detail,
            'subtotal' => app_format_money(round($subtotal,2),''),
            'total' => app_format_money(round($total, 2),''),
            'tax_html' => $tax_html,
            'taxes' => $taxes,
            'list_item' => $list_item,
            'currency' => $to_currency,
            'currency_rate' => $currency_rate,
            'estimate_html' => $estimate_html,
            'type' => $type,
            'cost_center' => isset($purchase_request->cost_center) ? $purchase_request->cost_center : '',
            'ledger_account' => isset($purchase_request->ledger_account) ? $purchase_request->ledger_account : '',
            'department' => isset($purchase_request->department) ? $purchase_request->department : '',
            'project' => isset($purchase_request->project) ? $purchase_request->project : '',
            'payment_terms' => $vendor_payment_terms,
            'vendornote' => isset($purchase_request->rq_description) ? $purchase_request->rq_description : '',
        ]);
    }

    /**
     * { coppy pur estimate }
     *
     * @param      <type>  $pur_estimate  The purchase estimate id
     * @return  json
     */
    public function coppy_pur_estimate($pur_estimate_id){
        $this->load->model('currencies_model');
        $pur_estimate_detail = $this->purchase_model->get_pur_estimate_detail_in_order($pur_estimate_id);
        $pur_estimate = $this->purchase_model->get_estimate($pur_estimate_id);

        $taxes = [];
        $tax_val = [];
        $tax_name = [];
        $subtotal = 0;
        $total = 0;
        $data_rs = [];
        $tax_html = '';
        
        if(count($pur_estimate_detail) > 0){
            foreach($pur_estimate_detail as $key => $item){
                $subtotal += $item['into_money'];
                $total += $item['total'];
            }
        }

        $base_currency = $this->currencies_model->get_base_currency();
        $list_item = $this->purchase_model->create_purchase_order_row_template();

        $currency_rate = 1;
        $to_currency = $base_currency->id;
        if($pur_estimate->currency != 0 && $pur_estimate->currency_rate != null){
            $currency_rate = $pur_estimate->currency_rate;
            $to_currency = $pur_estimate->currency;
        }

        if(count($pur_estimate_detail) > 0){
            $index = 0;
            foreach($pur_estimate_detail as $key => $item){
                $index++;
                $unit_name = pur_get_unit_name($item['unit_id']);
                $taxname = $item['tax_name'];
                $item_name = $item['item_name'];
                if(strlen($item_name) == 0){
                    $item_name = pur_get_item_variatiom($item['item_code']);
                }

                $list_item .= $this->purchase_model->create_purchase_order_row_template('newitems[' . $index . ']',  $item_name, (isset($item['description']) ? $item['description'] : ''), $item['quantity'], $unit_name, $item['unit_price'], $taxname, $item['item_code'], $item['unit_id'], $item['tax_rate'],  $item['total_money'], $item['discount_%'], $item['discount_money'], $item['total'], $item['into_money'], $item['tax'], $item['tax_value'], $index, true, $currency_rate, $to_currency);
            }
        }

        $taxes_data = $this->purchase_model->get_html_tax_pur_estimate($pur_estimate_id);
        $tax_html = $taxes_data['html'];

        echo json_encode([
            'result' => $pur_estimate_detail,
            'dc_percent' => $pur_estimate->discount_percent,
            'dc_total' => $pur_estimate->discount_total,
            'discount_type' => $pur_estimate->discount_type,
            'subtotal' => app_format_money(round($subtotal,2),''),
            'total' => app_format_money(round($total, 2),''),
            'tax_html' => $tax_html,
            'taxes' => $taxes,
            'list_item' => $list_item,
            'currency' => $to_currency,
            'currency_rate' => $currency_rate,
            'shipping_fee' => $pur_estimate->shipping_fee
        ]);
    }

    /**
     * { view purchase order }
     *
     * @param      <type>  $pur_order  The purchase order id
     * @return json
     */
    public function view_pur_order($pur_order){
        $pur_order_detail = $this->purchase_model->get_pur_order_detail($pur_order);
        $pur_order = $this->purchase_model->get_pur_order($pur_order);
        $base_currency = get_base_currency_pur();

        $total = $pur_order->total;
        $rate = 1;
        if($base_currency->id != $pur_order->currency && $pur_order->currency != 0){
            $po_currency = pur_get_currency_by_id($pur_order->currency);

            $rate = pur_get_currency_rate($po_currency->name, $base_currency->name);
        }

        $total = $total*$rate;

        echo json_encode([
            'total' => app_format_money($total,''),
            'vendor' => $pur_order->vendor,
            'buyer' => $pur_order->buyer,
            'project' => $pur_order->project,
            'department' => $pur_order->department,
        ]);
    }

    /**
     * { change status pur estimate }
     *
     * @param      <type>  $status  The status
     * @param      <type>  $id      The identifier
     * @return json
     */
    public function change_status_pur_estimate($status,$id){
        $change = $this->purchase_model->change_status_pur_estimate($status,$id);
        if($change == true){
            
            $message = _l('change_status_pur_estimate').' '._l('successfully');
            echo json_encode([
                'result' => $message,
            ]);
        }else{
            $message = _l('change_status_pur_estimate').' '._l('fail');
            echo json_encode([
                'result' => $message,
            ]);
        }
    }

    /**
     * { change status pur order }
     *
     * @param      <type>  $status  The status
     * @param      <type>  $id      The identifier
     * @return json
     */
    public function change_status_pur_order($status,$id){
        $change = $this->purchase_model->change_status_pur_order($status,$id);
        if($change == true){
            
            $message = _l('change_status_pur_order').' '._l('successfully');
            echo json_encode([
                'result' => $message,
            ]);
        }else{
            $message = _l('change_status_pur_order').' '._l('fail');
            echo json_encode([
                'result' => $message,
            ]);
        }
    }

    /**
     * { purchase order }
     *
     * @param      string  $id     The identifier
     * @return view
     */
    public function purchase_order($id = ''){
        purchase_token();
        if(!has_permission('purchase_orders', '', 'view') && !is_admin() && !has_permission('purchase_orders', '', 'view_own')){
            access_denied('purchase');
        }
        purchase_init();
        $this->load->model('expenses_model');
        $this->load->model('payment_modes_model');
        $this->load->model('taxes_model');
        $this->load->model('currencies_model');
        $this->load->model('departments_model');
        $this->load->model('projects_model');
        $this->load->model('clients_model');

        $data['pur_orderid']            = $id;
        $data['title'] = _l('purchase_order');

        $data['departments'] = $this->departments_model->get();
        $data['projects'] = $this->projects_model->get();
        $data['currency'] = $this->currencies_model->get_base_currency();
        $data['payment_modes'] = $this->payment_modes_model->get('', [], true);
        $data['currencies']         = $this->currencies_model->get();
        $data['taxes']              = $this->taxes_model->get();
        $data['vendors'] = [];
        $data['expense_categories'] = $this->expenses_model->get_category();
        $data['item_tags'] = $this->purchase_model->get_item_tag_filter();
        $data['customers'] = $this->clients_model->get();
        $data['pur_request'] = $this->purchase_model->get_pur_request_by_status(2);

        $data['projects'] = $this->projects_model->get();
        
        $this->load->view('purchase_order/manage', $data);
    }

    /**
     * Gets the pur order data ajax.
     *
     * @param      <type>   $id         The identifier
     * @param      boolean  $to_return  To return
     *
     * @return     view.
     */
    public function get_pur_order_data_ajax($id, $to_return = false)
    {
        if (!has_permission('purchase_orders', '', 'view') && !has_permission('purchase_orders', '', 'view_own')) {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die('No purchase order found');
        }

        $estimate = $this->purchase_model->get_pur_order($id);

        if(!isset($estimate->id)){
            die('No purchase order found');
        }

        if(has_permission('purchase_orders', '', 'view_own') && !is_admin()){
            $staffid = get_staff_user_id();

            $approve_access = total_rows(db_prefix().'pur_approval_details', ['staffid' => $staffid, 'rel_type' => 'pur_order', 'rel_id' => $id]);

            if($estimate->buyer != $staffid && $estimate->addedfrom != $staffid && !is_vendor_admin($estimate->vendor) && $approve_access == 0){
                echo _l('access_denied');
                die;
            }
        }

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);

        $data['payment'] = $this->purchase_model->get_inv_payment_purchase_order($id);
        $data['pur_order_attachments'] = $this->purchase_model->get_purchase_order_attachments($id);
        $data['estimate_detail'] = $this->purchase_model->get_pur_order_detail($id);
        $data['estimate']          = $estimate;
        $data['members']           = $this->staff_model->get('', ['active' => 1]);
        $data['vendor_contacts'] = $this->purchase_model->get_contacts($estimate->vendor);
        $send_mail_approve = $this->session->userdata("send_mail_approve");
        if((isset($send_mail_approve)) && $send_mail_approve != ''){
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata("send_mail_approve");
        }
        $data['invoices'] = $this->purchase_model->get_invoices_by_po($id);
        $data['appr_setting'] = $this->purchase_model->get_approve_setting_new('pur_order');
        // $id passed so the linked PR's department chain is matched - see view_pur_request
        $data['check_appr'] = $this->purchase_model->get_approve_setting('pur_order', '', $id);
        $data['get_staff_sign'] = $this->purchase_model->get_staff_sign($id,'pur_order');

        if(isset($data['appr_setting']->approval_type) && $data['appr_setting']->approval_type == 1){

            $data['check_approve_status'] = $this->purchase_model->check_approval_details_new($id,'pur_order');
        }else{

            $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'pur_order');
        }

        $data['list_approve_status'] = $this->purchase_model->get_list_approval_details($id,'pur_order');
        $data['tax_data'] = $this->purchase_model->get_html_tax_pur_order($id);

        $data['activity_logs'] = $this->purchase_model->get_activity_logs($id, 'purchase_order');

        if(get_status_modules_pur('warehouse')){
            $data['received_vouchers'] = $this->purchase_model->get_received_vouchers_by_po($id);
        }

        // Load GL accounts for payment ledger dropdown
        $this->db->select('id, CONCAT(number, " - ", name) as name');
        $this->db->where('active', 1);
        $this->db->order_by('number', 'ASC');
        $data['gl_accounts'] = $this->db->get(db_prefix().'acc_accounts')->result_array();
        
        if ($to_return == false) {
            $this->load->view('purchase_order/pur_order_preview', $data);
        } else {
            return $this->load->view('purchase_order/pur_order_preview', $data, true);
        }
    }

    /**
     * { purchase order form }
     *
     * @param      string  $id     The identifier
     * @return redirect, view
     */
    public function pur_order($id = ''){

        $data['mode'] = $this->input->get('mode');
        if ($this->input->post()) {
            $pur_order_data = $this->input->post();
            $pur_order_data['terms'] = $this->input->post('terms', false);
            $pur_order_data['vendornote'] = $this->input->post('vendornote', false);
            if ($id == '' || (isset($pur_order_data['is_copy']) && $pur_order_data['is_copy'] == 1)) {
                unset($pur_order_data['is_copy']);
                if (!has_permission('purchase_orders', '', 'create')) {
                    access_denied('purchase_order');
                }
                $po_id = $this->purchase_model->add_pur_order($pur_order_data);
                if (is_array($po_id) && isset($po_id['duplicate'])) {
                    set_alert('warning', _l('pur_order') . ' already created for this Purchase Request: ' . $po_id['po_number']);
                    redirect(admin_url('purchase/purchase_order/' . $po_id['po_id']));
                } elseif ($po_id) {
                    set_alert('success', _l('added_successfully', _l('pur_order')));
                    
                    redirect(admin_url('purchase/purchase_order/' . $po_id));
                    
                }
            } else if($id != '' && (!isset($pur_order_data['is_copy']))) {
                if (!has_permission('purchase_orders', '', 'edit')) {
                    access_denied('purchase_order');
                }
                $success = $this->purchase_model->update_pur_order($pur_order_data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('pur_order')));
                }
                redirect(admin_url('purchase/purchase_order/' . $id));
                
            }
        }

        $this->load->model('currencies_model');
        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $pur_order_row_template = $this->purchase_model->create_purchase_order_row_template();

        if ($id == '') {
            $title = _l('create_new_pur_order');
        } else {
            
            $data['pur_order_detail'] = $this->purchase_model->get_pur_order_detail($id);
            $data['pur_order'] = $this->purchase_model->get_pur_order($id);

            if($data['pur_order']->approve_status == 2 && $data['mode'] != 'copy'){
                access_denied('purchase_order');
            }

            if($data['mode'] == 'copy'){
                $data['pur_order']->pur_order_name = _l('pur_copy_from').' '.$data['pur_order']->pur_order_number;
            }

            $currency_rate = 1;
            if($data['pur_order']->currency != 0 && $data['pur_order']->currency_rate != null){
                $currency_rate = $data['pur_order']->currency_rate;
            }

            $to_currency = $data['base_currency']->name;
            if($data['pur_order']->currency != 0 && $data['pur_order']->to_currency != null) {
                $to_currency = $data['pur_order']->to_currency;
            }


            $data['tax_data'] = $this->purchase_model->get_html_tax_pur_order($id);
            $title = _l('pur_order_detail');

            if (count($data['pur_order_detail']) > 0) { 
                $index_order = 0;
                foreach ($data['pur_order_detail'] as $order_detail) { 
                    $index_order++;
                    $unit_name = pur_get_unit_name($order_detail['unit_id']);
                    $taxname = $order_detail['tax_name'];
                    $item_name = $order_detail['item_name'];

                    if(strlen($item_name) == 0){
                        $item_name = pur_get_item_variatiom($order_detail['item_code']);
                    }

                    $f_name = 'items[' . $index_order . ']';
                    if($data['mode'] == 'copy'){
                        $f_name = 'newitems[' . $index_order . ']';
                    }

                    $pur_order_row_template .= $this->purchase_model->create_purchase_order_row_template($f_name,  $item_name, $order_detail['description'], $order_detail['quantity'], $unit_name, $order_detail['unit_price'], $taxname, $order_detail['item_code'], $order_detail['unit_id'], $order_detail['tax_rate'],  $order_detail['total_money'], $order_detail['discount_%'], $order_detail['discount_money'], $order_detail['total'], $order_detail['into_money'], $order_detail['tax'], $order_detail['tax_value'], $order_detail['id'], true, $currency_rate, $to_currency);
                }
            }
        }
        $data['pur_order_row_template'] = $pur_order_row_template;

        
        $data['currencies'] = $this->currencies_model->get();

        $this->load->model('clients_model');
        $data['clients'] = $this->clients_model->get();

        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();

        $currency_id = $data['base_currency']->id;
        if(isset($data['pur_order'])){
            $currency_id = $data['pur_order']->currency;
        }

        $data['invoices'] = $this->purchase_model->get_invoice_for_pr('currency = '.$currency_id);

        $data['pur_request'] = $this->purchase_model->get_pur_request_by_status(2);

        // When editing, ensure the current PO's PR is in the list
        if($id != '' && isset($data['pur_order']) && !empty($data['pur_order']->pur_request)){
            $current_pr_in_list = false;
            foreach($data['pur_request'] as $pr){
                if($pr['id'] == $data['pur_order']->pur_request){
                    $current_pr_in_list = true;
                    break;
                }
            }
            if(!$current_pr_in_list){
                $current_pr = $this->purchase_model->get_purchase_request($data['pur_order']->pur_request);
                if($current_pr && !is_array($current_pr)){
                    array_unshift($data['pur_request'], (array)$current_pr);
                }
            }
        }

        $data['projects'] = $this->projects_model->get();
        $data['ven'] = $this->input->get('vendor');
        $data['taxes'] = $this->purchase_model->get_taxes();
        $data['units'] = $this->purchase_model->get_units();
        $data['staff']             = $this->staff_model->get('', ['active' => 1]);
        $data['vendors'] = $this->purchase_model->get_vendor();
        $data['estimates'] = $this->purchase_model->get_estimates_by_status(2);
        $data['units'] = $this->purchase_model->get_units();

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->purchase_model->pur_get_grouped('can_be_purchased');
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }

        $data['title'] = $title;

        $this->load->view('purchase_order/pur_order', $data);
    }

    /**
     * { delete pur order }
     *
     * @param      <type>  $id     The identifier
     * @return redirect
     */
    /**
     * AJAX: Check if a PO has any active (non-deleted) GRNs linked to it
     * Returns JSON: can_delete, message, grn_count
     */
    public function check_po_grn($id){
        if(!is_numeric($id)){
            echo json_encode(['can_delete' => true, 'message' => '', 'grn_count' => 0]);
            die;
        }

        // Block if already soft-deleted
        $this->db->select('is_deleted, pur_order_number');
        $this->db->where('id', $id);
        $po = $this->db->get(db_prefix().'pur_orders')->row();
        if($po && $po->is_deleted == 1){
            echo json_encode([
                'can_delete' => false,
                'grn_count'  => 0,
                'message'    => 'Purchase Order ' . $po->pur_order_number . ' has already been deleted.',
            ]);
            die;
        }

        // Find active (non-deleted) GRNs linked to this PO
        $this->db->where('pr_order_id', $id);
        $this->db->where('is_deleted', 0);
        $grns = $this->db->get(db_prefix().'goods_receipt')->result_array();

        if(!empty($grns)){
            $grn_codes = array_column($grns, 'goods_receipt_code');
            $count     = count($grns);
            echo json_encode([
                'can_delete' => false,
                'grn_count'  => $count,
                'message'    => 'Cannot delete this Purchase Order. It has ' . $count . ' active GRN(s): '
                              . implode(', ', $grn_codes) . '. Please delete the GRN(s) first.',
            ]);
        } else {
            echo json_encode(['can_delete' => true, 'message' => '', 'grn_count' => 0]);
        }
        die;
    }

    public function delete_pur_order($id){
        if (!has_permission('purchase_orders', '', 'delete')) {
            access_denied('purchase_order');
        }
        if (!$id) {
            redirect(admin_url('purchase/purchase_order'));
        }

        // Guard: block if already soft-deleted
        $this->db->select('is_deleted, pur_order_number');
        $this->db->where('id', $id);
        $po_check = $this->db->get(db_prefix().'pur_orders')->row();
        if($po_check && $po_check->is_deleted == 1){
            set_alert('warning', 'Purchase Order ' . $po_check->pur_order_number . ' has already been deleted.');
            redirect(admin_url('purchase/purchase_order'));
        }

        // Server-side guard: block if active GRNs exist for this PO
        $this->db->where('pr_order_id', $id);
        $this->db->where('is_deleted', 0);
        $active_grn_count = $this->db->count_all_results(db_prefix().'goods_receipt');
        if($active_grn_count > 0){
            set_alert('danger', 'Cannot delete Purchase Order. It has ' . $active_grn_count . ' active GRN(s). Please delete the GRN(s) first.');
            redirect(admin_url('purchase/purchase_order'));
        }

        $success = $this->purchase_model->delete_pur_order($id);
        if (is_array($success)) {
            set_alert('warning', _l('purchase_order'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('purchase_order')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('purchase_order')));
        }
        redirect(admin_url('purchase/purchase_order'));
    }

    /**
     * { estimate by vendor }
     *
     * @param      <type>  $vendor  The vendor
     * @return json
     */
    public function estimate_by_vendor($vendor){
        $estimate = $this->purchase_model->estimate_by_vendor($vendor);
        $ven = $this->purchase_model->get_vendor($vendor);

        $currency = get_base_currency_pur();
        $currency_id = $currency->id;
        if($ven->default_currency != 0){
            $currency_id = $ven->default_currency;
        }
        
        $vendor_data = '';
        $html = '<option value=""></option>';
        $company = '';
        foreach($estimate as $es){
            $html .= '<option value="'.$es['id'].'">'.format_pur_estimate_number($es['id']).'</option>';
        }

        $option_html = '';

        if(total_rows(db_prefix().'pur_vendor_items', ['vendor' => $vendor]) <= ajax_on_total_items()){
            $items = $this->purchase_model->get_items_by_vendor_variation($vendor);
            $option_html .= '<option value=""></option>';
            foreach($items as $item){
                $option_html .= '<option value="'.$item['id'].'" >'.$item['label'].'</option>';
            }
        }


        if($ven){
            $vendor_data .= '<div class="col-md-6">';
            $vendor_data .= '<p class="bold p_style">'._l('vendor_detail').'</p>
                            <hr class="hr_style"/>';
            $vendor_data .= '<table class="table table-striped table-bordered"><tbody>';
            $vendor_data .= '<tr><td>'._l('company').'</td><td>'.$ven->company.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('client_vat_number').'</td><td>'.$ven->vat.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('client_phonenumber').'</td><td>'.$ven->phonenumber.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('website').'</td><td>'.$ven->website.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('vendor_category').'</td><td>'.get_vendor_category_html($ven->category).'</td></tr>';
            $vendor_data .= '<tr><td>'._l('client_address').'</td><td>'.$ven->address.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('client_city').'</td><td>'.$ven->city.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('client_state').'</td><td>'.$ven->state.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('client_postal_code').'</td><td>'.$ven->zip.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('clients_country').'</td><td>'.get_country_short_name($ven->country).'</td></tr>';
            $vendor_data .= '<tr><td>'._l('bank_detail').'</td><td>'.$ven->bank_detail.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('payment_terms').'</td><td>'.$ven->payment_terms.'</td></tr>';
            $vendor_data .= '</tbody></table>';                    
            $vendor_data .= '</div>';

            $vendor_data .= '<div class="col-md-6">';
            $vendor_data .= '<p class="bold p_style">'._l('billing_address').'</p>
                            <hr class="hr_style"/>';
            $vendor_data .= '<table class="table table-striped table-bordered"><tbody>';
            $vendor_data .= '<tr><td>'._l('billing_street').'</td><td>'.$ven->billing_street.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('billing_city').'</td><td>'.$ven->billing_city.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('billing_state').'</td><td>'.$ven->billing_state.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('billing_zip').'</td><td>'.$ven->billing_zip.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('billing_country').'</td><td>'.get_country_short_name($ven->billing_country).'</td></tr>';
            $vendor_data .= '</tbody></table>'; 
            $vendor_data .= '<p class="bold p_style">'._l('shipping_address').'</p>
                            <hr class="hr_style"/>';
            $vendor_data .= '<table class="table table-striped table-bordered"><tbody>';
            $vendor_data .= '<tr><td>'._l('shipping_street').'</td><td>'.$ven->shipping_street.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('shipping_city').'</td><td>'.$ven->shipping_city.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('shipping_state').'</td><td>'.$ven->shipping_state.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('shipping_zip').'</td><td>'.$ven->shipping_zip.'</td></tr>';
            $vendor_data .= '<tr><td>'._l('shipping_country').'</td><td>'.get_country_short_name($ven->shipping_country).'</td></tr>';
            $vendor_data .= '</tbody></table>';                  
            $vendor_data .= '</div>';

            if($ven->vendor_code != ''){
               $company = $ven->vendor_code; 
            }
            
        }

        echo json_encode([
            'result' => $html,
            'ven_html' => $vendor_data,
            'company' => $company,
            'option_html' => $option_html,
            'currency_id' => $currency_id,
            'payment_terms' => (isset($ven->payment_terms) && $ven->payment_terms != '') ? $ven->payment_terms : ''
        ]);
    }

    /**
     * { table pur order }
     */
    public function table_pur_order(){
        $this->app->get_table_data(module_views_path('purchase', 'purchase_order/table_pur_order'));
    }

    /**
     * { contracts }
     * @return  view
     */
    public function contracts(){
        purchase_token();
        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();
        $this->load->model('projects_model');
        $data['projects'] = $this->projects_model->get();
        purchase_init();
        $data['vendors'] = [];
        $data['title'] = _l('contracts');
        $this->load->view('contracts/manage',$data);
    }

    /**
     * { contract }
     *
     * @param      string  $id     The identifier
     * @return redirect , view
     */
    public function contract($id = ''){
        if ($this->input->post()) {
            $contract_data = $this->input->post();
            $contract_data['currency'] = get_base_currency()->id;
            if ($id == '') {
                
                $id = $this->purchase_model->add_contract($contract_data);
                if ($id) {
                    handle_pur_contract_file($id);
                    set_alert('success', _l('added_successfully', _l('contract')));
                    
                    redirect(admin_url('purchase/contracts'));
                    
                }
            } else {
                handle_pur_contract_file($id);
                $success = $this->purchase_model->update_contract($contract_data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('contract')));
                }
                redirect(admin_url('purchase/contract/' . $id));                
            }
        }

        if ($id == '') {
            $title = _l('create_new_contract');
        } else {
            $data['contract'] = $this->purchase_model->get_contract($id);

            if(!isset($data['contract']->id)){

                die('No purchase contract found');
            }

            if(!has_permission('purchase_contracts', '', 'view') && !has_permission('purchase_contracts', '', 'view_own')){
                access_denied('purchase');
            }

            if(has_permission('purchase_contracts', '', 'view_own') && !is_admin()){
                if($data['contract']->add_from != get_staff_user_id() && !in_array($data['contract']->vendor, get_vendor_admin_list(get_staff_user_id()))){
                    access_denied('purchase');
                }
            }

            
            $data['vendor_contacts'] = $this->purchase_model->get_contacts($data['contract']->vendor);
            $data['attachments'] = $this->purchase_model->get_pur_contract_attachment($id);
            $data['payment'] = $this->purchase_model->get_payment_by_contract($id);
            $title = _l('contract_detail');
        }
        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();
        $this->load->model('projects_model');
        $data['projects'] = $this->projects_model->get();
        $data['ven'] = $this->input->get('vendor');
        $data['pur_orders'] = $this->purchase_model->get_pur_order_approved();
        $data['taxes'] = $this->purchase_model->get_taxes();
        $data['staff']             = $this->staff_model->get('', ['active' => 1]);
        $data['members']             = $this->staff_model->get('', ['active' => 1]);
        $data['vendors'] = [];
        $data['units'] = $this->purchase_model->get_units();
        $data['items'] = $this->purchase_model->get_items();
        $data['title'] = $title;

        $this->load->view('contracts/contract', $data);
    }

    /**
     * { delete contract }
     *
     * @param      <type>  $id     The identifier
     * @return redirect
     */
    public function delete_contract($id){
        if (!has_permission('purchase_contracts', '', 'delete')) {
            access_denied('contracts');
        }
        if (!$id) {
            redirect(admin_url('purchase/contracts'));
        }
        $success = $this->purchase_model->delete_contract($id);
        if (is_array($success)) {
            set_alert('warning', _l('contracts'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('contracts')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('contracts')));
        }
        redirect(admin_url('purchase/contracts'));
    }

    /**
     * Determines if contract number exists.
     */
    public function contract_number_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if the email is the same
                $contract = $this->input->post('contract');
                if ($contract != '') {
                    $this->db->where('id', $contract);
                    $cd = $this->db->get('tblpur_contracts')->row();
                    if ($cd->contract_number == $this->input->post('contract_number')) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('contract_number', $this->input->post('contract_number'));
                $total_rows = $this->db->count_all_results('tblpur_contracts');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * { table contracts }
     */
    public function table_contracts(){
         $this->app->get_table_data(module_views_path('purchase', 'contracts/table_contracts'));
    }

    /**
     * Saves a contract data.
     * @return  json
     */
    public function save_contract_data()
    {
        if (!has_permission('purchase_contracts', '', 'edit') && !has_permission('purchase_contracts', '', 'create')) {
            header('HTTP/1.0 400 Bad error');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied'),
            ]);
            die;
        }

        $success = false;
        $message = '';

        $this->db->where('id', $this->input->post('contract_id'));
        $this->db->update(db_prefix().'pur_contracts', [
                'content' => $this->input->post('content', false),
        ]);

        $success = $this->db->affected_rows() > 0;
        $message = _l('updated_successfully', _l('contract'));

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * { pdf contract }
     *
     * @param      <type>  $id     The identifier
     * @return pdf output
     */
    public function pdf_contract($id)
    {
        if (!has_permission('purchase_contracts', '', 'view') && !has_permission('purchase_contracts', '', 'view_own')) {
            access_denied('contracts');
        }

        if (!$id) {
            redirect(admin_url('purchase/contracts'));
        }

        $contract = $this->purchase_model->get_contract($id);
        
        try {
            $pdf = pur_contract_pdf($contract);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(slug_it($contract->contract_number) . '.pdf', $type);
    }

    /**
     * { sign contract }
     *
     * @param      <type>  $contract  The contract
     * @return json
     */
    public function sign_contract($contract){
        if($this->input->post()){
            $data = $this->input->post();
            $success = $this->purchase_model->sign_contract($contract, $data['status']);
            $message = '';
            if($success == true){
                process_digital_signature_image($data['signature'], PURCHASE_MODULE_UPLOAD_FOLDER .'/contract_sign/'. $contract);
                $message = _l('sign_successfully');
            }

            echo json_encode([
                'success' => $success,
                'message' => $message,
            ]);
            
        }

    }

    /**
     * Sends a request approve.
     * @return  json
     */
    public function send_request_approve(){
        $data = $this->input->post();
        $message = 'Send request approval fail';

        // Block approval if PR or PO is soft-deleted
        if($data['rel_type'] == 'pur_request'){
            $this->db->select('is_deleted, pur_rq_code');
            $this->db->where('id', $data['rel_id']);
            $rec = $this->db->get(db_prefix().'pur_request')->row();
            if($rec && $rec->is_deleted == 1){
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot send approval for a deleted Purchase Request (' . $rec->pur_rq_code . ').',
                ]);
                die;
            }
            // If PR is rejected (status 3), reset to draft (status 1) before re-sending
            if($rec && $rec->status == 3){
                $this->db->where('id', $data['rel_id']);
                $this->db->update(db_prefix().'pur_request', ['status' => 1]);
            }
        }

        if($data['rel_type'] == 'pur_order'){
            $this->db->select('is_deleted, pur_order_number');
            $this->db->where('id', $data['rel_id']);
            $rec = $this->db->get(db_prefix().'pur_orders')->row();
            if($rec && $rec->is_deleted == 1){
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot send approval for a deleted Purchase Order (' . $rec->pur_order_number . ').',
                ]);
                die;
            }
        }

        $success = $this->purchase_model->send_request_approve($data);
        if ($success === true) {                
                $message = 'Send request approval success';
                $data_new = [];
                $data_new['send_mail_approve'] = $data;
                $this->session->set_userdata($data_new);

                // Set status to "Sent for Approval" (5)
                if($data['rel_type'] == 'pur_request'){
                    $this->db->where('id', $data['rel_id']);
                    $this->db->update(db_prefix().'pur_request', ['status' => 5]);
                } elseif($data['rel_type'] == 'pur_order'){
                    $this->db->where('id', $data['rel_id']);
                    $this->db->update(db_prefix().'pur_orders', ['approve_status' => 5]);
                }

                // ── Batch payment: if this payment_request shares a transactionid
                //    with other pending payments, send all of them for approval too ──
                if($data['rel_type'] == 'payment_request'){
                    $this->db->select('transactionid');
                    $this->db->where('id', $data['rel_id']);
                    $pay_row = $this->db->get(db_prefix().'pur_invoice_payment')->row();

                    if($pay_row && !empty($pay_row->transactionid)){
                        // Find all other pending payments (approval_status=1) with same txn ID
                        $this->db->where('transactionid', $pay_row->transactionid);
                        $this->db->where('id !=', $data['rel_id']);
                        $this->db->where('approval_status', 1);
                        $this->db->where('is_deleted', 0);
                        $batch_siblings = $this->db->get(db_prefix().'pur_invoice_payment')->result();

                        $batch_sent = 0;
                        foreach($batch_siblings as $sibling){
                            $sibling_data = $data;
                            $sibling_data['rel_id'] = $sibling->id;
                            $sibling_result = $this->purchase_model->send_request_approve($sibling_data);
                            if($sibling_result === true){
                                $batch_sent++;
                            }
                        }

                        if($batch_sent > 0){
                            $txn_id = $pay_row->transactionid;
                            $message = _l('approved_successfully') . ' — Batch payment: ' . $batch_sent
                                . ' additional payment(s) with Transaction ID "' . $txn_id
                                . '" also sent for approval automatically.';
                        }
                    }
                }

        }elseif($success === false){
            $message = _l('no_matching_process_found');
            $success = false;
            
        }else{
            $message = _l('could_not_find_approver_with', _l($success));
            $success = false;
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]); 
        die;
    }

    /**
     * Sends a mail.
     * @return json
     */
    public function send_mail()
    {
        if ($this->input->is_ajax_request()) {
            $data = $this->input->post();
            if((isset($data)) && $data != ''){
                $this->purchase_model->send_mail($data);

                $success = 'success';
                echo json_encode([
                'success' => $success,                
            ]); 
            }
        }
    }

    /**
     * { approve request }
     * @return json
     */
    public function approve_request(){
        $data = $this->input->post();
        $data['staff_approve'] = get_staff_user_id();
        $success = false; 
        $code = '';
        $signature = '';
        $message = '';

        // Block approval action if PR or PO is soft-deleted
        if(isset($data['rel_type']) && isset($data['rel_id'])){
            if($data['rel_type'] == 'pur_request'){
                $this->db->select('is_deleted');
                $this->db->where('id', $data['rel_id']);
                $rec = $this->db->get(db_prefix().'pur_request')->row();
                if($rec && $rec->is_deleted == 1){
                    echo json_encode(['success'=>false,'message'=>'Cannot approve a deleted Purchase Request.']);
                    die;
                }
            }
            if($data['rel_type'] == 'pur_order'){
                $this->db->select('is_deleted');
                $this->db->where('id', $data['rel_id']);
                $rec = $this->db->get(db_prefix().'pur_orders')->row();
                if($rec && $rec->is_deleted == 1){
                    echo json_encode(['success'=>false,'message'=>'Cannot approve a deleted Purchase Order.']);
                    die;
                }
            }
        }

        if(isset($data['signature'])){
            $signature = $data['signature'];
            unset($data['signature']);
        }
        $status_string = 'status_'.$data['approve'];

        $check_approve_status = $this->purchase_model->check_approval_details($data['rel_id'],$data['rel_type']);
        $appr_setting = $this->purchase_model->get_approve_setting_new($data['rel_type']);
        if(isset($appr_setting->approval_type) && $appr_setting->approval_type == 1){
            $check_approve_status = $this->purchase_model->check_approval_details_new($data['rel_id'], $data['rel_type']);
        }else{
            $check_approve_status = $this->purchase_model->check_approval_details($data['rel_id'], $data['rel_type']);
        }

        $approval_detail = $this->purchase_model->get_approve_detail_pur($data);

        // check_approval_details() only returns an array while approvers are still
        // outstanding. Once every approver has approved it returns boolean true, and
        // on a rejection the string 'reject'. Indexing ['staffid'] on either of those
        // gave null, and in_array() with a null haystack is a TypeError on PHP 8 -
        // a redundant approve click on an already decided document returned a 500
        // instead of a message. Resolve the pending approvers safely first.
        $pending_approvers = (is_array($check_approve_status) && isset($check_approve_status['staffid']) && is_array($check_approve_status['staffid']))
            ? $check_approve_status['staffid']
            : [];

        if(isset($data['approve']) && in_array(get_staff_user_id(), $pending_approvers) && isset($approval_detail->id)){

            if(isset($data['sign_type'])){
                $sign_type = $data['sign_type'];
                unset($data['sign_type']);
            }
          
            $success = $this->purchase_model->update_approval_details($approval_detail->id, $data);

            $message = _l('approved_successfully');

            if ($success) {
                // A Purchase Form records every approver's own decision in its activity
                // log, so the log reads as a trail of who signed and when. Written per
                // step here because update_approve_request() only fires on the final
                // status change, which would leave the intermediate approvers invisible.
                if ($data['rel_type'] == 'pur_bid_waiver') {
                    $bw_position = pur_get_job_position(get_staff_user_id());

                    $this->purchase_model->add_activity_log([
                        'rel_id'   => $data['rel_id'],
                        'rel_type' => 'pur_bid_waiver',
                        'staffid'  => get_staff_user_id(),
                        'date'     => date('Y-m-d H:i:s'),
                        'note'     => (!empty($bw_position) ? $bw_position . ': ' : '')
                                      . ($data['approve'] == 2 ? _l('pur_bw_approved') : _l('pur_bw_rejected')),
                    ]);
                }

                if($data['approve'] == 2){
                    $message = _l('approved_successfully');
                    $data_log = [];

                    if($signature != ''){
                        $data_log['note'] = "signed_request";
                    }else{
                        $data_log['note'] = "approve_request";
                    }
                    if($signature != ''){
                        switch ($data['rel_type']) {
                            case 'payment_request':
                                $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/payment_invoice/signature/' .$data['rel_id'];
                                break;
                             case 'purchase_invoice':
                                $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/pur_invoice/signature/' .$data['rel_id'];
                                break;
                            case 'pur_order':
                                $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/pur_order/signature/' .$data['rel_id'];
                                break;
                            case 'pur_request':
                                $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/pur_request/signature/' .$data['rel_id'];
                                break;
                            case 'pur_quotation':
                                $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/pur_estimate/signature/' .$data['rel_id'];
                                break;
                            case 'order_return':
                                $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/order_return/signature/' .$data['rel_id'];
                                break;
                            case 'faf_requests':
                                $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/faf_requests/signature/' .$data['rel_id'];
                                break;
                            case 'pur_bid_waiver':
                                // Sits alongside the form's attachments, which already
                                // live under bid_waivers/<id>/
                                $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/bid_waivers/signature/' .$data['rel_id'];
                                break;
                            default:
                                $path = PURCHASE_MODULE_UPLOAD_FOLDER;
                                break;
                        }

                        if(isset($sign_type) && $sign_type == 'sign'){
                            purchase_process_digital_signature_image($signature, $path, 'signature_'.$approval_detail->id);
                        }


                        $message = _l('sign_successfully');
                    }
                   


                    $check_approve_status = $this->purchase_model->check_approval_details($data['rel_id'],$data['rel_type']);

                    if(isset($appr_setting->approval_type) && $appr_setting->approval_type == 1){
                        $this->purchase_model->update_approve_request($data['rel_id'],$data['rel_type'], 2);
                    }else{
                        if ($check_approve_status === true){
                            $this->purchase_model->update_approve_request($data['rel_id'],$data['rel_type'], 2);
                        }
                    }

                    // ── Batch payment alert ──────────────────────────────────────────────
                    if($data['rel_type'] == 'payment_request'){
                        $batch_count = isset($this->purchase_model->_last_batch_approved_count)
                            ? (int)$this->purchase_model->_last_batch_approved_count : 0;
                        $batch_txn   = isset($this->purchase_model->_last_batch_txn_id)
                            ? $this->purchase_model->_last_batch_txn_id : '';
                        if($batch_count > 0){
                            $message .= ' ' . sprintf(
                                _l('batch_payment_also_approved'),
                                $batch_count,
                                $batch_txn
                            );
                            // Fallback if lang key missing
                            if(strpos($message, 'batch_payment_also_approved') !== false){
                                $message = _l('approved_successfully') . ' — Batch payment: ' . $batch_count
                                    . ' additional payment(s) with Transaction ID "' . $batch_txn . '" also approved.';
                            }
                        }
                    }
                }else{
                    $message = _l('rejected_successfully');
                    
                    $this->purchase_model->update_approve_request($data['rel_id'],$data['rel_type'], '3');
                }
            }
        }

        $data_new = [];
        $data_new['send_mail_approve'] = $data;
        $this->session->set_userdata($data_new);
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
        die();      
    }

    /**
     * approve request
     * @param  integer $id
     * @return json
     */
    public function approve_request_for_order_return() {
        $data = $this->input->post();

        $data['staff_approve'] = get_staff_user_id();
        $success = false;
        $code = '';
        $signature = '';

        if (isset($data['signature'])) {
            $signature = $data['signature'];
            unset($data['signature']);
        }
        $status_string = 'status_' . $data['approve'];
        $check_approve_status = $this->purchase_model->check_approval_details($data['rel_id'], $data['rel_type']);


        if (isset($data['approve']) && in_array(get_staff_user_id(), $check_approve_status['staffid'])) {

            $success = $this->purchase_model->update_approval_details($check_approve_status['id'], $data);

            $message = _l('approved_successfully');

            if ($success) {
                if ($data['approve'] == 1) {
                    $message = _l('approved_successfully');
                    $data_log = [];

                    if ($signature != '') {
                        $data_log['note'] = "signed_request";
                    } else {
                        $data_log['note'] = "approve_request";
                    }
                    if ($signature != '') {
                        switch ($data['rel_type']) {
                        

                            case 'order_return':
                            $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/order_return/signature/' .$data['rel_id'];
                            break;
                            


                            default:
                            $path = PURCHASE_MODULE_UPLOAD_FOLDER;
                            break;
                        }
                        purchase_process_digital_signature_image($signature, $path, 'signature_' . $check_approve_status['id']);
                        $message = _l('sign_successfully');
                    }
                    $data_log['rel_id'] = $data['rel_id'];
                    $data_log['rel_type'] = $data['rel_type'];
                    $data_log['staffid'] = get_staff_user_id();
                    $data_log['date'] = date('Y-m-d H:i:s');

                    $this->purchase_model->add_activity_log($data_log);

                    $check_approve_status = $this->purchase_model->check_approval_details($data['rel_id'], $data['rel_type']);

                    if ($check_approve_status === true) {
                        $this->purchase_model->update_approve_request($data['rel_id'], $data['rel_type'], 1);
                    }
                } else {
                    $message = _l('rejected_successfully');
                    $data_log = [];
                    $data_log['rel_id'] = $data['rel_id'];
                    $data_log['rel_type'] = $data['rel_type'];
                    $data_log['staffid'] = get_staff_user_id();
                    $data_log['date'] = date('Y-m-d H:i:s');
                    $data_log['note'] = "rejected_request";
                    $this->purchase_model->add_activity_log($data_log);
                    $this->purchase_model->update_approve_request($data['rel_id'], $data['rel_type'], '-1');
                }
            }
        }

        $data_new = [];
        $data_new['send_mail_approve'] = $data;
        $this->session->set_userdata($data_new);
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
        die();
    }

    /**
     * Sends a request quotation.
     * @return redirect
     */
    public function send_request_quotation(){
        if($this->input->post()){
            $data = $this->input->post();
            $data['content'] = $this->input->post('content', false);
            $send = $this->purchase_model->send_pr($data);
            if($send){
                set_alert('success',_l('send_pr_successfully'));
                
            }else{
                set_alert('warning',_l('send_pr_fail'));
            }
            redirect(admin_url('purchase/purchase_request'));
            
        }
    }

    /**
     * { purchase request pdf }
     *
     * @param      <type>  $id     The identifier
     * @return pdf output
     */
    public function pur_request_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('purchase/purchase_request'));
        }

        // Use NextGen format
        $pur_request = $this->purchase_model->get_pur_request_nextgen_pdf_html($id);

        try {
            $pdf = $this->purchase_model->pur_request_pdf($pur_request);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }
        
        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        // Add watermark based on PR status
        $pr = $this->purchase_model->get_purchase_request($id);
        $watermark_text = '';
        if(isset($pr->is_deleted) && $pr->is_deleted == 1){
            $watermark_text = 'DELETED';
        } elseif(isset($pr->status) && $pr->status == 3){
            $watermark_text = 'REJECTED';
        } elseif(!isset($pr->status) || $pr->status != 2){
            $watermark_text = 'NOT VALID';
        }

        if(!empty($watermark_text)){
            $pages = $pdf->getNumPages();
            for($i = 1; $i <= $pages; $i++){
                $pdf->setPage($i);
                $pdf->SetAlpha(0.15);
                $pdf->SetFont('helvetica', 'B', 60);
                $pdf->SetTextColor(255, 0, 0);
                $pdf->StartTransform();
                $pdf->Rotate(45, 105, 148);
                $pdf->Text(40, 140, $watermark_text);
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }

        $pdf->Output('purchase_request.pdf', $type);
    }

    /**
     * { request quotation pdf }
     *
     * @param      <type>  $id     The identifier
     * @return pdf output
     */
    public function request_quotation_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('purchase/purchase_request'));
        }

        // Use NextGen format (same as PR)
        $pur_request = $this->purchase_model->get_pur_request_nextgen_pdf_html($id);

        try {
            $pdf = $this->purchase_model->request_quotation_pdf($pur_request);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }
        
        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output('request_quotation.pdf', $type);
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function purchase_order_setting(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function item_by_vendor(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function show_item_cf_on_pdf(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function show_tax_column(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function send_email_welcome_for_new_contact(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

     /**
     * { purchase order setting }
     * @return  json
     */
    public function reset_purchase_order_number_every_month(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

     /**
     * { purchase order setting }
     * @return  json
     */
    public function show_vendor_note_on_po_pdf(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function automatically_create_po(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function allow_vendor_add_edit_delete_purchase_invoice(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function allow_vendor_add_edit_delete_purchase_quotation(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function show_purchase_order_name_on_po_pdf(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function po_only_prefix_and_number(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * Gets the notes.
     *
     * @param      <type>  $id     The id of purchase order
     */
    public function get_notes($id)
    {       
        $data['notes'] = $this->misc_model->get_notes($id, 'purchase_order');
        $this->load->view('admin/includes/sales_notes_template', $data);
    }

    /**
     * Gets the purchase contract notes.
     *
     * @param      <type>  $id     The id of purchase order
     */
    public function get_notes_pur_contract($id)
    {
        $data['notes'] = $this->misc_model->get_notes($id, 'pur_contract');
        $this->load->view('admin/includes/sales_notes_template', $data);
    }

     /**
     * Gets the purchase invoice notes.
     *
     * @param      <type>  $id     The id of purchase order
     */
    public function get_notes_pur_invoice($id)
    {
        $data['notes'] = $this->misc_model->get_notes($id, 'pur_invoice');
        $this->load->view('admin/includes/sales_notes_template', $data);
    }

    /**
     * Adds a note.
     *
     * @param        $rel_id  The purchase contract id
     */
    public function add_pur_contract_note($rel_id)
    {
        if ($this->input->post() ) {
            $this->misc_model->add_note($this->input->post(), 'pur_contract', $rel_id);
            echo pur_html_entity_decode($rel_id);
        }
    }

    /**
     * Adds a note.
     *
     * @param        $rel_id  The purchase contract id
     */
    public function add_pur_invoice_note($rel_id)
    {
        if ($this->input->post() ) {
            $this->misc_model->add_note($this->input->post(), 'pur_invoice', $rel_id);
            echo pur_html_entity_decode($rel_id);
        }
    }

    /**
     * Adds a note.
     *
     * @param      <type>  $rel_id  The purchase order id
     */
    public function add_note($rel_id)
    {
        if ($this->input->post() ) {
            $this->misc_model->add_note($this->input->post(), 'purchase_order', $rel_id);
            echo pur_html_entity_decode($rel_id);
        }
    }

    /**
     * Uploads a purchase order attachment.
     *
     * @param      string  $id  The purchase order
     * @return redirect
     */
    public function purchase_order_attachment($id){

        handle_purchase_order_file($id);

        redirect(admin_url('purchase/purchase_order/'.$id));
    }

    /**
     * Uploads a purchase order attachment.
     *
     * @param      string  $id  The purchase order
     * @return redirect
     */
    public function purchase_request_attachment($id){

        handle_purchase_request_file($id);

        // If AJAX request, return JSON response with the new attachment list
        if($this->input->is_ajax_request() || $this->input->server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest'){
            $attachments = $this->purchase_model->get_purchase_request_attachments($id);
            // Enrich with staff name so JS can render it
            foreach($attachments as &$att){
                $att['staffid_name'] = get_staff_full_name($att['staffid']);
            }
            unset($att);
            echo json_encode(['success' => true, 'attachments' => $attachments]);
            return;
        }

        redirect(admin_url('purchase/view_pur_request/'.$id.'?tab=attachment'));
    }

    /**
     * Save logic notes for purchase request
     */
    public function save_logic_notes($id){
        if($this->input->post()){
            $logic_notes = $this->input->post('logic_notes', false);
            $this->db->where('id', $id);
            $this->db->update(db_prefix().'pur_request', ['logic_notes' => $logic_notes]);
            set_alert('success', _l('updated_successfully', _l('logic_notes')));
        }
        redirect(admin_url('purchase/view_pur_request/'.$id.'?tab=logic_notes'));
    }

    /**
     * Save logic notes via AJAX — no page reload, no leave warning
     */
    public function save_logic_notes_ajax($id){
        if(!is_numeric($id)){
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            die;
        }
        if($this->input->post()){
            $logic_notes = $this->input->post('logic_notes', false);
            $this->db->where('id', $id);
            $this->db->update(db_prefix().'pur_request', ['logic_notes' => $logic_notes]);
            echo json_encode(['success' => true, 'message' => _l('updated_successfully', _l('logic_notes'))]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No data']);
        }
        die;
    }

    /**
     * Check if vendor recommendations exist for a purchase request (AJAX)
     */
    public function check_vendor_recommendations($pr_id){
        if(!is_numeric($pr_id)){
            echo json_encode(['has_recommendations' => false]);
            die;
        }
        $this->db->where('pur_request_id', $pr_id);
        $count = $this->db->count_all_results(db_prefix().'pur_vendor_recommendations');
        echo json_encode(['has_recommendations' => ($count > 0)]);
        die;
    }

    /**
     * Add vendor recommendation with file upload
     */
    public function add_vendor_recommendation($pr_id){
        if(!is_numeric($pr_id)){
            set_alert('danger', 'Invalid request.');
            redirect(admin_url('purchase/purchase_request'));
            return;
        }
        $data = [];
        $data['pur_request_id'] = $pr_id;
        $data['vendor_name'] = $this->input->post('vr_vendor_name');
        $data['quotation_amount'] = $this->input->post('vr_amount');
        $data['remarks'] = $this->input->post('vr_remarks');
        $data['staffid'] = get_staff_user_id();
        $data['dateadded'] = date('Y-m-d H:i:s');
        $data['recommended'] = 0;

        // Handle file upload
        if(isset($_FILES['vr_file']['name']) && $_FILES['vr_file']['name'] != ''){
            $path = PURCHASE_MODULE_UPLOAD_FOLDER . '/vendor_recommendations/' . $pr_id . '/';
            _maybe_create_upload_path($path);
            $filename = unique_filename($path, $_FILES['vr_file']['name']);
            if(move_uploaded_file($_FILES['vr_file']['tmp_name'], $path . $filename)){
                $data['file_name'] = $filename;
                $data['filetype'] = $_FILES['vr_file']['type'];
            }
        }

        $this->db->insert(db_prefix().'pur_vendor_recommendations', $data);
        if($this->db->insert_id()){
            set_alert('success', 'Vendor recommendation added successfully.');
        }
        redirect(admin_url('purchase/view_pur_request/'.$pr_id.'?tab=vendor_recommendations'));
    }

    /**
     * Set a vendor recommendation as recommended
     */
    public function set_vendor_recommended($vr_id, $pr_id){
        // Unset all others for this PR
        $this->db->where('pur_request_id', $pr_id);
        $this->db->update(db_prefix().'pur_vendor_recommendations', ['recommended' => 0]);
        // Set this one
        $this->db->where('id', $vr_id);
        $this->db->update(db_prefix().'pur_vendor_recommendations', ['recommended' => 1]);
        set_alert('success', 'Vendor marked as recommended.');
        redirect(admin_url('purchase/view_pur_request/'.$pr_id.'?tab=vendor_recommendations'));
    }

    /**
     * Delete vendor recommendation
     */
    public function delete_vendor_recommendation($vr_id, $pr_id){
        // Block deletion if PR is approved
        $pr = $this->purchase_model->get_purchase_request($pr_id);
        if($pr && $pr->status == 2){
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('purchase/view_pur_request/'.$pr_id.'?tab=vendor_recommendations'));
            return;
        }
        
        $this->db->where('id', $vr_id);
        $vr = $this->db->get(db_prefix().'pur_vendor_recommendations')->row();
        if($vr && !empty($vr->file_name)){
            $path = PURCHASE_MODULE_UPLOAD_FOLDER . '/vendor_recommendations/' . $pr_id . '/' . $vr->file_name;
            if(file_exists($path)) unlink($path);
        }
        $this->db->where('id', $vr_id);
        $this->db->delete(db_prefix().'pur_vendor_recommendations');
        set_alert('success', 'Vendor recommendation deleted.');
        redirect(admin_url('purchase/view_pur_request/'.$pr_id.'?tab=vendor_recommendations'));
    }


    /**
     * { preview purchase order file }
     *
     * @param      <type>  $id      The identifier
     * @param      <type>  $rel_id  The relative identifier
     * @return  view
     */
    public function file_purorder($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin']             = is_admin();
        $data['file'] = $this->purchase_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }
        $this->load->view('purchase_order/_file', $data);
    }

    /**
     * { delete purchase order attachment }
     *
     * @param      <type>  $id     The identifier
     */
    public function delete_purorder_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);
        
        // Block deletion if PO is approved
        if($file){
            $po = $this->purchase_model->get_pur_order($file->rel_id);
            if($po && $po->approve_status == 2){
                header('HTTP/1.0 400 Bad error');
                echo _l('access_denied');
                die;
            }
        }
        
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo pur_html_entity_decode($this->purchase_model->delete_purorder_attachment($id));
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /**
     * Adds a payment.
     *
     * @param      <type>  $pur_order  The purchase order id
     * @return  redirect
     */
    public function add_payment($pur_order){
         if ($this->input->post()) {
            $data = $this->input->post();
            $message = '';
            $success = $this->purchase_model->add_payment($data, $pur_order);
            if ($success) {
                $message = _l('added_successfully', _l('payment'));
            }
            set_alert('success', $message);
            redirect(admin_url('purchase/purchase_order/'.$pur_order));
            
        }
    }

    /**
     * Adds a payment on PO.
     *
     * @param      <type>  $pur_order  The purchase order id
     * @return  redirect
     */
    public function add_payment_on_po($pur_order){
         if ($this->input->post()) {
            $data = $this->input->post();
            $message = '';
            $success = $this->purchase_model->add_payment_on_po($data, $pur_order);
            if ($success) {
                $message = _l('added_successfully', _l('payment'));
            }
            set_alert('success', $message);
            redirect(admin_url('purchase/purchase_order/'.$pur_order));
            
        }
    }

    /**
     * { delete payment }
     *
     * @param      <type>  $id         The identifier
     * @param      <type>  $pur_order  The pur order
     * @return  redirect
     */
    public function delete_payment($id,$pur_order)
    {
        if (!$id) {
            redirect(admin_url('purchase/purchase_order/'.$pur_order));
        }
        $response = $this->purchase_model->delete_payment($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('payment')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('payment')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('payment')));
        }
        redirect(admin_url('purchase/purchase_order/'.$pur_order));
    }

    /**
     * { purchase order pdf }
     *
     * @param      <type>  $id     The identifier
     * @return pdf output
     */
    public function purorder_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('purchase/purchase_order'));
        }

        $po = $this->purchase_model->get_pur_order($id);

        // Use NextGen format
        $pur_request = $this->purchase_model->get_purorder_nextgen_pdf_html($id);

        try {
            $pdf = $this->purchase_model->purorder_pdf($pur_request);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }
        
        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        // Add watermark based on PO status
        if(isset($po->is_deleted) && $po->is_deleted == 1){
            $watermark_text = 'DELETED';
        } elseif(isset($po->approve_status) && $po->approve_status == 3){
            $watermark_text = 'REJECTED';
        } elseif(!isset($po->approve_status) || $po->approve_status != 2){
            $watermark_text = 'NOT VALID';
        } else {
            $watermark_text = '';
        }

        if(!empty($watermark_text)){
            $pages = $pdf->getNumPages();
            for($i = 1; $i <= $pages; $i++){
                $pdf->setPage($i);
                $pdf->SetAlpha(0.15);
                $pdf->SetFont('helvetica', 'B', 60);
                $pdf->SetTextColor(255, 0, 0);
                $pdf->StartTransform();
                $pdf->Rotate(45, 105, 148);
                $pdf->Text(40, 140, $watermark_text);
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }

        // Clear any output buffered by PHP notices in development mode
        if (ob_get_level()) {
            ob_end_clean();
        }

        $pdf->Output($po->pur_order_number.'.pdf', $type);
    }

    /**
     * { clear signature }
     *
     * @param      <type>  $id     The identifier
     */
    public function clear_signature($id)
    {
        if (has_permission('purchase_contracts', '', 'delete')) {
            $this->purchase_model->clear_signature($id);
        }

        redirect(admin_url('purchase/contract/' . $id));
    }

    /**
     * { Purchase reports }
     * 
     * @return view
     */
    public function reports(){
        purchase_token();
        if (!is_admin() && !has_permission('purchase_reports','','view')) {
            access_denied('purchase');
        }
        purchase_init();
        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();
        $data['title'] = _l('purchase_reports');
        $data['items'] = $this->purchase_model->get_items();
        $this->load->view('reports/manage_report',$data);
    }

    /**
     * Direct PDF download for Purchase Report (used by voice commands)
     * Generates a summary PDF of all Purchase Orders in the date range
     */
    public function purchase_report_pdf(){
        if (!is_staff_logged_in()) {
            redirect(admin_url('authentication'));
        }

        // Same gate as reports() above. A logged in check alone let any staff member
        // pull these straight from the URL, regardless of their permissions.
        if (!is_admin() && !has_permission('purchase_reports', '', 'view')) {
            access_denied('purchase');
        }

        $from_date = $this->input->get('from_date') ? $this->input->get('from_date') : date('Y-01-01');
        $to_date = $this->input->get('to_date') ? $this->input->get('to_date') : date('Y-m-d');

        $this->load->model('currencies_model');
        $currency = $this->currencies_model->get_base_currency();

        // Get PO data for the date range
        $this->db->select([
            db_prefix() . 'pur_orders.pur_order_number',
            db_prefix() . 'pur_orders.order_date',
            db_prefix() . 'departments.name as department_name',
            db_prefix() . 'pur_vendor.company as vendor_name',
            db_prefix() . 'pur_orders.subtotal',
            db_prefix() . 'pur_orders.total_tax',
            db_prefix() . 'pur_orders.total',
            db_prefix() . 'pur_orders.approve_status',
        ]);
        $this->db->from(db_prefix() . 'pur_orders');
        $this->db->join(db_prefix() . 'departments', db_prefix() . 'departments.departmentid = ' . db_prefix() . 'pur_orders.department', 'left');
        $this->db->join(db_prefix() . 'pur_vendor', db_prefix() . 'pur_vendor.userid = ' . db_prefix() . 'pur_orders.vendor', 'left');
        $this->db->where(db_prefix() . 'pur_orders.order_date >=', $from_date);
        $this->db->where(db_prefix() . 'pur_orders.order_date <=', $to_date);
        $this->db->order_by(db_prefix() . 'pur_orders.order_date', 'DESC');
        $orders = $this->db->get()->result();

        $company_name = get_option('companyname');
        $total_amount = 0;
        $total_tax = 0;
        $total_subtotal = 0;

        $rows_html = '';
        $sn = 1;
        foreach($orders as $order){
            $status_text = '';
            switch($order->approve_status){
                case 1: $status_text = 'Approved'; break;
                case 2: $status_text = 'Pending'; break;
                case 3: $status_text = 'Rejected'; break;
                default: $status_text = 'Draft'; break;
            }
            $rows_html .= '<tr>';
            $rows_html .= '<td>' . $sn++ . '</td>';
            $rows_html .= '<td>' . $order->pur_order_number . '</td>';
            $rows_html .= '<td>' . $order->order_date . '</td>';
            $rows_html .= '<td>' . ($order->vendor_name ?? '-') . '</td>';
            $rows_html .= '<td>' . ($order->department_name ?? '-') . '</td>';
            $rows_html .= '<td style="text-align:right;">' . number_format($order->subtotal, 2) . '</td>';
            $rows_html .= '<td style="text-align:right;">' . number_format($order->total_tax, 2) . '</td>';
            $rows_html .= '<td style="text-align:right;">' . number_format($order->total, 2) . '</td>';
            $rows_html .= '<td>' . $status_text . '</td>';
            $rows_html .= '</tr>';
            $total_subtotal += $order->subtotal;
            $total_tax += $order->total_tax;
            $total_amount += $order->total;
        }

        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #333; padding: 4px 6px; }
            th { background: #f0f0f0; font-weight: bold; text-align: center; }
            .header { text-align: center; margin-bottom: 10px; }
            .header h2 { margin: 0; }
            .header p { margin: 2px 0; }
            .totals td { font-weight: bold; background: #f9f9f9; }
        </style></head><body>';
        $html .= '<div class="header">';
        $html .= '<h2>' . $company_name . '</h2>';
        $html .= '<h3>Purchase Order Report</h3>';
        $html .= '<p>From: ' . $from_date . ' &nbsp; To: ' . $to_date . '</p>';
        $html .= '</div>';
        $html .= '<table>';
        $html .= '<thead><tr><th>#</th><th>PO Number</th><th>Date</th><th>Vendor</th><th>Department</th><th>Subtotal</th><th>Tax</th><th>Total</th><th>Status</th></tr></thead>';
        $html .= '<tbody>' . $rows_html . '</tbody>';
        $html .= '<tfoot><tr class="totals"><td colspan="5" style="text-align:right;">TOTAL</td><td style="text-align:right;">' . number_format($total_subtotal, 2) . '</td><td style="text-align:right;">' . number_format($total_tax, 2) . '</td><td style="text-align:right;">' . number_format($total_amount, 2) . '</td><td></td></tr></tfoot>';
        $html .= '</table>';
        $html .= '<p style="margin-top:10px;">Total Purchase Orders: ' . count($orders) . '</p>';
        $html .= '</body></html>';

        require_once FCPATH . 'modules/accounting/libraries/dompdf/autoload.inc.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('purchase_report_' . $from_date . '_to_' . $to_date . '.pdf', array("Attachment" => false));
        exit(0);
    }

    /**
     * Direct PDF download for Purchase Invoice Report (voice command)
     */
    public function purchase_inv_report_pdf(){
        if (!is_staff_logged_in()) { redirect(admin_url('authentication')); }

        if (!is_admin() && !has_permission('purchase_reports', '', 'view')) {
            access_denied('purchase');
        }

        $from_date = $this->input->get('from_date') ? $this->input->get('from_date') : date('Y-01-01');
        $to_date = $this->input->get('to_date') ? $this->input->get('to_date') : date('Y-m-d');

        $this->load->model('currencies_model');
        $currency = $this->currencies_model->get_base_currency();

        $this->db->select([
            db_prefix() . 'pur_invoices.invoice_number',
            db_prefix() . 'pur_invoices.invoice_date',
            db_prefix() . 'pur_invoices.payment_status',
            db_prefix() . 'pur_invoices.subtotal',
            db_prefix() . 'pur_invoices.tax',
            db_prefix() . 'pur_invoices.total',
            db_prefix() . 'pur_contracts.contract_number',
        ]);
        $this->db->from(db_prefix() . 'pur_invoices');
        $this->db->join(db_prefix() . 'pur_contracts', db_prefix() . 'pur_contracts.id = ' . db_prefix() . 'pur_invoices.contract', 'left');
        $this->db->where(db_prefix() . 'pur_invoices.invoice_date >=', $from_date);
        $this->db->where(db_prefix() . 'pur_invoices.invoice_date <=', $to_date);
        $this->db->order_by(db_prefix() . 'pur_invoices.invoice_date', 'DESC');
        $invoices = $this->db->get()->result();

        $company_name = get_option('companyname');
        $rows_html = '';
        $sn = 1;
        $total_subtotal = 0; $total_tax = 0; $total_amount = 0;
        foreach($invoices as $inv){
            $rows_html .= '<tr><td>'.$sn++.'</td><td>'.$inv->invoice_number.'</td><td>'.($inv->contract_number ?? '-').'</td><td>'.$inv->invoice_date.'</td><td>'.$inv->payment_status.'</td><td style="text-align:right;">'.number_format($inv->subtotal,2).'</td><td style="text-align:right;">'.number_format($inv->tax,2).'</td><td style="text-align:right;">'.number_format($inv->total,2).'</td></tr>';
            $total_subtotal += $inv->subtotal; $total_tax += $inv->tax; $total_amount += $inv->total;
        }

        $html = '<html><head><style>body{font-family:DejaVu Sans,sans-serif;font-size:11px;}table{width:100%;border-collapse:collapse;margin-top:10px;}th,td{border:1px solid #333;padding:4px 6px;}th{background:#f0f0f0;font-weight:bold;text-align:center;}.header{text-align:center;margin-bottom:10px;}.header h2{margin:0;}.totals td{font-weight:bold;background:#f9f9f9;}</style></head><body>';
        $html .= '<div class="header"><h2>'.$company_name.'</h2><h3>Purchase Invoice Report</h3><p>From: '.$from_date.' &nbsp; To: '.$to_date.'</p></div>';
        $html .= '<table><thead><tr><th>#</th><th>Invoice No</th><th>Contract</th><th>Date</th><th>Status</th><th>Subtotal</th><th>Tax</th><th>Total</th></tr></thead>';
        $html .= '<tbody>'.$rows_html.'</tbody>';
        $html .= '<tfoot><tr class="totals"><td colspan="5" style="text-align:right;">TOTAL</td><td style="text-align:right;">'.number_format($total_subtotal,2).'</td><td style="text-align:right;">'.number_format($total_tax,2).'</td><td style="text-align:right;">'.number_format($total_amount,2).'</td></tr></tfoot>';
        $html .= '</table><p>Total Invoices: '.count($invoices).'</p></body></html>';

        require_once FCPATH . 'modules/accounting/libraries/dompdf/autoload.inc.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('purchase_invoice_report_'.$from_date.'_to_'.$to_date.'.pdf', array("Attachment" => false));
        exit(0);
    }

    /**
     * Direct PDF download for Import Goods Report (voice command)
     */
    public function import_goods_report_pdf(){
        if (!is_staff_logged_in()) { redirect(admin_url('authentication')); }

        if (!is_admin() && !has_permission('purchase_reports', '', 'view')) {
            access_denied('purchase');
        }

        $from_date = $this->input->get('from_date') ? $this->input->get('from_date') : date('Y-01-01');
        $to_date = $this->input->get('to_date') ? $this->input->get('to_date') : date('Y-m-d');

        $this->load->model('currencies_model');
        $currency = $this->currencies_model->get_base_currency();

        $this->db->select([
            db_prefix().'items.commodity_code as item_code',
            db_prefix().'items.description as item_name',
            db_prefix().'pur_orders.pur_order_number as po_name',
            db_prefix().'pur_order_detail.unit_price',
            db_prefix().'pur_order_detail.quantity',
            db_prefix().'pur_order_detail.tax_value',
            db_prefix().'pur_order_detail.total_money',
            db_prefix().'pur_vendor.company as vendor_name',
        ]);
        $this->db->from(db_prefix() . 'pur_order_detail');
        $this->db->join(db_prefix().'items', db_prefix().'items.id = '.db_prefix().'pur_order_detail.item_code', 'left');
        $this->db->join(db_prefix().'pur_orders', db_prefix().'pur_orders.id = '.db_prefix().'pur_order_detail.pur_order', 'left');
        $this->db->join(db_prefix().'pur_vendor', db_prefix().'pur_vendor.userid = '.db_prefix().'pur_orders.vendor', 'left');
        $this->db->where(db_prefix().'pur_orders.order_date >=', $from_date);
        $this->db->where(db_prefix().'pur_orders.order_date <=', $to_date);
        $this->db->order_by(db_prefix().'pur_orders.order_date', 'DESC');
        $items = $this->db->get()->result();

        $company_name = get_option('companyname');
        $rows_html = '';
        $sn = 1; $total_amount = 0;
        foreach($items as $item){
            $rows_html .= '<tr><td>'.$sn++.'</td><td>'.($item->item_code ?? '-').'</td><td>'.($item->item_name ?? '-').'</td><td>'.($item->po_name ?? '-').'</td><td>'.($item->vendor_name ?? '-').'</td><td style="text-align:right;">'.number_format($item->unit_price,2).'</td><td style="text-align:right;">'.$item->quantity.'</td><td style="text-align:right;">'.number_format($item->tax_value,2).'</td><td style="text-align:right;">'.number_format($item->total_money,2).'</td></tr>';
            $total_amount += $item->total_money;
        }

        $html = '<html><head><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px;}table{width:100%;border-collapse:collapse;margin-top:10px;}th,td{border:1px solid #333;padding:3px 5px;}th{background:#f0f0f0;font-weight:bold;text-align:center;}.header{text-align:center;margin-bottom:10px;}.header h2{margin:0;}.totals td{font-weight:bold;background:#f9f9f9;}</style></head><body>';
        $html .= '<div class="header"><h2>'.$company_name.'</h2><h3>Import Goods Report</h3><p>From: '.$from_date.' &nbsp; To: '.$to_date.'</p></div>';
        $html .= '<table><thead><tr><th>#</th><th>Item Code</th><th>Description</th><th>PO Number</th><th>Vendor</th><th>Unit Price</th><th>Qty</th><th>Tax</th><th>Total</th></tr></thead>';
        $html .= '<tbody>'.$rows_html.'</tbody>';
        $html .= '<tfoot><tr class="totals"><td colspan="8" style="text-align:right;">TOTAL</td><td style="text-align:right;">'.number_format($total_amount,2).'</td></tr></tfoot>';
        $html .= '</table><p>Total Items: '.count($items).'</p></body></html>';

        require_once FCPATH . 'modules/accounting/libraries/dompdf/autoload.inc.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('import_goods_report_'.$from_date.'_to_'.$to_date.'.pdf', array("Attachment" => false));
        exit(0);
    }

    /**
     * Direct PDF download for PO Voucher Report (voice command)
     */
    public function po_voucher_report_pdf(){
        if (!is_staff_logged_in()) { redirect(admin_url('authentication')); }

        if (!is_admin() && !has_permission('purchase_reports', '', 'view')) {
            access_denied('purchase');
        }

        $from_date = $this->input->get('from_date') ? $this->input->get('from_date') : date('Y-01-01');
        $to_date = $this->input->get('to_date') ? $this->input->get('to_date') : date('Y-m-d');

        $this->db->select([
            db_prefix().'pur_orders.pur_order_number',
            db_prefix().'pur_orders.order_date',
            db_prefix().'pur_orders.type',
            db_prefix().'departments.name as department_name',
            db_prefix().'pur_vendor.company as vendor_name',
            db_prefix().'pur_orders.approve_status',
            db_prefix().'pur_orders.delivery_status',
            db_prefix().'pur_orders.total',
        ]);
        $this->db->from(db_prefix() . 'pur_orders');
        $this->db->join(db_prefix().'departments', db_prefix().'departments.departmentid = '.db_prefix().'pur_orders.department', 'left');
        $this->db->join(db_prefix().'pur_vendor', db_prefix().'pur_vendor.userid = '.db_prefix().'pur_orders.vendor', 'left');
        $this->db->where(db_prefix().'pur_orders.order_date >=', $from_date);
        $this->db->where(db_prefix().'pur_orders.order_date <=', $to_date);
        $this->db->order_by(db_prefix().'pur_orders.order_date', 'DESC');
        $orders = $this->db->get()->result();

        $company_name = get_option('companyname');
        $rows_html = '';
        $sn = 1; $total_amount = 0;
        foreach($orders as $o){
            $status = $o->approve_status == 1 ? 'Approved' : ($o->approve_status == 2 ? 'Pending' : ($o->approve_status == 3 ? 'Rejected' : 'Draft'));
            $delivery = $o->delivery_status == 1 ? 'Delivered' : 'Undelivered';
            $rows_html .= '<tr><td>'.$sn++.'</td><td>'.$o->pur_order_number.'</td><td>'.$o->order_date.'</td><td>'.($o->type ?? '-').'</td><td>'.($o->department_name ?? '-').'</td><td>'.($o->vendor_name ?? '-').'</td><td>'.$status.'</td><td>'.$delivery.'</td><td style="text-align:right;">'.number_format($o->total,2).'</td></tr>';
            $total_amount += $o->total;
        }

        $html = '<html><head><style>body{font-family:DejaVu Sans,sans-serif;font-size:11px;}table{width:100%;border-collapse:collapse;margin-top:10px;}th,td{border:1px solid #333;padding:4px 6px;}th{background:#f0f0f0;font-weight:bold;text-align:center;}.header{text-align:center;margin-bottom:10px;}.header h2{margin:0;}.totals td{font-weight:bold;background:#f9f9f9;}</style></head><body>';
        $html .= '<div class="header"><h2>'.$company_name.'</h2><h3>PO Voucher Report</h3><p>From: '.$from_date.' &nbsp; To: '.$to_date.'</p></div>';
        $html .= '<table><thead><tr><th>#</th><th>PO Number</th><th>Date</th><th>Type</th><th>Department</th><th>Vendor</th><th>Status</th><th>Delivery</th><th>Total</th></tr></thead>';
        $html .= '<tbody>'.$rows_html.'</tbody>';
        $html .= '<tfoot><tr class="totals"><td colspan="8" style="text-align:right;">TOTAL</td><td style="text-align:right;">'.number_format($total_amount,2).'</td></tr></tfoot>';
        $html .= '</table><p>Total POs: '.count($orders).'</p></body></html>';

        require_once FCPATH . 'modules/accounting/libraries/dompdf/autoload.inc.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('po_voucher_report_'.$from_date.'_to_'.$to_date.'.pdf', array("Attachment" => false));
        exit(0);
    }

    /**
     *  import goods report
     *  
     *  @return json
     */
    public function import_goods_report()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('currencies_model');

            $select = [
                'tblitems.commodity_code as item_code', 
                'tblitems.description as item_name',
                '(select pur_order_number from ' . db_prefix() . 'pur_orders where ' . db_prefix() . 'pur_orders.id = pur_order) as po_name', 
                'total_money',
            ];
            $where =[];
            $custom_date_select = $this->get_where_report_period(db_prefix() . 'pur_orders.order_date');
            if ($custom_date_select != '') {
                array_push($where, $custom_date_select);
            }
            $currency = $this->currencies_model->get_base_currency();

            if($this->input->post('report_currency')){
                $report_currency = $this->input->post('report_currency');
                $base_currency = get_base_currency_pur();

                if($report_currency == $base_currency->id){
                    array_push($where, 'AND '.db_prefix().'pur_orders.currency IN (0, '.$report_currency.')');
                }else{
                    array_push($where, 'AND '.db_prefix().'pur_orders.currency = '.$report_currency);
                }

                $currency = pur_get_currency_by_id($report_currency);
            }

            if ($this->input->post('products_services')) {
                $products_services  = $this->input->post('products_services');
                $_products_services = [];
                if (is_array($products_services)) {
                    foreach ($products_services as $product) {
                        if ($product != '') {
                            array_push($_products_services, $product);
                        }
                    }
                }
                if (count($_products_services) > 0) {
                    array_push($where, 'AND tblitems.id IN (' . implode(', ', $_products_services) . ')');
                }
            }
            
            $aColumns     = $select;
            $sIndexColumn = 'id';
            $sTable       = db_prefix() . 'pur_order_detail';
            $join         = [
                'LEFT JOIN ' . db_prefix() . 'items ON ' . db_prefix() . 'items.id = ' . db_prefix() . 'pur_order_detail.item_code',
                'LEFT JOIN ' . db_prefix() . 'pur_orders ON ' . db_prefix() . 'pur_orders.id = ' . db_prefix() . 'pur_order_detail.pur_order',
            ];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
                db_prefix() . 'items.id as item_id',
                db_prefix() . 'pur_order_detail.pur_order as po_id',
                'unit_price',
                'tax_value',
                'discount_money',
                'quantity',
                'vendor',
            ]);

            $output  = $result['output'];
            $rResult = $result['rResult'];

            $footer_data = [
                'total'           => 0,
            ];

            foreach ($rResult as $aRow) {
                $row = [];

                $row[] = '<a href="' . admin_url('purchase/items/' . $aRow['item_id']) . '" target="_blank">' . $aRow['item_code'] . '</a>';

                $row[] = $aRow['item_name'];

                $row[] = '<a href="' . admin_url('purchase/purchase_order/' . $aRow['po_id']) . '" target="_blank">' . $aRow['po_name'] . '</a>';

                $row[] = '<a href="' . admin_url('purchase/vendor/' . $aRow['vendor']) . '" >' .  get_vendor_company_name($aRow['vendor']) . '</a>';

                $row[] = app_format_money($aRow['unit_price'], $currency->name);

                $row[] = $aRow['quantity'];

                $row[] = app_format_money($aRow['tax_value'], $currency->name);

                $row[] = app_format_money($aRow['discount_money'], $currency->name);

                $row[] = app_format_money($aRow['total_money'], $currency->name);
                $footer_data['total'] += $aRow['total_money'];

                $output['aaData'][] = $row;
            }

            foreach ($footer_data as $key => $total) {
                $footer_data[$key] = app_format_money($total, $currency->name);
            }

            $output['sums'] = $footer_data;
            echo json_encode($output);
            die();
        }
    }

    /**
     * Gets the where report period.
     *
     * @param      string  $field  The field
     *
     * @return     string  The where report period.
     */
    private function get_where_report_period($field = 'date')
    {
        $months_report      = $this->input->post('report_months');
        $custom_date_select = '';
        if ($months_report != '') {
            if (is_numeric($months_report)) {
                // Last month
                if ($months_report == '1') {
                    $beginMonth = date('Y-m-01', strtotime('first day of last month'));
                    $endMonth   = date('Y-m-t', strtotime('last day of last month'));
                } else {
                    $months_report = (int) $months_report;
                    $months_report--;
                    $beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
                    $endMonth   = date('Y-m-t');
                }

                $custom_date_select = 'AND (' . $field . ' BETWEEN "' . $beginMonth . '" AND "' . $endMonth . '")';
            } elseif ($months_report == 'this_month') {
                $custom_date_select = 'AND (' . $field . ' BETWEEN "' . date('Y-m-01') . '" AND "' . date('Y-m-t') . '")';
            } elseif ($months_report == 'this_year') {
                $custom_date_select = 'AND (' . $field . ' BETWEEN "' .
                date('Y-m-d', strtotime(date('Y-01-01'))) .
                '" AND "' .
                date('Y-m-d', strtotime(date('Y-12-31'))) . '")';
            } elseif ($months_report == 'last_year') {
                $custom_date_select = 'AND (' . $field . ' BETWEEN "' .
                date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01'))) .
                '" AND "' .
                date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31'))) . '")';
            } elseif ($months_report == 'custom') {
                $from_date = to_sql_date($this->input->post('report_from'));
                $to_date   = to_sql_date($this->input->post('report_to'));
                if ($from_date == $to_date) {
                    $custom_date_select = 'AND ' . $field . ' = "' . $from_date . '"';
                } else {
                    $custom_date_select = 'AND (' . $field . ' BETWEEN "' . $from_date . '" AND "' . $to_date . '")';
                }
            }
        }

        return $custom_date_select;
    }

    /**
     * get data Purchase statistics by number of purchase orders
     * 
     * @return     json
     */
    public function number_of_purchase_orders_analysis(){
        $year_report      = $this->input->post('year');
        echo json_encode($this->purchase_model->number_of_purchase_orders_analysis($year_report));
        die();
    }

    /**
     * get data Purchase statistics by cost
     * 
     * @return     json
     */
    public function cost_of_purchase_orders_analysis(){
        $this->load->model('currencies_model');
        $year_report      = $this->input->post('year');
        $report_currency = $this->input->post('report_currency');
        $currency = pur_get_currency_by_id($report_currency);
        
        $currency_name = '';
        $currency_unit = '';
        if($currency){
            $currency_name = $currency->name;
            $currency_unit = $currency->symbol;
        }
        echo json_encode([
            'data' => $this->purchase_model->cost_of_purchase_orders_analysis($year_report, $report_currency),
            'unit' => $currency_unit,
            'name' => $currency_name,
        ]);
        die();
    }

    /**
     * get data Purchase statistics by cost
     * 
     * @return     json
     */
    public function payable_receivable_analysis(){
        $this->load->model('currencies_model');
        $year_report      = $this->input->post('year');
        $report_currency = $this->input->post('report_currency');
        $currency = pur_get_currency_by_id($report_currency);
        
        $currency_name = '';
        $currency_unit = '';
        if($currency){
            $currency_name = $currency->name;
            $currency_unit = $currency->symbol;
        }
        echo json_encode([
            'data_payable' => $this->purchase_model->payable_analysis($year_report, $report_currency),
            'data_receivable' => $this->purchase_model->receivable_analysis($year_report, $report_currency),
            'unit' => $currency_unit,
            'name' => $currency_name,
        ]);
        die();
    }

    /**
     * { table vendor contracts }
     *
     * @param      <type>  $vendor  The vendor
     */
    public function table_vendor_contracts($vendor){
        $this->app->get_table_data(module_views_path('purchase', 'contracts/table_contracts'),['vendor' => $vendor]);
    }

    /**
     * { table vendor pur order }
     *
     * @param      <type>  $vendor  The vendor
     */
    public function table_vendor_pur_order($vendor){
        $this->app->get_table_data(module_views_path('purchase', 'purchase_order/table_pur_order'),['vendor' => $vendor]);
    }

    /**
     * { delete vendor admin }
     *
     * @param      <type>  $customer_id  The customer identifier
     * @param      <type>  $staff_id     The staff identifier
     */
    public function delete_vendor_admin($customer_id, $staff_id)
    {
        if (!has_permission('purchase_vendors', '', 'create') && !has_permission('purchase_vendors', '', 'edit')) {
            access_denied('vendors');
        }

        $this->db->where('vendor_id', $customer_id);
        $this->db->where('staff_id', $staff_id);
        $this->db->delete(db_prefix().'pur_vendor_admin');
        redirect(admin_url('purchase/vendor/' . $customer_id) . '?tab=vendor_admins');
    }

/**
     * table commodity list
     * 
     * @return array
     */
    public function table_item_list()
    {
        $this->app->get_table_data(module_views_path('purchase', 'items/table_item_list'));
    }

    /**
     * item list
     * @param  integer $id 
     * @return load view
     */
    public function items($id = ''){
        $this->load->model('departments_model');
        $this->load->model('staff_model');

        
        $data['units'] = $this->purchase_model->get_unit_add_item();
        $data['taxes'] = $this->purchase_model->get_taxes();
        $data['commodity_groups'] = $this->purchase_model->get_commodity_group_add_commodity();
        $data['sub_groups'] = $this->purchase_model->get_sub_group();
        // Load purchase types for Items Type dropdown
        $this->db->select('id, CONCAT(type_code, " - ", name) as name');
        $this->db->order_by('name', 'ASC');
        $data['commodity_types'] = $this->db->get(db_prefix().'pur_request_types')->result_array();
        $data['title'] = _l('item_list');

        $data['item_id'] = $id;

        $this->load->view('items/item_list', $data);
    }

    /**
     * get item data ajax
     * @param  integer $id 
     * @return view
     */
    public function get_item_data_ajax($id){
        
        $data['id'] = $id;
        $data['item'] = $this->purchase_model->get_item($id);
        $data['item_file'] = $this->purchase_model->get_item_attachments($id);
        if(is_numeric($data['item']->from_vendor_item)){
            $data['vendor_image'] = $this->purchase_model->get_vendor_item_file($data['item']->from_vendor_item);
        }

        // Get Item Type name
        $data['item_type_name'] = '';
        if(!empty($data['item']->commodity_type)){
            $this->db->select('name, type_code');
            $this->db->where('id', $data['item']->commodity_type);
            $type_row = $this->db->get(db_prefix().'pur_request_types')->row();
            if($type_row){
                $data['item_type_name'] = $type_row->type_code . ' - ' . $type_row->name;
            }
        }

        $this->load->view('items/item_detail', $data);
    }

    /**
     * add item list
     * @param  integer $id 
     * @return redirect
     */
    public function add_item_list($id = '')
    {
        if ($this->input->post()) {
            $message          = '';
            $data             = $this->input->post();
            
            if (!$this->input->post('id')) {
           
                $mess = $this->purchase_model->add_item($data);
                if ($mess) {
                    set_alert('success',_l('added_successfully'). _l('item_list'));

                }else{
                    set_alert('warning',_l('Add_item_list_false'));
                }
                redirect(admin_url('purchase/item_list'));
               
            } else {
                $id = $data['id'];
                unset($data['id']);
                $success = $this->purchase_model->add_purchase($data, $id);
                if ($success) {
                    set_alert('success',_l('updated_successfully'). _l('item_list'));
                }else{
                    set_alert('warning',_l('updated_item_list_false'));
                }
                
                redirect(admin_url('purchase/item_list'));
            }
        }
    }

    /**
     * delete item
     * @param  integer $id 
     * @return redirect
     */
    public function delete_item($id){
        if (!$id) {
            redirect(admin_url('purchase/item_list'));
        }
        $response = $this->purchase_model->delete_item($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('item_list')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('item_list')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('item_list')));
        }
        redirect(admin_url('purchase/item_list'));
    }

    /**
     * Gets the commodity barcode.
     */
    public function get_commodity_barcode()
    {
        $commodity_barcode = $this->purchase_model->generate_commodity_barcode();

        echo json_encode([
            $commodity_barcode
        ]);
        die();
    }

    /**
     * commodity list add edit
     * @param  integer $id
     * @return json
     */
    public function commodity_list_add_edit($id=''){
        $data = $this->input->post();
        if($data){
            if(!isset($data['id'])){
                $ids = $this->purchase_model->add_commodity_one_item($data);
                if ($ids) {

                    // handle commodity list add edit file
                    $success = true;
                    $message = _l('added_successfully');
                    set_alert('success', $message);
                    /*upload multifile*/
                    echo json_encode([
                        'url'       => admin_url('purchase/items/' . $ids),
                        'commodityid' => $ids,
                    ]);
                    die;

                }
                echo json_encode([
                    'url' => admin_url('purchase/items'),
                ]);
                die;
               
            }else{
                $id = $data['id'];
                unset($data['id']);
                $success = $this->purchase_model->update_commodity_one_item($data,$id);

                /*update file*/

                if($success == true){
                    
                    $message = _l('updated_successfully');
                    set_alert('success', $message);
                }

                echo json_encode([
                    'url'       => admin_url('purchase/items/' . $id),
                    'commodityid' => $id,
                ]);
                die;

                
            }
        }


    }

    /**
     * add commodity attachment
     * @param  integer $id
     * @return json
     */
    public function add_commodity_attachment($id)
    {

        handle_item_attachments($id);
        echo json_encode([
            'url' => admin_url('purchase/items'),
        ]);
    }

    /**
     * get commodity file url 
     * @param  integer $commodity_id
     * @return json
     */
    public function get_commodity_file_url($commodity_id){
        $arr_commodity_file = $this->purchase_model->get_item_attachments($commodity_id);
        /*get images old*/
        $images_old_value='';

        $custom_fields_html = render_custom_fields('items', $commodity_id, [], ['items_pr' => true]);

        $list = $this->purchase_model->get_item_longdescriptions($commodity_id);

        if(isset($list)){
            $long_descriptions = strip_tags($list->long_description);
            $description = $list->long_description;
        }else{
            $long_descriptions = '';
            $description = '';

        }


        if(count($arr_commodity_file) > 0){
            foreach ($arr_commodity_file as $key => $value) {
                $images_old_value .='<div class="dz-preview dz-image-preview image_old'.$value["id"].'">';

                    $images_old_value .='<div class="dz-image">';
                    if(file_exists(PURCHASE_MODULE_ITEM_UPLOAD_FOLDER .$value["rel_id"].'/'.$value["file_name"])){
                        $images_old_value .='<img class="image-w-h" data-dz-thumbnail alt="'.$value["file_name"].'" src="'.site_url('modules/purchase/uploads/item_img/'.$value["rel_id"].'/'.$value["file_name"]).'">';
                    }else{
                        $images_old_value .='<img class="image-w-h" data-dz-thumbnail alt="'.$value["file_name"].'" src="'.site_url('modules/warehouse/uploads/item_img/'.$value["rel_id"].'/'.$value["file_name"]).'">';
                    }
                    $images_old_value .='</div>';

                    $images_old_value .='<div class="dz-error-mark">';
                        $images_old_value .='<a class="dz-remove" data-dz-remove>Remove file';
                        $images_old_value .='</a>';
                    $images_old_value .='</div>';

                    $images_old_value .='<div class="remove_file">';
                        $images_old_value .= '<a href="#" class="text-danger" onclick="delete_contract_attachment(this,'.$value["id"].'); return false;"><i class="fa fa fa-times"></i></a>';
                    $images_old_value .='</div>';

                $images_old_value .='</div>';
            }
        }
        
        if(isset($list->from_vendor_item) && is_numeric($list->from_vendor_item)){
            $vendor_image = $this->purchase_model->get_vendor_item_file($list->from_vendor_item);
            if(count($vendor_image) > 0){ 
                foreach ($vendor_image as $key => $value) {
                    $images_old_value .='<div class="dz-preview dz-image-preview image_old'.$value["id"].'">';

                    $images_old_value .='<div class="dz-image">';
                    if(file_exists(PURCHASE_PATH.'vendor_items/' .$list->from_vendor_item .'/'.$value['file_name'])){
                        $images_old_value .='<img class="image-w-h" data-dz-thumbnail alt="'.$value["file_name"].'" src="'.site_url('modules/purchase/uploads/vendor_items/' . $value['rel_id'] .'/'.$value['file_name']).'">';
                    }
                    $images_old_value .='</div>';

                    $images_old_value .='<div class="dz-error-mark">';
                        $images_old_value .='<a class="dz-remove" data-dz-remove>Remove file';
                        $images_old_value .='</a>';
                    $images_old_value .='</div>';

                

                $images_old_value .='</div>';
                }
            }
        }
        
              
              
            echo json_encode([
                'arr_images' => $images_old_value,
                'custom_fields_html' => $custom_fields_html,
                'description' => $description,
                'long_descriptions' => $long_descriptions,
            ]);
            die();

    }

    /**
     * delete commodity file
     * @param  integer $attachment_id
     * @return json
     */
    public function delete_commodity_file($attachment_id)
    {
        if (!has_permission('purchase_items', '', 'delete') && !is_admin()) {
            access_denied('purchase');
        }

        $file = $this->misc_model->get_file($attachment_id);
            echo json_encode([
                'success' => $this->purchase_model->delete_commodity_file($attachment_id),
            ]);
    }

    /**
     * unit type 
     * @param  integer $id 
     * @return redirect    
     */
    public function unit_type($id = '')
        {
            if ($this->input->post()) {
                $message          = '';
                $data             = $this->input->post();
                
                if (!$this->input->post('id')) {
                    $mess = $this->purchase_model->add_unit_type($data);

                    if (is_array($mess) && isset($mess['duplicates'])) {
                        set_alert('warning', pur_unit_type_duplicate_message($mess['duplicates']));
                    } elseif ($mess) {
                        set_alert('success',_l('added_successfully').' '. _l('unit_type'));

                    }else{
                        set_alert('warning',_l('Add_unit_type_false'));
                    }
                    redirect(admin_url('purchase/setting?group=units'));
                   
                } else {
                    $id = $data['id'];
                    unset($data['id']);
                    $success = $this->purchase_model->add_unit_type($data, $id);

                    if (is_array($success) && isset($success['duplicates'])) {
                        set_alert('warning', pur_unit_type_duplicate_message($success['duplicates']));
                    } elseif ($success) {
                        set_alert('success',_l('updated_successfully').' '. _l('unit_type'));
                    }else{
                        set_alert('warning',_l('updated_unit_type_false'));
                    }
                    
                    redirect(admin_url('purchase/setting?group=units'));
                }
            }
        }


        /**
         * delete unit type 
         * @param  integer $id
         * @return redirect
         */
        public function delete_unit_type($id){
            if (!$id) {
                redirect(admin_url('purchase/setting?group=units'));
            }
            $response = $this->purchase_model->delete_unit_type($id);
            if (is_array($response) && isset($response['referenced'])) {
                set_alert('warning', _l('is_referenced', _l('unit_type')));
            } elseif ($response == true) {
                set_alert('success', _l('deleted', _l('unit_type')));
            } else {
                set_alert('warning', _l('problem_deleting', _l('unit_type')));
            }
            redirect(admin_url('purchase/setting?group=units'));
        }

    /**
     * delete commodity
     * @param  integer $id 
     * @return redirect
     */
    public function delete_commodity($id){
        if (!$id) {
            redirect(admin_url('purchase/items'));
        }
        $response = $this->purchase_model->delete_commodity($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('commodity_list')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('commodity_list')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('commodity_list')));
        }
        redirect(admin_url('purchase/items'));
    }

    /**
     * Adds an expense.
     */
    public function add_expense()
    {
        if ($this->input->post()) {
            $this->load->model('expenses_model');
            $data = $this->input->post();

            if(isset($data['pur_order'])){
                $pur_order = $data['pur_order'];
                unset($data['pur_order']);
            }

            $id = $this->expenses_model->add($data);

            if ($id) {

                $this->purchase_model->mark_converted_pur_order($pur_order,$id);

                set_alert('success', _l('converted', _l('expense')));
                echo json_encode([
                    'url'       => admin_url('expenses/list_expenses/' .$id),
                    'expenseid' => $id,
                ]);
                die;

            }
        }
    }

    /**
     * Uploads an attachment.
     *
     * @param      <type>  $id     The identifier
     */
    public function upload_attachment($id)
    {
        handle_pur_vendor_attachments_upload($id);
    }

    /**
     * { function_description }
     *
     * @param      <type>  $id      The identifier
     * @param      <type>  $rel_id  The relative identifier
     */
    public function file_pur_vendor($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin']             = is_admin();
        $data['file'] = $this->purchase_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }
        $this->load->view('vendors/_file', $data);
    }

    /**
     * { delete ic attachment }
     *
     * @param      <type>  $id     The identifier
     */
    public function delete_ic_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo pur_html_entity_decode($this->purchase_model->delete_ic_attachment($id));
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /* Change client status / active / inactive */
    public function change_contact_status($id, $status)
    {
        if (has_permission('purchase_vendors', '', 'edit') || is_vendor_admin(get_user_id_by_contact_id_pur($id)) || is_admin()) {
            if ($this->input->is_ajax_request()) {
                $this->purchase_model->change_contact_status($id, $status);
            }
        }
    }

    /**
     * { vendor items }
     */
    public function vendor_items(){
        purchase_token();
        if (!has_permission('purchase_vendor_items', '', 'view') && !is_admin() && !has_permission('purchase_vendor_items', '', 'view_own')) {
            access_denied('vendor_items');
        }
        purchase_init();  
        $data['title'] = _l('vendor_items');
        $data['vendors'] = []; 

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->purchase_model->pur_get_grouped('can_be_purchased');
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        
        //$data['items_groups'] = $this->invoice_items_model->get_groups();
        $data['commodity_groups'] = $this->purchase_model->get_commodity_group_add_commodity();
        $this->load->view('vendor_items/manage', $data);
    }

    /**
     *  vendor item table
     *  
     *  @return json
     */
    public function vendor_items_table()
    {
        if ($this->input->is_ajax_request()) {
            
            $select = [
                db_prefix() . 'pur_vendor_items.id as vendor_items_id',
                db_prefix() . 'pur_vendor_items.items as items',
                db_prefix() . 'pur_vendor.company as company', 
                db_prefix() . 'pur_vendor_items.add_from as pur_vendor_items_addedfrom', 
               
            ];
            $where = [];
            

            if ($this->input->post('vendor_filter')) {
                $vendor_filter  = $this->input->post('vendor_filter');
                array_push($where, 'AND vendor IN ('. implode(',', $vendor_filter).')');
            }

            if ($this->input->post('group_items_filter')) {
                $group_items_filter  = $this->input->post('group_items_filter');
                array_push($where, 'AND group_items IN ('. implode(',', $group_items_filter).')');
            }

            if ($this->input->post('items_filter')) {
                $items_filter  = $this->input->post('items_filter');
                array_push($where, 'AND items = '.$items_filter);
            }

            if(!has_permission('purchase_vendor_items', '', 'view')){
                array_push($where, 'AND ' . db_prefix() . 'pur_vendor_items.vendor IN (SELECT vendor_id FROM ' . db_prefix() . 'pur_vendor_admin WHERE staff_id=' . get_staff_user_id() . ')');
            }

            $aColumns     = $select;
            $sIndexColumn = 'id';
            $sTable       = db_prefix() . 'pur_vendor_items';
            $join         = ['LEFT JOIN ' . db_prefix() . 'pur_vendor ON ' . db_prefix() . 'pur_vendor.userid = ' . db_prefix() . 'pur_vendor_items.vendor',
                            'LEFT JOIN ' . db_prefix() . 'items ON ' . db_prefix() . 'items.id = ' . db_prefix() . 'pur_vendor_items.items'
                        ];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where,[db_prefix() . 'pur_vendor.userid as userid','datecreate','description','commodity_code']);

            $output  = $result['output'];
            $rResult = $result['rResult'];

            $footer_data = [
                'total' => 0,
            ];

            foreach ($rResult as $aRow) {
                $row = [];

                $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['vendor_items_id'] . '"><label></label></div>';

                $row[] = '<a href="'.admin_url('purchase/vendor/'.$aRow['userid']).'">'.$aRow['company'].'</a>';

                $row[] = '<a href="'.admin_url('purchase/items/'.$aRow['items']).'" >'.$aRow['commodity_code'].' - '.$aRow['description'].'</a>';

                $row[] = _d($aRow['datecreate']);

                $options = icon_btn('purchase/delete_vendor_items/' . $aRow['vendor_items_id'], 'remove', 'btn-danger', ['title' => _l('delete')]);

                $row[] =  $options;

                $output['aaData'][] = $row;
            }

            echo json_encode($output);
            die();
        }
    }

    /**
     * new vendor items
     */
    public function new_vendor_items(){
        if (!has_permission('purchase_vendor_items', '', 'create') && !is_admin() ) {
            access_denied('vendor_items');
        }
        $this->load->model('staff_model');

        if ($this->input->post()) {
            $data                = $this->input->post();
            if (!has_permission('purchase_vendor_items', '', 'create')) {
                access_denied('vendor_items');
            }
            $success = $this->purchase_model->add_vendor_items($data);
            if ($success) {
                set_alert('success', _l('added_successfully', _l('vendor_items')));
            }
            redirect(admin_url('purchase/vendor_items'));
        }
        $data['title'] = _l('vendor_items');

        $data['vendors'] = [];
        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->purchase_model->pur_get_grouped('can_be_purchased');
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['commodity_groups'] = $this->purchase_model->get_commodity_group_add_commodity();
    
        $this->load->view('vendor_items/vendor_items', $data);
    }

    /**
     * { group item change }
     */
    public function group_it_change($group = ''){
        if($group != ''){
            $html = '';
            if (total_rows(db_prefix() . 'items', [ 'group_id' => $group ]) <= ajax_on_total_items()) {
                $list_items = $this->purchase_model->get_item_by_group($group);
                if(count($list_items) > 0){
                    foreach($list_items as $item){
                        $html .= '<option value="'.$item['id'].'" selected>'.$item['commodity_code'].' - '.$item['description'].'</option>';
                    }
                }
            }

            echo json_encode([
                'html' => $html,
            ]);
        }else{

            $html = '';
            if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
                $items = $this->purchase_model->get_item();
                if(count($items) > 0){
                    foreach($items as $it){
                        $html .= '<option value="'.$it['id'].'">'.$it['commodity_code'].' - '.$it['description'].'</option>';
                    }
                }
            }

            echo json_encode([
                'html' => $html,
            ]);
        }   

    }

    /**
     * { delete vendor item  }
     *
     * @param      <type>  $id     The identifier
     */
    public function delete_vendor_items($id){
        if (!has_permission('purchase_vendor_items', '', 'delete') && !is_admin()) {
            access_denied('vendor_items');
        }
        if (!$id) {
            redirect(admin_url('purchase/vendor_items'));
        }
        
        $success = $this->purchase_model->delete_vendor_items($id);
        if ($success == true) {
            set_alert('success', _l('deleted', _l('vendor_items')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('vendor_items')));
        }
        redirect(admin_url('purchase/vendor_items'));
    }

    /**
     * purchase delete bulk action
     * @return
     */
    public function purchase_delete_bulk_action()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }
        $total_deleted = 0;
        $total_updated = 0;
        $total_cloned = 0;
        if ($this->input->post()) {

            $ids                   = $this->input->post('ids');
            $rel_type                   = $this->input->post('rel_type');
            /*check permission*/
            switch ($rel_type) {
                case 'commodity_list':
                    if (!has_permission('purchase_items', '', 'delete') && !is_admin()) {
                        access_denied('commodity_list');
                    }
                    break;

                case 'vendors':
                    if (!has_permission('purchase_vendors', '', 'delete') && !is_admin()) {
                        access_denied('vendors');
                    }
                    break;

                case 'vendor_items':
                    if (!has_permission('purchase_vendor_items', '', 'delete') && !is_admin()) {
                        access_denied('vendor_items');
                    }
                    break;

                case 'change_item_selling_price':
                if (!has_permission('purchase_items', '', 'edit') && !is_admin()) {
                    access_denied('commodity_list');
                }
                break;

                case 'change_item_purchase_price':
                if (!has_permission('purchase_items', '', 'edit') && !is_admin()) {
                    access_denied('commodity_list');
                }
                break;
                
                default:
                    break;
            }

            /*delete data*/
            if ($this->input->post('mass_delete') && $this->input->post('mass_delete') == 'true') {
                if (is_array($ids)) {
                    foreach ($ids as $id) {

                            switch ($rel_type) {
                                case 'commodity_list':
                                    if ($this->purchase_model->delete_commodity($id)) {
                                        $total_deleted++;
                                        break;
                                    }else{
                                        break;
                                    }

                                case 'vendors':
                                    if ($this->purchase_model->delete_vendor($id)) {
                                        $total_deleted++;
                                        break;
                                    }else{
                                        break;
                                    }

                                case 'vendor_items':
                                    if ($this->purchase_model->delete_vendor_items($id)) {
                                        $total_deleted++;
                                        break;
                                    }else{
                                        break;
                                    }
                                
                                default:
                                   
                                    break;
                            }
                        }
                    }
                /*return result*/
                switch ($rel_type) {
                    case 'commodity_list':
                        set_alert('success', _l('total_commodity_list'). ": " .$total_deleted);
                        break;

                    case 'vendors':
                        set_alert('success', _l('total_vendors_list'). ": " .$total_deleted);
                        break;

                    case 'vendor_items':
                        set_alert('success', _l('total_vendor_items_list'). ": " .$total_deleted);
                        break;    
                    
                    default:
                        break;

                }
            }

            // Clone items
            if ($this->input->post('clone_items') && $this->input->post('clone_items') == 'true') {
                if (is_array($ids)) {
                    foreach ($ids as $id) {

                            switch ($rel_type) {
                                case 'commodity_list':
                                    if ($this->purchase_model->clone_item($id)) {
                                        $total_cloned++;
                                        break;
                                    }else{
                                        break;
                                    }
                                
                                default:
                                   
                                    break;
                            }
                        }
                    }
                /*return result*/
                switch ($rel_type) {
                    case 'commodity_list':
                        set_alert('success', _l('total_commodity_list'). ": " .$total_cloned);
                        break;

                    default:
                        break;

                }
            }

            // update selling price, purchase price
            if ( ($this->input->post('change_item_selling_price') ) || ($this->input->post('change_item_purchase_price') )  )  {

                if (is_array($ids)) {
                    foreach ($ids as $id) {

                        switch ($rel_type) {
                            case 'change_item_selling_price':
                            if ($this->purchase_model->commodity_udpate_profit_rate($id, $this->input->post('selling_price'), 'selling_percent' )) {
                                $total_updated++;
                                break;
                            }else{
                                break;
                            }

                            case 'change_item_purchase_price':
                            if ($this->purchase_model->commodity_udpate_profit_rate($id, $this->input->post('purchase_price'), 'purchase_percent' )) {
                                $total_updated++;
                                break;
                            }else{
                                break;
                            }
                            

                            default:

                            break;
                        }


                    }
                }

                /*return result*/
                switch ($rel_type) {
                    case 'change_item_selling_price':
                    set_alert('success', _l('total_commodity_list'). ": " .$total_updated);
                    break;

                    case 'change_item_purchase_price':
                    set_alert('success', _l('total_commodity_list'). ": " .$total_updated);
                    break;
                    

                    default:
                    break;

                }

            }


        }
    }

    /**
     * { pur order setting }
     * @return redirect
     */
    public function pur_order_setting(){
        if( !is_admin() && !has_permission('purchase_settings', '', 'edit')){
            access_denied('purchase');
        }

        if($this->input->post()){
            $data = $this->input->post();

            $data['terms_and_conditions'] = $this->input->post('terms_and_conditions', false);
            $update = $this->purchase_model->update_po_number_setting($data);

            if($update == true){
                set_alert('success', _l('updated_successfully'));
            }else{
                set_alert('warning', _l('updated_fail'));
            }

            redirect(admin_url('purchase/setting'));
        }

    }

    /**
     * { pur order setting }
     * @return redirect
     */
    public function update_order_return_setting(){
        if( !is_admin() && !has_permission('purchase_settings', '', 'edit')){
            access_denied('purchase');
        }

        if($this->input->post()){
            $data = $this->input->post();
            $update = $this->purchase_model->update_order_return_setting($data);

            if($update == true){
                set_alert('success', _l('updated_successfully'));
            }else{
                set_alert('warning', _l('updated_fail'));
            }

            redirect(admin_url('purchase/setting?group=order_return'));
        }

    }


    /**
     * Get staff members by department (AJAX)
     */
    public function get_staff_by_department($department_id = '')
    {
        if(empty($department_id)){
            $staffs = $this->staff_model->get('', ['active' => 1]);
        } else {
            $this->db->select(db_prefix().'staff.staffid, CONCAT('.db_prefix().'staff.firstname, " ", '.db_prefix().'staff.lastname) as full_name');
            $this->db->from(db_prefix().'staff');
            $this->db->join(db_prefix().'staff_departments', db_prefix().'staff_departments.staffid = '.db_prefix().'staff.staffid');
            $this->db->where(db_prefix().'staff_departments.departmentid', $department_id);
            $this->db->where(db_prefix().'staff.active', 1);
            $staffs = $this->db->get()->result_array();
        }
        echo json_encode($staffs);
        die;
    }

    public function get_html_approval_setting($id = '')
    {
        $html = '';
        $staffs = $this->staff_model->get();
        $approver = [
                0 => ['id' => 'direct_manager', 'name' => _l('direct_manager')],
                1 => ['id' => 'head_of_department', 'name' => _l('department_manager')],
                2 => ['id' => 'staff', 'name' => _l('staff')]];
        $action = [ 
                    1 => ['id' => 'approve', 'name' => _l('approve')],
                    0 => ['id' => 'sign', 'name' => _l('sign')],
                ];

        $hr_record_status = 0; 
        if(get_status_modules_pur('hr_profile') == true){
            $hr_record_status = 1;
        }
        if(is_numeric($id)){
            $approval_setting = $this->purchase_model->get_approval_setting($id);

            $setting = json_decode($approval_setting->setting);

            $approver_md = '1';
            $hide_class = 'hide';
            $staff_md = '8';
            $approver_default = 'staff';
            $staff_hide = '';
            if($hr_record_status == 1){
                $approver_md = '4';
                $staff_md = '4';
                $hide_class = '';
                $approver_default = '';
                $staff_hide = 'hide';
            }
            
            foreach ($setting as $key => $value) {

                if($value->approver == 'staff'){
                    $staff_hide = '';
                }else{
                    $staff_hide = 'hide';
                }

                $amount_value = isset($value->amount) ? $value->amount : '';

                if($key == 0){

                    $html .= '<div id="item_approve">
                                    <div class="col-md-11">
                                    <div class="col-md-'.$approver_md.' '.$hide_class.'"> '.
                                    render_select('approver['.$key.']',$approver,array('id','name'),'approver', $value->approver, array('data-id' => '0', 'required' => 'true'), [],'', 'approver_class').'
                                    </div>
                                    <div class="col-md-'.$staff_md.' '.$staff_hide.'" id="is_staff_0">
                                    '. render_select('staff['.$key.']',$staffs,array('staffid','full_name'),'staff', $value->staff).'
                                    </div>
                                    <div class="col-md-3">
                                        '. render_select('action['.$key.']',$action,array('id','name'),'action', $value->action).' 
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="amount['.$key.']">'._l('amount').'</label>
                                            <input type="number" name="amount['.$key.']" class="form-control" value="'.$amount_value.'" min="0" step="any">
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-md-1 btn_apr">
                                    <span class="pull-bot">
                                        <button name="add" class="btn new_vendor_requests btn-success" data-ticket="true" type="button"><i class="fa fa-plus"></i></button>
                                        </span>
                                  </div>
                                </div>';
                }else{
                     $html .= '<div id="item_approve">
                                    <div class="col-md-11">
                                    <div class="col-md-'.$approver_md.' '.$hide_class.'"">
                                        '.
                                    render_select('approver['.$key.']',$approver,array('id','name'),'approver', $value->approver, array('data-id' => '0', 'required' => 'true'), [],'', 'approver_class').' 
                                    </div>
                                    <div class="col-md-'.$staff_md.' '.$staff_hide.'" id="is_staff_'.$key.'">
                                        '. render_select('staff['.$key.']',$staffs,array('staffid','full_name'),'staff', $value->staff).' 
                                    </div>
                                    <div class="col-md-3">
                                        '. render_select('action['.$key.']',$action,array('id','name'),'action', $value->action).' 
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="amount['.$key.']">'._l('amount').'</label>
                                            <input type="number" name="amount['.$key.']" class="form-control" value="'.$amount_value.'" min="0" step="any">
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-md-1 btn_apr">
                                    <span class="pull-bot">
                                        <button name="add" class="btn remove_vendor_requests btn-danger" data-ticket="true" type="button"><i class="fa fa-minus"></i></button>
                                        </span>
                                  </div>
                                </div>';
                }
            }
        }else{

            $approver_md = '1';
            $hide_class = 'hide';
            $staff_md = '8';
            $approver_default = 'staff';
            $staff_hide = '';
            if($hr_record_status == 1){
                $approver_md = '4';
                $staff_md = '4';
                $hide_class = '';
                $approver_default = '';
                $staff_hide = 'hide';
            }
            $html .= '<div id="item_approve">
                        <div class="col-md-11">
                        <div class="col-md-'.$approver_md.' '.$hide_class.' "> '.
                        render_select('approver[0]',$approver,array('id','name'),'approver', $approver_default, array('data-id' => '0', 'required' => 'true'), [],'', 'approver_class').'
                        </div>
                        <div class="col-md-'.$staff_md.' '.$staff_hide.'" id="is_staff_0">
                        '. render_select('staff[0]',$staffs,array('staffid','full_name'),'staff').'
                        </div>
                        <div class="col-md-3">
                            '. render_select('action[0]',$action,array('id','name'),'action','approve').' 
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="amount[0]">'._l('amount').'</label>
                                <input type="number" name="amount[0]" class="form-control" placeholder="'._l('amount').'" min="0" step="any">
                            </div>
                        </div>
                        </div>
                        <div class="col-md-1 btn_apr">
                        <span class="pull-bot">
                            <button name="add" class="btn new_vendor_requests btn-success" data-ticket="true" type="button"><i class="fa fa-plus"></i></button>
                            </span>
                      </div>
                    </div>';
        }

        echo json_encode([
                    $html
                ]);
    }

    /**
     * commodty group type
     * @param  integer $id
     * @return redirect
     */
    public function commodity_group_type($id = '') {
        if ($this->input->post()) {
            $message = '';
            $data = $this->input->post();

            if (!$this->input->post('id')) {

                $mess = $this->purchase_model->add_commodity_group_type($data);
                if ($mess) {
                    set_alert('success', _l('added_successfully') . _l('commodity_group_type'));

                } else {
                    set_alert('warning', _l('Add_commodity_group_type_false'));
                }
                redirect(admin_url('purchase/setting?group=commodity_group'));

            } else {
                $id = $data['id'];
                unset($data['id']);
                $success = $this->purchase_model->add_commodity_group_type($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully') . _l('commodity_group_type'));
                } else {
                    set_alert('warning', _l('updated_commodity_group_type_false'));
                }

                redirect(admin_url('purchase/setting?group=commodity_group'));
            }
        }
    }

    /**
     * delete commodity group type
     * @param  integer $id
     * @return redirect
     */
    public function delete_commodity_group_type($id) {
        if (!$id) {
            redirect(admin_url('purchase/setting?group=commodity_group'));
        }
        $response = $this->purchase_model->delete_commodity_group_type($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('commodity_group_type')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('commodity_group_type')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('commodity_group_type')));
        }
        redirect(admin_url('purchase/setting?group=commodity_group'));
    }

    /**
     * sub group
     * @param  integer $id
     * @return redirect
     */
    public function sub_group($id = '') {
        if ($this->input->post()) {
            $message = '';
            $data = $this->input->post();

            if (!$this->input->post('id')) {

                $mess = $this->purchase_model->add_sub_group($data);
                if ($mess) {
                    set_alert('success', _l('added_successfully') . ' ' . _l('sub_group'));

                } else {
                    set_alert('warning', _l('Add_sub_group_false'));
                }
                redirect(admin_url('purchase/setting?group=sub_group'));

            } else {
                $id = $data['id'];
                unset($data['id']);
                $success = $this->purchase_model->add_sub_group($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully') . ' ' . _l('sub_group'));
                } else {
                    set_alert('warning', _l('updated_sub_group_false'));
                }

                redirect(admin_url('purchase/setting?group=sub_group'));
            }
        }
    }

    /**
     * delete sub group
     * @param  integer $id
     * @return redirect
     */
    public function delete_sub_group($id) {
        if (!$id) {
            redirect(admin_url('purchase/setting?group=sub_group'));
        }
        $response = $this->purchase_model->delete_sub_group($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('sub_group')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('sub_group')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('sub_group')));
        }
        redirect(admin_url('purchase/setting?group=sub_group'));
    }

     /**
     * get subgroup fill data
     * @return html 
     */
    public function get_subgroup_fill_data()
    {
        $data = $this->input->post();

        $subgroup = $this->purchase_model->list_subgroup_by_group($data['group_id']);

        echo json_encode([
        'subgroup' => $subgroup
        ]);

    }

    /**
     * { copy public link }
     *
     * @param      string  $id     The identifier
     */
    public function copy_public_link($id){
        $pur_order = $this->purchase_model->get_pur_order($id);
        $copylink = '';
        if($pur_order){
            if($pur_order->hash != '' && $pur_order->hash != null){
                $copylink = site_url('purchase/vendors_portal/pur_order/'.$id.'/'.$pur_order->hash);
            }else{
                $hash = app_generate_hash();
                $copylink = site_url('purchase/vendors_portal/pur_order/'.$id.'/'.$hash);
                $this->db->where('id',$id);
                $this->db->update(db_prefix().'pur_orders',['hash' => $hash,]);
            }
        }

        echo json_encode([
            'copylink' => $copylink,
        ]);
    }

    /**
     * { copy public link pur request }
     *
     * @param      string  $id     The identifier
     */
    public function copy_public_link_pur_request($id){
        $pur_request = $this->purchase_model->get_purchase_request($id);
        $copylink = '';
        if($pur_request){
            if($pur_request->hash != '' && $pur_request->hash != null){
                $copylink = site_url('purchase/vendors_portal/pur_request/'.$id.'/'.$pur_request->hash);
            }else{
                $hash = app_generate_hash();
                $copylink = site_url('purchase/vendors_portal/pur_request/'.$id.'/'.$hash);
                $this->db->where('id',$id);
                $this->db->update(db_prefix().'pur_request',['hash' => $hash,]);
            }
        }

        echo json_encode([
            'copylink' => $copylink,
        ]);
    }

    /**
     * { file pur vendor }
     *
     * @param       $id      The identifier
     * @param       $rel_id  The relative identifier
     */
    public function file_pur_contract($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin']             = is_admin();
        $data['file'] = $this->purchase_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }
        $this->load->view('contracts/_file', $data);
    }

    /**
     * { delete purchase contract attachment }
     *
     * @param        $id     The identifier
     */
    public function delete_pur_contract_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo pur_html_entity_decode($this->purchase_model->delete_pur_contract_attachment($id));
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /**
     * { vendor category form }
     * @return redirect
     */
    public function vendor_cate(){
        if ($this->input->post()) {
            $message = '';
            $data = $this->input->post();
            if (!$this->input->post('id')) {
                $id = $this->purchase_model->add_vendor_category($data);
                if ($id) {
                    $success = true;
                    $message = _l('added_successfully', _l('vendor_category'));
                    set_alert('success', $message);
                }
                redirect(admin_url('purchase/setting?group=vendor_category'));
            } else {
                $id = $data['id'];
                unset($data['id']);
                $success = $this->purchase_model->update_vendor_category($data, $id);
                if ($success) {
                    $message = _l('updated_successfully', _l('vendor_category'));
                    set_alert('success', $message);
                }
                redirect(admin_url('purchase/setting?group=vendor_category'));
            }
            die;
        }
    }

    /**
     * delete job_position
     * @param  integer $id
     * @return redirect
     */
    public function delete_vendor_category($id) {
        if (!$id) {
            redirect(admin_url('purchase/setting?group=vendor_category'));
        }
        $response = $this->purchase_model->delete_vendor_category($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('vendor_category')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('vendor_category')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('vendor_category')));
        }
        redirect(admin_url('purchase/setting?group=vendor_category'));
    }

    /**
     * Uploads a purchase estimate attachment.
     *
     * @param      string  $id  The purchase order
     * @return redirect
     */
    public function purchase_estimate_attachment($id){

        handle_purchase_estimate_file($id);

        redirect(admin_url('purchase/quotations/'.$id));
    }

    /**
     * { preview purchase estimate file }
     *
     * @param        $id      The identifier
     * @param        $rel_id  The relative identifier
     * @return  view
     */
    public function file_pur_estimate($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin']             = is_admin();
        $data['file'] = $this->purchase_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }
        $this->load->view('quotations/_file', $data);
    }

    /**
     * { delete purchase order attachment }
     *
     * @param      <type>  $id     The identifier
     */
    public function delete_estimate_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo pur_html_entity_decode($this->purchase_model->delete_estimate_attachment($id));
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /**
     * Determines if vendor code exists.
     */
    public function vendor_code_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if the email is the same
                $id = $this->input->post('userid');
                if ($id != '') {
                    $this->db->where('userid', $id);
                    $pur_vendor = $this->db->get(db_prefix().'pur_vendor')->row();
                    if ($pur_vendor->vendor_code == $this->input->post('vendor_code')) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('vendor_code', $this->input->post('vendor_code'));
                $total_rows = $this->db->count_all_results(db_prefix().'pur_vendor    ');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * { dpm name in pur request number }
     *
     * @param        $dpm    The dpm
     */
    public function dpm_name_in_pur_request_number($dpm){
        $this->load->model('departments_model');
        $department = $this->departments_model->get($dpm);
        $name_rs = '';
        if($department){
            $name_repl = str_replace(' ', '', $department->name);
            $name_rs = strtoupper($name_repl);
        }

        echo json_encode([
            'rs' => $name_rs,
        ]);
    }

    public function get_next_pr_number_by_type($type = ''){
        if(empty($type)){
            echo 1;
            die;
        }
        // Count existing PR codes that start with this type prefix (no dash format)
        $this->db->like('pur_rq_code', $type, 'after');
        $count = $this->db->count_all_results(db_prefix().'pur_request');
        echo $count + 1;
        die;
    }

    public function get_next_po_number_by_type($type = ''){
        if(empty($type)){
            echo 1;
            die;
        }
        // Return next PO number (type is no longer embedded in PO number)
        $next_number = get_purchase_option('next_po_number');
        echo $next_number;
        die;
    }

    public function get_next_pq_number_by_type($type = ''){
        if(empty($type)){
            echo 1;
            die;
        }
        // Count estimates where type matches
        $this->db->where('type', $type);
        $count = $this->db->count_all_results(db_prefix().'pur_estimates');
        echo $count + 1;
        die;
    }

    /**
     * { update customfield po }
     *
     * @param        $id     The identifier
     */
    public function update_customfield_po($id){
        if($this->input->post()){
            $data = $this->input->post();
            $success = $this->purchase_model->update_customfield_po($id,$data);
            if($success){
                $message = _l('updated_successfully', _l('vendor_category'));
                set_alert('success', $message);
            }
            redirect(admin_url('purchase/purchase_order/'.$id));
        }
    }

    /**
     * { po voucher }
     */
    public function po_voucher(){

        $po_voucher = $this->purchase_model->get_po_voucher_html();

        try {
            $pdf = $this->purchase_model->povoucher_pdf($po_voucher);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }
        
        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output('PO_voucher.pdf', $type);
    }


    /**
     *  po voucher report
     *  
     *  @return json
     */
    public function po_voucher_report()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('currencies_model');

            $select = [
                'pur_order_number',
                'order_date',
                'type',
                'project',
                'department',
                'vendor',
                'approve_status',
                'delivery_status',
            ];
            $where =[];
            $custom_date_select = $this->get_where_report_period(db_prefix() . 'pur_orders.order_date');
            if ($custom_date_select != '') {
                array_push($where, $custom_date_select);
            }

            

            $currency = $this->currencies_model->get_base_currency();
            $aColumns     = $select;
            $sIndexColumn = 'id';
            $sTable       = db_prefix() . 'pur_orders';
            $join         = [
                'LEFT JOIN ' . db_prefix() . 'departments ON ' . db_prefix() . 'departments.departmentid = ' . db_prefix() . 'pur_orders.department',
                'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'pur_orders.project',
                'LEFT JOIN ' . db_prefix() . 'pur_vendor ON ' . db_prefix() . 'pur_vendor.userid = ' . db_prefix() . 'pur_orders.vendor',
            ];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
                db_prefix().'pur_orders.id as id',
                db_prefix().'departments.name as department_name',
                db_prefix().'projects.name as project_name',
                db_prefix().'pur_vendor.company as vendor_name',
                'total',
            ]);

            $output  = $result['output'];
            $rResult = $result['rResult'];

            foreach ($rResult as $aRow) {
                $row = [];

                $row[] = '<a href="' . admin_url('purchase/purchase_order/' . $aRow['id']) . '" target="_blank">' . $aRow['pur_order_number'] . '</a>';

                $row[] = _d($aRow['order_date']);

                $row[] = _l($aRow['type']);

                $row[] = '<a href="'. admin_url('projects/view/'.$aRow['project']) .'" target="_blank">'.$aRow['project_name'].'</a>';

                $row[] = $aRow['department_name'];

                $row[] = '<a href="'. admin_url('purchase/vendor/'.$aRow['vendor']) .'" target="_blank">'.$aRow['vendor_name'].'</a>';

                $row[] = get_status_approve($aRow['approve_status']);

                $delivery_status = '';
                if($aRow['delivery_status'] == 0){
                    $delivery_status = '<span class="label label-danger">'._l('undelivered').'</span>';
                }elseif($aRow['delivery_status'] == 1){
                    $delivery_status = '<span class="label label-success">'._l('delivered').'</span>';
                }
                $row[] = $delivery_status;

                 $paid = $aRow['total'] - purorder_inv_left_to_pay($aRow['id']);

                $percent = 0;

                if($aRow['total'] > 0){

                    $percent = ($paid / $aRow['total'] ) * 100;

                }

                

                $row[] = '<div class="progress">

                              <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40"

                              aria-valuemin="0" aria-valuemax="100" style="width:'.round($percent).'%">

                               ' .round($percent).' % 

                              </div>

                            </div>';
              
                $output['aaData'][] = $row;
            }

            echo json_encode($output);
            die();
        }
    }

    /**
     *  po voucher report
     *  
     *  @return json
     */
    public function po_report()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('currencies_model');

            $select = [
                'pur_order_number',
                'order_date',
                'department',
                'vendor',
                'approve_status',
                'subtotal',
                'total_tax',
                'total',
            ];
            $where =[];
            $custom_date_select = $this->get_where_report_period(db_prefix() . 'pur_orders.order_date');
            if ($custom_date_select != '') {
                array_push($where, $custom_date_select);
            }

            

            $currency = $this->currencies_model->get_base_currency();

            if($this->input->post('report_currency')){
                $report_currency = $this->input->post('report_currency');
                $base_currency = get_base_currency_pur();

                if($report_currency == $base_currency->id){
                    array_push($where, 'AND '.db_prefix().'pur_orders.currency IN (0, '.$report_currency.')');
                }else{
                    array_push($where, 'AND '.db_prefix().'pur_orders.currency = '.$report_currency);
                }

                $currency = pur_get_currency_by_id($report_currency);
            }

            $aColumns     = $select;
            $sIndexColumn = 'id';
            $sTable       = db_prefix() . 'pur_orders';
            $join         = [
                'LEFT JOIN ' . db_prefix() . 'departments ON ' . db_prefix() . 'departments.departmentid = ' . db_prefix() . 'pur_orders.department',
                'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'pur_orders.project',
                'LEFT JOIN ' . db_prefix() . 'pur_vendor ON ' . db_prefix() . 'pur_vendor.userid = ' . db_prefix() . 'pur_orders.vendor',
            ];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
                db_prefix().'pur_orders.id as id',
                db_prefix().'departments.name as department_name',
                db_prefix().'projects.name as project_name',
                db_prefix().'pur_vendor.company as vendor_name',
                'total',
            ]);

            $output  = $result['output'];
            $rResult = $result['rResult'];

            $footer_data = [
                'total'           => 0,
                'total_tax'       => 0,
                'total_value'     => 0,
            ];

            foreach ($rResult as $aRow) {
                $row = [];

                $row[] = '<a href="' . admin_url('purchase/purchase_order/' . $aRow['id']) . '" target="_blank">' . $aRow['pur_order_number'] . '</a>';

                $row[] = _d($aRow['order_date']);

                $row[] = $aRow['department_name'];

                $row[] = '<a href="'. admin_url('purchase/vendor/'.$aRow['vendor']) .'" target="_blank">'.$aRow['vendor_name'].'</a>';

                $row[] = get_status_approve($aRow['approve_status']);

                $row[] = app_format_money($aRow['subtotal'], $currency->name);

                $row[] = app_format_money($aRow['total_tax'], $currency->name);

                $row[] = app_format_money($aRow['total'], $currency->name);

                $footer_data['total'] += $aRow['total'];
                $footer_data['total_tax'] += $aRow['total_tax'];
                $footer_data['total_value'] += $aRow['subtotal'];
              
                $output['aaData'][] = $row;
            }

            foreach ($footer_data as $key => $total) {
                $footer_data[$key] = app_format_money($total, $currency->name);
            }

            $output['sums'] = $footer_data;
            echo json_encode($output);
            die();
        }
    }

    /**
     *  purchase inv report
     *  
     *  @return json
     */
    public function purchase_inv_report()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('currencies_model');

            $select = [
                'invoice_number',
                'contract',
                 db_prefix().'pur_invoices.pur_order',
                'invoice_date',
                'payment_status',
                'subtotal',
                'tax',
                'total',
            ];
            $where =[];
            // Reversed invoices must not appear in the report or its footer totals
            array_push($where, 'AND '.db_prefix().'pur_invoices.is_deleted = 0');
            $custom_date_select = $this->get_where_report_period(db_prefix() . 'pur_invoices.invoice_date');
            if ($custom_date_select != '') {
                array_push($where, $custom_date_select);
            }

            

            $currency = $this->currencies_model->get_base_currency();

            if($this->input->post('report_currency')){
                $report_currency = $this->input->post('report_currency');
                $base_currency = get_base_currency_pur();

                if($report_currency == $base_currency->id){
                    array_push($where, 'AND '.db_prefix().'pur_invoices.currency IN (0, '.$report_currency.')');
                }else{
                    array_push($where, 'AND '.db_prefix().'pur_invoices.currency = '.$report_currency);
                }

                $currency = pur_get_currency_by_id($report_currency);
            }

            if($this->input->post('payment_status') && $this->input->post('payment_status') != ''){
                array_push($where, 'AND '.db_prefix().'pur_invoices.payment_status = "'.$this->input->post('payment_status').'"');
            }

            $aColumns     = $select;
            $sIndexColumn = 'id';
            $sTable       = db_prefix() . 'pur_invoices';
            $join         = [
                'LEFT JOIN '.db_prefix().'pur_contracts ON '.db_prefix().'pur_contracts.id = '.db_prefix().'pur_invoices.contract'
            ];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
                db_prefix().'pur_invoices.id as id',
                db_prefix().'pur_contracts.contract_number as contract_number',

            ]);

            $output  = $result['output'];
            $rResult = $result['rResult'];

            $footer_data = [
                'total'           => 0,
                'total_tax'       => 0,
                'total_value'     => 0,
            ];

            foreach ($rResult as $aRow) {
                $row = [];

                $row[] = '<a href="' . admin_url('purchase/purchase_invoice/' . $aRow['id']) . '" target="_blank">' . $aRow['invoice_number'] . '</a>';

                $row[] = '<a href="'.admin_url('purchase/contract/'.$aRow['contract']).'" target="_blank">'.$aRow['contract_number'].'</a>';

                $row[] = '<a href="'.admin_url('purchase/purchase_order/'.$aRow[db_prefix().'pur_invoices.pur_order']).'" target="_blank">'.get_pur_order_subject($aRow[ db_prefix().'pur_invoices.pur_order']).'</a>';

                $row[] = _d($aRow['invoice_date']);

                $class = '';
                if($aRow['payment_status'] == 'unpaid'){
                    $class = 'danger';
                }elseif($aRow['payment_status'] == 'paid'){
                    $class = 'success';
                }elseif ($aRow['payment_status'] == 'partially_paid') {
                    $class = 'warning';
                }

                $row[] = '<span class="label label-'.$class.' s-status invoice-status-3">'._l($aRow['payment_status']).'</span>';

                $row[] = app_format_money($aRow['subtotal'], $currency->name);

                $row[] = app_format_money($aRow['tax'], $currency->name);

                $row[] = app_format_money($aRow['total'], $currency->name);

                $footer_data['total'] += $aRow['total'];
                $footer_data['total_tax'] += $aRow['tax'];
                $footer_data['total_value'] += $aRow['subtotal'];
              
                $output['aaData'][] = $row;
            }

            foreach ($footer_data as $key => $total) {
                $footer_data[$key] = app_format_money($total, $currency->name);
            }

            $output['sums'] = $footer_data;
            echo json_encode($output);
            die();
        }
    }

    /**
     * { invoices }
     * @return view
     */
    public function invoices(){
        purchase_token();
        $data['title'] = _l('invoices');
        $data['contracts'] = $this->purchase_model->get_contract();
        $data['pur_orders'] = $this->purchase_model->get_list_pur_orders();
        purchase_init();
        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();
        $data['vendors'] = [];
        $this->load->view('invoices/manage',$data);
    }

    /**
     * { table purchase invoices }
     */
    public function table_pur_invoices(){
        $this->app->get_table_data(module_views_path('purchase', 'invoices/table_pur_invoices'));
    }

    /**
     * { purchase invoice }
     *
     * @param      string  $id     The identifier
     */
    public function pur_invoice($id = ''){
        if($id == ''){
            $data['title'] = _l('add_invoice');

        }else{
            $data['title'] = _l('edit_invoice');
            
        }
        $data['contracts'] = $this->purchase_model->get_contract();
        $data['taxes'] = $this->purchase_model->get_taxes();
        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $data['vendors'] = $this->purchase_model->get_vendor();
        $pur_invoice_row_template = $this->purchase_model->create_purchase_invoice_row_template();

        $data['base_currency'] = $this->currencies_model->get_base_currency();

        if($id != ''){
            $data['pur_orders'] = $this->purchase_model->get_pur_order_approved_for_inv();
            $data['pur_invoice'] = $this->purchase_model->get_pur_invoice($id);
            $data['pur_invoice_detail'] = $this->purchase_model->get_pur_invoice_detail($id);

            if($data['pur_invoice']->approval_status == 2){
                access_denied('purchase_invoice');
            }

            $currency_rate = 1;
            if($data['pur_invoice']->currency != 0 && $data['pur_invoice']->currency_rate != null){
                $currency_rate = $data['pur_invoice']->currency_rate;
            }

            $to_currency = $data['base_currency']->name;
            if($data['pur_invoice']->currency != 0 && $data['pur_invoice']->to_currency != null) {
                $to_currency = $data['pur_invoice']->to_currency;
            }

            if (count($data['pur_invoice_detail']) > 0) { 
                $index_order = 0;
                foreach ($data['pur_invoice_detail'] as $inv_detail) { 
                    $index_order++;
                    $unit_name = pur_get_unit_name($inv_detail['unit_id']);
                    $taxname = $inv_detail['tax_name'];
                    $item_name = $inv_detail['item_name'];

                    if(strlen($item_name) == 0){
                        $item_name = pur_get_item_variatiom($inv_detail['item_code']);
                    }

                    $pur_invoice_row_template .= $this->purchase_model->create_purchase_invoice_row_template('items[' . $index_order . ']',  $item_name, $inv_detail['description'], $inv_detail['quantity'], $unit_name, $inv_detail['unit_price'], $taxname, $inv_detail['item_code'], $inv_detail['unit_id'], $inv_detail['tax_rate'],  $inv_detail['total_money'], $inv_detail['discount_percent'], $inv_detail['discount_money'], $inv_detail['total'], $inv_detail['into_money'], $inv_detail['tax'], $inv_detail['tax_value'], $inv_detail['id'], true, $currency_rate, $to_currency);
                }
            }else{
                $item_name = $data['pur_invoice']->invoice_number;
                $description = $data['pur_invoice']->adminnote;
                $quantity = 1;
                $taxname = '';
                $tax_rate = 0;
                $tax = get_tax_rate_item($id);
                if($tax && !is_array($tax)){
                    $taxname = $tax->name;
                    $tax_rate = $tax->taxrate;
                }

                $total = $data['pur_invoice']->subtotal + $data['pur_invoice']->tax;
                $index = 0;

                $pur_invoice_row_template .= $this->purchase_model->create_purchase_invoice_row_template('newitems[' . $index . ']',  $item_name, $description, $quantity, '', $data['pur_invoice']->subtotal, $taxname, null, null, $tax_rate,  $data['pur_invoice']->total, 0, 0, $total, $data['pur_invoice']->subtotal , $data['pur_invoice']->tax_rate, $data['pur_invoice']->tax, '', true);
            }

        }else{
            $data['pur_orders'] = $this->purchase_model->get_pur_order_approved_for_inv();
        }

        $data['pur_invoice_row_template'] = $pur_invoice_row_template;

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->purchase_model->pur_get_grouped('can_be_purchased');
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }

        $this->load->view('invoices/pur_invoice',$data);
    }

    /**
     * { vendors change }
     */
    public function pur_vendors_change($vendor){
        $currency_id = get_vendor_currency($vendor);
        if($currency_id == 0 || $currency_id == ''){
            $currency_id = get_base_currency()->id;
        }

        $option_po = '<option value=""></option>';
        $option_ct = '<option value=""></option>';
       
        $pur_orders = $this->purchase_model->get_pur_order_approved_for_inv_by_vendor($vendor);
        foreach($pur_orders as $po){
            $option_po .= '<option value="'.$po['id'].'">'.$po['pur_order_number'].'</option>';
        }
    
        $contracts = $this->purchase_model->get_contracts_by_vendor($vendor);
        foreach($contracts as $ct){
            $option_ct .= '<option value="'.$ct['id'].'">'.$ct['contract_number'].'</option>';
        }
        

        $option_html = '';

        if(total_rows(db_prefix().'pur_vendor_items', ['vendor' => $vendor]) <= ajax_on_total_items()){
            $items = $this->purchase_model->get_items_by_vendor_variation($vendor);
            $option_html .= '<option value=""></option>';
            foreach($items as $item){
                $option_html .= '<option value="'.$item['id'].'" >'.$item['label'].'</option>';
            }
        }

        echo json_encode([
            'type' => get_purchase_option('create_invoice_by'),
            'html' => $option_ct,
            'po_html' => $option_po,
            'option_html' => $option_html,
            'currency_id' => $currency_id,
        ]);
    }

    /**
     * { pur invoice form }
     * @return redirect
     */
    public function pur_invoice_form(){
        if($this->input->post()){
            $data = $this->input->post();
            if($data['id'] == ''){
                unset($data['id']);
                $mess = $this->purchase_model->add_pur_invoice($data);
                if ($mess) {
                    handle_pur_invoice_file($mess);
                    set_alert('success', _l('added_successfully') . ' ' . _l('purchase_invoice'));

                } else {
                    set_alert('warning', _l('add_purchase_invoice_fail'));
                }
                redirect(admin_url('purchase/invoices'));
            }else{
                $id = $data['id'];
                unset($data['id']);
                handle_pur_invoice_file($id);
                $success = $this->purchase_model->update_pur_invoice($id, $data);
                if($success){
                    set_alert('success', _l('updated_successfully') . ' ' . _l('purchase_invoice'));
                }else{
                    set_alert('warning', _l('update_purchase_invoice_fail'));
                }
                redirect(admin_url('purchase/invoices'));
            }
        }
    }

    /**
     * Inventory Shipping Charges - list page
     */
    public function shipping_charges(){
        if(!has_permission('purchase_invoices', '', 'view') && !has_permission('purchase_invoices', '', 'view_own')){
            access_denied('purchase');
        }
        purchase_token();
        purchase_init();
        $data['title'] = _l('inventory_shipping_charges');
        $this->load->view('invoices/shipping_charges', $data);
    }

    /**
     * Shipping Charge Form (Create/Edit)
     */
    public function shipping_charge_form($id = ''){
        if(!has_permission('purchase_invoices', '', 'view') && !has_permission('purchase_invoices', '', 'view_own')){
            access_denied('purchase');
        }
        purchase_token();
        purchase_init();

        // Handle form submission
        if($this->input->post()){
            $post = $this->input->post();
            $edit_id = $post['id'];
            unset($post['id']);

            // Line items drive the totals. Everything is recomputed here rather than
            // trusting the figures the browser posted.
            $items       = isset($post['items']) && is_array($post['items']) ? $post['items'] : [];
            unset($post['items']);

            $lines          = [];
            $sum_amount     = 0;   // service lines only, excluding VAT
            $sum_tax        = 0;   // VAT derived from each line's VAT %
            $vat_line_total = 0;   // rows that merely restate the VAT total
            $rates_seen     = [];
            $sort           = 0;

            foreach($items as $item){
                $description = isset($item['description']) ? trim($item['description']) : '';
                $qty         = isset($item['qty'])  ? (float) $item['qty']  : 0;
                $rate        = isset($item['rate']) ? (float) $item['rate'] : 0;

                // Skip blank rows the user left behind
                if($description === '' && $qty == 0 && $rate == 0){
                    continue;
                }

                if($qty == 0){
                    $qty = 1;
                }

                // A row that restates VAT carries no VAT of its own, whatever the
                // dropdown was left on. Its select is disabled in the form so it may
                // not be posted at all.
                $is_vat_line = $this->_sc_is_vat_line($description);
                $vat_percent = $is_vat_line
                    ? 0
                    : (isset($item['vat_percent']) ? (float) $item['vat_percent'] : 0);

                $amount     = round($qty * $rate, 2);
                $tax_amount = round($amount * $vat_percent / 100, 2);

                $lines[] = [
                    'description' => $description,
                    'qty'         => $qty,
                    'rate'        => $rate,
                    'amount'      => $amount,
                    'vat_percent' => $vat_percent,
                    'tax_amount'  => $tax_amount,
                    'sort'        => $sort++,
                ];

                if($is_vat_line){
                    // Stated tax rather than a service - keep it out of the subtotal
                    $vat_line_total += $amount;
                } else {
                    $sum_amount += $amount;
                    $sum_tax    += $tax_amount;
                    $rates_seen[(string) $vat_percent] = true;
                }
            }

            // Where the bill restates VAT as its own line, that tax is already counted
            // per line above, so adding it again would double count. The stated figure
            // is only used when no service line carries a VAT % of its own.
            if(round($sum_tax, 2) == 0 && $vat_line_total > 0){
                $sum_tax = $vat_line_total;
            }

            if(count($lines) === 0){
                set_alert('warning', _l('pur_shipping_charge_no_items'));
                redirect(admin_url('purchase/shipping_charge_form' . ($edit_id ? '/' . $edit_id : '')));
            }

            $post['amount']     = round($sum_amount, 2);
            $post['tax_amount'] = round($sum_tax, 2);
            $post['total']      = round($sum_amount + $sum_tax, 2);
            // Only meaningful when every line shares one rate; mixed bills store 0
            $post['tax_percent'] = count($rates_seen) === 1 ? (float) array_key_first($rates_seen) : 0;

            // An unselected cost centre posts an empty string. Stored as NULL rather
            // than letting it become 0, so "not coded to a cost centre" stays
            // distinguishable from a real id - the same rule Expenses_model applies.
            if (array_key_exists('cost_center', $post) && !is_numeric($post['cost_center'])) {
                $post['cost_center'] = null;
            }

            if (array_key_exists('shipping_invoice_number', $post)) {
                $post['shipping_invoice_number'] = trim((string) $post['shipping_invoice_number']);
            }

            // The datepicker posts in the admin's display format, so the value has to
            // be converted before it reaches a DATE column - and validated, because
            // to_sql_date() hands back the raw input when it cannot parse it.
            if (array_key_exists('shipping_invoice_date', $post)) {
                $post['shipping_invoice_date'] = $this->_sc_normalise_date($post['shipping_invoice_date']);
            }

            // terms_template only picks which template to load into the Terms box; it
            // is not a column on the table. $post is written wholesale below, so
            // leaving it in would make the whole insert fail - and ENVIRONMENT is
            // production, where a bad query fails silently with no alert.
            unset($post['terms_template']);

            if($edit_id && is_numeric($edit_id) && $edit_id > 0){
                // Delete old accounting entries
                $this->db->where('rel_id', $edit_id);
                $this->db->where('rel_type', 'inv_shipping_charge');
                $this->db->delete(db_prefix().'acc_account_history');
                // Update
                $this->db->where('id', $edit_id);
                $this->db->update(db_prefix().'inv_shipping_charges', $post);
                $record_id = $edit_id;
                set_alert('success', _l('updated_successfully'));
            } else {
                // Generate shipping number (ISC00001)
                $last_num = $this->db->select('id')->order_by('id', 'desc')->limit(1)->get(db_prefix().'inv_shipping_charges')->row();
                $next_num = $last_num ? (int)$last_num->id + 1 : 1;
                $post['shipping_number'] = 'ISC' . str_pad($next_num, 5, '0', STR_PAD_LEFT);
                $post['date_created'] = date('Y-m-d H:i:s');
                $this->db->insert(db_prefix().'inv_shipping_charges', $post);
                $record_id = $this->db->insert_id();
                // Update number with actual ID
                $this->db->where('id', $record_id);
                $this->db->update(db_prefix().'inv_shipping_charges', ['shipping_number' => 'ISC' . str_pad($record_id, 5, '0', STR_PAD_LEFT)]);
                set_alert('success', _l('added_successfully'));
            }
            // Replace the lines wholesale - simpler and safer than diffing, and the
            // header totals above were derived from exactly this set.
            $this->db->where('shipping_charge_id', $record_id);
            $this->db->delete(db_prefix().'inv_shipping_charge_items');

            foreach($lines as $line){
                $line['shipping_charge_id'] = $record_id;
                $this->db->insert(db_prefix().'inv_shipping_charge_items', $line);
            }

            // Post accounting. The real VAT total is passed through: with mixed rates
            // across lines it cannot be derived from a single percentage. The freight
            // bill's own date is passed too, so the entries carry that date rather
            // than the day the charge happened to be keyed in.
            // The date the charge was originally raised, so re-saving a charge that has
            // no bill date keeps its entries in the period they were first posted to
            // rather than moving them to today.
            $this->db->select('date_created');
            $this->db->where('id', $record_id);
            $sc_created = $this->db->get(db_prefix().'inv_shipping_charges')->row();

            $this->_post_shipping_charge_accounting(
                $record_id,
                $post['invoice_id'],
                $post['vendor'],
                $post['amount'],
                $post['tax_percent'],
                $post['tax_amount'],
                isset($post['shipping_invoice_date']) ? $post['shipping_invoice_date'] : null,
                $sc_created ? $sc_created->date_created : null
            );
            redirect(admin_url('purchase/shipping_charges'));
        }

        $this->load->model('currencies_model');
        $data['title'] = _l('inventory_shipping_charges');
        $data['vendors'] = $this->purchase_model->get_vendor();
        $data['taxes'] = $this->db->get(db_prefix().'taxes')->result_array();
        $this->db->where('is_deleted', 0);
        $this->db->order_by('id', 'desc');
        $data['invoices'] = $this->db->get(db_prefix().'pur_invoices')->result_array();

        $data['shipping_charge_items'] = [];

        $data['debit_account_name'] = '';
        $data['debit_po_number']    = '';
        $data['debit_po_type_name'] = '';

        // Template library for the Terms & Conditions dropdown, the same list the
        // purchase order form offers
        $data['pur_terms_list'] = $this->purchase_model->get_terms();

        // Whether the selected freight vendor carries its own Terms & Conditions. When
        // it does the terms box is filled from the vendor and both the box and the
        // template dropdown are locked, matching the purchase order form.
        $data['vendor_has_own_terms'] = false;

        if(is_numeric($id)){
            $this->db->where('id', $id);
            $data['shipping_charge'] = $this->db->get(db_prefix().'inv_shipping_charges')->row();

            $this->db->where('shipping_charge_id', $id);
            $this->db->order_by('sort', 'asc');
            $this->db->order_by('id', 'asc');
            $data['shipping_charge_items'] = $this->db->get(db_prefix().'inv_shipping_charge_items')->result_array();

            // So the form shows the debit account straight away on load, without
            // waiting for the invoice-changed AJAX round trip
            if($data['shipping_charge']){
                $data['debit_account_name'] = $this->_shipping_charge_debit_account($data['shipping_charge']->invoice_id)['name'];

                $sc_debit = $this->_shipping_charge_debit_account($data['shipping_charge']->invoice_id);
                $data['debit_po_number']    = $sc_debit['po_number'];
                $data['debit_po_type_name'] = $sc_debit['po_type_name'];

                // Same for the terms lock, so an edit does not briefly show an
                // editable box before the JS catches up
                if($data['shipping_charge']->vendor > 0){
                    $this->db->select('payment_terms');
                    $this->db->where('userid', $data['shipping_charge']->vendor);
                    $sc_ven = $this->db->get(db_prefix().'pur_vendor')->row();
                    $data['vendor_has_own_terms'] = (bool) ($sc_ven && !empty($sc_ven->payment_terms));
                }
            }
        }
        $this->load->view('invoices/shipping_charge_form', $data);
    }

    /**
     * View single shipping charge (like invoice preview with line items)
     */
    /**
     * Print-friendly shipping charge: every service line, no admin chrome and no
     * paging, so the whole bill lands on paper.
     */
    public function print_shipping_charge($id){
        if(!has_permission('purchase_invoices', '', 'view') && !has_permission('purchase_invoices', '', 'view_own')){
            access_denied('purchase');
        }
        purchase_token();
        purchase_init();

        $data = $this->_shipping_charge_view_data($id);

        if($data === false){
            redirect(admin_url('purchase/shipping_charges'));
        }

        $this->load->view('invoices/print_shipping_charge', $data);
    }

    /**
     * Everything both the on-screen and the printable shipping charge need.
     * Shared so the two can never drift apart.
     *
     * @param  int $id
     * @return array|false false when the charge does not exist
     */
    private function _shipping_charge_view_data($id){
        $data = [];

        $this->db->where('id', $id);
        $data['shipping_charge'] = $this->db->get(db_prefix().'inv_shipping_charges')->row();

        if(!$data['shipping_charge']){
            return false;
        }

        // Get invoice details
        $this->db->where('id', $data['shipping_charge']->invoice_id);
        $data['invoice'] = $this->db->get(db_prefix().'pur_invoices')->row();

        // Get invoice vendor name
        $data['invoice_vendor_name'] = '';
        if($data['invoice'] && $data['invoice']->vendor > 0){
            $this->db->select('company');
            $this->db->where('userid', $data['invoice']->vendor);
            $v = $this->db->get(db_prefix().'pur_vendor')->row();
            if($v) $data['invoice_vendor_name'] = $v->company;
        }

        // Get PO number
        $data['po_number'] = '';
        if($data['invoice'] && is_numeric($data['invoice']->pur_order) && $data['invoice']->pur_order > 0){
            $this->db->select('pur_order_number');
            $this->db->where('id', $data['invoice']->pur_order);
            $po = $this->db->get(db_prefix().'pur_orders')->row();
            if($po) $data['po_number'] = $po->pur_order_number;
        }

        // Get shipping vendor name
        $data['vendor_name'] = '';
        $this->db->select('company');
        $this->db->where('userid', $data['shipping_charge']->vendor);
        $sv = $this->db->get(db_prefix().'pur_vendor')->row();
        if($sv) $data['vendor_name'] = $sv->company;

        // Get currency
        $data['currency_name'] = '';
        if($data['invoice'] && $data['invoice']->currency > 0){
            $curr = pur_get_currency_by_id($data['invoice']->currency);
            if($curr) $data['currency_name'] = $curr->name;
        } else {
            $base = get_base_currency();
            if($base) $data['currency_name'] = $base->name;
        }

        // Get invoice line items
        $data['invoice_items'] = $this->purchase_model->get_pur_invoice_detail($data['shipping_charge']->invoice_id);

        // The service lines that make up this charge
        $this->db->where('shipping_charge_id', $id);
        $this->db->order_by('sort', 'asc');
        $this->db->order_by('id', 'asc');
        $data['shipping_charge_items'] = $this->db->get(db_prefix().'inv_shipping_charge_items')->result_array();

        // The cost centre name, resolved once here instead of in each view
        $data['cost_center_name'] = '';
        if(!empty($data['shipping_charge']->cost_center)){
            $this->db->select('name');
            $this->db->where('id', $data['shipping_charge']->cost_center);
            $cc = $this->db->get(db_prefix().'cost_centers')->row();
            if($cc) $data['cost_center_name'] = $cc->name;
        }

        // The GL account the freight was debited to, plus how it was arrived at, so the
        // view and print can show the order and item type the charge is tied to
        $debit = $this->_shipping_charge_debit_account($data['shipping_charge']->invoice_id);
        $data['debit_account_name']   = $debit['name'];
        $data['debit_account_source'] = $debit['source'];
        $data['debit_po_number']      = $debit['po_number'];
        $data['debit_po_type_name']   = $debit['po_type_name'];

        $data['accounting_date'] = !empty($data['shipping_charge']->shipping_invoice_date)
            && $data['shipping_charge']->shipping_invoice_date != '0000-00-00'
                ? $data['shipping_charge']->shipping_invoice_date
                : $data['shipping_charge']->date_created;

        $data['title'] = _l('inventory_shipping_charges') . ' #' . $id;

        return $data;
    }

    public function view_shipping_charge($id){
        if(!has_permission('purchase_invoices', '', 'view') && !has_permission('purchase_invoices', '', 'view_own')){
            access_denied('purchase');
        }
        purchase_token();
        purchase_init();

        $data = $this->_shipping_charge_view_data($id);

        if($data === false){
            redirect(admin_url('purchase/shipping_charges'));
        }

        $this->load->view('invoices/view_shipping_charge', $data);
    }

    /**
     * Reverse (delete) shipping charge + remove accounting entries
     */
    public function reverse_shipping_charge($id){
        if(!has_permission('purchase_invoices', '', 'edit') && !is_admin()){
            access_denied('purchase');
        }

        // Guarded because an id of '' or a non-numeric value would be coerced to 0 by
        // MySQL and silently match nothing, reporting success for a no-op
        if(!is_numeric($id) || $id <= 0){
            set_alert('warning', _l('not_found'));
            redirect(admin_url('purchase/shipping_charges'));
        }

        $id = (int) $id;

        // Remove accounting entries
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'inv_shipping_charge');
        $this->db->delete(db_prefix().'acc_account_history');

        // Remove the service lines. There is no foreign key on this table, so without
        // this they were left behind on every reversal, orphaned against a charge id
        // that no longer exists and liable to be picked up by a later charge reusing
        // that id.
        $this->db->where('shipping_charge_id', $id);
        $this->db->delete(db_prefix().'inv_shipping_charge_items');

        // Remove record
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'inv_shipping_charges');

        set_alert('success', _l('deleted'));
        redirect(admin_url('purchase/shipping_charges'));
    }

    /**
     * AJAX: Get invoice details for shipping charges page
     */
    /**
     * AJAX: the freight vendor's own Terms & Conditions.
     *
     * The shipping charge form fills its Terms box from this and disables the
     * template dropdown when the vendor has terms of its own, the same rule the
     * purchase order form applies. When the vendor has none the dropdown is left
     * enabled so a tblpur_terms template can be picked instead.
     *
     * Deliberately narrow: get_vendor_details() returns whole blocks of vendor
     * HTML that this form has no use for.
     */
    public function get_vendor_payment_terms($vendor_id = ''){
        $terms = '';

        if(is_numeric($vendor_id) && $vendor_id > 0){
            $this->db->select('payment_terms');
            $this->db->where('userid', $vendor_id);
            $ven = $this->db->get(db_prefix().'pur_vendor')->row();

            if($ven && !empty($ven->payment_terms)){
                $terms = $ven->payment_terms;
            }
        }

        echo json_encode(['payment_terms' => $terms]);
        die;
    }

    public function get_invoice_details_for_shipping($invoice_id = ''){
        if(!is_numeric($invoice_id)){
            echo json_encode([]);
            die;
        }
        $this->db->where('id', $invoice_id);
        $inv = $this->db->get(db_prefix().'pur_invoices')->row();
        if(!$inv){
            echo json_encode([]);
            die;
        }
        $result = [
            'id'              => $inv->id,
            'invoice_number'  => $inv->invoice_number,
            'vendor'          => $inv->vendor,
            'vendor_name'     => '',
            'invoice_date'    => _d($inv->invoice_date),
            'subtotal'        => (float)$inv->subtotal,
            'tax'             => (float)$inv->tax,
            'total'           => (float)$inv->total,
            'pur_order'       => $inv->pur_order,
            'po_number'       => '',
            'currency_name'   => '',
            // So the shipping form can show which GL account the freight will be
            // debited to before the user saves
            'debit_account_name' => '',
        ];

        $debit_account                    = $this->_shipping_charge_debit_account($inv->id);
        $result['debit_account_name']     = $debit_account['name'];
        $result['debit_account_source']   = $debit_account['source'];
        $result['debit_po_number']        = $debit_account['po_number'];
        $result['debit_po_type_name']     = $debit_account['po_type_name'];
        // Get vendor name
        if($inv->vendor > 0){
            $this->db->select('company');
            $this->db->where('userid', $inv->vendor);
            $v = $this->db->get(db_prefix().'pur_vendor')->row();
            if($v) $result['vendor_name'] = $v->company;
        }
        // Get PO number
        if(is_numeric($inv->pur_order) && $inv->pur_order > 0){
            $this->db->select('pur_order_number');
            $this->db->where('id', $inv->pur_order);
            $po = $this->db->get(db_prefix().'pur_orders')->row();
            if($po) $result['po_number'] = $po->pur_order_number;
        }
        // Get currency
        if($inv->currency > 0){
            $curr = pur_get_currency_by_id($inv->currency);
            if($curr) $result['currency_name'] = $curr->name;
        } else {
            $base = get_base_currency();
            if($base) $result['currency_name'] = $base->name;
        }
        echo json_encode($result);
        die;
    }

    /**
     * AJAX: Get invoice line items as HTML table
     */
    public function get_invoice_items_html($invoice_id = ''){
        if(!is_numeric($invoice_id)){
            echo '';
            die;
        }
        $items = $this->purchase_model->get_pur_invoice_detail($invoice_id);
        if(!$items || count($items) == 0){
            echo '<p class="text-muted">No line items found.</p>';
            die;
        }

        $html = '<div class="table-responsive"><table class="table table-bordered table-striped" style="font-size:12px;">';
        $html .= '<thead><tr style="background:#f5f5f5;">';
        $html .= '<th>#</th><th>Item</th><th>Description</th><th class="text-right">Unit Price</th><th class="text-center">Qty</th><th class="text-right">Tax</th><th class="text-right">Total</th>';
        $html .= '</tr></thead><tbody>';
        $sr = 1;
        foreach($items as $item){
            $html .= '<tr>';
            $html .= '<td>' . $sr++ . '</td>';
            $html .= '<td>' . htmlspecialchars($item['item_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($item['description'] ?? '') . '</td>';
            $html .= '<td class="text-right">' . app_format_money((float)$item['unit_price'], '') . '</td>';
            $html .= '<td class="text-center">' . app_format_number((float)$item['quantity']) . '</td>';
            $html .= '<td class="text-right">' . app_format_money((float)$item['tax_value'], '') . '</td>';
            $html .= '<td class="text-right">' . app_format_money((float)$item['total_money'], '') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        echo $html;
        die;
    }

    /**
     * AJAX: DataTable for shipping charges
     */
    public function table_shipping_charges(){
        $this->app->get_table_data(module_views_path('purchase', 'invoices/table_shipping_charges'));
    }

    /**
     * AJAX: Save (Create/Update) shipping charge
     */
    public function save_shipping_charge(){
        $id         = $this->input->post('id');
        $invoice_id = $this->input->post('invoice_id');
        $vendor     = $this->input->post('vendor');
        $amount     = (float)$this->input->post('amount');
        $tax_percent= (float)$this->input->post('tax_percent');
        $total      = (float)$this->input->post('total');

        if(!is_numeric($invoice_id) || !is_numeric($vendor)){
            echo json_encode(['success' => false, 'message' => 'Invalid data.']);
            die;
        }

        $data = [
            'invoice_id'  => $invoice_id,
            'vendor'      => $vendor,
            'amount'      => $amount,
            'tax_percent' => $tax_percent,
            'total'       => $total,
        ];

        $is_update = ($id && is_numeric($id) && $id > 0);

        if($is_update){
            // Delete old accounting entries for this record before re-posting
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'inv_shipping_charge');
            $this->db->delete(db_prefix().'acc_account_history');

            $this->db->where('id', $id);
            $this->db->update(db_prefix().'inv_shipping_charges', $data);
        } else {
            $data['date_created'] = date('Y-m-d H:i:s');
            $this->db->insert(db_prefix().'inv_shipping_charges', $data);
            $id = $this->db->insert_id();
        }

        // Post double-entry accounting
        $this->_post_shipping_charge_accounting($id, $invoice_id, $vendor, $amount, $tax_percent);

        echo json_encode(['success' => true, 'message' => $is_update ? _l('updated_successfully') : _l('added_successfully')]);
        die;
    }

    /**
     * Post double-entry accounting for inventory shipping charge
     * Dr 290 (Freight) = amount | Cr Vendor AP = amount
     * Dr 124 (VAT Paid) = tax   | Cr Vendor AP = tax
     */
    /**
     * Whether a shipping charge line only restates the VAT total, as clearing bills
     * print at the end ("VAT @ 5%   53.25") so their Amount column foots to the net.
     *
     * Decided on the description alone. The VAT % dropdown is inherited when a line
     * is added, so a "VAT 5%" row often arrives carrying 5% - keying off that value
     * would wrongly make it count as a service. Matching on the description STARTING
     * with VAT keeps genuine services that merely mention it - "TRANSPORTATION
     * (VAT)", "SERVICE CHARGES (VAT)" - out of scope.
     *
     * Kept in step with sc_is_vat_line() in views/invoices/shipping_charge_form.php.
     */
    private function _sc_is_vat_line($description){
        return (bool) preg_match('/^vat(?![a-z])/i', trim((string) $description));
    }

    /**
     * Turn a posted date into a storable Y-m-d, or NULL.
     *
     * to_sql_date() cannot be trusted on its own here: when DateTime::createFromFormat
     * fails it returns the RAW INPUT rather than null or false. Written straight to a
     * DATE column MySQL coerces that to '0000-00-00', which then reads back as a date
     * that exists but is meaningless - and because this field drives the accounting
     * posting date, the entries would quietly fall back to today with nothing to show
     * why. ENVIRONMENT is production, so there is no error surfaced either.
     *
     * checkdate() also rejects real-looking but impossible dates such as 31-02-2026.
     *
     * @param  mixed $value
     * @return string|null Y-m-d, or null when blank or unparseable
     */
    private function _sc_normalise_date($value){
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // A value already in Y-m-d, e.g. one round-tripped out of the database
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
        }

        // DateTime::createFromFormat() rolls impossible dates over instead of failing:
        // "31-02-2026" quietly becomes 3 March 2026, which then passes checkdate(). A
        // typo would therefore post the freight to the wrong month with nothing to show
        // for it. getLastErrors() is the only reliable way to catch the rollover - PHP
        // raises a warning for it - so the input is validated before it is converted.
        DateTime::createFromFormat('!' . get_current_date_format(true), $value);
        $errors = DateTime::getLastErrors();   // false in PHP 8 when there is nothing to report

        if (is_array($errors) && ((int) $errors['warning_count'] > 0 || (int) $errors['error_count'] > 0)) {
            return null;
        }

        // Converted through the app's own helper so any date hooks still apply
        $sql = to_sql_date($value);

        if (!is_string($sql) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $sql, $m)) {
            return null;
        }

        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return $sql;
    }

    /**
     * Which GL account a shipping charge should be debited to.
     *
     * Freight is part of the cost of whatever was bought, so it must follow the nature
     * of that purchase - an asset, raw material, spares, a service - rather than always
     * landing on Inventory Raw Materials.
     *
     * Resolution order, most specific first:
     *
     *   1. tblpur_orders.ledger_account       the account the requester actually coded
     *                                         this order to. Explicit coding always wins.
     *   2. tblpur_request_types.ledger_account the default for the order's TYPE, matched
     *                                         on type_code. This is the same mapping the
     *                                         PO form itself uses via
     *                                         get_ledger_by_request_type(), so an Asset
     *                                         order falls to Capital Work in Progress and
     *                                         a Spares order to Inventory Utilities
     *                                         Spares, instead of both hitting 112.
     *   3. Inventory Raw Materials (112)      last resort only.
     *
     * The purchase invoice itself carries no GL column, which is why the chain goes
     * through the order: shipping charge -> pur_invoices.pur_order -> pur_orders.
     *
     * Shared by the accounting post, the form's live preview and the view/print pages,
     * so none of them can disagree about where the money went.
     *
     * @param  int $invoice_id
     * @return array{id:int,name:string,source:string,po_number:string,po_type:string,po_type_name:string}
     */
    private function _shipping_charge_debit_account($invoice_id){
        $fallback_id = 112; // Inventory Raw Materials

        $result = [
            'id'           => $fallback_id,
            'name'         => '',
            'source'       => 'default',   // 'po' | 'type' | 'default'
            'po_number'    => '',
            'po_type'      => '',
            'po_type_name' => '',
        ];

        $po = null;

        if(is_numeric($invoice_id) && $invoice_id > 0){
            $this->db->select('pur_order');
            $this->db->where('id', $invoice_id);
            $inv = $this->db->get(db_prefix().'pur_invoices')->row();

            if($inv && is_numeric($inv->pur_order) && $inv->pur_order > 0){
                $this->db->select('ledger_account, pur_order_number, type');
                $this->db->where('id', $inv->pur_order);
                $po = $this->db->get(db_prefix().'pur_orders')->row();
            }
        }

        if($po){
            $result['po_number'] = (string) $po->pur_order_number;
            $result['po_type']   = trim((string) $po->type);

            // Name the order type, so the form and the view can explain the choice
            if($result['po_type'] !== ''){
                $this->db->select('name, ledger_account');
                $this->db->where('type_code', $result['po_type']);
                $rt = $this->db->get(db_prefix().'pur_request_types')->row();

                if($rt){
                    $result['po_type_name'] = (string) $rt->name;
                }
            }

            // 1. The order's own account
            $account = $this->_sc_account_if_exists($po->ledger_account);

            if($account){
                $result['id']     = (int) $account->id;
                $result['name']   = $account->name;
                $result['source'] = 'po';

                return $result;
            }

            // 2. The default for the order's type. Several types are deliberately left
            //    unmapped (Service, Consumables, Non-Coded Materials all sit at 0), so
            //    this only fires where a real account has been configured.
            if(isset($rt)){
                $account = $this->_sc_account_if_exists($rt->ledger_account);

                if($account){
                    $result['id']     = (int) $account->id;
                    $result['name']   = $account->name;
                    $result['source'] = 'type';

                    return $result;
                }
            }
        }

        // 3. Last resort. Named too, so the form and the print can say which account.
        $fb = $this->_sc_account_if_exists($fallback_id);
        $result['name'] = $fb ? $fb->name : ('#' . $fallback_id);

        return $result;
    }

    /**
     * An account row, but only when the id is set and the account still exists.
     *
     * A deactivated account is still honoured: silently rerouting historical coding
     * elsewhere would be a worse surprise than posting to a dormant account. Several
     * request types store 0 rather than NULL for "no account", so both are rejected.
     *
     * @param  mixed $account_id
     * @return object|null
     */
    private function _sc_account_if_exists($account_id){
        if(empty($account_id) || !is_numeric($account_id) || (int) $account_id <= 0){
            return null;
        }

        $this->db->select('id, name');
        $this->db->where('id', (int) $account_id);

        return $this->db->get(db_prefix().'acc_accounts')->row();
    }

    private function _post_shipping_charge_accounting($record_id, $invoice_id, $vendor_id, $amount, $tax_percent, $tax_amount = null, $shipping_invoice_date = null, $fallback_date = null){
        if($amount <= 0) return;

        // Get vendor AP account
        $this->db->select('ledger_account, company');
        $this->db->where('userid', $vendor_id);
        $vendor = $this->db->get(db_prefix().'pur_vendor')->row();
        $vendor_ap_account = $vendor ? (int)$vendor->ledger_account : 159;
        $vendor_name = $vendor ? $vendor->company : '';

        // Get invoice number for description
        $this->db->select('invoice_number');
        $this->db->where('id', $invoice_id);
        $inv = $this->db->get(db_prefix().'pur_invoices')->row();
        $inv_number = $inv ? $inv->invoice_number : '';

        // Freight follows the GL account the purchase order was coded to, falling
        // back to Inventory Raw Materials only when the PO has none.
        $freight_account = $this->_shipping_charge_debit_account($invoice_id)['id'];
        // VAT Paid account (always 124)
        $vat_account = 124;

        // Lines can carry different VAT rates, so the caller passes the real total.
        // Fall back to the single-rate calculation for any older caller.
        $tax_amount = $tax_amount !== null
            ? round((float) $tax_amount, 2)
            : round($amount * $tax_percent / 100, 2);

        // Post on the date printed on the freight bill, not the day it was keyed in, so
        // a bill entered late still lands in the period it belongs to.
        //
        // Order: the bill date, then the date the charge was originally raised, then
        // today. The middle step matters on re-save: without it, editing an old charge
        // that predates shipping_invoice_date would drag its entries out of their
        // original period and into the current one.
        $date = null;

        foreach ([$shipping_invoice_date, $fallback_date] as $candidate) {
            if (empty($candidate) || substr((string) $candidate, 0, 10) === '0000-00-00') {
                continue;
            }
            $parsed = date_create((string) $candidate);
            if ($parsed) {
                $date = $parsed->format('Y-m-d');
                break;
            }
        }

        if ($date === null) {
            $date = date('Y-m-d');
        }
        $now = date('Y-m-d H:i:s');
        $staff = get_staff_user_id();

        $data_insert = [];

        // Entry 1: Dr Freight Expense (amount before tax)
        $data_insert[] = [
            'account'     => $freight_account,
            'split'       => $vendor_ap_account,
            'debit'       => $amount,
            'credit'      => 0,
            'description' => 'Shipping: ' . $inv_number . ' - ' . $vendor_name,
            'number'      => $inv_number,
            'vendor'      => $vendor_id,
            'date'        => $date,
            'rel_id'      => $record_id,
            'rel_type'    => 'inv_shipping_charge',
            'datecreated' => $now,
            'addedfrom'   => $staff,
            'tax'         => 0,
            'item'        => 0,
        ];

        // Entry 2: Cr Vendor AP (amount before tax)
        $data_insert[] = [
            'account'     => $vendor_ap_account,
            'split'       => $freight_account,
            'debit'       => 0,
            'credit'      => $amount,
            'description' => 'Shipping: ' . $inv_number . ' - ' . $vendor_name,
            'number'      => $inv_number,
            'vendor'      => $vendor_id,
            'date'        => $date,
            'rel_id'      => $record_id,
            'rel_type'    => 'inv_shipping_charge',
            'datecreated' => $now,
            'addedfrom'   => $staff,
            'tax'         => 0,
            'item'        => 0,
        ];

        // Entry 3: Dr VAT Paid (tax amount) - only if tax > 0
        if($tax_amount > 0){
            $data_insert[] = [
                'account'     => $vat_account,
                'split'       => $vendor_ap_account,
                'debit'       => $tax_amount,
                'credit'      => 0,
                'description' => 'Shipping VAT: ' . $inv_number . ' - ' . $vendor_name,
                'number'      => $inv_number,
                'vendor'      => $vendor_id,
                'date'        => $date,
                'rel_id'      => $record_id,
                'rel_type'    => 'inv_shipping_charge',
                'datecreated' => $now,
                'addedfrom'   => $staff,
                'tax'         => 0,
                'item'        => 0,
            ];

            // Entry 4: Cr Vendor AP (tax amount)
            $data_insert[] = [
                'account'     => $vendor_ap_account,
                'split'       => $vat_account,
                'debit'       => 0,
                'credit'      => $tax_amount,
                'description' => 'Shipping VAT: ' . $inv_number . ' - ' . $vendor_name,
                'number'      => $inv_number,
                'vendor'      => $vendor_id,
                'date'        => $date,
                'rel_id'      => $record_id,
                'rel_type'    => 'inv_shipping_charge',
                'datecreated' => $now,
                'addedfrom'   => $staff,
                'tax'         => 0,
                'item'        => 0,
            ];
        }

        if(count($data_insert) > 0){
            $this->db->insert_batch(db_prefix().'acc_account_history', $data_insert);
        }
    }

    /**
     * AJAX: Get single shipping charge for edit
     */
    public function get_shipping_charge($id = ''){
        if(!is_numeric($id)){
            echo json_encode([]);
            die;
        }
        $this->db->where('id', $id);
        $row = $this->db->get(db_prefix().'inv_shipping_charges')->row();
        echo json_encode($row ? $row : []);
        die;
    }

    /**
     * AJAX: Delete shipping charge
     */
    public function delete_shipping_charge(){
        $id = $this->input->post('id');
        if(!is_numeric($id)){
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            die;
        }
        // Remove accounting entries
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'inv_shipping_charge');
        $this->db->delete(db_prefix().'acc_account_history');

        // Remove the record
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'inv_shipping_charges');
        echo json_encode(['success' => true, 'message' => _l('deleted')]);
        die;
    }

    /**
     * AJAX: Save Inventory Shipping Charges for an invoice (old - kept for compatibility)
     */
    public function save_inv_shipping_charges(){
        $invoice_id = $this->input->post('invoice_id');
        if(!is_numeric($invoice_id)){
            echo json_encode(['success' => false, 'message' => 'Invalid invoice ID.']);
            die;
        }

        $data = [
            'inv_shipping_vendor' => $this->input->post('shipping_vendor'),
            'inv_shipping_amount' => (float)$this->input->post('shipping_amount'),
            'inv_shipping_tax'    => (float)$this->input->post('shipping_tax'),
            'inv_shipping_total'  => (float)$this->input->post('shipping_total'),
        ];

        $this->db->where('id', $invoice_id);
        $this->db->update(db_prefix().'pur_invoices', $data);

        echo json_encode(['success' => true, 'message' => 'Shipping charges saved successfully.']);
        die;
    }

    /**
     * AJAX: Get shipping data for an invoice
     */
    public function get_inv_shipping_data($invoice_id = ''){
        if(!is_numeric($invoice_id)){
            echo json_encode(['inv_shipping_vendor' => '', 'inv_shipping_amount' => 0, 'inv_shipping_tax' => 0, 'inv_shipping_total' => 0]);
            die;
        }
        $this->db->select('inv_shipping_vendor, inv_shipping_amount, inv_shipping_tax, inv_shipping_total');
        $this->db->where('id', $invoice_id);
        $row = $this->db->get(db_prefix().'pur_invoices')->row();
        if($row){
            echo json_encode($row);
        } else {
            echo json_encode(['inv_shipping_vendor' => '', 'inv_shipping_amount' => 0, 'inv_shipping_tax' => 0, 'inv_shipping_total' => 0]);
        }
        die;
    }

    /**
     * AJAX: Check if invoice can be deleted.
     * Rules:
     *  - If active (non-deleted) payments exist → blocked, cannot delete
     *  - If all payments are soft-deleted (or none) → allowed
     *  - If allowed AND shares a transaction ID with other invoices → warn about batch
     */
    public function check_invoice_batch_txn($id){
        if(!is_numeric($id)){
            echo json_encode(['can_delete' => true, 'has_batch' => false, 'count' => 0, 'message' => '']);
            die;
        }

        // Check for active (non-deleted) payments on this invoice
        $this->db->where('pur_invoice', $id);
        $this->db->where('is_deleted', 0);
        $active_payment_count = $this->db->count_all_results(db_prefix().'pur_invoice_payment');

        if($active_payment_count > 0){
            // Invoice has active payments — cannot delete
            echo json_encode([
                'can_delete' => false,
                'has_batch'  => false,
                'count'      => $active_payment_count,
                'message'    => 'Cannot delete this invoice. It has ' . $active_payment_count . ' active payment(s). Please delete the payment(s) first before deleting the invoice.',
            ]);
            die;
        }

        // No active payments — check for batch transaction ID warning
        // (look at soft-deleted payments too, to detect batch membership)
        $this->db->select('transactionid');
        $this->db->where('pur_invoice', $id);
        $this->db->where('transactionid IS NOT NULL AND transactionid != ""', null, false);
        $payments = $this->db->get(db_prefix().'pur_invoice_payment')->result();

        if(empty($payments)){
            echo json_encode(['can_delete' => true, 'has_batch' => false, 'count' => 0, 'message' => '']);
            die;
        }

        $txn_ids = array_unique(array_column((array)$payments, 'transactionid'));
        $batch_invoice_count = 0;
        $txn_list = [];

        foreach($txn_ids as $txn_id){
            $this->db->select('COUNT(DISTINCT pur_invoice) as cnt');
            $this->db->where('transactionid', $txn_id);
            $this->db->where('pur_invoice !=', $id);
            $row = $this->db->get(db_prefix().'pur_invoice_payment')->row();
            $cnt = $row ? (int)$row->cnt : 0;
            if($cnt > 0){
                $batch_invoice_count += $cnt;
                $txn_list[] = $txn_id;
            }
        }

        if($batch_invoice_count > 0){
            $msg = 'This invoice is part of a batch payment (Transaction ID: ' . implode(', ', $txn_list) . '). '
                 . $batch_invoice_count . ' other invoice(s) share the same transaction ID. '
                 . 'Only this invoice will be deleted. Continue?';
            echo json_encode([
                'can_delete'     => true,
                'has_batch'      => true,
                'count'          => $batch_invoice_count,
                'transaction_ids'=> $txn_list,
                'message'        => $msg,
            ]);
        } else {
            echo json_encode(['can_delete' => true, 'has_batch' => false, 'count' => 0, 'message' => '']);
        }
        die;
    }

    public function delete_pur_invoice($id){
        if (!$id) {
            redirect(admin_url('purchase/invoices'));
        }

        // Server-side guard: block deletion if active (non-deleted) payments exist
        $this->db->where('pur_invoice', $id);
        $this->db->where('is_deleted', 0);
        $active_payments = $this->db->count_all_results(db_prefix().'pur_invoice_payment');
        if($active_payments > 0){
            set_alert('danger', 'Cannot delete invoice. Please delete the ' . $active_payments . ' active payment(s) first.');
            redirect(admin_url('purchase/invoices'));
        }

        // Soft delete this invoice only (do NOT cascade to batch siblings)
        $this->db->where('id', $id);
        $this->db->update(db_prefix().'pur_invoices', ['is_deleted' => 1]);
        if($this->db->affected_rows() > 0){
            set_alert('success', _l('deleted', _l('purchase_invoice')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('purchase_invoice')));
        }
        redirect(admin_url('purchase/invoices'));
    }

    /**
     * { contract change }
     *
     * @param      <type>  $ct    
     */
    public function contract_change($ct){
        $contract = $this->purchase_model->get_contract($ct);
        $value = 0;
        if($contract){
            $value = $contract->contract_value;
        }

        echo json_encode([
            'value' => $value,
            'purchase_order' => $contract->pur_order,
        ]);
    }

    /**
     * Get GRNs (Goods Receipt Notes) by Purchase Order ID (AJAX)
     */
    public function get_grns_by_po($po_id = ''){
        if(empty($po_id)){
            echo json_encode(['html' => '<option value=""></option>', 'is_service' => false]);
            die;
        }

        // Check if this PO is for a service type (no GRN needed)
        $is_service = $this->purchase_model->_is_service_type_po($po_id);

        $this->db->where('pr_order_id', $po_id);
        $this->db->where('is_deleted', 0);
        $this->db->order_by('id', 'desc');
        $grns = $this->db->get(db_prefix().'goods_receipt')->result_array();

        // Get GRN IDs already linked to invoices
        $invoiced_grns = [];
        $this->db->select('grn_id');
        $this->db->where('grn_id IS NOT NULL AND grn_id != ""', null, false);
        $this->db->where('is_deleted', 0);
        $invoices = $this->db->get(db_prefix().'pur_invoices')->result_array();
        foreach($invoices as $inv){
            $ids = json_decode($inv['grn_id'], true);
            if(!is_array($ids)) $ids = explode(',', $inv['grn_id']);
            foreach($ids as $gid){
                $gid = trim($gid);
                if(is_numeric($gid)) $invoiced_grns[] = (int)$gid;
            }
        }
        
        $html = '<option value=""></option>';
        foreach($grns as $grn){
            if(in_array((int)$grn['id'], $invoiced_grns)){
                continue;
            }
            $html .= '<option value="'.$grn['id'].'">'.$grn['goods_receipt_code'].'</option>';
        }
        echo json_encode(['html' => $html, 'is_service' => $is_service]);
        die;
    }

    /**
     * Get GRNs by multiple PO IDs (AJAX)
     */
    public function get_grns_by_multi_po(){
        @ob_clean();
        $po_ids = $this->input->post('po_ids');
        if(empty($po_ids) || !is_array($po_ids)){
            echo json_encode(['html' => '']);
            die;
        }

        // Get GRN IDs already linked to invoices
        $invoiced_grns = [];
        $this->db->select('grn_id');
        $this->db->where('grn_id IS NOT NULL AND grn_id != ""', null, false);
        $this->db->where('is_deleted', 0);
        $invoices = $this->db->get(db_prefix().'pur_invoices')->result_array();
        foreach($invoices as $inv){
            $ids = json_decode($inv['grn_id'], true);
            if(!is_array($ids)) $ids = explode(',', $inv['grn_id']);
            foreach($ids as $gid){
                $gid = trim($gid);
                if(is_numeric($gid)) $invoiced_grns[] = (int)$gid;
            }
        }

        $html = '';
        foreach($po_ids as $po_id){
            $po_id = (int)$po_id;
            if($po_id <= 0) continue;

            $this->db->where('pr_order_id', $po_id);
            $this->db->where('is_deleted', 0);
            $this->db->order_by('id', 'desc');
            $grns = $this->db->get(db_prefix().'goods_receipt')->result_array();

            // Get PO number for grouping
            $this->db->select('pur_order_number');
            $this->db->where('id', $po_id);
            $po_row = $this->db->get(db_prefix().'pur_orders')->row();
            $po_label = $po_row ? $po_row->pur_order_number : 'PO#'.$po_id;

            foreach($grns as $grn){
                if(in_array((int)$grn['id'], $invoiced_grns)) continue;
                $html .= '<option value="'.$grn['id'].'">'.$grn['goods_receipt_code'].' ('.$po_label.')</option>';
            }
        }

        echo json_encode(['html' => $html]);
        die;
    }

    /**
     * Get GRN detail for multiple GRNs and populate invoice items (AJAX)
     */
    public function grn_change_multi(){
        @ob_clean();
        $grn_ids = $this->input->post('grn_ids');
        if(empty($grn_ids) || !is_array($grn_ids)){
            echo json_encode(['list_item' => '']);
            die;
        }

        $list_item = $this->purchase_model->create_purchase_invoice_row_template();
        $base_currency = get_base_currency();
        $currency_rate = 1;
        $to_currency = $base_currency->id;
        $index = 0;

        foreach($grn_ids as $grn_id){
            if(!is_numeric($grn_id)) continue;

            // Get GRN header
            $this->db->where('id', $grn_id);
            $grn = $this->db->get(db_prefix().'goods_receipt')->row();
            if(!$grn) continue;

            // Get currency from PO (use first GRN's PO for currency)
            if($grn->pr_order_id && $index == 0){
                $pur_order = $this->purchase_model->get_pur_order($grn->pr_order_id);
                if($pur_order && $pur_order->currency != 0 && $pur_order->currency_rate != null){
                    $currency_rate = $pur_order->currency_rate;
                    $to_currency = $pur_order->currency;
                }
            }

            // Get GRN details
            $this->db->where('goods_receipt_id', $grn_id);
            $grn_details = $this->db->get(db_prefix().'goods_receipt_detail')->result_array();

            // Pre-load PO detail items for matching by position
            $_po_items_for_inv = [];
            if($grn && isset($grn->pr_order_id) && is_numeric($grn->pr_order_id) && $grn->pr_order_id > 0){
                $this->db->where('pur_order', $grn->pr_order_id);
                $this->db->order_by('id', 'asc');
                $_po_items_for_inv = $this->db->get(db_prefix().'pur_order_detail')->result_array();
            }

            foreach($grn_details as $item_idx => $item){
                $index++;
                $unit_name = '';
                if(is_numeric($item['unit_id'])){
                    $unit_name = pur_get_unit_name($item['unit_id']);
                }

                // Get item name: prefer PO detail item_name (by position), then GRN commodity_name
                $item_name = '';
                $inv_item_desc = '';
                if(isset($_po_items_for_inv[$item_idx])){
                    $po_item_row = $_po_items_for_inv[$item_idx];
                    if(!empty($po_item_row['item_name'])) $item_name = $po_item_row['item_name'];
                    if(!empty($po_item_row['description'])) $inv_item_desc = $po_item_row['description'];
                }
                if(empty($item_name)){
                    $item_name = isset($item['commodity_name']) ? $item['commodity_name'] : '';
                }
                if(empty($item_name)){
                    $item_name = pur_get_item_variatiom($item['commodity_code']);
                }
                // Fallback description from items table
                if(empty($inv_item_desc)){
                    $_itm = get_commodity_name($item['commodity_code']);
                    if($_itm && !empty($_itm->long_description)) $inv_item_desc = $_itm->long_description;
                }

                $quantities = (float)$item['quantities'];
                $unit_price = (float)$item['unit_price'];
                $received_qty = isset($item['received_qty']) ? (float)$item['received_qty'] : $quantities;
                $pending_qty = isset($item['pending_qty']) ? (float)$item['pending_qty'] : 0;

                // Use GRN values directly (same as single grn_change)
                $into_money = $received_qty * $unit_price;
                $tax_value = isset($item['tax_money']) ? (float)$item['tax_money'] : 0;
                $total = $into_money + $tax_value;
                $total_money = $total;

                $taxname = isset($item['tax_name']) ? $item['tax_name'] : '';
                $tax_rate = isset($item['tax_rate']) ? $item['tax_rate'] : '';
                $tax_id = isset($item['tax']) ? $item['tax'] : '';

                // Set received/pending for display
                $this->purchase_model->_inv_row_received_qty = $received_qty;
                $this->purchase_model->_inv_row_pending_qty = $pending_qty;

                $list_item .= $this->purchase_model->create_purchase_invoice_row_template('newitems[' . $index . ']', $item_name, $inv_item_desc, $received_qty, $unit_name, $unit_price, $taxname, $item['commodity_code'], $item['unit_id'], $tax_rate, $total_money, 0, 0, $total, $into_money, $tax_id, $tax_value, $index, true, $currency_rate, $to_currency);

                $this->purchase_model->_inv_row_received_qty = '';
                $this->purchase_model->_inv_row_pending_qty = '';
            }
        }

        echo json_encode([
            'list_item' => $list_item,
            'currency' => $to_currency,
            'currency_rate' => $currency_rate,
        ]);
        die;
    }

    /**
     * Get GRN detail and populate invoice items (AJAX)
     */
    public function grn_change($grn_id = ''){
        if(empty($grn_id)){
            echo json_encode(['list_item' => '']);
            die;
        }

        // Get GRN details
        $this->db->where('goods_receipt_id', $grn_id);
        $grn_details = $this->db->get(db_prefix().'goods_receipt_detail')->result_array();

        // Get GRN header for currency info
        $this->db->where('id', $grn_id);
        $grn = $this->db->get(db_prefix().'goods_receipt')->row();

        $list_item = $this->purchase_model->create_purchase_invoice_row_template();

        $base_currency = get_base_currency();
        $currency_rate = 1;
        $to_currency = $base_currency->id;

        if($grn && $grn->pr_order_id){
            $pur_order = $this->purchase_model->get_pur_order($grn->pr_order_id);
            if($pur_order && $pur_order->currency != 0 && $pur_order->currency_rate != null){
                $currency_rate = $pur_order->currency_rate;
                $to_currency = $pur_order->currency;
            }
        }

        if(count($grn_details) > 0){
            $index = 0;
            foreach($grn_details as $item){
                $index++;
                $unit_name = '';
                if(is_numeric($item['unit_id'])){
                    $unit_name = pur_get_unit_name($item['unit_id']);
                }
                $item_name = isset($item['commodity_name']) ? $item['commodity_name'] : '';
                if(strlen($item_name) == 0){
                    $item_name = pur_get_item_variatiom($item['commodity_code']);
                }

                $quantities = (float)$item['quantities'];
                $unit_price = (float)$item['unit_price'];
                $received_qty = isset($item['received_qty']) ? (float)$item['received_qty'] : $quantities;
                $pending_qty = isset($item['pending_qty']) ? (float)$item['pending_qty'] : 0;

                // Calculate amount based on received qty
                $into_money = $received_qty * $unit_price;
                $tax_value = isset($item['tax_money']) ? (float)$item['tax_money'] : 0;
                $total = $into_money + $tax_value;
                $total_money = $total;

                $taxname = isset($item['tax_name']) ? $item['tax_name'] : '';
                $tax_rate = isset($item['tax_rate']) ? $item['tax_rate'] : '';
                $tax_id = isset($item['tax']) ? $item['tax'] : '';

                // Set received/pending for display
                $this->purchase_model->_inv_row_received_qty = $received_qty;
                $this->purchase_model->_inv_row_pending_qty = $pending_qty;

                // Get item description from PO detail
                $inv_item_desc2 = '';
                if(isset($grn->pr_order_id) && is_numeric($grn->pr_order_id)){
                    $this->db->select('description');
                    $this->db->where('pur_order', $grn->pr_order_id);
                    $this->db->where('item_code', $item['commodity_code']);
                    $_pod2 = $this->db->get(db_prefix().'pur_order_detail')->row();
                    if($_pod2 && !empty($_pod2->description)) $inv_item_desc2 = $_pod2->description;
                }
                if(empty($inv_item_desc2)){
                    $_itm2 = get_commodity_name($item['commodity_code']);
                    if($_itm2 && !empty($_itm2->long_description)) $inv_item_desc2 = $_itm2->long_description;
                }

                $list_item .= $this->purchase_model->create_purchase_invoice_row_template('newitems[' . $index . ']', $item_name, $inv_item_desc2, $received_qty, $unit_name, $unit_price, $taxname, $item['commodity_code'], $item['unit_id'], $tax_rate, $total_money, 0, 0, $total, $into_money, $tax_id, $tax_value, $index, true, $currency_rate, $to_currency);

                $this->purchase_model->_inv_row_received_qty = '';
                $this->purchase_model->_inv_row_pending_qty = '';
            }
        }

        echo json_encode([
            'list_item' => $list_item,
            'currency' => $to_currency,
            'currency_rate' => $currency_rate,
        ]);
        die;
    }

    /**
     * { purchase order change }
     *
     * @param      <type>  $ct    
     */
    public function pur_order_change($ct){
        $pur_order = $this->purchase_model->get_pur_order($ct);
        $pur_order_detail = $this->purchase_model->get_pur_order_detail($ct);
        
        $list_item = $this->purchase_model->create_purchase_invoice_row_template();
        $discount_percent = 0;

        $base_currency = get_base_currency();

        $currency_rate = 1;
        $to_currency = $base_currency->id;
        if($pur_order->currency != 0 && $pur_order->currency_rate != null){
            $currency_rate = $pur_order->currency_rate;
            $to_currency = $pur_order->currency;
        }

        if(count($pur_order_detail) > 0){
            $index = 0;
            foreach($pur_order_detail as $key => $item){
                $index++;
                $unit_name = pur_get_unit_name($item['unit_id']);
                $taxname = $item['tax_name'];
                $item_name = $item['item_name'];
                if(strlen($item_name) == 0){
                    $item_name = pur_get_item_variatiom($item['item_code']);
                }

                // Get received qty from goods_receipt_history_detail for this PO item
                $this->db->select('SUM('.db_prefix().'goods_receipt_history_detail.received_qty) as total_received');
                $this->db->from(db_prefix().'goods_receipt_history_detail');
                $this->db->join(db_prefix().'goods_receipt_history', db_prefix().'goods_receipt_history.id = '.db_prefix().'goods_receipt_history_detail.history_id');
                $this->db->where(db_prefix().'goods_receipt_history.pr_order_id', $ct);
                $this->db->where(db_prefix().'goods_receipt_history_detail.commodity_code', $item['item_code']);
                $received_row = $this->db->get()->row();
                
                $item_received = ($received_row && $received_row->total_received) ? (float)$received_row->total_received : 0;
                $item_pending = (float)$item['quantity'] - $item_received;
                if($item_pending < 0) $item_pending = 0;
                $this->purchase_model->_inv_row_received_qty = $item_received;
                $this->purchase_model->_inv_row_pending_qty = $item_pending;

                $list_item .= $this->purchase_model->create_purchase_invoice_row_template('newitems[' . $index . ']',  $item_name, $item['description'], $item['quantity'], $unit_name, $item['unit_price'], $taxname, $item['item_code'], $item['unit_id'], $item['tax_rate'],  $item['total_money'], $item['discount_%'], $item['discount_money'], $item['total'], $item['into_money'], $item['tax'], $item['tax_value'], $index, true, $currency_rate, $to_currency);

                // Reset
                $this->purchase_model->_inv_row_received_qty = '';
                $this->purchase_model->_inv_row_pending_qty = '';
            }
        }

        $discount_type = 'after_tax';
        if($pur_order){
            $discount_percent = $pur_order->discount_percent;
            $discount_type = $pur_order->discount_type;
        }

        // Get vendor info
        $vendor_id = isset($pur_order->vendor) ? $pur_order->vendor : '';
        $vendor_name = '';
        if(is_numeric($vendor_id) && $vendor_id > 0){
            $vendor_data = $this->purchase_model->get_vendor($vendor_id);
            if($vendor_data){
                $vendor_name = $vendor_data->company;
            }
        }

        echo json_encode([
            'discount_type' => $discount_type,
            'list_item' => $list_item,
            'discount_percent' => $discount_percent,
            'currency' => $to_currency,
            'currency_rate' => $currency_rate,
            'shipping_fee' => $pur_order->shipping_fee,
            'order_discount' => $pur_order->discount_total,
            'vendor_id' => $vendor_id,
            'vendor_name' => $vendor_name,
        ]);
    }

    /**
     * Get PO items for invoice - used for service type POs that don't have GRN
     */
    public function get_po_items_for_invoice($po_id = ''){
        if(empty($po_id)){
            echo json_encode(['list_item' => '']);
            die;
        }

        $pur_order = $this->purchase_model->get_pur_order($po_id);
        $pur_order_detail = $this->purchase_model->get_pur_order_detail($po_id);

        $list_item = '';
        $currency_rate = 1;
        $to_currency = get_base_currency()->id;
        if($pur_order && $pur_order->currency != 0 && $pur_order->currency_rate != null){
            $currency_rate = $pur_order->currency_rate;
            $to_currency = $pur_order->currency;
        }

        if(count($pur_order_detail) > 0){
            $index = 0;
            foreach($pur_order_detail as $key => $item){
                $index++;
                $unit_name = pur_get_unit_name($item['unit_id']);
                $taxname = $item['tax_name'];
                $item_name = $item['item_name'];
                if(strlen($item_name) == 0){
                    $item_name = pur_get_item_variatiom($item['item_code']);
                }

                $list_item .= $this->purchase_model->create_purchase_invoice_row_template('newitems[' . $index . ']', $item_name, $item['description'], $item['quantity'], $unit_name, $item['unit_price'], $taxname, $item['item_code'], $item['unit_id'], $item['tax_rate'], $item['total_money'], $item['discount_%'], $item['discount_money'], $item['total'], $item['into_money'], $item['tax'], $item['tax_value'], $index, true, $currency_rate, $to_currency);
            }
        }

        echo json_encode(['list_item' => $list_item]);
        die;
    }

    /**
     * Batch print - search invoices by transaction ID
     */
    public function batch_print_search(){
        $txn_id = $this->input->post('transaction_id');
        if(empty($txn_id)){
            echo json_encode(['html' => '<p class="text-muted text-center">'._l('no_results_found').'</p>', 'count' => 0]);
            return;
        }

        // Find invoices that have payments with this transaction ID
        $this->db->select(db_prefix().'pur_invoices.*, '.db_prefix().'pur_invoice_payment.transactionid as pay_txn, '.db_prefix().'pur_invoice_payment.amount as pay_amount, '.db_prefix().'pur_invoice_payment.date as pay_date');
        $this->db->from(db_prefix().'pur_invoices');
        $this->db->join(db_prefix().'pur_invoice_payment', db_prefix().'pur_invoice_payment.pur_invoice = '.db_prefix().'pur_invoices.id');
        $this->db->where(db_prefix().'pur_invoice_payment.transactionid', $txn_id);
        $this->db->where(db_prefix().'pur_invoices.is_deleted', 0);
        $invoices = $this->db->get()->result();

        // Also check invoices table transactionid
        if(count($invoices) == 0){
            $this->db->where('transactionid', $txn_id);
            $this->db->where('is_deleted', 0);
            $invoices = $this->db->get(db_prefix().'pur_invoices')->result();
        }

        $html = '';
        if(count($invoices) > 0){
            $html .= '<table class="table table-bordered table-striped"><thead><tr>';
            $html .= '<th>'._l('invoice_code').'</th><th>'._l('pur_vendor').'</th><th>'._l('invoice_date').'</th><th>'._l('invoice_amount').'</th><th>'._l('debit_note').'</th><th>'._l('payment_status').'</th>';
            $html .= '</tr></thead><tbody>';
            foreach($invoices as $inv){
                $vendor_name = get_vendor_company_name($inv->vendor);
                $base_currency = get_base_currency_pur();
                if($inv->currency != 0) $base_currency = pur_get_currency_by_id($inv->currency);
                
                // Get debit notes applied to this invoice
                $this->db->select(db_prefix().'pur_debits.amount, '.db_prefix().'pur_debit_notes.prefix, '.db_prefix().'pur_debit_notes.number');
                $this->db->from(db_prefix().'pur_debits');
                $this->db->join(db_prefix().'pur_debit_notes', db_prefix().'pur_debit_notes.id = '.db_prefix().'pur_debits.debit_id');
                $this->db->where(db_prefix().'pur_debits.invoice_id', $inv->id);
                $dn_applied = $this->db->get()->result();
                $dn_info = '-';
                if(count($dn_applied) > 0){
                    $dn_parts = [];
                    foreach($dn_applied as $da){
                        $dn_num = $da->prefix . str_pad($da->number, 5, '0', STR_PAD_LEFT);
                        $dn_parts[] = $dn_num . ' (' . app_format_money($da->amount, $base_currency->name) . ')';
                    }
                    $dn_info = implode('<br>', $dn_parts);
                }
                
                $html .= '<tr>';
                $html .= '<td>'.$inv->invoice_number.'</td>';
                $html .= '<td>'.$vendor_name.'</td>';
                $html .= '<td>'._d($inv->invoice_date).'</td>';
                $html .= '<td>'.app_format_money($inv->total, $base_currency->name).'</td>';
                $html .= '<td>'.$dn_info.'</td>';
                $html .= '<td>'._l($inv->payment_status).'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html = '<p class="text-muted text-center">'._l('no_results_found').'</p>';
        }

        echo json_encode(['html' => $html, 'count' => count($invoices)]);
    }

    /**
     * Batch print PDF - generate PDF for all invoices with same transaction ID
     */
    public function batch_print_pdf(){
        // Clean any prior output to prevent TCPDF error
        if(ob_get_level()) ob_end_clean();

        $txn_id = $this->input->get('transaction_id');
        if(empty($txn_id)){
            redirect(admin_url('purchase/invoices'));
        }

        // Find invoices
        $this->db->select(db_prefix().'pur_invoices.*');
        $this->db->from(db_prefix().'pur_invoices');
        $this->db->join(db_prefix().'pur_invoice_payment', db_prefix().'pur_invoice_payment.pur_invoice = '.db_prefix().'pur_invoices.id');
        $this->db->where(db_prefix().'pur_invoice_payment.transactionid', $txn_id);
        $this->db->where(db_prefix().'pur_invoices.is_deleted', 0);
        $this->db->group_by(db_prefix().'pur_invoices.id');
        $invoices = $this->db->get()->result();

        if(count($invoices) == 0){
            $this->db->where('transactionid', $txn_id);
            $this->db->where('is_deleted', 0);
            $invoices = $this->db->get(db_prefix().'pur_invoices')->result();
        }

        if(count($invoices) == 0){
            set_alert('warning', _l('no_results_found'));
            redirect(admin_url('purchase/invoices'));
        }

        // Get vendor details
        $vendor = $this->purchase_model->get_vendor($invoices[0]->vendor);
        $base_currency = get_base_currency_pur();
        if($invoices[0]->currency != 0) $base_currency = pur_get_currency_by_id($invoices[0]->currency);

        // Get company info
        $company_name = get_option('invoice_company_name');

        // Build HTML for PDF
        $html = '<div style="text-align:center;margin-bottom:10px;">';
        $html .= '<h2>'.$company_name.'</h2>';
        $html .= '<h3>'._l('batch_payments').'</h3>';
        $html .= '<p><strong>'._l('transaction_id').':</strong> '.htmlspecialchars($txn_id).'</p>';
        $html .= '</div>';

        if($vendor){
            $html .= '<table width="100%" cellpadding="3"><tr>';
            $html .= '<td width="50%"><strong>'._l('pur_vendor').':</strong> '.$vendor->company;
            if(isset($vendor->address) && $vendor->address != '') $html .= '<br>'.$vendor->address;
            if(isset($vendor->phonenumber) && $vendor->phonenumber != '') $html .= '<br>'.$vendor->phonenumber;
            $html .= '</td>';
            $html .= '<td width="50%" align="right"><strong>'._l('date').':</strong> '._d(date('Y-m-d')).'</td>';
            $html .= '</tr></table><br>';
        }

        // Fetch payment record(s) for this transaction ID to get payment mode & cheque details
        $this->db->where('transactionid', $txn_id);
        $this->db->where('is_deleted', 0);
        $this->db->order_by('id', 'asc');
        $txn_payments = $this->db->get(db_prefix().'pur_invoice_payment')->result_array();

        // Use first payment as representative for payment mode & cheque details
        $txn_payment_mode_name = '';
        $txn_payment_mode_id   = '';
        $txn_cheque_bank       = '';
        $txn_cheque_no         = '';
        $txn_cheque_date       = '';
        $txn_cheque_attachment = '';
        $txn_payment_id        = '';
        if(count($txn_payments) > 0){
            $first_pay = $txn_payments[0];
            $txn_payment_id = $first_pay['id'];
            if(!empty($first_pay['paymentmode'])){
                $this->db->where('id', $first_pay['paymentmode']);
                $pm_row = $this->db->get(db_prefix().'payment_modes')->row();
                if($pm_row) $txn_payment_mode_name = $pm_row->name;
            }
            $txn_cheque_bank       = $first_pay['cheque_bank_name'] ?? '';
            $txn_cheque_no         = $first_pay['cheque_number'] ?? '';
            $txn_cheque_date       = $first_pay['cheque_date'] ?? '';
            $txn_cheque_attachment = $first_pay['cheque_attachment'] ?? '';
        }

        $is_cheque = (stripos($txn_payment_mode_name, 'cheque') !== false || stripos($txn_payment_mode_name, 'check') !== false);

        // ── Payment info block (always shown) ────────────────────────────────
        $html .= '<table width="100%" cellpadding="4" style="margin-bottom:6px;border-bottom:1px solid #ccc;">';
        $html .= '<tr>';
        $html .= '<td width="50%"><strong>'._l('payment_mode').':</strong> '.htmlspecialchars($txn_payment_mode_name).'</td>';
        $html .= '<td width="50%" align="right"><strong>'._l('transaction_id').':</strong> '.htmlspecialchars($txn_id).'</td>';
        $html .= '</tr>';
        $html .= '</table>';

        // ── Cheque details block (shown once at top, only when cheque mode) ──
        if($is_cheque){
            $html .= '<table width="100%" cellpadding="4" cellspacing="0" style="border:1px solid #aaa;margin-bottom:12px;">';
            $html .= '<tr style="background:#ddeeff;">';
            $html .= '<td colspan="3" style="padding:5px 7px;"><strong><i>'._l('cheque_details').'</i></strong></td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td width="33%" style="padding:4px 7px;border-top:1px solid #ccc;">';
            $html .= '<strong>'._l('cheque_bank_name').':</strong><br>'.htmlspecialchars($txn_cheque_bank ?: '-');
            $html .= '</td>';
            $html .= '<td width="33%" style="padding:4px 7px;border-top:1px solid #ccc;">';
            $html .= '<strong>'._l('cheque_number').':</strong><br>'.htmlspecialchars($txn_cheque_no ?: '-');
            $html .= '</td>';
            $html .= '<td width="34%" style="padding:4px 7px;border-top:1px solid #ccc;">';
            $html .= '<strong>'._l('cheque_date').':</strong><br>'.(!empty($txn_cheque_date) ? _d($txn_cheque_date) : '-');
            $html .= '</td>';
            $html .= '</tr>';
            // Attachment note (can't embed file in PDF, just note its presence)
            if(!empty($txn_cheque_attachment)){
                $html .= '<tr>';
                $html .= '<td colspan="3" style="padding:4px 7px;border-top:1px solid #ccc;">';
                $html .= '<strong>'._l('cheque_attachment').':</strong> '.htmlspecialchars($txn_cheque_attachment).' &#10003;';
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        // Summary table
        $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
        $html .= '<tr style="background-color:#f0f0f0;">';
        $html .= '<td width="5%" align="center"><strong>#</strong></td>';
        $html .= '<td width="20%" align="center"><strong>'._l('invoice_code').'</strong></td>';
        $html .= '<td width="10%" align="center"><strong>'._l('invoice_date').'</strong></td>';
        $html .= '<td width="10%" align="center"><strong>'._l('pur_due_date').'</strong></td>';
        $html .= '<td width="13%" align="right"><strong>'._l('invoice_amount').'</strong></td>';
        $html .= '<td width="13%" align="right"><strong>'._l('paid').'</strong></td>';
        $html .= '<td width="17%" align="center"><strong>'._l('debit_note').'</strong></td>';
        $html .= '<td width="12%" align="center"><strong>'._l('payment_status').'</strong></td>';
        $html .= '</tr>';

        $total_amount = 0;
        $total_paid = 0;
        $i = 1;
        foreach($invoices as $inv){
            // Get paid amount for this invoice under this transaction
            $this->db->select_sum('amount');
            $this->db->where('pur_invoice', $inv->id);
            $this->db->where('transactionid', $txn_id);
            $this->db->where('is_deleted', 0);
            $paid_row = $this->db->get(db_prefix().'pur_invoice_payment')->row();
            $paid_amount = ($paid_row && $paid_row->amount) ? (float)$paid_row->amount : 0;

            // Get debit note applied to this invoice
            $this->db->select(db_prefix().'pur_debits.amount, '.db_prefix().'pur_debit_notes.prefix, '.db_prefix().'pur_debit_notes.number');
            $this->db->from(db_prefix().'pur_debits');
            $this->db->join(db_prefix().'pur_debit_notes', db_prefix().'pur_debit_notes.id = '.db_prefix().'pur_debits.debit_id');
            $this->db->where(db_prefix().'pur_debits.invoice_id', $inv->id);
            $debit_applied = $this->db->get()->result();

            $debit_info = '';
            $debit_total = 0;
            foreach($debit_applied as $da){
                $dn_number = $da->prefix . str_pad($da->number, 5, '0', STR_PAD_LEFT);
                $debit_info .= $dn_number . ' (' . app_format_money($da->amount, $base_currency->name) . ')<br>';
                $debit_total += (float)$da->amount;
            }

            $html .= '<tr>';
            $html .= '<td width="5%" align="center">'.$i.'</td>';
            $html .= '<td width="20%">'.$inv->invoice_number.'</td>';
            $html .= '<td width="10%" align="center">'._d($inv->invoice_date).'</td>';
            $html .= '<td width="10%" align="center">'._d($inv->duedate).'</td>';
            $html .= '<td width="13%" align="right">'.app_format_money($inv->total, $base_currency->name).'</td>';
            $html .= '<td width="13%" align="right">'.app_format_money($paid_amount, $base_currency->name).'</td>';
            $html .= '<td width="17%">'.($debit_info ?: '-').'</td>';
            $html .= '<td width="12%" align="center">'._l($inv->payment_status).'</td>';
            $html .= '</tr>';
            $total_amount += (float)$inv->total;
            $total_paid += $paid_amount + $debit_total;
            $i++;
        }

        $html .= '<tr style="background-color:#f0f0f0;">';
        $html .= '<td width="5%"></td>';
        $html .= '<td width="20%"></td>';
        $html .= '<td width="10%"></td>';
        $html .= '<td width="10%" align="right"><strong>'._l('total').'</strong></td>';
        $html .= '<td width="13%" align="right"><strong>'.app_format_money($total_amount, $base_currency->name).'</strong></td>';
        $html .= '<td width="13%" align="right"><strong>'.app_format_money($total_paid, $base_currency->name).'</strong></td>';
        $html .= '<td width="17%"></td>';
        $html .= '<td width="12%"></td>';
        $html .= '</tr></table>';

        // Use TCPDF directly
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('NextGen');
        $pdf->SetAuthor($company_name);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('batch_payment_'.$txn_id.'.pdf', 'I');
    }

    /**
     * Get all related attachments (PR, PO, GRN) for invoice by PO ID
     */
    public function get_invoice_related_attachments($po_id){
        $html = '';
        if(!is_numeric($po_id)){
            echo json_encode(['html' => '']);
            return;
        }

        $skip_grn = $this->input->get('skip_grn');

        // PR Attachments
        $this->db->select('pur_request');
        $this->db->where('id', $po_id);
        $po_row = $this->db->get(db_prefix().'pur_orders')->row();
        if($po_row && is_numeric($po_row->pur_request) && $po_row->pur_request > 0){
            $this->db->where('rel_id', $po_row->pur_request);
            $this->db->where('rel_type', 'pur_request');
            $pr_files = $this->db->get(db_prefix().'files')->result_array();
            if(count($pr_files) > 0){
                $html .= '<div class="panel_s mtop15"><div class="panel-body"><h5 class="bold"><i class="fa fa-file-text-o"></i> '._l('pur_request').' '._l('attachments').'</h5>';
                foreach($pr_files as $f){
                    $preview_url = site_url(PURCHASE_PATH.'pur_request/'.$f['rel_id'].'/'.$f['file_name']);
                    $html .= '<div class="mbot10 row"><div class="col-md-7"><i class="'.get_mime_class($f['filetype']).'"></i> <a href="'.$preview_url.'" target="_blank" download>'.$f['file_name'].'</a></div>';
                    $html .= '<div class="col-md-1"><a href="'.$preview_url.'" target="_blank" class="btn btn-success btn-xs" data-toggle="tooltip" title="Preview"><i class="fa fa-eye"></i></a></div>';
                    $html .= '<div class="col-md-4"><small class="text-muted"><i class="fa fa-user"></i> '.get_staff_full_name($f['staffid']).' | '._d($f['dateadded']).'</small></div></div>';
                }
                $html .= '</div></div>';
            }
        }

        // PO Attachments
        $this->db->where('rel_id', $po_id);
        $this->db->where('rel_type', 'pur_order');
        $po_files = $this->db->get(db_prefix().'files')->result_array();
        if(count($po_files) > 0){
            $html .= '<div class="panel_s mtop15"><div class="panel-body"><h5 class="bold"><i class="fa fa-shopping-cart"></i> '._l('pur_order').' '._l('attachments').'</h5>';
            foreach($po_files as $f){
                $preview_url = site_url(PURCHASE_PATH.'pur_order/'.$f['rel_id'].'/'.$f['file_name']);
                $html .= '<div class="mbot10 row"><div class="col-md-7"><i class="'.get_mime_class($f['filetype']).'"></i> <a href="'.$preview_url.'" target="_blank" download>'.$f['file_name'].'</a></div>';
                $html .= '<div class="col-md-1"><a href="'.$preview_url.'" target="_blank" class="btn btn-success btn-xs" data-toggle="tooltip" title="Preview"><i class="fa fa-eye"></i></a></div>';
                $html .= '<div class="col-md-4"><small class="text-muted"><i class="fa fa-user"></i> '.get_staff_full_name($f['staffid']).' | '._d($f['dateadded']).'</small></div></div>';
            }
            $html .= '</div></div>';
        }

        // GRN Attachments for this PO (skip if skip_grn param is set)
        if(!$skip_grn){
            $this->db->where('pr_order_id', $po_id);
            $grns = $this->db->get(db_prefix().'goods_receipt')->result_array();
            $all_grn_files = [];
            foreach($grns as $grn){
                $this->db->where('rel_id', $grn['id']);
                $this->db->where('rel_type', 'goods_receipt');
                $gf = $this->db->get(db_prefix().'files')->result_array();
                $all_grn_files = array_merge($all_grn_files, $gf);
            }
            if(count($all_grn_files) > 0){
                $html .= '<div class="panel_s mtop15"><div class="panel-body"><h5 class="bold"><i class="fa fa-truck"></i> '._l('goods_receipt').' '._l('attachments').'</h5>';
                foreach($all_grn_files as $f){
                    $preview_url = site_url('modules/warehouse/uploads/goods_receipt/'.$f['rel_id'].'/'.$f['file_name']);
                    $html .= '<div class="mbot10 row"><div class="col-md-7"><i class="'.get_mime_class($f['filetype']).'"></i> <a href="'.$preview_url.'" target="_blank" download>'.$f['file_name'].'</a></div>';
                    $html .= '<div class="col-md-1"><a href="'.$preview_url.'" target="_blank" class="btn btn-success btn-xs" data-toggle="tooltip" title="Preview"><i class="fa fa-eye"></i></a></div>';
                    $html .= '<div class="col-md-4"><small class="text-muted"><i class="fa fa-user"></i> '.get_staff_full_name($f['staffid']).' | '._d($f['dateadded']).'</small></div></div>';
                }
                $html .= '</div></div>';
            }
        }

        echo json_encode(['html' => $html]);
    }

    /**
     * { tax rate change }
     *
     * @param        $tax    The tax
     */
    public function tax_rate_change($tax){
        $this->load->model('taxes_model');
        $tax = $this->taxes_model->get($tax);
        $rate = 0;
        if($tax){
            $rate = $tax->taxrate;
        }

        echo  json_encode([
            'rate' => $rate,
        ]);
    }

    /**
     * { purchase invoice }
     *
     * @param       $id     The identifier
     */
    public function purchase_invoice($id){
        if (!$id) {
            redirect(admin_url('purchase/invoices'));
        }

        // ── Auto-migrate: ensure cheque columns exist in tblpur_invoice_payment ──
        $this->_ensure_cheque_columns();

        $data['pur_invoice'] = $this->purchase_model->get_pur_invoice($id);

        if(!has_permission('purchase_invoices', '', 'view') && !has_permission('purchase_invoices', '', 'view_own')){
            access_denied('purchase');
        }

        if(has_permission('purchase_invoices', '', 'view_own') && !is_admin()){
            $staffid = get_staff_user_id();

            $approve_access = total_rows(db_prefix().'pur_approval_details', ['staffid' => $staffid, 'rel_type' => 'purchase_invoice', 'rel_id' => $id]);

            if($data['pur_invoice']->add_from != $staffid && !is_vendor_admin($data['pur_invoice']->vendor) && $approve_access == 0){
                echo _l('access_denied');
                die;
            }
        }


        $this->load->model('staff_model');
        $this->load->model('currencies_model');

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);

        $data['applied_debits'] = $this->purchase_model->get_applied_invoice_debits($id);
        $data['debits_available'] = $this->purchase_model->total_remaining_debits_by_vendor($data['pur_invoice']->vendor, $data['pur_invoice']->currency);

        if ($data['debits_available'] > 0) {
            $data['open_debits'] = $this->purchase_model->get_open_debits($data['pur_invoice']->vendor);
        }
        $vendor_currency_id = get_vendor_currency($data['pur_invoice']->vendor);
        $data['vendor_currency'] = $this->currencies_model->get_base_currency();
        if($vendor_currency_id != 0){
            $data['vendor_currency'] = pur_get_currency_by_id($vendor_currency_id);
        }

        $data['invoice_detail'] = $this->purchase_model->get_pur_invoice_detail($id);

        $send_mail_approve = $this->session->userdata("send_mail_approve");
        if((isset($send_mail_approve)) && $send_mail_approve != ''){
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata("send_mail_approve");
        }

        $data['appr_setting'] = $this->purchase_model->get_approve_setting_new('purchase_invoice');
        
        $data['check_appr'] = $this->purchase_model->get_approve_setting('purchase_invoice');
        $data['get_staff_sign'] = $this->purchase_model->get_staff_sign($id,'purchase_invoice');

        if(isset($data['appr_setting']->approval_type) && $data['appr_setting']->approval_type == 1){
            $data['check_approve_status'] = $this->purchase_model->check_approval_details_new($id,'purchase_invoice');
        }else{
            $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'purchase_invoice');
        }

        $data['list_approve_status'] = $this->purchase_model->get_list_approval_details($id,'purchase_invoice');

        $data['activity_logs'] = $this->purchase_model->get_activity_logs($id, 'purchase_invoice');

        $data['tax_data'] = $this->purchase_model->get_html_tax_pur_invoice($id);
        
        $data['title'] = $data['pur_invoice']->invoice_number;
        $data['members']           = $this->staff_model->get('', ['active' => 1]);
        $data['payment'] = $this->purchase_model->get_payment_invoice($id);
        $data['pur_invoice_attachments'] = $this->purchase_model->get_purchase_invoice_attachments($id);
        $this->load->view('invoices/pur_invoice_preview',$data);
    }

    /**
     * Adds a payment for invoice.
     *
     * @param      <type>  $pur_order  The purchase order id
     * @return  redirect
     */
    /**
     * Available balance of a GL account, for the payment modal.
     * Lets the form warn and block before submitting; the authoritative check
     * still runs server-side in add_invoice_payment().
     */
    public function gl_account_balance($account_id = '')
    {
        $this->load->model('accounting/accounting_model');
        $funds = $this->accounting_model->account_funds_status($account_id);

        echo json_encode([
            'found'         => $funds['found'],
            'applies'       => $funds['applies'],
            'balance'       => $funds['balance'],
            'balance_money' => app_format_money($funds['balance'], ''),
            'name'          => $funds['name'],
        ]);
        die;
    }

    public function add_invoice_payment($invoice){
         if ($this->input->post()) {
            $data = $this->input->post();
            $message = '';
            $is_ajax = $this->input->is_ajax_request();

            // ── Auto-migrate: ensure cheque columns exist ──────────────────────
            $this->_ensure_cheque_columns();

            // Block payment if invoice is soft-deleted
            $this->db->select('is_deleted, invoice_number');
            $this->db->where('id', $invoice);
            $inv_check = $this->db->get(db_prefix().'pur_invoices')->row();
            if($inv_check && $inv_check->is_deleted == 1){
                $msg = 'Cannot add payment. Invoice ' . $inv_check->invoice_number . ' has been deleted.';
                if($is_ajax){ echo json_encode(['success'=>false,'message'=>$msg]); die; }
                set_alert('danger', $msg);
                redirect(admin_url('purchase/purchase_invoice/'.$invoice));
            }

            // Guard: date must not be empty — default to today
            if(empty($data['date'])){
                $data['date'] = _d(date('Y-m-d'));
            }

            // Guard: paymentmode must not be empty — use hidden fallback if needed
            if(empty($data['paymentmode']) && !empty($data['paymentmode_hidden'])){
                $data['paymentmode'] = $data['paymentmode_hidden'];
            }
            unset($data['paymentmode_hidden']);
            if(empty($data['paymentmode'])){
                $msg = 'Payment Mode is required.';
                if($is_ajax){ echo json_encode(['success'=>false,'message'=>$msg]); die; }
                set_alert('danger', $msg);
                redirect(admin_url('purchase/purchase_invoice/'.$invoice));
            }

            // Server-side cheque field validation
            $this->db->where('id', $data['paymentmode']);
            $pm_check = $this->db->get(db_prefix().'payment_modes')->row();
            $is_cheque_mode_ctrl = $pm_check && (stripos($pm_check->name,'cheque') !== false || stripos($pm_check->name,'check') !== false);
            if($is_cheque_mode_ctrl){
                $cheque_errors = [];
                if(empty($data['cheque_bank_name'])) $cheque_errors[] = _l('cheque_bank_name');
                if(empty($data['cheque_number']))    $cheque_errors[] = _l('cheque_number');
                if(empty($data['cheque_date']))      $cheque_errors[] = _l('cheque_date');
                if(!empty($cheque_errors)){
                    $msg = _l('required').': '.implode(', ', $cheque_errors);
                    if($is_ajax){ echo json_encode(['success'=>false,'message'=>$msg]); die; }
                    set_alert('danger', $msg);
                    redirect(admin_url('purchase/purchase_invoice/'.$invoice));
                }
            }

            // Handle cheque attachment upload — upload to temp path first,
            // then move to payment_cheque/{payment_id}/ after insert
            $temp_file_path = null;
            $temp_filename  = null;
            if(!empty($_FILES['cheque_attachment']['name'])){
                $ext = strtolower(pathinfo($_FILES['cheque_attachment']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','pdf','gif'];
                if(in_array($ext, $allowed)){
                    $temp_dir = PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_cheque/tmp/';
                    if(!is_dir($temp_dir)) mkdir($temp_dir, 0755, true);
                    $temp_filename = 'cheque_'.time().'.'.$ext;
                    if(move_uploaded_file($_FILES['cheque_attachment']['tmp_name'], $temp_dir.$temp_filename)){
                        $temp_file_path = $temp_dir.$temp_filename;
                        $data['cheque_attachment'] = $temp_filename;
                    }
                }
            }

            // ── Sufficient funds check on the selected GL account ──────────────
            // The payment credits this account (money leaving), so an asset account
            // must actually hold enough. Only asset accounts are checked - see
            // Accounting_model::account_funds_status().
            if(!empty($data['gl_account']) && is_numeric($data['gl_account'])){
                $this->load->model('accounting/accounting_model');
                $funds = $this->accounting_model->account_funds_status($data['gl_account']);

                if($funds['found'] && $funds['applies']){
                    $pay_amount = (float)str_replace(',', '', $data['amount'] ?? 0);

                    if($funds['balance'] <= 0 || $funds['balance'] < $pay_amount){
                        $msg = _l('gl_account_insufficient_balance',
                            [$funds['name'], app_format_money($funds['balance'], ''), app_format_money($pay_amount, '')]
                        );
                        if($is_ajax){ echo json_encode(['success'=>false,'message'=>$msg]); die; }
                        set_alert('danger', $msg);
                        redirect(admin_url('purchase/purchase_invoice/'.$invoice));
                    }
                }
            }

            // Store debit note selection before removing it from the payment data
            $selected_debit_note_id     = isset($data['debit_note_id']) ? (int)$data['debit_note_id'] : 0;
            $selected_debit_note_amount = isset($data['debit_note_amount']) ? (float)$data['debit_note_amount'] : 0;
            unset($data['debit_note_id'], $data['debit_note_amount']);

            $success = $this->purchase_model->add_invoice_payment($data, $invoice);
            if ($success) {
                // Apply the debit note through the same model call the batch payment
                // flow uses. This replaces a hand-rolled insert that skipped the
                // accounting entries, update_debit_note_status() and
                // update_pur_invoice_status() - so a debit note applied from here
                // never reached tblacc_account_history.
                if($selected_debit_note_id > 0){
                    $apply_amount = $this->purchase_model->debit_note_applicable_amount(
                        $selected_debit_note_id,
                        $invoice,
                        $selected_debit_note_amount
                    );

                    if($apply_amount > 0){
                        $applied = $this->purchase_model->apply_debits($selected_debit_note_id, [
                            'invoice_id' => $invoice,
                            'amount'     => $apply_amount,
                        ]);

                        if($applied){
                            $this->purchase_model->update_pur_invoice_status($invoice);
                        }
                    }
                }

                // Move cheque attachment from temp to payment_cheque/{payment_id}/
                if($temp_file_path && $temp_filename && file_exists($temp_file_path)){
                    $final_dir = PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_cheque/'.$success.'/';
                    if(!is_dir($final_dir)) mkdir($final_dir, 0755, true);
                    rename($temp_file_path, $final_dir.$temp_filename);
                    // Update the payment record with the correct path (filename is the same)
                    $this->db->where('id', $success);
                    $this->db->update(db_prefix().'pur_invoice_payment', ['cheque_attachment' => $temp_filename]);
                    // Also update cheque_management if created
                    $this->db->where('source_type', 'pur_invoice_payment');
                    $this->db->where('source_id', $success);
                    $this->db->update(db_prefix().'cheque_management', ['cheque_attachment' => $temp_filename]);
                }
                $message = _l('added_successfully', _l('payment'));
                if($is_ajax){ echo json_encode(['success'=>true,'message'=>$message,'payment_id'=>$success]); die; }
            } else {
                if($is_ajax){ echo json_encode(['success'=>false,'message'=>'Failed to save payment.']); die; }
            }
            set_alert('success', $message);
            redirect(admin_url('purchase/purchase_invoice/'.$invoice));
            
        }
    }

     /**
     * { delete payment }
     *
     * @param      <type>  $id         The identifier
     * @param      <type>  $pur_order  The pur order
     * @return  redirect
     */
    /**
     * AJAX: Check if a payment is part of a batch (shared transaction ID)
     * Returns JSON: has_batch, count, transaction_id
     */
    public function check_payment_batch($id){
        if(!is_numeric($id)){
            echo json_encode(['has_batch' => false, 'count' => 0, 'transaction_id' => '']);
            die;
        }

        $this->db->where('id', $id);
        $payment = $this->db->get(db_prefix().'pur_invoice_payment')->row();

        if(!$payment || empty($payment->transactionid)){
            echo json_encode(['has_batch' => false, 'count' => 0, 'transaction_id' => '']);
            die;
        }

        // Count ALL other active payments with the same transaction ID
        $this->db->where('transactionid', $payment->transactionid);
        $this->db->where('id !=', $id);
        $this->db->where('is_deleted', 0);
        $batch_count = $this->db->count_all_results(db_prefix().'pur_invoice_payment');

        if($batch_count > 0){
            echo json_encode([
                'has_batch'      => true,
                'count'          => $batch_count,
                'transaction_id' => $payment->transactionid,
            ]);
        } else {
            echo json_encode(['has_batch' => false, 'count' => 0, 'transaction_id' => $payment->transactionid]);
        }
        die;
    }

    public function delete_payment_pur_invoice($id,$inv)
    {
        if (!$id) {
            redirect(admin_url('purchase/purchase_invoice/'.$inv));
        }

        // Check if this payment has a transaction ID that's shared with other payments (batch payment)
        $this->db->where('id', $id);
        $payment = $this->db->get(db_prefix().'pur_invoice_payment')->row();
        
        if($payment && !empty($payment->transactionid)){
            // Find all payments with the same transaction ID
            $this->db->where('transactionid', $payment->transactionid);
            $this->db->where('id !=', $id);
            $batch_count = $this->db->count_all_results(db_prefix().'pur_invoice_payment');
            
            if($batch_count > 0){
                // Delete all payments with same transaction ID
                $this->db->where('transactionid', $payment->transactionid);
                $batch_payments = $this->db->get(db_prefix().'pur_invoice_payment')->result();
                $deleted = 0;
                foreach($batch_payments as $bp){
                    $res = $this->purchase_model->delete_payment_pur_invoice($bp->id);
                    if($res) $deleted++;
                }
                set_alert('success', _l('deleted').' - Batch payment ('.$deleted.' payments with Transaction ID: '.$payment->transactionid.') deleted');
                redirect(admin_url('purchase/purchase_invoice/'.$inv));
                return;
            }
        }

        $response = $this->purchase_model->delete_payment_pur_invoice($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('payment')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('payment')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('payment')));
        }
        redirect(admin_url('purchase/purchase_invoice/'.$inv));
    }

    /**
     * { payment invoice }
     *
     * @param       $id     The identifier
     * @return view
     */
    public function payment_invoice($id){
        $this->load->model('currencies_model');

        // ── Auto-migrate: ensure cheque columns exist in tblpur_invoice_payment ──
        $this->_ensure_cheque_columns();

        $send_mail_approve = $this->session->userdata("send_mail_approve");
        if((isset($send_mail_approve)) && $send_mail_approve != ''){
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata("send_mail_approve");
        }


        if ($this->input->post()) {
            if (staff_cant('edit', 'payments')) {
                access_denied('Update Payment');
            }
            $post_data = $this->input->post();
            $is_ajax   = $this->input->is_ajax_request();

            // Paymentmode fallback from hidden input (selectpicker may not submit native select)
            if(empty($post_data['paymentmode']) && !empty($post_data['paymentmode_hidden'])){
                $post_data['paymentmode'] = $post_data['paymentmode_hidden'];
            }
            unset($post_data['paymentmode_hidden']);

            // Server-side cheque field validation on edit
            if(!empty($post_data['paymentmode'])){
                $this->db->where('id', $post_data['paymentmode']);
                $pm_edit = $this->db->get(db_prefix().'payment_modes')->row();
                $is_cheque_edit = $pm_edit && (stripos($pm_edit->name,'cheque') !== false || stripos($pm_edit->name,'check') !== false);
                if($is_cheque_edit){
                    $cheque_errors = [];
                    if(empty($post_data['cheque_bank_name'])) $cheque_errors[] = _l('cheque_bank_name');
                    if(empty($post_data['cheque_number']))    $cheque_errors[] = _l('cheque_number');
                    if(empty($post_data['cheque_date']))      $cheque_errors[] = _l('cheque_date');
                    if(!empty($cheque_errors)){
                        $msg = _l('required').': '.implode(', ', $cheque_errors);
                        if($is_ajax){ echo json_encode(['success'=>false,'message'=>$msg]); die; }
                        set_alert('danger', $msg);
                        redirect(admin_url('purchase/payment_invoice/'.$id));
                    }
                }
            }

            // Handle cheque attachment upload on edit
            if(!empty($_FILES['cheque_attachment']['name'])){
                $upload_path = PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_cheque/'.$id.'/';
                if(!is_dir($upload_path)) mkdir($upload_path, 0755, true);
                $ext = strtolower(pathinfo($_FILES['cheque_attachment']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','pdf','gif'];
                if(in_array($ext, $allowed)){
                    $filename = 'cheque_'.time().'.'.$ext;
                    if(move_uploaded_file($_FILES['cheque_attachment']['tmp_name'], $upload_path.$filename)){
                        // Remove old file if exists
                        $old = $this->purchase_model->get_payment_pur_invoice($id);
                        if($old && !empty($old->cheque_attachment)){
                            @unlink($upload_path.$old->cheque_attachment);
                        }
                        $post_data['cheque_attachment'] = $filename;
                    }
                }
            } elseif(!empty($post_data['remove_cheque_attachment'])){
                // Remove existing attachment
                $old = $this->purchase_model->get_payment_pur_invoice($id);
                if($old && !empty($old->cheque_attachment)){
                    @unlink(PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_cheque/'.$id.'/'.$old->cheque_attachment);
                }
                $post_data['cheque_attachment'] = null;
            }
            unset($post_data['remove_cheque_attachment']);

            $success = $this->purchase_model->update_payment_invoice($post_data, $id);
            if ($success) {
                $msg = _l('updated_successfully', _l('payment'));
                if($is_ajax){ echo json_encode(['success'=>true,'message'=>$msg]); die; }
                set_alert('success', $msg);
            } else {
                if($is_ajax){ echo json_encode(['success'=>false,'message'=>'Failed to update payment.']); die; }
            }
            redirect(admin_url('purchase/payment_invoice/' . $id));
        }
        
        $data['check_appr'] = $this->purchase_model->get_approve_setting('payment_request');
        $data['get_staff_sign'] = $this->purchase_model->get_staff_sign($id,'payment_request');
        $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'payment_request');

        $data['appr_setting'] = $this->purchase_model->get_approve_setting_new('payment_request');
        if(isset($data['appr_setting']->approval_type) && $data['appr_setting']->approval_type == 1){

            $data['check_approve_status'] = $this->purchase_model->check_approval_details_new($id,'payment_request');
        }else{

            $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'payment_request');
        }

        $data['list_approve_status'] = $this->purchase_model->get_list_approval_details($id,'payment_request');


        $data['payment_invoice'] = $this->purchase_model->get_payment_pur_invoice($id);
        $data['title'] = _l('payment_for').' '.get_pur_invoice_number($data['payment_invoice']->pur_invoice);

        $data['invoice'] = $this->purchase_model->get_pur_invoice($data['payment_invoice']->pur_invoice);

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get();

        $data['base_currency'] = $this->currencies_model->get_base_currency();
        if($data['invoice']->currency != 0){
            $data['base_currency'] = pur_get_currency_by_id($data['invoice']->currency);
        }

        $this->load->view('invoices/payment_invoice',$data);
    }

    /**
     * { purchase invoice attachment }
     */
    public function purchase_invoice_attachment($id){
        handle_pur_invoice_file($id);
        redirect(admin_url('purchase/purchase_invoice/'.$id));
    }

    /**
     * AJAX: Get batch payment siblings for a payment_request
     * Returns all payments sharing the same transactionid (including the current one)
     * Used to show a batch info popup before approving
     */
    public function get_batch_payment_info($payment_id){
        if(!is_numeric($payment_id)){
            echo json_encode(['is_batch'=>false,'payments'=>[]]);
            die;
        }

        $this->db->where('id', $payment_id);
        $payment = $this->db->get(db_prefix().'pur_invoice_payment')->row();

        if(!$payment || empty($payment->transactionid)){
            echo json_encode(['is_batch'=>false,'payments'=>[]]);
            die;
        }

        // Fetch ALL payments with the same transactionid (including this one)
        $this->db->where('transactionid', $payment->transactionid);
        $this->db->where('is_deleted', 0);
        $this->db->order_by('id', 'asc');
        $all_payments = $this->db->get(db_prefix().'pur_invoice_payment')->result_array();

        if(count($all_payments) <= 1){
            echo json_encode(['is_batch'=>false,'payments'=>[]]);
            die;
        }

        // Build display data for each payment
        $this->load->model('payment_modes_model');
        $payment_modes = $this->payment_modes_model->get();
        $pm_map = [];
        foreach($payment_modes as $pm){ $pm_map[$pm['id']] = $pm['name']; }

        $this->load->model('currencies_model');
        $base_currency = $this->currencies_model->get_base_currency();

        $rows = [];
        foreach($all_payments as $p){
            $inv = $this->purchase_model->get_pur_invoice($p['pur_invoice']);
            $inv_number = $inv ? $inv->invoice_number : '#'.$p['pur_invoice'];
            $pm_name    = isset($pm_map[$p['paymentmode']]) ? $pm_map[$p['paymentmode']] : '-';
            $status_label = '';
            if($p['approval_status'] == 1)      $status_label = '<span class="label label-info">Pending</span>';
            elseif($p['approval_status'] == 2)  $status_label = '<span class="label label-success">Approved</span>';
            elseif($p['approval_status'] == 3)  $status_label = '<span class="label label-danger">Rejected</span>';
            else                                $status_label = '<span class="label label-default">Draft</span>';

            $rows[] = [
                'id'              => $p['id'],
                'invoice_number'  => $inv_number,
                'amount'          => app_format_money($p['amount'], $base_currency->name),
                'date'            => _d($p['date']),
                'paymentmode'     => $pm_name,
                'transactionid'   => $p['transactionid'],
                'approval_status' => $p['approval_status'],
                'status_label'    => $status_label,
                'is_current'      => ($p['id'] == $payment_id),
                'payment_url'     => admin_url('purchase/payment_invoice/'.$p['id']),
            ];
        }

        echo json_encode([
            'is_batch'       => true,
            'txn_id'         => $payment->transactionid,
            'total_count'    => count($rows),
            'pending_count'  => count(array_filter($rows, function($r){ return $r['approval_status'] == 1; })),
            'payments'       => $rows,
        ]);
        die;
    }

    /**
     * Serve cheque attachment file for a payment
     */
    public function payment_cheque($payment_id, $filename){
        if(!is_numeric($payment_id) || empty($filename)){
            show_404(); return;
        }
        // Sanitize filename - no path traversal
        $filename = basename($filename);
        $filepath = PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_cheque/'.$payment_id.'/'.$filename;
        if(!file_exists($filepath)){
            show_404(); return;
        }
        $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime_map = ['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif'];
        $mime = isset($mime_map[$ext]) ? $mime_map[$ext] : 'application/octet-stream';
        header('Content-Type: '.$mime);
        header('Content-Disposition: inline; filename="'.addslashes($filename).'"');
        header('Content-Length: '.filesize($filepath));
        readfile($filepath);
        exit;
    }

    /**
     * { preview purchase invoice file }
     *
     * @param        $id      The identifier
     * @param        $rel_id  The relative identifier
     * @return  view
     */
    public function file_purinv($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin']             = is_admin();
        $data['file'] = $this->purchase_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }
        $this->load->view('invoices/_file', $data);
    }

    /**
     * { delete purchase order attachment }
     *
     * @param      <type>  $id     The identifier
     */
    public function delete_purinv_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo pur_html_entity_decode($this->purchase_model->delete_purinv_attachment($id));
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /**
     * { purchase estimate pdf }
     *
     * @param      <type>  $id     The identifier
     * @return pdf output
     */
    public function purestimate_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('purchase/quotations'));
        }

        $pur_estimate = $this->purchase_model->get_purestimate_pdf_html($id);

        try {
            $pdf = $this->purchase_model->purestimate_pdf($pur_estimate,$id);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }
        
        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(format_pur_estimate_number($id).'.pdf', $type);
    }

    /**
     * Sends a request quotation.
     * @return redirect
     */
    public function send_quotation(){
        if($this->input->post()){
            $data = $this->input->post();
            $data['content'] = $this->input->post('content', false);
            $send = $this->purchase_model->send_quotation($data);
            if($send){
                set_alert('success',_l('send_quotation_successfully'));
                
            }else{
                set_alert('warning',_l('send_quotation_fail'));
            }
            redirect(admin_url('purchase/quotations/'.$data['pur_estimate_id']));
            
        }
    }

    /**
     * Sends a purchase order.
     * @return redirect
     */
    public function send_po(){
        if($this->input->post()){
            $data = $this->input->post();
            $data['content'] = $this->input->post('content', false);
            $send = $this->purchase_model->send_po($data);
            if($send){
                set_alert('success',_l('send_po_successfully'));
                
            }else{
                set_alert('warning',_l('send_po_fail'));
            }
            redirect(admin_url('purchase/purchase_order/'.$data['po_id']));
            
        }
    }

    /**
     * import xlsx commodity
     * @param  integer $id
     * @return view
     */
    public function import_xlsx_commodity() {
        if (!is_admin() && !has_permission('purchase_items', '', 'create')) {
            access_denied('purchase');
        }
        $this->load->model('staff_model');
        $data_staff = $this->staff_model->get(get_staff_user_id());

        /*get language active*/
        if ($data_staff) {
            if ($data_staff->default_language != '') {
                $data['active_language'] = $data_staff->default_language;

            } else {

                $data['active_language'] = get_option('active_language');
            }

        } else {
            $data['active_language'] = get_option('active_language');
        }
        $data['title'] = _l('import_excel');

        $this->load->view('items/import_excel', $data);
    }

    /**
     * import file xlsx commodity
     * @return json
     */
    public function import_file_xlsx_commodity() {
        if (!is_admin() && !has_permission('purchase_items', '', 'create')) {
            access_denied(_l('purchase'));
        }

        if(!class_exists('XLSXReader_fin')){
            require_once(module_dir_path(PURCHASE_MODULE_NAME).'/assets/plugins/XLSXReader/XLSXReader.php');
        }
        require_once(module_dir_path(PURCHASE_MODULE_NAME).'/assets/plugins/XLSXWriter/xlsxwriter.class.php');

        $total_row_false = 0;
        $total_rows_data = 0;
        $dataerror = 0;
        $total_row_success = 0;
        $total_rows_data_error = 0;
        $filename='';

        if ($this->input->post()) {

            /*delete file old before export file*/
            $path_before = COMMODITY_ERROR_PUR.'FILE_ERROR_COMMODITY'.get_staff_user_id().'.xlsx';
            if(file_exists($path_before)){
                unlink(COMMODITY_ERROR_PUR.'FILE_ERROR_COMMODITY'.get_staff_user_id().'.xlsx');
            }

            if (isset($_FILES['file_csv']['name']) && $_FILES['file_csv']['name'] != '') {
                //do_action('before_import_leads');

                // Get the temp file path
                $tmpFilePath = $_FILES['file_csv']['tmp_name'];
                // Make sure we have a filepath
                if (!empty($tmpFilePath) && $tmpFilePath != '') {
                    $tmpDir = TEMP_FOLDER . '/' . time() . uniqid() . '/';

                    if (!file_exists(TEMP_FOLDER)) {
                        mkdir(TEMP_FOLDER, 0755);
                    }

                    if (!file_exists($tmpDir)) {
                        mkdir($tmpDir, 0755);
                    }

                    // Setup our new file path
                    $newFilePath = $tmpDir . $_FILES['file_csv']['name'];

                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                        
                        //Writer file
                        $writer_header = array(
                            _l('commodity_code')          =>'string',
                            _l('commodity_name')          =>'string',
                            _l('commodity_barcode')          =>'string',
                            _l('sku_code')               =>'string',
                            _l('sku_name')       =>'string',
                            _l('description')             =>'string',
                            _l('unit_id')                      =>'string',
                            _l('commodity_group')                     =>'string',
                            _l('sub_group')                     =>'string',
                            _l('purchase_price')                     =>'string',
                            _l('rate')                     =>'string',
                            _l('tax_1')                     =>'string',
                            _l('tax_2')                     =>'string',
                            _l('error')                     =>'string',
                        );

                        $widths_arr = array();
                        for($i = 1; $i <= count($writer_header); $i++ ){
                            $widths_arr[] = 40;
                        }

                        $writer = new XLSXWriter();
                        $writer->writeSheetHeader('Sheet1', $writer_header,  $col_options = ['widths'=>$widths_arr ]);

                        //Reader file
                        $xlsx = new XLSXReader_fin($newFilePath);
                        $sheetNames = $xlsx->getSheetNames();
                        $data = $xlsx->getSheetData($sheetNames[1]);

                        $total_rows = 0;
                        $total_row_false    = 0;
                        
                        for ($row = 1; $row < count($data); $row++) {

                            $total_rows++;

                            $rd = array();
                            $flag = 0;
                            $flag2 = 0;

                            $string_error ='';

                            $flag_id_unit_id;
                            $flag_id_commodity_group;
                            $flag_id_sub_group;
                            $flag_id_tax;
                            $flag_id_tax2;

                            $value_commodity_code    = isset($data[$row][0]) ? $data[$row][0] : '' ;
                            $value_commodity_name    = isset($data[$row][1]) ? $data[$row][1] : '' ;
                            $value_commodity_barcode    = isset($data[$row][2]) ? $data[$row][2] : '';
                            $value_sku_code   = isset($data[$row][3]) ? $data[$row][3] : '' ;
                            $value_sku_name      = isset($data[$row][4]) ? $data[$row][4] : '';
                            $value_description       = isset($data[$row][5]) ? $data[$row][5] : '';
                            $value_unit_id            = isset($data[$row][6]) ? $data[$row][6] : '';
                            $value_commodity_group            = isset($data[$row][7]) ? $data[$row][7] : '';
                            $value_sub_group            = isset($data[$row][8]) ? $data[$row][8] : '';
                            $value_purchase_price            = isset($data[$row][9]) ? $data[$row][9] : '';
                            $value_rate            = isset($data[$row][10]) ? $data[$row][10] : '';
                            $value_tax            = isset($data[$row][11]) ? $data[$row][11] : '';
                            $value_tax2            = isset($data[$row][12]) ? $data[$row][12] : '';

                            if(is_null($value_commodity_code) == true || $value_commodity_code ==''){
                                $string_error .=_l('commodity_code'). _l('not_yet_entered');
                                $flag = 1;
                            }else{
                                $this->db->where('commodity_code', $value_commodity_code);
                                $total_rows_check = $this->db->count_all_results(db_prefix().'items');
                                if ($total_rows_check > 0) {
                                    $string_error .=_l('commodity_code'). _l('already_exist');
                                    $flag = 1;
                                }
                            }
                            
                            if(is_null($value_commodity_name) == true || $value_commodity_name ==''){
                                $string_error .=_l('commodity_name'). _l('not_yet_entered');
                                $flag = 1;
                            }

                            //check unit_code exist  (input: id or name contract)
                            if (is_null($value_unit_id) != true && ( $value_unit_id != '0') && $value_unit_id != '') {
                                /*case input id*/
                                if (is_numeric($value_unit_id)) {
                                    $this->db->where('unit_type_id', $value_unit_id);
                                    $unit_id_value = $this->db->count_all_results(db_prefix() . 'ware_unit_type');
                                    if ($unit_id_value == 0) {
                                        $string_error .= _l('unit_id') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get id unit_id*/
                                        $flag_id_unit_id = $value_unit_id;
                                    }
                                } else {
                                    /*case input name*/
                                    $this->db->like(db_prefix() . 'ware_unit_type.unit_code', $value_unit_id);
                                    $unit_id_value = $this->db->get(db_prefix() . 'ware_unit_type')->result_array();
                                    if (count($unit_id_value) == 0) {
                                        $string_error .= _l('unit_id') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get unit_id*/
                                        $flag_id_unit_id = $unit_id_value[0]['unit_type_id'];
                                    }
                                }
                            }

                            //check commodity_group exist  (input: id or name contract)
                            if (is_null($value_commodity_group) != true && ($value_commodity_group != '0') && $value_commodity_group != '') {
                                /*case input id*/
                                if (is_numeric($value_commodity_group)) {
                                    $this->db->where('id', $value_commodity_group);
                                    $commodity_group_value = $this->db->count_all_results(db_prefix() . 'items_groups');
                                    if ($commodity_group_value == 0) {
                                        $string_error .= _l('commodity_group') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get id commodity_group*/
                                        $flag_id_commodity_group = $value_commodity_group;
                                    }
                                } else {
                                    /*case input name*/
                                    $this->db->like(db_prefix() . 'items_groups.commodity_group_code', $value_commodity_group);
                                    $commodity_group_value = $this->db->get(db_prefix() . 'items_groups')->result_array();
                                    if (count($commodity_group_value) == 0) {
                                        $string_error .= _l('commodity_group') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get id commodity_group*/
                                        $flag_id_commodity_group = $commodity_group_value[0]['id'];
                                    }
                                }
                            }

                            //check taxes exist  (input: id or name contract)
                            if (is_null($value_tax) != true && ($value_tax!= '0') && $value_tax != '') {
                                /*case input id*/
                                if (is_numeric($value_tax)) {
                                    $this->db->where('id', $value_tax);
                                    $cell_tax_value = $this->db->count_all_results(db_prefix() . 'taxes');
                                    if ($cell_tax_value == 0) {
                                        $string_error .= _l('tax') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get id cell_tax*/
                                        $flag_id_tax = $value_tax;
                                    }
                                } else {
                                    /*case input name*/
                                    $this->db->like(db_prefix() . 'taxes.name', $value_tax);
                                    $cell_tax_value = $this->db->get(db_prefix() . 'taxes')->result_array();
                                    if (count($cell_tax_value) == 0) {
                                        $string_error .= _l('tax') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get id warehouse_id*/
                                        $flag_id_tax = $cell_tax_value[0]['id'];
                                    }
                                }
                            }

                            //check taxes exist  (input: id or name contract)
                            if (is_null($value_tax2) != true && ($value_tax2!= '0') && $value_tax2 != '') {
                                /*case input id*/
                                if (is_numeric($value_tax2)) {
                                    $this->db->where('id', $value_tax2);
                                    $cell_tax_value2 = $this->db->count_all_results(db_prefix() . 'taxes');
                                    if ($cell_tax_value2 == 0) {
                                        $string_error .= _l('tax') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get id cell_tax*/
                                        $flag_id_tax2 = $value_tax2;
                                    }
                                } else {
                                    /*case input name*/
                                    $this->db->like(db_prefix() . 'taxes.name', $value_tax2);
                                    $cell_tax_value2 = $this->db->get(db_prefix() . 'taxes')->result_array();
                                    if (count($cell_tax_value2) == 0) {
                                        $string_error .= _l('tax') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get id warehouse_id*/
                                        $flag_id_tax2 = $cell_tax_value2[0]['id'];
                                    }
                                }
                            }

                            //check commodity_group exist  (input: id or name contract)
                            if (is_null($value_sub_group) != true && $value_sub_group != '') {
                                /*case input id*/
                                if (is_numeric($value_sub_group)) {
                                    $this->db->where('id', $value_sub_group);
                                    $sub_group_value = $this->db->count_all_results(db_prefix() . 'wh_sub_group');
                                    if ($sub_group_value == 0) {
                                        $string_error .= _l('sub_group') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get id sub_group*/
                                        $flag_id_sub_group = $value_sub_group;
                                    }
                                } else {
                                    /*case input  name*/
                                    $this->db->like(db_prefix() . 'wh_sub_group.sub_group_code', $value_sub_group);
                                    $sub_group_value = $this->db->get(db_prefix() . 'wh_sub_group')->result_array();
                                    if (count($sub_group_value) == 0) {
                                        $string_error .= _l('sub_group') . _l('does_not_exist');
                                        $flag2 = 1;
                                    } else {
                                        /*get id sub_group*/
                                        $flag_id_sub_group = $sub_group_value[0]['id'];
                                    }
                                }
                            }

                            //check value_rate input
                            if (is_null($value_rate) != true && $value_rate != '') {
                                if (!check_valid_number_with_setting($value_rate)) {
                                    $string_error .= _l('cell_rate') . _l('_check_invalid');
                                    $flag = 1;
                                }
                            }
                            //check value_purchase_price input
                            if (is_null($value_purchase_price) != true && $value_purchase_price != '') {
                                if (!check_valid_number_with_setting($value_purchase_price)) {
                                    $string_error .= _l('purchase_price') . _l('_check_invalid');
                                    $flag = 1;
                                }
                            }

                            if(($flag == 1) || $flag2 == 1 ){
                                //write error file
                                $writer->writeSheetRow('Sheet1', [
                                    $value_commodity_code,
                                    $value_commodity_name,
                                    $value_commodity_barcode,
                                    $value_sku_code,
                                    $value_sku_name,
                                    $value_description,
                                    $value_unit_id,
                                    $value_commodity_group,
                                    $value_sub_group,
                                    $value_purchase_price,
                                    $value_rate,
                                    $value_tax,
                                    $value_tax2,
                                    $string_error,
                                ]);

                                // $numRow++;
                                $total_row_false++;
                            }

                            if($flag == 0 && $flag2 == 0){
                                $rd['commodity_code']                = $value_commodity_code;
                                $rd['description']                = $value_commodity_name;
                                $rd['commodity_barcode']                     = $value_commodity_barcode;
                                $rd['sku_code']     = $value_sku_code;
                                $rd['sku_name']                = $value_sku_name;
                                $rd['long_description']                         = $value_description;
                                $rd['unit_id']                         = isset($flag_id_unit_id) ? $flag_id_unit_id : '';
                                $rd['group_id']                         = isset($flag_id_commodity_group) ? $flag_id_commodity_group : '';
                                $rd['sub_group']                         = isset($flag_id_sub_group) ? $flag_id_sub_group : '';
                                $rd['tax']                         = isset($flag_id_tax) ? $flag_id_tax : '';
                                $rd['tax2']                         = isset($flag_id_tax2) ? $flag_id_tax2 : '';
                                $rd['rate']                         = reformat_currency_pur($value_rate);
                                $rd['purchase_price']                         = reformat_currency_pur($value_purchase_price);
                               
                                $rows[] = $rd;
                                $response = $this->purchase_model->import_xlsx_commodity($rd);
                            }
                        }

                        $total_rows = $total_rows;
                        $total_row_success = isset($rows) ? count($rows) : 0;
                        // $dataerror = $dataError;
                        $dataerror = '';
                        $message ='Not enought rows for importing';

                        if($total_row_false != 0){
                            $filename = 'Import_item_error_'.get_staff_user_id().'_'.strtotime(date('Y-m-d H:i:s')).'.xlsx';
                            $writer->writeToFile(str_replace($filename, PURCHASE_IMPORT_ITEM_ERROR.$filename, $filename));
                        }
                    }
                    
                } else {
                    set_alert('warning', _l('import_upload_failed'));
                }
            }

        }
        echo json_encode([
            'message'           => $message,
            'total_row_success' => $total_row_success,
            'total_row_false'   => $total_row_false,
            'total_rows'        => $total_rows,
            'site_url'          => site_url(),
            'staff_id'          => get_staff_user_id(),
            'filename'          => PURCHASE_IMPORT_ITEM_ERROR.$filename,

        ]);

    }

    /**
     * { import vendor }
     */
    public function vendor_import()
    {
        if (!has_permission('purchase_vendors', '', 'create')) {
            access_denied('purchase');
        }

        $this->load->model('staff_model');
        $data_staff = $this->staff_model->get(get_staff_user_id());

        /*get language active*/
        if ($data_staff) {
            if ($data_staff->default_language != '') {
                $data['active_language'] = $data_staff->default_language;

            } else {

                $data['active_language'] = get_option('active_language');
            }

        } else {
            $data['active_language'] = get_option('active_language');
        }
        $data['title'] = _l('import_excel');

        $this->load->view('vendors/import_excel', $data);
    }

    /**
     * { reset data }
     */
    public function reset_data()
    {

        if ( !is_admin()) {
            access_denied('purchase');
        }

            //delete purchase request
            $this->db->truncate(db_prefix().'pur_request');
            //delete purchase request detail
            $this->db->truncate(db_prefix().'pur_request_detail');
            //delete purchase order
            $this->db->truncate(db_prefix().'pur_orders');
            //delete purchase order detail
            $this->db->truncate(db_prefix().'pur_order_detail');
            //delete purchase order payment
            $this->db->truncate(db_prefix().'pur_order_payment');
            //delete purchase invoice
            $this->db->truncate(db_prefix().'pur_invoices');
            //delete purchase invoice payment
            $this->db->truncate(db_prefix().'pur_invoice_payment');
            //delete purchase estimate
            $this->db->truncate(db_prefix().'pur_estimates');
            //delete pur_estimate_detail
            $this->db->truncate(db_prefix().'pur_estimate_detail');
            //delete pur_contracts
            $this->db->truncate(db_prefix().'pur_contracts');
            //delete tblpur_approval_details
            $this->db->truncate(db_prefix().'pur_approval_details');

            //delete create task rel_type: "pur_contract", "pur_contract".
            $this->db->where('rel_type', 'pur_contract');
            $this->db->or_where('rel_type', 'pur_order');
            $this->db->or_where('rel_type', 'pur_quotation');
            $this->db->or_where('rel_type', 'pur_invoice');
            $this->db->delete(db_prefix() . 'tasks');


            $this->db->where('rel_type', 'pur_contract');
            $this->db->or_where('rel_type', 'pur_order');
            $this->db->or_where('rel_type', 'pur_estimate');
            $this->db->or_where('rel_type', 'pur_invoice');
            $this->db->delete(db_prefix() . 'files');

            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_order/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_contract/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_order/signature/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_invoice/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_estimate/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_estimate/signature/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_invoice/signature/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/payment_request/signature/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/request_quotation/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/send_po/');
            delete_files_pur(PURCHASE_MODULE_UPLOAD_FOLDER.'/send_quotation/');

            $this->db->where('rel_type', 'pur_contract');
            $this->db->or_where('rel_type', 'purchase_order');
            $this->db->or_where('rel_type', 'pur_invoice');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'pur_contract');
            $this->db->or_where('rel_type', 'purchase_order');
            $this->db->or_where('rel_type', 'pur_invoice');
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('fieldto', 'pur_order');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_type', 'pur_invoice');
            $this->db->or_where('rel_type', 'pur_order');
            $this->db->delete(db_prefix() . 'taggables');

            set_alert('success',_l('reset_data_successful'));
            
            redirect(admin_url('purchase/setting'));

    }

    /**
     * Removes a po logo.
     */
    public function remove_po_logo(){
        if ( !is_admin()) {
            access_denied('purchase');
        }

        $success = $this->purchase_model->remove_po_logo();
        if($success){
            set_alert('success', _l('deleted', _l('po_logo')));
        }
        redirect(admin_url('purchase/setting'));
    }

    /**
     * delete_error file day before
     * @return [type] 
     */
    public function delete_error_file_day_before()
    {
        //Delete old file before 7 day
        $date = date_create(date('Y-m-d H:i:s'));
        date_sub($date,date_interval_create_from_date_string("7 days"));
        $before_7_day = strtotime(date_format($date,"Y-m-d H:i:s"));

        foreach(glob(PURCHASE_IMPORT_VENDOR_ERROR . '*') as $file) {

            $file_arr = explode("/",$file);
            $filename = array_pop($file_arr);

            if(file_exists($file)) {
                $file_name_arr = explode("_",$filename);
                $date_create_file = array_pop($file_name_arr);
                $date_create_file =  str_replace('.xlsx', '', $date_create_file);

                if((float)$date_create_file <= (float)$before_7_day){
                    unlink(PURCHASE_IMPORT_VENDOR_ERROR.$filename);
                }
            }
        }
        return true;
    }

    /**
     * { import job position excel }
     */
    public function import_file_xlsx_vendor()
    {
        if(!class_exists('XLSXReader_fin')){
            require_once(module_dir_path(PURCHASE_MODULE_NAME).'/assets/plugins/XLSXReader/XLSXReader.php');
        }
        require_once(module_dir_path(PURCHASE_MODULE_NAME).'/assets/plugins/XLSXWriter/xlsxwriter.class.php');


        $filename ='';
        if($this->input->post()){
            if (isset($_FILES['file_csv']['name']) && $_FILES['file_csv']['name'] != '') {

                $this->delete_error_file_day_before();

                // Get the temp file path
                $tmpFilePath = $_FILES['file_csv']['tmp_name'];                
                // Make sure we have a filepath
                if (!empty($tmpFilePath) && $tmpFilePath != '') {
                    $tmpDir = TEMP_FOLDER . '/' . time() . uniqid() . '/';

                    if (!file_exists(TEMP_FOLDER)) {
                        mkdir(TEMP_FOLDER, 0755);
                    }

                    if (!file_exists($tmpDir)) {
                        mkdir($tmpDir, 0755);
                    }

                    // Setup our new file path
                    $newFilePath = $tmpDir . $_FILES['file_csv']['name'];                    

                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                        //Writer file
                        $writer_header = array(
                            _l('vendor_code')          =>'string',
                            _l('first_name')          =>'string',
                            _l('last_name')          =>'string',
                            _l('email')               =>'string',
                            _l('contact_phonenumber')       =>'string',
                            _l('position')             =>'string',
                            _l('company')                      =>'string',
                            _l('vat')                     =>'string',
                            _l('phonenumber')                     =>'string',
                            _l('country')                     =>'string',
                            _l('city')                     =>'string',
                            _l('zip')                     =>'string',
                            _l('state')                     =>'string',
                            _l('address')                     =>'string',
                            _l('website')                     =>'string',
                            _l('bank_detail')                     =>'string',
                            _l('payment_terms')                     =>'string',
                            _l('pur_billing_street')                     =>'string',
                            _l('pur_billing_city')                     =>'string',
                            _l('pur_billing_state')                     =>'string',
                            _l('pur_billing_zip')                     =>'string',
                            _l('pur_billing_country')                     =>'string',
                            _l('pur_shipping_street')                     =>'string',
                            _l('pur_shipping_city')                     =>'string',
                            _l('pur_shipping_state')                     =>'string',
                            _l('pur_shipping_zip')                     =>'string',
                            _l('pur_shipping_country')                     =>'string',
                            _l('error')                     =>'string',
                        );

                        $widths_arr = array();
                        for($i = 1; $i <= count($writer_header); $i++ ){
                            $widths_arr[] = 40;
                        }

                        $writer = new XLSXWriter();
                        $writer->writeSheetHeader('Sheet1', $writer_header,  $col_options = ['widths'=>$widths_arr ]);

                        //Reader file
                        $xlsx = new XLSXReader_fin($newFilePath);
                        $sheetNames = $xlsx->getSheetNames();
                        $data = $xlsx->getSheetData($sheetNames[1]);

                        $total_rows = 0;
                        $total_row_false    = 0; 

                        for ($row = 1; $row < count($data); $row++) {

                            $total_rows++;

                            $rd = array();
                            $flag = 0;
                            $flag2 = 0;

                            $string_error ='';

                            $value_vendor_code    = isset($data[$row][0]) ? $data[$row][0] : '' ;
                            $value_fist_name    = isset($data[$row][1]) ? $data[$row][1] : '' ;
                            $value_last_name    = isset($data[$row][2]) ? $data[$row][2] : '';
                            $value_email   = isset($data[$row][3]) ? $data[$row][3] : '' ;
                            $value_contact_phonenumber      = isset($data[$row][4]) ? $data[$row][4] : '';
                            $value_position       = isset($data[$row][5]) ? $data[$row][5] : '';
                            $value_company            = isset($data[$row][6]) ? $data[$row][6] : '';
                            $value_vat            = isset($data[$row][7]) ? $data[$row][7] : '';
                            $value_phonenumber            = isset($data[$row][8]) ? $data[$row][8] : '';
                            $value_country            = isset($data[$row][9]) ? $data[$row][9] : '';
                            $value_city            = isset($data[$row][10]) ? $data[$row][10] : '';
                            $value_zip            = isset($data[$row][11]) ? $data[$row][11] : '';
                            $value_state            = isset($data[$row][12]) ? $data[$row][12] : '';
                            $value_address            = isset($data[$row][13]) ? $data[$row][13] : '';
                            $value_website            = isset($data[$row][14]) ? $data[$row][14] : '';
                            $value_bank_detail            = isset($data[$row][15]) ? $data[$row][15] : '';
                            $value_payment_terms            = isset($data[$row][16]) ? $data[$row][16] : '';
                            $value_pur_billing_street            = isset($data[$row][17]) ? $data[$row][17] : '';
                            $value_pur_billing_city            = isset($data[$row][18]) ? $data[$row][18] : '';
                            $value_pur_billing_state            = isset($data[$row][19]) ? $data[$row][19] : '';
                            $value_pur_billing_zip            = isset($data[$row][20]) ? $data[$row][20] : '';
                            $value_pur_billing_country            = isset($data[$row][21]) ? $data[$row][21] : '';
                            $value_pur_shipping_street            = isset($data[$row][22]) ? $data[$row][22] : '';
                            $value_pur_shipping_city            = isset($data[$row][23]) ? $data[$row][23] : '';
                            $value_pur_shipping_state            = isset($data[$row][24]) ? $data[$row][24] : '';
                            $value_pur_shipping_zip            = isset($data[$row][25]) ? $data[$row][25] : '';
                            $value_pur_shipping_country            = isset($data[$row][26]) ? $data[$row][26] : '';

                            if(is_null($value_vendor_code) == true || $value_vendor_code ==''){
                                $string_error .=_l('vendor_code'). _l('not_yet_entered');
                                $flag = 1;
                            }else{
                                $this->db->where('vendor_code', $value_vendor_code);
                                $total_rows_check = $this->db->count_all_results(db_prefix().'pur_vendor');
                                if ($total_rows_check > 0) {
                                    $string_error .=_l('vendor_code'). _l('already_exist');
                                    $flag = 1;
                                }
                            }
                            
                            if(is_null($value_fist_name) == true || $value_fist_name ==''){
                                $string_error .=_l('fist_name'). _l('not_yet_entered');
                                $flag = 1;
                            }

                            if(is_null($value_last_name) == true || $value_last_name ==''){
                                $string_error .=_l('last_name'). _l('not_yet_entered');
                                $flag = 1;
                            }

                            if(is_null($value_email) == true || $value_email ==''){
                                $string_error .=_l('email'). _l('not_yet_entered');
                                $flag = 1;
                            }else{
                                $this->db->where('email', $value_email);
                                $total_rows_check_email = $this->db->count_all_results(db_prefix().'pur_contacts');
                                if ($total_rows_check_email > 0) {
                                    $string_error .=_l('email'). _l('already_exist');
                                    $flag = 1;
                                }
                            }

                            if(is_null($value_company) == true || $value_company ==''){
                                $string_error .=_l('company'). _l('not_yet_entered');
                                $flag = 1;
                            }

                            if(($flag == 1) || $flag2 == 1 ){
                                //write error file
                                $writer->writeSheetRow('Sheet1', [
                                    $value_vendor_code,
                                    $value_fist_name,
                                    $value_last_name,
                                    $value_email,
                                    $value_contact_phonenumber,
                                    $value_position,
                                    $value_company,
                                    $value_vat,
                                    $value_phonenumber,
                                    $value_country,
                                    $value_city,
                                    $value_zip,
                                    $value_state,
                                    $value_address,
                                    $value_website,
                                    $value_bank_detail,
                                    $value_payment_terms,
                                    $value_pur_billing_street,
                                    $value_pur_billing_city,
                                    $value_pur_billing_state,
                                    $value_pur_billing_zip,
                                    $value_pur_billing_country,
                                    $value_pur_shipping_street,
                                    $value_pur_shipping_city,
                                    $value_pur_shipping_state,
                                    $value_pur_shipping_zip,
                                    $value_pur_shipping_country,
                                    $string_error,
                                ]);

                                // $numRow++;
                                $total_row_false++;
                            }

                            if($flag == 0 && $flag2 == 0){
                                $rd['vendor_code']                = $value_vendor_code;
                                $rd['firstname']                = $value_fist_name;
                                $rd['lastname']                     = $value_last_name;
                                $rd['email']     = $value_email;
                                $rd['contact_phonenumber']                = $value_contact_phonenumber;
                                $rd['title']                         = $value_position;
                                $rd['company']                         = $value_company;
                                $rd['vat']                         = $value_vat;
                                $rd['phonenumber']                         = $value_phonenumber;
                                $rd['country']                         = $value_country;
                                $rd['city']                         = $value_city;
                                $rd['zip']                         = $value_zip;
                                $rd['state']                         = $value_state;
                                $rd['address']                         = $value_address;
                                $rd['website']                         = $value_website;
                                $rd['bank_detail']                         = $value_bank_detail;
                                $rd['payment_terms']                         = $value_payment_terms;
                                $rd['billing_street']                         = $value_pur_billing_street;
                                $rd['billing_city']                         = $value_pur_billing_city;
                                $rd['billing_state']                         = $value_pur_billing_state;
                                $rd['billing_zip']                         = $value_pur_billing_zip;
                                $rd['billing_country']                         = $value_pur_billing_country;
                                $rd['shipping_street']                         = $value_pur_shipping_street;
                                $rd['shipping_city']                         = $value_pur_shipping_city;
                                $rd['shipping_state']                         = $value_pur_shipping_state;
                                $rd['shipping_zip']                         = $value_pur_shipping_zip;
                                $rd['shipping_country']                         = $value_pur_shipping_country;

                                $rows[] = $rd;
                                $response = $this->purchase_model->add_vendor($rd,null, true, true);

                            }


                        }

                        $total_rows = $total_rows;
                        $total_row_success = isset($rows) ? count($rows) : 0;
                        // $dataerror = $dataError;
                        $dataerror = '';
                        $message ='Not enought rows for importing';

                        if($total_row_false != 0){
                            $filename = 'Import_vendor_error_'.get_staff_user_id().'_'.strtotime(date('Y-m-d H:i:s')).'.xlsx';
                            $writer->writeToFile(str_replace($filename, PURCHASE_IMPORT_VENDOR_ERROR.$filename, $filename));
                        }

                    }
                }
            }
        }


        if (file_exists($newFilePath)) {
            @unlink($newFilePath);
        }

        echo json_encode([
            'message'           => $message,
            'total_row_success' => $total_row_success,
            'total_row_false'   => $total_row_false,
            'total_rows'        => $total_rows,
            'site_url'          => site_url(),
            'staff_id'          => get_staff_user_id(),
            'filename'          => PURCHASE_IMPORT_VENDOR_ERROR.$filename,
        ]);
    }

    /**
     * { change delivery status }
     *
     * @param      integer  $status     The status
     * @param         $pur_order  The pur order
     * @return     json
     */
    public function change_delivery_status($status, $pur_order){
        $success = $this->purchase_model->change_delivery_status($status, $pur_order);
        $message = '';
        $html = '';
        $status_str = '';
        $class = '';
        if($success == true){
            $message = _l('change_delivery_status_successfully');
        }else{
            $message = _l('change_delivery_status_fail');
        }

        if(has_permission('purchase_orders', '', 'edit') || is_admin()){
            $html .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
            $html .= '<a href="#" class="dropdown-toggle text-dark" id="tablePurOderStatus-' . $pur_order . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
            $html .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
            $html .= '</a>';

            $html .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePurOderStatus-' . $pur_order . '">';

            if($status == 0){
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 1 ,' . $pur_order . '); return false;">
                             ' ._l('completely_delivered') . '
                          </a>
                       </li>';
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 2 ,' . $pur_order . '); return false;">
                             ' ._l('pending_delivered') . '
                          </a>
                       </li>';
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 3 ,' . $pur_order . '); return false;">
                             ' ._l('partially_delivered') . '
                          </a>
                       </li>';

                $status_str = _l('undelivered');
                $class = 'label-danger';
            }else if($status == 1){
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 0 ,' . $pur_order . '); return false;">
                             ' ._l('undelivered') . '
                          </a>
                       </li>';
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 2 ,' . $pur_order . '); return false;">
                             ' ._l('pending_delivered') . '
                          </a>
                       </li>';
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 3 ,' . $pur_order . '); return false;">
                             ' ._l('partially_delivered') . '
                          </a>
                       </li>';
                $status_str = _l('completely_delivered');
                $class = 'label-success';
            }else if($status == 2){ 
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 0 ,' . $pur_order . '); return false;">
                             ' ._l('undelivered') . '
                          </a>
                       </li>';
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 1 ,' . $pur_order . '); return false;">
                             ' ._l('completely_delivered') . '
                          </a>
                       </li>';
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 3 ,' . $pur_order . '); return false;">
                             ' ._l('partially_delivered') . '
                          </a>
                       </li>';
                $status_str = _l('pending_delivered');
                $class = 'label-info';
            }else if($status == 3){ 
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 0 ,' . $pur_order . '); return false;">
                             ' ._l('undelivered') . '
                          </a>
                       </li>';
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 1 ,' . $pur_order . '); return false;">
                             ' ._l('completely_delivered') . '
                          </a>
                       </li>';
                $html .= '<li>
                          <a href="#" onclick="change_delivery_status( 2 ,' . $pur_order . '); return false;">
                             ' ._l('pending_delivered') . '
                          </a>
                       </li>';
                $status_str = _l('partially_delivered');
                $class = 'label-warning';
            }
               

            $html .= '</ul>';
            $html .= '</div>';
        }

        echo json_encode([
            'success' => $success,
            'status_str' => $status_str,
            'class' => $class,
            'mess' => $message,
            'html' => $html,
        ]);
    }

    /**
     * { convert po payment }
     */
    /**
     * Adjust a vendor's supplier advance against one of their purchase invoices.
     *
     * Posts Dr Accounts Payable / Cr Advances to Suppliers per allocation and
     * reduces the invoice balance.
     */
    public function apply_advance_to_invoice($invoice_id)
    {
        if (!has_permission('purchase_invoices', '', 'edit') && !is_admin()) {
            access_denied('purchase_invoices');
        }

        $amount  = $this->input->post('amount');
        $applied = $this->purchase_model->apply_advance_to_invoice($invoice_id, $amount);

        if ($this->input->is_ajax_request()) {
            echo json_encode([
                'success' => $applied > 0,
                'applied' => $applied,
                'message' => $applied > 0
                    ? _l('pur_advance_applied_successfully', app_format_money($applied, pur_get_currency_by_id(get_invoice_currency_id($invoice_id))))
                    : _l('pur_advance_nothing_to_apply'),
            ]);
            die;
        }

        if ($applied > 0) {
            set_alert('success', _l('pur_advance_applied_successfully', app_format_money($applied, pur_get_currency_by_id(get_invoice_currency_id($invoice_id)))));
        } else {
            set_alert('warning', _l('pur_advance_nothing_to_apply'));
        }

        redirect(admin_url('purchase/purchase_invoice/' . $invoice_id));
    }

    public function convert_po_payment($pur_order){
        $success = $this->purchase_model->convert_po_payment($pur_order);
        $mess = '';
        if($success == true){
            $mess = _l('converted_succesfully');
        }else{
            $mess = _l('no_payments_are_converted');
        }

        echo json_encode([
            'mess' => $mess,
            'success' => $success,
        ]);
    }

    /**
     * Gets the comments.
     *
     * @param        $id     The identifier
     */
    public function get_comments($id, $type)
    {
        $data['comments'] = $this->purchase_model->get_comments($id, $type);
        $this->load->view('comments_template', $data);
    }

    /**
     * Adds a comment.
     */
    public function add_comment()
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->purchase_model->add_comment($this->input->post()),
            ]);
        }
    }

    /**
     * { edit comment }
     *
     * @param        $id     The identifier
     */
    public function edit_comment($id)
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->purchase_model->edit_comment($this->input->post(), $id),
                'message' => _l('comment_updated_successfully'),
            ]);
        }
    }

    /**
     * Removes a comment.
     *
     * @param        $id     The identifier
     */
    public function remove_comment($id)
    {
        $this->db->where('id', $id);
        $comment = $this->db->get(db_prefix().'pur_comments')->row();
        if ($comment) {
            if ($comment->staffid != get_staff_user_id() && !is_admin()) {
                echo json_encode([
                    'success' => false,
                ]);
                die;
            }
            echo json_encode([
                'success' => $this->purchase_model->remove_comment($id),
            ]);
        } else {
            echo json_encode([
                'success' => false,
            ]);
        }
    }

    /**
     * { coppy sale invoice }
     *
     * @param        $invoice  The invoice
     */
    public function coppy_sale_invoice($invoice){
        $this->load->model('currencies_model');
        $this->load->model('invoices_model');

        $inv = $this->invoices_model->get($invoice);
        $base_currency = $this->currencies_model->get_base_currency();

        $list_item = $this->purchase_model->create_purchase_request_row_template();
        $currency_rate = 1;
        $to_currency = $base_currency->id;
        if($inv->currency != 0 && $inv->currency != $base_currency->id){
            $inv_currency = pur_get_currency_by_id($inv->currency);
            $currency_rate = pur_get_currency_rate($base_currency->name, $inv_currency->name);;
            $to_currency = $inv->currency;
        }

        if($inv && isset($inv->items)){
            if(count($inv->items) > 0){

                $index_request = 0;
                foreach($inv->items as $key => $item){
                    $index_request++;

                    $item_taxes = get_invoice_item_taxes($item['id']); 

                    $tax = '';
                    $tax_value = 0;
                    $tax_name = [];
                    $tax_name[0] = '';
                    $tax_rate = '';

                    if(count($item_taxes) > 0){
                        foreach($item_taxes as $key => $_tax){
                            if(($key + 1) < count($item_taxes) ){
                                $tax .= $this->purchase_model->get_tax_by_tax_name($_tax['taxname']).'|';
                                
                                $tax_rate .= $_tax['taxrate'].'|';
                            }else{
                                $tax .= $this->purchase_model->get_tax_by_tax_name($_tax['taxname']);
                               
                                $tax_rate .= $_tax['taxrate'];
                            }
                            $tax_name[] = $_tax['taxname'];

                            $tax_value += $item['qty'] * $item['rate'] * $_tax['taxrate'] / 100;
                        }
                    }



                    $item_code = get_item_id_by_des($item['description']);
                    $item_text = $item['description'];
                    $unit_price = $item['rate'];
                    $unit_name = $item['unit'];
                    $into_money = (float) ($item['rate'] * $item['qty']);
                    $total = $tax_value + $into_money;
                    
                    $list_item .= $this->purchase_model->create_purchase_request_row_template('newitems[' . $index_request . ']', $item_code, $item_text, $unit_price, $item['qty'], $unit_name, '', $into_money, $index_request, $tax_value, $total, $tax_name, $tax_rate, $tax, false, $currency_rate, $to_currency);
                }
            }
        }

        echo json_encode([
            'list_item' => $list_item,
            'currency' => $to_currency,
            'currency_rate' => $currency_rate,
        ]);
        
    }

    /**
     * { coppy sale invoice }
     *
     * @param        $invoice  The invoice
     */
    public function coppy_sale_estimate($estimate_id){
        $this->load->model('currencies_model');
        $this->load->model('estimates_model');

        $estimate = $this->estimates_model->get($estimate_id);
        $base_currency = $this->currencies_model->get_base_currency();

        $list_item = $this->purchase_model->create_purchase_request_row_template();
        $currency_rate = 1;
        $to_currency = $base_currency->id;
        if($estimate->currency != 0 && $estimate->currency != $base_currency->id){
            $es_currency = pur_get_currency_by_id($estimate->currency);
            $currency_rate = pur_get_currency_rate($base_currency->name, $es_currency->name);;
            $to_currency = $estimate->currency;
        }

        if($estimate && isset($estimate->items)){
            if(count($estimate->items) > 0){

                $index_request = 0;
                foreach($estimate->items as $key => $item){
                    $index_request++;

                    $item_taxes = get_invoice_item_taxes($item['id']); 

                    $tax = '';
                    $tax_value = 0;
                    $tax_name = [];
                    $tax_name[0] = '';
                    $tax_rate = '';

                    if(count($item_taxes) > 0){
                        foreach($item_taxes as $key => $_tax){
                            if(($key + 1) < count($item_taxes) ){
                                $tax .= $this->purchase_model->get_tax_by_tax_name($_tax['taxname']).'|';
                                
                                $tax_rate .= $_tax['taxrate'].'|';
                            }else{
                                $tax .= $this->purchase_model->get_tax_by_tax_name($_tax['taxname']);
                               
                                $tax_rate .= $_tax['taxrate'];
                            }
                            $tax_name[] = $_tax['taxname'];

                            $tax_value += $item['qty'] * $item['rate'] * $_tax['taxrate'] / 100;
                        }
                    }



                    $item_code = get_item_id_by_des($item['description']);
                    $item_text = $item['description'];
                    $unit_price = $item['rate'];
                    $unit_name = $item['unit'];
                    $into_money = (float) ($item['rate'] * $item['qty']);
                    $total = $tax_value + $into_money;
                    
                    $list_item .= $this->purchase_model->create_purchase_request_row_template('newitems[' . $index_request . ']', $item_code, $item_text, $unit_price, $item['qty'], $unit_name, '', $into_money, $index_request, $tax_value, $total, $tax_name, $tax_rate, $tax, false, $currency_rate, $to_currency);
                }
            }
        }

        echo json_encode([
            'list_item' => $list_item,
            'currency' => $to_currency,
            'currency_rate' => $currency_rate,
        ]);
        
    }

    /**
     * { inv by client }
     */
    public function inv_by_client(){
        $data_rs = [];
        $html = '';
        $base_currency = get_base_currency();
        if($this->input->post()){
            $clients = $this->input->post('client');
            $currency = $base_currency->id;
            if($this->input->post('currency')){
                $post_currency = $this->input->post('currency');
                if($post_currency != 0 && $post_currency != '' && $post_currency != null){
                    $currency = $post_currency;
                }
            }

            if(is_array($clients)){
                foreach($clients as $cli){
                    $list_inv = $this->purchase_model->get_inv_by_client_for_po($cli, 'currency = '.$currency);
                    if(count($list_inv) > 0){
                        foreach($list_inv as $inv){
                            
                            $data_rs[] = $inv;
                            
                        }
                    }
                }
            }else{
                $data_rs = $this->purchase_model->get_invoice_for_pr('currency = '.$base_currency->id);
            }
        }else{
            $data_rs = $this->purchase_model->get_invoice_for_pr('currency = '.$base_currency->id);
        }

        $html .= '';
        foreach($data_rs as $rs){
            $html .= '<option value="'.$rs['id'].'">'.format_invoice_number($rs['id']).'</option>';
        }

        echo json_encode(['html' => $html]);
    }

    /**
     * { coppy sale invoice }
     *
     * @param        $invoice  The invoice
     */
    public function coppy_sale_invoice_po(){
        $this->load->model('currencies_model');
        $this->load->model('invoices_model');

        $sale_invoices = $this->input->post('sale_invoice');
        $list_item = $this->purchase_model->create_purchase_order_row_template();
        $index_request = 0;
        foreach($sale_invoices as $invoice){

            $inv = $this->invoices_model->get($invoice);
            $base_currency = $this->currencies_model->get_base_currency();

            

            $currency_rate = 1;
            $to_currency = $base_currency->id;
            if($inv->currency != 0 && $inv->currency != $base_currency->id){
                $inv_currency = pur_get_currency_by_id($inv->currency);
                $currency_rate = pur_get_currency_rate($base_currency->name, $inv_currency->name);;
                $to_currency = $inv->currency;
            }

            

            if($inv && isset($inv->items)){
                if(count($inv->items) > 0){
                    
                    foreach($inv->items as $key => $item){
                        $index_request++;

                        $item_taxes = get_invoice_item_taxes($item['id']); 

                        $tax = '';
                        $tax_value = 0;
                        $tax_name = [];

                        $tax_rate = '';

                        if(count($item_taxes) > 0){
                            foreach($item_taxes as $key => $_tax){
                                if(($key + 1) < count($item_taxes) ){
                                    $tax .= $this->purchase_model->get_tax_by_tax_name($_tax['taxname']).'|';
                                    
                                    $tax_rate .= $_tax['taxrate'].'|';
                                }else{
                                    $tax .= $this->purchase_model->get_tax_by_tax_name($_tax['taxname']);
                                   
                                    $tax_rate .= $_tax['taxrate'];
                                }
                                $tax_name[] = $_tax['taxname'];

                                $tax_value += $item['qty'] * $item['rate'] * $_tax['taxrate'] / 100;
                            }
                        }

                        $item_code = get_item_id_by_des($item['description']);
                        $item_text = $item['description'];
                        $unit_price = $item['rate'];
                        $unit_name = $item['unit'];
                        $into_money = (float) ($item['rate'] * $item['qty']);
                        $total = $tax_value + $into_money;

                        $list_item .= $this->purchase_model->create_purchase_order_row_template('newitems[' . $index_request . ']', $item_text, $item['long_description'], $item['qty'], $unit_name, $unit_price, $tax_name, $item_code, '', $tax_rate, $total, '', '', $total, $into_money, $tax, $tax_value, $index_request, false, $currency_rate, $to_currency);
                    }
                }
            }
        }


        echo json_encode([
            
            'list_item' => $list_item,
            'currency' => $to_currency,
            'currency_rate' => $currency_rate,
        ]);
    }

        /**
     * { table vendor }
     */
    public function dashboard_po_table()
    {
        $this->app->get_table_data(module_views_path('purchase', 'dashboard_po_table'));
    }

    /**
     * Compares the quote pur request.
     *
     * @param        $pur_request  The pur request
     */
    public function compare_quote_pur_request($pur_request){
        if($this->input->post()){
            $data = $this->input->post();
            $success = $this->purchase_model->update_compare_quote($pur_request, $data);
            if($success){
                set_alert('success', _l('updated_successfully'));
            }
            redirect(admin_url('purchase/view_pur_request/'. $pur_request));
        }
    }

    /**
     * { debit notes }
     *
     * @param      string  $id     The identifier
     */
    public function debit_notes($id = ''){
        purchase_token();
        if (!has_permission('purchase_debit_notes', '', 'view') && !is_admin() && !has_permission('purchase_debit_notes', '', 'view_own')) {
            access_denied('debit_notes');
        }
        purchase_init();
        close_setup_menu();

        $data['years']          = $this->purchase_model->get_debits_years();
        $data['statuses']       = $this->purchase_model->get_debit_note_statuses();
        $data['debit_note_id'] = $id;
        $data['title']          = _l('pur_debit_note');
        $this->load->view('debit_notes/manage', $data);
    }

    /**
     * { debit notes table }
     */
    public function debit_notes_table(){
        $this->app->get_table_data(module_views_path('purchase', 'debit_notes/table_debit_notes'));
    }


    /**
     * { debit note }
     *
     * @param      string  $id     The identifier
     */
    public function debit_note($id = ''){
        if (!has_permission('purchase_debit_notes', '', 'view') && !is_admin() && !has_permission('purchase_debit_notes', '', 'view_own')) {
            access_denied('debit_notes');
        }
        if ($this->input->post()) {
            $debit_note_data = $this->input->post();
            $debit_note_data['terms'] = $this->input->post('terms', false);
            if ($id == '') {
                if (!has_permission('purchase_debit_notes', '', 'create')) {
                    access_denied('debit_notes');
                }
                $id = $this->purchase_model->add_debit_note($debit_note_data);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('debit_note')));
                    redirect(admin_url('purchase/debit_notes/' . $id));
                }
            } else {
                if (!has_permission('purchase_debit_notes', '', 'edit')) {
                    access_denied('debit_notes');
                }
                $success = $this->purchase_model->update_debit_note($debit_note_data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('debit_note')));
                }
                redirect(admin_url('purchase/debit_notes/' . $id));
            }
        }
        if ($id == '') {
            $title = _l('add_new', _l('debit_note'));
        } else {
            $debit_note = $this->purchase_model->get_debit_note($id);

            if (!has_permission('purchase_debit_notes', '', 'edit')) {
                access_denied('debit_notes');
            }

            if (!$debit_note || (!has_permission('purchase_debit_notes', '', 'view') && !has_permission('purchase_debit_notes', '', 'view_own') && $debit_note->addedfrom != get_staff_user_id() && !in_array($debit_note->vendorid, get_vendor_admin_list()))) {
                blank_page(_l('credit_note_not_found'), 'danger');
            }

            $data['debit_note'] = $debit_note;
            $data['edit']        = true;
            $title               = _l('edit', _l('debit_note')) . ' - ' . format_debit_note_number($debit_note->id);
        }

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        $this->load->model('taxes_model');
        $data['taxes'] = $this->taxes_model->get();
        $this->load->model('invoice_items_model');

       
        $data['vendors'] = [];


        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->purchase_model->pur_get_grouped('can_be_purchased');
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }

        $data['title']     = $title;
        $data['bodyclass'] = 'credit-note';
        $this->load->view('debit_notes/debit_note', $data);
    }

    /**
     * { validate number }
     */
    public function validate_debit_note_number()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');
        if ($isedit == 'true') { 
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }
        if (total_rows(db_prefix() . 'pur_debit_notes', [
            'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
            'number' => $number,
        ]) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    /**
     * { vendor change data }
     *
     * @param        $vendor  The vendor
     */
    public function vendor_change_data($vendor){
        if ($this->input->is_ajax_request()) {
            $this->load->model('currencies_model');
            $data                     = [];
            $data['billing_shipping'] = $this->purchase_model->get_vendor_billing_and_shipping_details($vendor);
            $data['vendor_currency']  = get_vendor_currency($vendor);

            $option_html = '';

            if(total_rows(db_prefix().'pur_vendor_items', ['vendor' => $vendor]) <= ajax_on_total_items()){
                $items = $this->purchase_model->get_items_by_vendor_variation($vendor);
                $option_html .= '<option value=""></option>';
                foreach($items as $item){
                    $option_html .= '<option value="'.$item['id'].'" >'.$item['label'].'</option>';
                }
            }

            $data['option_html'] = $option_html;
            
            echo json_encode($data);
        }
    }

    /**
     * Gets the debit note data ajax.
     *
     * @param        $id     The identifier
     */
    public function get_debit_note_data_ajax($id)
    {
        if (!has_permission('purchase_debit_notes', '', 'view') && !is_admin() && !has_permission('purchase_debit_notes', '', 'view_own')) {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die(_l('debit_note_not_found'));
        }

        $debit_note = $this->purchase_model->get_debit_note($id);

        if (!$debit_note || (!has_permission('purchase_debit_notes', '', 'view') && !has_permission('purchase_debit_notes', '', 'view_own') )) {
            echo _l('debit_note_not_found');
            die;
        }

        if(has_permission('purchase_debit_notes', '', 'view_own') && !is_admin()){
            if($debit_note->addedfrom != get_staff_user_id() && !in_array($debit_note->vendorid, get_vendor_admin_list(get_staff_user_id()))){
                echo _l('access_denied');
                die;
            }
        }

        $data['vendor_contacts'] = $this->purchase_model->get_contacts($debit_note->vendorid);
        $data['debit_note']                   = $debit_note;
        $data['members']                       = $this->staff_model->get('', ['active' => 1]);
        $data['available_debitable_invoices'] = $this->purchase_model->get_available_debitable_invoices($id);

        $this->load->view('debit_notes/debit_note_preview_template', $data);
    }

    /**
     * { delete debit note }
     */
    public function delete_debit_note($id){
        if (!has_permission('purchase_debit_notes', '', 'delete')) {
            access_denied('debit_notes');
        }

        if (!$id) {
            redirect(admin_url('debit_notes'));
        }

        $debit_note = $this->purchase_model->get_debit_note($id);

        if ($debit_note->debit_used || $debit_note->status == 2) {
            $success = false;
        } else {
            $success = $this->purchase_model->delete_debit_note($id);
        }

        if ($success) {
            set_alert('success', _l('deleted', _l('debit_note')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('debit_note_lowercase')));
        }

        redirect(admin_url('purchase/debit_notes'));
    }

    /**
     * { apply debits to invoices }
     *
     * @param        $debit_note_id  The debit note identifier
     */
    public function apply_debits_to_invoices($debit_note_id)
    {
        $debitApplied = false;
        if ($this->input->post()) {
            foreach ($this->input->post('amount') as $invoice_id => $amount) {
                if ($this->purchase_model->apply_debits($debit_note_id, ['amount' => $amount, 'invoice_id' => $invoice_id])) {
                    $this->purchase_model->update_pur_invoice_status($invoice_id);
                    $debitApplied = true;
                }
            }
        }
        if ($debitApplied) {
            set_alert('success', _l('debits_successfully_applied_to_invoices'));
        }
        redirect(admin_url('purchase/debit_notes/' . $credit_note_id));
    }

    /**
     * { delete debit note applied debit }
     *
     * @param        $id          The identifier
     * @param        $debit_id   The debit identifier
     * @param        $invoice_id  The invoice identifier
     */
    public function delete_debit_note_applied_debit($id, $debit_id, $invoice_id)
    {
        if (has_permission('purchase_debit_notes', '', 'delete')) {
            $this->purchase_model->delete_applied_debit($id, $debit_id, $invoice_id);
        }
        redirect(admin_url('purchase/debit_notes/' . $debit_id));
    }

    /**
     * { mark debit note open }
     *
     * @param        $id     The identifier
     */
    public function mark_debit_note_open($id)
    {
        if (total_rows(db_prefix() . 'pur_debit_notes', ['status' => 3, 'id' => $id]) > 0 && has_permission('purchase_debit_notes', '', 'edit')) {
            $this->purchase_model->mark_debit_note($id, 1);
        }

        redirect(admin_url('purchase/debit_notes/' . $id));
    }

    /**
     * { mark debit note void }
     *
     * @param        $id     The identifier
     */
    public function mark_debit_note_void($id)
    {
        $debit_note = $this->purchase_model->get_debit_note($id);
        if ($debit_note->status != 2 && $debit_note->status != 3 && !$debit_note->debits_used && has_permission('purchase_debit_notes', '', 'edit')) {
            $this->purchase_model->mark_debit_note($id, 3);
        }
        redirect(admin_url('purchase/debit_notes/' . $id));
    }

    /**
     * { refund }
     *
     * @param        $id         The identifier
     * @param        $refund_id  The refund identifier
     */
    public function refund_debit_note($id, $refund_id = null)
    {
        if (has_permission('purchase_debit_notes', '', 'edit')) {
            $this->load->model('payment_modes_model');
            if (!$refund_id) {
                $data['payment_modes'] = $this->payment_modes_model->get('', [
                    'expenses_only !=' => 1,
                ]);
            } else {
                $data['refund']        = $this->purchase_model->get_refund($refund_id);
                $data['payment_modes'] = $this->payment_modes_model->get('', [], true, true);
                $i                     = 0;
                foreach ($data['payment_modes'] as $mode) {
                    if ($mode['active'] == 0 && $data['refund']->payment_mode != $mode['id']) {
                        unset($data['payment_modes'][$i]);
                    }
                    $i++;
                }
            }

            $data['debit_note'] = $this->purchase_model->get_debit_note($id);
            $this->load->view('debit_notes/refund', $data);
        }
    }

    /**
     * Creates a refund.
     *
     * @param        $debit_note_id  The debit note identifier
     */
    public function create_refund($debit_note_id)
    {
        if (has_permission('purchase_debit_notes', '', 'edit')) {
            $data                = $this->input->post();
            $data['refunded_on'] = to_sql_date($data['refunded_on']);
            $data['staff_id']    = get_staff_user_id();
            $success             = $this->purchase_model->create_refund($debit_note_id, $data);

            if ($success) {
                set_alert('success', _l('added_successfully', _l('refund')));
            }
        }

        redirect(admin_url('purchase/debit_notes/' . $debit_note_id));
    }

    /**
     * { edit refund }
     *
     * @param        $refund_id      The refund identifier
     * @param        $debit_note_id  The debit note identifier
     */
    public function edit_refund($refund_id, $debit_note_id)
    {
        if (has_permission('purchase_debit_notes', '', 'edit')) {
            $data                = $this->input->post();
            $data['refunded_on'] = to_sql_date($data['refunded_on']);
            $success             = $this->purchase_model->edit_refund($refund_id, $data);

            if ($success) {
                set_alert('success', _l('updated_successfully', _l('refund')));
            }
        }

        redirect(admin_url('purchase/debit_notes/' . $debit_note_id));
    }

    /**
     * { delete refund }
     *
     * @param        $refund_id       The refund identifier
     * @param        $credit_note_id  The credit note identifier
     */
    public function delete_debit_refund($refund_id, $debit_note_id)
    {
        if (has_permission('purchase_debit_notes', '', 'delete')) {
            $success = $this->purchase_model->delete_refund($refund_id, $debit_note_id);
            if ($success) {
                set_alert('success', _l('deleted', _l('refund')));
            }
        }
        redirect(admin_url('purchase/debit_notes/' . $debit_note_id));
    }

    /**
     * { delete attachment }
     *
     * @param        $id     The identifier
     */
    public function delete_debit_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->purchase_model->delete_attachment($id);
        } else {
            ajax_access_denied();
        }
    }

    /* Generates credit note PDF and send to email */
    public function debit_note_pdf($id)
    {
        
        if (!$id) {
            redirect(admin_url('purchase/debit_notes'));
        }
        $debit_note        = $this->purchase_model->get_debit_note($id);
        $debit_note_number = format_debit_note_number($debit_note->id);

        try {
            $pdf = debit_note_pdf($debit_note);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(mb_strtoupper(slug_it($debit_note_number)) . '.pdf', $type);
    }

    /**
     * Sends a purchase order.
     * @return redirect
     */
    public function send_debit_note(){
        if($this->input->post()){
            $data = $this->input->post();
            $data['content'] = $this->input->post('content', false);
            $send = $this->purchase_model->send_debit_note($data);
            if($send){
                set_alert('success',_l('send_debit_note_successfully'));
                
            }else{
                set_alert('warning',_l('send_debit_note_fail'));
            }
            redirect(admin_url('purchase/debit_notes/'.$data['debit_note_id']));
            
        }
    }

    /**
     * { apply debits }
     *
     * @param        $invoice_id  The invoice identifier
     */
    public function apply_debits($invoice_id)
    {
        $total_debits_applied = 0;
        foreach ($this->input->post('amount') as $debit_id => $amount) {
            $success = $this->purchase_model->apply_debits($debit_id, [
            'invoice_id' => $invoice_id,
            'amount'     => $amount,
        ]);
            if ($success) {
                $total_debits_applied++;
            }
        }

        if ($total_debits_applied > 0) {
            $this->purchase_model->update_pur_invoice_status($invoice_id);
            set_alert('success', _l('invoice_credits_applied'));
        }
        redirect(admin_url('purchase/purchase_invoice/' . $invoice_id));
    }

    /**
     * { delete invoice applied debit }
     *
     * @param        $id          The identifier
     * @param        $debit_id    The debit identifier
     * @param        $invoice_id  The invoice identifier
     */
    public function delete_invoice_applied_debit($id, $debit_id, $invoice_id)
    {
        if (has_permission('purchase_debit_notes', '', 'delete')) {
            $this->purchase_model->delete_applied_debit($id, $debit_id, $invoice_id);
        }
        redirect(admin_url('purchase/purchase_invoice/' . $invoice_id));
    }

     /**
     * { table vendor pur order }
     *
     * @param      <type>  $vendor  The vendor
     */
    public function table_vendor_pur_invoices($vendor){
        $this->app->get_table_data(module_views_path('purchase', 'invoices/table_pur_invoices'),['vendor' => $vendor]);
    }

     /**
     * { table vendor debit notes }
     *
     * @param      <type>  $vendor  The vendor
     */
    public function table_vendor_debit_notes($vendor){
        $this->app->get_table_data(module_views_path('purchase', 'debit_notes/table_debit_notes'),['vendor' => $vendor]);
    }

    /**
     * { statement }
     */
    public function statement()
    {
        if (!has_permission('purchase_vendors', '', 'view') && !is_admin()) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad error');
            echo _l('access_denied');
            die;
        }

        $vendor_id = $this->input->get('vendor_id');
        $from        = $this->input->get('from');
        $to          = $this->input->get('to');

        $data['statement'] = $this->purchase_model->get_statement($vendor_id, to_sql_date($from), to_sql_date($to));

        $data['from'] = $from;
        $data['to']   = $to;

        $viewData['html'] = $this->load->view('vendors/groups/_statement', $data, true);

        echo json_encode($viewData);
    }

    /**
     * { statement pdf }
     */
    public function statement_pdf(){
        $vendor_id = $this->input->get('vendor_id');

        if (!has_permission('purchase_vendors', '', 'view') && !is_admin()) {
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('purchase/vendor/' . $vendor_id));
        }

        $from = $this->input->get('from');
        $to   = $this->input->get('to');

        $data['statement'] = $this->purchase_model->get_statement($vendor_id, to_sql_date($from), to_sql_date($to));

        try {
            $pdf = purchase_statement_pdf($data['statement']);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';
        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(slug_it(_l('vendor_statement') . '-' . $data['statement']['client']->company) . '.pdf', $type);
    }

    /**
     * Sends a purchase statment.
     * @return redirect
     */
    public function send_statement(){
        $vendor_id = $this->input->get('vendor_id');

        if (!has_permission('purchase_vendors', '', 'view')) {
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('purchase/vendor/' . $vendor_id));
        }

        $data = $this->input->post();

        $from = $this->input->get('from');
        $to   = $this->input->get('to');

        $data['from'] = $from;
        $data['to'] = $to;

        $data['content'] = $this->input->post('content', false);
        $data['vendor_id'] = $vendor_id;
        $success = $this->purchase_model->send_statement_to_email($data);

        if ($success) {
            set_alert('success', _l('statement_sent_to_vendor_success'));
        } else {
            set_alert('danger', _l('statement_sent_to_vendor_fail'));
        }

        redirect(admin_url('purchase/vendor/' . $vendor_id . '?group=purchase_statement'));
    }

    /**
     * permission modal
     * @return [type] 
     */
    public function permission_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $this->load->model('staff_model');

        if ($this->input->post('slug') === 'update') {
            $staff_id = $this->input->post('staff_id');
            $role_id = $this->input->post('role_id');

            $data = [ 'funcData' => ['staff_id'=> isset($staff_id) ? $staff_id : null ] ];

            if(isset($staff_id)) {
                $data['member']  = $this->staff_model->get($staff_id);
            }

            $data['roles_value']         = $this->roles_model->get();
            $data['staffs']  = purchase_get_staff_id_dont_permissions();
            $add_new = $this->input->post('add_new');

            if($add_new == ' hide'){
                $data['add_new']        = ' hide';
                $data['display_staff']  = '';
            }else{
                $data['add_new'] = '';
                $data['display_staff']  = ' hide';
            }


            $this->load->view('includes/permissions_modal', $data);
        }
    }

    public function permission_table()
    {
        if ($this->input->is_ajax_request()) {

            $select = [
                'staffid',
                'CONCAT(firstname," ",lastname) as full_name',
                'firstname', //for role name
                'email',
                'phonenumber',
            ];
            $where = [];
            $where[] = 'AND '.db_prefix().'staff.admin != 1';

            $arr_staff_id = purchase_get_staff_id_permissions();

            if(count($arr_staff_id) > 0){
                $where[] = 'AND '.db_prefix().'staff.staffid IN (' . implode(', ', $arr_staff_id) . ')';
            }else{
                $where[] = 'AND '.db_prefix().'staff.staffid IN ("")';
            }

            $aColumns     = $select;
            $sIndexColumn = 'staffid';
            $sTable       = db_prefix() . 'staff';
            $join         = ['LEFT JOIN ' . db_prefix() . 'roles ON ' . db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role'];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [ db_prefix() . 'roles.name as role_name', db_prefix() . 'staff.role']);

            $output  = $result['output'];
            $rResult = $result['rResult'];

            $not_hide = '';

            foreach ($rResult as $aRow) {
                $row = [];

                $row[] = '<a href="' . admin_url('staff/member/' . $aRow['staffid']) . '">' . $aRow['full_name']  . '</a>';

                $row[] = $aRow['role_name'];
                $row[] = $aRow['email'];
                $row[] = $aRow['phonenumber'];

                $options ='';

                if(is_admin() || has_permission('purchase_settings', '', 'edit')){
                    $options = icon_btn('#', 'fa fa-pencil-square', 'btn-default', [
                        'title'   => _l('edit'),
                        'onclick' => 'permissions_update(' . $aRow['staffid'] . ', '.$aRow['role'].', '.$not_hide.'); return false;',
                    ]);
                }

                if(is_admin() || has_permission('purchase_settings', '', 'edit')){
                    $options .= icon_btn('purchase/delete_purchase_permission/' . $aRow['staffid'], 'fa fa-remove', 'btn-danger _delete', ['title' => _l('delete')]);
                }

                $row[] = $options;

                $output['aaData'][] = $row;
            }

            echo json_encode($output);
            die();
        }
    }

    /**
     * staff id changed
     * @param  [type] $staff_id 
     * @return [type]           
     */
    public function staff_id_changed($staff_id)
    {   
        $role_id = '';
        $status = 'false';
        $r_permission=[];

        $staff  = $this->staff_model->get($staff_id);

        if($staff){
            if(count($staff->permissions) > 0){
                foreach ($staff->permissions as $permission) {
                    $r_permission[$permission['feature']][] = $permission['capability'];
                }
            }

            $role_id = $staff->role;
            $status = 'true';

        }

        if(count($r_permission) > 0){
            $data=['role_id'   => $role_id, 'status'    => $status, 'permission' => 'true', 'r_permission' => $r_permission];
        }else{
            $data=['role_id'   => $role_id, 'status'    => $status, 'permission' => 'false', 'r_permission' => $r_permission];
        }

        echo json_encode($data); 
        die;
    }


    /**
     * purchase update permissions
     * @param  string $id 
     * @return [type]     
     */
    public function purchase_update_permissions($id = '')
    {
        if (!is_admin() && !has_permission('purchase_settings', '', 'edit')) {
            access_denied('purchase');
        }
        $data = $this->input->post();

        if(!isset($id) || $id == ''){
            $id   = $data['staff_id'];
        }


        if(isset($id) && $id != ''){

            $data = hooks()->apply_filters('before_update_staff_member', $data, $id);

            if (is_admin()) {
                if (isset($data['administrator'])) {
                    $data['admin'] = 1;
                    unset($data['administrator']);
                } else {
                    if ($id != get_staff_user_id()) {
                        if ($id == 1) {
                            return [
                                'cant_remove_main_admin' => true,
                            ];
                        }
                    } else {
                        return [
                            'cant_remove_yourself_from_admin' => true,
                        ];
                    }
                    $data['admin'] = 0;
                }
            }

            $this->db->where('staffid', $id);
            $this->db->update(db_prefix() . 'staff', [
                'role'  => $data['role']
            ]);

            $response = $this->staff_model->update_permissions((isset($data['admin']) && $data['admin'] == 1 ? [] : $data['permissions']), $id);
        }else{
            $this->load->model('roles_model');

            $role_id = $data['role'];
            unset($data['role']);
            unset($data['staff_id']);

            $data['update_staff_permissions'] = true;

            $response = $this->roles_model->update($data, $role_id);
        }

        if (is_array($response)) {
            if (isset($response['cant_remove_main_admin'])) {
                set_alert('warning', _l('staff_cant_remove_main_admin'));
            } elseif (isset($response['cant_remove_yourself_from_admin'])) {
                set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
            }
        } elseif ($response == true) {
            set_alert('success', _l('updated_successfully', _l('staff_member')));
        }
        redirect(admin_url('purchase/setting?group=permissions'));

    }


    /**
     * delete purchase permission
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_purchase_permission($id)
    {
        if(!is_admin() && !has_permission('purchase_settings', '', 'edit')) {
            access_denied('purchase');
        }

        $response = $this->purchase_model->delete_hr_profile_permission($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('pur_is_referenced', _l('permissions')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('permissions')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('permissions')));
        }
        redirect(admin_url('purchase/setting?group=permissions'));

    }

    /**
     * { update customfield invoice }
     *
     * @param        $id     The identifier
     */
    public function update_customfield_invoice($id){
        if($this->input->post()){
            $data = $this->input->post();
            $success = $this->purchase_model->update_customfield_invoice($id,$data);
            if($success){
                $message = _l('updated_successfully');
                set_alert('success', $message);
            }
            redirect(admin_url('purchase/purchase_invoice/'.$id));
        }
    }

    /**
     * { refresh order value }
     */
    public function refresh_order_value($po_id){
        $success = false;
        if($po_id != ''){
            $success = $this->purchase_model->refresh_order_value($po_id);
        }

        echo json_encode([
            'success' => $success,
        ]);
    }

    /**
     * Gets the purchase request row template.
     */
    public function get_purchase_request_row_template(){
        $name = $this->input->post('name');
        $item_text = $this->input->post('item_text');
        $unit_price = $this->input->post('unit_price');
        $quantity = $this->input->post('quantity');
        $unit_name = $this->input->post('unit_name');
        $unit_id = $this->input->post('unit_id');
        $into_money = $this->input->post('into_money');
        $item_key = $this->input->post('item_key');
        $tax_value = $this->input->post('tax_value');
        $tax_name = $this->input->post('taxname');
        $total = $this->input->post('total');
        $item_code = $this->input->post('item_code');
        $currency_rate = $this->input->post('currency_rate');
        $to_currency = $this->input->post('to_currency');
        $item_description = $this->input->post('item_description');
        
        echo $this->purchase_model->create_purchase_request_row_template( $name, $item_code, $item_text, $unit_price, $quantity, $unit_name, $unit_id, $into_money, $item_key, $tax_value, $total, $tax_name, '', '', false, $currency_rate, $to_currency, $item_description);
    }

    /**
     * wh commodity code search
     * @return [type] 
     */
    public function pur_commodity_code_search($type = 'purchase_price', $can_be = 'can_be_purchased', $vendor = '')
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            echo json_encode($this->purchase_model->pur_commodity_code_search($this->input->post('q'), $type, $can_be, false, $vendor));
        }
    }

    /**
     * wh commodity code search
     * @return [type] 
     */
    public function pur_commodity_code_search_vendor_item($type = 'purchase_price', $can_be = 'can_be_purchased', $group = '')
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            echo json_encode($this->purchase_model->pur_commodity_code_search($this->input->post('q'), $type, $can_be, false, '', $group));
        }
    }

    /**
     * Gets the item by identifier.
     *
     * @param          $id             The identifier
     * @param      bool|int  $get_warehouse  The get warehouse
     * @param      bool      $warehouse_id   The warehouse identifier
     */
    public function get_item_by_id($id, $currency_rate = 1)
    {
        if ($this->input->is_ajax_request()) {
            $item                     = $this->purchase_model->get_item_v2($id);
            $item->long_description   = nl2br($item->long_description ?? '');

            if($currency_rate != 1){
                $item->purchase_price = round(($item->purchase_price*$currency_rate), 2);
            }
            
            $html = '<option value=""></option>';
           
            $item->warehouses_html = $html;

            echo json_encode($item);
        }
    }

    /**
     * Gets the quotation row template.
     */
    public function get_quotation_row_template(){
        $name = $this->input->post('name');
        $item_name = $this->input->post('item_name');
        $quantity = $this->input->post('quantity');
        $unit_name = $this->input->post('unit_name');
        $unit_price = $this->input->post('unit_price');
        $taxname = $this->input->post('taxname');
        $item_code = $this->input->post('item_code');
        $unit_id = $this->input->post('unit_id');
        $tax_rate = $this->input->post('tax_rate');
        $discount = $this->input->post('discount');
        $item_key = $this->input->post('item_key');
        $currency_rate = $this->input->post('currency_rate');
        $to_currency = $this->input->post('to_currency');

        echo $this->purchase_model->create_quotation_row_template($name, $item_name, $quantity, $unit_name, $unit_price, $taxname, $item_code, $unit_id, $tax_rate, '', $discount, '', '', '', '', '', $item_key, false, $currency_rate, $to_currency);
    }

    /**
     * Gets the purchase order row template.
     */
    public function get_purchase_order_row_template(){
        $name = $this->input->post('name');
        $item_name = $this->input->post('item_name');
        $item_description = $this->input->post('item_description');
        $quantity = $this->input->post('quantity');
        $unit_name = $this->input->post('unit_name');
        $unit_price = $this->input->post('unit_price');
        $taxname = $this->input->post('taxname');
        $item_code = $this->input->post('item_code');
        $unit_id = $this->input->post('unit_id');
        $tax_rate = $this->input->post('tax_rate');
        $discount = $this->input->post('discount');
        $item_key = $this->input->post('item_key');
        $currency_rate = $this->input->post('currency_rate');
        $to_currency = $this->input->post('to_currency');

        echo $this->purchase_model->create_purchase_order_row_template($name, $item_name, $item_description, $quantity, $unit_name, $unit_price, $taxname, $item_code, $unit_id, $tax_rate, '', $discount, '', '', '', '', '', $item_key, false, $currency_rate, $to_currency);
    }

    /**
     * currency rate table
     * @return [type] 
     */
    public function currency_rate_table(){
        $this->app->get_table_data(module_views_path('purchase', 'includes/currencies/currency_rate_table'));
    }

    /**
     * update automatic conversion
     */
    public function update_setting_currency_rate(){
        $data = $this->input->post();
        $success = $this->purchase_model->update_setting_currency_rate($data);
        if($success == true){
            $message = _l('updated_successfully', _l('setting'));
            set_alert('success', $message);
        }
        redirect(admin_url('purchase/setting?group=currency_rates'));
    }

    /**
     * Gets all currency rate online.
     */
    public function get_all_currency_rate_online()
    {
        $result = $this->purchase_model->get_all_currency_rate_online();
        if($result){
            set_alert('success', _l('updated_successfully', _l('pur_currency_rates')));
        }
        else{
            set_alert('warning', _l('no_data_changes', _l('pur_currency_rates')));                  
        }

        redirect(admin_url('purchase/setting?group=currency_rates'));
    }

    /**
     * update currency rate
     * @return [type] 
     */
    public function update_currency_rate($id)
    {
        if($this->input->post()){
            $data = $this->input->post();

            $result =  $this->purchase_model->update_currency_rate($data, $id);
            if($result){
                set_alert('success', _l('updated_successfully', _l('pur_currency_rates')));
            }
            else{
                set_alert('warning', _l('no_data_changes', _l('pur_currency_rates')));                  
            }
        }

        redirect(admin_url('purchase/setting?group=currency_rates'));
    }

    /**
     * Gets the currency rate online.
     *
     * @param        $id     The identifier
     */
    public function get_currency_rate_online($id)
    {
            $result =  $this->purchase_model->get_currency_rate_online($id);
            echo json_encode(['value' => $result]);
            die;
    }


    /**
     * delete currency
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_currency_rate($id){
        if($id != ''){
            $result =  $this->purchase_model->delete_currency_rate($id);
            if($result){
                set_alert('success', _l('deleted_successfully', _l('pur_currency_rates')));
            }
            else{
                set_alert('danger', _l('deleted_failure', _l('pur_currency_rates')));                   
            }
        }
        redirect(admin_url('purchase/setting?group=currency_rates'));
    }

    /**
     * currency rate modal
     * @return [type] 
     */
    public function currency_rate_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id=$this->input->post('id');

        $data=[];
        $data['currency_rate'] = $this->purchase_model->get_currency_rate($id);

        $this->load->view('includes/currencies/currency_rate_modal', $data);
    }

    /**
     * currency rate table
     * @return [type] 
     */
    public function currency_rate_logs_table(){
        $this->app->get_table_data(module_views_path('purchase', 'includes/currencies/currency_rate_logs_table'));
    }

    /**
     * { pur inv payment change }
     *
     * @param        $invoice_id  The invoice identifier
     */
    public function pur_inv_payment_change($invoice_id){
        $amount = purinvoice_left_to_pay($invoice_id);

        echo json_encode([
            'amount' => $amount,
        ]);
    }

     /**
     * Adds a payment on PO.
     *
     * @param      <type>  $pur_order  The purchase order id
     * @return  redirect
     */
    public function add_payment_on_po_with_inv($pur_order){
         if ($this->input->post()) {
            $data = $this->input->post();
            $message = '';
            $success = $this->purchase_model->add_payment_on_po_with_inv($data);
            if ($success) {
                $message = _l('added_successfully', _l('payment'));
            }
            set_alert('success', $message);
            redirect(admin_url('purchase/purchase_order/'.$pur_order));
        }
    }


    /**
     * { table vendor pur order }
     *
     * @param      <type>  $vendor  The vendor
     */
    public function table_project_pur_order($project_id){
        $this->app->get_table_data(module_views_path('purchase', 'purchase_order/table_pur_order'),['project' => $project_id]);
    }

    /**
     * Gets the project information.
     */
    public function get_project_info($pur_order){
        $po = $this->purchase_model->get_pur_order($pur_order);

        $this->load->model('projects_model');

        $base_currency = get_base_currency();

        $currency = $base_currency->id;

        $project_id = '';
        $customer = '';
        if($po->project != 0){
            $project = $this->projects_model->get($po->project);

            if($project){
                $project_id = $po->project;
                $customer = $project->clientid;
            }
        }

        if($po->currency != 0){
            $currency = $po->currency;
        }

        echo json_encode([
            'project_id' => $project_id,
            'customer' => $customer,
            'currency' => $currency,
            'vendor' => $po->vendor
        ]);
    }

    /**
     * Returns orders.
     */
    public function order_returns($id = ''){
        purchase_token();
        $data['title'] = _l('pur_return_orders');
        purchase_init();
        $data['delivery_id'] = $id;

        $data['from_date'] = _d(date('Y-m-d', strtotime( date('Y-m-d') . "-15 day")));
        $data['to_date'] = _d(date('Y-m-d'));
        
        $data['vendors'] = [];

        //display packing list not yet approval
        $data['rel_type'] = 'purchase_return_order';

        $this->load->view('return_orders/manage', $data);
    }

    /**
     * table manage packing list
     * @return [type] 
     */
    public function table_manage_order_return()
    {
        $this->app->get_table_data(module_views_path('purchase', 'return_orders/table_order_return'));
    }

    /**
     * save and send request send mail
     * @return [type] 
     */
    public function save_and_send_request_send_mail($data ='') {
        if ((isset($data)) && $data != '') {
            $this->purchase_model->send_mail($data);

            $success = 'success';
            echo json_encode([
                'success' => $success,
            ]);
        }
    }

    /**
     * { order return }
     */
    public function order_return($order_return_type = 'purchasing_return_order', $id = ''){
        $this->load->model('clients_model');

        if($id == ''){
            $data['title'] = _l('add_new_order_return');
        }else{
            $data['title'] = _l('update_order_return');
        }

        if ($this->input->post()) {
            $message = '';
            $data = $this->input->post();

            if (!$this->input->post('id')) {
               
                $mess = $this->purchase_model->add_order_return($data, $data['rel_type']);
                
                if ($mess) {
                    if($data['save_and_send_request'] == 'true'){
                        $this->save_and_send_request_send_mail(['rel_id' => $mess, 'rel_type' => 'order_return', 'addedfrom' => get_staff_user_id()]);
                    }
                    set_alert('success', _l('added_successfully'));
                } else {
                    set_alert('warning', _l('pur_add_order_return_failed'));
                }

                redirect(admin_url('purchase/order_returns/'.$mess));

            }else{
                $id = $this->input->post('id');

                $mess = $this->purchase_model->update_order_return($data, $data['rel_type'], $id);
                
                if($data['save_and_send_request'] == 'true'){
                    $this->save_and_send_request_send_mail(['rel_id' => $id, 'rel_type' => 'order_return', 'addedfrom' => get_staff_user_id()]);
                }

                if ($mess) {
                    set_alert('success', _l('updated_successfully'));
                } else {
                    set_alert('warning', _l('pur_update_order_return_failed'));
                }
                redirect(admin_url('purchase/order_returns/'.$id));
            }

        }

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->purchase_model->pur_get_grouped('can_be_purchased');
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }

        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $data['order_return_name_ex'] = 'ORDER_RETURN' . date('YmdHi');
        $data['clients'] = $this->clients_model->get();

        $order_return_row_template = $this->purchase_model->create_order_return_row_template('purchasing_return_order');

        if($id != ''){

            $order_return = $this->purchase_model->get_order_return($id);
            if (!$order_return) {
                blank_page('Order Return Not Found', 'danger');
            }
            $data['order_return_detail'] = $this->purchase_model->get_order_return_detail($id);
            $data['order_return'] = $order_return;

            if (count($data['order_return_detail']) > 0) {
                $index_receipt = 0;
                foreach ($data['order_return_detail'] as $order_return_detail) {
                    $index_receipt++;
                    $unit_name = pur_get_unit_name($order_return_detail['unit_id']);
                    $taxname = '';
                    $expiry_date = null;
                    $lot_number = null;
                    $commodity_name = $order_return_detail['commodity_name'];
                    
                    if(strlen($commodity_name) == 0){
                        $commodity_name = pur_get_item_variatiom($order_return_detail['commodity_code']);
                    }

                    $order_return_row_template .= $this->purchase_model->create_order_return_row_template($order_return->rel_type, $order_return_detail['rel_type_detail_id'], 'items[' . $index_receipt . ']', $commodity_name, $order_return_detail['quantity'], $unit_name, $order_return_detail['unit_price'], $taxname, $order_return_detail['commodity_code'], $order_return_detail['unit_id'] , $order_return_detail['tax_rate'], $order_return_detail['total_amount'], $order_return_detail['discount'], $order_return_detail['discount_total'], $order_return_detail['total_after_discount'], $order_return_detail['reason_return'], $order_return_detail['sub_total'],$order_return_detail['tax_name'],$order_return_detail['tax_id'], $order_return_detail['id'], true);
                    
                }
            }
        }

        $data['order_return_row_template'] = $order_return_row_template;

        $this->load->view('return_orders/order_return', $data);
    }

    /**
     * Get GRNs by PO for purchase return order (AJAX)
     */
    public function get_grns_by_po_for_return($po_id){
        if(!is_numeric($po_id)){
            echo json_encode(['html' => '']);
            die;
        }
        // Get GRNs that are approved and not already used in a return order
        $this->db->where('pr_order_id', $po_id);
        $this->db->where('approval', 1);
        $this->db->where('(is_deleted = 0 OR is_deleted IS NULL)');
        $this->db->where('id NOT IN (SELECT grn_id FROM '.db_prefix().'wh_order_returns WHERE grn_id IS NOT NULL AND grn_id != 0 AND status != "canceled")', NULL, FALSE);
        $grns = $this->db->get(db_prefix().'goods_receipt')->result_array();
        
        $html = '';
        foreach($grns as $grn){
            $html .= '<option value="'.$grn['id'].'">'.$grn['goods_receipt_code'].'</option>';
        }
        echo json_encode(['html' => $html]);
        die;
    }

    /**
     * Get return items by selected GRNs (AJAX - loads items from GRN delivered qty)
     */
    public function get_return_items_by_grns(){
        $grn_ids = $this->input->post('grn_ids');
        $po_id = $this->input->post('po_id');
        if(empty($grn_ids) || !is_array($grn_ids)){
            echo json_encode(['result' => '', 'number_of_item' => 0]);
            die;
        }

        // Get items delivered in selected GRNs
        $this->db->select('grd.commodity_code, grd.commodity_name, SUM(grd.quantities) as delivered_qty, grd.unit_price, grd.unit_id, grd.tax as tax_id, grd.tax_rate, grd.tax_name');
        $this->db->from(db_prefix().'goods_receipt_detail grd');
        $this->db->where_in('grd.goods_receipt_id', $grn_ids);
        $this->db->group_by('grd.commodity_code');
        $items = $this->db->get()->result_array();

        // Get already returned quantities for this PO
        $returned_qty = [];
        if(is_numeric($po_id)){
            $this->db->select('ord.commodity_code, SUM(ord.quantity) as returned_qty');
            $this->db->from(db_prefix().'wh_order_return_details ord');
            $this->db->join(db_prefix().'wh_order_returns orr', 'orr.id = ord.order_return_id');
            $this->db->where('orr.rel_id', $po_id);
            $this->db->where('orr.status !=', 'canceled');
            $this->db->group_by('ord.commodity_code');
            $ret_rows = $this->db->get()->result_array();
            foreach($ret_rows as $rr){
                $returned_qty[$rr['commodity_code']] = (float)$rr['returned_qty'];
            }
        }

        $row_template = '';
        $count = 0;
        foreach($items as $item){
            $commodity_code = $item['commodity_code'];
            $grn_qty = (float)$item['delivered_qty'];
            $already_returned = isset($returned_qty[$commodity_code]) ? $returned_qty[$commodity_code] : 0;
            $available_qty = $grn_qty - $already_returned;
            if($available_qty <= 0) continue;

            $count++;
            $unit_name = '';
            $data_unit = get_unit_type_item($item['unit_id']);
            if($data_unit) $unit_name = $data_unit->unit_name;

            $commodity_name = $item['commodity_name'];
            if(empty($commodity_name) && is_numeric($commodity_code)){
                $this->db->where('id', $commodity_code);
                $db_item = $this->db->get(db_prefix().'items')->row();
                if($db_item) $commodity_name = $db_item->description;
            }

            $taxname = isset($item['tax_name']) ? $item['tax_name'] : '';
            $tax_rate = isset($item['tax_rate']) ? $item['tax_rate'] : '';
            $tax_id = isset($item['tax_id']) ? $item['tax_id'] : '';
            $unit_price = (float)$item['unit_price'];
            $total_amount = $available_qty * $unit_price;

            $row_template .= $this->purchase_model->create_order_return_row_template(
                'purchasing_return_order', 
                $commodity_code, 
                'newitems['.$count.']', 
                $commodity_name, 
                $available_qty, 
                $unit_name, 
                $unit_price, 
                $taxname, 
                $commodity_code, 
                $item['unit_id'], 
                $tax_rate, 
                $total_amount, 
                0, 0, '', '', '', 
                $taxname, 
                $tax_id, 
                $commodity_code, 
                true, 
                $available_qty, 
                'partially'
            );
        }

        echo json_encode(['result' => $row_template, 'number_of_item' => $count]);
        die;
    }

    /**
     * order return get item data
     * @param  string $delivery_id 
     * @return [type]              
     */
    public function order_return_get_item_data($rel_id = 0, $rel_type = '', $return_type = 'fully')
    {
        if ($this->input->is_ajax_request()) {
            $data = [];
            if($rel_type == 'purchasing_return_order' ){
                $data = $this->purchase_model->pur_order_detail_order_return($rel_id, $return_type);
            }
            if($data){
                $po = $this->purchase_model->get_pur_order($rel_id);
                $base_currency = get_base_currency();
                if($po->currency != 0){
                    $base_currency = pur_get_currency_by_id($po->currency);
                }

                $discount_total = 0;
                if($po){
                    $discount_total = $po->discount_total;
                }

                $company_html = '<option value="'.(isset($data['company_id']) ? $data['company_id'] : '').'" >'.get_vendor_company_name(isset($data['company_id']) ? $data['company_id'] : '').'</option>';

                $result = [
                    'discount_total' => $discount_total,
                    'discount_type' => $po->discount_type,
                    'company_id' => $data['company_id'] ? $data['company_id'] : '',
                    'company_html' => $company_html,
                    'email' => $data['email'] ? $data['email'] : '',
                    'phonenumber' => $data['phonenumber'] ? $data['phonenumber'] : '',
                    'order_number' => $data['order_number'] ? $data['order_number'] : '',
                    'order_date' => $data['order_date'] ? $data['order_date'] : '',
                    'number_of_item' => $data['number_of_item'] ? $data['number_of_item'] : '',
                    'order_total' => $data['order_total'] ? $data['order_total'] : '',
                    'datecreated' => $data['datecreated'] ? $data['datecreated'] : '',
                    'additional_discount' => 0,
                    'main_additional_discount' => $data['main_additional_discount'] ? $data['main_additional_discount'] : '',
                    'result' => $data['result'] ? $data['result'] : '',
                    'currency' => $base_currency->id,
                    'return_type' => $return_type,
                ];
                echo json_encode($result);
                die;
            }
        }
    }

    /**
     * wh client data
     * @param  [type] $customer_id 
     * @return [type]              
     */
    public function pur_client_data($customer_id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('clients_model');

            $phonenumber = '';
            $email = '';
            $currency = '';
            $fee_return_order = 0;
            $return_policies_information = '';
            if($rel_type == 'purchasing_return_order'){
                                    $this->load->model('purchase/purchase_model');
                $vendor = $this->purchase_model->get_vendor($customer_id);
                if($vendor){
                    $phonenumber = $vendor->phonenumber;
                    $contacts = $this->purchase_model->get_contacts($customer_id);
                    if(count($contacts) > 0){
                        $email = $contacts[0]['email'];
                    }
                    $currency = $vendor->default_currency;
                    $fee_return_order = $vendor->return_order_fee;
                    $return_policies_information = $vendor->return_policies;
                }
            }

            echo json_encode([
                'fee_return_order' => $fee_return_order,
                'phonenumber' => $phonenumber,
                'email' => $email,
                'currency' => $currency,
                'return_policies_information' => $return_policies_information,
            ]);
        }
    }

    /**
     * view order return
     * @param  [type] $id 
     * @return [type]     
     */
    public function view_order_return($id)
    {
        //approval
        $send_mail_approve = $this->session->userdata("send_mail_approve");
        if ((isset($send_mail_approve)) && $send_mail_approve != '') {
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata("send_mail_approve");
        }
        $this->load->model('clients_model');

        $data['get_staff_sign'] = $this->purchase_model->get_staff_sign($id, 'order_return');
        $data['check_approve_status'] = $this->purchase_model->check_approval_details($id, 'order_return');
        $data['list_approve_status'] = $this->purchase_model->get_list_approval_details($id, 'order_return');


        //get vaule render dropdown select

        $data['units_code_name'] = $this->purchase_model->get_units_code_name();

        $data['order_return_detail'] = $this->purchase_model->get_order_return_detail($id);
        $data['order_return'] = $this->purchase_model->get_order_return($id);
        $data['order_return_refunds'] = $this->purchase_model->get_order_return_refunds($id);

        $data['activity_log'] = $this->purchase_model->pur_get_activity_log($id,'order_return');

        $data['title'] = _l('pur_order_return');
        $check_appr = $this->purchase_model->get_approve_setting('order_return');
        $data['check_appr'] = $check_appr;
        $data['tax_data'] = $this->purchase_model->get_html_tax_order_return($id);
        $this->load->model('currencies_model');

        $base_currency = $this->currencies_model->get_base_currency();
        if($data['order_return']->currency != 0){
            $base_currency = pur_get_currency_by_id($data['order_return']->currency);
        }

        $data['base_currency'] = $base_currency;

        $this->load->view('return_orders/view_order_return', $data);

    }

    /**
     * order return check before approval
     * @return [type] 
     */
    public function order_return_check_before_approval()
    {
        $data = $this->input->post();
            // packing list
            //check before send request approval
        if( $data['order_rel_type'] == 'manual'){
            echo json_encode([
                'success' => true,
                'message' => '',
            ]);
            die;
        }

    }

    /**
     * add activity
     */
    public function pur_add_activity()
    {
        $goods_delivery_id = $this->input->post('goods_delivery_id');


        if ($this->input->post()) {
            $description = $this->input->post('activity');
            $rel_type = $this->input->post('rel_type');
            $aId     = $this->purchase_model->log_pur_activity($goods_delivery_id, $rel_type, $description);
            
            if($aId){
                $status = true;
                $message = _l('added_successfully');
            }else{
                $status = false;
                $message = _l('added_failed');
            }

            echo json_encode([
                'status' => $status,
                'message' => $message,
            ]);
        }
    }

    /**
     * delete activitylog
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_activitylog($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $delete = $this->purchase_model->delete_activitylog($id);
        if($delete){
            $status = true;
        }else{
            $status = false;
        }

        echo json_encode([
            'success' => $status,
        ]);
    }

    /**
     * delete order return
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_order_return($id) {

        if(!has_permission('purchase_order_return', '', 'delete')  &&  !is_admin()) {
            access_denied('purchase]');
        }

        $response = $this->purchase_model->delete_order_return($id);
        if ($response == true) {
            set_alert('success', _l('deleted'));
        } else {
            set_alert('warning', _l('problem_deleting'));
        }
        redirect(admin_url('purchase/order_returns'));
    }

    /**
     * order return pdf
     * @param  [type] $id 
     * @return [type]     
     */
    public function order_return_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('purchase/order_returns'));
        }
        $this->load->model('clients_model');
        $this->load->model('currencies_model');

        $order_return_number = '';
        $order_return = $this->purchase_model->get_order_return($id);
        $order_return->client = $this->clients_model->get($order_return->company_id);
        $order_return->order_return_detail = $this->purchase_model->get_order_return_detail($id);

        $order_return->base_currency = $this->currencies_model->get_base_currency();
        if($order_return->currency != 0){
            $order_return->base_currency = pur_get_currency_by_id($order_return->currency);
        }

        $order_return->tax_data = $this->purchase_model->get_html_tax_order_return($id);
        $order_return->clientid = $order_return->company_id;


        if($order_return){
            $order_return_number .= $order_return->order_return_number.' - '.$order_return->order_return_name;
        }
        try {
            $pdf = $this->purchase_model->order_return_pdf($order_return);

        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }

        $type = 'D';
        ob_end_clean();

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(mb_strtoupper(slug_it($order_return_number)).'.pdf', $type);
    }

    /**
     * open warehouse modal
     * @return [type] 
     */
    public function open_warehouse_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $id = $this->input->post('order_return_id');

        $data = [];
        $data['title'] = _l('select_warehouse_to_create_inventory_delivery');
        $data['id'] = $id;
        $data['warehouses'] = [];
        if(get_status_modules_pur('warehouse')){
            $this->load->model('warehouse/warehouse_model');
            $data['warehouses'] = $this->warehouse_model->get_warehouse();
        }

        $this->load->view('return_orders/select_warehouse_modal', $data);
    }
/**
     * Gets the vendor shared.
     *
     * @param        $pr_id  The pr identifier
     */
    public function get_vendor_shared($pr_id){
        $purchase_request = $this->purchase_model->get_purchase_request($pr_id);
        $vendor_str = $purchase_request->send_to_vendors;
        $vendor_arr = [];

        $html = '<label for="send_to_vendors">'._l('pur_send_to_vendors').'</label><select name="send_to_vendors[]" id="send_to_vendors" class="ajax-sesarch" multiple="true" data-live-search="true" data-width="100%" data-none-selected-text="'. _l('ticket_settings_none_assigned').'" >';
        if($vendor_str != ''){
            $vendor_arr = explode(',', $vendor_str);
            foreach($vendor_arr as $vendor_id){
                $html .= '<option value="'.$vendor_id.'" selected>'.get_vendor_company_name($vendor_id).'</option>';
            }
        }
       
        $html .= '</select>';  

        echo json_encode([
            'shared_vendor' => $html,
        ]);
    }

    public function share_request(){
        if($this->input->post()){
            $data = $this->input->post();
            $success = $this->purchase_model->share_request_to_vendor($data);

            if($success){
                set_alert('success', _l('share_request_successfully') );
            }

            redirect(admin_url('purchase/purchase_request'));
        }
    }

    /**
     * { preview purchase order file }
     *
     * @param      <type>  $id      The identifier
     * @param      <type>  $rel_id  The relative identifier
     * @return  view
     */
    public function file_purrequest($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin']             = is_admin();
        $data['file'] = $this->purchase_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }
        $this->load->view('purchase_request/_file', $data);
    }

    /**
     * { delete purchase order attachment }
     *
     * @param      <type>  $id     The identifier
     */
    public function delete_purrequest_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);
        
        // Block deletion if PR is approved
        if($file){
            $pr = $this->purchase_model->get_purchase_request($file->rel_id);
            if($pr && $pr->status == 2){
                header('HTTP/1.0 400 Bad error');
                echo _l('access_denied');
                die;
            }
        }
        
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo json_encode(['success' => $this->purchase_model->delete_purrequest_attachment($id) ]);
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }


    /**
     * { change delivery status }
     *
     * @param      integer  $status     The status
     * @param         $pur_order  The pur order
     * @return     json
     */
    public function change_pr_approve_status($status, $pur_request){
        $success = $this->purchase_model->change_pr_approve_status($status, $pur_request);
        $message = '';
        $html = '';
        $status_str = '';
        $class = '';
        if($success == true){
            $message = _l('change_approval_status_successfully');
        }else{
            $message = _l('change_approval_status_fail');
        }

        // if(has_permission('purchase_orders', '', 'edit') || is_admin()){
        //     $html .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        //     $html .= '<a href="#" class="dropdown-toggle text-dark" id="tablePurOderStatus-' . $pur_request . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        //     $html .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        //     $html .= '</a>';

        //     $html .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePurOderStatus-' . $pur_request . '">';

        //     if($status == 1){
        //         $html .= '<li>
        //                   <a href="#" onclick="change_pr_approve_status( 2 ,' . $pur_request . '); return false;">
        //                      ' ._l('purchase_approved') . '
        //                   </a>
        //                </li>';
        //         $html .= '<li>
        //                   <a href="#" onclick="change_pr_approve_status( 3 ,' . $pur_request . '); return false;">
        //                      ' ._l('purchase_reject') . '
        //                   </a>
        //                </li>';

        //         $status_str = _l('purchase_draft');
        //         $class = 'label-primary';
        //     }else if($status == 2){
        //         $html .= '<li>
        //                   <a href="#" onclick="change_pr_approve_status( 1 ,' . $pur_request . '); return false;">
        //                      ' ._l('purchase_not_yet_approve') . '
        //                   </a>
        //                </li>';
        //         $html .= '<li>
        //                   <a href="#" onclick="change_pr_approve_status( 3 ,' . $pur_request . '); return false;">
        //                      ' ._l('purchase_reject') . '
        //                   </a>
        //                </li>';
        //         $status_str = _l('purchase_approved');
        //         $class = 'label-success';
        //     }else if($status == 3){ 
        //         $html .= '<li>
        //                   <a href="#" onclick="change_pr_approve_status( 1 ,' . $pur_request . '); return false;">
        //                      ' ._l('purchase_draft') . '
        //                   </a>
        //                </li>';
        //         $html .= '<li>
        //                   <a href="#" onclick="change_pr_approve_status( 2 ,' . $pur_request . '); return false;">
        //                      ' ._l('purchase_approved') . '
        //                   </a>
        //                </li>';

        //         $status_str = _l('purchase_reject');
        //         $class = 'label-warning';
        //     }
               

        //     $html .= '</ul>';
        //     $html .= '</div>';
        // }

        echo json_encode([
            'success' => $success,
            'status_str' => $status_str,
            'class' => $class,
            'mess' => $message,
            'html' => $html,
        ]);
    }

    /**
     * { table vendor pur order }
     *
     * @param      <type>  $vendor  The vendor
     */
    public function table_project_pur_contract($project_id){
        $this->app->get_table_data(module_views_path('purchase', 'contracts/table_contracts'),['project' => $project_id]);
    }

    /**
     * { table vendor pur order }
     *
     * @param      <type>  $vendor  The vendor
     */
    public function table_project_pur_request($project_id){
        $this->app->get_table_data(module_views_path('purchase', 'purchase_request/table_pur_request'),['project' => $project_id]);
    }


    /**
     * Gets the purchase order row template.
     */
    public function get_purchase_invoice_row_template(){
        $name = $this->input->post('name');
        $item_name = $this->input->post('item_name');
        $item_description = $this->input->post('item_description');
        $quantity = $this->input->post('quantity');
        $unit_name = $this->input->post('unit_name');
        $unit_price = $this->input->post('unit_price');
        $taxname = $this->input->post('taxname');
        $item_code = $this->input->post('item_code');
        $unit_id = $this->input->post('unit_id');
        $tax_rate = $this->input->post('tax_rate');
        $discount = $this->input->post('discount');
        $item_key = $this->input->post('item_key');
        $currency_rate = $this->input->post('currency_rate');
        $to_currency = $this->input->post('to_currency');

        echo $this->purchase_model->create_purchase_invoice_row_template($name, $item_name, $item_description, $quantity, $unit_name, $unit_price, $taxname, $item_code, $unit_id, $tax_rate, '', $discount, '', '', '', '', '', $item_key, false, $currency_rate, $to_currency );
    }

    /**
     * { mark return order as }
     *
     * @param        $status  The status
     * @param        $id      The identifier
     */
    public function mark_return_order_as($status, $id){
        $this->db->where('id', $id);
        $this->db->update(db_prefix().'wh_order_returns', ['status' => $status]);
        if($this->db->affected_rows() > 0){
            hooks()->do_action('after_pur_return_order_status_changed', ['id' => $id, 'status' => $status]);

            set_alert('success', _l('change_order_return_status_successfully'));
        }

        redirect(admin_url('purchase/order_returns/'.$id));
    }

     /**
     * { refund }
     *
     * @param        $id         The identifier
     * @param        $refund_id  The refund identifier
     */
    public function refund_order_return($id, $refund_id = null)
    {
        if (has_permission('purchase_order_return', '', 'edit')) {
            $this->load->model('payment_modes_model');
            if (!$refund_id) {
                $data['payment_modes'] = $this->payment_modes_model->get('', [
                    'expenses_only !=' => 1,
                ]);
            } else {
                $data['refund']        = $this->purchase_model->get_order_return_refund($refund_id);
                $data['payment_modes'] = $this->payment_modes_model->get('', [], true, true);
                $i                     = 0;
                foreach ($data['payment_modes'] as $mode) {
                    if ($mode['active'] == 0 && $data['refund']->payment_mode != $mode['id']) {
                        unset($data['payment_modes'][$i]);
                    }
                    $i++;
                }
            }

            $data['order_return'] = $this->purchase_model->get_order_return($id);
            $this->load->view('return_orders/refund', $data);
        }
    }

    /**
     * Creates a refund.
     *
     * @param        $order_return_id  The debit note identifier
     */
    public function create_order_return_refund($order_return_id)
    {
        if (has_permission('purchase_order_return', '', 'edit')) {
            $data                = $this->input->post();
            $data['refunded_on'] = to_sql_date($data['refunded_on']);
            $data['staff_id']    = get_staff_user_id();
            $success             = $this->purchase_model->create_order_return_refund($order_return_id, $data);

            if ($success) {
                set_alert('success', _l('added_successfully', _l('refund')));
            }
        }

        redirect(admin_url('purchase/order_returns/' . $order_return_id));
    }

    /**
     * { edit refund }
     *
     * @param        $refund_id      The refund identifier
     * @param        $order_return_id  The debit note identifier
     */
    public function edit_order_return_refund($refund_id, $order_return_id)
    {
        if (has_permission('purchase_order_return', '', 'edit')) {
            $data                = $this->input->post();
            $data['refunded_on'] = to_sql_date($data['refunded_on']);
            $success             = $this->purchase_model->edit_order_return_refund($refund_id, $data);

            if ($success) {
                set_alert('success', _l('updated_successfully', _l('refund')));
            }
        }

        redirect(admin_url('purchase/order_returns/' . $order_return_id));
    }


    /**
     * { delete refund }
     *
     * @param        $refund_id       The refund identifier
     * @param        $credit_note_id  The credit note identifier
     */
    public function delete_order_return_refund($refund_id, $order_return_id)
    {
        if (has_permission('purchase_debit_notes', '', 'delete')) {
            $success = $this->purchase_model->delete_order_return_refund($refund_id, $order_return_id);
            if ($success) {
                set_alert('success', _l('deleted', _l('refund')));
            }
        }
        redirect(admin_url('purchase/order_returns/' . $order_return_id));
    }

    /**
     * { update delivery status }
     *
     * @param      <type>  $pur_order  The pur order
     * @param      <type>  $status     The status
     */
    public function mark_pur_order_as( $status, $pur_order){
        
        $this->db->where('id', $pur_order);
        $this->db->update(db_prefix().'pur_orders', ['order_status' => $status]);
        if ($this->db->affected_rows() > 0) {
            if($status == 'delivered'){
                $this->db->where('id', $pur_order);
                $this->db->update(db_prefix().'pur_orders', ['delivery_status' => 1, 'delivery_date' => date('Y-m-d')]);


                $data_log = [];
                $data_log['rel_id'] = $pur_order;
                $data_log['rel_type'] = 'purchase_order';
                $data_log['staffid'] = get_staff_user_id();
                $data_log['date'] = date('Y-m-d H:i:s');
                $data_log['note'] = _l('purchase_order_change_delivery_stautus_to'). ' '.pur_get_delivery_status_str(1);
                $this->purchase_model->add_activity_log($data_log);

            }else{
                $this->db->where('id', $pur_order);
                $this->db->update(db_prefix().'pur_orders', ['delivery_status' => 0]);


                $data_log = [];
                $data_log['rel_id'] = $pur_order;
                $data_log['rel_type'] = 'purchase_order';
                $data_log['staffid'] = get_staff_user_id();
                $data_log['date'] = date('Y-m-d H:i:s');
                $data_log['note'] = _l('purchase_order_change_delivery_stautus_to'). ' '.pur_get_delivery_status_str(0);
                $this->purchase_model->add_activity_log($data_log);
            }

            $data_log = [];
            $data_log['rel_id'] = $pur_order;
            $data_log['rel_type'] = 'purchase_order';
            $data_log['staffid'] = get_staff_user_id();
            $data_log['date'] = date('Y-m-d H:i:s');
            $data_log['note'] = _l('purchase_order_change_stautus_to'). ' '._l('pur_'.$status);
            $this->purchase_model->add_activity_log($data_log);

            hooks()->do_action('after_purchase_order_change_order_status', $pur_order);

            set_alert('success', _l('updated_successfully', _l('order_status')));
        }

        redirect(admin_url('purchase/purchase_order/' . $pur_order));
    }

     /**
     * { table vendor debit notes }
     *
     * @param      <type>  $vendor  The vendor
     */
    public function table_vendor_quoations($vendor){
        $this->app->get_table_data(module_views_path('purchase', 'quotations/table_estimates'),['vendor' => $vendor]);
    }

    /**
     * Gets the currency rate.
     *
     * @param        $currency_id  The currency identifier
     */
    public function get_currency_rate($currency_id){
        $base_currency = get_base_currency();

        $pr_currency = pur_get_currency_by_id($currency_id);

        $currency_rate = 1;
        $convert_str = ' ('.$base_currency->name.' => '.$base_currency->name.')'; 
        $currency_name = '('.$base_currency->name.')';
        if($base_currency->id != $pr_currency->id){
            $currency_rate = pur_get_currency_rate($base_currency->name, $pr_currency->name);
            $convert_str = ' ('.$base_currency->name.' => '.$pr_currency->name.')'; 
            $currency_name = '('.$pr_currency->name.')';
        }


        $data_rs = [];
        $html = '';
        $base_currency = get_base_currency();
        if($this->input->post()){
            $clients = $this->input->post('clients');
            

            foreach($clients as $cli){
                $list_inv = $this->purchase_model->get_inv_by_client_for_po($cli, 'currency = '.$currency_id);
                if(count($list_inv) > 0){
                    foreach($list_inv as $inv){
                        $data_rs[] = $inv;  
                    }
                }
            }
        }else{
            $data_rs = $this->purchase_model->get_invoice_for_pr('currency = '.$currency_id);
        }

        $html .= '';
        foreach($data_rs as $rs){
            $html .= '<option value="'.$rs['id'].'">'.format_invoice_number($rs['id']).'</option>';
        }

        echo json_encode([
            'html' => $html,
            'currency_rate' => $currency_rate,
            'convert_str' => $convert_str,
            'currency_name' => $currency_name,
        ]);

    }

    /**
     * { vendor contract change }
     *
     * @param        $vendor  The vendor
     */
    public function vendor_contract_change($vendor)
    {
        $pur_orders = $this->purchase_model->get_pur_order_approved_by_vendor($vendor);

        $html = '<option value=""></option>';
        foreach($pur_orders as $po){
            $html .= '<option value="'.$po['id'].'">'.$po['pur_order_number'].'</option>';
        }

        echo json_encode(['html' => $html]);
    }



    /* Change client status / active / inactive */
    public function change_vendor_status($id, $status)
    {
        if ($this->input->is_ajax_request()) {

            $this->db->where('userid', $id);
            $this->db->update(db_prefix() . 'pur_vendor', [
                'active' => $status,
            ]);
        }
    }

    /**
     * { confirm registration }
     *
     * @param        $vendor_id  The client identifier
     */
    public function confirm_registration($vendor_id)
    {
        if (!is_admin() && !has_permission('purchase_settings', '', 'edit') ) {
            access_denied('Vendor Confirm Registration, ID: ' . $vendor_id);
        }
        $success = $this->purchase_model->confirm_registration($vendor_id);
        if($success){
            set_alert('success', _l('vendor_registration_successfully_confirmed'));
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function allow_vendors_to_register(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * Sends a purchase order.
     * @return redirect
     */
    public function send_contract(){
        if($this->input->post()){
            $data = $this->input->post();
            $data['content'] = $this->input->post('content', false);
            $send = $this->purchase_model->send_contract($data);
            if($send){
                set_alert('success',_l('send_contract_successfully'));
                
            }else{
                set_alert('warning',_l('send_contract_fail'));
            }
            redirect(admin_url('purchase/contract/'.$data['contract_id']));
            
        }
    }

    /**
     * Get the template modal content
     *
     * @return string
     */
    public function modal_template()
    {
        $this->load->model('templates_model');
        $data['rel_type'] = $this->input->post('rel_type');

        // When modal is submitted, it returns to the proposal/contract that was being edited.
        $data['rel_id'] = $this->input->post('rel_id');

        if ($this->input->post('slug') == 'new') {
            $data['title'] = _l('add_template');
        } elseif ($this->input->post('slug') == 'edit') {
            $data['title'] = _l('edit_template');
            $data['id']    = $this->input->post('id');
            $this->authorize($data['id']);
            $data['template'] = $this->templates_model->get($data['id']);
        }

        $this->load->view('templates/template', $data);
    }

    /**
     * Manage template
     *
     * @param  int|null $id
     *
     */
    public function template($id = null)
    {
        $this->load->model('templates_model');
        $content = $this->input->post('content', false);
        $content = html_purify($content);

        $data['name']      = $this->input->post('name');
        $data['content']   = $content;
        $data['addedfrom'] = get_staff_user_id();
        $data['type']      = $this->input->post('rel_type');

        // so when modal is submitted, it returns to the proposal/contract that was being edited.
        $rel_id = $this->input->post('rel_id');

        if (is_numeric($id)) {
            $this->authorize($id);
            $success = $this->templates_model->update($id, $data);
            $message = _l('template_updated');
        } else {
            $success = $this->templates_model->create($data);
            $message = _l('template_added');
        }

        if ($success) {
            set_alert('success', $message);
        }

        redirect(admin_url('purchase/contract/' . $rel_id));
    }

    /**
     * { request quotation pdf }
     *
     * @param      <type>  $id     The identifier
     * @return pdf output
     */
    public function compare_quotation_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('purchase/purchase_request'));
        }

        $pur_request = $this->purchase_model->get_compare_quotation_pdf_html($id);

        try {
            $pdf = $this->purchase_model->compare_quotation_pdf($pur_request);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }
        
        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output('compare_quotation.pdf', $type);
    }

    /**
     * Save the shipping / customs header fields and the per-line packing figures
     * used by the Invoice & Packing List document.
     */
    public function save_pur_invoice_shipping($invoice_id)
    {
        if (!$this->input->post() || !is_numeric($invoice_id)) {
            show_404();
        }

        if (!has_permission('purchase_invoices', '', 'edit')) {
            access_denied('Edit Purchase Invoice');
        }

        $post = $this->input->post();

        $header = [
            'consignee_name'      => $post['consignee_name'] ?? null,
            'consignee_address'   => $post['consignee_address'] ?? null,
            'port_of_loading'     => $post['port_of_loading'] ?? null,
            'port_of_discharge'   => $post['port_of_discharge'] ?? null,
            'port_of_exit'        => $post['port_of_exit'] ?? null,
            'final_destination'   => $post['final_destination'] ?? null,
            'notify_party'        => $post['notify_party'] ?? null,
            'packing_list_number' => $post['packing_list_number'] ?? null,
        ];

        $this->db->where('id', $invoice_id);
        $this->db->update(db_prefix() . 'pur_invoices', $header);

        // Per-line packing figures. Blank stays NULL rather than 0 so the PDF can
        // leave the cell empty instead of declaring a zero weight to customs.
        if (!empty($post['packing']) && is_array($post['packing'])) {
            foreach ($post['packing'] as $detail_id => $row) {
                if (!is_numeric($detail_id)) {
                    continue;
                }

                $this->db->where('id', $detail_id);
                $this->db->where('pur_invoice', $invoice_id);
                $this->db->update(db_prefix() . 'pur_invoice_details', [
                    'pallets'      => (isset($row['pallets']) && $row['pallets'] !== '') ? $row['pallets'] : null,
                    'net_weight'   => (isset($row['net_weight']) && $row['net_weight'] !== '') ? $row['net_weight'] : null,
                    'gross_weight' => (isset($row['gross_weight']) && $row['gross_weight'] !== '') ? $row['gross_weight'] : null,
                ]);
            }
        }

        set_alert('success', _l('updated_successfully', _l('invoice_packing_list')));
        redirect(admin_url('purchase/purchase_invoice/' . $invoice_id));
    }

    /**
     * Invoice & Packing List PDF - two pages in the Freezone / customs layout.
     * The customs declaration sheet is not produced here; that comes from the
     * customs portal.
     */
    public function pur_invoice_packing_pdf($invoice_id){
        if (!$invoice_id) {
            redirect(admin_url('purchase/invoices'));
        }

        $html = $this->purchase_model->get_pur_invoice_packing_pdf_html($invoice_id);

        if (empty($html)) {
            show_404();
        }

        try {
            $pdf = $this->purchase_model->pur_invoice_packing_pdf($html);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }

        // Same DELETED watermark treatment as the standard invoice PDF
        $this->db->select('is_deleted, invoice_number');
        $this->db->where('id', $invoice_id);
        $inv_check = $this->db->get(db_prefix().'pur_invoices')->row();

        if($inv_check && $inv_check->is_deleted == 1){
            $pages = $pdf->getNumPages();
            for($i = 1; $i <= $pages; $i++){
                $pdf->setPage($i);
                $pdf->SetAlpha(0.15);
                $pdf->SetFont('helvetica', 'B', 60);
                $pdf->SetTextColor(255, 0, 0);
                $pdf->StartTransform();
                $pdf->Rotate(45, 105, 148);
                $pdf->Text(40, 140, 'DELETED');
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        $filename = 'invoice_packing_list';
        if($inv_check && !empty($inv_check->invoice_number)){
            $filename = 'Invoice_PackingList_' . $inv_check->invoice_number;
        }

        $pdf->Output($filename . '.pdf', $type);
    }

    /**
     * { purchase_invoice_pdf }
     */
    public function purchase_invoice_pdf($invoice_id){
        if (!$invoice_id) {
            redirect(admin_url('purchase/invoices'));
        }

        $purchase_invoice = $this->purchase_model->get_purchase_invoice_pdf_html($invoice_id);

        try {
            $pdf = $this->purchase_model->purchase_invoice_pdf($purchase_invoice);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }

        // Add "REVERSED" watermark if invoice is deleted
        $this->db->select('is_deleted');
        $this->db->where('id', $invoice_id);
        $inv_check = $this->db->get(db_prefix().'pur_invoices')->row();
        if($inv_check && $inv_check->is_deleted == 1){
            $pages = $pdf->getNumPages();
            for($i = 1; $i <= $pages; $i++){
                $pdf->setPage($i);
                $pdf->SetAlpha(0.15);
                $pdf->SetFont('helvetica', 'B', 60);
                $pdf->SetTextColor(255, 0, 0);
                $pdf->StartTransform();
                $pdf->Rotate(45, 105, 148);
                $pdf->Text(40, 140, 'DELETED');
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }
        
        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        // Clear any output buffered by PHP notices in development mode
        if (ob_get_level()) {
            ob_end_clean();
        }

        $pdf->Output('purchase_invoice.pdf', $type);
    }

    /**
     * [batch_payment_modal description]
     * @return [type] [description]
     */
    public function batch_payment_modal() {

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);

        $data['invoices'] = $this->purchase_model->get_unpaid_approve_invoices();
        $data['vendors'] = $this->db->select('userid,' . get_sql_select_vendor_company())
            ->where_in('userid', collect($data['invoices'])->pluck('vendor')->toArray())
            ->get(db_prefix() . 'pur_vendor')->result();

        // Get available debit notes per vendor for batch payment
        $vendor_debit_notes = [];
        foreach($data['vendors'] as $vendor){
            $this->db->select('id, prefix, number, total, vendorid');
            $this->db->where('vendorid', $vendor->userid);
            $this->db->where('status', 1);
            $debit_notes = $this->db->get(db_prefix().'pur_debit_notes')->result();
            foreach($debit_notes as &$dn){
                $dn->remaining = $this->purchase_model->total_remaining_debits_by_debit_note($dn->id);
                $dn->formatted_number = $dn->prefix . str_pad($dn->number, 5, '0', STR_PAD_LEFT);
            }
            // Only include debit notes with remaining balance > 0
            $vendor_debit_notes[$vendor->userid] = array_filter($debit_notes, function($dn){ return $dn->remaining > 0; });
        }
        $data['vendor_debit_notes'] = $vendor_debit_notes;

        $this->load->view('invoices/batch_payment_modal', $data);
    }

    /**
     * [add_batch_payment description]
     */
    public function add_batch_payment()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        if (!staff_can('create', 'purchase_invoices')) {
            access_denied('Create Payment');
        }

        // ── Auto-migrate: ensure cheque columns exist ──────────────────────
        $this->_ensure_cheque_columns();

        $post    = $this->input->post();
        $is_ajax = $this->input->is_ajax_request();

        // Handle single shared cheque attachment — upload once, reuse filename for all cheque rows
        if(!empty($_FILES['shared_cheque_attachment']['name'])){
            $ext     = strtolower(pathinfo($_FILES['shared_cheque_attachment']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','pdf','gif'];
            if(in_array($ext, $allowed)){
                // Pass tmp_name directly — model copies it per payment in the same request
                $post['_shared_cheque_tmp'] = $_FILES['shared_cheque_attachment']['tmp_name'];
                $post['_shared_cheque_ext'] = $ext;
            }
        }

        $totalAdded = $this->purchase_model->add_batch_payment($post);

        if($is_ajax){
            if($totalAdded > 0){
                echo json_encode(['success'=>true, 'message'=>_l('batch_payment_added_successfully', $totalAdded), 'count'=>$totalAdded]);
            } else {
                echo json_encode(['success'=>false, 'message'=>'No payments were added. Please check amounts and payment modes.']);
            }
            die;
        }

        if ($totalAdded > 0) {
            set_alert('success', _l('batch_payment_added_successfully', $totalAdded));
        }
        return redirect(admin_url('purchase/invoices'));
    }

    /**
     * { change status pur order }
     *
     * @param      <type>  $status  The status
     * @param      <type>  $id      The identifier
     * @return json
     */
    public function change_status_pur_invoice($status,$id){
        $change = $this->purchase_model->change_status_pur_invoice($status,$id);
        if($change == true){
            
            $message = _l('change_status_pur_invoice').' '._l('successfully');
            echo json_encode([
                'result' => $message,
            ]);
        }else{
            $message = _l('change_status_pur_invoice').' '._l('fail');
            echo json_encode([
                'result' => $message,
            ]);
        }
    }

    /**
     * [sign_attachment description]
     * @return [type] [description]
     */
    public function sign_attachment()
    {
        if ($this->input->post()) {

            $data = $this->input->post();

            $check_approve_status = $this->purchase_model->check_sign_approval_details($data['approve_rel_id'],$data['approve_rel_type']);
            $success = false;

            if(isset($check_approve_status['id'])){
                $success = handle_upload_sign_image($data['approve_rel_id'],$data['approve_rel_type'], $check_approve_status['id']);
            }
            
            if($data['approve_rel_type'] == 'pur_order'){
                redirect(admin_url('purchase/purchase_order/'.$data['approve_rel_id']));
            }else if($data['approve_rel_type'] == 'pur_request'){
                redirect(admin_url('purchase/view_pur_request/'.$data['approve_rel_id']));
            }else if($data['approve_rel_type'] == 'pur_quotation'){
                redirect(admin_url('purchase/quotations/'.$data['approve_rel_id']));
            }else if($data['approve_rel_type'] == 'purchase_invoice'){
                redirect(admin_url('purchase/purchase_invoice/'.$data['approve_rel_id']));
            }else if($data['approve_rel_type'] == 'payment_request'){
                redirect(admin_url('purchase/payment_invoice/'.$data['approve_rel_id']));
            }else if($data['approve_rel_type'] == 'faf_requests'){
                redirect(admin_url('purchase/faf_requests/'.$data['approve_rel_id']));
            }
          
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function allow_upload_esign_for_approve_type(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function can_edit_pr_number(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function can_edit_po_number(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }


    /**
     * { refresh order value }
     */
    public function refresh_order_value_fix(){

        $success = $this->purchase_model->refresh_order_value_fix();
        
        echo json_encode([
            'success' => $success,
        ]);
    }

    /**
     * [statistics_po_by_type_count description]
     * @return [type] [description]
     */
    public function statistics_po_by_type_count(){
        $this->load->model('currencies_model');
        $custom_date_select = $this->get_where_report_period(db_prefix() . 'pur_orders.order_date');
       
        $report_currency = $this->input->post('report_currency');
        $currency = pur_get_currency_by_id($report_currency);
        
        $currency_name = '';
        $currency_unit = '';
        if($currency){
            $currency_name = $currency->name;
            $currency_unit = $currency->symbol;
        }
        echo json_encode([
            'data_count' => $this->purchase_model->count_po_by_type($custom_date_select, $report_currency),
            'unit' => $currency_unit,
            'name' => $currency_name,
        ]);
        die();
    }

    /**
     * [statistics_po_by_type_cost description]
     * @return [type] [description]
     */
    public function statistics_po_by_type_cost(){
        $this->load->model('currencies_model');
        $custom_date_select = $this->get_where_report_period(db_prefix() . 'pur_orders.order_date');
       
        $report_currency = $this->input->post('report_currency');
        $currency = pur_get_currency_by_id($report_currency);
        
        $currency_name = '';
        $currency_unit = '';
        if($currency){
            $currency_name = $currency->name;
            $currency_unit = $currency->symbol;
        }
        echo json_encode([
            'data_count' => $this->purchase_model->cost_po_by_type($custom_date_select, $report_currency),
            'unit' => $currency_unit,
            'name' => $currency_name,
        ]);
        die();
    }

    /**
     * { purchase order setting }
     * @return  json
     */
    public function show_term_conditions_on_po_pdf(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_purchase_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * [download_file description]
     * @param  [type] $folder_indicator [description]
     * @param  string $attachmentid     [description]
     * @return [type]                   [description]
     */
    public function download_file($folder_indicator, $attachmentid = ''){

        $file = $this->purchase_model->get_file_by_id($attachmentid);

        $path = APP_MODULES_PATH.'/purchase/uploads/'.$folder_indicator.'/'.$file->rel_id.'/'.$file->file_name;
       
        force_download($path, null);
    }

    /* Change client status / active / inactive */
    public function change_status_file($id, $status)
    {
        if ($this->input->is_ajax_request()) {

            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'files', [
                'visible_to_vendor' => $status,
            ]);
        }
    }

    /**
     * [pur_department_required_condition description]
     * @return [type] [description]
     */
    public function pur_department_required_condition(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * [pur_order_project_required_condition description]
     * @return [type] [description]
     */
    public function pur_order_project_required_condition(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }


    /**
     * [pur_can_select_approvers_on_purchase_order_form description]
     * @return [type] [description]
     */
    public function pur_can_select_approvers_on_purchase_order_form(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }


    /**
     * [pur_can_select_approvers_on_faf_form description]
     * @return [type] [description]
     */
    public function pur_can_select_approvers_on_faf_form(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * [get_relation_data description]
     * @return [type] [description]
     */
    public function get_relation_data()
    {
        if ($this->input->post()) {
            $type = $this->input->post('type');
            $data = pur_get_relation_data($type, '', $this->input->post('extra'));
            if ($this->input->post('rel_id')) {
                $rel_id = $this->input->post('rel_id');
            } else {
                $rel_id = '';
            }

            $relOptions = pur_init_relation_options($data, $type, $rel_id);
            echo json_encode($relOptions);
            die;
        }
    }

    /**
     * [get_html_approval_setting_for_po description]
     * @param  string $id [description]
     * @return [type]     [description]
     */
     public function get_html_approval_setting_for_po($id = '')
    {
        $html = '';
        $staffs = $this->staff_model->get();
        $approver = [
                0 => ['id' => 'direct_manager', 'name' => _l('direct_manager')],
                1 => ['id' => 'head_of_department', 'name' => _l('department_manager')],
                2 => ['id' => 'staff', 'name' => _l('staff')]];
        $action = [ 
                    1 => ['id' => 'approve', 'name' => _l('approve')],
                    0 => ['id' => 'sign', 'name' => _l('sign')],
                ];

        $hr_record_status = 0; 
        if(get_status_modules_pur('hr_profile') == true){
            $hr_record_status = 1;
        }
        if(is_numeric($id)){
            $po = $this->purchase_model->get_pur_order($id);

            $setting = json_decode($po->approval_setting);

            $approver_md = '1';
            $hide_class = 'hide';
            $staff_md = '8';
            $approver_default = 'staff';
            $staff_hide = '';

            if(is_array($setting)){
                foreach ($setting as $key => $value) {

                    if($value->approver == 'staff'){
                        $staff_hide = '';
                    }else{
                        $staff_hide = 'hide';
                    }
                    if($key == 0){

                        $html .= '<div id="item_approve">
                                        <div class="col-md-11">
                                        <div class="col-md-'.$approver_md.' '.$hide_class.'"> '.
                                        render_select('approver['.$key.']',$approver,array('id','name'),'approver', $value->approver, array('data-id' => '0', 'required' => 'true'), [],'', 'approver_class').'
                                        </div>
                                        <div class="col-md-6" id="is_staff_0">
                                        '. render_select('staff['.$key.']',$staffs,array('staffid','full_name'),'staff', $value->staff).'
                                        </div>
                                        <div class="col-md-6">
                                            '. render_select('action['.$key.']',$action,array('id','name'),'action', $value->action).' 
                                        </div>
                                        </div>
                                        <div class="col-md-1 btn_apr">
                                        <span class="pull-bot">
                                            <button name="add" class="btn new_vendor_requests btn-success" data-ticket="true" type="button"><i class="fa fa-plus"></i></button>
                                            </span>
                                      </div>
                                    </div>';
                    }else{
                         $html .= '<div id="item_approve">
                                        <div class="col-md-11">
                                        <div class="col-md-'.$approver_md.' '.$hide_class.'"">
                                            '.
                                        render_select('approver['.$key.']',$approver,array('id','name'),'approver', $value->approver, array('data-id' => '0', 'required' => 'true'), [],'', 'approver_class').' 
                                        </div>
                                        <div class="col-md-6" id="is_staff_'.$key.'">
                                            '. render_select('staff['.$key.']',$staffs,array('staffid','full_name'),'staff', $value->staff).' 
                                        </div>
                                        <div class="col-md-6">
                                            '. render_select('action['.$key.']',$action,array('id','name'),'action', $value->action).' 
                                        </div>
                                        </div>
                                        <div class="col-md-1 btn_apr">
                                        <span class="pull-bot">
                                            <button name="add" class="btn remove_vendor_requests btn-danger" data-ticket="true" type="button"><i class="fa fa-minus"></i></button>
                                            </span>
                                      </div>
                                    </div>';
                    }
                }
            }
        }
        echo json_encode([
                    $html
                ]);
    }

    /* =====================================================================
     * Petty Cash Advance Request
     * ===================================================================== */

    /**
     * List of petty cash advances.
     */
    public function petty_cash_advances()
    {
        purchase_token();

        if (!has_permission('purchase_petty_cash', '', 'view') && !has_permission('purchase_petty_cash', '', 'view_own')) {
            access_denied('petty_cash_advances');
        }

        purchase_init();

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('purchase', 'petty_cash_advances/table_petty_cash_advance'));
        }

        $data['title'] = _l('pur_petty_cash_advances');

        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();

        $this->load->view('petty_cash_advances/manage', $data);
    }

    /**
     * Create or edit a petty cash advance.
     *
     * @param string $id Empty to create
     */
    public function petty_cash_advance($id = '')
    {
        if (!has_permission('purchase_petty_cash', '', 'create') && !has_permission('purchase_petty_cash', '', 'edit')) {
            access_denied('petty_cash_advances');
        }

        if ($this->input->post()) {
            $post_data = $this->input->post();

            // Free text block, kept unfiltered so wording is not mangled
            $post_data['purpose_of_advance'] = $this->input->post('purpose_of_advance', false);

            if ($id == '') {
                if (!has_permission('purchase_petty_cash', '', 'create')) {
                    access_denied('petty_cash_advances');
                }

                $new_id = $this->purchase_model->add_petty_cash_advance($post_data);

                if ($new_id) {
                    // Handled after the insert because the upload path is keyed on the id
                    handle_pur_petty_cash_attachment($new_id);
                    set_alert('success', _l('added_successfully'));

                    redirect(admin_url('purchase/petty_cash_advance_detail/' . $new_id));
                }

                set_alert('danger', _l('something_went_wrong'));
                redirect(admin_url('purchase/petty_cash_advances'));
            }

            if (!has_permission('purchase_petty_cash', '', 'edit')) {
                access_denied('petty_cash_advances');
            }

            // Content already signed off must not change underneath the approvers
            if ($this->purchase_model->petty_cash_advance_has_approval_action($id)) {
                set_alert('warning', _l('pur_pca_cannot_edit_approved'));
                redirect(admin_url('purchase/petty_cash_advance_detail/' . $id));
            }

            $updated  = $this->purchase_model->update_petty_cash_advance($post_data, $id);
            $uploaded = handle_pur_petty_cash_attachment($id);

            if ($updated || $uploaded) {
                set_alert('success', _l('updated_successfully'));
            }

            redirect(admin_url('purchase/petty_cash_advance_detail/' . $id));
        }

        if ($id == '') {
            $data['title'] = _l('pur_new_petty_cash_advance');

            list($number, $request_no) = $this->purchase_model->next_petty_cash_advance_number();
            $data['next_request_no']   = $request_no;
        } else {
            $data['advance'] = $this->purchase_model->get_petty_cash_advance($id);

            if (!$data['advance']) {
                blank_page(_l('pur_petty_cash_advance') . ' ' . _l('pur_pca_not_found'), 'danger');
            }

            if ($this->purchase_model->petty_cash_advance_has_approval_action($id)) {
                set_alert('warning', _l('pur_pca_cannot_edit_approved'));
                redirect(admin_url('purchase/petty_cash_advance_detail/' . $id));
            }

            $data['items']       = $this->purchase_model->get_petty_cash_advance_items($id);
            $data['attachments'] = $this->purchase_model->get_petty_cash_advance_attachments($id);
            $data['title']       = $data['advance']->request_no;
        }

        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();

        $this->load->model('staff_model');
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);

        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $data['vendors']    = $this->purchase_model->get_vendor();
        $data['categories'] = $this->db->order_by('name', 'ASC')->get(db_prefix() . 'expenses_categories')->result_array();

        $this->load->view('petty_cash_advances/petty_cash_advance', $data);
    }

    /**
     * Read-only view of an advance, including the approvals block.
     *
     * @param int $id
     */
    public function petty_cash_advance_detail($id)
    {
        if (!has_permission('purchase_petty_cash', '', 'view') && !has_permission('purchase_petty_cash', '', 'view_own')) {
            access_denied('petty_cash_advances');
        }

        $advance = $this->purchase_model->get_petty_cash_advance($id);

        if (!$advance) {
            blank_page(_l('pur_petty_cash_advance') . ' ' . _l('pur_pca_not_found'), 'danger');
        }

        // Without global view rights a user only reaches their own requests
        if (!has_permission('purchase_petty_cash', '', 'view')) {
            $staff_id = get_staff_user_id();

            if ($advance->created_by != $staff_id && $advance->employee != $staff_id) {
                access_denied('petty_cash_advances');
            }
        }

        $data['title']       = $advance->request_no;
        $data['advance']     = $advance;
        $data['items']       = $this->purchase_model->get_petty_cash_advance_items($id);
        $data['approvals']   = $this->purchase_model->get_petty_cash_advance_approvals($id);
        $data['attachments'] = $this->purchase_model->get_petty_cash_advance_attachments($id);
        $data['items_total'] = $this->purchase_model->petty_cash_advance_items_total($id);

        $data['activity_logs'] = $this->purchase_model->get_activity_logs($id, 'pur_petty_cash');

        $this->load->view('petty_cash_advances/petty_cash_advance_detail', $data);
    }

    /**
     * Record an approval or rejection against one role.
     */
    public function petty_cash_advance_approve()
    {
        if (!$this->input->is_ajax_request()) {
            ajax_access_denied();
        }

        // Recording an approval changes the document, so it needs edit rights
        if (!has_permission('purchase_petty_cash', '', 'edit')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die;
        }

        $id = $this->input->post('advance_id');

        if (!is_numeric($id) || !$this->purchase_model->get_petty_cash_advance($id)) {
            echo json_encode(['success' => false, 'message' => _l('pur_pca_approval_not_recorded')]);
            die;
        }

        $success = $this->purchase_model->save_petty_cash_advance_approval(
            $id,
            $this->input->post('role'),
            $this->input->post('status'),
            $this->input->post('note')
        );

        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('pur_pca_approval_recorded') : _l('pur_pca_approval_not_recorded'),
        ]);
        die;
    }

    /**
     * Delete a petty cash advance.
     *
     * @param int $id
     */
    public function delete_petty_cash_advance($id)
    {
        if (!has_permission('purchase_petty_cash', '', 'delete')) {
            access_denied('petty_cash_advances');
        }

        if (!$id) {
            redirect(admin_url('purchase/petty_cash_advances'));
        }

        if ($this->purchase_model->delete_petty_cash_advance($id)) {
            set_alert('success', _l('deleted_successfully'));
        } else {
            set_alert('danger', _l('problem_deleting'));
        }

        redirect(admin_url('purchase/petty_cash_advances'));
    }

    /**
     * Preview one advance attachment in a modal.
     *
     * @param int $id     tblfiles id
     * @param int $rel_id Advance id
     */
    public function file_petty_cash_advance($id, $rel_id)
    {
        if (!has_permission('purchase_petty_cash', '', 'view') && !has_permission('purchase_petty_cash', '', 'view_own')) {
            ajax_access_denied();
        }

        $data['file'] = $this->purchase_model->get_file($id, $rel_id);

        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }

        $this->load->view('petty_cash_advances/_file', $data);
    }

    /**
     * Delete one advance attachment.
     *
     * @param int $id tblfiles id
     */
    public function delete_petty_cash_advance_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);

        if (!$file) {
            header('HTTP/1.0 404 Not Found');
            die;
        }

        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo pur_html_entity_decode($this->purchase_model->delete_petty_cash_advance_attachment($id));

            return;
        }

        header('HTTP/1.0 400 Bad error');
        echo _l('access_denied');
        die;
    }

    /* =====================================================================
     * Procurement Justification & Bid Waiver Form
     * ===================================================================== */

    /**
     * List of bid waiver forms.
     */
    public function bid_waivers()
    {
        purchase_token();

        if (!has_permission('purchase_bid_waiver', '', 'view') && !has_permission('purchase_bid_waiver', '', 'view_own')) {
            access_denied('bid_waivers');
        }

        purchase_init();

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('purchase', 'bid_waivers/table_bid_waiver'));
        }

        $data['title'] = _l('pur_bid_waivers');

        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();

        $this->load->view('bid_waivers/manage', $data);
    }

    /**
     * Create or edit a bid waiver form.
     *
     * @param string $id Empty to create
     */
    public function bid_waiver($id = '')
    {
        if (!has_permission('purchase_bid_waiver', '', 'create') && !has_permission('purchase_bid_waiver', '', 'edit')) {
            access_denied('bid_waivers');
        }

        if ($this->input->post()) {
            $post_data = $this->input->post();

            // Rich text fields must bypass XSS filtering or the markup is mangled
            $post_data['purpose_of_purchase']     = $this->input->post('purpose_of_purchase', false);
            $post_data['technical_specification'] = $this->input->post('technical_specification', false);

            if ($id == '') {
                if (!has_permission('purchase_bid_waiver', '', 'create')) {
                    access_denied('bid_waivers');
                }

                $new_id = $this->purchase_model->add_bid_waiver($post_data);

                if ($new_id) {
                    // Attachments are handled after the insert because the upload path
                    // is keyed on the record id
                    handle_pur_bid_waiver_attachment($new_id);

                    // add_bid_waiver() raises the approval request itself, but the
                    // notification and email to the approvers are sent by the detail page
                    // on load, from this session key - that is the route every purchase
                    // document uses. Without it a form was routed to its approvers in
                    // silence: no bell notification and no email, so nobody knew it was
                    // waiting on them.
                    //
                    // Harmless when no chain is configured: send_mail() finds no pending
                    // approvers and sends nothing.
                    $this->session->set_userdata([
                        'send_mail_approve' => [
                            'rel_id'    => $new_id,
                            'rel_type'  => 'pur_bid_waiver',
                            'addedfrom' => get_staff_user_id(),
                        ],
                    ]);

                    set_alert('success', _l('added_successfully'));

                    redirect(admin_url('purchase/bid_waiver_detail/' . $new_id));
                }

                set_alert('danger', _l('something_went_wrong'));
                redirect(admin_url('purchase/bid_waivers'));
            }

            if (!has_permission('purchase_bid_waiver', '', 'edit')) {
                access_denied('bid_waivers');
            }

            // Content that has already been signed off must not change underneath the
            // people who approved it
            if ($this->purchase_model->bid_waiver_has_approval_action($id)) {
                set_alert('warning', _l('pur_bw_cannot_edit_approved'));
                redirect(admin_url('purchase/bid_waiver_detail/' . $id));
            }

            $updated  = $this->purchase_model->update_bid_waiver($post_data, $id);
            $uploaded = handle_pur_bid_waiver_attachment($id);

            if ($updated || $uploaded) {
                set_alert('success', _l('updated_successfully'));
            }

            redirect(admin_url('purchase/bid_waiver_detail/' . $id));
        }

        if ($id == '') {
            $data['title'] = _l('pur_new_bid_waiver');

            list($number, $request_no) = $this->purchase_model->next_bid_waiver_number();
            $data['next_request_no']   = $request_no;
        } else {
            $data['bid_waiver'] = $this->purchase_model->get_bid_waiver($id);

            if (!$data['bid_waiver']) {
                blank_page(_l('pur_bid_waiver') . ' ' . _l('pur_bw_not_found'), 'danger');
            }

            if ($this->purchase_model->bid_waiver_has_approval_action($id)) {
                set_alert('warning', _l('pur_bw_cannot_edit_approved'));
                redirect(admin_url('purchase/bid_waiver_detail/' . $id));
            }

            $data['quotations']  = $this->purchase_model->get_bid_waiver_quotations($id);
            $data['attachments'] = $this->purchase_model->get_bid_waiver_attachments($id);
            $data['title']       = $data['bid_waiver']->request_no;
        }

        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();

        $this->load->model('staff_model');
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);

        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $data['vendors'] = $this->purchase_model->get_vendor();

        $this->load->view('bid_waivers/bid_waiver', $data);
    }

    /**
     * Read-only view of a bid waiver form, including the approvals block.
     *
     * @param int $id
     */
    public function bid_waiver_detail($id)
    {
        if (!has_permission('purchase_bid_waiver', '', 'view') && !has_permission('purchase_bid_waiver', '', 'view_own')) {
            access_denied('bid_waivers');
        }

        $bid_waiver = $this->purchase_model->get_bid_waiver($id);

        if (!$bid_waiver) {
            blank_page(_l('pur_bid_waiver') . ' ' . _l('pur_bw_not_found'), 'danger');
        }

        // Without global view rights a user only reaches their own forms - or a form
        // they have been asked to approve. Without that second case a designated
        // approver would be notified and then denied the page the notification links
        // to, which is why view_pur_request() checks the approval rows the same way.
        if (!has_permission('purchase_bid_waiver', '', 'view')) {
            $staff_id = get_staff_user_id();

            $approve_access = total_rows(db_prefix() . 'pur_approval_details', [
                'staffid'  => $staff_id,
                'rel_type' => 'pur_bid_waiver',
                'rel_id'   => $id,
            ]);

            if ($bid_waiver->created_by != $staff_id
                && $bid_waiver->requestor != $staff_id
                && $approve_access == 0) {
                access_denied('bid_waivers');
            }
        }

        // Approver chain, resolved exactly as the Purchase Request and Purchase Order
        // pages resolve theirs. $id is passed explicitly so this form's own department
        // chain is matched - without it get_approve_setting() falls through to its
        // arbitrary "any setting for this type" branch.
        $data['check_appr']     = $this->purchase_model->get_approve_setting('pur_bid_waiver', '', $id);
        $data['appr_setting']   = $this->purchase_model->get_approve_setting_new('pur_bid_waiver');
        $data['get_staff_sign'] = $this->purchase_model->get_staff_sign($id, 'pur_bid_waiver');

        if (isset($data['appr_setting']->approval_type) && $data['appr_setting']->approval_type == 1) {
            $data['check_approve_status'] = $this->purchase_model->check_approval_details_new($id, 'pur_bid_waiver');
        } else {
            $data['check_approve_status'] = $this->purchase_model->check_approval_details($id, 'pur_bid_waiver');
        }

        $data['list_approve_status'] = $this->purchase_model->get_list_approval_details($id, 'pur_bid_waiver');

        // Approver notifications are sent by a second AJAX call the page fires on load.
        // Indirect, but it is the route every other purchase document uses and the
        // mail only goes out because the browser reloads this page after the post.
        $send_mail_approve = $this->session->userdata('send_mail_approve');
        if (isset($send_mail_approve) && $send_mail_approve != '') {
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata('send_mail_approve');
        }

        $data['title']       = $bid_waiver->request_no;
        $data['bid_waiver']  = $bid_waiver;
        $data['quotations']  = $this->purchase_model->get_bid_waiver_quotations($id);
        $data['approvals']   = $this->purchase_model->get_bid_waiver_approvals($id);
        $data['attachments'] = $this->purchase_model->get_bid_waiver_attachments($id);

        $data['activity_logs'] = $this->purchase_model->get_activity_logs($id, 'pur_bid_waiver');

        $this->load->view('bid_waivers/bid_waiver_detail', $data);
    }

    /**
     * Record an approval or rejection against one role.
     */
    public function bid_waiver_approve()
    {
        if (!$this->input->is_ajax_request()) {
            ajax_access_denied();
        }

        // Recording an approval is a change to the document, so it needs edit rights
        if (!has_permission('purchase_bid_waiver', '', 'edit')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die;
        }

        $id     = $this->input->post('bid_waiver_id');
        $role   = $this->input->post('role');
        $status = $this->input->post('status');
        $note   = $this->input->post('note');

        if (!is_numeric($id) || !$this->purchase_model->get_bid_waiver($id)) {
            echo json_encode(['success' => false, 'message' => _l('pur_bw_approval_not_recorded')]);
            die;
        }

        // Approval is the approver chain's decision, the same as it is for a Purchase
        // Request or a Purchase Order. Where a chain governs this form the section 6
        // boxes are a printed record only and this endpoint refuses outright.
        //
        // It has to refuse here and not merely hide the buttons: role and status arrive
        // from data- attributes on the page, so anyone holding the edit permission could
        // post straight to this endpoint and sign every box on any form - which is
        // exactly what happened on BW00000003, where one person signed four roles while
        // the designated approver had not seen it.
        if ($this->purchase_model->get_approve_setting('pur_bid_waiver', '', $id)) {
            echo json_encode(['success' => false, 'message' => _l('pur_bw_approval_chain_owns')]);
            die;
        }

        // bid_waiver_detail() limits a view_own user to their own forms. That check was
        // never repeated here, so such a user could sign a form they cannot even open
        // by posting another id.
        if (!has_permission('purchase_bid_waiver', '', 'view')) {
            $staff_id   = get_staff_user_id();
            $bid_waiver = $this->purchase_model->get_bid_waiver($id);

            if ($bid_waiver->created_by != $staff_id && $bid_waiver->requestor != $staff_id) {
                echo json_encode(['success' => false, 'message' => _l('access_denied')]);
                die;
            }
        }

        $success = $this->purchase_model->save_bid_waiver_approval($id, $role, $status, $note);

        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('pur_bw_approval_recorded') : _l('pur_bw_approval_not_recorded'),
        ]);
        die;
    }

    /**
     * Delete a bid waiver form.
     *
     * @param int $id
     */
    public function delete_bid_waiver($id)
    {
        if (!has_permission('purchase_bid_waiver', '', 'delete')) {
            access_denied('bid_waivers');
        }

        if (!$id) {
            redirect(admin_url('purchase/bid_waivers'));
        }

        if ($this->purchase_model->delete_bid_waiver($id)) {
            set_alert('success', _l('deleted_successfully'));
        } else {
            set_alert('danger', _l('problem_deleting'));
        }

        redirect(admin_url('purchase/bid_waivers'));
    }

    /**
     * Preview one bid waiver attachment in a modal.
     *
     * @param int $id     tblfiles id
     * @param int $rel_id Bid waiver id
     */
    public function file_bid_waiver($id, $rel_id)
    {
        if (!has_permission('purchase_bid_waiver', '', 'view') && !has_permission('purchase_bid_waiver', '', 'view_own')) {
            ajax_access_denied();
        }

        $data['file'] = $this->purchase_model->get_file($id, $rel_id);

        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }

        $this->load->view('bid_waivers/_file', $data);
    }

    /**
     * Delete one bid waiver attachment.
     *
     * @param int $id tblfiles id
     */
    public function delete_bid_waiver_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);

        if (!$file) {
            header('HTTP/1.0 404 Not Found');
            die;
        }

        // Either the uploader or an admin, matching how the other purchase
        // attachments behave
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo pur_html_entity_decode($this->purchase_model->delete_bid_waiver_attachment($id));

            return;
        }

        header('HTTP/1.0 400 Bad error');
        echo _l('access_denied');
        die;
    }

    /**
     * [faf_requests description]
     * @return [type] [description]
     */
    public function faf_requests($id = ''){
        purchase_token();
        if(!has_permission('purchase_faf', '', 'view') && !has_permission('purchase_faf', '', 'view_own')){
            access_denied('faf_requests');
        }
        purchase_init();
        $data['title'] = _l('pur_faf_requests');
        $data['fafid'] = $id;

        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();

        $this->load->model('staff_model');
        $data['staffs'] = $this->staff_model->get('', ['active' => 1]);


        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('purchase', 'faf_requests/table_faf_request'));
        }


        $this->load->view('faf_requests/manage', $data);
    }


    /**
     * [faf_request description]
     * @return [type] [description]
     */
    public function faf_request($id = ''){
        if(!has_permission('purchase_faf', '', 'create') && !has_permission('purchase_faf', '', 'edit')){
            access_denied('faf_requests');
        }

        if($this->input->post()){
            $faf_data = $this->input->post();
            $faf_data['summary'] = $this->input->post('summary', false);
            if($id == ''){
                if(!has_permission('purchase_faf', '', 'create')){
                    access_denied('faf_request');
                }

                $faf_id = $this->purchase_model->add_faf($faf_data);
                if($faf_id){
                    handle_pur_faf_attachment($faf_id);
                    set_alert('success', _l('added_successfully'));
                }
            }else{
                if(!has_permission('purchase_faf', '', 'edit')){
                    access_denied('faf_request');
                }

                $success = $this->purchase_model->update_faf($faf_data, $id);

                $upload = handle_pur_faf_attachment($id);

                if($success || $upload){
                    set_alert('success', _l('updated_successfully'));
                }
            }


            redirect(admin_url('purchase/faf_requests'));
        }


        if($id == ''){
            $data['title'] = _l('new_faf_request');
        }else{

            $data['faf_attachments'] = $this->purchase_model->get_faf_attachment($id);
            $data['faf'] = $this->purchase_model->get_faf($id);

            if(!has_permission('purchase_faf', '', 'view')){
               
            }

            $data['title'] = $data['faf']->reference_number;
        }

        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $this->load->model('departments_model');
        $data['departments'] = $this->departments_model->get();
        $data['staff']             = $this->staff_model->get('', ['active' => 1]);


        $this->load->view('faf_requests/faf_request', $data);

    }

    /**
     * [deletefaf_request description]
     * @return [type] [description]
     */
    public function delete_faf_request($id){
        if(!has_permission('purchase_faf', '', 'delete')){
            access_denied('faf_requests');
        }

        if(!$id){
            redirect(admin_url('purchase/faf_requests'));
        }

        $success = $this->purchase_model->delete_faf_request($id);

        if($success){
            set_alert('success', _l('deleted_successfully'));
        }

        redirect(admin_url('purchase/faf_requests'));
    }


    /**
     * [get_html_approval_setting_for_po description]
     * @param  string $id [description]
     * @return [type]     [description]
     */
     public function get_html_approval_setting_for_faf($id = '')
    {
        $html = '';
        $staffs = $this->staff_model->get();
        $approver = [
                0 => ['id' => 'direct_manager', 'name' => _l('direct_manager')],
                1 => ['id' => 'head_of_department', 'name' => _l('department_manager')],
                2 => ['id' => 'staff', 'name' => _l('staff')]];
        $action = [ 
                    1 => ['id' => 'approve', 'name' => _l('approve')],
                    0 => ['id' => 'sign', 'name' => _l('sign')],
                ];

        $hr_record_status = 0; 
        if(get_status_modules_pur('hr_profile') == true){
            $hr_record_status = 1;
        }
        if(is_numeric($id)){
            $po = $this->purchase_model->get_faf($id);

            $setting = json_decode($po->approval_setting);

            $approver_md = '1';
            $hide_class = 'hide';
            $staff_md = '8';
            $approver_default = 'staff';
            $staff_hide = '';

            
            foreach ($setting as $key => $value) {

                if($value->approver == 'staff'){
                    $staff_hide = '';
                }else{
                    $staff_hide = 'hide';
                }
                if($key == 0){

                    $html .= '<div id="item_approve">
                                    <div class="col-md-11">
                                    <div class="col-md-'.$approver_md.' '.$hide_class.'"> '.
                                    render_select('approver['.$key.']',$approver,array('id','name'),'approver', $value->approver, array('data-id' => '0', 'required' => 'true'), [],'', 'approver_class').'
                                    </div>
                                    <div class="col-md-6" id="is_staff_0">
                                    '. render_select('staff['.$key.']',$staffs,array('staffid','full_name'),'staff', $value->staff).'
                                    </div>
                                    <div class="col-md-6">
                                        '. render_select('action['.$key.']',$action,array('id','name'),'action', $value->action).' 
                                    </div>
                                    </div>
                                    <div class="col-md-1 btn_apr">
                                    <span class="pull-bot">
                                        <button name="add" class="btn new_vendor_requests btn-success" data-ticket="true" type="button"><i class="fa fa-plus"></i></button>
                                        </span>
                                  </div>
                                </div>';
                }else{
                     $html .= '<div id="item_approve">
                                    <div class="col-md-11">
                                    <div class="col-md-'.$approver_md.' '.$hide_class.'"">
                                        '.
                                    render_select('approver['.$key.']',$approver,array('id','name'),'approver', $value->approver, array('data-id' => '0', 'required' => 'true'), [],'', 'approver_class').' 
                                    </div>
                                    <div class="col-md-6" id="is_staff_'.$key.'">
                                        '. render_select('staff['.$key.']',$staffs,array('staffid','full_name'),'staff', $value->staff).' 
                                    </div>
                                    <div class="col-md-6">
                                        '. render_select('action['.$key.']',$action,array('id','name'),'action', $value->action).' 
                                    </div>
                                    </div>
                                    <div class="col-md-1 btn_apr">
                                    <span class="pull-bot">
                                        <button name="add" class="btn remove_vendor_requests btn-danger" data-ticket="true" type="button"><i class="fa fa-minus"></i></button>
                                        </span>
                                  </div>
                                </div>';
                }
            }
        }
        echo json_encode([
                    $html
                ]);
    }

    /**
     * { preview faf_request file }
     *
     * @param      <type>  $id      The identifier
     * @param      <type>  $rel_id  The relative identifier
     * @return  view
     */
    public function file_faf_request($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin']             = is_admin();
        $data['file'] = $this->purchase_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }
        $this->load->view('faf_requests/_file', $data);
    }

    /**
     * { delete attachment }
     *
     * @param      <type>  $id     The identifier
     */
    public function delete_faf_request_attachment($id)
    {
        $this->load->model('misc_model');
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo pur_html_entity_decode($this->purchase_model->delete_faf_request_attachment($id));
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /**
     * [faf_detail description]
     * @return [type] [description]
     */
    public function faf_detail($id){
        if(!has_permission('purchase_faf', '', 'view') && !has_permission('purchase_faf', '', 'view_own')){
            access_denied('faf_requests');
        }

        $faf = $this->purchase_model->get_faf($id);

        if(!$faf){
           die('No FAF Request found'); 
        }

        if(!has_permission('purchase_faf', '', 'view')){
            $staffid = get_staff_user_id();

            $approve_access = total_rows(db_prefix().'pur_approval_details', ['staffid' => $staffid, 'rel_type' => 'faf_request', 'rel_id' => $id]);

            if($faf->created_by != $staffid && $faf->requestor != $staffid && !is_vendor_admin($faf->vendor) && $approve_access == 0){
                access_denied('faf_requests');
            }
        }
        $data['title'] = $faf->reference_number;

        $data['faf'] = $faf;

        $data['faf_attachments'] = $this->purchase_model->get_faf_attachment($id);
        $send_mail_approve = $this->session->userdata("send_mail_approve");
        if((isset($send_mail_approve)) && $send_mail_approve != ''){
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata("send_mail_approve");
        }
        $data['appr_setting'] = $this->purchase_model->get_approve_setting_new('faf_requests');
        $data['check_appr'] = $this->purchase_model->get_approve_setting('faf_requests');
        $data['get_staff_sign'] = $this->purchase_model->get_staff_sign($id,'faf_requests');

        if(isset($data['appr_setting']->approval_type) && $data['appr_setting']->approval_type == 1){

            $data['check_approve_status'] = $this->purchase_model->check_approval_details_new($id,'faf_requests');
        }else{

            $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'faf_requests');
        }

        $data['list_approve_status'] = $this->purchase_model->get_list_approval_details($id,'faf_requests');
        $data['activity_logs'] = $this->purchase_model->get_activity_logs($id, 'pur_faf');

        $this->load->model('staff_model');
        $data['members']                       = $this->staff_model->get('', ['active' => 1]);


        $this->load->view('faf_requests/faf_detail', $data);

    }

    /**
     * [requestor_list_by_department description]
     * @return [type] [description]
     */
    public function requestor_list_by_department($department_id){
        $html = '<option value=""></option>';

        if(is_numeric($department_id)){
            $this->db->where('departmentid', $department_id);
            $staff_departments = $this->db->get(db_prefix().'staff_departments')->result_array();

            foreach($staff_departments as $st_dpm){
                $html .= '<option value="'.$st_dpm['staffid'].'">'.get_staff_full_name($st_dpm['staffid']).'</option>';
            }
        }

        echo json_encode([
            'html' => $html,
        ]);
    }

     /**
     * Gets the purchase faf notes.
     *
     * @param      <type>  $id     The id of purchase order
     */
    public function get_sales_notes_faf($id)
    {
        $data['notes'] = $this->misc_model->get_notes($id, 'pur_faf');
        $this->load->view('admin/includes/sales_notes_template', $data);
    }

    /**
     * Adds a note.
     *
     * @param        $rel_id  The purchase contract id
     */
    public function add_pur_faf_note($rel_id)
    {
        if ($this->input->post() ) {
            $this->load->model('misc_model');
            $this->misc_model->add_note($this->input->post(), 'pur_faf', $rel_id);
            echo pur_html_entity_decode($rel_id);
        }
    }


    /**
     * { change status pur order }
     *
     * @param      <type>  $status  The status
     * @param      <type>  $id      The identifier
     * @return json
     */
    public function change_status_pur_faf($status,$id){
        $change = $this->purchase_model->change_status_pur_faf($status,$id);
        if($change == true){
            
            $message = _l('change_status_pur_faf').' '._l('successfully');
            echo json_encode([
                'result' => $message,
            ]);
        }else{
            $message = _l('change_status_pur_faf').' '._l('fail');
            echo json_encode([
                'result' => $message,
            ]);
        }
    }

    /**
     * [requestor_upload_sign description]
     * @return [type] [description]
     */
    public function requestor_upload_sign($faf_id){
        if($this->input->post()){


            $data = $this->input->post();
            $signature = $data['signature'];

            $faf = $this->purchase_model->get_faf($faf_id);

            $path = PURCHASE_MODULE_UPLOAD_FOLDER .'/faf_requests/requestor_sign/' .$faf_id;

            $success = pur_process_requestor_faf_signature_image($faf_id, $signature, $path, 'signature_'.$faf_id);

            if($success){
                $this->db->where('id', $faf_id);
                $this->db->update(db_prefix().'pur_faf_requests', ['requestor_signed_at' => date('Y-m-d H:i:s')]);
            }
           
            echo json_encode([
                'success' => $success,
             
            ]);
        }

    }

    /**
     * [requestor_sign_attachment description]
     * @return [type] [description]
     */
    public function requestor_sign_attachment($faf_id)
    {
        
           
        $success = false;

       
        $success = handle_upload_requestor_sign_image($faf_id);

        if($success){
            $this->db->where('id', $faf_id);
            $this->db->update(db_prefix().'pur_faf_requests', ['requestor_signed_at' => date('Y-m-d H:i:s')]);
        }
        
        redirect(admin_url('purchase/faf_requests/'.$faf_id));
        
    }

    /**
     * { faf pdf }
     *
     * @param      <type>  $id     The identifier
     * @return pdf output
     */
    public function faf_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('purchase/faf_requests'));
        }

        $faf = $this->purchase_model->get_faf($id);

        $html = $this->purchase_model->get_faf_pdf_html($id);

        try {
            $pdf = $this->purchase_model->faf_pdf($html);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }
        
        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output($faf->reference_number.'.pdf', $type);
    }


    /**
     * Gets the pur order data ajax.
     *
     * @param      <type>   $id         The identifier
     * @param      boolean  $to_return  To return
     *
     * @return     view.
     */
    public function get_faf_request_data_ajax($id, $to_return = false)
    {
        
        if(!has_permission('purchase_faf', '', 'view') && !has_permission('purchase_faf', '', 'view_own')){
             echo _l('access_denied');
            die;
        }

        $faf = $this->purchase_model->get_faf($id);

        if(!$faf){
           die('No FAF Request found'); 
        }

        if(!has_permission('purchase_faf', '', 'view')){
            $staffid = get_staff_user_id();

            $approve_access = total_rows(db_prefix().'pur_approval_details', ['staffid' => $staffid, 'rel_type' => 'faf_requests', 'rel_id' => $id]);

            if($faf->created_by != $staffid && $faf->requestor != $staffid && !is_vendor_admin($faf->vendor_id) && $approve_access == 0){
                echo _l('access_denied');
                die;
            }
        }
        $data['title'] = $faf->reference_number;

        $data['faf'] = $faf;

        $data['faf_attachments'] = $this->purchase_model->get_faf_attachment($id);
        $send_mail_approve = $this->session->userdata("send_mail_approve");
        if((isset($send_mail_approve)) && $send_mail_approve != ''){
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata("send_mail_approve");
        }
        $data['appr_setting'] = $this->purchase_model->get_approve_setting_new('faf_requests');
        $data['check_appr'] = $this->purchase_model->get_approve_setting('faf_requests');
        $data['get_staff_sign'] = $this->purchase_model->get_staff_sign($id,'faf_requests');

        if(isset($data['appr_setting']->approval_type) && $data['appr_setting']->approval_type == 1){

            $data['check_approve_status'] = $this->purchase_model->check_approval_details_new($id,'faf_requests');
        }else{

            $data['check_approve_status'] = $this->purchase_model->check_approval_details($id,'faf_requests');
        }

        $data['list_approve_status'] = $this->purchase_model->get_list_approval_details($id,'faf_requests');
        $data['activity_logs'] = $this->purchase_model->get_activity_logs($id, 'pur_faf');

        $this->load->model('staff_model');
        $data['members']                       = $this->staff_model->get('', ['active' => 1]);
        
        if ($to_return == false) {
            $this->load->view('faf_requests/faf_detail', $data);
        } else {
            return $this->load->view('faf_requests/faf_detail', $data, true);
        } 

    }

    /**
     * [po_show_custom_field_on_pdf description]
     * @return [type] [description]
     */
    public function po_show_custom_field_on_pdf(){
        $data = $this->input->post();
        if($data != 'null'){
            $value = $this->purchase_model->update_pc_options_setting($data);
            if($value){
                $success = true;
                $message = _l('updated_successfully');
            }else{
                $success = false;
                $message = _l('updated_false');
            }
            echo json_encode([
                'message' => $message,
                'success' => $success,
            ]);
            die;
        }
    }

    /**
     * Ensure cheque columns exist in tblpur_invoice_payment.
     * Runs ALTER TABLE only if a column is missing — safe to call on every request.
     * This auto-migration means fix_cheque_payment_details.sql never needs to be
     * run manually.
     */
    private function _ensure_cheque_columns()
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        $table = db_prefix() . 'pur_invoice_payment';

        // Get all existing column names
        $existing = [];
        $cols = $this->db->query("SHOW COLUMNS FROM `{$table}`")->result_array();
        foreach ($cols as $c) {
            $existing[] = $c['Field'];
        }

        // All 4 already exist — nothing to do
        $needed = ['cheque_bank_name', 'cheque_number', 'cheque_date', 'cheque_attachment'];
        if (count(array_intersect($needed, $existing)) === 4) return;

        // Build ADD COLUMN clauses only for missing columns.
        // MySQL processes ADD COLUMN clauses left-to-right in a single ALTER TABLE,
        // so AFTER references to columns added earlier in the same statement are valid.
        $to_add = [];
        $note_exists = in_array('note', $existing);

        if (!in_array('cheque_bank_name', $existing)) {
            $after = $note_exists ? " AFTER `note`" : "";
            $to_add[] = "ADD COLUMN `cheque_bank_name` VARCHAR(200) NULL DEFAULT NULL{$after}";
        }
        if (!in_array('cheque_number', $existing)) {
            // cheque_bank_name either already existed or was just added above — safe to reference
            $to_add[] = "ADD COLUMN `cheque_number` VARCHAR(100) NULL DEFAULT NULL AFTER `cheque_bank_name`";
        }
        if (!in_array('cheque_date', $existing)) {
            $to_add[] = "ADD COLUMN `cheque_date` DATE NULL DEFAULT NULL AFTER `cheque_number`";
        }
        if (!in_array('cheque_attachment', $existing)) {
            $to_add[] = "ADD COLUMN `cheque_attachment` VARCHAR(500) NULL DEFAULT NULL AFTER `cheque_date`";
        }

        if (!empty($to_add)) {
            $this->db->query("ALTER TABLE `{$table}` " . implode(', ', $to_add));
        }
    }

}
