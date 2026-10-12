<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Procurement Justification & Bid Waiver Form - create / edit.
 *
 * Section numbering follows the printed form so the two can be read side by side.
 */

$bw = isset($bid_waiver) ? $bid_waiver : null;

/** Value for a plain column, falling back to a default when creating. */
function bw_val($bw, $field, $default = '')
{
    return $bw && isset($bw->$field) && $bw->$field !== null ? $bw->$field : $default;
}

/** Whether a checkbox column is ticked. */
function bw_checked($bw, $field)
{
    return $bw && !empty($bw->$field) ? ' checked' : '';
}

$department_options = [];
foreach ($departments as $department) {
    $department_options[] = ['id' => $department['departmentid'], 'name' => $department['name']];
}

$staff_options = [];
foreach ($staff as $member) {
    $staff_options[] = ['id' => $member['staffid'], 'name' => get_staff_full_name($member['staffid'])];
}

$vendor_options = [];
foreach ($vendors as $vendor) {
    $vendor_options[] = ['id' => $vendor['userid'], 'name' => $vendor['company']];
}

$currency_options = [];
foreach ($currencies as $currency) {
    $currency_options[] = ['id' => $currency['id'], 'name' => $currency['name']];
}

$purchase_type_options = [];
foreach (pur_bid_waiver_purchase_types() as $type_key => $type_label) {
    $purchase_type_options[] = ['id' => $type_key, 'name' => $type_label];
}

// The waiver reason checkboxes and the vendor selection reasons are both plain
// column => language key maps, so they render from a loop instead of being spelled
// out twice each.
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

