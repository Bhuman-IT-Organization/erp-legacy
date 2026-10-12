<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * VAT report: output tax on sales and input tax on purchases for a period.
 *
 * Figures come from the ledger rows sitting on the VAT accounts, so this report and
 * the Trial Balance can never disagree. See Accounting_model::get_vat_report().
 */

$currency_name = isset($currency) && $currency ? $currency->name : '';
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div class="row">
              <div class="col-md-6">
                <h4 class="no-margin font-bold"><?php echo _l('vat_report'); ?></h4>
                <p class="text-muted no-mbot"><?php echo _l('vat_report_note'); ?></p>
              </div>
              <div class="col-md-6 text-right">
                <?php
                // The export carries the period being viewed, so the spreadsheet
                // always covers exactly what is on screen
                $vat_export_url = admin_url('accounting/rp_vat_report_xlsx')
                    . '?from_date=' . urlencode(_d($from_date))
                    . '&to_date=' . urlencode(_d($to_date));
                ?>
                <a href="<?php echo $vat_export_url; ?>" class="btn btn-success noprint">
                  <i class="fa fa-file-excel-o"></i> <?php echo _l('vat_report_export_excel'); ?>
                </a>
                <a href="#" onclick="window.print(); return false;" class="btn btn-default noprint">
                  <i class="fa fa-print"></i> <?php echo _l('print'); ?>
                </a>
              </div>
            </div>

            <hr />

            <?php echo form_open(admin_url('accounting/rp_vat_report'), ['method' => 'get', 'class' => 'noprint']); ?>
            <div class="row">
              <div class="col-md-3">
                <?php echo render_date_input('from_date', 'from_date', _d($from_date)); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_date_input('to_date', 'to_date', _d($to_date)); ?>
              </div>
              <div class="col-md-3">
                <label class="control-label">&nbsp;</label><br />
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
              </div>
            </div>
            <?php echo form_close(); ?>

            <hr />

            <!-- Return header: who is filing, for what period -->
            <div class="row mbot15">
              <div class="col-md-6">
                <h4 class="bold no-mtop"><?php echo format_organization_info(); ?></h4>
                <?php if (!empty($company_trn)) { ?>
                <p class="no-mbot">
                  <span class="bold"><?php echo _l('vat_report_trn'); ?>:</span>
                  <?php echo e($company_trn); ?>
                </p>
                <?php } ?>
              </div>
              <div class="col-md-6 text-right">
                <p class="no-mbot">
                  <span class="bold"><?php echo _l('vat_report_period'); ?>:</span>
                  <?php echo _d($from_date); ?> &mdash; <?php echo _d($to_date); ?>
                </p>
              </div>
            </div>

            <?php
            // ── Summary first: the numbers that go on the return ──────────────
            $net_vat     = $report['net_vat'];
            $is_payable  = $net_vat >= 0;
            ?>
            <div class="row mbot15">
              <div class="col-md-6 col-md-offset-6">
                <table class="table table-bordered no-mbot">
                  <tbody>
                    <tr>
                      <td><?php echo _l('vat_report_output_vat'); ?></td>
                      <td class="text-right bold"><?php echo app_format_money($report['output_vat'], $currency_name); ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _l('vat_report_input_vat'); ?></td>
                      <td class="text-right bold"><?php echo app_format_money($report['input_vat'], $currency_name); ?></td>
                    </tr>
                    <tr style="border-top:2px solid #000;">
                      <td class="bold">
                        <?php echo $is_payable ? _l('vat_report_net_payable') : _l('vat_report_net_reclaimable'); ?>
                      </td>
                      <td class="text-right bold <?php echo $is_payable ? 'text-danger' : 'text-success'; ?>">
                        <?php echo app_format_money(abs($net_vat), $currency_name); ?>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <?php
            // ── The two sections ─────────────────────────────────────────────
            $vat_sections = [
                'sales' => [
                    'heading'    => _l('vat_report_sales_heading'),
                    'note'       => _l('vat_report_sales_note'),
                    'party'      => _l('customer'),
                    'net_label'  => _l('vat_report_taxable_sales'),
                    'vat_label'  => _l('vat_report_output_vat'),
                    'net_total'  => $report['sales_net'],
                    'vat_total'  => $report['output_vat'],
                    'empty'      => _l('vat_report_no_sales'),
                ],
                'purchases' => [
                    'heading'    => _l('vat_report_purchases_heading'),
                    'note'       => _l('vat_report_purchases_note'),
                    'party'      => _l('vendor'),
                    'net_label'  => _l('vat_report_taxable_purchases'),
                    'vat_label'  => _l('vat_report_input_vat'),
                    'net_total'  => $report['purchases_net'],
                    'vat_total'  => $report['input_vat'],
                    'empty'      => _l('vat_report_no_purchases'),
                ],
            ];

            foreach ($vat_sections as $section_key => $section) { ?>
            <h4 class="bold mtop20"><?php echo $section['heading']; ?></h4>
            <p class="text-muted"><?php echo $section['note']; ?></p>

            <?php if (count($report[$section_key]) == 0) { ?>
            <div class="alert alert-info"><?php echo $section['empty']; ?></div>
            <?php } else { ?>
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th><?php echo _l('date'); ?></th>
                    <th><?php echo _l('vat_report_document_type'); ?></th>
                    <!-- Our own number first, then the number on the counterparty's document -->
                    <th><?php echo _l('vat_report_invoice_number'); ?></th>
                    <th><?php echo _l('vat_report_document_number'); ?></th>
                    <th><?php echo $section['party']; ?></th>
                    <th><?php echo _l('vat_report_trn'); ?></th>
                    <th class="text-right"><?php echo $section['net_label']; ?></th>
                    <th class="text-right"><?php echo $section['vat_label']; ?></th>
                    <th class="text-right"><?php echo _l('vat_report_rate'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php $sr = 1;
                foreach ($report[$section_key] as $line) {
                    // Effective rate, useful for spotting a mis-keyed tax line.
                    // Blank when there is no taxable base to divide by.
                    $effective_rate = $line['net_amount'] != 0
                        ? round($line['vat_amount'] / $line['net_amount'] * 100, 2)
                        : null;
                    ?>
                  <tr>
                    <td><?php echo $sr++; ?></td>
                    <td><?php echo _d($line['date']); ?></td>
                    <td><?php echo e($line['document_type']); ?></td>
                    <td>
                      <?php if (!empty($line['document_url'])) { ?>
                      <a href="<?php echo $line['document_url']; ?>" target="_blank"><?php echo e($line['number']); ?></a>
                      <?php } else {
                          echo e($line['number']);
                      } ?>
                    </td>
                    <td>
                      <?php
                        // The party's own reference. Not every document type carries
                        // one - shipping charges and notes have no such field - so an
                        // em dash makes an empty cell read as "none" rather than as a
                        // rendering fault.
                        echo !empty($line['reference']) ? e($line['reference']) : '&mdash;';
                      ?>
                    </td>
                    <td>
                      <?php if (!empty($line['party_name']) && !empty($line['party_url'])) { ?>
                      <a href="<?php echo $line['party_url']; ?>" target="_blank"><?php echo e($line['party_name']); ?></a>
                      <?php } else {
                          echo e($line['party_name']);
                      } ?>
                    </td>
                    <td><?php echo e($line['party_trn']); ?></td>
                    <td class="text-right"><?php echo app_format_money($line['net_amount'], $currency_name); ?></td>
                    <td class="text-right bold"><?php echo app_format_money($line['vat_amount'], $currency_name); ?></td>
                    <td class="text-right">
                      <?php echo $effective_rate === null ? '' : app_format_number($effective_rate) . '%'; ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="7" class="text-right bold"><?php echo _l('total'); ?></td>
                    <td class="text-right bold"><?php echo app_format_money($section['net_total'], $currency_name); ?></td>
                    <td class="text-right bold"><?php echo app_format_money($section['vat_total'], $currency_name); ?></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <?php } ?>
            <?php } ?>

            <?php
            // Which accounts the figures were read from, so the report can be tied
            // back to the Trial Balance without guesswork
            $vat_account_labels = ['output' => [], 'input' => []];
            foreach ($report['accounts'] as $bucket => $bucket_accounts) {
                foreach ($bucket_accounts as $bucket_account) {
                    $vat_account_labels[$bucket][] = trim($bucket_account['number'] . ' - ' . $bucket_account['name']);
                }
            }
            ?>
            <hr />
            <p class="text-muted tw-text-sm no-mbot">
              <?php echo _l('vat_report_source_note'); ?>
              <?php if (count($vat_account_labels['output']) > 0) { ?>
              <br /><span class="bold"><?php echo _l('vat_report_output_vat'); ?>:</span>
              <?php echo e(implode(', ', $vat_account_labels['output'])); ?>
              <?php } ?>
              <?php if (count($vat_account_labels['input']) > 0) { ?>
              <br /><span class="bold"><?php echo _l('vat_report_input_vat'); ?>:</span>
              <?php echo e(implode(', ', $vat_account_labels['input'])); ?>
              <?php } ?>
            </p>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
  // Runs after init_tail() so jQuery and the date pickers are available
  $(function() {
      init_datepicker();
  });
</script>
</body>
</html>
