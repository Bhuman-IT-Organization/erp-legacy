<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop">
              <i class="fa fa-calendar-check-o"></i> <?= _l('attendance_dashboard'); ?>
            </h4>

            <?= form_open(admin_url('attendance_dashboard'), ['method' => 'get', 'class' => 'form-inline mtop15']); ?>
              <div class="form-group">
                <label for="date" class="mright5"><?= _l('date'); ?></label>
                <input type="date" name="date" id="date" class="form-control"
                       value="<?= e($date); ?>">
              </div>
              <div class="form-group mleft10">
                <label for="department" class="mright5"><?= _l('department'); ?></label>
                <select name="department" id="department" class="form-control">
                  <option value=""><?= _l('all'); ?></option>
                  <?php foreach ($departments as $dept) { ?>
                    <option value="<?= (int) $dept['departmentid']; ?>"
                      <?= $department === (int) $dept['departmentid'] ? 'selected' : ''; ?>>
                      <?= e($dept['name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
              <button type="submit" class="btn btn-info mleft10"><?= _l('submit'); ?></button>
            <?= form_close(); ?>

            <?php if (!empty($overview['holiday'])) { ?>
              <div class="alert alert-info mtop15">
                <i class="fa fa-info-circle"></i>
                <?= _l('attendance_is_holiday'); ?>: <strong><?= e($overview['holiday']); ?></strong>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-3">
        <div class="panel_s">
          <div class="panel-body text-center">
            <h2 class="bold no-margin text-success"><?= (int) $overview['summary']['present']; ?></h2>
            <span class="text-muted"><?= _l('attendance_attended'); ?></span>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="panel_s">
          <div class="panel-body text-center">
            <h2 class="bold no-margin text-danger"><?= (int) $overview['summary']['absent']; ?></h2>
            <span class="text-muted"><?= _l('attendance_not_marked'); ?></span>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="panel_s">
          <div class="panel-body text-center">
            <h2 class="bold no-margin text-warning"><?= (int) $overview['summary']['leave']; ?></h2>
            <span class="text-muted"><?= _l('attendance_on_leave_or_holiday'); ?></span>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="panel_s">
          <div class="panel-body text-center">
            <h2 class="bold no-margin"><?= (int) $overview['summary']['total']; ?></h2>
            <span class="text-muted"><?= _l('attendance_total_employees'); ?></span>
          </div>
        </div>
      </div>
    </div>

    <?php if (count($unclosed) > 0) { ?>
      <div class="row">
        <div class="col-md-12">
          <div class="panel_s">
            <div class="panel-body">
              <h4 class="no-mtop text-warning">
                <i class="fa fa-exclamation-triangle"></i> <?= _l('attendance_still_checked_in'); ?>
              </h4>
              <p class="text-muted"><?= _l('attendance_still_checked_in_help'); ?></p>
              <table class="table table-condensed">
                <thead>
                  <tr>
                    <th><?= _l('staff'); ?></th>
                    <th><?= _l('attendance_checked_in_at'); ?></th>
                    <th><?= _l('attendance_hours_open'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($unclosed as $open) { ?>
                    <tr>
                      <td><?= e(get_staff_full_name($open['staff_id'])); ?></td>
                      <td><?= _dt($open['date']); ?></td>
                      <td><span class="label label-warning"><?= e($open['hours_open']); ?></span></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><?= _l('attendance_daily_overview'); ?> &mdash; <?= _d($overview['date']); ?></h4>
            <hr />
            <div class="table-responsive">
              <table class="table table-striped" id="attendance-overview-table">
                <thead>
                  <tr>
                    <th style="width:24px;"></th>
                    <th><?= _l('staff'); ?></th>
                    <th><?= _l('attendance_employee_id'); ?></th>
                    <th><?= _l('status'); ?></th>
                    <th><?= _l('attendance_first_punch'); ?></th>
                    <th><?= _l('attendance_last_punch'); ?></th>
                    <th><?= _l('attendance_punches'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($overview['rows'] as $row) {
                      // Traffic light, plus a text label - colour alone is not
                      // accessible to a colour-blind user or a screen reader.
                      $dot = ['green' => '#27ae60', 'red' => '#c0392b', 'orange' => '#e67e22'];
                      ?>
                    <tr>
                      <td>
                        <span title="<?= e($row['label']); ?>"
                              style="display:inline-block;width:12px;height:12px;border-radius:50%;
                                     background:<?= $dot[$row['colour']]; ?>;"></span>
                        <span class="sr-only"><?= e($row['label']); ?></span>
                      </td>
                      <td><?= e($row['name']); ?></td>
                      <td><?= $row['employee_id'] ? e($row['employee_id']) : '-'; ?></td>
                      <td>
                        <?php
                        $cls = ['green' => 'label-success', 'red' => 'label-danger', 'orange' => 'label-warning'];
                        ?>
                        <span class="label <?= $cls[$row['colour']]; ?>"><?= e($row['label']); ?></span>
                      </td>
                      <td><?= $row['punches'] ? _dt($row['punches']['first_punch']) : '-'; ?></td>
                      <td><?= $row['punches'] ? _dt($row['punches']['last_punch']) : '-'; ?></td>
                      <td>
                        <?php if ($row['punches']) { ?>
                          <?= (int) $row['punches']['total']; ?>
                          <?php if (!$row['punches']['checked_out']) { ?>
                            <span class="label label-warning mleft5"><?= _l('attendance_no_checkout'); ?></span>
                          <?php } ?>
                        <?php } else { ?>
                          -
                        <?php } ?>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<?php init_tail(); ?>
