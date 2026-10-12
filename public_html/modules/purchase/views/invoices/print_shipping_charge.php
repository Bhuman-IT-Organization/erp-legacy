<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Printable shipping charge.
 *
 * Deliberately standalone rather than going through init_head(): the admin
 * chrome, sidebar and scripts are not wanted on paper, and every service line
 * must appear - no datatable paging, no scroll container.
 *
 * The VAT-row rule matches the form, the controller and the on-screen view: a
 * line whose description starts with the word VAT restates the tax total and is
 * kept out of Amount Excl. VAT.
 */

$sc_number = !empty($shipping_charge->shipping_number)
    ? $shipping_charge->shipping_number
    : 'ISC' . str_pad($shipping_charge->id, 5, '0', STR_PAD_LEFT);

$sc_items    = isset($shipping_charge_items) ? $shipping_charge_items : [];
$sc_subtotal = 0;
$sc_vat      = 0;
$sc_vat_line = 0;

foreach ($sc_items as $sc_item) {
    if (preg_match('/^vat(?![a-z])/i', trim((string) $sc_item['description']))) {
        $sc_vat_line += (float) $sc_item['amount'];
    } else {
        $sc_subtotal += (float) $sc_item['amount'];
        $sc_vat      += (float) $sc_item['tax_amount'];
    }
}

if (round($sc_vat, 2) == 0 && $sc_vat_line > 0) {
    $sc_vat = $sc_vat_line;
}

