<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Purchase Form - read only view plus approvals.
 *
 * Approval is the approver chain's decision, the same as it is for a Purchase Request
 * or a Purchase Order: only the approver the chain is currently waiting on sees the
 * Approve and Deny buttons, and purchase/approve_request re-checks that on the server.
 * The five role boxes in section 6 are the printed record on the form.
 */

$bw = $bid_waiver;

$purchase_types = pur_bid_waiver_purchase_types();

$base_currency = get_base_currency_pur();
$currency      = $base_currency;
if (!empty($bw->currency)) {
    $found = pur_get_currency_by_id($bw->currency);
    if ($found) {
        $currency = $found;
    }
}

// An approver chain configured for this document type owns the approval, exactly as
// it does for a Purchase Request or a Purchase Order. Where one exists the five role
// boxes in section 6 are a printed record only: the chain decides approve_status and
// bid_waiver_approve() refuses to record a role decision at all.
$chain_owns_approval = !empty($check_appr);

// THE IDENTITY GATE. Only the approver the chain is currently waiting on may act.
// check_approval_details() returns the first outstanding row while approvers remain,
// boolean true once all have approved and the string 'reject' after a rejection, so
// the staffid list has to be resolved defensively.
$pending_approvers = (isset($check_approve_status) && is_array($check_approve_status)
    && isset($check_approve_status['staffid']) && is_array($check_approve_status['staffid']))
    ? $check_approve_status['staffid']
    : [];

$my_staff_id = get_staff_user_id();

// Loose comparison is deliberate: pur_approval_details.staffid is a VARCHAR holding
// '27', and this is how the Purchase Request page compares it too.
$is_pending_approver = in_array($my_staff_id, $pending_approvers);
$my_step_is_a_signature = isset($get_staff_sign) && in_array($my_staff_id, $get_staff_sign);

// Holding the edit permission no longer approves anything on a chained form. It stays
// the gate only on installs with no chain configured, where the role boxes are still
// the only sign off there is.
$can_approve = $chain_owns_approval
    ? false
    : has_permission('purchase_bid_waiver', '', 'edit');

// Content that has been signed off must not change underneath the people who signed
// it. Any recorded decision locks the form, from either route, as does full approval.
$locked = (int) $bw->approve_status === 2;

if (!$locked) {
    foreach ($approvals as $approval) {
        if (in_array((int) $approval['status'], [2, 3], true)) {
            $locked = true;
            break;
        }
    }
}

if (!$locked && isset($list_approve_status)) {
    foreach ($list_approve_status as $chain_row) {
        if (in_array((int) $chain_row['approve'], [2, 3], true)) {
            $locked = true;
            break;
        }
    }
}

/** Render a yes/no tick for a stored checkbox column. */
function bw_tick($value)
{
    return !empty($value)
        ? '<i class="fa fa-check-square-o text-success"></i>'
        : '<i class="fa fa-square-o text-muted"></i>';
}

$waiver_reasons = [
    'wr_single_source'         => 'pur_bw_wr_single_source',
    'wr_existing_supplier'     => 'pur_bw_wr_existing_supplier',
    'wr_urgent_requirement'    => 'pur_bw_wr_urgent_requirement',
    'wr_continuation_contract' => 'pur_bw_wr_continuation_contract',
    'wr_quality_limitation'    => 'pur_bw_wr_quality_limitation',
    'wr_no_other_supplier'     => 'pur_bw_wr_no_other_supplier',
];

