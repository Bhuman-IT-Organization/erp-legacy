<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <a href="<?= admin_url('increments/apply_form'); ?>" class="btn btn-info pull-left">
              <i class="fa fa-plus"></i> <?= _l('increment_new'); ?>
            </a>
            <div class="clearfix"></div>

            <h4 class="no-mtop mtop15">
              <i class="fa fa-solid fa-arrow-trend-up"></i> <?= _l('increment_requests'); ?>
            </h4>

            <?php if (count($requests) === 0) { ?>
              <p class="text-muted text-center mtop15"><?= _l('increment_none_yet'); ?></p>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-striped dt-table">
                  <thead>
                    <tr>
                      <th><?= _l('loan_reference'); ?></th>
                      <th><?= _l('staff'); ?></th>
                      <th><?= _l('increment_amount'); ?></th>
                      <th><?= _l('increment_approved_amount'); ?></th>
                      <th><?= _l('increment_effective_from'); ?></th>
                      <th><?= _l('status'); ?></th>
                      <th><?= _l('options'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($requests as $r) { ?>
                      <tr>
                        <td><?= e((string) $r['reference']); ?></td>
                        <td><?= e((string) $r['staff_name']); ?></td>
                        <td><?= app_format_money($r['increment_amount'], ''); ?></td>
                        <td><?= $r['approved_amount'] !== null
                            ? app_format_money($r['approved_amount'], '') : '&mdash;'; ?></td>
                        <td><?= $r['effective_from'] ? _d($r['effective_from']) : '&mdash;'; ?></td>
                        <td><?php echo increment_status_label($r['status']); ?></td>
                        <td>
                          <a href="<?= admin_url('increments/view/' . $r['id']); ?>"
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
