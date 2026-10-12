<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12" id="small-table">
        <div class="panel_s">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-10">
                <h4 class="no-margin font-bold"><i class="fa fa-reply-all menu-icon" aria-hidden="true"></i> Sales Returns</h4>
              </div>
              <div class="col-md-2 text-right">
                <a href="<?php echo admin_url('sales_returns/create'); ?>" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo _l('pur_new'); ?></a>
              </div>
            </div>
            <hr />
            <div class="table-responsive">
              <table class="table table-striped dt-table" data-order='[[0,"desc"]]'>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Return Number</th>
                    <th>Customer</th>
                    <th>Sales Order</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($returns as $r){ 
                    $status_class = 'default';
                    if($r['status'] == 'approved' || $r['status'] == 'finish') $status_class = 'success';
                    elseif($r['status'] == 'canceled') $status_class = 'danger';
                    elseif($r['status'] == 'processing') $status_class = 'info';
                  ?>
                  <tr>
                    <td><?php echo $r['id']; ?></td>
                    <td>
                      <a href="<?php echo admin_url('sales_returns/view/'.$r['id']); ?>"><?php echo $r['return_number'] ?: 'SR-'.$r['id']; ?></a>
                    </td>
                    <td><?php echo get_company_name($r['clientid']); ?></td>
                    <td><?php echo $r['estimate_id'] ? format_estimate_number($r['estimate_id']) : '-'; ?></td>
                    <td><?php echo _d($r['return_date']); ?></td>
                    <td><?php echo app_format_money($r['total'], get_base_currency()); ?></td>
                    <td><span class="label label-<?php echo $status_class; ?> inline-block"><?php echo ucfirst($r['status']); ?></span></td>
                    <td>
                      <?php if($r['status'] != 'canceled'){ ?>
                      <div class="row-options">
                        <a href="<?php echo admin_url('sales_returns/view/'.$r['id']); ?>"><?php echo _l('view'); ?></a> |
                        <a href="<?php echo admin_url('sales_returns/create/'.$r['id']); ?>"><?php echo _l('edit'); ?></a> |
                        <a href="<?php echo admin_url('sales_returns/delete/'.$r['id']); ?>" class="text-danger _delete"><?php echo _l('delete'); ?></a>
                      </div>
                      <?php } else { ?>
                      <div class="row-options">
                        <a href="<?php echo admin_url('sales_returns/view/'.$r['id']); ?>"><?php echo _l('view'); ?></a>
                      </div>
                      <?php } ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
  initDataTable('.dt-table', window.location.href, [], [], undefined, [0, 'desc']);
});
</script>
</body>
</html>