$selection_reasons = [
    'sr_lowest_price'            => 'pur_bw_sr_lowest_price',
    'sr_better_commercial_terms' => 'pur_bw_sr_better_commercial_terms',
    'sr_technically_compliant'   => 'pur_bw_sr_technically_compliant',
    'sr_previous_performance'    => 'pur_bw_sr_previous_performance',
    'sr_best_delivery'           => 'pur_bw_sr_best_delivery',
    'sr_quality_service'         => 'pur_bw_sr_quality_service',
];
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
                <h4 class="no-margin font-bold"><?php echo _l('pur_bid_waiver_form_title'); ?></h4>
                <p class="text-muted no-mbot">
                  <?php echo e($bw->request_no); ?> &middot; <?php echo pur_bid_waiver_status_label($bw->approve_status); ?>
                </p>
              </div>
              <div class="col-md-5 text-right">
                <a href="#" onclick="window.print(); return false;" class="btn btn-default noprint">
                  <i class="fa fa-print"></i> <?php echo _l('print'); ?>
                </a>
                <?php if (has_permission('purchase_bid_waiver', '', 'edit') && !$locked) { ?>
                <a href="<?php echo admin_url('purchase/bid_waiver/' . $bw->id); ?>" class="btn btn-warning noprint">
                  <i class="fa fa-pencil"></i> <?php echo _l('edit'); ?>
                </a>
                <?php } ?>
                <a href="<?php echo admin_url('purchase/bid_waivers'); ?>" class="btn btn-default noprint">
                  <?php echo _l('pur_bw_back'); ?>
                </a>
              </div>
            </div>

            <?php if ($locked) { ?>
            <div class="alert alert-info noprint"><?php echo _l('pur_bw_cannot_edit_approved'); ?></div>
            <?php } ?>

            <hr class="hr-panel-heading" />

            <!-- Document control -->
            <table class="table table-bordered mbot15">
              <thead>
                <tr>
                  <th><?php echo _l('pur_bw_doc_version'); ?></th>
                  <th><?php echo _l('pur_bw_doc_effective_date'); ?></th>
                  <th><?php echo _l('pur_bw_doc_revise'); ?></th>
                  <th><?php echo _l('pur_bw_doc_number'); ?></th>
                  <th><?php echo _l('pur_bw_doc_approved_by'); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><?php echo e($bw->doc_version); ?></td>
                  <td><?php echo $bw->doc_effective_date ? _d($bw->doc_effective_date) : ''; ?></td>
                  <?php // Revise holds the date of the most recent edit, empty on version 1.0 ?>
                  <td><?php echo $bw->doc_revise ? _d($bw->doc_revise) : ''; ?></td>
                  <td><?php echo e($bw->doc_number); ?></td>
                  <td>
                    <?php if (!empty($bw->doc_approved_by)) {
                        echo e($bw->doc_approved_by);
                    } else { ?>
                    <span class="text-muted"><?php echo _l('pur_bw_doc_approved_pending'); ?></span>
                    <?php } ?>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- 1 -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_1'); ?></h4>
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <td class="bold" style="width:30%;"><?php echo _l('pur_bw_request_no'); ?></td>
                  <td><?php echo e($bw->request_no); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_bw_department'); ?></td>
                  <td><?php echo e($bw->department_name); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_bw_requestor_name'); ?></td>
                  <td><?php echo $bw->requestor ? e(get_staff_full_name($bw->requestor)) : ''; ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_bw_budget_required_date'); ?></td>
                  <td><?php echo $bw->budget_required_date ? _d($bw->budget_required_date) : ''; ?></td>
                </tr>
              </tbody>
            </table>

            <!-- 2 -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_2'); ?></h4>
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <td class="bold" style="width:30%;"><?php echo _l('pur_bw_purpose_of_purchase'); ?></td>
                  <td><?php echo nl2br(e($bw->purpose_of_purchase)); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_bw_technical_specification'); ?></td>
                  <td><?php echo nl2br(e($bw->technical_specification)); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_bw_quantity_unit'); ?></td>
                  <td><?php echo e($bw->quantity_unit); ?></td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_bw_purchase_type'); ?></td>
                  <td><?php echo isset($purchase_types[$bw->purchase_type]) ? $purchase_types[$bw->purchase_type] : ''; ?></td>
                </tr>
              </tbody>
            </table>

            <!-- 3 -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_3'); ?></h4>
            <?php if (count($quotations) == 0) { ?>
            <div class="alert alert-info"><?php echo _l('pur_bw_no_quotations'); ?></div>
            <?php } else { ?>
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th><?php echo _l('pur_bw_vendor'); ?></th>
                    <th class="text-right"><?php echo _l('pur_bw_amount'); ?></th>
                    <th><?php echo _l('pur_bw_currency'); ?></th>
                    <th><?php echo _l('pur_bw_lead_time'); ?></th>
                    <th><?php echo _l('pur_bw_payment_terms'); ?></th>
                    <th><?php echo _l('pur_bw_attachment'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($quotations as $quotation) {
                      $line_currency = $base_currency;
                      if (!empty($quotation['currency'])) {
                          $found_line = pur_get_currency_by_id($quotation['currency']);
                          if ($found_line) {
                              $line_currency = $found_line;
                          }
                      } ?>
                  <tr>
                    <td>
                      <?php if (!empty($quotation['vendor_id'])) { ?>
                      <a href="<?php echo admin_url('purchase/vendor/' . $quotation['vendor_id']); ?>"><?php echo e($quotation['vendor_company']); ?></a>
                      <?php } ?>
                    </td>
                    <td class="text-right"><?php echo app_format_money($quotation['amount'], $line_currency); ?></td>
                    <td><?php echo e($line_currency->name); ?></td>
                    <td><?php echo e($quotation['lead_time']); ?></td>
                    <td><?php echo e($quotation['payment_terms']); ?></td>
                    <td>
                      <?php if (!empty($quotation['attachment'])) { ?>
                      <a href="<?php echo site_url(PURCHASE_PATH . 'bid_waivers/' . $bw->id . '/quotations/' . rawurlencode($quotation['attachment'])); ?>" target="_blank" download>
                        <i class="fa fa-paperclip"></i> <?php echo e($quotation['attachment']); ?>
                      </a>
                      <?php } ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <?php } ?>

            <!-- 4 -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_4'); ?></h4>
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <td class="bold" style="width:60%;"><?php echo _l('pur_bw_two_or_more_quotations'); ?></td>
                  <td>
                    <?php
                    if ($bw->two_or_more_quotations === null) {
                        echo '&mdash;';
                    } else {
                        echo ((int) $bw->two_or_more_quotations === 1) ? _l('pur_bw_yes') : _l('pur_bw_no');
                    }
                    ?>
                  </td>
                </tr>
                <?php foreach ($waiver_reasons as $field => $lang_key) { ?>
                <tr>
                  <td><?php echo _l($lang_key); ?></td>
                  <td><?php echo bw_tick($bw->$field); ?></td>
                </tr>
                <?php } ?>
                <tr>
                  <td><?php echo _l('pur_bw_other'); ?></td>
                  <td><?php echo bw_tick($bw->wr_other); ?> <?php echo e($bw->wr_other_text); ?></td>
                </tr>
              </tbody>
            </table>

            <!-- 5 -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_5'); ?></h4>
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <td class="bold" style="width:60%;"><?php echo _l('pur_bw_recommended_vendor'); ?></td>
                  <td>
                    <?php if (!empty($bw->recommended_vendor)) { ?>
                    <a href="<?php echo admin_url('purchase/vendor/' . $bw->recommended_vendor); ?>"><?php echo e($bw->vendor_company); ?></a>
                    <?php } ?>
                  </td>
                </tr>
                <tr>
                  <td class="bold"><?php echo _l('pur_bw_total_amount'); ?></td>
                  <td class="bold"><?php echo app_format_money($bw->total_amount, $currency); ?></td>
                </tr>
                <?php foreach ($selection_reasons as $field => $lang_key) { ?>
                <tr>
                  <td><?php echo _l($lang_key); ?></td>
                  <td><?php echo bw_tick($bw->$field); ?></td>
                </tr>
                <?php } ?>
                <tr>
                  <td><?php echo _l('pur_bw_other'); ?></td>
                  <td><?php echo bw_tick($bw->sr_other); ?> <?php echo e($bw->sr_other_text); ?></td>
                </tr>
              </tbody>
            </table>

            <!-- 6. Approvals -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_6'); ?></h4>

            <?php
                // One entry per approver the chain actually requires. Nothing is emitted
                // for a role the chain does not reach.
                $approval_columns = pur_bid_waiver_approval_columns($bw->id, $bw->total_amount);

                $sig_dir = PURCHASE_MODULE_UPLOAD_FOLDER . '/bid_waivers/signature/' . $bw->id;
                $sig_web = PURCHASE_PATH . 'bid_waivers/signature/' . $bw->id;
            ?>

            <?php if ($chain_owns_approval) { ?>
            <?php // Laid out the way the Purchase Request and Purchase Order pages lay
                  // out theirs: one block per approver with their name, position and an
                  // approved or rejected stamp. ?>
            <p class="bold mtop15"><?php echo _l('pur_approval_infor'); ?></p>
            <hr class="hr-panel-heading" />

            <div class="row mbot15">
              <?php foreach ($approval_columns as $col) { ?>
              <div class="col-md-3 text-center">
                <?php // The heading from the printed form, so the block is still
                      // recognisable as Initiator, Line Manager and so on. ?>
                <small class="text-muted text-uppercase"><?php echo e($col['label']); ?></small>

                <p class="text-uppercase bold no-mtop mbot0"><?php echo e($col['name']); ?></p>
                <?php if ($col['position'] !== '') { ?>
                <span class="text-muted"><?php echo e($col['position']); ?></span><br />
                <?php } ?>

                <?php if ($col['status'] === 2) { ?>
                <img src="<?php echo site_url(PURCHASE_PATH . 'approval/approved.png'); ?>" class="img_style" alt="<?php echo _l('pur_bw_approved'); ?>" />
                <?php } elseif ($col['status'] === 3) { ?>
                <img src="<?php echo site_url(PURCHASE_PATH . 'approval/rejected.png'); ?>" class="img_style" alt="<?php echo _l('pur_bw_rejected'); ?>" />
                <?php } elseif ($col['is_active']) { ?>
                <br /><span class="label label-info"><?php echo _l('pur_bw_awaiting_this_approver'); ?></span>
                <?php } else { ?>
                <br /><span class="label label-warning"><?php echo _l('pur_bw_pending'); ?></span>
                <?php } ?>

                <?php // Signature, when that approver's step is a sign rather than an approve ?>
                <?php if ($col['row_id'] && is_file($sig_dir . '/signature_' . $col['row_id'] . '.png')) { ?>
                <br /><img src="<?php echo site_url($sig_web . '/signature_' . $col['row_id'] . '.png'); ?>" class="img_style" alt="" />
                <?php } elseif ($col['row_id'] && is_file($sig_dir . '/signature_' . $col['row_id'] . '.jpg')) { ?>
                <br /><img src="<?php echo site_url($sig_web . '/signature_' . $col['row_id'] . '.jpg'); ?>" class="img_style" alt="" />
                <?php } ?>

                <?php if (!empty($col['note'])) { ?>
                <p class="mtop10"><?php echo e($col['note']); ?></p>
                <?php } ?>
                <?php if (!empty($col['date'])) { ?>
                <p class="bold text-<?php echo $col['status'] === 2 ? 'success' : ($col['status'] === 3 ? 'danger' : 'muted'); ?>">
                  <?php echo _dt($col['date']); ?>
                </p>
                <?php } ?>
              </div>
              <?php } ?>
            </div>
            <div class="clearfix"></div>

            <div class="noprint mbot20">
              <?php
                  // Draft or rejected may be sent. Approved never shows the button again.
                  $can_send_for_approval = pur_can_send_for_approval(
                      $bw->approve_status,
                      $check_approve_status,
                      isset($appr_setting) ? $appr_setting : null,
                      isset($list_approve_status) ? $list_approve_status : [],
                      [1, 3]
                  );

                  // Only the person who raised the form, or an admin, may resubmit it
                  // after a rejection.
                  if ($can_send_for_approval && (int) $bw->approve_status === 3
                      && !($bw->requestor == $my_staff_id || $bw->created_by == $my_staff_id || is_admin())) {
                      $can_send_for_approval = false;
                  }

                  if ($can_send_for_approval) { ?>
              <a href="#" class="btn btn-success" data-loading-text="<?php echo _l('wait_text'); ?>"
                 onclick="bw_send_request_approve(); return false;">
                <?php echo _l('send_request_approve_pur'); ?>
              </a>
              <?php } ?>

              <?php if ($is_pending_approver && !$my_step_is_a_signature && in_array((int) $bw->approve_status, [1, 3], true)) { ?>
              <div class="btn-group">
                <a href="#" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <?php echo _l('approve'); ?><span class="caret"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-right ul_style">
                  <li>
                    <div class="col-md-12"><?php echo render_textarea('reason', 'pur_bw_approval_note'); ?></div>
                  </li>
                  <li>
                    <div class="row text-right col-md-12 pad_right_0 mbot15">
                      <a href="#" class="btn btn-success mright15" data-loading-text="<?php echo _l('wait_text'); ?>"
                         onclick="bw_approve_request(); return false;"><?php echo _l('pur_bw_approve'); ?></a>
                      <a href="#" class="btn btn-warning" data-loading-text="<?php echo _l('wait_text'); ?>"
                         onclick="bw_deny_request(); return false;"><?php echo _l('pur_bw_reject'); ?></a>
                    </div>
                  </li>
                </ul>
              </div>
              <?php } elseif (!$is_pending_approver && count($pending_approvers) > 0) { ?>
              <?php // Named plainly so it is obvious who the form is waiting on, rather
                    // than simply showing no button and looking broken. ?>
              <p class="text-muted no-mbot">
                <i class="fa fa-clock-o"></i>
                <?php
                    $waiting_on = [];
                    foreach ($pending_approvers as $pending_staff_id) {
                        $waiting_on[] = get_staff_full_name($pending_staff_id);
                    }
                    echo _l('pur_bw_awaiting_approver') . ' ' . e(implode(', ', $waiting_on));
                ?>
              </p>
              <?php } ?>
            </div>
            <div class="clearfix"></div>
            <?php
                // Deliberately no "CEO approval is required above 50K" note here. The
                // chain expresses a threshold as the amount on each approver level and
                // send_request_approve() has already applied it, so the sentence off the
                // printed form describes a rule this document no longer runs on. It is
                // still shown below for installs with no chain, where the CEO role box
                // is real and the threshold is what governs it.
            ?>

            <?php } else { ?>
            <?php // No chain configured. The five printed role boxes are all these
                  // installs have to sign with, so they are kept exactly as they were. ?>
            <div class="alert alert-warning noprint"><?php echo _l('pur_bw_no_approval_chain'); ?></div>

            <p class="text-muted">
              <?php echo sprintf(_l('pur_bw_ceo_threshold_note'), app_format_money(pur_bid_waiver_ceo_threshold(), $currency)); ?>
            </p>

            <div class="table-responsive">
              <table class="table table-bordered" id="bw-approvals" data-bid-waiver-id="<?php echo $bw->id; ?>">
                <thead>
                  <tr>
                    <?php foreach ($approval_columns as $col) { ?>
                    <th class="text-center">
                      <?php echo e($col['label']); ?>
                      <?php if ($col['threshold']) { ?>
                      <br /><small class="text-muted">&gt; <?php echo app_format_money(pur_bid_waiver_ceo_threshold(), $currency); ?></small>
                      <?php } ?>
                    </th>
                    <?php } ?>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <?php foreach ($approval_columns as $col) { ?>
                    <td class="text-center" style="vertical-align:top;">
                      <?php if (!$col['applies']) { ?>
                      <span class="text-muted tw-text-sm"><?php echo _l('pur_bw_ceo_not_required'); ?></span>
                      <?php } else { ?>

                        <?php if ($col['status'] === 2) { ?>
                        <img src="<?php echo site_url(PURCHASE_PATH . 'approval/approved.png'); ?>" class="img_style" alt="<?php echo _l('pur_bw_approved'); ?>" />
                        <?php } elseif ($col['status'] === 3) { ?>
                        <img src="<?php echo site_url(PURCHASE_PATH . 'approval/rejected.png'); ?>" class="img_style" alt="<?php echo _l('pur_bw_rejected'); ?>" />
                        <?php } elseif ($col['is_active']) { ?>
                        <span class="label label-info"><?php echo _l('pur_bw_awaiting_this_approver'); ?></span>
                        <?php } else { ?>
                        <span class="label label-warning"><?php echo _l('pur_bw_pending'); ?></span>
                        <?php } ?>

                        <?php // Whoever clicked, which is the only name a role row records ?>
                        <?php if ($col['acted_by'] !== '') { ?>
                        <br /><small><?php echo e($col['acted_by']); ?></small>
                        <?php } ?>

                        <?php if (!empty($col['date'])) { ?>
                        <br /><small class="text-muted"><?php echo _dt($col['date']); ?></small>
                        <?php } ?>
                        <?php if (!empty($col['note'])) { ?>
                        <br /><small class="text-muted"><?php echo e($col['note']); ?></small>
                        <?php } ?>

                        <?php if ($can_approve) { ?>
                        <div class="mtop10 noprint">
                          <input type="text" class="form-control input-sm bw-approval-note mbot5"
                                 placeholder="<?php echo _l('pur_bw_approval_note'); ?>"
                                 value="<?php echo e($col['note']); ?>">
                          <button type="button" class="btn btn-success btn-sm bw-approval-action"
                                  data-role="<?php echo $col['role_key']; ?>" data-status="2">
                            <?php echo _l('pur_bw_approve'); ?>
                          </button>
                          <button type="button" class="btn btn-danger btn-sm bw-approval-action"
                                  data-role="<?php echo $col['role_key']; ?>" data-status="3">
                            <?php echo _l('pur_bw_reject'); ?>
                          </button>
                        </div>
                        <?php } ?>
                      <?php } ?>
                    </td>
                    <?php } ?>
                  </tr>
                </tbody>
              </table>
            </div>
            <?php } // end: no approval chain configured ?>

            <!-- Attachments -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_attachment'); ?></h4>
            <?php if (count($attachments) == 0) { ?>
            <p class="text-muted"><?php echo _l('no_attachments_found'); ?></p>
            <?php } else { ?>
            <div id="bid_waiver_pv_file">
              <?php foreach ($attachments as $file) {
                  $href = site_url(PURCHASE_PATH . 'bid_waivers/' . $file['rel_id'] . '/' . rawurlencode($file['file_name']));
                  if (!empty($file['external'])) {
                      $href = $file['external_link'];
                  } ?>
              <div class="mbot15 row inline-block full-width" data-attachment-id="<?php echo $file['id']; ?>">
                <div class="col-md-8">
                  <a href="#" onclick="bw_preview_file(<?php echo $file['id']; ?>, <?php echo $file['rel_id']; ?>); return false;"
                     class="mbot10 mright5 btn btn-success pull-left noprint" data-toggle="tooltip" title="<?php echo _l('preview_file'); ?>">
                    <i class="fa fa-eye"></i>
                  </a>
                  <div class="pull-left"><i class="<?php echo get_mime_class($file['filetype']); ?>"></i></div>
                  <a href="<?php echo $href; ?>" target="_blank" download><?php echo e($file['file_name']); ?></a>
                  <br /><small class="text-muted"><?php echo e($file['filetype']); ?></small>
                </div>
                <div class="col-md-4 text-right noprint">
                  <?php if ($file['staffid'] == get_staff_user_id() || is_admin()) { ?>
                  <a href="#" class="text-danger" onclick="bw_delete_attachment(<?php echo $file['id']; ?>); return false;"><i class="fa fa-times"></i></a>
                  <?php } ?>
                </div>
              </div>
              <?php } ?>
            </div>
            <div id="bid_waiver_file_data"></div>
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
<?php require 'modules/purchase/assets/js/bid_waiver/bid_waiver_detail_js.php'; ?>
</body>
</html>
