<?php

defined('BASEPATH') or exit('No direct script access allowed');
$warehouseByStaff = $this->ci->warehouse_model->getWarehouseByStaff();
$get_base_currency =  get_base_currency();
if($get_base_currency){
    $base_currency = $get_base_currency->id;
}else{
    $base_currency = 0;
}

$aColumns = [
    db_prefix().'inventory_manage.id',
    'description',
    db_prefix().'inventory_manage.expiry_date',
    db_prefix().'inventory_manage.lot_number',
    db_prefix().'inventory_manage.warehouse_id',
    // Ordered / Received / Pending quantity, read from the goods receipts that
    // brought this batch in. Same three figures as
    // /admin/warehouse/manage_purchase, so physical godown stock can be checked
    // against the application line by line.
    //
    // Like the landed cost column below, these reuse real column names so
    // data_tables_init can build valid SQL; the cell content is replaced in the
    // render loops and the columns are marked not sortable / not searchable in
    // view_commodity_detail_js.php.
    db_prefix().'inventory_manage.date_manufacture',
    db_prefix().'items.sku_code',
    db_prefix().'items.sku_name',
    'unit_id',
    'rate',
    db_prefix().'inventory_manage.purchase_price',
    // Landed cost column. commodity_id is a real column so data_tables_init can
    // build valid SQL; the cell content is replaced below with the computed landed
    // unit cost. The column is marked not sortable / not searchable in
    // view_commodity_detail_js.php because it is calculated, not stored.
    db_prefix().'inventory_manage.commodity_id',
    'tax',
    'origin',
    ];

// Each stock row is one delivery, so it carries the freight from the invoice that
// actually brought that batch in rather than an average across all deliveries.
// Traced through the goods receipt - see
// Purchase_model::get_landed_cost_for_stock_row().
$landed_cost_available = function_exists('get_status_modules_wh') && get_status_modules_wh('purchase');
if($landed_cost_available){
    $this->ci->load->model('purchase/purchase_model');
}

// Keyed on the batch so two rows of the same delivery only cost one lookup
$landed_cost_cache = [];

$landed_cost_cell = function($aRow) use (&$landed_cost_cache, $landed_cost_available, $base_currency) {
    if(!$landed_cost_available || empty($aRow['commodity_id'])){
        return '<span class="text-muted">-</span>';
    }

    $stock_row = [
        'warehouse_id'     => isset($aRow['warehouse_ids']) ? $aRow['warehouse_ids'] : '',
        'date_manufacture' => isset($aRow['date_manufacture']) ? $aRow['date_manufacture'] : '',
        'expiry_date'      => isset($aRow['expiry_date']) ? $aRow['expiry_date'] : '',
        'lot_number'       => isset($aRow['lot_number']) ? $aRow['lot_number'] : '',
    ];

    $cache_key = implode('|', array_merge([$aRow['commodity_id']], $stock_row));

    if(!array_key_exists($cache_key, $landed_cost_cache)){
        $landed_cost_cache[$cache_key] = $this->ci->purchase_model->get_landed_cost_for_stock_row(
            $aRow['commodity_id'],
            $stock_row
        );
    }

    $landed = $landed_cost_cache[$cache_key];

    if(!$landed || $landed['landed_unit_cost'] <= 0){
        return '<span class="text-muted">-</span>';
    }

    $cell = '<span class="bold">'.app_format_money($landed['landed_unit_cost'], $base_currency).'</span>';

    // Where the figure came from, so the number is never unexplained. Kept as
    // subtext rather than extra columns.
    $source = [];
    if(!empty($landed['invoice_number'])){
        $source[] = '<a href="'.admin_url('purchase/purchase_invoice/'.$landed['invoice_id']).'" target="_blank">'.e($landed['invoice_number']).'</a>';
    }
    foreach($landed['shipping_charges'] as $landed_charge){
        $source[] = '<a href="'.admin_url('purchase/view_shipping_charge/'.$landed_charge['id']).'" target="_blank">'.e($landed_charge['number']).'</a>';
    }

    if(count($source) > 0){
        $cell .= '<br /><span class="text-muted text-sm">'.implode(' &middot; ', $source).'</span>';
    }

    if($landed['allocated_shipping'] > 0){
        $cell .= '<br /><span class="text-muted text-sm">'
            .app_format_money($landed['unit_price'], $base_currency).' + '
            .app_format_money(round($landed['allocated_shipping'] / max($landed['quantity'], 1), 4), $base_currency)
            .' '._l('wh_landed_cost_freight_suffix').'</span>';
    }

    return $cell;
};

