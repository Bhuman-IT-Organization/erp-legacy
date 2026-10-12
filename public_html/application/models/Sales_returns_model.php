<?php defined('BASEPATH') or exit('No direct script access allowed');

class Sales_returns_model extends App_Model
{
    public function get_all()
    {
        $this->db->order_by('id', 'desc');
        return $this->db->get(db_prefix().'sales_returns')->result_array();
    }

    public function get($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix().'sales_returns')->row();
    }

    public function get_items($sales_return_id)
    {
        $this->db->where('sales_return_id', $sales_return_id);
        return $this->db->get(db_prefix().'sales_return_items')->result_array();
    }

    public function add($data)
    {
        $items = isset($data['items']) ? $data['items'] : [];
        unset($data['items']);

        // Encode GDV IDs as JSON
        if(isset($data['gdv_ids']) && is_array($data['gdv_ids'])){
            $data['gdv_ids'] = json_encode($data['gdv_ids']);
        }

        // Remove non-DB fields
        unset($data['subtotal'], $data['total_tax'], $data['total']);

        // Calculate totals
        $subtotal = 0;
        foreach($items as &$item){
            $qty = (float)($item['qty'] ?? 0);
            $price = (float)($item['unit_price'] ?? 0);
            $item['amount'] = $qty * $price;
            $subtotal += $item['amount'];
        }
        unset($item);

        $data['subtotal'] = $subtotal;
        $data['total'] = $subtotal;
        $data['total_tax'] = 0;
        $data['created_by'] = get_staff_user_id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = 'draft';

        if(isset($data['return_date'])){
            $data['return_date'] = to_sql_date($data['return_date']);
        }

        $this->db->insert(db_prefix().'sales_returns', $data);
        $insert_id = $this->db->insert_id();

        if($insert_id && !empty($items)){
            foreach($items as $item){
                $item['sales_return_id'] = $insert_id;
                $this->db->insert(db_prefix().'sales_return_items', $item);
            }

            // Create accounting entries (reverse of sales + goods delivery)
            $this->create_accounting_entries($insert_id);
        }

        return $insert_id;
    }

    public function update($data, $id)
    {
        $items = isset($data['items']) ? $data['items'] : [];
        unset($data['items']);

        if(isset($data['gdv_ids']) && is_array($data['gdv_ids'])){
            $data['gdv_ids'] = json_encode($data['gdv_ids']);
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix().'sales_returns', $data);

        // Re-insert items
        $this->db->where('sales_return_id', $id);
        $this->db->delete(db_prefix().'sales_return_items');
        foreach($items as $item){
            $item['sales_return_id'] = $id;
            $this->db->insert(db_prefix().'sales_return_items', $item);
        }

        // Re-create accounting
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'sales_return');
        $this->db->delete(db_prefix().'acc_account_history');
        $this->create_accounting_entries($id);

        return true;
    }

    public function delete($id)
    {
        // Soft delete — mark as deleted, keep in DB
        $this->db->where('id', $id);
        $this->db->update(db_prefix().'sales_returns', [
            'is_deleted' => 1,
            'status' => 'canceled',
        ]);

        // Remove accounting entries (reverse the reversal)
        $CI =& get_instance();
        $CI->db->where('rel_id', $id);
        $CI->db->where('rel_type', 'sales_return');
        $CI->db->delete(db_prefix().'acc_account_history');

        return true;
    }

    /**
     * Create reverse accounting entries for sales return
     */
    private function create_accounting_entries($sales_return_id)
    {
        $return = $this->get($sales_return_id);
        $items = $this->get_items($sales_return_id);
        if(!$return || empty($items)) return;

        $ar_account = (int)get_option('acc_invoice_deposit_to') ?: 117;
        $revenue_account = (int)get_option('acc_invoice_payment_account') ?: 199;
        $cogs_account = (int)get_option('acc_wh_stock_export_deposit_to') ?: 222;
        $inventory_account = (int)get_option('acc_wh_stock_export_payment_account') ?: 114;
        $vat_account = 123;

        $CI =& get_instance();
        $CI->db->where('tax_id', 1);
        $tax_map = $CI->db->get(db_prefix().'acc_tax_mappings')->row();
        if($tax_map && !empty($tax_map->payment_account)) $vat_account = (int)$tax_map->payment_account;

        $date = $return->return_date ?: date('Y-m-d');
        $staff = get_staff_user_id() ?: 1;

        foreach($items as $item){
            $qty = (float)($item['qty'] ?? 0);
            $price = (float)($item['unit_price'] ?? 0);
            $amount = $qty * $price;
            if($amount <= 0) continue;

            $item_code = (int)($item['item_code'] ?? 0);

            // 1. Dr Inventory FG, Cr COGS
            $CI->db->insert(db_prefix().'acc_account_history', [
                'account' => $inventory_account, 'debit' => $amount, 'credit' => 0,
                'rel_id' => $sales_return_id, 'rel_type' => 'sales_return',
                'date' => $date, 'datecreated' => date('Y-m-d H:i:s'), 'addedfrom' => $staff,
                'item' => $item_code, 'description' => 'Sales return - stock back', 'split' => $cogs_account,
            ]);
            $CI->db->insert(db_prefix().'acc_account_history', [
                'account' => $cogs_account, 'debit' => 0, 'credit' => $amount,
                'rel_id' => $sales_return_id, 'rel_type' => 'sales_return',
                'date' => $date, 'datecreated' => date('Y-m-d H:i:s'), 'addedfrom' => $staff,
                'item' => $item_code, 'description' => 'Sales return - COGS reversed', 'split' => $inventory_account,
            ]);

            // 2. Dr Revenue, Cr AR
            $CI->db->insert(db_prefix().'acc_account_history', [
                'account' => $revenue_account, 'debit' => $amount, 'credit' => 0,
                'rel_id' => $sales_return_id, 'rel_type' => 'sales_return',
                'date' => $date, 'datecreated' => date('Y-m-d H:i:s'), 'addedfrom' => $staff,
                'description' => 'Sales return - revenue reversed', 'split' => $ar_account,
            ]);
            $CI->db->insert(db_prefix().'acc_account_history', [
                'account' => $ar_account, 'debit' => 0, 'credit' => $amount,
                'rel_id' => $sales_return_id, 'rel_type' => 'sales_return',
                'date' => $date, 'datecreated' => date('Y-m-d H:i:s'), 'addedfrom' => $staff,
                'description' => 'Sales return - AR reduced', 'split' => $revenue_account,
            ]);

            // 3. VAT reversal if tax_rate > 0
            $tax_rate = (float)($item['tax_rate'] ?? 0);
            if($tax_rate > 0){
                $vat_amount = round($amount * $tax_rate / 100, 2);
                $CI->db->insert(db_prefix().'acc_account_history', [
                    'account' => $vat_account, 'debit' => $vat_amount, 'credit' => 0,
                    'rel_id' => $sales_return_id, 'rel_type' => 'sales_return',
                    'date' => $date, 'datecreated' => date('Y-m-d H:i:s'), 'addedfrom' => $staff,
                    'tax' => 1, 'description' => 'Sales return - VAT reversed', 'split' => $ar_account,
                ]);
                $CI->db->insert(db_prefix().'acc_account_history', [
                    'account' => $ar_account, 'debit' => 0, 'credit' => $vat_amount,
                    'rel_id' => $sales_return_id, 'rel_type' => 'sales_return',
                    'date' => $date, 'datecreated' => date('Y-m-d H:i:s'), 'addedfrom' => $staff,
                    'tax' => 1, 'description' => 'Sales return - VAT AR reduced', 'split' => $vat_account,
                ]);
            }
        }
    }
}
