<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Petty Cash Advance Request - create / edit.
 *
 * Section numbering follows the printed form so the two can be read side by side.
 */

$pca = isset($advance) ? $advance : null;

/** Value for a column, with a fallback when creating. */
function pca_val($pca, $field, $default = '')
{
    return $pca && isset($pca->$field) && $pca->$field !== null ? $pca->$field : $default;
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

$category_options = [];
foreach ($categories as $category) {
    $category_options[] = ['id' => $category['id'], 'name' => $category['name']];
}

$existing_items = isset($items) ? $items : [];

// Default currency for a new request
$selected_currency = pca_val($pca, 'currency');
if ($selected_currency === '' || $selected_currency === null) {
    foreach ($currencies as $currency) {
        if ($currency['isdefault'] == 1) {
            $selected_currency = $currency['id'];
        }
    }
}
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <?php echo form_open_multipart($this->uri->uri_string(), ['id' => 'petty-cash-advance-form']); ?>
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div class="row mbot15">
              <div class="col-md-8">
                <h4 class="no-margin font-bold"><?php echo _l('pur_pca_form_title'); ?></h4>
                <?php
                // format_organization_info() returns ready made markup, so it must not
                // be escaped or the tags print as literal text
                ?>
                <div class="text-muted no-mbot"><?php echo format_organization_info(); ?></div>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('purchase/petty_cash_advances'); ?>" class="btn btn-default">
                  <?php echo _l('cancel'); ?>
                </a>
              </div>
            </div>

            <hr class="hr-panel-heading" />

            <?php
            // Document control strip. Every field is system maintained and rendered
            // disabled: a disabled input is not submitted, and the model also drops
            // these keys from the writable column list.
            $doc_version   = $pca ? $pca->doc_version : '1.0';
            $doc_next      = $pca ? pur_doc_next_version($pca->doc_version) : '1.0';
            $doc_effective = $pca && $pca->doc_effective_date ? _d($pca->doc_effective_date) : _d(date('Y-m-d'));
            $doc_revise    = $pca && $pca->doc_revise ? $pca->doc_revise : '';
            $doc_number    = $pca && $pca->doc_number !== '' && $pca->doc_number !== null
                             ? $pca->doc_number
                             : pur_pca_doc_number();
            $doc_approved  = $pca && $pca->doc_approved_by ? $pca->doc_approved_by : '';
            ?>
            <div class="row">
              <div class="col-md-2">
                <label class="control-label"><?php echo _l('pur_pca_doc_version'); ?></label>
                <input type="text" class="form-control" value="<?php echo e($doc_version); ?>" disabled>
                <?php if ($pca) { ?>
                <small class="text-muted"><?php echo sprintf(_l('pur_bw_doc_version_next'), e($doc_next)); ?></small>
                <?php } ?>
              </div>
              <div class="col-md-3">
                <label class="control-label"><?php echo _l('pur_pca_doc_effective_date'); ?></label>
                <input type="text" class="form-control" value="<?php echo e($doc_effective); ?>" disabled>
              </div>
              <div class="col-md-2">
                <label class="control-label"><?php echo _l('pur_pca_doc_revise'); ?></label>
                <input type="text" class="form-control" value="<?php echo $doc_revise ? e(_d($doc_revise)) : ''; ?>" disabled>
              </div>
              <div class="col-md-2">
                <label class="control-label"><?php echo _l('pur_pca_doc_number'); ?></label>
                <input type="text" class="form-control" value="<?php echo e($doc_number); ?>" disabled>
              </div>
              <div class="col-md-3">
                <label class="control-label"><?php echo _l('pur_pca_doc_approved_by'); ?></label>
                <input type="text" class="form-control" value="<?php echo e($doc_approved); ?>"
                       placeholder="<?php echo _l('pur_bw_doc_approved_pending'); ?>" disabled>
              </div>
            </div>

            <!-- 1. Requestor Details -->
            <h4 class="bold mtop20"><?php echo _l('pur_pca_section_1'); ?></h4>
            <hr class="hr-panel-heading" />
            <div class="row">
              <div class="col-md-3">
                <label class="control-label"><?php echo _l('pur_pca_request_no'); ?></label>
                <?php
                // Generated on save, so shown read-only rather than posted back -
                // letting it be edited would allow duplicates
                $request_no_display = $pca ? $pca->request_no : (isset($next_request_no) ? $next_request_no : '');
                ?>
                <input type="text" class="form-control" value="<?php echo e($request_no_display); ?>" disabled>
              </div>
              <div class="col-md-3">
                <?php echo render_input('employee_id', 'pur_pca_employee_id', pca_val($pca, 'employee_id')); ?>
              </div>
              <div class="col-md-3">
                <?php
                // A staff dropdown rather than free text. The printed form has a plain
                // Employee Name box, but storing the staff id is what lets the list be
                // scoped to "my requests" for users without global view rights.
                echo render_select('employee', $staff_options, ['id', 'name'], 'pur_pca_employee_name', pca_val($pca, 'employee'), ['required' => 'true']);
                ?>
              </div>
              <div class="col-md-3">
                <?php echo render_select('department', $department_options, ['id', 'name'], 'pur_pca_department', pca_val($pca, 'department'), ['required' => 'true']); ?>
              </div>
              <div class="col-md-3">
                <?php
                $required_date = pca_val($pca, 'required_date');
                echo render_date_input('required_date', 'pur_pca_required_date', $required_date ? _d($required_date) : '');
                ?>
              </div>
            </div>

            <!-- 2. Advance Request Details -->
            <h4 class="bold mtop20"><?php echo _l('pur_pca_section_2'); ?></h4>
            <hr class="hr-panel-heading" />
            <div class="row">
              <div class="col-md-12">
                <?php echo render_textarea('purpose_of_advance', 'pur_pca_purpose_of_advance', pca_val($pca, 'purpose_of_advance'), ['rows' => 3]); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_select('expense_category', $category_options, ['id', 'name'], 'pur_pca_expense_category', pca_val($pca, 'expense_category')); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_input('amount_requested', 'pur_pca_amount_requested', pca_val($pca, 'amount_requested', '0.00'), 'number', ['step' => 'any']); ?>
              </div>
              <div class="col-md-3">
                <?php echo render_select('currency', $currency_options, ['id', 'name'], 'pur_pca_currency', $selected_currency); ?>
              </div>
              <div class="col-md-3">
                <?php
                // The printed form repeats Required Date in section 2; both are kept so
                // the layout matches
                $advance_required_date = pca_val($pca, 'advance_required_date');
                echo render_date_input('advance_required_date', 'pur_pca_advance_required_date', $advance_required_date ? _d($advance_required_date) : '');
                ?>
              </div>

              <div class="col-md-12">
                <label class="control-label"><?php echo _l('pur_pca_payment_priority'); ?></label><br />
                <?php
                $priority = pca_val($pca, 'payment_priority', 'normal');
                foreach (pur_pca_priorities() as $priority_key => $priority_label) { ?>
                <label class="radio radio-inline radio-primary">
                  <input type="radio" name="payment_priority" value="<?php echo $priority_key; ?>"
                         id="pca-priority-<?php echo $priority_key; ?>"
                         <?php echo $priority === $priority_key ? 'checked' : ''; ?>>
                  <label for="pca-priority-<?php echo $priority_key; ?>"><?php echo $priority_label; ?></label>
                </label>
                <?php } ?>
              </div>
            </div>

            <!-- 3. Expense / Items Details -->
            <h4 class="bold mtop20"><?php echo _l('pur_pca_section_3'); ?></h4>
            <p class="text-muted"><?php echo _l('pur_pca_items_hint'); ?></p>
            <div class="table-responsive">
              <table class="table table-bordered" id="pca-items">
                <thead>
                  <tr>
                    <th style="width:50%;"><?php echo _l('pur_pca_description'); ?></th>
                    <th style="width:18%;"><?php echo _l('pur_pca_amount'); ?></th>
                    <th style="width:28%;"><?php echo _l('pur_pca_vendor'); ?></th>
                    <th style="width:4%;"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php $row_index = 0;
                  foreach ($existing_items as $item) { ?>
                  <tr class="pca-item-row">
                    <td>
                      <input type="text" class="form-control" name="items[<?php echo $row_index; ?>][description]" value="<?php echo e($item['description']); ?>">
                    </td>
                    <td>
                      <input type="number" step="any" class="form-control pca-item-amount" name="items[<?php echo $row_index; ?>][amount]" value="<?php echo e($item['amount']); ?>">
                    </td>
                    <td>
                      <?php // include_blank stays on so a row can be left without a vendor ?>
                      <?php echo render_select('items[' . $row_index . '][vendor_id]', $vendor_options, ['id', 'name'], '', $item['vendor_id']); ?>
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-danger btn-icon pca-remove-item"><i class="fa fa-remove"></i></button>
                    </td>
                  </tr>
                  <?php $row_index++;
                  } ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td class="text-right bold"><?php echo _l('pur_pca_items_total'); ?></td>
                    <td class="bold" id="pca-items-total">0.00</td>
                    <td colspan="2"></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <button type="button" class="btn btn-default" id="pca-add-item">
              <i class="fa fa-plus"></i> <?php echo _l('pur_pca_add_item'); ?>
            </button>

            <!-- Attachments -->
            <h4 class="bold mtop20"><?php echo _l('pur_pca_attachment'); ?></h4>
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

              <?php if ($pca && !empty($attachments)) { ?>
              <div class="col-md-12" id="pca_pv_file">
                <hr />
                <?php foreach ($attachments as $file) {
                    $href = site_url(PURCHASE_PATH . 'petty_cash_advances/' . $file['rel_id'] . '/' . rawurlencode($file['file_name']));
                    if (!empty($file['external'])) {
                        $href = $file['external_link'];
                    } ?>
                <div class="mbot15 row inline-block full-width" data-attachment-id="<?php echo $file['id']; ?>">
                  <div class="col-md-8">
                    <a href="#" onclick="pca_preview_file(<?php echo $file['id']; ?>, <?php echo $file['rel_id']; ?>); return false;"
                       class="mbot10 mright5 btn btn-success pull-left" data-toggle="tooltip" title="<?php echo _l('preview_file'); ?>">
                      <i class="fa fa-eye"></i>
                    </a>
                    <div class="pull-left"><i class="<?php echo get_mime_class($file['filetype']); ?>"></i></div>
                    <a href="<?php echo $href; ?>" target="_blank" download><?php echo e($file['file_name']); ?></a>
                    <br /><small class="text-muted"><?php echo e($file['filetype']); ?></small>
                  </div>
                  <div class="col-md-4 text-right">
                    <?php if ($file['staffid'] == get_staff_user_id() || is_admin()) { ?>
                    <a href="#" class="text-danger" onclick="pca_delete_attachment(<?php echo $file['id']; ?>); return false;"><i class="fa fa-times"></i></a>
                    <?php } ?>
                  </div>
                </div>
                <?php } ?>
              </div>
              <div id="pca_file_data"></div>
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

<!-- Row template for the item repeater. Held outside the form so its inputs are never
     submitted; __INDEX__ is replaced with the next free row number. -->
<script type="text/html" id="pca-item-template">
  <tr class="pca-item-row">
    <td><input type="text" class="form-control" name="items[__INDEX__][description]"></td>
    <td><input type="number" step="any" class="form-control pca-item-amount" name="items[__INDEX__][amount]"></td>
    <td><?php echo str_replace("\n", '', render_select('items[__INDEX__][vendor_id]', $vendor_options, ['id', 'name'], '', '')); ?></td>
    <td class="text-center"><button type="button" class="btn btn-danger btn-icon pca-remove-item"><i class="fa fa-remove"></i></button></td>
  </tr>
</script>

<?php init_tail(); ?>
<?php require 'modules/purchase/assets/js/petty_cash_advance/petty_cash_advance_js.php'; ?>
</body>
</html>