// Ordered / received / pending quantity per stock line.
//
// tblgoods_receipt_detail.commodity_code holds the item ID, not the item code
// string - verified against the data, every stock row matches on it. Pairing it
// with the warehouse pins the receipts to the batch shown on this row.
//
// field_exists() rather than information_schema: the live database user cannot
// read information_schema, and with db_debug off a failed query returns false and
// is then fatal further down.
$grn_qty_available = $this->ci->db->field_exists('received_qty', db_prefix().'goods_receipt_detail')
    && $this->ci->db->field_exists('pending_qty', db_prefix().'goods_receipt_detail');

// One lookup per item/warehouse pair, however many batch rows share it
$grn_qty_cache = [];

$grn_qty = function($aRow) use (&$grn_qty_cache, $grn_qty_available) {
    $empty = ['key' => '', 'ordered' => 0, 'received' => 0, 'pending' => 0, 'found' => false];

    $commodity_id = isset($aRow['commodity_id']) ? (int)$aRow['commodity_id'] : 0;
    $warehouse_id = isset($aRow['warehouse_ids']) ? (int)$aRow['warehouse_ids'] : 0;

    if(!$grn_qty_available || $commodity_id <= 0 || $warehouse_id <= 0){
        return $empty;
    }

    $cache_key = $commodity_id.'|'.$warehouse_id;

    if(!array_key_exists($cache_key, $grn_qty_cache)){
        $totals = $empty;
        $totals['key'] = $cache_key;

        // COALESCE and not NULLIF: a receipt genuinely recorded as 0 received must
        // stay 0 rather than falling back to the ordered quantity. Rows written
        // before the column existed are NULL and do fall back, matching
        // Warehouse_model.
        $grn_qty_query = $this->ci->db->query(
            'SELECT SUM('.db_prefix().'goods_receipt_detail.quantities + 0) AS ordered_total,
                    SUM(COALESCE('.db_prefix().'goods_receipt_detail.received_qty, '.db_prefix().'goods_receipt_detail.quantities) + 0) AS received_total,
                    SUM(COALESCE('.db_prefix().'goods_receipt_detail.pending_qty, 0) + 0) AS pending_total,
                    COUNT(*) AS receipt_rows
             FROM '.db_prefix().'goods_receipt_detail
             WHERE '.db_prefix().'goods_receipt_detail.commodity_code = '.$this->ci->db->escape((string)$commodity_id).'
               AND '.db_prefix().'goods_receipt_detail.warehouse_id = '.$this->ci->db->escape((string)$warehouse_id)
        );

        if($grn_qty_query !== false){
            $grn_qty_row = $grn_qty_query->row();
            if($grn_qty_row && (int)$grn_qty_row->receipt_rows > 0){
                $totals['ordered']  = (float)$grn_qty_row->ordered_total;
                $totals['received'] = (float)$grn_qty_row->received_total;
                $totals['pending']  = (float)$grn_qty_row->pending_total;
                $totals['found']    = true;
            }
        }

        $grn_qty_cache[$cache_key] = $totals;
    }

    return $grn_qty_cache[$cache_key];
};

// Footer total. Only the received quantity is totalled, as asked - an ordered or
// pending total across unrelated deliveries is not a figure anyone reconciles
// against. Counted once per item/warehouse pair so several batch rows sharing the
// same receipts cannot inflate it.
$received_qty_total       = 0;
$received_qty_total_seen  = [];

$grn_qty_cell = function($totals, $which) use (&$received_qty_total, &$received_qty_total_seen) {
    if($which == 'received' && $totals['found'] && !isset($received_qty_total_seen[$totals['key']])){
        $received_qty_total_seen[$totals['key']] = true;
        $received_qty_total += $totals['received'];
    }

    if(!$totals['found']){
        return '<span class="text-muted">-</span>';
    }

    if($which == 'pending' && $totals['pending'] > 0){
        return '<span class="text-danger bold">'.(float)$totals['pending'].'</span>';
    }

    return (float)$totals[$which];
};

