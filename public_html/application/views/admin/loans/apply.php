<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop">
              <i class="fa fa-solid fa-hand-holding-dollar"></i> <?= $title; ?>
            </h4>

            <?= form_open(admin_url('loans/apply' . ($loan ? '/' . $loan->id : '')), ['id' => 'loan-form']); ?>

            <?php if ($can_view_all && !$loan) { ?>
              <div class="form-group select-placeholder">
                <label for="staff_id" class="control-label"><?= _l('loan_employee'); ?></label>
                <select name="staff_id" id="loan-staff" class="selectpicker" data-width="100%"
                        data-live-search="true"
                        data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                  <?php foreach ($staff as $s) { ?>
                    <option value="<?= (int) $s['staffid']; ?>"
                      <?= (int) $s['staffid'] === (int) $for_staff_id ? 'selected' : ''; ?>>
                      <?= e($s['firstname'] . ' ' . $s['lastname']); ?>
                    </option>
                  <?php } ?>
                </select>
                <p class="text-muted"><?= _l('loan_on_behalf_help'); ?></p>
              </div>
            <?php } ?>

            <?php /* The automatic context. Read only - the employee does not type these. */ ?>
            <div class="row bg-light mbot15" style="padding:12px;border-radius:4px;">
              <div class="col-md-4">
                <label class="control-label text-muted"><?= _l('loan_service_years'); ?></label>
                <h5 class="no-mtop" id="ctx-service"><?= $service_years !== null ? $service_years : '&mdash;'; ?></h5>
              </div>
              <div class="col-md-4">
                <label class="control-label text-muted"><?= _l('loan_outstanding'); ?></label>
                <h5 class="no-mtop" id="ctx-outstanding"><?= app_format_money($outstanding_balance, ''); ?></h5>
              </div>
              <div class="col-md-4">
                <label class="control-label text-muted"><?= _l('loan_current_salary'); ?></label>
                <h5 class="no-mtop" id="ctx-salary">
                  <?= $current_salary !== null ? app_format_money($current_salary, '') : _l('loan_no_salary_on_file'); ?>
                </h5>
              </div>
            </div>

            <?php if ($policy) { ?>
              <div class="alert alert-info">
                <?= _l('loan_policy_summary'); ?>:
                <?php
                $bits = [];
                if ($policy->max_amount !== null) {
                    $bits[] = _l('loan_max') . ' ' . app_format_money($policy->max_amount, '');
                }
                if ($policy->max_instalments !== null) {
                    $bits[] = (int) $policy->max_instalments . ' ' . _l('loan_instalments_max');
                }
                if ((int) $policy->min_service_months > 0) {
                    $bits[] = (int) $policy->min_service_months . ' ' . _l('loan_months_min_service');
                }
                echo $bits ? implode(' &middot; ', $bits) : _l('loan_no_limits_set');
                ?>
              </div>
            <?php } else { ?>
              <div class="alert alert-warning"><?= _l('loan_no_active_policy_notice'); ?></div>
            <?php } ?>

            <?= render_textarea('reason', 'loan_reason', $loan->reason ?? ''); ?>

            <div class="row">
              <div class="col-md-6">
                <?= render_input('requested_amount', 'loan_requested_amount',
                    $loan->requested_amount ?? '', 'number'); ?>
              </div>
              <div class="col-md-6">
                <?= render_input('proposed_instalment', 'loan_proposed_instalment',
                    $loan->proposed_instalment ?? '', 'number'); ?>
              </div>
            </div>

            <?= render_date_input('first_instalment_date', 'loan_first_instalment_date',
                isset($loan->first_instalment_date) ? _d($loan->first_instalment_date) : ''); ?>

            <?php /* The schedule preview, filled by JS from the three inputs above. */ ?>
            <div id="schedule-preview" class="mtop15" style="display:none;">
              <h5><?= _l('loan_repayment_schedule'); ?>
                <small class="text-muted" id="schedule-summary"></small>
              </h5>
              <div class="table-responsive">
                <table class="table table-condensed">
                  <thead>
                    <tr>
                      <th><?= _l('loan_instalment_no'); ?></th>
                      <th><?= _l('loan_due_date'); ?></th>
                      <th><?= _l('loan_amount'); ?></th>
                    </tr>
                  </thead>
                  <tbody id="schedule-body"></tbody>
                </table>
              </div>
            </div>

            <hr>
            <div class="text-right">
              <a href="<?= admin_url('loans'); ?>" class="btn btn-default"><?= _l('cancel'); ?></a>
              <button type="submit" name="save_draft" value="1" class="btn btn-default">
                <?= _l('loan_save_draft'); ?>
              </button>
              <button type="submit" name="submit_for_approval" value="1" class="btn btn-info">
                <?= _l('loan_submit_for_approval'); ?>
              </button>
            </div>

            <?= form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
  $(function () {
    var previewUrl = '<?= admin_url('loans/preview_schedule'); ?>';
    var contextUrl = '<?= admin_url('loans/staff_context'); ?>';
    var csrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash(); ?>';

    // Redraw the schedule whenever the numbers change, so the employee sees the full
    // plan and the last instalment date before submitting.
    function refreshSchedule() {
      var amount = parseFloat($('input[name="requested_amount"]').val());
      var instalment = parseFloat($('input[name="proposed_instalment"]').val());
      var firstDate = $('input[name="first_instalment_date"]').val();

      if (!amount || !instalment || amount <= 0 || instalment <= 0) {
        $('#schedule-preview').hide();
        return;
      }

      var payload = {amount: amount, instalment: instalment, first_date: firstDate};
      payload[csrfName] = csrfHash;

      $.post(previewUrl, payload, function (res) {
        csrfHash = res.csrf_hash || csrfHash;
        if (!res.rows || res.rows.length === 0) {
          $('#schedule-preview').hide();
          return;
        }
        var body = '';
        $.each(res.rows, function (i, row) {
          body += '<tr><td>' + row.instalment_no + '</td><td>' + row.due_date
                + '</td><td>' + row.amount + '</td></tr>';
        });
        $('#schedule-body').html(body);
        $('#schedule-summary').text(
          res.count + ' <?= _l('loan_instalments'); ?>, <?= _l('loan_last_on'); ?> ' + res.last_date
        );
        $('#schedule-preview').show();
      }, 'json');
    }

    var t;
    $('input[name="requested_amount"], input[name="proposed_instalment"], input[name="first_instalment_date"]')
      .on('keyup change', function () {
        clearTimeout(t);
        t = setTimeout(refreshSchedule, 400);
      });

    // When a manager picks a different employee, refresh the read-only context.
    $('#loan-staff').on('changed.bs.select', function () {
      var payload = {staff_id: $(this).val()};
      payload[csrfName] = csrfHash;
      $.post(contextUrl, payload, function (res) {
        csrfHash = res.csrf_hash || csrfHash;
        $('#ctx-service').text(res.service_years === null ? '\u2014' : res.service_years);
        $('#ctx-outstanding').text(res.outstanding_balance);
        $('#ctx-salary').text(res.current_salary === null ? '<?= _l('loan_no_salary_on_file'); ?>' : res.current_salary);
      }, 'json');
    });

    refreshSchedule();
  });
</script>
</body>
</html>
