<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Asset inventory grid.
 *
 * Mirrors the warehouse inventory grid (modules/warehouse/views/inventory/table_inventory.php)
 * but for stock received against asset purchase orders.
 *
 * Both kinds of stock live in tblinventory_manage. The warehouse grid joins
 * tblitems and filters on tblitems.active = 1; this one joins
 * tblfe_asset_creation instead and filters on item_type = 'asset'. That column
 * exists because commodity_id is not namespaced - every commodity_id in this
 * database resolves in both tables, so joining on the id alone would show a pH
 * Meter with 19,200 units in stock. See sql/2026-09-02/inventory_manage_item_type.sql.
 *
 * Two cost figures are shown, as on the item side:
 *   Purchase Price  what the batch was received at, straight from the column
 *   Landed Cost     purchase price plus that delivery's share of freight,
 *                   computed by Purchase_model::get_landed_cost_for_stock_row()
 *
 * Every column carries an explicit "as" alias. data_tables_init() selects a
 * qualified column as its own dotted name, so tblfe_asset_creation.code arrives
 * under the key "tblfe_asset_creation.code" and $aRow['code'] is empty. Aliasing
 * keeps the row keys predictable, and the ORDER BY still uses the real column
 * because the helper strips everything from " as " onwards before sorting.
 */

$ac = db_prefix() . 'fe_asset_creation';
$im = db_prefix() . 'inventory_manage';

