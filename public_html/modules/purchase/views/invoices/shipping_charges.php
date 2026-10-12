<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <div class="_buttons">
              <a href="<?php echo admin_url('purchase/shipping_charge_form'); ?>" class="btn btn-info pull-left">
                <i class="fa fa-plus"></i> <?php echo _l('new'); ?>
              </a>
            </div>
            <div class="clearfix"></div>
            <hr class="hr-panel-heading" />

            <?php
            // 11 headers, matching the 10 $aColumns entries plus Options in
            // table_shipping_charges.php. Keep all three counts in step.
            render_datatable(array(
              '#',
              _l('pur_shipping_invoice_number'),
              _l('pur_shipping_invoice_date'),
              _l('invoices'),
              _l('cost_center'),
              _l('pur_vendor'),
              _l('amount'),
              _l('tax') . ' (%)',
              _l('total'),
              _l('date'),
              _l('options'),
            ), 'shipping-charges'); ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
// Must run AFTER init_tail(), which is what loads jQuery and main.js.
$(function(){
  // Column 10 is the Options column — it has no $aColumns mapping in
  // table_shipping_charges.php, so it must be excluded from both searching and
  // sorting. It has moved as columns were added (7 -> 9 -> 10); the index is
  // always the last header, i.e. header count - 1.
  initDataTable(
    '.table-shipping-charges',
    admin_url + 'purchase/table_shipping_charges',
    [10],         // not searchable
    [10],         // not sortable
    undefined,    // fnServerParams
    [0, 'desc']   // default order: newest shipping number first
  );
});
</script>
</body>
</html>
