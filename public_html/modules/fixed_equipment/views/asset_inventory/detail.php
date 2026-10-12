<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-9">
                <h4 class="no-margin">
                  <?php echo e($asset->asset_title); ?>
                  <small class="text-muted"><?php echo e($asset->code); ?></small>
                </h4>
                <?php if (!empty($asset->descriptions)) { ?>
                  <p class="text-muted no-margin"><?php echo e($asset->descriptions); ?></p>
                <?php } ?>
              </div>
              <div class="col-md-3 text-right">
                <a href="<?php echo admin_url('fixed_equipment/asset_inventory'); ?>" class="btn btn-default">
                  <i class="fa fa-arrow-left"></i> <?php echo _l('fe_asset_inventory'); ?>
                </a>
                <a href="<?php echo admin_url('fixed_equipment/asset_creation/' . $asset->id); ?>" class="btn btn-default">
                  <i class="fa fa-pencil-square-o"></i> <?php echo _l('edit'); ?>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">

      <!-- Asset details -->
      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop bold"><?php echo _l('fe_asset_detail'); ?></h4>
            <hr class="hr-panel-heading" />
            <table class="table table-borderless no-margin">
              <tbody>
                <?php
                // Only fields that carry a value are listed. A column of dashes says
                // nothing and pushes the useful rows off the screen.
                $details = [
                  _l('fe_asset_code')           => $asset->code,
                  _l('fe_asset_uid')            => $asset->asset_uid,
                  _l('fe_asset_class')          => isset($asset->asset_class) ? $asset->asset_class : '',
                  _l('fe_capitalization_date')  => (isset($asset->capitalization_date) && !empty($asset->capitalization_date) && $asset->capitalization_date != '0000-00-00')
                                                     ? _d($asset->capitalization_date) : '',
                  _l('department')              => $asset->department,
                  _l('fe_cost_centre')          => $asset->cost_centre,
                  _l('fe_manufacturer')         => $asset->manufacturer_name,
                  _l('fe_vendor')               => $asset->vendor_name,
                  _l('fe_useful_life')          => $asset->useful_life,
                  _l('fe_location')             => $asset->location,
                  _l('fe_quantity')             => $asset->qty,
                ];

                foreach ($details as $label => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    echo '<tr><td class="bold" style="width:45%">' . $label . '</td><td>' . e($value) . '</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Stock and cost -->
      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop bold"><?php echo _l('fe_asset_stock_and_cost'); ?></h4>
            <hr class="hr-panel-heading" />
            <?php
            $batches = isset($stock_totals->batches) ? (int) $stock_totals->batches : 0;
            $qty     = isset($stock_totals->total_qty) ? (float) $stock_totals->total_qty : 0;
            $value   = isset($stock_totals->total_value) ? (float) $stock_totals->total_value : 0;
            ?>
            <table class="table table-borderless no-margin">
              <tbody>
                <tr>
                  <td class="bold" style="width:45%"><?php echo _l('fe_quantity_on_hand'); ?></td>
                  <td><span class="bold"><?php echo app_format_number($qty); ?></span>
                    <span class="text-muted">
                      (<?php echo app_format_number($batches) . ' ' . _l('fe_asset_inventory_batches'); ?>)
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('fe_asset_inventory_stock_value'); ?></td>
                  <td><?php echo app_format_money($value, $base_currency); ?></td>
                </tr>
                <?php if (isset($purchase_cost['unit_cost']) && $purchase_cost['unit_cost'] > 0) { ?>
                  <tr>
                    <td class="bold"><?php echo _l('fe_avg_purchase_unit_cost'); ?></td>
                    <td>
                      <?php echo app_format_money($purchase_cost['unit_cost'], $base_currency); ?>
                      <span class="text-muted text-sm">
                        <?php echo ' ' . _l('fe_goods_only'); ?>
                      </span>
                    </td>
                  </tr>
                <?php } ?>
                <?php if ($landed_unit_cost > 0) { ?>
                  <tr>
                    <td class="bold"><?php echo _l('fe_avg_landed_unit_cost'); ?></td>
                    <td>
                      <span class="bold"><?php echo app_format_money($landed_unit_cost, $base_currency); ?></span>
                      <span class="text-muted text-sm">
                        <?php echo ' ' . _l('fe_including_freight'); ?>
                      </span>
                    </td>
                  </tr>
                <?php } ?>
                <?php if (isset($purchase_cost['last_invoice_number']) && $purchase_cost['last_invoice_number'] != '') { ?>
                  <tr>
                    <td class="bold"><?php echo _l('fe_last_invoice'); ?></td>
                    <td>
                      <a href="<?php echo admin_url('purchase/purchase_invoice/' . $purchase_cost['last_invoice_id']); ?>" target="_blank">
                        <?php echo e($purchase_cost['last_invoice_number']); ?>
                      </a>
                      <?php if (!empty($purchase_cost['last_invoice_date'])) { ?>
                        <span class="text-muted text-sm"><?php echo ' ' . _d($purchase_cost['last_invoice_date']); ?></span>
                      <?php } ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>

            <?php if ($qty > 0 && $landed_unit_cost <= 0) { ?>
              <div class="alert alert-info mtop15 no-mbot">
                <?php echo _l('fe_no_landed_cost_yet'); ?>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Stock batches -->
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop bold"><?php echo _l('fe_stock_batches'); ?></h4>
            <p class="text-muted"><?php echo _l('fe_stock_batches_help'); ?></p>
            <hr class="hr-panel-heading" />
            <?php render_datatable(array(
              _l('id'),
              _l('fe_warehouse'),
              _l('fe_quantity'),
              _l('fe_asset_inventory_purchase_price'),
              _l('fe_asset_inventory_landed_cost'),
              _l('fe_asset_inventory_stock_value'),
              _l('fe_lot_number'),
              _l('fe_expiry_date'),
              _l('fe_date_manufacture'),
            ), 'asset_stock'); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Landed cost per invoice -->
    <?php if (count($landed_cost_lines) > 0) { ?>
      <div class="row">
        <div class="col-md-12">
          <div class="panel_s">
            <div class="panel-body">
              <h4 class="no-mtop bold"><?php echo _l('fe_purchase_history'); ?></h4>
              <p class="text-muted"><?php echo _l('fe_purchase_history_help'); ?></p>
              <hr class="hr-panel-heading" />
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th><?php echo _l('fe_invoice'); ?></th>
                      <th><?php echo _l('fe_invoice_date'); ?></th>
                      <th class="text-right"><?php echo _l('fe_quantity'); ?></th>
                      <th class="text-right"><?php echo _l('fe_unit_price'); ?></th>
                      <th class="text-right"><?php echo _l('fe_line_net'); ?></th>
                      <th class="text-right"><?php echo _l('fe_freight_allocated'); ?></th>
                      <th class="text-right"><?php echo _l('fe_landed_total'); ?></th>
                      <th class="text-right"><?php echo _l('fe_asset_inventory_landed_cost'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($landed_cost_lines as $line) { ?>
                      <tr>
                        <td>
                          <a href="<?php echo admin_url('purchase/purchase_invoice/' . $line['invoice_id']); ?>" target="_blank">
                            <?php echo e($line['invoice_number']); ?>
                          </a>
                          <?php if (!empty($line['vendor_invoice_number'])) { ?>
                            <br /><span class="text-muted text-sm"><?php echo e($line['vendor_invoice_number']); ?></span>
                          <?php } ?>
                          <?php foreach ($line['shipping_charges'] as $charge) { ?>
                            <br /><a class="text-sm" href="<?php echo admin_url('purchase/view_shipping_charge/' . $charge['id']); ?>" target="_blank">
                              <?php echo e($charge['number']); ?>
                            </a>
                          <?php } ?>
                        </td>
                        <td><?php echo !empty($line['invoice_date']) ? _d($line['invoice_date']) : '-'; ?></td>
                        <td class="text-right"><?php echo app_format_number($line['quantity']); ?></td>
                        <td class="text-right"><?php echo app_format_money($line['unit_price'], $base_currency); ?></td>
                        <td class="text-right"><?php echo app_format_money($line['line_net'], $base_currency); ?></td>
                        <td class="text-right">
                          <?php echo app_format_money($line['allocated_shipping'], $base_currency); ?>
                          <?php if ($line['share_percent'] > 0) { ?>
                            <br /><span class="text-muted text-sm"><?php echo app_format_number($line['share_percent']); ?>%</span>
                          <?php } ?>
                        </td>
                        <td class="text-right"><?php echo app_format_money($line['landed_total'], $base_currency); ?></td>
                        <td class="text-right bold"><?php echo app_format_money($line['landed_unit_cost'], $base_currency); ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>

  </div>
</div>
<?php init_tail(); ?>
<script>
  // After init_tail(): initDataTable and jQuery do not exist before it.
  (function ($) {
    "use strict";

    // Columns 4 and 5 are Landed Cost and Stock Value, both computed per row in
    // PHP rather than selected, so SQL cannot sort or search them.
    initDataTable(
      $('.table-asset_stock'),
      admin_url + 'fixed_equipment/table_asset_stock/<?php echo (int) $asset->id; ?>',
      [4, 5],
      [4, 5],
      undefined,
      [0, 'desc']
    );
  })(jQuery);
</script>