$sIndexColumn = db_prefix().'inventory_manage.id';
$sTable       = db_prefix().'inventory_manage';



$where = [];


$warehouse_ft = $this->ci->input->post('warehouse_ft');
$commodity_ft = $this->ci->input->post('commodity_ft');
$alert_filter = $this->ci->input->post('alert_filter');


if(!isset($warehouse_ft) && !isset($commodity_ft) && !isset($alert_filter) && ($alert_filter == '')){
    $join =['LEFT JOIN '.db_prefix().'items ON '.db_prefix().'items.id = '.db_prefix().'inventory_manage.commodity_id',];
}else{

     $join = [

    'LEFT JOIN '.db_prefix().'items ON '.db_prefix().'items.id = '.db_prefix().'inventory_manage.commodity_id',
   
    ];

    if(is_array($warehouseByStaff)){
        $where[] = 'AND ( '.db_prefix().'inventory_manage.warehouse_id IN ('.implode(',', $warehouseByStaff).') )';
    }
}


if(isset($warehouse_ft)){
    $where_warehouse_ft = '';
    foreach ($warehouse_ft as $warehouse_id) {
        if($warehouse_id != '')
        {
            if($where_warehouse_ft == ''){
                $where_warehouse_ft .= ' AND ('.db_prefix().'inventory_manage.warehouse_id = "'.$warehouse_id.'"';
            }else{
                $where_warehouse_ft .= ' or '.db_prefix().'inventory_manage.warehouse_id = "'.$warehouse_id.'"';
            }
        }
    }
    if($where_warehouse_ft != '')
    {
        $where_warehouse_ft .= ')';
        array_push($where, $where_warehouse_ft);
    }

}

if(isset($commodity_ft)){
    $where_commodity_ft = ' AND tblitems.id = "'.$commodity_ft.'"';
    array_push($where, $where_commodity_ft);
    
}


$where_alert_filter = ' AND '.db_prefix().'inventory_manage.inventory_number > "0"';
array_push($where, $where_alert_filter);

// Item stock only. Asset stock lives in the same table and its commodity_id points
// at tblfe_asset_creation, not tblitems - and those ids collide, so without this an
// asset batch joins to whichever item happens to share its id and appears here as a
// phantom second row. Assets belong on
// /admin/fixed_equipment/asset_inventory instead.
//
// NULL is included because rows recorded before the item_type column existed have
// not been classified, and dropping them would silently hide real item stock.
//
// field_exists() rather than information_schema: the live database user cannot read
// information_schema, and with db_debug off a failed query returns false and is then
// fatal further down.
if($this->ci->db->field_exists('item_type', db_prefix().'inventory_manage')){
    array_push($where, ' AND ('.db_prefix().'inventory_manage.item_type = "item" OR '
        .db_prefix().'inventory_manage.item_type IS NULL)');
}



if(!isset($warehouse_ft) && !isset($commodity_ft) && ($alert_filter == '')){
    $result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix().'items.id',db_prefix().'inventory_manage.commodity_id',db_prefix().'inventory_manage.warehouse_id as warehouse_ids',db_prefix().'inventory_manage.inventory_number',db_prefix().'inventory_manage.date_manufacture',db_prefix().'inventory_manage.expiry_date', db_prefix().'items.description', db_prefix().'items.unit_id', db_prefix().'items.commodity_code',  db_prefix().'items.commodity_barcode', db_prefix().'items.commodity_type',  db_prefix().'items.warehouse_id',  db_prefix().'items.origin',   db_prefix().'items.color_id',   db_prefix().'items.style_id',   db_prefix().'items.model_id', db_prefix().'items.size_id',     db_prefix().'items.rate',  db_prefix().'items.tax',  db_prefix().'items.group_id' ,  db_prefix().'items.long_description' ,  db_prefix().'items.sku_code',  db_prefix().'items.sku_name',  db_prefix().'items.sub_group',db_prefix().'inventory_manage.lot_number']);
}else{
    $result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix().'items.id',db_prefix().'inventory_manage.commodity_id',db_prefix().'inventory_manage.warehouse_id as warehouse_ids',db_prefix().'inventory_manage.inventory_number',db_prefix().'inventory_manage.date_manufacture',db_prefix().'inventory_manage.expiry_date',db_prefix().'items.description',db_prefix().'items.group_id',db_prefix().'items.unit_id',db_prefix().'items.rate',db_prefix().'items.tax', db_prefix().'items.description', db_prefix().'items.unit_id', db_prefix().'items.commodity_code',  db_prefix().'items.commodity_barcode', db_prefix().'items.commodity_type',  db_prefix().'items.warehouse_id',  db_prefix().'items.origin',   db_prefix().'items.color_id',   db_prefix().'items.style_id',   db_prefix().'items.model_id', db_prefix().'items.size_id',    db_prefix().'items.rate',  db_prefix().'items.tax',  db_prefix().'items.group_id' ,  db_prefix().'items.long_description',  db_prefix().'items.sku_code',  db_prefix().'items.sku_name',  db_prefix().'items.sub_group' ,db_prefix().'inventory_manage.lot_number', db_prefix().'inventory_manage.id']);

}