$existing_quotations = isset($quotations) ? $quotations : [];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <?php echo form_open_multipart($this->uri->uri_string(), ['id' => 'bid-waiver-form']); ?>
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div class="row mbot15">
              <div class="col-md-8">
                <h4 class="no-margin font-bold"><?php echo _l('pur_bid_waiver_form_title'); ?></h4>
                <?php
                // format_organization_info() returns ready made markup (bold company name,
                // <br /> separated address, VAT number). It must not be escaped or the tags
                // print as literal text.
                ?>
                <div class="text-muted no-mbot"><?php echo format_organization_info(); ?></div>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('purchase/bid_waivers'); ?>" class="btn btn-default">
                  <?php echo _l('cancel'); ?>
                </a>
              </div>
            </div>

            <hr class="hr-panel-heading" />

            <?php
            // Document control strip. Every field here is maintained by the system, so
            // they render disabled. A disabled input is not submitted at all, and the
            // model also drops these keys from the writable column list, so neither a
            // stray edit nor a crafted POST can change them.
            //
            //   Ver         1.0 on create, then 2.0, 3.0 on each edit
            //   Effective   the date version 1.0 was raised, then fixed
            //   Revise      the date of the most recent edit, blank until first edit
            //   Document No the controlled form code, from a purchase option
            //   Approved By stamped once every required approval role has approved
            $doc_version   = $bw ? $bw->doc_version : '1.0';
            $doc_next      = $bw ? pur_bid_waiver_next_version($bw->doc_version) : '1.0';
            $doc_effective = $bw && $bw->doc_effective_date ? _d($bw->doc_effective_date) : _d(date('Y-m-d'));
            $doc_revise    = $bw && $bw->doc_revise ? $bw->doc_revise : '';
            $doc_number    = $bw && $bw->doc_number !== '' && $bw->doc_number !== null
                             ? $bw->doc_number
                             : pur_bid_waiver_doc_number();
            $doc_approved  = $bw && $bw->doc_approved_by ? $bw->doc_approved_by : '';
            ?>
            <div class="row">
              <div class="col-md-2">
                <label class="control-label"><?php echo _l('pur_bw_doc_version'); ?></label>
                <input type="text" class="form-control" value="<?php echo e($doc_version); ?>" disabled>
                <?php if ($bw) { ?>
                <small class="text-muted"><?php echo sprintf(_l('pur_bw_doc_version_next'), e($doc_next)); ?></small>
                <?php } ?>
              </div>
              <div class="col-md-3">
                <label class="control-label"><?php echo _l('pur_bw_doc_effective_date'); ?></label>
                <input type="text" class="form-control" value="<?php echo e($doc_effective); ?>" disabled>
              </div>
              <div class="col-md-2">
                <label class="control-label"><?php echo _l('pur_bw_doc_revise'); ?></label>
                <input type="text" class="form-control" value="<?php echo $doc_revise ? e(_d($doc_revise)) : ''; ?>" disabled>
              </div>
              <div class="col-md-2">
                <label class="control-label"><?php echo _l('pur_bw_doc_number'); ?></label>
                <input type="text" class="form-control" value="<?php echo e($doc_number); ?>" disabled>
              </div>
              <div class="col-md-3">
                <label class="control-label"><?php echo _l('pur_bw_doc_approved_by'); ?></label>
                <input type="text" class="form-control" value="<?php echo e($doc_approved); ?>"
                       placeholder="<?php echo _l('pur_bw_doc_approved_pending'); ?>" disabled>
              </div>
            </div>

            <!-- 1. Purchase Requirement Details -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_1'); ?></h4>
            <hr class="hr-panel-heading" />
            <div class="row">
              <div class="col-md-3">
                <label class="control-label"><?php echo _l('pur_bw_request_no'); ?></label>
                <?php
                // Generated on save, so it is shown read-only rather than posted back -
                // letting it be edited would allow duplicates
                $request_no_display = $bw ? $bw->request_no : (isset($next_request_no) ? $next_request_no : '');
                ?>
                <input type="text" class="form-control" value="<?php echo e($request_no_display); ?>" disabled>
              </div>
              <div class="col-md-3">
                <?php echo render_select('department', $department_options, ['id', 'name'], 'pur_bw_department', bw_val($bw, 'department'), ['required' => 'true']); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_select('requestor', $staff_options, ['id', 'name'], 'pur_bw_requestor_name', bw_val($bw, 'requestor'), ['required' => 'true']); ?>
              </div>
              <div class="col-md-3">
                <?php
                $budget_date = bw_val($bw, 'budget_required_date');
                echo render_date_input('budget_required_date', 'pur_bw_budget_required_date', $budget_date ? _d($budget_date) : '');
                ?>
              </div>
            </div>

            <!-- 2. Business Need & Background -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_2'); ?></h4>
            <hr class="hr-panel-heading" />
            <div class="row">
              <div class="col-md-12">
                <?php echo render_textarea('purpose_of_purchase', 'pur_bw_purpose_of_purchase', bw_val($bw, 'purpose_of_purchase'), ['rows' => 3]); ?>
              </div>
              <div class="col-md-12">
                <?php echo render_textarea('technical_specification', 'pur_bw_technical_specification', bw_val($bw, 'technical_specification'), ['rows' => 3]); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('quantity_unit', 'pur_bw_quantity_unit', bw_val($bw, 'quantity_unit')); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_select('purchase_type', $purchase_type_options, ['id', 'name'], 'pur_bw_purchase_type', bw_val($bw, 'purchase_type')); ?>
              </div>
            </div>

            <!-- 3. Quotation & Commercial Evaluation -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_3'); ?></h4>
            <p class="text-muted"><?php echo _l('pur_bw_quotation_hint'); ?></p>
            <div class="table-responsive">
              <table class="table table-bordered" id="bw-quotations">
                <thead>
                  <tr>
                    <th style="width:24%;"><?php echo _l('pur_bw_vendor'); ?></th>
                    <th style="width:13%;"><?php echo _l('pur_bw_amount'); ?></th>
                    <th style="width:13%;"><?php echo _l('pur_bw_currency'); ?></th>
                    <th style="width:14%;"><?php echo _l('pur_bw_lead_time'); ?></th>
                    <th style="width:16%;"><?php echo _l('pur_bw_payment_terms'); ?></th>
                    <th style="width:16%;"><?php echo _l('pur_bw_attachment'); ?></th>
                    <th style="width:4%;"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Existing rows are rendered server side; the "add" button clones the
                  // template held in #bw-quotation-template below.
                  $row_index = 0;
                  foreach ($existing_quotations as $quotation) { ?>
                  <tr class="bw-quotation-row">
                    <td>
                      <?php // include_blank stays on: without it the first vendor is preselected and an empty row would look filled in ?>
                      <?php echo render_select('quotations[' . $row_index . '][vendor_id]', $vendor_options, ['id', 'name'], '', $quotation['vendor_id']); ?>
                    </td>
                    <td>
                      <input type="number" step="any" class="form-control bw-quotation-amount" name="quotations[<?php echo $row_index; ?>][amount]" value="<?php echo e($quotation['amount']); ?>">
                    </td>
                    <td>
                      <?php echo render_select('quotations[' . $row_index . '][currency]', $currency_options, ['id', 'name'], '', $quotation['currency']); ?>
                    </td>
                    <td>
                      <input type="text" class="form-control" name="quotations[<?php echo $row_index; ?>][lead_time]" value="<?php echo e($quotation['lead_time']); ?>">
                    </td>
                    <td>
                      <input type="text" class="form-control" name="quotations[<?php echo $row_index; ?>][payment_terms]" value="<?php echo e($quotation['payment_terms']); ?>">
                    </td>
                    <td>
                      <input type="file" class="form-control" name="quotation_attachments[<?php echo $row_index; ?>]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,application/pdf">
                      <?php if (!empty($quotation['attachment'])) { ?>
                      <?php // Round-tripped so replacing the rows on save does not lose the file ?>
                      <input type="hidden" name="quotations[<?php echo $row_index; ?>][attachment_existing]" value="<?php echo e($quotation['attachment']); ?>">
                      <a href="<?php echo site_url(PURCHASE_PATH . 'bid_waivers/' . $bw->id . '/quotations/' . rawurlencode($quotation['attachment'])); ?>" target="_blank" class="text-muted tw-text-sm" download>
                        <i class="fa fa-paperclip"></i> <?php echo e($quotation['attachment']); ?>
                      </a>
                      <?php } ?>
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-danger btn-icon bw-remove-quotation"><i class="fa fa-remove"></i></button>
                    </td>
                  </tr>
                  <?php $row_index++;
                  } ?>
                </tbody>
              </table>
            </div>
            <button type="button" class="btn btn-default" id="bw-add-quotation">
              <i class="fa fa-plus"></i> <?php echo _l('pur_bw_add_quotation'); ?>
            </button>

            <!-- 4. Bid Waiver Assessment -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_4'); ?></h4>
            <hr class="hr-panel-heading" />
            <div class="row">
              <div class="col-md-12">
                <p class="bold"><?php echo _l('pur_bw_two_or_more_quotations'); ?></p>
                <?php
                // Stored as 1 / 0 / NULL. NULL means unanswered, which is why there is no
                // default selection here.
                $two_or_more = $bw && $bw->two_or_more_quotations !== null ? (int) $bw->two_or_more_quotations : null;
                ?>
                <label class="radio radio-inline radio-primary">
                  <input type="radio" name="two_or_more_quotations" value="1" id="bw-quotations-yes"<?php echo $two_or_more === 1 ? ' checked' : ''; ?>>
                  <label for="bw-quotations-yes"><?php echo _l('pur_bw_yes'); ?></label>
                </label>
                <label class="radio radio-inline radio-primary">
                  <input type="radio" name="two_or_more_quotations" value="0" id="bw-quotations-no"<?php echo $two_or_more === 0 ? ' checked' : ''; ?>>
                  <label for="bw-quotations-no"><?php echo _l('pur_bw_no'); ?></label>
                </label>
              </div>

              <?php
              // The reasons explain why the bid was waived, so they are only asked for
              // when two or more comparable quotations were NOT obtained - answer No.
              // Answering Yes means the purchase was competitively quoted and there is
              // nothing to waive.
              //
              // Hidden on a new form too, where the answer is still null: nothing has
              // been waived until someone says so. Must agree with OPEN_REASONS_WHEN in
              // bid_waiver_js.php, which owns the same decision for the live toggle.
              ?>
              <div class="col-md-12 mtop15" id="bw-waiver-reasons"<?php echo $two_or_more === 0 ? '' : ' style="display:none;"'; ?>>
                <div class="panel_s no-mbot">
                  <div class="panel-body bg-light">
                    <p class="bold"><?php echo _l('pur_bw_waiver_reasons'); ?></p>
                    <div class="row">
                      <?php foreach ($waiver_reasons as $field => $lang_key) { ?>
                      <div class="col-md-6">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="1"<?php echo bw_checked($bw, $field); ?>>
                          <label for="<?php echo $field; ?>"><?php echo _l($lang_key); ?></label>
                        </div>
                      </div>
                      <?php } ?>
                      <div class="col-md-6">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="wr_other" id="wr_other" value="1"<?php echo bw_checked($bw, 'wr_other'); ?>>
                          <label for="wr_other"><?php echo _l('pur_bw_other'); ?></label>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <input type="text" class="form-control" name="wr_other_text" id="wr_other_text"
                               placeholder="<?php echo _l('pur_bw_other_specify'); ?>"
                               value="<?php echo e(bw_val($bw, 'wr_other_text')); ?>">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 5. Vendor Selection & Justification -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_section_5'); ?></h4>
            <hr class="hr-panel-heading" />
            <div class="row">
              <div class="col-md-6">
                <?php echo render_select('recommended_vendor', $vendor_options, ['id', 'name'], 'pur_bw_recommended_vendor', bw_val($bw, 'recommended_vendor')); ?>
              </div>
              <div class="col-md-3">
                <?php
                // Drives whether the CEO approval box applies, so it is captured
                // explicitly rather than summed from the quotation lines
                echo render_input('total_amount', 'pur_bw_total_amount', bw_val($bw, 'total_amount', '0.00'), 'number', ['step' => 'any']);
                ?>
              </div>
              <div class="col-md-3">
                <?php
                $selected_currency = bw_val($bw, 'currency');
                if ($selected_currency === '' || $selected_currency === null) {
                    foreach ($currencies as $currency) {
                        if ($currency['isdefault'] == 1) {
                            $selected_currency = $currency['id'];
                        }
                    }
                }
                echo render_select('currency', $currency_options, ['id', 'name'], 'pur_bw_currency', $selected_currency);
                ?>
              </div>

              <div class="col-md-12">
                <p class="bold"><?php echo _l('pur_bw_selection_reasons'); ?></p>
                <div class="row">
                  <?php foreach ($selection_reasons as $field => $lang_key) { ?>
                  <div class="col-md-6">
                    <div class="checkbox checkbox-primary">
                      <input type="checkbox" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="1"<?php echo bw_checked($bw, $field); ?>>
                      <label for="<?php echo $field; ?>"><?php echo _l($lang_key); ?></label>
                    </div>
                  </div>
                  <?php } ?>
                  <div class="col-md-6">
                    <div class="checkbox checkbox-primary">
                      <input type="checkbox" name="sr_other" id="sr_other" value="1"<?php echo bw_checked($bw, 'sr_other'); ?>>
                      <label for="sr_other"><?php echo _l('pur_bw_other'); ?></label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control" name="sr_other_text" id="sr_other_text"
                           placeholder="<?php echo _l('pur_bw_other_specify'); ?>"
                           value="<?php echo e(bw_val($bw, 'sr_other_text')); ?>">
                  </div>
                </div>
              </div>
            </div>

            <!-- Attachments -->
            <h4 class="bold mtop20"><?php echo _l('pur_bw_attachment'); ?></h4>
            <hr class="hr-panel-heading" />
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <div class="input-group">
                    <input type="file" extension="jpg,png,pdf,doc,docx,xls,xlsx,zip,rar" filesize="67108864"
                           class="form-control" name="attachments[0]"
                           accept=".jpg,.png,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,image/jpeg,image/png,application/pdf">
                    <span class="input-group-btn">
                      <button class="btn btn-default add_more_attachments" data-max="6" type="button"><i class="fa fa-plus"></i></button>
                    </span>
                  </div>
                </div>
              </div>

              <?php if ($bw && !empty($attachments)) { ?>
              <div class="col-md-12" id="bid_waiver_pv_file">
                <hr />
                <?php foreach ($attachments as $file) {
                    $href = site_url(PURCHASE_PATH . 'bid_waivers/' . $file['rel_id'] . '/' . rawurlencode($file['file_name']));
                    if (!empty($file['external'])) {
                        $href = $file['external_link'];
                    } ?>
                <div class="mbot15 row inline-block full-width" data-attachment-id="<?php echo $file['id']; ?>">
                  <div class="col-md-8">
                    <a href="#" onclick="bw_preview_file(<?php echo $file['id']; ?>, <?php echo $file['rel_id']; ?>); return false;"
                       class="mbot10 mright5 btn btn-success pull-left" data-toggle="tooltip" title="<?php echo _l('preview_file'); ?>">
                      <i class="fa fa-eye"></i>
                    </a>
                    <div class="pull-left"><i class="<?php echo get_mime_class($file['filetype']); ?>"></i></div>
                    <a href="<?php echo $href; ?>" target="_blank" download><?php echo e($file['file_name']); ?></a>
                    <br /><small class="text-muted"><?php echo e($file['filetype']); ?></small>
                  </div>
                  <div class="col-md-4 text-right">
                    <?php if ($file['staffid'] == get_staff_user_id() || is_admin()) { ?>
                    <a href="#" class="text-danger" onclick="bw_delete_attachment(<?php echo $file['id']; ?>); return false;"><i class="fa fa-times"></i></a>
                    <?php } ?>
                  </div>
                </div>
                <?php } ?>
              </div>
              <div id="bid_waiver_file_data"></div>
              <?php } ?>
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="btn-bottom-toolbar text-right">
      <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
    </div>

    <?php echo form_close(); ?>
  </div>
