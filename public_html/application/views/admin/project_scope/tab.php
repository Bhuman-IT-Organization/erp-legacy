<?php
/**
 * The "Scope / Activities" tab inside the project view.
 *
 * Rendered by app_tabs, which loads this file with the project already in scope as
 * $project. It loads its own data here rather than relying on the projects controller,
 * so the core controller does not need changing.
 */
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('project_scope_model');
$CI->load->model('departments_model');

$project_id  = (int) $project->id;
$activities  = $CI->project_scope_model->get_activities($project_id);
$statuses    = $CI->project_scope_model->get_statuses(true);
$completion  = $CI->project_scope_model->project_completion($project_id);
$budget      = $CI->project_scope_model->budget_summary($project_id);
$departments = $CI->departments_model->get();
$staff_list  = $CI->staff_model->get('', ['active' => 1]);
$can_edit    = staff_can('edit', 'projects')
    || (staff_can('edit_own', 'projects') && $CI->projects_model->is_member($project_id, get_staff_user_id()));
$pending     = $CI->project_scope_model->get_pending_progress($project_id);
?>

<div class="row">
  <div class="col-md-12">
    <div class="row mbot15">
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body text-center">
            <h2 class="no-mtop bold"><?= (float) $completion['percent']; ?>%</h2>
            <p class="text-muted no-mbot"><?= _l('pscope_overall_completion'); ?></p>
            <small class="text-muted"><?= (int) $completion['items']; ?> <?= _l('pscope_items'); ?>,
              <?= _l('pscope_weighted'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-3">
                <p class="text-muted no-mbot"><?= _l('pscope_budget'); ?></p>
                <h5 class="no-mtop"><?= app_format_money($budget['budget'], ''); ?></h5>
              </div>
              <div class="col-md-3">
                <p class="text-muted no-mbot"><?= _l('pscope_material_budget'); ?></p>
                <h5 class="no-mtop"><?= app_format_money($budget['material'], ''); ?></h5>
              </div>
              <div class="col-md-3">
                <p class="text-muted no-mbot"><?= _l('pscope_actual_spent'); ?></p>
                <h5 class="no-mtop"><?= app_format_money($budget['actual'], ''); ?></h5>
              </div>
              <div class="col-md-3">
                <p class="text-muted no-mbot"><?= _l('pscope_remaining'); ?></p>
                <h5 class="no-mtop <?= $budget['remaining'] < 0 ? 'text-danger' : ''; ?>">
                  <?= app_format_money($budget['remaining'], ''); ?>
                </h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php if ($can_edit) { ?>
      <button type="button" class="btn btn-info mbot15" data-toggle="modal" data-target="#scope-modal"
              onclick="scopeReset();">
        <i class="fa fa-plus"></i> <?= _l('pscope_add_activity'); ?>
      </button>
    <?php } ?>

    <?php if (count($pending) > 0 && $can_edit) { ?>
      <div class="alert alert-warning">
        <?= count($pending); ?> <?= _l('pscope_updates_awaiting'); ?>
      </div>
    <?php } ?>

    <?php if (count($activities) === 0) { ?>
      <p class="text-muted text-center mtop15"><?= _l('pscope_none'); ?></p>
    <?php } else { ?>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th><?= _l('pscope_code'); ?></th>
              <th><?= _l('pscope_activity'); ?></th>
              <th><?= _l('pscope_responsible'); ?></th>
              <th><?= _l('pscope_department'); ?></th>
              <th><?= _l('status'); ?></th>
              <th><?= _l('pscope_weight'); ?></th>
              <th style="min-width:160px;"><?= _l('pscope_progress'); ?></th>
              <th><?= _l('pscope_budget'); ?></th>
              <th><?= _l('options'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activities as $a) {
                $pct = (int) $a['is_complete'] === 1 ? 100 : (float) $a['progress_percent']; ?>
              <tr class="<?= (int) $a['is_excluded'] === 1 ? 'text-muted' : ''; ?>">
                <td><?= e((string) $a['code']); ?></td>
                <td>
                  <strong><?= e($a['name']); ?></strong>
                  <?php if (!empty($a['quantity'])) { ?>
                    <br><small class="text-muted"><?= (float) $a['quantity']; ?> <?= e((string) $a['unit']); ?></small>
                  <?php } ?>
                </td>
                <td><?= e((string) $a['responsible_name']) ?: '&mdash;'; ?></td>
                <td><?= e((string) $a['department_name']) ?: '&mdash;'; ?></td>
                <td>
                  <span class="label" style="background-color:<?= e((string) $a['status_colour']) ?: '#64748b'; ?>">
                    <?= e((string) $a['status_name']); ?>
                  </span>
                </td>
                <td><?= (float) $a['weight']; ?></td>
                <td>
                  <div class="progress no-mbot" style="margin-bottom:2px;">
                    <div class="progress-bar" role="progressbar" style="width:<?= $pct; ?>%;">
                      <?= $pct; ?>%
                    </div>
                  </div>
                  <?php if ($a['requires_approval']) { ?>
                    <small class="text-muted"><?= _l('pscope_needs_signoff'); ?></small>
                  <?php } ?>
                </td>
                <td><?= $a['budget_amount'] !== null ? app_format_money($a['budget_amount'], '') : '&mdash;'; ?></td>
                <td>
                  <button type="button" class="btn btn-default btn-icon" data-toggle="modal"
                          data-target="#progress-modal"
                          onclick='progressOpen(<?= htmlspecialchars(json_encode($a), ENT_QUOTES, "UTF-8"); ?>);'>
                    <i class="fa fa-solid fa-arrow-up-right-dots"></i>
                  </button>
                  <?php if ($can_edit) { ?>
                    <button type="button" class="btn btn-default btn-icon" data-toggle="modal"
                            data-target="#scope-modal"
                            onclick='scopeEdit(<?= htmlspecialchars(json_encode($a), ENT_QUOTES, "UTF-8"); ?>);'>
                      <i class="fa fa-regular fa-pen-to-square"></i>
                    </button>
                    <a href="<?= admin_url('project_scope/delete/' . $a['id'] . '/' . $project_id); ?>"
                       class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>

    <?php /* The pending progress sign-offs for this project. */ ?>
    <?php if (count($pending) > 0 && $can_edit) { ?>
      <h5 class="mtop20"><?= _l('pscope_pending_signoff'); ?></h5>
      <table class="table table-condensed">
        <thead>
          <tr>
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
              <td><?= e((string) $p['activity_name']); ?></td>
              <td><?= (float) $p['previous_percent']; ?>% &rarr; <strong><?= (float) $p['progress_percent']; ?>%</strong></td>
              <td><?= e((string) $p['updated_by_name']); ?></td>
              <td><?= e((string) $p['note']); ?></td>
              <td>
                <?= form_open(admin_url('project_scope/review_progress'), ['class' => 'form-inline']); ?>
                  <input type="hidden" name="progress_id" value="<?= (int) $p['id']; ?>">
                  <input type="text" name="reason" class="form-control input-sm"
                         placeholder="<?= _l('pscope_reject_reason'); ?>" style="width:120px;">
                  <button type="submit" name="decision" value="approve" class="btn btn-success btn-sm">
                    <?= _l('pscope_approve'); ?>
                  </button>
                  <button type="submit" name="decision" value="reject" class="btn btn-danger btn-sm">
                    <?= _l('pscope_reject'); ?>
                  </button>
                <?= form_close(); ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    <?php } ?>
  </div>
</div>

<?php /* ------------------------------------------------------- add/edit modal */ ?>
<?php if ($can_edit) { ?>
<div class="modal fade" id="scope-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <?= form_open(admin_url('project_scope/save')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="scope-title"><?= _l('pscope_add_activity'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="scope-id">
        <input type="hidden" name="project_id" value="<?= $project_id; ?>">

        <div class="row">
          <div class="col-md-3"><?= render_input('code', 'pscope_code'); ?></div>
          <div class="col-md-9"><?= render_input('name', 'pscope_activity'); ?></div>
        </div>

        <?= render_textarea('description', 'description'); ?>

        <div class="row">
          <div class="col-md-4"><?= render_input('quantity', 'pscope_quantity', '', 'number'); ?></div>
          <div class="col-md-4"><?= render_input('unit', 'pscope_unit'); ?></div>
          <div class="col-md-4"><?= render_input('weight', 'pscope_weight', 1, 'number'); ?></div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group select-placeholder">
              <label><?= _l('pscope_responsible'); ?></label>
              <select name="responsible_staff_id" class="selectpicker" data-width="100%" data-live-search="true"
                      data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                <option value=""></option>
                <?php foreach ($staff_list as $s) { ?>
                  <option value="<?= (int) $s['staffid']; ?>"><?= e($s['firstname'] . ' ' . $s['lastname']); ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group select-placeholder">
              <label><?= _l('pscope_department'); ?></label>
              <select name="department_id" class="selectpicker" data-width="100%"
                      data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                <option value=""></option>
                <?php foreach ($departments as $d) { ?>
                  <option value="<?= (int) $d['departmentid']; ?>"><?= e($d['name']); ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4">
            <div class="form-group select-placeholder">
              <label><?= _l('status'); ?></label>
              <select name="status_id" class="selectpicker" data-width="100%">
                <?php foreach ($statuses as $st) { ?>
                  <option value="<?= (int) $st['id']; ?>"><?= e($st['name']); ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="col-md-4"><?= render_input('budget_amount', 'pscope_budget', '', 'number'); ?></div>
          <div class="col-md-4"><?= render_input('material_budget', 'pscope_material_budget', '', 'number'); ?></div>
        </div>

        <div class="row">
          <div class="col-md-6"><?= render_date_input('planned_start', 'pscope_planned_start'); ?></div>
          <div class="col-md-6"><?= render_date_input('planned_end', 'pscope_planned_end'); ?></div>
        </div>

        <div class="checkbox">
          <input type="checkbox" name="requires_approval" id="scope-requires-approval" value="1" checked>
          <label for="scope-requires-approval"><?= _l('pscope_requires_approval'); ?></label>
        </div>
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

<?php /* ------------------------------------------------------- progress modal */ ?>
<div class="modal fade" id="progress-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('project_scope/progress')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title"><?= _l('pscope_update_progress'); ?> <span id="progress-activity-name"></span></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="activity_id" id="progress-activity-id">
        <?= render_input('progress_percent', 'pscope_progress_percent', '', 'number'); ?>
        <?= render_input('quantity_done', 'pscope_quantity_done', '', 'number'); ?>
        <?= render_textarea('note', 'note'); ?>
        <p class="text-muted" id="progress-approval-note"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
        <button type="submit" class="btn btn-info"><?= _l('pscope_record'); ?></button>
      </div>
    </div>
    <?= form_close(); ?>
  </div>
</div>

<script>
  function scopeSet(id, val) {
    var $el = $('#' + id);
    if (!$el.length) { return; }
    $el.val(val === null || typeof val === 'undefined' ? '' : val);
    if ($el.is('select')) { $el.selectpicker('refresh'); }
  }

  function scopeReset() {
    $('#scope-modal form')[0].reset();
    scopeSet('scope-id', '');
    $('#scope-modal select').selectpicker('refresh');
    $('#scope-requires-approval').prop('checked', true);
    $('#scope-title').text('<?= _l('pscope_add_activity'); ?>');
  }

  function scopeEdit(a) {
    scopeReset();
    scopeSet('scope-id', a.id);
    $('#scope-modal input[name="code"]').val(a.code);
    $('#scope-modal input[name="name"]').val(a.name);
    $('#scope-modal textarea[name="description"]').val(a.description);
    $('#scope-modal input[name="quantity"]').val(a.quantity);
    $('#scope-modal input[name="unit"]').val(a.unit);
    $('#scope-modal input[name="weight"]').val(a.weight);
    $('#scope-modal input[name="budget_amount"]').val(a.budget_amount);
    $('#scope-modal input[name="material_budget"]').val(a.material_budget);
    $('#scope-modal select[name="responsible_staff_id"]').val(a.responsible_staff_id).selectpicker('refresh');
    $('#scope-modal select[name="department_id"]').val(a.department_id).selectpicker('refresh');
    $('#scope-modal select[name="status_id"]').val(a.status_id).selectpicker('refresh');
    $('#scope-requires-approval').prop('checked', parseInt(a.requires_approval, 10) === 1);
    $('#scope-title').text('<?= _l('edit'); ?>');
  }

  function progressOpen(a) {
    scopeSet('progress-activity-id', a.id);
    $('#progress-activity-name').text(a.name);
    $('#progress-modal input[name="progress_percent"]').val(a.is_complete == 1 ? 100 : a.progress_percent);
    $('#progress-modal input[name="quantity_done"]').val('');
    $('#progress-modal textarea[name="note"]').val('');
    $('#progress-approval-note').text(parseInt(a.requires_approval, 10) === 1
      ? '<?= _l('pscope_will_need_approval'); ?>' : '');
  }
</script>
