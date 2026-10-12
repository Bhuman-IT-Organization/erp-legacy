<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop">
              <i class="fa fa-solid fa-hand-holding-dollar"></i>
              <?= e((string) $loan->reference); ?>
              <?php echo loan_status_label($loan->status); ?>
            </h4>

            <table class="table table-borderless">
              <tr>
                <td class="text-muted" style="width:40%;"><?= _l('staff'); ?></td>
                <td><?= e((string) $loan->staff_name); ?></td>
              </tr>
              <tr>
                <td class="text-muted"><?= _l('loan_reason'); ?></td>
                <td><?= nl2br(e((string) $loan->reason)); ?></td>
              </tr>
              <tr>
                <td class="text-muted"><?= _l('loan_requested_amount'); ?></td>
                <td><?= app_format_money($loan->requested_amount, ''); ?></td>
              </tr>
              <tr>
                <td class="text-muted"><?= _l('loan_proposed_instalment'); ?></td>
                <td><?= app_format_money($loan->proposed_instalment, ''); ?></td>
              </tr>
              <tr>
                <td class="text-muted"><?= _l('loan_service_years'); ?></td>
                <td><?= $loan->service_years !== null ? $loan->service_years : '&mdash;'; ?></td>
              </tr>
              <tr>
                <td class="text-muted"><?= _l('loan_outstanding_at_request'); ?></td>
                <td><?= $loan->outstanding_at_request !== null
                    ? app_format_money($loan->outstanding_at_request, '') : '&mdash;'; ?></td>
              </tr>
              <?php if ($loan->approved_amount !== null) { ?>
                <tr class="success">
                  <td class="text-muted"><?= _l('loan_approved_amount'); ?></td>
                  <td><strong><?= app_format_money($loan->approved_amount, ''); ?></strong></td>
                </tr>
                <tr class="success">
                  <td class="text-muted"><?= _l('loan_approved_instalment'); ?></td>
                  <td><strong><?= app_format_money($loan->approved_instalment, ''); ?></strong></td>
                </tr>
              <?php } ?>
            </table>

            <?php /* The schedule, once the loan is approved. */ ?>
            <?php if (count($loan->instalments) > 0) { ?>
              <h5 class="mtop20"><?= _l('loan_repayment_schedule'); ?></h5>
              <div class="table-responsive">
                <table class="table table-condensed">
                  <thead>
                    <tr>
                      <th><?= _l('loan_instalment_no'); ?></th>
                      <th><?= _l('loan_due_date'); ?></th>
                      <th><?= _l('loan_amount'); ?></th>
                      <th><?= _l('status'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($loan->instalments as $inst) { ?>
                      <tr>
                        <td><?= (int) $inst['instalment_no']; ?></td>
                        <td><?= _d($inst['due_date']); ?></td>
                        <td><?= app_format_money($inst['amount'], ''); ?></td>
                        <td>
                          <span class="label label-<?= $inst['status'] === 'deducted' ? 'success' : 'default'; ?>">
                            <?= _l('loan_instalment_status_' . $inst['status']); ?>
                          </span>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>

            <?php /* Draft actions belong to the applicant. */ ?>
            <?php if ($loan->status === 'draft') { ?>
              <hr>
              <a href="<?= admin_url('loans/apply/' . $loan->id); ?>" class="btn btn-default">
                <i class="fa fa-regular fa-pen-to-square"></i> <?= _l('edit'); ?>
              </a>
              <a href="<?= admin_url('loans/submit/' . $loan->id); ?>" class="btn btn-info">
                <?= _l('loan_submit_for_approval'); ?>
              </a>
              <a href="<?= admin_url('loans/delete/' . $loan->id); ?>" class="btn btn-danger _delete">
                <?= _l('delete'); ?>
              </a>
            <?php } elseif ($loan->status === 'pending') { ?>
              <hr>
              <a href="<?= admin_url('loans/cancel/' . $loan->id); ?>" class="btn btn-default _delete">
                <?= _l('loan_cancel_request'); ?>
              </a>
            <?php } ?>
          </div>
        </div>
      </div>

      <?php /* The approval chain and, when it is your turn, the action panel. */ ?>
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-mtop"><?= _l('loan_approval_progress'); ?></h5>

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
                    <br>
                    <small class="text-muted">
                      <?= e((string) $step['approver_name']); ?>
                      <?php if ($step['acted_at']) { ?>
                        &mdash; <?= _dt($step['acted_at']); ?>
                      <?php } elseif ($step['due_date']) { ?>
                        &mdash; <?= _l('loan_due_by'); ?> <?= _d($step['due_date']); ?>
                      <?php } ?>
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
              <?= form_open(admin_url('loans/act/' . $loan->id)); ?>

              <?php /* Only the level allowed to set amounts sees these fields. */ ?>
              <?php if (!empty($current['can_edit_amounts'])) { ?>
                <div class="alert alert-info"><?= _l('loan_you_set_the_amounts'); ?></div>
                <?= render_input('approved_amount', 'loan_approved_amount',
                    $loan->approved_amount ?? $loan->requested_amount, 'number'); ?>
                <?= render_input('approved_instalment', 'loan_approved_instalment',
                    $loan->approved_instalment ?? $loan->proposed_instalment, 'number'); ?>
                <?= render_date_input('approved_first_instalment_date', 'loan_first_instalment_date',
                    isset($loan->first_instalment_date) ? _d($loan->first_instalment_date) : ''); ?>
              <?php } ?>

              <?= render_textarea('note', 'loan_approval_note'); ?>

              <button type="submit" name="decision" value="approved" class="btn btn-success btn-block">
                <i class="fa fa-check"></i> <?= _l('loan_approve'); ?>
              </button>
              <button type="submit" name="decision" value="rejected" class="btn btn-danger btn-block"
                      onclick="return confirm('<?= _l('loan_confirm_reject'); ?>');">
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