</div>

<!-- Row template for the quotation repeater. Kept out of the form so its inputs are
     never submitted, and __INDEX__ is replaced with the next free row number. -->
<script type="text/html" id="bw-quotation-template">
  <tr class="bw-quotation-row">
    <td><?php echo str_replace("\n", '', render_select('quotations[__INDEX__][vendor_id]', $vendor_options, ['id', 'name'], '', '')); ?></td>
    <td><input type="number" step="any" class="form-control bw-quotation-amount" name="quotations[__INDEX__][amount]"></td>
    <td><?php echo str_replace("\n", '', render_select('quotations[__INDEX__][currency]', $currency_options, ['id', 'name'], '', $selected_currency)); ?></td>
    <td><input type="text" class="form-control" name="quotations[__INDEX__][lead_time]"></td>
    <td><input type="text" class="form-control" name="quotations[__INDEX__][payment_terms]"></td>
    <td><input type="file" class="form-control" name="quotation_attachments[__INDEX__]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,application/pdf"></td>
    <td class="text-center"><button type="button" class="btn btn-danger btn-icon bw-remove-quotation"><i class="fa fa-remove"></i></button></td>
  </tr>
</script>

<?php init_tail(); ?>
<?php require 'modules/purchase/assets/js/bid_waiver/bid_waiver_js.php'; ?>
</body>
</html>
