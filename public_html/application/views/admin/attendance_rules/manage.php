<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-sliders"></i> <?= _l('attendance_rules'); ?></h4>
            <p class="text-muted"><?= _l('attendance_rules_help'); ?></p>

            <ul class="nav nav-tabs mtop15" role="tablist">
              <?php
              $tabs = [
                  'overtime'          => _l('attendance_overtime_rules'),
                  'comp_off'          => _l('attendance_comp_off'),
                  'leave_entitlement' => _l('attendance_leave_entitlement'),
              ];
              foreach ($tabs as $slug => $label) { ?>
                <li role="presentation" class="<?= $group === $slug ? 'active' : ''; ?>">
                  <a href="<?= admin_url('attendance_rules?group=' . $slug); ?>"><?= $label; ?></a>
                </li>
              <?php } ?>
            </ul>

            <div class="tab-content mtop15">

              <?php /* ------------------------------------------------ OVERTIME */ ?>
              <?php if ($group === 'overtime') { ?>
                <div class="alert alert-info">
                  <?= _l('attendance_overtime_help'); ?>
                </div>

                <button type="button" class="btn btn-info mbot15" data-toggle="modal" data-target="#ot-modal"
                        onclick="otReset();">
                  <i class="fa fa-plus"></i> <?= _l('attendance_add_rule'); ?>
                </button>

                <?php if (count($rules) === 0) { ?>
                  <p class="text-muted text-center mtop15"><?= _l('attendance_no_overtime_rules'); ?></p>
                <?php } else { ?>
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th><?= _l('attendance_priority'); ?></th>
                          <th><?= _l('name'); ?></th>
                          <th><?= _l('attendance_day_type'); ?></th>
                          <th><?= _l('attendance_time_window'); ?></th>
                          <th><?= _l('coefficient'); ?></th>
                          <th><?= _l('attendance_min_minutes'); ?></th>
                          <th><?= _l('attendance_effective'); ?></th>
                          <th><?= _l('status'); ?></th>
                          <th><?= _l('options'); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($rules as $rule) { ?>
                          <tr>
                            <td><?= (int) $rule['priority']; ?></td>
                            <td><?= e($rule['name']); ?></td>
                            <td><?= _l('attendance_day_' . $rule['day_type']); ?></td>
                            <td>
                              <?php if ($rule['from_time'] && $rule['to_time']) {
                                  echo e(substr($rule['from_time'], 0, 5)) . ' &ndash; ' . e(substr($rule['to_time'], 0, 5));
                                  // from > to is a window that runs past midnight.
                                  if ($rule['from_time'] > $rule['to_time']) { ?>
                                    <span class="label label-default"><?= _l('attendance_overnight'); ?></span>
                                  <?php }
                              } else {
                                  echo '<span class="text-muted">' . _l('attendance_any_time') . '</span>';
                              } ?>
                            </td>
                            <td><strong><?= e($rule['coefficient']); ?>&times;</strong></td>
                            <td><?= (int) $rule['min_minutes']; ?></td>
                            <td>
                              <?= $rule['effective_from'] ? _d($rule['effective_from']) : '-'; ?>
                              &rarr;
                              <?= $rule['effective_to'] ? _d($rule['effective_to']) : '&infin;'; ?>
                            </td>
                            <td>
                              <?php if ((int) $rule['active'] === 1) { ?>
                                <span class="label label-success"><?= _l('active'); ?></span>
                              <?php } else { ?>
                                <span class="label label-default"><?= _l('inactive'); ?></span>
                              <?php } ?>
                            </td>
                            <td>
                              <button type="button" class="btn btn-default btn-icon"
                                      data-toggle="modal" data-target="#ot-modal"
                                      onclick='otEdit(<?= json_encode($rule); ?>);'>
                                <i class="fa fa-pencil"></i>
                              </button>
                              <a href="<?= admin_url('attendance_rules/delete_overtime/' . (int) $rule['id']); ?>"
                                 class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                            </td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                <?php } ?>
              <?php } ?>

              <?php /* ------------------------------------------------ COMP-OFF */ ?>
              <?php if ($group === 'comp_off') { ?>
                <div class="alert alert-info"><?= _l('attendance_comp_off_definition'); ?></div>

                <?php if ($setting && (int) $setting->active !== 1) { ?>
                  <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> <?= _l('attendance_comp_off_inactive_warning'); ?>
                  </div>
                <?php } ?>

                <?= form_open(admin_url('attendance_rules/save_comp_off')); ?>
                  <div class="row">
                    <div class="col-md-4">
                      <?= render_input('accrual_ratio', 'attendance_accrual_ratio',
                          $setting ? $setting->accrual_ratio : 1, 'number', ['step' => '0.001', 'min' => '0.001']); ?>
                      <p class="text-muted"><small><?= _l('attendance_accrual_ratio_help'); ?></small></p>
                    </div>
                    <div class="col-md-4">
                      <?= render_input('expiry_days', 'attendance_expiry_days',
                          $setting ? $setting->expiry_days : 0, 'number', ['min' => '0']); ?>
                      <p class="text-muted"><small><?= _l('attendance_expiry_days_help'); ?></small></p>
                    </div>
                    <div class="col-md-4">
                      <?= render_input('min_minutes_to_accrue', 'attendance_min_minutes_to_accrue',
                          $setting ? $setting->min_minutes_to_accrue : 60, 'number', ['min' => '0']); ?>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <?php $mode = $setting ? $setting->election_mode : 'employee'; ?>
                      <div class="select-placeholder form-group">
                        <label for="election_mode"><?= _l('attendance_election_mode'); ?></label>
                        <select name="election_mode" id="election_mode" class="form-control">
                          <option value="employee" <?= $mode === 'employee' ? 'selected' : ''; ?>>
                            <?= _l('attendance_election_employee'); ?>
                          </option>
                          <option value="employer" <?= $mode === 'employer' ? 'selected' : ''; ?>>
                            <?= _l('attendance_election_employer'); ?>
                          </option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <hr />
                  <p class="bold"><?= _l('attendance_earn_on'); ?></p>
                  <?php
                  $flags = [
                      'earn_on_working_day'    => 'attendance_earn_working_day',
                      'earn_on_weekly_off'     => 'attendance_earn_weekly_off',
                      'earn_on_public_holiday' => 'attendance_earn_public_holiday',
                  ];
                  foreach ($flags as $field => $label) {
                      $checked = $setting ? (int) $setting->{$field} === 1 : ($field !== 'earn_on_working_day');
                      ?>
                    <div class="checkbox checkbox-primary">
                      <input type="checkbox" name="<?= $field; ?>" id="<?= $field; ?>" value="1" <?= $checked ? 'checked' : ''; ?>>
                      <label for="<?= $field; ?>"><?= _l($label); ?></label>
                    </div>
                  <?php } ?>

                  <hr />
                  <div class="checkbox checkbox-warning">
                    <?php $excl = $setting ? (int) $setting->exclude_from_overtime === 1 : true; ?>
                    <input type="checkbox" name="exclude_from_overtime" id="exclude_from_overtime" value="1"
                           <?= $excl ? 'checked' : ''; ?>>
                    <label for="exclude_from_overtime"><?= _l('attendance_exclude_from_overtime'); ?></label>
                  </div>
                  <p class="text-muted"><small><?= _l('attendance_exclude_from_overtime_help'); ?></small></p>

                  <hr />
                  <div class="checkbox checkbox-success">
                    <?php $act = $setting ? (int) $setting->active === 1 : false; ?>
                    <input type="checkbox" name="active" id="comp_active" value="1" <?= $act ? 'checked' : ''; ?>>
                    <label for="comp_active"><?= _l('attendance_comp_off_enable'); ?></label>
                  </div>

                  <?= render_textarea('note', 'note', $setting ? $setting->note : ''); ?>

                  <button type="submit" class="btn btn-info"><?= _l('submit'); ?></button>
                <?= form_close(); ?>
              <?php } ?>

              <?php /* --------------------------------------- LEAVE ENTITLEMENT */ ?>
              <?php if ($group === 'leave_entitlement') { ?>
                <div class="alert alert-info"><?= _l('attendance_entitlement_help'); ?></div>

                <?= form_open(admin_url('attendance_rules'), ['method' => 'get', 'class' => 'form-inline mbot15']); ?>
                  <input type="hidden" name="group" value="leave_entitlement">
                  <div class="form-group">
                    <label for="year" class="mright5"><?= _l('attendance_period_year'); ?></label>
                    <input type="number" name="year" id="year" class="form-control" value="<?= (int) $year; ?>">
                  </div>
                  <button type="submit" class="btn btn-default mleft10"><?= _l('submit'); ?></button>
                <?= form_close(); ?>

                <button type="button" class="btn btn-info mbot15" data-toggle="modal" data-target="#ent-modal"
                        onclick="entReset(<?= (int) $year; ?>);">
                  <i class="fa fa-plus"></i> <?= _l('attendance_add_entitlement'); ?>
                </button>

                <?php if (count($entitlements) === 0) { ?>
                  <p class="text-muted text-center mtop15"><?= _l('attendance_no_entitlements'); ?></p>
                <?php } else { ?>
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th><?= _l('staff'); ?></th>
                          <th><?= _l('attendance_leave_type'); ?></th>
                          <th><?= _l('attendance_cut_off_date'); ?></th>
                          <th><?= _l('attendance_opening_balance'); ?></th>
                          <th><?= _l('attendance_entitlement_days'); ?></th>
                          <th><?= _l('attendance_carried_forward'); ?></th>
                          <th><?= _l('attendance_total_available'); ?></th>
                          <th><?= _l('options'); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $types_by_id = [];
                        foreach ($leave_types as $t) { $types_by_id[(int) $t['id']] = $t['type_name']; }

                        foreach ($entitlements as $ent) {
                            $total = (float) $ent['opening_balance']
                                + (float) $ent['entitlement_days']
                                + (float) $ent['carried_forward'];
                            ?>
                          <tr>
                            <td><?= e($ent['staff_name']); ?></td>
                            <td>
                              <?= $ent['leave_type_id'] && isset($types_by_id[(int) $ent['leave_type_id']])
                                  ? e($types_by_id[(int) $ent['leave_type_id']])
                                  : '<span class="text-muted">' . _l('attendance_all_types') . '</span>'; ?>
                            </td>
                            <td><?= _d($ent['effective_from']); ?></td>
                            <td><?= e($ent['opening_balance']); ?></td>
                            <td><?= e($ent['entitlement_days']); ?></td>
                            <td><?= e($ent['carried_forward']); ?></td>
                            <td><strong><?= round($total, 2); ?></strong></td>
                            <td>
                              <button type="button" class="btn btn-default btn-icon"
                                      data-toggle="modal" data-target="#ent-modal"
                                      onclick='entEdit(<?= json_encode($ent); ?>);'>
                                <i class="fa fa-pencil"></i>
                              </button>
                              <a href="<?= admin_url('attendance_rules/delete_entitlement/' . (int) $ent['id']); ?>"
                                 class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                            </td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                <?php } ?>
              <?php } ?>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php /* ------------------------------------------------- overtime rule modal */ ?>
<?php if ($group === 'overtime') { ?>
<div class="modal fade" id="ot-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <?= form_open(admin_url('attendance_rules/save_overtime'), ['id' => 'ot-form']); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title"><?= _l('attendance_overtime_rule'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" value="">
        <div class="row">
          <div class="col-md-8"><?= render_input('name', 'name', '', 'text'); ?></div>
          <div class="col-md-4">
            <?= render_input('coefficient', 'coefficient', 1, 'number', ['step' => '0.001', 'min' => '0.001']); ?>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="select-placeholder form-group">
              <label for="day_type"><?= _l('attendance_day_type'); ?></label>
              <select name="day_type" id="day_type" class="form-control">
                <option value="any"><?= _l('attendance_day_any'); ?></option>
                <option value="working_day"><?= _l('attendance_day_working_day'); ?></option>
                <option value="weekly_off"><?= _l('attendance_day_weekly_off'); ?></option>
                <option value="public_holiday"><?= _l('attendance_day_public_holiday'); ?></option>
              </select>
            </div>
          </div>
          <div class="col-md-4"><?= render_input('from_time', 'attendance_from_time', '', 'time'); ?></div>
          <div class="col-md-4"><?= render_input('to_time', 'attendance_to_time', '', 'time'); ?></div>
        </div>
        <p class="text-muted"><small><?= _l('attendance_time_window_help'); ?></small></p>
        <div class="row">
          <div class="col-md-3"><?= render_input('min_minutes', 'attendance_min_minutes', 0, 'number'); ?></div>
          <div class="col-md-3"><?= render_input('max_hours_per_day', 'attendance_max_hours_per_day', 0, 'number', ['step' => '0.01']); ?></div>
          <div class="col-md-3"><?= render_input('priority', 'attendance_priority', 10, 'number'); ?></div>
          <div class="col-md-3">
            <div class="checkbox checkbox-success mtop25">
              <input type="checkbox" name="active" id="ot_active" value="1" checked>
              <label for="ot_active"><?= _l('active'); ?></label>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6"><?= render_date_input('effective_from', 'attendance_effective_from'); ?></div>
          <div class="col-md-6"><?= render_date_input('effective_to', 'attendance_effective_to'); ?></div>
        </div>
        <?= render_select('departments[]', $departments, ['departmentid', 'name'],
            'attendance_limit_departments', '', ['multiple' => true, 'data-actions-box' => true], [], '', '', false); ?>
        <p class="text-muted"><small><?= _l('attendance_scope_help'); ?></small></p>
        <?= render_textarea('note', 'note'); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
        <button type="submit" class="btn btn-info"><?= _l('submit'); ?></button>
      </div>
    </div>
    <?= form_close(); ?>
  </div>
</div>
<?php } ?>

<?php /* -------------------------------------------------- entitlement modal */ ?>
<?php if ($group === 'leave_entitlement') { ?>
<div class="modal fade" id="ent-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('attendance_rules/save_entitlement'), ['id' => 'ent-form']); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title"><?= _l('attendance_leave_entitlement'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" value="">
        <div class="select-placeholder form-group">
          <label for="staff_id"><?= _l('staff'); ?></label>
          <select name="staff_id" id="staff_id" class="selectpicker" data-width="100%" data-live-search="true" required>
            <option value=""></option>
            <?php foreach ($staff as $member) { ?>
              <option value="<?= (int) $member['staffid']; ?>">
                <?= e($member['firstname'] . ' ' . $member['lastname']); ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div class="select-placeholder form-group">
          <label for="leave_type_id"><?= _l('attendance_leave_type'); ?></label>
          <select name="leave_type_id" id="leave_type_id" class="form-control">
            <option value=""><?= _l('attendance_all_types'); ?></option>
            <?php foreach ($leave_types as $type) { ?>
              <option value="<?= (int) $type['id']; ?>"><?= e($type['type_name']); ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="row">
          <div class="col-md-6"><?= render_input('period_year', 'attendance_period_year', date('Y'), 'number'); ?></div>
          <div class="col-md-6"><?= render_date_input('effective_from', 'attendance_cut_off_date'); ?></div>
        </div>
        <p class="text-muted"><small><?= _l('attendance_cut_off_help'); ?></small></p>
        <div class="row">
          <div class="col-md-4"><?= render_input('opening_balance', 'attendance_opening_balance', 0, 'number', ['step' => '0.01']); ?></div>
          <div class="col-md-4"><?= render_input('entitlement_days', 'attendance_entitlement_days', 0, 'number', ['step' => '0.01']); ?></div>
          <div class="col-md-4"><?= render_input('carried_forward', 'attendance_carried_forward', 0, 'number', ['step' => '0.01']); ?></div>
        </div>
        <?= render_date_input('carry_forward_expires_on', 'attendance_carry_forward_expires'); ?>
        <?= render_textarea('note', 'note'); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
        <button type="submit" class="btn btn-info"><?= _l('submit'); ?></button>
      </div>
    </div>
    <?= form_close(); ?>
  </div>
</div>
<?php } ?>

<?php init_tail(); ?>
<script>
function otReset() {
    "use strict";
    var $f = $('#ot-form');
    $f.find('[name="id"]').val('');
    $f.find('[name="name"], [name="from_time"], [name="to_time"], [name="note"]').val('');
    $f.find('[name="coefficient"]').val(1);
    $f.find('[name="min_minutes"], [name="max_hours_per_day"]').val(0);
    $f.find('[name="priority"]').val(10);
    $f.find('[name="day_type"]').val('any');
    $f.find('[name="effective_from"], [name="effective_to"]').val('');
    $f.find('[name="active"]').prop('checked', true);
    $f.find('[name="departments[]"]').val([]).selectpicker && $f.find('[name="departments[]"]').selectpicker('refresh');
}

function otEdit(rule) {
    "use strict";
    var $f = $('#ot-form');
    $f.find('[name="id"]').val(rule.id);
    $f.find('[name="name"]').val(rule.name);
    $f.find('[name="coefficient"]').val(rule.coefficient);
    $f.find('[name="day_type"]').val(rule.day_type);
    // Times come back as HH:MM:SS; the time input wants HH:MM.
    $f.find('[name="from_time"]').val(rule.from_time ? rule.from_time.substring(0, 5) : '');
    $f.find('[name="to_time"]').val(rule.to_time ? rule.to_time.substring(0, 5) : '');
    $f.find('[name="min_minutes"]').val(rule.min_minutes);
    $f.find('[name="max_hours_per_day"]').val(rule.max_hours_per_day);
    $f.find('[name="priority"]').val(rule.priority);
    $f.find('[name="note"]').val(rule.note || '');
    $f.find('[name="active"]').prop('checked', String(rule.active) === '1');

    var depts = rule.departments ? String(rule.departments).split(',') : [];
    var $d = $f.find('[name="departments[]"]');
    $d.val(depts);
    if ($d.selectpicker) { $d.selectpicker('refresh'); }
}

function entReset(year) {
    "use strict";
    var $f = $('#ent-form');
    $f.find('[name="id"]').val('');
    $f.find('[name="staff_id"]').val('');
    $f.find('[name="leave_type_id"]').val('');
    $f.find('[name="period_year"]').val(year);
    $f.find('[name="effective_from"], [name="carry_forward_expires_on"], [name="note"]').val('');
    $f.find('[name="opening_balance"], [name="entitlement_days"], [name="carried_forward"]').val(0);
    if ($f.find('[name="staff_id"]').selectpicker) { $f.find('[name="staff_id"]').selectpicker('refresh'); }
}

function entEdit(ent) {
    "use strict";
    var $f = $('#ent-form');
    $f.find('[name="id"]').val(ent.id);
    $f.find('[name="staff_id"]').val(ent.staff_id);
    $f.find('[name="leave_type_id"]').val(ent.leave_type_id || '');
    $f.find('[name="period_year"]').val(ent.period_year);
    $f.find('[name="opening_balance"]').val(ent.opening_balance);
    $f.find('[name="entitlement_days"]').val(ent.entitlement_days);
    $f.find('[name="carried_forward"]').val(ent.carried_forward);
    $f.find('[name="note"]').val(ent.note || '');
    if ($f.find('[name="staff_id"]').selectpicker) { $f.find('[name="staff_id"]').selectpicker('refresh'); }
}
</script>
