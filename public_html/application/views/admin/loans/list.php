<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <div class="_buttons">
              <a href="<?= admin_url('loans/apply'); ?>" class="btn btn-info pull-left">
                <i class="fa fa-plus"></i> <?= _l('loan_new_application'); ?>
              </a>
            </div>
            <div class="clearfix"></div>

            <h4 class="no-mtop mtop15">
              <i class="fa fa-solid fa-hand-holding-dollar"></i> <?= _l('loan_applications'); ?>
              <?php if ($scope === 'own') { ?>
                <small class="text-muted">&mdash; <?= _l('loan_your_applications'); ?></small>
              <?php } ?>
            </h4>

            <?php if (count($loans) === 0) { ?>
              <p class="text-muted text-center mtop15"><?= _l('loan_none_yet'); ?></p>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-striped dt-table">
                  <thead>
                    <tr>
                      <th><?= _l('loan_reference'); ?></th>
                      <?php if ($scope === 'all') { ?><th><?= _l('staff'); ?></th><?php } ?>
                      <th><?= _l('loan_requested_amount'); ?></th>
                      <th><?= _l('loan_approved_amount'); ?></th>
                      <th><?= _l('loan_outstanding'); ?></th>
                      <th><?= _l('status'); ?></th>
                      <th><?= _l('options'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $this->load->model('loan_model');
                    foreach ($loans as $loan) {
                        $outstanding = in_array($loan['status'], ['approved', 'disbursed', 'closed'], true)
                            ? $this->loan_model->outstanding_balance($loan['staff_id'], null)
                            : null;
                    ?>
                      <tr>
                        <td><?= e((string) $loan['reference']); ?></td>
                        <?php if ($scope === 'all') { ?>
                          <td><?= e((string) $loan['staff_name']); ?></td>
                        <?php } ?>
                        <td><?= app_format_money($loan['requested_amount'], ''); ?></td>
                        <td><?= $loan['approved_amount'] !== null
                            ? app_format_money($loan['approved_amount'], '') : '&mdash;'; ?></td>
                        <td>
                          <?php
                          // Outstanding is per employee, so only show it on the row for
                          // clarity when viewing a single person's loans.
                          echo $outstanding !== null ? app_format_money($outstanding, '') : '&mdash;';
                          ?>
                        </td>
                        <td><?php echo loan_status_label($loan['status']); ?></td>
                        <td>
                          <a href="<?= admin_url('loans/view/' . $loan['id']); ?>"
                             class="btn btn-default btn-icon"><i class="fa fa-eye"></i></a>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
</body>
</html>
