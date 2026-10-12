<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-solid fa-table-list"></i> <?= _l('hr_masters'); ?></h4>
            <p class="text-muted"><?= _l('hr_masters_help'); ?></p>

            <ul class="nav nav-tabs mtop15" role="tablist">
              <?php
              $tab_labels = [
                  'categories'      => _l('hr_employee_categories'),
                  'visa_types'      => _l('hr_visa_types'),
                  'document_types'  => _l('hr_document_types'),
                  'loan_policy'     => _l('hr_loan_policy'),
                  'increment_split' => _l('hr_increment_split'),
                  'approval_flows'  => _l('hr_approval_flows'),
                  'assignments'     => _l('hr_assignments'),
              ];
              foreach ($groups as $slug) { ?>
                <li role="presentation" class="<?= $group === $slug ? 'active' : ''; ?>">
                  <a href="<?= admin_url('hr_masters?group=' . $slug); ?>"><?= $tab_labels[$slug]; ?></a>
                </li>
              <?php } ?>
            </ul>

            <div class="tab-content mtop15">

              <?php /* ============================================== CATEGORIES */ ?>
              <?php if ($group === 'categories') { ?>
                <div class="alert alert-info"><?= _l('hr_categories_help'); ?></div>

                <button type="button" class="btn btn-info mbot15" data-toggle="modal" data-target="#cat-modal"
                        onclick="catReset();">
                  <i class="fa fa-plus"></i> <?= _l('hr_add_category'); ?>
                </button>

                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th><?= _l('name'); ?></th>
                        <th><?= _l('hr_approval_levels'); ?></th>
                        <th><?= _l('hr_is_labour'); ?></th>
                        <th><?= _l('hr_vacation_alert_days'); ?></th>
                        <th><?= _l('status'); ?></th>
                        <th><?= _l('options'); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($categories as $row) { ?>
                        <tr>
                          <td><?= e($row['name']); ?>
                            <br><small class="text-muted"><?= e($row['slug']); ?></small></td>
                          <td><?= (int) $row['approval_levels']; ?></td>
                          <td><?= $row['is_labour'] ? _l('yes') : _l('no'); ?></td>
                          <td>
                            <?= $row['vacation_balance_alert_days'] !== null
                                ? (float) $row['vacation_balance_alert_days']
                                : '<span class="text-muted">' . _l('hr_no_alert') . '</span>'; ?>
                          </td>
                          <td>
                            <span class="label label-<?= $row['active'] ? 'success' : 'default'; ?>">
                              <?= $row['active'] ? _l('active') : _l('inactive'); ?>
                            </span>
                          </td>
                          <td>
                            <a href="#" class="btn btn-default btn-icon" data-toggle="modal" data-target="#cat-modal"
                               onclick='catEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>); return false;'>
                              <i class="fa fa-regular fa-pen-to-square"></i>
                            </a>
                            <a href="<?= admin_url('hr_masters/delete_category/' . $row['id']); ?>"
                               class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                          </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              <?php } ?>

              <?php /* ============================================== VISA TYPES */ ?>
              <?php if ($group === 'visa_types') { ?>
                <div class="alert alert-info"><?= _l('hr_visa_types_help'); ?></div>

                <button type="button" class="btn btn-info mbot15" data-toggle="modal" data-target="#visa-modal"
                        onclick="visaReset();">
                  <i class="fa fa-plus"></i> <?= _l('hr_add_visa_type'); ?>
                </button>

                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th><?= _l('name'); ?></th>
                        <th><?= _l('hr_sort_order'); ?></th>
                        <th><?= _l('status'); ?></th>
                        <th><?= _l('options'); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($visa_types as $row) { ?>
                        <tr>
                          <td><?= e($row['name']); ?></td>
                          <td><?= (int) $row['sort_order']; ?></td>
                          <td>
                            <span class="label label-<?= $row['active'] ? 'success' : 'default'; ?>">
                              <?= $row['active'] ? _l('active') : _l('inactive'); ?>
                            </span>
                          </td>
                          <td>
                            <a href="#" class="btn btn-default btn-icon" data-toggle="modal" data-target="#visa-modal"
                               onclick='visaEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>); return false;'>
                              <i class="fa fa-regular fa-pen-to-square"></i>
                            </a>
                            <a href="<?= admin_url('hr_masters/delete_visa_type/' . $row['id']); ?>"
                               class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                          </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              <?php } ?>

              <?php /* ========================================== DOCUMENT TYPES */ ?>
              <?php if ($group === 'document_types') { ?>
                <div class="alert alert-info"><?= _l('hr_document_types_help'); ?></div>

                <button type="button" class="btn btn-info mbot15" data-toggle="modal" data-target="#doc-modal"
                        onclick="docReset();">
                  <i class="fa fa-plus"></i> <?= _l('hr_add_document_type'); ?>
                </button>

                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th><?= _l('name'); ?></th>
                        <th><?= _l('hr_has_number'); ?></th>
                        <th><?= _l('hr_has_expiry'); ?></th>
                        <th><?= _l('hr_reminder_days'); ?></th>
                        <th><?= _l('hr_employee_can_upload'); ?></th>
                        <th><?= _l('hr_requires_hr_approval'); ?></th>
                        <th><?= _l('hr_updates_master_fields'); ?></th>
                        <th><?= _l('status'); ?></th>
                        <th><?= _l('options'); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($document_types as $row) { ?>
                        <tr>
                          <td><?= e($row['name']); ?></td>
                          <td><?= $row['has_number'] ? _l('yes') : _l('no'); ?></td>
                          <td><?= $row['has_expiry'] ? _l('yes') : _l('no'); ?></td>
                          <td><?= (int) $row['reminder_days']; ?></td>
                          <td><?= $row['employee_can_upload'] ? _l('yes') : _l('no'); ?></td>
                          <td><?= $row['requires_hr_approval'] ? _l('yes') : _l('no'); ?></td>
                          <td>
                            <?php
                            $mapped = array_filter([$row['staff_number_field'], $row['staff_expiry_field']]);
                            echo count($mapped)
                                ? '<code>' . implode('</code> <code>', array_map('e', $mapped)) . '</code>'
                                : '<span class="text-muted">' . _l('hr_not_mapped') . '</span>';
                            ?>
                          </td>
                          <td>
                            <span class="label label-<?= $row['active'] ? 'success' : 'default'; ?>">
                              <?= $row['active'] ? _l('active') : _l('inactive'); ?>
                            </span>
                          </td>
                          <td>
                            <a href="#" class="btn btn-default btn-icon" data-toggle="modal" data-target="#doc-modal"
                               onclick='docEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>); return false;'>
                              <i class="fa fa-regular fa-pen-to-square"></i>
                            </a>
                            <a href="<?= admin_url('hr_masters/delete_document_type/' . $row['id']); ?>"
                               class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                          </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              <?php } ?>

              <?php /* ============================================= LOAN POLICY */ ?>
              <?php if ($group === 'loan_policy') { ?>
                <div class="alert alert-warning">
                  <strong><?= _l('hr_your_numbers_needed'); ?></strong>
                  <?= _l('hr_loan_policy_help'); ?>
                </div>

                <button type="button" class="btn btn-info mbot15" data-toggle="modal" data-target="#loan-modal"
                        onclick="loanReset();">
                  <i class="fa fa-plus"></i> <?= _l('hr_add_loan_policy'); ?>
                </button>

                <?php if (count($policies) === 0) { ?>
                  <p class="text-muted text-center mtop15"><?= _l('hr_no_loan_policy'); ?></p>
                <?php } else { ?>
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th><?= _l('hr_employee_category'); ?></th>
                          <th><?= _l('hr_max_amount'); ?></th>
                          <th><?= _l('hr_max_instalments'); ?></th>
                          <th><?= _l('hr_min_service_months'); ?></th>
                          <th><?= _l('hr_max_concurrent_loans'); ?></th>
                          <th><?= _l('hr_max_instalment_percent'); ?></th>
                          <th><?= _l('status'); ?></th>
                          <th><?= _l('options'); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($policies as $row) { ?>
                          <tr>
                            <td><?= $row['category_name']
                                ? e($row['category_name'])
                                : '<span class="text-muted">' . _l('hr_any_category') . '</span>'; ?></td>
                            <td><?= $row['max_amount'] !== null ? app_format_money($row['max_amount'], '') : '&mdash;'; ?></td>
                            <td><?= $row['max_instalments'] !== null ? (int) $row['max_instalments'] : '&mdash;'; ?></td>
                            <td><?= (int) $row['min_service_months']; ?></td>
                            <td><?= (int) $row['max_concurrent_loans']; ?></td>
                            <td><?= $row['max_instalment_percent_of_salary'] !== null
                                ? (float) $row['max_instalment_percent_of_salary'] . '%' : '&mdash;'; ?></td>
                            <td>
                              <span class="label label-<?= $row['active'] ? 'success' : 'default'; ?>">
                                <?= $row['active'] ? _l('active') : _l('inactive'); ?>
                              </span>
                            </td>
                            <td>
                              <a href="#" class="btn btn-default btn-icon" data-toggle="modal" data-target="#loan-modal"
                                 onclick='loanEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>); return false;'>
                                <i class="fa fa-regular fa-pen-to-square"></i>
                              </a>
                              <a href="<?= admin_url('hr_masters/delete_loan_policy/' . $row['id']); ?>"
                                 class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                            </td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                <?php } ?>
              <?php } ?>

              <?php /* ========================================= INCREMENT SPLIT */ ?>
              <?php if ($group === 'increment_split') { ?>
                <div class="alert alert-warning">
                  <strong><?= _l('hr_your_numbers_needed'); ?></strong>
                  <?= _l('hr_increment_split_help'); ?>
                </div>

                <?php foreach ($categories as $category) {
                    $totals = $split_totals[$category['id']] ?? ['ok' => false, 'total' => 0, 'rules' => 0]; ?>
                  <div class="panel_s mbot15">
                    <div class="panel-body">
                      <h5 class="no-mtop">
                        <?= e($category['name']); ?>
                        <?php if ($totals['rules'] === 0) { ?>
                          <span class="label label-default"><?= _l('hr_split_not_configured'); ?></span>
                        <?php } elseif ($totals['ok']) { ?>
                          <span class="label label-success"><?= (float) $totals['total']; ?>%</span>
                        <?php } else { ?>
                          <span class="label label-danger">
                            <?= (float) $totals['total']; ?>% &ndash; <?= _l('hr_split_must_total_100'); ?>
                          </span>
                        <?php } ?>
                      </h5>

                      <table class="table table-condensed mtop10">
                        <thead>
                          <tr>
                            <th><?= _l('hr_slot'); ?></th>
                            <th><?= _l('hr_component'); ?></th>
                            <th><?= _l('hr_allowance_type'); ?></th>
                            <th><?= _l('hr_percent'); ?></th>
                            <th><?= _l('status'); ?></th>
                            <th><?= _l('options'); ?></th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $rows = array_values(array_filter($split_rules, function ($r) use ($category) {
                              return (int) $r['category_id'] === (int) $category['id'];
                          }));
                          foreach ($rows as $row) { ?>
                            <tr>
                              <td><?= (int) $row['sort_order']; ?></td>
                              <td><?= _l('hr_component_' . $row['component_type']); ?></td>
                              <td>
                                <?php
                                if ($row['component_type'] === 'basic') {
                                    echo '<span class="text-muted">&mdash;</span>';
                                } elseif (empty($row['allowance_type_id'])) {
                                    echo '<span class="text-danger">' . _l('hr_pick_allowance_type') . '</span>';
                                } else {
                                    $label = $row['allowance_type_id'];
                                    foreach ($allowance_types as $at) {
                                        if ((int) $at['type_id'] === (int) $row['allowance_type_id']) {
                                            $label = $at['type_name'];
                                        }
                                    }
                                    echo e($label);
                                }
                                ?>
                              </td>
                              <td><strong><?= (float) $row['percent']; ?>%</strong></td>
                              <td>
                                <span class="label label-<?= $row['active'] ? 'success' : 'default'; ?>">
                                  <?= $row['active'] ? _l('active') : _l('inactive'); ?>
                                </span>
                              </td>
                              <td>
                                <a href="#" class="btn btn-default btn-icon" data-toggle="modal"
                                   data-target="#split-modal"
                                   onclick='splitEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>); return false;'>
                                  <i class="fa fa-regular fa-pen-to-square"></i>
                                </a>
                                <a href="<?= admin_url('hr_masters/delete_split_rule/' . $row['id']); ?>"
                                   class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                              </td>
                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>

                      <button type="button" class="btn btn-default btn-sm" data-toggle="modal"
                              data-target="#split-modal" onclick="splitReset(<?= (int) $category['id']; ?>);">
                        <i class="fa fa-plus"></i> <?= _l('hr_add_split_rule'); ?>
                      </button>
                    </div>
                  </div>
                <?php } ?>
              <?php } ?>

              <?php /* ========================================== APPROVAL FLOWS */ ?>
              <?php if ($group === 'approval_flows') { ?>
                <div class="alert alert-warning">
                  <strong><?= _l('hr_your_numbers_needed'); ?></strong>
                  <?= _l('hr_approval_flows_help'); ?>
                </div>

                <button type="button" class="btn btn-info mbot15" data-toggle="modal" data-target="#flow-modal"
                        onclick="flowReset();">
                  <i class="fa fa-plus"></i> <?= _l('hr_add_approval_flow'); ?>
                </button>

                <?php if (count($flows) === 0) { ?>
                  <p class="text-muted text-center mtop15"><?= _l('hr_no_approval_flows'); ?></p>
                <?php } else { ?>
                  <?php foreach ($flows as $flow) { ?>
                    <div class="panel_s mbot15">
                      <div class="panel-body">
                        <h5 class="no-mtop">
                          <?= e($flow['name']); ?>
                          <small class="text-muted">
                            <?= $request_types[$flow['request_type']] ?? e($flow['request_type']); ?>
                            &middot;
                            <?= $flow['category_name']
                                ? e($flow['category_name'])
                                : _l('hr_any_category'); ?>
                          </small>
                          <span class="label label-<?= $flow['active'] ? 'success' : 'default'; ?>">
                            <?= $flow['active'] ? _l('active') : _l('inactive'); ?>
                          </span>
                          <?php if (count($flow['levels']) === 0) { ?>
                            <span class="label label-danger"><?= _l('hr_flow_has_no_levels'); ?></span>
                          <?php } ?>
                        </h5>

                        <div class="pull-right">
                          <a href="#" class="btn btn-default btn-sm" data-toggle="modal" data-target="#flow-modal"
                             onclick='flowEdit(<?= htmlspecialchars(json_encode($flow), ENT_QUOTES, "UTF-8"); ?>); return false;'>
                            <i class="fa fa-regular fa-pen-to-square"></i> <?= _l('edit'); ?>
                          </a>
                          <a href="<?= admin_url('hr_masters/delete_flow/' . $flow['id']); ?>"
                             class="btn btn-danger btn-sm _delete"><i class="fa fa-remove"></i></a>
                        </div>
                        <div class="clearfix"></div>

                        <table class="table table-condensed mtop10">
                          <thead>
                            <tr>
                              <th><?= _l('approval_level'); ?></th>
                              <th><?= _l('name'); ?></th>
                              <th><?= _l('hr_approver'); ?></th>
                              <th><?= _l('hr_can_edit_amounts'); ?></th>
                              <th><?= _l('hr_sla_days'); ?></th>
                              <th><?= _l('options'); ?></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($flow['levels'] as $level) { ?>
                              <tr>
                                <td><strong><?= (int) $level['level_no']; ?></strong></td>
                                <td><?= e((string) $level['name']); ?></td>
                                <td>
                                  <?= $approver_types[$level['approver_type']] ?? e($level['approver_type']); ?>
                                  <?php if ($level['approver_type'] === 'specific_staff' && $level['approver_staff_id']) { ?>
                                    &ndash; <?= e(get_staff_full_name($level['approver_staff_id'])); ?>
                                  <?php } ?>
                                </td>
                                <td><?= $level['can_edit_amounts'] ? _l('yes') : _l('no'); ?></td>
                                <td><?= $level['sla_days'] !== null ? (int) $level['sla_days'] : '&mdash;'; ?></td>
                                <td>
                                  <a href="#" class="btn btn-default btn-icon" data-toggle="modal"
                                     data-target="#level-modal"
                                     onclick='levelEdit(<?= htmlspecialchars(json_encode($level), ENT_QUOTES, "UTF-8"); ?>); return false;'>
                                    <i class="fa fa-regular fa-pen-to-square"></i>
                                  </a>
                                  <a href="<?= admin_url('hr_masters/delete_level/' . $level['id']); ?>"
                                     class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                                </td>
                              </tr>
                            <?php } ?>
                          </tbody>
                        </table>

                        <button type="button" class="btn btn-default btn-sm" data-toggle="modal"
                                data-target="#level-modal"
                                onclick="levelReset(<?= (int) $flow['id']; ?>, <?= count($flow['levels']) + 1; ?>);">
                          <i class="fa fa-plus"></i> <?= _l('hr_add_level'); ?>
                        </button>
                      </div>
                    </div>
                  <?php } ?>
                <?php } ?>
              <?php } ?>

              <?php /* ============================================ ASSIGNMENTS */ ?>
              <?php if ($group === 'assignments') { ?>
                <div class="alert alert-info"><?= _l('hr_assignments_help'); ?></div>

                <?php
                $unassigned = 0;
                foreach ($profiles as $p) {
                    if (empty($p['category_id'])) {
                        $unassigned++;
                    }
                }
                if ($unassigned > 0) { ?>
                  <div class="alert alert-warning">
                    <?= $unassigned; ?> <?= _l('hr_employees_without_category'); ?>
                  </div>
                <?php } ?>

                <form action="<?= admin_url('hr_masters/bulk_assign_category'); ?>" method="post">
                  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <div class="row mbot15">
                    <div class="col-md-4">
                      <select name="category_id" class="selectpicker" data-width="100%"
                              data-none-selected-text="<?= _l('hr_bulk_pick_category'); ?>">
                        <option value=""></option>
                        <?php foreach ($categories as $c) { ?>
                          <option value="<?= (int) $c['id']; ?>"><?= e($c['name']); ?></option>
                        <?php } ?>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <button type="submit" class="btn btn-info">
                        <?= _l('hr_assign_to_selected'); ?>
                      </button>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th><input type="checkbox" onclick="hrToggleAll(this);"></th>
                          <th><?= _l('staff'); ?></th>
                          <th><?= _l('hr_employee_number'); ?></th>
                          <th><?= _l('hr_employee_category'); ?></th>
                          <th><?= _l('hr_visa_type'); ?></th>
                          <th><?= _l('hr_joining_date'); ?></th>
                          <th><?= _l('hr_salary_on_joining'); ?></th>
                          <th><?= _l('options'); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($profiles as $row) { ?>
                          <tr>
                            <td><input type="checkbox" name="staff_ids[]" value="<?= (int) $row['staffid']; ?>"></td>
                            <td><?= e($row['firstname'] . ' ' . $row['lastname']); ?></td>
                            <td><?= e((string) $row['staff_identifi']); ?></td>
                            <td>
                              <?= $row['category_name']
                                  ? e($row['category_name'])
                                  : '<span class="label label-warning">' . _l('hr_not_set') . '</span>'; ?>
                            </td>
                            <td><?= e((string) $row['visa_type_name']); ?></td>
                            <td>
                              <?php
                              // The override wins; otherwise show tblstaff.datecreated and
                              // say so, because that value is the record creation date and
                              // is wrong for anyone migrated in from another system.
                              if (!empty($row['joining_date'])) {
                                  echo _d($row['joining_date']);
                              } else {
                                  echo '<span class="text-muted">' . _d($row['datecreated'])
                                      . ' <small>' . _l('hr_from_record_created') . '</small></span>';
                              }
                              ?>
                            </td>
                            <td><?= $row['salary_on_joining'] !== null
                                ? app_format_money($row['salary_on_joining'], '') : '&mdash;'; ?></td>
                            <td>
                              <a href="#" class="btn btn-default btn-icon" data-toggle="modal"
                                 data-target="#profile-modal"
                                 onclick='profileEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>); return false;'>
                                <i class="fa fa-regular fa-pen-to-square"></i>
                              </a>
                            </td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                </form>
              <?php } ?>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php /* ================================================================ MODALS */ ?>

<?php if ($group === 'categories') { ?>
<div class="modal fade" id="cat-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('hr_masters/save_category')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="cat-title"><?= _l('hr_add_category'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="cat-id">
        <?= render_input('name', 'name'); ?>
        <?= render_input('approval_levels', 'hr_approval_levels', 2, 'number'); ?>
        <p class="text-muted"><?= _l('hr_approval_levels_help'); ?></p>
        <?= render_input('vacation_balance_alert_days', 'hr_vacation_alert_days', '', 'number'); ?>
        <p class="text-muted"><?= _l('hr_vacation_alert_days_help'); ?></p>
        <?= render_input('sort_order', 'hr_sort_order', 0, 'number'); ?>
        <div class="checkbox">
          <input type="checkbox" name="is_labour" id="cat-is-labour" value="1">
          <label for="cat-is-labour"><?= _l('hr_is_labour'); ?></label>
        </div>
        <p class="text-muted"><?= _l('hr_is_labour_help'); ?></p>
        <div class="checkbox">
          <input type="checkbox" name="active" id="cat-active" value="1" checked>
          <label for="cat-active"><?= _l('active'); ?></label>
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

<?php if ($group === 'visa_types') { ?>
<div class="modal fade" id="visa-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('hr_masters/save_visa_type')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="visa-title"><?= _l('hr_add_visa_type'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="visa-id">
        <?= render_input('name', 'name'); ?>
        <?= render_input('sort_order', 'hr_sort_order', 0, 'number'); ?>
        <div class="checkbox">
          <input type="checkbox" name="active" id="visa-active" value="1" checked>
          <label for="visa-active"><?= _l('active'); ?></label>
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

<?php if ($group === 'document_types') { ?>
<div class="modal fade" id="doc-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('hr_masters/save_document_type')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="doc-title"><?= _l('hr_add_document_type'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="doc-id">
        <?= render_input('name', 'name'); ?>
        <?= render_input('reminder_days', 'hr_reminder_days', 30, 'number'); ?>
        <p class="text-muted"><?= _l('hr_reminder_days_help'); ?></p>

        <div class="form-group select-placeholder">
          <label for="staff_number_field"><?= _l('hr_staff_number_field'); ?></label>
          <select name="staff_number_field" id="doc-number-field" class="selectpicker" data-width="100%"
                  data-none-selected-text="<?= _l('hr_not_mapped'); ?>">
            <option value=""></option>
            <?php foreach ($staff_fields as $field => $label) { ?>
              <option value="<?= e($field); ?>"><?= e($label); ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="form-group select-placeholder">
          <label for="staff_expiry_field"><?= _l('hr_staff_expiry_field'); ?></label>
          <select name="staff_expiry_field" id="doc-expiry-field" class="selectpicker" data-width="100%"
                  data-none-selected-text="<?= _l('hr_not_mapped'); ?>">
            <option value=""></option>
            <?php foreach ($staff_fields as $field => $label) { ?>
              <option value="<?= e($field); ?>"><?= e($label); ?></option>
            <?php } ?>
          </select>
        </div>
        <p class="text-muted"><?= _l('hr_master_field_help'); ?></p>

        <?= render_input('sort_order', 'hr_sort_order', 0, 'number'); ?>

        <div class="checkbox">
          <input type="checkbox" name="has_number" id="doc-has-number" value="1" checked>
          <label for="doc-has-number"><?= _l('hr_has_number'); ?></label>
        </div>
        <div class="checkbox">
          <input type="checkbox" name="has_expiry" id="doc-has-expiry" value="1" checked>
          <label for="doc-has-expiry"><?= _l('hr_has_expiry'); ?></label>
        </div>
        <div class="checkbox">
          <input type="checkbox" name="employee_can_upload" id="doc-can-upload" value="1" checked>
          <label for="doc-can-upload"><?= _l('hr_employee_can_upload'); ?></label>
        </div>
        <div class="checkbox">
          <input type="checkbox" name="requires_hr_approval" id="doc-requires-approval" value="1" checked>
          <label for="doc-requires-approval"><?= _l('hr_requires_hr_approval'); ?></label>
        </div>
        <div class="checkbox">
          <input type="checkbox" name="active" id="doc-active" value="1" checked>
          <label for="doc-active"><?= _l('active'); ?></label>
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

<?php if ($group === 'loan_policy') { ?>
<div class="modal fade" id="loan-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('hr_masters/save_loan_policy')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="loan-title"><?= _l('hr_add_loan_policy'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="loan-id">

        <div class="form-group select-placeholder">
          <label for="category_id"><?= _l('hr_employee_category'); ?></label>
          <select name="category_id" id="loan-category" class="selectpicker" data-width="100%"
                  data-none-selected-text="<?= _l('hr_any_category'); ?>">
            <option value=""><?= _l('hr_any_category'); ?></option>
            <?php foreach ($categories as $c) { ?>
              <option value="<?= (int) $c['id']; ?>"><?= e($c['name']); ?></option>
            <?php } ?>
          </select>
        </div>

        <?= render_input('max_amount', 'hr_max_amount', '', 'number'); ?>
        <?= render_input('max_instalments', 'hr_max_instalments', '', 'number'); ?>
        <?= render_input('min_service_months', 'hr_min_service_months', 0, 'number'); ?>
        <?= render_input('max_concurrent_loans', 'hr_max_concurrent_loans', 1, 'number'); ?>
        <?= render_input('max_instalment_percent_of_salary', 'hr_max_instalment_percent', '', 'number'); ?>
        <?= render_textarea('note', 'note'); ?>

        <div class="checkbox">
          <input type="checkbox" name="active" id="loan-active" value="1">
          <label for="loan-active"><?= _l('active'); ?></label>
        </div>
        <p class="text-muted"><?= _l('hr_loan_policy_active_help'); ?></p>
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

<?php if ($group === 'increment_split') { ?>
<div class="modal fade" id="split-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('hr_masters/save_split_rule')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="split-title"><?= _l('hr_add_split_rule'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="split-id">
        <input type="hidden" name="category_id" id="split-category">

        <div class="form-group select-placeholder">
          <label for="component_type"><?= _l('hr_component'); ?></label>
          <select name="component_type" id="split-component" class="selectpicker" data-width="100%">
            <option value="basic"><?= _l('hr_component_basic'); ?></option>
            <option value="allowance"><?= _l('hr_component_allowance'); ?></option>
          </select>
        </div>

        <div class="form-group select-placeholder">
          <label for="allowance_type_id"><?= _l('hr_allowance_type'); ?></label>
          <select name="allowance_type_id" id="split-allowance" class="selectpicker" data-width="100%"
                  data-none-selected-text="<?= _l('hr_pick_allowance_type'); ?>">
            <option value=""></option>
            <?php foreach ($allowance_types as $at) { ?>
              <option value="<?= (int) $at['type_id']; ?>"><?= e($at['type_name']); ?></option>
            <?php } ?>
          </select>
          <?php if (count($allowance_types) === 0) { ?>
            <p class="text-danger"><?= _l('hr_no_allowance_types'); ?></p>
          <?php } ?>
        </div>

        <?= render_input('percent', 'hr_percent', '', 'number'); ?>
        <?= render_input('label', 'hr_label'); ?>
        <?= render_input('sort_order', 'hr_slot', 1, 'number'); ?>

        <div class="checkbox">
          <input type="checkbox" name="active" id="split-active" value="1">
          <label for="split-active"><?= _l('active'); ?></label>
        </div>
        <p class="text-muted"><?= _l('hr_split_active_help'); ?></p>
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

<?php if ($group === 'approval_flows') { ?>
<div class="modal fade" id="flow-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('hr_masters/save_flow')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="flow-title"><?= _l('hr_add_approval_flow'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="flow-id">
        <?= render_input('name', 'name'); ?>

        <div class="form-group select-placeholder">
          <label for="request_type"><?= _l('hr_request_type'); ?></label>
          <select name="request_type" id="flow-request-type" class="selectpicker" data-width="100%">
            <?php foreach ($request_types as $slug => $label) { ?>
              <option value="<?= e($slug); ?>"><?= e($label); ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="form-group select-placeholder">
          <label for="employee_category_id"><?= _l('hr_employee_category'); ?></label>
          <select name="employee_category_id" id="flow-category" class="selectpicker" data-width="100%"
                  data-none-selected-text="<?= _l('hr_any_category'); ?>">
            <option value=""><?= _l('hr_any_category'); ?></option>
            <?php foreach ($categories as $c) { ?>
              <option value="<?= (int) $c['id']; ?>">
                <?= e($c['name']); ?> (<?= (int) $c['approval_levels']; ?> <?= _l('hr_levels'); ?>)
              </option>
            <?php } ?>
          </select>
          <p class="text-muted"><?= _l('hr_flow_category_help'); ?></p>
        </div>

        <?= render_textarea('note', 'note'); ?>

        <div class="checkbox">
          <input type="checkbox" name="active" id="flow-active" value="1" checked>
          <label for="flow-active"><?= _l('active'); ?></label>
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

<div class="modal fade" id="level-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('hr_masters/save_level')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="level-title"><?= _l('hr_add_level'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="level-id">
        <input type="hidden" name="flow_id" id="level-flow-id">

        <?= render_input('level_no', 'approval_level', 1, 'number'); ?>
        <?= render_input('name', 'name'); ?>

        <div class="form-group select-placeholder">
          <label for="approver_type"><?= _l('hr_approver'); ?></label>
          <select name="approver_type" id="level-approver-type" class="selectpicker" data-width="100%">
            <?php foreach ($approver_types as $slug => $label) { ?>
              <option value="<?= e($slug); ?>"><?= e($label); ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="form-group select-placeholder">
          <label for="approver_staff_id"><?= _l('staff'); ?></label>
          <select name="approver_staff_id" id="level-staff" class="selectpicker" data-width="100%"
                  data-live-search="true" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
            <option value=""></option>
            <?php foreach ($staff as $s) { ?>
              <option value="<?= (int) $s['staffid']; ?>">
                <?= e($s['firstname'] . ' ' . $s['lastname']); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <div class="form-group select-placeholder">
          <label for="approver_role_id"><?= _l('role'); ?></label>
          <select name="approver_role_id" id="level-role" class="selectpicker" data-width="100%"
                  data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
            <option value=""></option>
            <?php foreach ($roles as $r) { ?>
              <option value="<?= (int) $r['roleid']; ?>"><?= e($r['name']); ?></option>
            <?php } ?>
          </select>
        </div>

        <?= render_input('sla_days', 'hr_sla_days', '', 'number'); ?>

        <div class="checkbox">
          <input type="checkbox" name="can_edit_amounts" id="level-can-edit" value="1">
          <label for="level-can-edit"><?= _l('hr_can_edit_amounts'); ?></label>
        </div>
        <p class="text-muted"><?= _l('hr_can_edit_amounts_help'); ?></p>
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

<?php if ($group === 'assignments') { ?>
<div class="modal fade" id="profile-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('hr_masters/save_profile')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="profile-title"><?= _l('hr_employee_profile'); ?></h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="staff_id" id="profile-staff-id">

        <div class="form-group select-placeholder">
          <label for="category_id"><?= _l('hr_employee_category'); ?></label>
          <select name="category_id" id="profile-category" class="selectpicker" data-width="100%"
                  data-none-selected-text="<?= _l('hr_not_set'); ?>">
            <option value=""></option>
            <?php foreach ($categories as $c) { ?>
              <option value="<?= (int) $c['id']; ?>"><?= e($c['name']); ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="form-group select-placeholder">
          <label for="visa_type_id"><?= _l('hr_visa_type'); ?></label>
          <select name="visa_type_id" id="profile-visa" class="selectpicker" data-width="100%"
                  data-none-selected-text="<?= _l('hr_not_set'); ?>">
            <option value=""></option>
            <?php foreach ($visa_types as $v) { ?>
              <option value="<?= (int) $v['id']; ?>"><?= e($v['name']); ?></option>
            <?php } ?>
          </select>
        </div>

        <?= render_date_input('joining_date', 'hr_joining_date'); ?>
        <p class="text-muted"><?= _l('hr_joining_date_help'); ?></p>
        <?= render_date_input('probation_end_date', 'hr_probation_end_date'); ?>
        <?= render_input('salary_on_joining', 'hr_salary_on_joining', '', 'number'); ?>
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
  // Small helpers shared by the modals. Each fills the form from the row that was
  // clicked, so editing never silently carries a value over from a previous open.
  function hrSet(id, value) {
    var $el = $('#' + id);
    if (!$el.length) { return; }
    $el.val(value === null || typeof value === 'undefined' ? '' : value);
    if ($el.hasClass('selectpicker') || $el.is('select')) { $el.selectpicker('refresh'); }
  }

  function hrCheck(id, on) {
    $('#' + id).prop('checked', !!parseInt(on, 10));
  }

  function hrToggleAll(el) {
    $('input[name="staff_ids[]"]').prop('checked', $(el).prop('checked'));
  }

  /* ------------------------------------------------------------- categories */
  function catReset() {
    hrSet('cat-id', '');
    $('#cat-modal input[name="name"]').val('');
    $('#cat-modal input[name="approval_levels"]').val(2);
    $('#cat-modal input[name="vacation_balance_alert_days"]').val('');
    $('#cat-modal input[name="sort_order"]').val(0);
    hrCheck('cat-is-labour', 0);
    hrCheck('cat-active', 1);
    $('#cat-title').text('<?= _l('hr_add_category'); ?>');
  }

  function catEdit(row) {
    catReset();
    hrSet('cat-id', row.id);
    $('#cat-modal input[name="name"]').val(row.name);
    $('#cat-modal input[name="approval_levels"]').val(row.approval_levels);
    $('#cat-modal input[name="vacation_balance_alert_days"]').val(row.vacation_balance_alert_days);
    $('#cat-modal input[name="sort_order"]').val(row.sort_order);
    hrCheck('cat-is-labour', row.is_labour);
    hrCheck('cat-active', row.active);
    $('#cat-title').text('<?= _l('edit'); ?>');
  }

  /* ------------------------------------------------------------- visa types */
  function visaReset() {
    hrSet('visa-id', '');
    $('#visa-modal input[name="name"]').val('');
    $('#visa-modal input[name="sort_order"]').val(0);
    hrCheck('visa-active', 1);
    $('#visa-title').text('<?= _l('hr_add_visa_type'); ?>');
  }

  function visaEdit(row) {
    visaReset();
    hrSet('visa-id', row.id);
    $('#visa-modal input[name="name"]').val(row.name);
    $('#visa-modal input[name="sort_order"]').val(row.sort_order);
    hrCheck('visa-active', row.active);
    $('#visa-title').text('<?= _l('edit'); ?>');
  }

  /* --------------------------------------------------------- document types */
  function docReset() {
    hrSet('doc-id', '');
    $('#doc-modal input[name="name"]').val('');
    $('#doc-modal input[name="reminder_days"]').val(30);
    $('#doc-modal input[name="sort_order"]').val(0);
    hrSet('doc-number-field', '');
    hrSet('doc-expiry-field', '');
    hrCheck('doc-has-number', 1);
    hrCheck('doc-has-expiry', 1);
    hrCheck('doc-can-upload', 1);
    hrCheck('doc-requires-approval', 1);
    hrCheck('doc-active', 1);
    $('#doc-title').text('<?= _l('hr_add_document_type'); ?>');
  }

  function docEdit(row) {
    docReset();
    hrSet('doc-id', row.id);
    $('#doc-modal input[name="name"]').val(row.name);
    $('#doc-modal input[name="reminder_days"]').val(row.reminder_days);
    $('#doc-modal input[name="sort_order"]').val(row.sort_order);
    hrSet('doc-number-field', row.staff_number_field);
    hrSet('doc-expiry-field', row.staff_expiry_field);
    hrCheck('doc-has-number', row.has_number);
    hrCheck('doc-has-expiry', row.has_expiry);
    hrCheck('doc-can-upload', row.employee_can_upload);
    hrCheck('doc-requires-approval', row.requires_hr_approval);
    hrCheck('doc-active', row.active);
    $('#doc-title').text('<?= _l('edit'); ?>');
  }

  /* ------------------------------------------------------------ loan policy */
  function loanReset() {
    hrSet('loan-id', '');
    hrSet('loan-category', '');
    $('#loan-modal input[name="max_amount"]').val('');
    $('#loan-modal input[name="max_instalments"]').val('');
    $('#loan-modal input[name="min_service_months"]').val(0);
    $('#loan-modal input[name="max_concurrent_loans"]').val(1);
    $('#loan-modal input[name="max_instalment_percent_of_salary"]').val('');
    $('#loan-modal textarea[name="note"]').val('');
    hrCheck('loan-active', 0);
    $('#loan-title').text('<?= _l('hr_add_loan_policy'); ?>');
  }

  function loanEdit(row) {
    loanReset();
    hrSet('loan-id', row.id);
    hrSet('loan-category', row.category_id);
    $('#loan-modal input[name="max_amount"]').val(row.max_amount);
    $('#loan-modal input[name="max_instalments"]').val(row.max_instalments);
    $('#loan-modal input[name="min_service_months"]').val(row.min_service_months);
    $('#loan-modal input[name="max_concurrent_loans"]').val(row.max_concurrent_loans);
    $('#loan-modal input[name="max_instalment_percent_of_salary"]').val(row.max_instalment_percent_of_salary);
    $('#loan-modal textarea[name="note"]').val(row.note);
    hrCheck('loan-active', row.active);
    $('#loan-title').text('<?= _l('edit'); ?>');
  }

  /* -------------------------------------------------------- increment split */
  function splitReset(categoryId) {
    hrSet('split-id', '');
    hrSet('split-category', categoryId || '');
    hrSet('split-component', 'basic');
    hrSet('split-allowance', '');
    $('#split-modal input[name="percent"]').val('');
    $('#split-modal input[name="label"]').val('');
    $('#split-modal input[name="sort_order"]').val(1);
    hrCheck('split-active', 0);
    $('#split-title').text('<?= _l('hr_add_split_rule'); ?>');
  }

  function splitEdit(row) {
    splitReset(row.category_id);
    hrSet('split-id', row.id);
    hrSet('split-component', row.component_type);
    hrSet('split-allowance', row.allowance_type_id);
    $('#split-modal input[name="percent"]').val(row.percent);
    $('#split-modal input[name="label"]').val(row.label);
    $('#split-modal input[name="sort_order"]').val(row.sort_order);
    hrCheck('split-active', row.active);
    $('#split-title').text('<?= _l('edit'); ?>');
  }

  /* ---------------------------------------------------------- approval flow */
  function flowReset() {
    hrSet('flow-id', '');
    $('#flow-modal input[name="name"]').val('');
    hrSet('flow-category', '');
    $('#flow-modal textarea[name="note"]').val('');
    hrCheck('flow-active', 1);
    $('#flow-title').text('<?= _l('hr_add_approval_flow'); ?>');
  }

  function flowEdit(row) {
    flowReset();
    hrSet('flow-id', row.id);
    $('#flow-modal input[name="name"]').val(row.name);
    hrSet('flow-request-type', row.request_type);
    hrSet('flow-category', row.employee_category_id);
    $('#flow-modal textarea[name="note"]').val(row.note);
    hrCheck('flow-active', row.active);
    $('#flow-title').text('<?= _l('edit'); ?>');
  }

  function levelReset(flowId, nextLevel) {
    hrSet('level-id', '');
    hrSet('level-flow-id', flowId);
    $('#level-modal input[name="level_no"]').val(nextLevel || 1);
    $('#level-modal input[name="name"]').val('');
    $('#level-modal input[name="sla_days"]').val('');
    hrSet('level-approver-type', 'direct_manager');
    hrSet('level-staff', '');
    hrSet('level-role', '');
    hrCheck('level-can-edit', 0);
    $('#level-title').text('<?= _l('hr_add_level'); ?>');
  }

  function levelEdit(row) {
    levelReset(row.flow_id, row.level_no);
    hrSet('level-id', row.id);
    $('#level-modal input[name="name"]').val(row.name);
    $('#level-modal input[name="sla_days"]').val(row.sla_days);
    hrSet('level-approver-type', row.approver_type);
    hrSet('level-staff', row.approver_staff_id);
    hrSet('level-role', row.approver_role_id);
    hrCheck('level-can-edit', row.can_edit_amounts);
    $('#level-title').text('<?= _l('edit'); ?>');
  }

  /* --------------------------------------------------------- staff profile */
  function profileEdit(row) {
    hrSet('profile-staff-id', row.staffid);
    hrSet('profile-category', row.category_id);
    hrSet('profile-visa', row.visa_type_id);
    $('#profile-modal input[name="joining_date"]').val(row.joining_date ? formatDateForInput(row.joining_date) : '');
    $('#profile-modal input[name="probation_end_date"]').val(row.probation_end_date ? formatDateForInput(row.probation_end_date) : '');
    $('#profile-modal input[name="salary_on_joining"]').val(row.salary_on_joining);
    $('#profile-modal textarea[name="note"]').val(row.note);
    $('#profile-title').text(row.firstname + ' ' + row.lastname);
  }

  // The rows arrive as Y-m-d from the database but the date inputs expect the
  // application's configured format, so convert rather than showing a raw date.
  function formatDateForInput(ymd) {
    if (!ymd) { return ''; }
    var parts = ymd.split('-');
    if (parts.length !== 3) { return ymd; }
    var fmt = '<?= get_option('dateformat'); ?>';
    if (fmt.indexOf('d-m-Y') === 0 || fmt.indexOf('dd-mm-yyyy') === 0) {
      return parts[2] + '-' + parts[1] + '-' + parts[0];
    }
    if (fmt.indexOf('m-d-Y') === 0 || fmt.indexOf('mm-dd-yyyy') === 0) {
      return parts[1] + '-' + parts[2] + '-' + parts[0];
    }
    if (fmt.indexOf('d/m/Y') === 0 || fmt.indexOf('dd/mm/yyyy') === 0) {
      return parts[2] + '/' + parts[1] + '/' + parts[0];
    }
    if (fmt.indexOf('m/d/Y') === 0 || fmt.indexOf('mm/dd/yyyy') === 0) {
      return parts[1] + '/' + parts[2] + '/' + parts[0];
    }
    return ymd;
  }
</script>
</body>
</html>