$output  = $result['output'];
$rResult = $result['rResult'];

if(!isset($warehouse_ft) && !isset($commodity_ft)  && ($alert_filter == '')){
    foreach ($rResult as $aRow) {
         $row = [];
        for ($i = 0; $i < count($aColumns); $i++) {
            if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
                $_data = $aRow[strafter($aColumns[$i], 'as ')];
            } else {
                $_data = isset($aRow[$aColumns[$i]]) ? $aRow[$aColumns[$i]] : '';
            }

             if ($aColumns[$i] == 'description') {
                $_data = '<a href="' . admin_url('warehouse/view_commodity_detail/' . $aRow['commodity_id']) . '">' . $aRow['commodity_code'] .'_'.$aRow['description']. '</a>';

            }elseif ($aColumns[$i] == db_prefix().'inventory_manage.expiry_date') {
                $_data = _d($aRow['expiry_date']);

            }elseif ($aColumns[$i] == db_prefix().'inventory_manage.lot_number') {
                $_data = $aRow['lot_number'];

            }elseif($aColumns[$i] == db_prefix().'inventory_manage.warehouse_id'){
                $team = get_warehouse_name($aRow['warehouse_ids']);
                $_data = $team ? $team->warehouse_name : '';
              
            }elseif ($aColumns[$i] == db_prefix().'inventory_manage.date_manufacture') {
                $_data = $grn_qty_cell($grn_qty($aRow), 'ordered');
            }elseif ($aColumns[$i] == db_prefix().'items.sku_code') {
                $_data = $grn_qty_cell($grn_qty($aRow), 'received');
            }elseif ($aColumns[$i] == db_prefix().'items.sku_name') {
                $_data = $grn_qty_cell($grn_qty($aRow), 'pending');
            }elseif ($aColumns[$i] == 'unit_id') {
                if($aRow['unit_id'] != null){
                    $_data = get_unit_type($aRow['unit_id']) != null ? get_unit_type($aRow['unit_id'])->unit_name : '';
                }else{
                    $_data = '';
                }
            }elseif ($aColumns[$i] == 'rate') {
                $_data = app_format_money((float)$aRow['rate'], $base_currency);
            }elseif ($aColumns[$i] == db_prefix().'inventory_manage.purchase_price') {
                $_data = app_format_money((float)$aRow['purchase_price'], $base_currency);
            }elseif ($aColumns[$i] == db_prefix().'inventory_manage.commodity_id') {
                $_data = $landed_cost_cell($aRow);
            }elseif ($aColumns[$i] == 'tax') {
                $_data = '';
                if($aRow['tax'] != 0 && $aRow['tax'] != ''){
                    $tax_rate = get_tax_by_id($aRow['tax']);
                    if($tax_rate && $tax_rate != null){
                        $_data = $tax_rate->name;
                    }
                }
            }elseif ($aColumns[$i] == 'origin') {
                $_data = (float)$aRow['inventory_number'] > 0 ? '<span class="label label-success">In Stock</span>' : '<span class="label label-danger">Out of Stock</span>';
            }
         
        $row[] = $_data;
            
        }
        $output['aaData'][] = $row;
    }
}else{

        foreach ($rResult as $aRow) {
         $row = [];

        for ($i = 0; $i < count($aColumns); $i++) {

            // Guarded because not every $aColumns key is selected under its fully
            // qualified name - the computed landed cost column is one such case
            $_data = isset($aRow[$aColumns[$i]]) ? $aRow[$aColumns[$i]] : '';


             if($aColumns[$i] == 'commodity_code') {
                 $_data = $aRow['commodity_code'];

            }elseif($aColumns[$i] ==  db_prefix().'inventory_manage.id'){
                 $_data = $aRow['id'];

            }elseif ($aColumns[$i] == 'description') {
                
                    $_data = '<a href="#"  data-name="'.$aRow['description'].'"  data-warehouse_id="'.$aRow['warehouse_id'].'" data-commodity_id="'.$aRow['commodity_id'].'" data-expiry_date="'.$aRow['expiry_date'].'" >' .$aRow['commodity_code'].'_'.$aRow['description']. '</a>';

            }elseif ($aColumns[$i] == 'group_id') {

                if($aRow['expiry_date'] > date('Y-m-d')){
                    $_data = _d($aRow['expiry_date']);
                }else{
                     $_data = '<a href="#" class="text-danger" >'._d($aRow['expiry_date']). '</a>';
                }

            }elseif ($aColumns[$i] == 'color_id') {
                $_data = $aRow['lot_number'];

            }elseif($aColumns[$i] == db_prefix().'inventory_manage.warehouse_id'){

                if($aRow['warehouse_ids'] != ''){
                    $team = get_warehouse_name($aRow['warehouse_ids']);

                    $str = '';
                    $value = $team != null ? get_object_vars($team)['warehouse_name'] : '';
                   
                        $str .= '<span class="label label-tag tag-id-1"><span class="tag">'.$value.'</span><span class="hide">, </span></span>&nbsp';
                        
                    $_data = $str;
                }
                else{
                    $_data = '';
                }  
              
            }elseif ($aColumns[$i] == db_prefix().'inventory_manage.date_manufacture') {
                $_data = $grn_qty_cell($grn_qty($aRow), 'ordered');
            }elseif ($aColumns[$i] == db_prefix().'items.sku_code') {
                $_data = $grn_qty_cell($grn_qty($aRow), 'received');
            }elseif ($aColumns[$i] == db_prefix().'items.sku_name') {
                $_data = $grn_qty_cell($grn_qty($aRow), 'pending');
            }elseif ($aColumns[$i] == 'unit_id') {
                if($aRow['unit_id'] != null){
                    $_data = get_unit_type($aRow['unit_id']) != null ? get_unit_type($aRow['unit_id'])->unit_name : '';
                }else{
                    $data = '';
                }
            }elseif ($aColumns[$i] == 'rate') {
                $_data = app_format_money((float)$aRow['rate'], $base_currency);
            }elseif($aColumns[$i] == db_prefix().'inventory_manage.purchase_price'){
                $_data = app_format_money((float)$aRow['purchase_price'], $base_currency);

            }elseif ($aColumns[$i] == db_prefix().'inventory_manage.commodity_id') {
                $_data = $landed_cost_cell($aRow);

            }elseif ($aColumns[$i] == 'tax') {
                $_data ='';
                $tax_rate = get_tax_rate($aRow['tax']);

                if($aRow['tax']){
                    if($tax_rate && $tax_rate != null && $tax_rate != 'null'){
                        $_data = $tax_rate->name;
                    }
                }

            }elseif ($aColumns[$i] == 'style_id') {
                if(get_status_inventory($aRow['id'], $aRow['inventory_number'])){
                    $_data = $aRow['inventory_number'];
                }else{

                    $_data = '<a href="#" class="text-danger" >'.$aRow['inventory_number']. '</a>';
                }
            }elseif ($aColumns[$i] == 'origin') {
                if(get_status_inventory($aRow['id'], $aRow['inventory_number'])){
                    $_data ='';
                }else{
                    $_data = '<span class="label label-tag tag-id-1 label-tabus"><span class="tag">'._l('unsafe_inventory').'</span><span class="hide">, </span></span>&nbsp';
                }
            }
             
         
        $row[] = $_data;
            
        }
        $output['aaData'][] = $row;
    }       

}

// Carried alongside the rows rather than as an extra data row, so it cannot be
// sorted or paged out of place. view_commodity_detail_js.php writes it into the
// table footer on each draw.
$output['sReceivedQtyTotal']      = (float)$received_qty_total;
$output['sReceivedQtyTotalLabel'] = _l('wh_total_received_qty');
