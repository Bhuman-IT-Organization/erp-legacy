<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stock batches for a single asset, shown on the asset detail page.
 *
 * The asset counterpart of modules/warehouse/views/table_inventory_stock.php.
 * One row per batch, because each batch arrived on its own delivery and so
 * carries that delivery's freight rather than an average across all of them.
 *
 * $asset_id is supplied by Fixed_equipment::table_asset_stock() through
 * get_table_data()'s params.
 */

$asset_id = isset($asset_id) ? (int) $asset_id : 0;

$im = db_prefix() . 'inventory_manage';

// Empty result set rather than a fatal error when the schema is not in place.
if (!$this->ci->db->table_exists($im) || !$this->ci->db->field_exists('item_type', $im)) {
    $output = [
        'draw'                 => (int) $this->ci->input->post('draw'),
        'iTotalRecords'        => 0,
        'iTotalDisplayRecords' => 0,
        'aaData'               => [],
    ];

    return;
}

$get_base_currency = get_base_currency();
$base_currency     = $get_base_currency ? $get_base_currency->id : 0;

// tblwarehouse is keyed on warehouse_id and labels with warehouse_name.
$warehouse_names = [];
if ($this->ci->db->table_exists(db_prefix() . 'warehouse')) {
    foreach ($this->ci->db->get(db_prefix() . 'warehouse')->result_array() as $_wh) {
        $warehouse_names[(int) $_wh['warehouse_id']] = $_wh['warehouse_name'];
    }
}

// Aliased for predictable row keys - a qualified column arrives under its own
// dotted name, not the bare one.
$aColumns = [
    $im . '.id as im_id',
    $im . '.warehouse_id as im_warehouse_id',
    'inventory_number',
    $im . '.purchase_price as im_purchase_price',
    // Landed cost, computed per row below. Not sortable or searchable.
    $im . '.commodity_id as im_commodity_id',
    // Stock value placeholder, computed below.
    '1',
    $im . '.lot_number as im_lot_number',
    $im . '.expiry_date as im_expiry_date',
    $im . '.date_manufacture as im_date_manufacture',
];

$sIndexColumn = $im . '.id';
$sTable       = $im;
$join         = [];

$where = [];
$where[] = 'AND ' . $im . '.item_type = "asset"';
$where[] = 'AND ' . $im . '.commodity_id = ' . $asset_id;

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, []);

$output  = $result['output'];
$rResult = $result['rResult'];

$landed_cost_available = function_exists('get_status_modules_pur') && get_status_modules_pur('purchase');
if ($landed_cost_available) {
    $this->ci->load->model('purchase/purchase_model');
}
$landed_cost_cache = [];

foreach ($rResult as $aRow) {
    $row = [];

    $quantity  = (float) $aRow['inventory_number'];
    $unit_cost = (float) $aRow['im_purchase_price'];
    $wh_id     = (int) $aRow['im_warehouse_id'];

    $row[] = $aRow['im_id'];

    $row[] = isset($warehouse_names[$wh_id])
        ? e($warehouse_names[$wh_id])
        : '<span class="text-muted">#' . $wh_id . '</span>';

    $row[] = '<span class="bold">' . app_format_number($quantity) . '</span>';
    $row[] = app_format_money($unit_cost, $base_currency);

    // Landed cost for this batch
    $landed = null;

    if ($landed_cost_available && !empty($aRow['im_commodity_id'])) {
        $stock_row = [
            'warehouse_id'     => $aRow['im_warehouse_id'],
            'date_manufacture' => $aRow['im_date_manufacture'],
            'expiry_date'      => $aRow['im_expiry_date'],
            'lot_number'       => $aRow['im_lot_number'],
        ];

        $cache_key = implode('|', array_merge([$aRow['im_commodity_id']], $stock_row));

        if (!array_key_exists($cache_key, $landed_cost_cache)) {
            $landed_cost_cache[$cache_key] = $this->ci->purchase_model->get_landed_cost_for_stock_row(
                $aRow['im_commodity_id'],
                $stock_row
            );
        }

        $landed = $landed_cost_cache[$cache_key];
    }

    if (!$landed || $landed['landed_unit_cost'] <= 0) {
        $row[] = '<span class="text-muted">-</span>';
    } else {
        $cell = '<span class="bold">' . app_format_money($landed['landed_unit_cost'], $base_currency) . '</span>';

        $source = [];
        if (!empty($landed['invoice_number'])) {
            $source[] = '<a href="' . admin_url('purchase/purchase_invoice/' . $landed['invoice_id']) . '" target="_blank">'
                . e($landed['invoice_number']) . '</a>';
        }
        foreach ($landed['shipping_charges'] as $landed_charge) {
            $source[] = '<a href="' . admin_url('purchase/view_shipping_charge/' . $landed_charge['id']) . '" target="_blank">'
                . e($landed_charge['number']) . '</a>';
        }
        if (count($source) > 0) {
            $cell .= '<br /><span class="text-muted text-sm">' . implode(' &middot; ', $source) . '</span>';
        }

        if ($landed['allocated_shipping'] > 0) {
            $cell .= '<br /><span class="text-muted text-sm">'
                . app_format_money($landed['unit_price'], $base_currency) . ' + '
                . app_format_money(round($landed['allocated_shipping'] / max($landed['quantity'], 1), 4), $base_currency)
                . ' ' . _l('fe_asset_inventory_freight_suffix') . '</span>';
        }

        $row[] = $cell;
    }

    // Value at the received price
    $row[] = app_format_money(round($quantity * $unit_cost, 2), $base_currency);

    $row[] = ($aRow['im_lot_number'] !== null && $aRow['im_lot_number'] !== '' && $aRow['im_lot_number'] !== '0')
        ? e($aRow['im_lot_number'])
        : '<span class="text-muted">-</span>';

    $row[] = (!empty($aRow['im_expiry_date']) && $aRow['im_expiry_date'] != '0000-00-00')
        ? _d($aRow['im_expiry_date'])
        : '<span class="text-muted">-</span>';

    $row[] = (!empty($aRow['im_date_manufacture']) && $aRow['im_date_manufacture'] != '0000-00-00')
        ? _d($aRow['im_date_manufacture'])
        : '<span class="text-muted">-</span>';

    $output['aaData'][] = $row;
}
