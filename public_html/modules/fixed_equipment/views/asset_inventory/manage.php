<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div class="row mbot15">
              <div class="col-md-8">
                <h4 class="no-margin"><?php echo _l('fe_asset_inventory'); ?></h4>
                <p class="text-muted no-margin">
                  <?php echo _l('fe_asset_inventory_help'); ?>
                </p>
              </div>
              <div class="col-md-4 text-right">
                <?php
                // Whole-table figures. A datatable footer could only total the page
                // being looked at, which would read as the company's asset stock and
                // be wrong.
                $rows  = isset($summary->stock_rows) ? (int) $summary->stock_rows : 0;
                $qty   = isset($summary->total_qty) ? (float) $summary->total_qty : 0;
                $value = isset($summary->total_value) ? (float) $summary->total_value : 0;
                ?>
                <span class="text-muted"><?php echo _l('fe_asset_inventory_totals'); ?></span><br />
                <span class="bold"><?php echo app_format_number($rows); ?></span> <?php echo _l('fe_asset_inventory_batches'); ?>
                &middot;
                <span class="bold"><?php echo app_format_number($qty); ?></span> <?php echo _l('fe_asset_inventory_units'); ?>
                &middot;
                <span class="bold"><?php echo app_format_money($value, ''); ?></span>
              </div>
            </div>

            <?php if (!empty($gl_reconciliation)) { ?>
              <div class="row mbot15">
                <div class="col-md-7">
                  <p class="text-muted no-mbot"><?php echo _l('fe_asset_inventory_recon_help'); ?></p>
                </div>
                <div class="col-md-5">
                  <table class="table table-borderless no-margin">
                    <tbody>
                      <tr>
                        <td><?php echo _l('fe_recon_stock_at_cost'); ?></td>
                        <td class="text-right"><?php echo app_format_money($gl_reconciliation['stock_value'], ''); ?></td>
                      </tr>
                      <?php if (abs($gl_reconciliation['freight']) > 0.004) { ?>
                        <tr>
                          <td><?php echo _l('fe_recon_freight'); ?></td>
                          <td class="text-right"><?php echo app_format_money($gl_reconciliation['freight'], ''); ?></td>
                        </tr>
                      <?php } ?>
                      <?php if (abs($gl_reconciliation['not_stocked']) > 0.004) { ?>
                        <tr>
                          <td>
                            <?php echo _l('fe_recon_not_stocked'); ?>
                            <i class="fa fa-question-circle text-muted" data-toggle="tooltip"
                               title="<?php echo _l('fe_recon_not_stocked_help'); ?>"></i>
                          </td>
                          <td class="text-right"><?php echo app_format_money($gl_reconciliation['not_stocked'], ''); ?></td>
                        </tr>
                      <?php } ?>
                      <tr class="bold" style="border-top:1px solid #ddd">
                        <td>
                          <?php
                          // Name the accounts so the figure can be checked against the
                          // chart of accounts without guessing which one is meant.
                          $labels = [];
                          foreach ($gl_reconciliation['accounts'] as $acc) {
                              $labels[] = $acc['number'] . ' ' . $acc['name'];
                          }
                          echo e(implode(', ', $labels));
                          ?>
                        </td>
                        <td class="text-right"><?php echo app_format_money($gl_reconciliation['gl_total'], ''); ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php } ?>

            <?php if (!empty($setup_missing)) { ?>
              <div class="alert alert-warning">
                <p class="bold no-mbot"><?php echo _l('fe_asset_inventory_setup_required'); ?></p>
                <ul class="no-mbot">
                  <?php foreach ($setup_missing as $problem) { ?>
                    <li><?php echo e($problem); ?></li>
                  <?php } ?>
                </ul>
              </div>
            <?php } ?>

            <hr class="hr-panel-heading" />

            <div class="row mbot15">
              <div class="col-md-4">
                <label for="asset_ft"><?php echo _l('fe_asset_creation'); ?></label>
                <select name="asset_ft[]" id="asset_ft" class="selectpicker" multiple
                        data-width="100%" data-live-search="true"
                        data-none-selected-text="<?php echo _l('dt_button_reload'); ?>">
                  <?php foreach ($assets as $asset) { ?>
                    <option value="<?php echo (int) $asset['id']; ?>">
                      <?php echo e($asset['code'] . ' - ' . $asset['asset_title']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="col-md-3">
                <label for="warehouse_ft"><?php echo _l('fe_warehouse'); ?></label>
                <select name="warehouse_ft[]" id="warehouse_ft" class="selectpicker" multiple
                        data-width="100%" data-live-search="true">
                  <?php foreach ($warehouses as $warehouse) { ?>
                    <option value="<?php echo (int) $warehouse['warehouse_id']; ?>">
                      <?php echo e($warehouse['warehouse_name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="col-md-3">
                <label for="department_ft"><?php echo _l('department'); ?></label>
                <select name="department_ft[]" id="department_ft" class="selectpicker" multiple
                        data-width="100%" data-live-search="true">
                  <?php foreach ($departments_filter as $dept) { ?>
                    <option value="<?php echo e($dept['department']); ?>">
                      <?php echo e($dept['department']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="col-md-2">
                <label>&nbsp;</label>
                <div class="checkbox checkbox-primary">
                  <input type="checkbox" id="show_zero_qty" name="show_zero_qty" value="1">
                  <label for="show_zero_qty"><?php echo _l('fe_asset_inventory_show_zero'); ?></label>
                </div>
              </div>
            </div>

            <?php
            $table_data = array(
              _l('fe_asset_code'),
              _l('fe_asset_uid'),
              _l('fe_asset_title'),
              _l('fe_asset_class'),
              _l('department'),
              _l('fe_cost_centre'),
              _l('fe_manufacturer'),
              _l('fe_warehouse'),
              _l('fe_quantity'),
              _l('fe_asset_inventory_purchase_price'),
              _l('fe_asset_inventory_landed_cost'),
              _l('fe_asset_inventory_stock_value'),
              _l('fe_capitalization_date'),
              _l('fe_lot_number'),
            );

            render_datatable($table_data, 'asset_inventory');
            ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
  // Must come after init_tail(): initDataTable and jQuery are not defined before it.
  (function ($) {
    "use strict";

    var ServerParams = {
      "asset_ft": "[name='asset_ft[]']",
      "warehouse_ft": "[name='warehouse_ft[]']",
      "department_ft": "[name='department_ft[]']",
      "show_zero_qty": "[name='show_zero_qty']"
    };

    // Columns 10 and 11 are Landed Cost and Stock Value. Both are calculated per
    // row in PHP rather than selected, so SQL cannot sort or search them - the same
    // treatment the item stock grid gives its landed cost column.
    // Default sort: column 0, asset code, descending.
    var table_asset_inventory = $('.table-asset_inventory');
    initDataTable(
      table_asset_inventory,
      admin_url + 'fixed_equipment/table_asset_inventory',
      [10, 11],
      [10, 11],
      ServerParams,
      [0, 'desc']
    );

    $.each(ServerParams, function (i, selector) {
      $(selector).on('change', function () {
        table_asset_inventory.DataTable().ajax.reload();
      });
    });
  })(jQuery);
</script>
