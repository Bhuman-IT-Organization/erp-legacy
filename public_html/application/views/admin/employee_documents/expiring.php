<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-solid fa-hourglass-half"></i> <?= _l('empdoc_expiring_soon'); ?></h4>
            <p class="text-muted"><?= _l('empdoc_expiring_help'); ?></p>

            <?php if (count($expiring) === 0) { ?>
              <p class="text-muted text-center mtop15"><?= _l('empdoc_nothing_expiring'); ?></p>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-striped dt-table">
                  <thead>
                    <tr>
                      <th><?= _l('staff'); ?></th>
                      <th><?= _l('empdoc_type'); ?></th>
                      <th><?= _l('empdoc_number'); ?></th>
                      <th><?= _l('empdoc_expiry_date'); ?></th>
                      <th><?= _l('empdoc_days_left'); ?></th>
                      <th><?= _l('options'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($expiring as $doc) {
                        $days = (int) $doc['days_left'];
                        $cls  = $days < 0 ? 'danger' : ($days <= 30 ? 'warning' : 'default'); ?>
                      <tr>
                        <td><?= e((string) $doc['staff_name']); ?></td>
                        <td><?= e((string) $doc['type_name']); ?></td>
                        <td><?= e((string) $doc['document_number']) ?: '&mdash;'; ?></td>
                        <td><?= _d($doc['expiry_date']); ?></td>
                        <td>
                          <span class="label label-<?= $cls; ?>">
                            <?= $days < 0
                                ? _l('empdoc_expired_ago', [abs($days)])
                                : _l('empdoc_in_days', [$days]); ?>
                          </span>
                        </td>
                        <td>
                          <a href="<?= admin_url('employee_documents/mine?staff_id=' . $doc['staff_id']); ?>"
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
