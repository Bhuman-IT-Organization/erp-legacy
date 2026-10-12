<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <div class="tw-mb-2">
                    <?php if ($available_count > 0) { ?>
                    <a href="#" onclick="new_related_option(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('new_task_related_option'); ?>
                    </a>
                    <?php } else { ?>
                    <button type="button" class="btn btn-primary" disabled
                        data-toggle="tooltip" title="<?= _l('task_related_all_types_used'); ?>">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('new_task_related_option'); ?>
                    </button>
                    <?php } ?>
                    <a href="<?= admin_url('task_masters/repeat'); ?>" class="btn btn-default">
                        <?= _l('task_repeat_master'); ?>
                    </a>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <p class="tw-text-neutral-600 tw-mb-0">
                            <?= _l('task_related_master_help'); ?>
                        </p>
                        <?php if ($available_count == 0) { ?>
                        <p class="tw-text-neutral-500 tw-mb-0 tw-mt-2">
                            <i class="fa-regular fa-circle-info tw-mr-1"></i>
                            <?= _l('task_related_all_types_used'); ?>
                        </p>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('id'),
                            _l('task_related_label'),
                            _l('task_related_key'),
                            _l('task_master_sort_order'),
                            _l('active'),
                            _l('options'),
                        ], 'task-related-master'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="related_option_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?= form_open(admin_url('task_masters/related_option'), ['id' => 'related-option-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <span class="edit-title"><?= _l('edit_task_related_option'); ?></span>
                    <span class="add-title"><?= _l('new_task_related_option'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div id="related_additional"></div>

                <?= render_input('name', 'task_related_label'); ?>

                <div class="form-group select-placeholder">
                    <label for="key_name" class="control-label">
                        <?= _l('task_related_key'); ?>
                    </label>
                    <select name="key_name" id="key_name" class="selectpicker" data-width="100%"
                        data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                        <option value=""></option>
                        <?php foreach ($supported_types as $key => $label) {
                            $isUsed = in_array($key, $used_keys); ?>
                        <option value="<?= e($key); ?>" data-used="<?= $isUsed ? '1' : '0'; ?>"
                            <?= $isUsed ? 'disabled' : ''; ?>>
                            <?= e($key); ?><?= $isUsed ? ' — ' . _l('task_related_key_in_use') : ''; ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <p class="tw-text-sm tw-text-neutral-500 -tw-mt-2">
                    <?= _l('task_related_key_help'); ?>
                </p>

                <?= render_input('sort_order', 'task_master_sort_order', 0, 'number'); ?>

                <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="active" id="related_active" checked>
                    <label for="related_active"><?= _l('active'); ?></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>

<?php init_tail(); ?>
<script>
$(function() {
    "use strict";

    initDataTable('.table-task-related-master', admin_url + 'task_masters/related', [4, 5], [4, 5], undefined, [3, 'asc']);

    appValidateForm($('#related-option-form'), {
        name: 'required',
        key_name: 'required'
    }, manage_related_option);

    $('#related_option_modal').on('hidden.bs.modal', function() {
        reset_related_form();
    });
});

function reset_related_form() {
    "use strict";
    $('#related_additional').html('');
    $('input[name="name"]').val('');
    $('input[name="sort_order"]').val(0);
    $('#related_active').prop('checked', true);

    // Restore the disabled state driven by data-used
    $('#key_name option').each(function() {
        var used = $(this).data('used');
        $(this).prop('disabled', used == 1 || used === '1');
    });

    $('#key_name').selectpicker('val', '');
    $('#key_name').selectpicker('refresh');

    $('.add-title').removeClass('hide');
    $('.edit-title').removeClass('hide');
}

function manage_related_option(form) {
    "use strict";
    $.post(form.action, $(form).serialize()).done(function(response) {
        try {
            response = JSON.parse(response);
        } catch (e) {
            alert_float('danger', 'Unexpected server response');
            return;
        }

        if (response.success === true) {
            alert_float('success', response.message);
            $('#related_option_modal').modal('hide');
            // Reload so the disabled-state of the dropdown reflects the new row
            setTimeout(function() {
                window.location.reload();
            }, 600);
        } else {
            alert_float('danger', response.message);
        }
    }).fail(function(data) {
        alert_float('danger', data.responseText);
    });

    return false;
}

function new_related_option() {
    "use strict";
    reset_related_form();
    $('.edit-title').addClass('hide');
    $('#related_option_modal').modal('show');
}

function edit_related_option(invoker, id) {
    "use strict";
    reset_related_form();

    var currentKey = $(invoker).data('key-name');

    $('#related_additional').append(hidden_input('id', id));
    $('input[name="name"]').val($(invoker).data('name'));
    $('input[name="sort_order"]').val($(invoker).data('sort-order'));
    $('#related_active').prop('checked', $(invoker).data('active') == 1);

    // The row being edited owns this key — re-enable it so it can stay selected
    $('#key_name option[value="' + currentKey + '"]').prop('disabled', false);
    $('#key_name').selectpicker('refresh');
    $('#key_name').selectpicker('val', currentKey);

    $('.add-title').addClass('hide');
    $('#related_option_modal').modal('show');
}
</script>
</body>

</html>
