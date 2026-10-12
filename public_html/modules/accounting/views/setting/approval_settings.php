<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Accounting approval settings.
 *
 * Approval chains for accounting documents, currently cheques, one chain per
 * department. Same layout and storage shape as the Fixed Equipment and Purchase
 * approval settings, so the three behave and resolve identically.
 */

$acc_chains = $this->accounting_model->get_acc_approval_setting();

$department_options = [['id' => '0', 'name' => _l('acc_approval_all_departments')]];
foreach ($departments as $department) {
    $department_options[] = [
        'id'   => (string) $department['departmentid'],
        'name' => $department['name'],
    ];
}

$related_options = [];
foreach ($related_types as $related_key => $related_label) {
    $related_options[] = ['id' => $related_key, 'name' => $related_label];
}

/** Names of the approvers in a chain, in order, as stored. */
$chain_approvers = function ($setting_json) {
    $names = [];

    foreach ((array) json_decode($setting_json, true) as $node) {
        if (empty($node['staff'])) {
            continue;
        }

        $name = trim((string) get_staff_full_name($node['staff']));

        // A step naming somebody who has since been removed from Staff must be
        // visible, not blank - otherwise a broken chain looks like a working one.
        $names[] = $name !== ''
            ? e($name)
            : '<span class="text-danger">#' . e($node['staff']) . ' ' . _l('acc_approval_staff_missing') . '</span>';
    }

    return $names;
};

/** The departments a chain covers, as names. */
$chain_departments = function ($departments_csv) use ($departments) {
    $ids = array_filter(array_map('intval', explode(',', (string) $departments_csv)));

    if (count($ids) === 0) {
        return '<span class="text-muted">' . _l('acc_approval_all_departments') . '</span>';
    }

    $names = [];
    foreach ($ids as $id) {
        foreach ($departments as $department) {
            if ((int) $department['departmentid'] === $id) {
                $names[] = e($department['name']);
            }
        }
    }

    return count($names) > 0 ? implode(', ', $names) : '<span class="text-muted">-</span>';
};
?>

