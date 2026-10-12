<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-center">
              <h4 class="tw-mt-0 tw-font-bold">Sales Return: <?php echo $return->return_number ?: 'SR-'.$return->id; ?></h4>
              <div>
                <span class="label label-<?php echo $return->status == 'draft' ? 'default' : ($return->status == 'approved' ? 'success' : 'danger'); ?> tw-text-sm"><?php echo ucfirst($return->status); ?></span>
                <a href="<?php echo admin_url('sales_returns/create/'.$return->id); ?>" class="btn btn-default btn-sm mLeft5"><i class="fa fa-pencil"></i> Edit</a>
                <a href="<?php echo admin_url('sales_returns'); ?>" class="btn btn-default btn-sm mLeft5"><i class="fa fa-arrow-left"></i> Back</a>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-md-4">
                <p><strong>Customer:</strong> <?php echo get_company_name($return->clientid); ?></p>
              </div>
              <div class="col-md-4">
                <p><strong>Sales Order:</strong> <?php echo $return->estimate_id ? format_estimate_number($return->estimate_id) : '-'; ?></p>
              </div>
              <div class="col-md-4">
                <p><strong>Return Date:</strong> <?php echo _d($return->return_date); ?></p>
              </div>
            </div>
            <?php if(!empty($return->note)){ ?>
            <p><strong>Note:</strong> <?php echo nl2br($return->note); ?></p>
            <?php } ?>
            <hr>
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr style="background-color:<?php echo get_option('pdf_table_heading_color'); ?>; color:<?php echo get_option('pdf_table_heading_text_color'); ?>;">
                    <th>#</th>
                    <th>Item</th>
                    <th class="text-right">Return Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i=1; foreach($return_items as $item){ ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $item['description']; ?></td>
                    <td class="text-right"><?php echo (float)$item['qty']; ?></td>
                    <td class="text-right"><?php echo app_format_money((float)$item['unit_price'], ''); ?></td>
                    <td class="text-right"><?php echo app_format_money((float)$item['amount'], ''); ?></td>
                  </tr>
                  <?php } ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right"><strong><?php echo app_format_money($return->total, get_base_currency()); ?></strong></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
</body>
</html>
