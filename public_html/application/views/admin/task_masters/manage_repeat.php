<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <div class="tw-mb-2">
                    <a href="#" onclick="new_repeat_option(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('new_task_repeat_option'); ?>
                    </a>
                    <a href="<?= admin_url('task_masters/related'); ?>" class="btn btn-default">
                        <?= _l('task_related_master'); ?>
                    </a>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <p class="tw-text-neutral-600 tw-mb-0">
                            <?= _l('task_repeat_master_help'); ?>
                        </p>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('id'),
                            _l('task_repeat_label'),
                            _l('task_repeat_every_value'),
                            _l('task_repeat_unit'),
                            _l('task_master_sort_order'),
                            _l('active'),
                            _l('options'),
                        ], 'task-repeat-master'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="repeat_option_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?= form_open(admin_url('task_masters/repeat_option'), ['id' => 'repeat-option-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <span class="edit-title"><?= _l('edit_task_repeat_option'); ?></span>
                    <span class="add-title"><?= _l('new_task_repeat_option'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div id="repeat_additional"></div>

                <?= render_input('name', 'task_repeat_label'); ?>

                <div class="row">
                    <div class="col-md-6">
                        <?= render_input('repeat_every', 'task_repeat_every_value', 1, 'number', ['min' => 1]); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo render_select('recurring_type', [
                            ['id' => 'day',   'name' => _l('task_repeat_unit_day')],
                            ['id' => 'week',  'name' => _l('task_repeat_unit_week')],
                            ['id' => 'month', 'name' => _l('task_repeat_unit_month')],
                            ['id' => 'year',  'name' => _l('task_repeat_unit_year')],
                        ], ['id', 'name'], 'task_repeat_unit', 'month', ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                    </div>
                </div>

                <?= render_input('sort_order', 'task_master_sort_order', 0, 'number'); ?>

                <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="active" id="repeat_active" checked>
                    <label for="repeat_active"><?= _l('active'); ?></label>
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

    initDataTable('.table-task-repeat-master', admin_url + 'task_masters/repeat', [5, 6], [5, 6], undefined, [4, 'asc']);

    appValidateForm($('#repeat-option-form'), {
        name: 'required',
        repeat_every: {
            required: true,
            min: 1
        },
        recurring_type: 'required'
    }, manage_repeat_option);

    $('#repeat_option_modal').on('hidden.bs.modal', function() {
        $('#repeat_additional').html('');
        $('#repeat-option-form')[0].reset();
        $('input[name="repeat_every"]').val(1);
        $('input[name="sort_order"]').val(0);
        $('#repeat_active').prop('checked', true);
        $('select[name="recurring_type"]').selectpicker('val', 'month');
        $('.add-title').removeClass('hide');
        $('.edit-title').removeClass('hide');
    });
});

function manage_repeat_option(form) {
    "use strict";
    $.post(form.action, $(form).serialize()).done(function(response) {
        response = JSON.parse(response);
        if (response.success == true) {
            alert_float('success', response.message);
        }
        $('.table-task-repeat-master').DataTable().ajax.reload(null, false);
        $('#repeat_option_modal').modal('hide');
    }).fail(function(data) {
        alert_float('danger', data.responseText);
    });

    return false;
}

function new_repeat_option() {
    "use strict";
    $('#repeat_option_modal').modal('show');
    $('.edit-title').addClass('hide');
}

function edit_repeat_option(invoker, id) {
    "use strict";
    $('#repeat_additional').append(hidden_input('id', id));

    $('input[name="name"]').val($(invoker).data('name'));
    $('input[name="repeat_every"]').val($(invoker).data('repeat-every'));
    $('input[name="sort_order"]').val($(invoker).data('sort-order'));
    $('select[name="recurring_type"]').selectpicker('val', $(invoker).data('recurring-type'));
    $('#repeat_active').prop('checked', $(invoker).data('active') == 1);

    $('#repeat_option_modal').modal('show');
    $('.add-title').addClass('hide');
}
</script>
</body>

</html>