$sc_net      = round($sc_subtotal + $sc_vat, 2);
$company     = get_option('invoice_company_name') ?: get_option('companyname');
$currency_lbl = $currency_name != '' ? ' (' . $currency_name . ')' : '';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php echo _l('inventory_shipping_charges') . ' ' . $sc_number; ?></title>
  <style>
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #000; margin: 22px; }
    h1 { font-size: 17px; margin: 0 0 2px 0; }
    h2 { font-size: 14px; margin: 14px 0 6px 0; }
    .muted { color: #555; }
    .right { text-align: right; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    table { width: 100%; border-collapse: collapse; }
    table.items th, table.items td { border: 1px solid #000; padding: 5px 6px; }
    table.items th { background: #eee; font-size: 11px; text-transform: uppercase; }
    table.meta td { padding: 2px 4px; vertical-align: top; }
    table.totals td { border: 1px solid #000; padding: 5px 6px; }
    .vat-row td { background: #f7f7ea; }
    .head { border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
    .foot { margin-top: 26px; font-size: 11px; }
    .noprint { margin-bottom: 14px; }
    @media print { .noprint { display: none !important; } body { margin: 0; } }
  </style>
</head>
<body>

<div class="noprint">
  <button type="button" onclick="window.print();">Print</button>
  <button type="button" onclick="window.close();">Close</button>
</div>

<div class="head">
  <h1><?php echo e($company); ?></h1>
  <div class="muted"><?php echo _l('inventory_shipping_charges'); ?> &mdash; <span class="bold"><?php echo e($sc_number); ?></span></div>
</div>

<table class="meta">
  <tr>
    <td width="15%" class="bold"><?php echo _l('invoice_code'); ?>:</td>
    <td width="35%"><?php echo isset($invoice->invoice_number) ? e($invoice->invoice_number) : ''; ?></td>
    <td width="15%" class="bold"><?php echo _l('pur_order'); ?>:</td>
    <td width="35%"><?php echo e($po_number); ?></td>
  </tr>
  <tr>
    <td class="bold"><?php echo _l('invoice_date'); ?>:</td>
    <td><?php echo isset($invoice->invoice_date) ? _d($invoice->invoice_date) : ''; ?></td>
    <td class="bold"><?php echo _l('pur_vendor'); ?>:</td>
    <td><?php echo e($invoice_vendor_name); ?></td>
  </tr>
  <tr>
    <td class="bold">Shipping Vendor:</td>
    <td><?php echo e($vendor_name); ?></td>
    <td class="bold">Created:</td>
    <td><?php echo _dt($shipping_charge->date_created); ?></td>
  </tr>
  <tr>
    <?php
    // The freight vendor's own reference and its date. Blank on charges raised
    // before these fields existed.
    ?>
    <td class="bold"><?php echo _l('pur_shipping_invoice_number'); ?>:</td>
    <td><?php echo !empty($shipping_charge->shipping_invoice_number) ? e($shipping_charge->shipping_invoice_number) : ''; ?></td>
    <td class="bold"><?php echo _l('pur_shipping_invoice_date'); ?>:</td>
    <td>
      <?php echo (!empty($shipping_charge->shipping_invoice_date) && $shipping_charge->shipping_invoice_date != '0000-00-00')
          ? _d($shipping_charge->shipping_invoice_date)
          : ''; ?>
    </td>
  </tr>
  <tr>
    <?php
    // The cost centre the charge is coded to, and the GL account the freight was
    // debited to. The account comes from the purchase order rather than always
    // being Inventory Raw Materials.
    ?>
    <td class="bold"><?php echo _l('cost_center'); ?>:</td>
    <td><?php echo e($cost_center_name); ?></td>
    <td class="bold"><?php echo _l('pur_shipping_charge_item_type'); ?>:</td>
    <td><?php echo !empty($debit_po_type_name) ? e($debit_po_type_name) : ''; ?></td>
  </tr>
  <tr>
    <?php // The account the freight was debited to, and the order type that decided it ?>
    <td class="bold"><?php echo _l('pur_shipping_charge_debit_account'); ?>:</td>
    <td colspan="3"><?php echo e($debit_account_name); ?></td>
  </tr>
</table>

<h2><?php echo _l('pur_shipping_charge_services'); ?></h2>

<table class="items">
  <thead>
    <tr>
      <th width="45">SNO</th>
      <th><?php echo _l('pur_shipping_charge_description'); ?></th>
      <th width="70" class="right"><?php echo _l('quantity'); ?></th>
      <th width="95" class="right"><?php echo _l('pur_shipping_charge_rate'); ?></th>
      <th width="110" class="right"><?php echo _l('amount'); ?><?php echo $currency_lbl; ?></th>
      <th width="65" class="right"><?php echo _l('pur_shipping_charge_vat_percent'); ?></th>
      <th width="100" class="right"><?php echo _l('pur_shipping_charge_tax_amount'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php if (count($sc_items) === 0) { ?>
    <tr><td colspan="7" class="center muted"><?php echo _l('no_items_found'); ?></td></tr>
    <?php } else {
      $sno = 1;
      foreach ($sc_items as $sc_item) {
        $is_vat_row = (bool) preg_match('/^vat(?![a-z])/i', trim((string) $sc_item['description']));
    ?>
    <tr<?php echo $is_vat_row ? ' class="vat-row"' : ''; ?>>
      <td><?php echo $sno++; ?></td>
      <td><?php echo e($sc_item['description']); ?></td>
      <td class="right"><?php echo number_format((float) $sc_item['qty'], 2); ?></td>
      <td class="right"><?php echo number_format((float) $sc_item['rate'], 2); ?></td>
      <td class="right"><?php echo number_format((float) $sc_item['amount'], 2); ?></td>
      <td class="right"><?php echo number_format((float) $sc_item['vat_percent'], 2); ?></td>
      <td class="right"><?php echo number_format((float) $sc_item['tax_amount'], 2); ?></td>
    </tr>
    <?php } } ?>
  </tbody>
</table>

<table style="margin-top:16px;">
  <tr>
    <td width="58%" style="vertical-align:bottom;">
      <span class="bold">TOTAL:</span><br>
      <span class="muted"><?php echo e(pur_amount_in_words($sc_net, $currency_name)); ?></span>
    </td>
    <td width="42%">
      <table class="totals">
        <tr>
          <td class="bold"><?php echo _l('pur_shipping_charge_amount_excl_vat'); ?></td>
          <td class="right"><?php echo number_format($sc_subtotal, 2); ?></td>
        </tr>
        <tr>
          <td class="bold"><?php echo _l('pur_shipping_charge_vat_total'); ?></td>
          <td class="right"><?php echo number_format($sc_vat, 2); ?></td>
        </tr>
        <tr>
          <td class="bold"><?php echo _l('pur_shipping_charge_net_total'); ?></td>
          <td class="right bold"><?php echo number_format($sc_net, 2); ?></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<?php
// Vendor Note and Terms print; Admin Note deliberately does not, since it is an
// internal note and this sheet can go to the vendor. Notes are plain text and
// escaped, terms hold HTML from the vendor record or a term template.
if (!empty($shipping_charge->vendor_note)) { ?>
<div style="margin-top:16px;">
  <div class="bold"><?php echo _l('vendor_note'); ?></div>
  <div style="white-space:pre-wrap;"><?php echo e($shipping_charge->vendor_note); ?></div>
</div>
<?php }

if (!empty($shipping_charge->terms)) { ?>
<div style="margin-top:16px;">
  <div class="bold"><?php echo _l('terms_and_conditions'); ?></div>
  <div><?php echo $shipping_charge->terms; ?></div>
</div>
<?php } ?>

<div class="foot muted">
  <?php echo _l('inventory_shipping_charges') . ' ' . e($sc_number); ?>
  &nbsp;|&nbsp; <?php echo _dt(date('Y-m-d H:i:s')); ?>
</div>

<?php if (!$this->input->get('noprint')) { ?>
<script>window.addEventListener('load', function(){ window.print(); });</script>
<?php } ?>

</body>
</html>
