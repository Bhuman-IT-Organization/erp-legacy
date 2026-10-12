<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-solid fa-arrow-trend-up"></i> <?= $title; ?></h4>

            <?= form_open(admin_url('increments/apply_form' . ($request ? '/' . $request->id : ''))); ?>

            <?php if (!$request) { ?>
              <div class="form-group select-placeholder">
                <label for="staff_id" class="control-label"><?= _l('loan_employee'); ?></label>
                <select name="staff_id" id="inc-staff" class="selectpicker" data-width="100%"
                        data-live-search="true"
                        data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                  <option value=""></option>
                  <?php foreach ($staff as $s) { ?>
                    <option value="<?= (int) $s['staffid']; ?>"
                      <?= (int) $s['staffid'] === (int) $for_staff_id ? 'selected' : ''; ?>>
                      <?= e($s['firstname'] . ' ' . $s['lastname']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
            <?php } ?>

            <?php /* The automatic context, read only. */ ?>
            <div id="auto-context" class="row bg-light mbot15"
                 style="padding:12px;border-radius:4px;<?= $auto ? '' : 'display:none;'; ?>">
              <?php
              $fields = [
                  'designation'           => _l('increment_designation'),
                  'joining_date'          => _l('increment_joining_date'),
                  'service_years'         => _l('increment_service_years'),
                  'visa_type'             => _l('increment_visa_type'),
                  'visa_expiry'           => _l('increment_visa_expiry'),
                  'salary_on_joining'     => _l('increment_salary_on_joining'),
                  'current_salary'        => _l('increment_current_salary'),
                  'last_increment_date'   => _l('increment_last_date'),
                  'last_increment_amount' => _l('increment_last_amount'),
              ];
              foreach ($fields as $key => $label) {
                  $val = $auto[$key] ?? null;
                  if (in_array($key, ['salary_on_joining', 'current_salary', 'last_increment_amount'], true)
                      && $val !== null) {
                      $val = app_format_money($val, '');
                  }
                  if (in_array($key, ['joining_date', 'visa_expiry', 'last_increment_date'], true) && $val) {
                      $val = _d($val);
                  }
              ?>
                <div class="col-md-4 mbot15">
                  <label class="control-label text-muted"><?= $label; ?></label>
                  <div id="ctx-<?= $key; ?>"><?= $val !== null && $val !== '' ? e((string) $val) : '&mdash;'; ?></div>
                </div>
              <?php } ?>
            </div>

            <?php /* Whether the split is ready, so nobody submits an increment that cannot apply. */ ?>
            <div id="split-warning" class="alert alert-warning"
                 style="<?= ($split && $split['ok']) ? 'display:none;' : ($auto ? '' : 'display:none;'); ?>">
              <?= _l('increment_split_not_ready'); ?>
            </div>

            <?= render_textarea('reason', 'increment_reason', $request->reason ?? ''); ?>
            <?= render_input('increment_amount', 'increment_amount',
                $request->increment_amount ?? '', 'number'); ?>
            <?= render_date_input('effective_from', 'increment_effective_from',
                isset($request->effective_from) ? _d($request->effective_from) : ''); ?>

            <hr>
            <div class="text-right">
              <a href="<?= admin_url('increments'); ?>" class="btn btn-default"><?= _l('cancel'); ?></a>
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
    var url = '<?= admin_url('increments/employee_data'); ?>';
    var csrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash(); ?>';

    // When HR picks an employee, pull the automatic context so they see current
    // salary, seniority, visa and last increment before deciding the amount.
    $('#inc-staff').on('changed.bs.select', function () {
      var staffId = $(this).val();
      if (!staffId) { return; }

      var payload = {staff_id: staffId};
      payload[csrfName] = csrfHash;

      $.post(url, payload, function (res) {
        csrfHash = res.csrf_hash || csrfHash;
        var money = function (v) { return v === null ? '\u2014' : v; };
        $('#ctx-designation').text(res.designation || '\u2014');
        $('#ctx-joining_date').text(res.joining_date || '\u2014');
        $('#ctx-service_years').text(res.service_years === null ? '\u2014' : res.service_years);
        $('#ctx-visa_type').text(res.visa_type || '\u2014');
        $('#ctx-visa_expiry').text(res.visa_expiry || '\u2014');
        $('#ctx-salary_on_joining').text(money(res.salary_on_joining));
        $('#ctx-current_salary').text(money(res.current_salary));
        $('#ctx-last_increment_date').text(res.last_increment_date || '\u2014');
        $('#ctx-last_increment_amount').text(money(res.last_increment_amount));
        $('#auto-context').show();
        $('#split-warning').toggle(!res.split_ok);
      }, 'json');
    });
  });
</script>
</body>
</html>
