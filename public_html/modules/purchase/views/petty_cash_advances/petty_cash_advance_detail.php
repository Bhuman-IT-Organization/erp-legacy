<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Petty Cash Advance Request - read only view plus approvals.
 */

$pca   = $advance;
$roles = pur_pca_approval_roles();

$base_currency = get_base_currency_pur();
$currency      = $base_currency;
if (!empty($pca->currency)) {
    $found = pur_get_currency_by_id($pca->currency);
    if ($found) {
        $currency = $found;
    }
}

$can_approve = has_permission('purchase_petty_cash', '', 'edit');

$locked = false;
foreach ($approvals as $approval) {
    if (in_array((int) $approval['status'], [2, 3], true)) {
        $locked = true;
        break;
    }
}

// The item lines should add up to the requested amount. A mismatch is worth showing
// rather than silently tolerating, because finance will be paying out the requested
// figure.
$items_total   = isset($items_total) ? (float) $items_total : 0.00;
$amount_mismatch = count($items) > 0 && round($items_total, 2) != round((float) $pca->amount_requested, 2);
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div class="row mbot15">
              <div class="col-md-7">
                <h4 class="no-margin font-bold"><?php echo _l('pur_pca_form_title'); ?></h4>
                <div class="text-muted"><?php echo format_organization_info(); ?></div>
                <p class="no-mbot mtop10">
                  <?php echo e($pca->request_no); ?> &middot;
                  <?php echo pur_pca_status_label($pca->approve_status); ?> &middot;
                  <?php echo pur_pca_priority_label($pca->payment_priority); ?>
                </p>
              </div>
              <div class="col-md-5 text-right">
                <a href="#" onclick="window.print(); return false;" class="btn btn-default noprint">
                  <i class="fa fa-print"></i> <?php echo _l('print'); ?>
                </a>
                <?php if (has_permission('purchase_petty_cash', '', 'edit') && !$locked) { ?>
                <a href="<?php echo admin_url('purchase/petty_cash_advance/' . $pca->id); ?>" class="btn btn-warning noprint">
                  <i class="fa fa-pencil"></i> <?php echo _l('edit'); ?>
                </a>
                <?php } ?>
                <a href="<?php echo admin_url('purchase/petty_cash_advances'); ?>" class="btn btn-default noprint">
                  <?php echo _l('pur_bw_back'); ?>
                </a>
              </div>
            </div>

            <?php if ($locked) { ?>
            <div class="alert alert-info noprint"><?php echo _l('pur_pca_cannot_edit_approved'); ?></div>
            <?php } ?>

            <hr class="hr-panel-heading" />

            <!-- Document control -->
            <table class="table table-bordered mbot15">
              <thead>
                <tr>
                  <th><?php echo _l('pur_pca_doc_version'); ?></th>
                  <th><?php echo _l('pur_pca_doc_effective_date'); ?></th>
                  <th><?php echo _l('pur_pca_doc_revise'); ?></th>
                  <th><?php echo _l('pur_pca_doc_number'); ?></th>
                  <th><?php echo _l('pur_pca_doc_approved_by'); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><?php echo e($pca->doc_version); ?></td>
                  <td><?php echo $pca->doc_effective_date ? _d($pca->doc_effective_date) : ''; ?></td>
                  <?php // Revise holds the date of the most recent edit, empty on version 1.0 ?>
                  <td><?php echo $pca->doc_revise ? _d($pca->doc_revise) : ''; ?></td>
                  <td><?php echo e($pca->doc_number); ?></td>
                  <td>
                    <?php if (!empty($pca->doc_approved_by)) {
                        echo e($pca->doc_approved_by);
                    } else { ?>
                    <span class="text-muted"><?php echo _l('pur_bw_doc_approved_pending'); ?></span>
                    <?php } ?>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- 1. Requestor Details -->
            <h4 class="bold mtop20"><?php echo _l('pur_pca_section_1'); ?></h4>
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <td class="bold" style="width:30%;"><?php echo _l('pur_pca_request_no'); ?></td>
                  <td><?php echo e($pca->request_no); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_pca_employee_id'); ?></td>
                  <td><?php echo e($pca->employee_id); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_pca_employee_name'); ?></td>
                  <td><?php echo $pca->employee ? e(get_staff_full_name($pca->employee)) : ''; ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_pca_department'); ?></td>
                  <td><?php echo e($pca->department_name); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_pca_required_date'); ?></td>
                  <td><?php echo $pca->required_date ? _d($pca->required_date) : ''; ?></td>
                </tr>
              </tbody>
            </table>

            <!-- 2. Advance Request Details -->
            <h4 class="bold mtop20"><?php echo _l('pur_pca_section_2'); ?></h4>
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <td class="bold" style="width:30%;"><?php echo _l('pur_pca_purpose_of_advance'); ?></td>
                  <td><?php echo nl2br(e($pca->purpose_of_advance)); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_pca_expense_category'); ?></td>
                  <td><?php echo e($pca->category_name); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_pca_amount_requested'); ?></td>
                  <td class="bold"><?php echo app_format_money($pca->amount_requested, $currency); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_pca_advance_required_date'); ?></td>
                  <td><?php echo $pca->advance_required_date ? _d($pca->advance_required_date) : ''; ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_pca_payment_priority'); ?></td>
                  <td><?php echo pur_pca_priority_label($pca->payment_priority); ?></td>
                </tr>
              </tbody>
            </table>

            <!-- 3. Expense / Items Details -->
            <h4 class="bold mtop20"><?php echo _l('pur_pca_section_3'); ?></h4>
            <?php if (count($items) == 0) { ?>
            <div class="alert alert-info"><?php echo _l('pur_pca_no_items'); ?></div>
            <?php } else { ?>
            <?php if ($amount_mismatch) { ?>
            <div class="alert alert-warning">
              <?php echo sprintf(
                  _l('pur_pca_items_total_mismatch'),
                  app_format_money($items_total, $currency),
                  app_format_money($pca->amount_requested, $currency)
              ); ?>
            </div>
            <?php } ?>
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th><?php echo _l('pur_pca_description'); ?></th>
                    <th class="text-right"><?php echo _l('pur_pca_amount'); ?></th>
                    <th><?php echo _l('pur_pca_vendor'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $item) { ?>
                  <tr>
                    <td><?php echo nl2br(e($item['description'])); ?></td>
                    <td class="text-right"><?php echo app_format_money($item['amount'], $currency); ?></td>
                    <td>
                      <?php if (!empty($item['vendor_id'])) { ?>
                      <a href="<?php echo admin_url('purchase/vendor/' . $item['vendor_id']); ?>"><?php echo e($item['vendor_company']); ?></a>
                      <?php } ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td class="text-right bold"><?php echo _l('pur_pca_items_total'); ?></td>
                    <td class="text-right bold"><?php echo app_format_money($items_total, $currency); ?></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <?php } ?>

            <!-- 4. Approvals -->
            <h4 class="bold mtop20"><?php echo _l('pur_pca_section_4'); ?></h4>
            <div class="table-responsive">
              <table class="table table-bordered" id="pca-approvals" data-advance-id="<?php echo $pca->id; ?>">
                <thead>
                  <tr>
                    <?php foreach ($roles as $role_lang) { ?>
                    <th class="text-center"><?php echo _l($role_lang); ?></th>
                    <?php } ?>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <?php foreach ($roles as $role_key => $role_lang) {
                        $approval = isset($approvals[$role_key]) ? $approvals[$role_key] : null; ?>
                    <td class="text-center" style="vertical-align:top;">
                      <?php if ($approval && (int) $approval['status'] === 2) { ?>
                      <span class="label label-success"><?php echo _l('pur_pca_approved'); ?></span>
                      <?php } elseif ($approval && (int) $approval['status'] === 3) { ?>
                      <span class="label label-danger"><?php echo _l('pur_pca_rejected'); ?></span>
                      <?php } else { ?>
                      <span class="label label-warning"><?php echo _l('pur_pca_pending'); ?></span>
                      <?php } ?>

                      <?php if ($approval && !empty($approval['staffid'])) { ?>
                      <br /><small><?php echo e(get_staff_full_name($approval['staffid'])); ?></small>
                      <?php } ?>
                      <?php if ($approval && !empty($approval['action_at'])) { ?>
                      <br /><small class="text-muted"><?php echo _dt($approval['action_at']); ?></small>
                      <?php } ?>
                      <?php if ($approval && !empty($approval['note'])) { ?>
                      <br /><small class="text-muted"><?php echo e($approval['note']); ?></small>
                      <?php } ?>

                      <?php if ($can_approve) { ?>
                      <div class="mtop10 noprint">
                        <input type="text" class="form-control input-sm pca-approval-note mbot5"
                               placeholder="<?php echo _l('pur_pca_approval_note'); ?>"
                               value="<?php echo $approval ? e($approval['note']) : ''; ?>">
                        <button type="button" class="btn btn-success btn-sm pca-approval-action"
                                data-role="<?php echo $role_key; ?>" data-status="2">
                          <?php echo _l('pur_pca_approve'); ?>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm pca-approval-action"
                                data-role="<?php echo $role_key; ?>" data-status="3">
                          <?php echo _l('pur_pca_reject'); ?>
                        </button>
                      </div>
                      <?php } ?>
                    </td>
                    <?php } ?>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Attachments -->
            <h4 class="bold mtop20"><?php echo _l('pur_pca_attachment'); ?></h4>
            <?php if (count($attachments) == 0) { ?>
            <p class="text-muted"><?php echo _l('no_attachments_found'); ?></p>
            <?php } else { ?>
            <div id="pca_pv_file">
              <?php foreach ($attachments as $file) {
                  $href = site_url(PURCHASE_PATH . 'petty_cash_advances/' . $file['rel_id'] . '/' . rawurlencode($file['file_name']));
                  if (!empty($file['external'])) {
                      $href = $file['external_link'];
                  } ?>
              <div class="mbot15 row inline-block full-width" data-attachment-id="<?php echo $file['id']; ?>">
                <div class="col-md-8">
                  <a href="#" onclick="pca_preview_file(<?php echo $file['id']; ?>, <?php echo $file['rel_id']; ?>); return false;"
                     class="mbot10 mright5 btn btn-success pull-left noprint" data-toggle="tooltip" title="<?php echo _l('preview_file'); ?>">
                    <i class="fa fa-eye"></i>
                  </a>
                  <div class="pull-left"><i class="<?php echo get_mime_class($file['filetype']); ?>"></i></div>
                  <a href="<?php echo $href; ?>" target="_blank" download><?php echo e($file['file_name']); ?></a>
                  <br /><small class="text-muted"><?php echo e($file['filetype']); ?></small>
                </div>
                <div class="col-md-4 text-right noprint">
                  <?php if ($file['staffid'] == get_staff_user_id() || is_admin()) { ?>
                  <a href="#" class="text-danger" onclick="pca_delete_attachment(<?php echo $file['id']; ?>); return false;"><i class="fa fa-times"></i></a>
                  <?php } ?>
                </div>
              </div>
              <?php } ?>
            </div>
            <div id="pca_file_data"></div>
            <?php } ?>

            <?php if (!empty($activity_logs)) { ?>
            <h4 class="bold mtop20 noprint"><?php echo _l('pur_bw_activity_log'); ?></h4>
            <ul class="list-unstyled noprint">
              <?php foreach ($activity_logs as $log) { ?>
              <li class="mbot5">
                <small class="text-muted"><?php echo _dt($log['date']); ?></small>
                &middot; <?php echo e($log['note']); ?>
                <?php if (!empty($log['staffid'])) { ?>
                &middot; <span class="text-muted"><?php echo e(get_staff_full_name($log['staffid'])); ?></span>
                <?php } ?>
              </li>
              <?php } ?>
            </ul>
            <?php } ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<?php require 'modules/purchase/assets/js/petty_cash_advance/petty_cash_advance_detail_js.php'; ?>
</body>
</html>