<div class="row">
    <div class="col-md-12">
        <h4 class="no-mtop font-bold"><?php echo _l('acc_approval_settings'); ?></h4>
        <p class="text-muted"><?php echo _l('acc_approval_settings_help'); ?></p>

        <?php if (has_permission('accounting_setting', '', 'edit') || is_admin()) { ?>
        <a href="#" class="btn btn-info mbot15" onclick="acc_approval_new(); return false;">
            <i class="fa fa-plus"></i> <?php echo _l('acc_approval_new_chain'); ?>
        </a>
        <?php } ?>

        <div class="clearfix"></div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo _l('name'); ?></th>
                    <th><?php echo _l('related'); ?></th>
                    <th><?php echo _l('department'); ?></th>
                    <th><?php echo _l('acc_approval_chain_steps'); ?></th>
                    <th><?php echo _l('options'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($acc_chains) === 0) { ?>
                <tr>
                    <td colspan="5" class="text-muted"><?php echo _l('acc_approval_no_chains'); ?></td>
                </tr>
                <?php } ?>

                <?php foreach ($acc_chains as $chain) {
                    $approvers = $chain_approvers($chain['setting']);
                    ?>
                <tr>
                    <td><?php echo e($chain['name']); ?></td>
                    <td>
                        <?php echo isset($related_types[$chain['related']])
                            ? e($related_types[$chain['related']])
                            : e($chain['related']); ?>
                    </td>
                    <td><?php echo $chain_departments($chain['departments']); ?></td>
                    <td>
                        <?php
                        // Numbered, because the order is the approval order
                        if (count($approvers) === 0) {
                            echo '<span class="text-danger">' . _l('acc_approval_no_steps') . '</span>';
                        } else {
                            $step = 0;
                            foreach ($approvers as $approver) {
                                $step++;
                                echo '<span class="label label-default">' . $step . '</span> ' . $approver;
                                if ($step < count($approvers)) {
                                    echo ' <i class="fa fa-long-arrow-right text-muted"></i> ';
                                }
                            }
                        }
                        ?>
                    </td>
                    <td>
                        <?php if (has_permission('accounting_setting', '', 'edit') || is_admin()) { ?>
                        <a href="#" onclick="acc_approval_edit(<?php echo (int) $chain['id']; ?>); return false;">
                            <?php echo _l('edit'); ?>
                        </a>
                        <?php } ?>
                        <?php if (has_permission('accounting_setting', '', 'delete') || is_admin()) { ?>
                        |
                        <a href="<?php echo admin_url('accounting/acc_approval_setting_delete/' . (int) $chain['id']); ?>"
                           class="text-danger _delete"><?php echo _l('delete'); ?></a>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="acc_approval_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open(admin_url('accounting/acc_approver_setting'), ['id' => 'acc-approval-form']); ?>
            <?php echo form_hidden('approval_setting_id', ''); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo _l('acc_approval_new_chain'); ?></h4>
            </div>
            <div class="modal-body">
                <?php echo render_input('name', 'name', '', 'text', ['required' => true]); ?>
                <?php echo render_select('related', $related_options, ['id', 'name'], 'related', 'cheque', ['required' => true]); ?>
                <?php echo render_select('departments', $department_options, ['id', 'name'], 'department', '0'); ?>
                <?php echo render_input('number_day_approval', 'maximum_number_of_days_to_sign', '', 'number'); ?>

                <div class="select-placeholder form-group">
                    <label for="notification_recipient"><?php echo _l('notification_recipient'); ?></label>
                    <select name="notification_recipient[]" class="selectpicker" data-width="100%" multiple="true"
                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>"
                            data-actions-box="true" data-live-search="true">
                        <?php foreach ($staffs as $staff_member) { ?>
                        <option value="<?php echo (int) $staff_member['staffid']; ?>">
                            <?php echo e(get_staff_full_name($staff_member['staffid'])); ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>

                <hr />
                <h5 class="bold"><?php echo _l('acc_approval_chain_steps'); ?></h5>
                <p class="text-muted"><?php echo _l('acc_approval_chain_steps_help'); ?></p>

                <div id="acc_approval_steps"></div>

                <button type="button" class="btn btn-default btn-sm" onclick="acc_approval_add_step(); return false;">
                    <i class="fa fa-plus"></i> <?php echo _l('acc_approval_add_step'); ?>
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<?php
// This view is loaded from inside setting/manage.php, which calls init_tail() - and
// therefore loads jQuery - only after the tab content. So nothing below may touch
// jQuery while the script is being parsed. An IIFE taking jQuery as an argument threw
// "jQuery is not defined" here, and because it threw, the window.* handlers were never
// assigned, which surfaced as "acc_approval_new is not defined" on the button.
//
// Everything is therefore defined as plain functions that resolve jQuery when they are
// called, and the one-time wiring waits for DOMContentLoaded.
?>
<script>
"use strict";

// Rendered from PHP once and reused for every step row, so the option list lives in a
// single place.
var accApprovalStaffOptions = <?php
    $staff_options = ['' => ''];
    foreach ($staffs as $staff_member) {
        $staff_options[(string) $staff_member['staffid']] = trim((string) get_staff_full_name($staff_member['staffid']));
    }
    echo json_encode($staff_options);
?>;

var accApprovalStepIndex = 0;

function accApprovalStepMarkup(selected) {
    var options = '';

    for (var id in accApprovalStaffOptions) {
        if (!Object.prototype.hasOwnProperty.call(accApprovalStaffOptions, id)) {
            continue;
        }

        var isSelected = (String(id) === String(selected)) ? ' selected' : '';
        options += '<option value="' + id + '"' + isSelected + '>' + accApprovalStaffOptions[id] + '</option>';
    }

    // The index has to keep increasing for the life of the page. Reusing a removed
    // row's index would post two selects under the same name and the second would
    // silently overwrite the first.
    var markup = '<div class="row acc-approval-step mbot10">'
        + '<div class="col-md-10"><select name="staff[' + accApprovalStepIndex + ']" class="form-control" required>'
        + options + '</select></div>'
        + '<div class="col-md-2"><button type="button" class="btn btn-danger btn-block acc-approval-remove-step">'
        + '<i class="fa fa-minus"></i></button></div>'
        + '</div>';

    accApprovalStepIndex++;

    return markup;
}

function acc_approval_add_step(selected) {
    jQuery('#acc_approval_steps').append(accApprovalStepMarkup(selected || ''));
}

function accApprovalResetModal() {
    var $ = jQuery;
    var form = $('#acc-approval-form');

    form.find('input[name="approval_setting_id"]').val('');
    form.find('input[name="name"]').val('');
    form.find('input[name="number_day_approval"]').val('');
    form.find('select[name="related"]').val('cheque');
    form.find('select[name="departments"]').val('0');
    form.find('select[name="notification_recipient[]"]').val([]);

    $('#acc_approval_steps').empty();
    acc_approval_add_step();

    try { form.find('.selectpicker').selectpicker('refresh'); } catch (e) {}
}

function acc_approval_new() {
    accApprovalResetModal();
    jQuery('#acc_approval_modal').modal('show');
}

function acc_approval_edit(id) {
    var $ = jQuery;

    accApprovalResetModal();

    $.get(admin_url + 'accounting/acc_get_approval_setting/' + id, function (response) {
        if (typeof response === 'string') {
            try { response = JSON.parse(response); } catch (e) { return; }
        }

        if (!response || !response.success) {
            return;
        }

        var form = $('#acc-approval-form');
        form.find('input[name="approval_setting_id"]').val(response.id);
        form.find('input[name="name"]').val(response.name);
        form.find('input[name="number_day_approval"]').val(response.number_day_approval);
        form.find('select[name="related"]').val(response.related);
        form.find('select[name="departments"]').val(response.departments === '' ? '0' : response.departments);
        form.find('select[name="notification_recipient[]"]').val(response.notification_recipient);

        $('#acc_approval_steps').empty();

        if (response.staff && response.staff.length) {
            for (var i = 0; i < response.staff.length; i++) {
                acc_approval_add_step(response.staff[i]);
            }
        } else {
            acc_approval_add_step();
        }

        try { form.find('.selectpicker').selectpicker('refresh'); } catch (e) {}

        $('#acc_approval_modal').modal('show');
    });
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined') {
        return;
    }

    jQuery(document).on('click', '.acc-approval-remove-step', function () {
        // Always leave one row behind: a chain with no steps can never be approved
        if (jQuery('#acc_approval_steps').find('.acc-approval-step').length > 1) {
            jQuery(this).closest('.acc-approval-step').remove();
        } else {
            jQuery(this).closest('.acc-approval-step').find('select').val('');
        }
    });

    if (jQuery('#acc_approval_steps').length) {
        acc_approval_add_step();
    }
});
</script>
