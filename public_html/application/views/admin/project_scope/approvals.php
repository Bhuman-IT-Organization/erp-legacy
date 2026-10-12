<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-solid fa-clipboard-check"></i> <?= _l('pscope_approvals'); ?></h4>
            <p class="text-muted"><?= _l('pscope_approvals_help'); ?></p>

            <?php if (count($pending) === 0) { ?>
              <p class="text-muted text-center mtop15"><?= _l('pscope_nothing_pending'); ?></p>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th><?= _l('project'); ?></th>
                      <th><?= _l('pscope_activity'); ?></th>
                      <th><?= _l('pscope_from_to'); ?></th>
                      <th><?= _l('pscope_by'); ?></th>
                      <th><?= _l('note'); ?></th>
                      <th><?= _l('options'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pending as $p) { ?>
                      <tr>
                        <td>
                          <a href="<?= admin_url('projects/view/' . (int) $p['project_id'] . '?group=project_scope'); ?>">
                            #<?= (int) $p['project_id']; ?>
                          </a>
                        </td>
                        <td><?= e((string) $p['activity_name']); ?></td>
                        <td><?= (float) $p['previous_percent']; ?>% &rarr;
                          <strong><?= (float) $p['progress_percent']; ?>%</strong></td>
                        <td><?= e((string) $p['updated_by_name']); ?></td>
                        <td><?= e((string) $p['note']); ?></td>
                        <td>
                          <?= form_open(admin_url('project_scope/review_progress'), ['class' => 'form-inline']); ?>
                            <input type="hidden" name="progress_id" value="<?= (int) $p['id']; ?>">
                            <input type="hidden" name="redirect_to" value="queue">
                            <input type="text" name="reason" class="form-control input-sm"
                                   placeholder="<?= _l('pscope_reject_reason'); ?>" style="width:120px;">
                            <button type="submit" name="decision" value="approve"
                                    class="btn btn-success btn-sm"><?= _l('pscope_approve'); ?></button>
                            <button type="submit" name="decision" value="reject"
                                    class="btn btn-danger btn-sm"><?= _l('pscope_reject'); ?></button>
                          <?= form_close(); ?>
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
