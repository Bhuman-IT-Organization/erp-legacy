<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop">
              <i class="fa fa-solid fa-arrow-trend-up"></i>
              <?= e((string) $request->reference); ?>
              <?php echo increment_status_label($request->status); ?>
            </h4>

            <table class="table table-borderless">
              <tr><td class="text-muted" style="width:40%;"><?= _l('staff'); ?></td>
                <td><?= e((string) $request->staff_name); ?></td></tr>
              <tr><td class="text-muted"><?= _l('increment_designation'); ?></td>
                <td><?= e((string) $request->designation) ?: '&mdash;'; ?></td></tr>
              <tr><td class="text-muted"><?= _l('increment_reason'); ?></td>
                <td><?= nl2br(e((string) $request->reason)); ?></td></tr>
              <tr class="active"><td class="text-muted"><?= _l('increment_amount'); ?></td>
                <td><strong><?= app_format_money($request->increment_amount, ''); ?></strong></td></tr>
              <?php if ($request->approved_amount !== null) { ?>
                <tr class="success"><td class="text-muted"><?= _l('increment_approved_amount'); ?></td>
                  <td><strong><?= app_format_money($request->approved_amount, ''); ?></strong></td></tr>
              <?php } ?>
              <tr><td class="text-muted"><?= _l('increment_effective_from'); ?></td>
                <td><?= $request->effective_from ? _d($request->effective_from) : '&mdash;'; ?></td></tr>
            </table>

            <h5 class="mtop20"><?= _l('increment_automatic_context'); ?></h5>
            <table class="table table-condensed">
              <tr><td class="text-muted"><?= _l('increment_joining_date'); ?></td>
                <td><?= $request->joining_date ? _d($request->joining_date) : '&mdash;'; ?></td>
                <td class="text-muted"><?= _l('increment_service_years'); ?></td>
                <td><?= $request->service_years !== null ? $request->service_years : '&mdash;'; ?></td></tr>
              <tr><td class="text-muted"><?= _l('increment_visa_type'); ?></td>
                <td><?= e((string) $request->visa_type) ?: '&mdash;'; ?></td>
                <td class="text-muted"><?= _l('increment_visa_expiry'); ?></td>
                <td><?= $request->visa_expiry ? _d($request->visa_expiry) : '&mdash;'; ?></td></tr>
              <tr><td class="text-muted"><?= _l('increment_salary_on_joining'); ?></td>
                <td><?= $request->salary_on_joining !== null
                    ? app_format_money($request->salary_on_joining, '') : '&mdash;'; ?></td>
                <td class="text-muted"><?= _l('increment_current_salary'); ?></td>
                <td><?= $request->current_salary !== null
                    ? app_format_money($request->current_salary, '') : '&mdash;'; ?></td></tr>
              <tr><td class="text-muted"><?= _l('increment_last_date'); ?></td>
                <td><?= $request->last_increment_date ? _d($request->last_increment_date) : '&mdash;'; ?></td>
                <td class="text-muted"><?= _l('increment_last_amount'); ?></td>
                <td><?= $request->last_increment_amount !== null
                    ? app_format_money($request->last_increment_amount, '') : '&mdash;'; ?></td></tr>
              <tr><td class="text-muted"><?= _l('increment_evaluation'); ?></td>
                <td colspan="3">
                  <?= $request->evaluation_result
                      ? e($request->evaluation_result) . ' (' . (int) $request->evaluation_year . ')'
                      : '<span class="text-muted">' . _l('increment_no_evaluation') . '</span>'; ?>
                </td></tr>
            </table>

            <?php /* The increment history - "extend to see the full history". */ ?>
            <?php if (count($history) > 0) { ?>
              <h5 class="mtop20">
                <a data-toggle="collapse" href="#inc-history">
                  <?= _l('increment_full_history'); ?> (<?= count($history); ?>)
                </a>
              </h5>
              <div class="collapse" id="inc-history">
                <table class="table table-condensed">
                  <thead>
                    <tr>
                      <th><?= _l('increment_effective_from'); ?></th>
                      <th><?= _l('increment_change_type'); ?></th>
                      <th><?= _l('hr_component'); ?></th>
                      <th><?= _l('increment_old'); ?></th>
                      <th><?= _l('increment_new_val'); ?></th>
                      <th><?= _l('increment_change'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($history as $h) { ?>
                      <tr class="<?= $h['component_type'] === 'total' ? 'active' : ''; ?>">
                        <td><?= _d($h['effective_from']); ?></td>
                        <td><?= _l('increment_type_' . $h['change_type']); ?></td>
                        <td><?= e((string) $h['component_label']); ?></td>
                        <td><?= app_format_money($h['old_amount'], ''); ?></td>
                        <td><?= app_format_money($h['new_amount'], ''); ?></td>
                        <td><strong><?= app_format_money($h['change_amount'], ''); ?></strong></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>

            <?php if ($request->status === 'draft') { ?>
              <hr>
              <a href="<?= admin_url('increments/apply_form/' . $request->id); ?>" class="btn btn-default">
                <i class="fa fa-regular fa-pen-to-square"></i> <?= _l('edit'); ?>
              </a>
              <a href="<?= admin_url('increments/submit/' . $request->id); ?>" class="btn btn-info">
                <?= _l('loan_submit_for_approval'); ?>
              </a>
              <a href="<?= admin_url('increments/delete/' . $request->id); ?>" class="btn btn-danger _delete">
                <?= _l('delete'); ?>
              </a>
            <?php } elseif ($request->status === 'pending') { ?>
              <hr>
              <a href="<?= admin_url('increments/cancel/' . $request->id); ?>" class="btn btn-default _delete">
                <?= _l('loan_cancel_request'); ?>
              </a>
            <?php } elseif ($request->status === 'approved') { ?>
              <div class="alert alert-warning mtop15"><?= _l('increment_approved_not_applied_alert'); ?></div>
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-mtop"><?= _l('increment_approval_progress'); ?></h5>

            <?php if (count($chain) === 0) { ?>
              <p class="text-muted"><?= _l('loan_not_submitted_yet'); ?></p>
            <?php } else { ?>
              <ul class="list-unstyled">
                <?php foreach ($chain as $step) {
                    $icon = 'fa-regular fa-circle text-muted';
                    if ($step['action'] === 'approved') {
                        $icon = 'fa-solid fa-circle-check text-success';
                    } elseif ($step['action'] === 'rejected') {
                        $icon = 'fa-solid fa-circle-xmark text-danger';
                    } ?>
                  <li class="mbot15">
                    <i class="fa <?= $icon; ?>"></i>
                    <strong><?= _l('approval_level'); ?> <?= (int) $step['level_no']; ?></strong>
                    <?= $step['level_name'] ? ' &middot; ' . e($step['level_name']) : ''; ?>
                    <br><small class="text-muted">
                      <?= e((string) $step['approver_name']); ?>
                      <?php if ($step['acted_at']) { ?> &mdash; <?= _dt($step['acted_at']); ?><?php } ?>
                    </small>
                    <?php if (!empty($step['note'])) { ?>
                      <br><small><em><?= e($step['note']); ?></em></small>
                    <?php } ?>
                  </li>
                <?php } ?>
              </ul>
            <?php } ?>

            <?php if ($can_act) { ?>
              <hr>
              <h5><?= _l('loan_your_decision'); ?></h5>
              <?= form_open(admin_url('increments/act/' . $request->id)); ?>

              <?php if (!empty($current['can_edit_amounts'])) { ?>
                <div class="alert alert-info"><?= _l('increment_you_set_amount'); ?></div>
                <?= render_input('approved_amount', 'increment_approved_amount',
                    $request->approved_amount ?? $request->increment_amount, 'number'); ?>
              <?php } ?>

              <?= render_textarea('note', 'loan_approval_note'); ?>

              <button type="submit" name="decision" value="approved" class="btn btn-success btn-block">
                <i class="fa fa-check"></i> <?= _l('loan_approve'); ?>
              </button>
              <button type="submit" name="decision" value="rejected" class="btn btn-danger btn-block"
                      onclick="return confirm('<?= _l('increment_confirm_reject'); ?>');">
                <i class="fa fa-times"></i> <?= _l('loan_reject'); ?>
              </button>

              <?= form_close(); ?>
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