// Bail out with an empty result set when the schema is not in place. db_debug is
// off in production, so a query against a missing column returns false and the row
// loop below would raise a fatal error, which the browser only sees as a 500.
if (!$this->ci->db->table_exists($ac)
    || !$this->ci->db->table_exists($im)
    || !$this->ci->db->field_exists('item_type', $im)) {
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

// Warehouse names resolved here rather than through the warehouse module's
// get_warehouse_name() helper, which is not loaded in this module's context.
// tblwarehouse is keyed on warehouse_id and names its label column
// warehouse_name, unlike the fixed_equipment module's own tblfe_warehouse which
// uses id/name.
$warehouse_names = [];
if ($this->ci->db->table_exists(db_prefix() . 'warehouse')) {
    foreach ($this->ci->db->get(db_prefix() . 'warehouse')->result_array() as $_wh) {
        $warehouse_names[(int) $_wh['warehouse_id']] = $_wh['warehouse_name'];
    }
}

// asset_class and capitalization_date were added to tblfe_asset_creation by hand
// rather than through a migration, so an environment can be missing them. A
// literal keeps the column count matching the headers - a short row is what
// produces "Requested unknown parameter" in the browser.
$ac_fields   = $this->ci->db->list_fields($ac);
$col_class   = in_array('asset_class', $ac_fields)
    ? $ac . '.asset_class as ac_asset_class'
    : "'' as ac_asset_class";
$col_capdate = in_array('capitalization_date', $ac_fields)
    ? $ac . '.capitalization_date as ac_capitalization_date'
    : "'' as ac_capitalization_date";

$aColumns = [
    $ac . '.code as ac_code',
    $ac . '.asset_uid as ac_asset_uid',
    $ac . '.asset_title as ac_asset_title',
    $col_class,
    $ac . '.department as ac_department',
    $ac . '.cost_centre as ac_cost_centre',
    $ac . '.manufacturer_name as ac_manufacturer_name',
    $im . '.warehouse_id as im_warehouse_id',
    'inventory_number',
    $im . '.purchase_price as im_purchase_price',
    // Landed cost. commodity_id is a real column so the helper can build valid
    // SQL; the cell is replaced below with the computed figure. Marked not
    // sortable / not searchable in the view because it is calculated, not stored -
    // the same approach as the item stock grid.
    $im . '.commodity_id as im_commodity_id',
    // Stock value = quantity x purchase price. '1' is a literal placeholder for
    // the same reason.
    '1',
    $col_capdate,
    $im . '.lot_number as im_lot_number',
];

$sIndexColumn = $im . '.id';
$sTable       = $im;

$join = [
    'INNER JOIN ' . $ac . ' ON ' . $ac . '.id = ' . $im . '.commodity_id',
];

$where = [];

// Only asset stock. Rows left NULL by the backfill are excluded deliberately -
// showing a row that might be ordinary inventory would misstate asset values.
$where[] = 'AND ' . $im . '.item_type = "asset"';

// Filters, matching the warehouse screen's behaviour
$warehouse_ft = $this->ci->input->post('warehouse_ft');
if (!empty($warehouse_ft) && is_array($warehouse_ft)) {
    $warehouse_ids = array_filter(array_map('intval', $warehouse_ft));
    if (count($warehouse_ids) > 0) {
        $where[] = 'AND ' . $im . '.warehouse_id IN (' . implode(',', $warehouse_ids) . ')';
    }
}

$asset_ft = $this->ci->input->post('asset_ft');
if (!empty($asset_ft) && is_array($asset_ft)) {
    $asset_ids = array_filter(array_map('intval', $asset_ft));
    if (count($asset_ids) > 0) {
        $where[] = 'AND ' . $im . '.commodity_id IN (' . implode(',', $asset_ids) . ')';
    }
}

$department_ft = $this->ci->input->post('department_ft');
if (!empty($department_ft) && is_array($department_ft)) {
    $escaped = [];
    foreach ($department_ft as $dept) {
        if ($dept !== '') {
            $escaped[] = $this->ci->db->escape($dept);
        }
    }
    if (count($escaped) > 0) {
        $where[] = 'AND ' . $ac . '.department IN (' . implode(',', $escaped) . ')';
    }
}

// Hide zero balances unless asked for, so the screen shows what is actually held
if ($this->ci->input->post('show_zero_qty') != 1) {
    $where[] = 'AND CAST(' . $im . '.inventory_number AS DECIMAL(20,4)) <> 0';
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    $im . '.id as im_id',
    $im . '.date_manufacture as im_date_manufacture',
    $im . '.expiry_date as im_expiry_date',
    $ac . '.id as asset_id',
    $ac . '.descriptions as ac_descriptions',
    $ac . '.vendor_name as ac_vendor_name',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

// One landed cost lookup per batch, cached so repeated batches of the same
// delivery cost a single query. Same engine the item stock grid uses.
$landed_cost_available = function_exists('get_status_modules_pur') && get_status_modules_pur('purchase');
if ($landed_cost_available) {
    $this->ci->load->model('purchase/purchase_model');
}
$landed_cost_cache = [];

$landed_cost_for = function ($aRow) use (&$landed_cost_cache, $landed_cost_available) {
    if (!$landed_cost_available || empty($aRow['im_commodity_id'])) {
        return null;
    }

    $stock_row = [
        'warehouse_id'     => isset($aRow['im_warehouse_id']) ? $aRow['im_warehouse_id'] : '',
        'date_manufacture' => isset($aRow['im_date_manufacture']) ? $aRow['im_date_manufacture'] : '',
        'expiry_date'      => isset($aRow['im_expiry_date']) ? $aRow['im_expiry_date'] : '',
        'lot_number'       => isset($aRow['im_lot_number']) ? $aRow['im_lot_number'] : '',
    ];

    $cache_key = implode('|', array_merge([$aRow['im_commodity_id']], $stock_row));

    if (!array_key_exists($cache_key, $landed_cost_cache)) {
        $landed_cost_cache[$cache_key] = $this->ci->purchase_model->get_landed_cost_for_stock_row(
            $aRow['im_commodity_id'],
            $stock_row
        );
    }

    return $landed_cost_cache[$cache_key];
};

foreach ($rResult as $aRow) {
    $row = [];

    $quantity  = (float) $aRow['inventory_number'];
    $unit_cost = (float) $aRow['im_purchase_price'];

    // Asset code, linked to the asset detail page - the asset counterpart of
    // warehouse/view_commodity_detail. This is a stock view, so it offers viewing
    // only; editing the asset definition stays in Asset Creation.
    $row[] = '<a href="' . admin_url('fixed_equipment/asset_detail/' . $aRow['asset_id']) . '">'
        . e($aRow['ac_code']) . '</a>'
        . '<div class="row-options">'
        . '<a href="' . admin_url('fixed_equipment/asset_detail/' . $aRow['asset_id']) . '">' . _l('view') . '</a>'
        . '</div>';

    $row[] = e($aRow['ac_asset_uid']);

    $title = '<span class="bold">' . e($aRow['ac_asset_title']) . '</span>';
    if (!empty($aRow['ac_descriptions'])) {
        $title .= '<br /><span class="text-muted text-sm">' . e($aRow['ac_descriptions']) . '</span>';
    }
    $row[] = $title;

    $row[] = e($aRow['ac_asset_class']);
    $row[] = e($aRow['ac_department']);
    $row[] = e($aRow['ac_cost_centre']);

    $manufacturer = e($aRow['ac_manufacturer_name']);
    if (!empty($aRow['ac_vendor_name'])) {
        $manufacturer .= '<br /><span class="text-muted text-sm">' . e($aRow['ac_vendor_name']) . '</span>';
    }
    $row[] = $manufacturer;

    $wh_id  = (int) $aRow['im_warehouse_id'];
    $row[]  = isset($warehouse_names[$wh_id])
        ? e($warehouse_names[$wh_id])
        : '<span class="text-muted">#' . $wh_id . '</span>';

    $row[] = '<span class="bold">' . app_format_number($quantity) . '</span>';
    $row[] = app_format_money($unit_cost, $base_currency);

    // Landed cost
    $landed = $landed_cost_for($aRow);

    if (!$landed || $landed['landed_unit_cost'] <= 0) {
        // Nothing invoiced against this batch yet, so there is no freight to spread
        $row[] = '<span class="text-muted">-</span>';
    } else {
        $cell = '<span class="bold">' . app_format_money($landed['landed_unit_cost'], $base_currency) . '</span>';

        // Where the number came from, so it is never unexplained
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

    // Stock value at the received price. Landed value is not used here on purpose:
    // landed cost is per delivery, and mixing it into a total alongside the
    // purchase price column would give two valuations of the same row with
    // nothing saying which is which.
    $row[] = app_format_money(round($quantity * $unit_cost, 2), $base_currency);

    $row[] = (!empty($aRow['ac_capitalization_date']) && $aRow['ac_capitalization_date'] != '0000-00-00')
        ? _d($aRow['ac_capitalization_date'])
        : '<span class="text-muted">-</span>';

    $row[] = ($aRow['im_lot_number'] !== null && $aRow['im_lot_number'] !== '' && $aRow['im_lot_number'] !== '0')
        ? e($aRow['im_lot_number'])
        : '<span class="text-muted">-</span>';

    $output['aaData'][] = $row;
}
