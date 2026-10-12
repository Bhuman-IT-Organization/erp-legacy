<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s mbot10">
          <div class="panel-body">
            <div class="row mbot10">
              <div class="col-md-8"><h4 class="no-margin font-bold"><i class="fa fa-bar-chart"></i> Cheque Reports</h4></div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('accounting/cheque_management'); ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to List</a>
              </div>
            </div>
            <hr>

            <!-- Summary Cards -->
            <div class="row mbot20">
              <?php foreach($summary as $sid => $s): ?>
              <div class="col-md-2 col-sm-4 mbot10">
                <div class="panel_s">
                  <div class="panel-body text-center">
                    <h5 class="no-margin bold text-<?php echo $s['class']; ?>"><?php echo $s['label']; ?></h5>
                    <h2 class="no-margin bold"><?php echo $s['count']; ?></h2>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mbot15" role="tablist">
              <li class="active"><a href="#tab_pdc" data-toggle="tab"><i class="fa fa-calendar"></i> Post-Dated Cheques (<?php echo count($pdc_cheques); ?>)</a></li>
              <li><a href="#tab_bounced" data-toggle="tab"><i class="fa fa-exclamation-triangle text-danger"></i> Bounced (<?php echo count($bounced_cheques); ?>)</a></li>
              <li><a href="#tab_aging" data-toggle="tab"><i class="fa fa-clock-o text-warning"></i> Aging (<?php echo count($aging_cheques); ?>)</a></li>
            </ul>

            <div class="tab-content">
              <!-- PDC Report -->
              <div class="tab-pane active" id="tab_pdc">
                <h5 class="bold">Post-Dated Cheques (Not Yet Cleared)</h5>
                <?php if(count($pdc_cheques) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped">
                    <thead><tr>
                      <th>Cheque #</th><th>Party</th><th>Bank</th><th>Cheque Date</th><th>Amount</th><th>Invoice Ref</th><th>Status</th><th>Days Until Due</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach($pdc_cheques as $ch):
                      $days = (int)((strtotime($ch['cheque_date']) - time()) / 86400);
                      $st = $status_map[$ch['status']] ?? ['Unknown','default'];
                    ?>
                    <tr>
                      <td><a href="<?php echo admin_url('accounting/cheque_management_view/'.$ch['id']); ?>">#<?php echo htmlspecialchars($ch['cheque_number']); ?></a></td>
                      <td><?php echo htmlspecialchars($ch['rel_name']??'-'); ?></td>
                      <td><?php echo htmlspecialchars($ch['cheque_bank_name']??'-'); ?></td>
                      <td><?php echo _d($ch['cheque_date']); ?></td>
                      <td><?php echo app_format_money($ch['amount'],$currency->name); ?></td>
                      <td><?php echo htmlspecialchars($ch['invoice_ref']??'-'); ?></td>
                      <td><span class="label label-<?php echo $st[1]; ?>"><?php echo $st[0]; ?></span></td>
                      <td class="<?php echo $days < 0 ? 'text-danger' : ($days <= 7 ? 'text-warning' : 'text-success'); ?>">
                        <?php echo $days < 0 ? abs($days).' days overdue' : $days.' days'; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php else: ?><p class="text-muted">No post-dated cheques pending.</p><?php endif; ?>
              </div>

              <!-- Bounced Report -->
              <div class="tab-pane" id="tab_bounced">
                <h5 class="bold text-danger">Bounced Cheques</h5>
                <?php if(count($bounced_cheques) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped">
                    <thead><tr>
                      <th>Cheque #</th><th>Party</th><th>Bank</th><th>Cheque Date</th><th>Bounce Date</th><th>Amount</th><th>Bounce Reason</th><th>Charges</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach($bounced_cheques as $ch): ?>
                    <tr>
                      <td><a href="<?php echo admin_url('accounting/cheque_management_view/'.$ch['id']); ?>">#<?php echo htmlspecialchars($ch['cheque_number']); ?></a></td>
                      <td><?php echo htmlspecialchars($ch['rel_name']??'-'); ?></td>
                      <td><?php echo htmlspecialchars($ch['cheque_bank_name']??'-'); ?></td>
                      <td><?php echo _d($ch['cheque_date']); ?></td>
                      <td><?php echo !empty($ch['bounce_date'])?_d($ch['bounce_date']):'-'; ?></td>
                      <td><?php echo app_format_money($ch['amount'],$currency->name); ?></td>
                      <td><?php echo htmlspecialchars($ch['bounce_reason']??'-'); ?></td>
                      <td><?php echo app_format_money($ch['bounce_charges']??0,$currency->name); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php else: ?><p class="text-muted">No bounced cheques.</p><?php endif; ?>
              </div>

              <!-- Aging Report -->
              <div class="tab-pane" id="tab_aging">
                <h5 class="bold text-warning">Aging Cheques (Deposited &gt; 7 days, not cleared)</h5>
                <?php if(count($aging_cheques) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped">
                    <thead><tr>
                      <th>Cheque #</th><th>Party</th><th>Bank</th><th>Cheque Date</th><th>Deposit Date</th><th>Amount</th><th>Days Pending</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach($aging_cheques as $ch):
                      $days_pending = !empty($ch['deposit_date']) ? (int)((time() - strtotime($ch['deposit_date'])) / 86400) : '-';
                    ?>
                    <tr>
                      <td><a href="<?php echo admin_url('accounting/cheque_management_view/'.$ch['id']); ?>">#<?php echo htmlspecialchars($ch['cheque_number']); ?></a></td>
                      <td><?php echo htmlspecialchars($ch['rel_name']??'-'); ?></td>
                      <td><?php echo htmlspecialchars($ch['cheque_bank_name']??'-'); ?></td>
                      <td><?php echo _d($ch['cheque_date']); ?></td>
                      <td><?php echo !empty($ch['deposit_date'])?_d($ch['deposit_date']):'-'; ?></td>
                      <td><?php echo app_format_money($ch['amount'],$currency->name); ?></td>
                      <td class="text-warning bold"><?php echo is_numeric($days_pending)?$days_pending.' days':'-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php else: ?><p class="text-muted">No aging cheques.</p><?php endif; ?>
              </div>
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
