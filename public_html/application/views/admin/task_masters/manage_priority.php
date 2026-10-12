<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <div class="tw-mb-2">
                    <a href="#" onclick="new_priority_option(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('new_task_priority'); ?>
                    </a>
                    <a href="<?= admin_url('task_masters/repeat'); ?>" class="btn btn-default">
                        <?= _l('task_repeat_master'); ?>
                    </a>
                    <a href="<?= admin_url('task_masters/related'); ?>" class="btn btn-default">
                        <?= _l('task_related_master'); ?>
                    </a>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <p class="tw-text-neutral-600 tw-mb-0">
                            <?= _l('task_priority_master_help'); ?>
                        </p>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('id'),
                            _l('task_priority_label'),
                            _l('task_priority_color'),
                            _l('task_master_sort_order'),
                            _l('active'),
                            _l('options'),
                        ], 'task-priority-master'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="priority_option_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?= form_open(admin_url('task_masters/priority_option'), ['id' => 'priority-option-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <span class="edit-title"><?= _l('edit_task_priority'); ?></span>
                    <span class="add-title"><?= _l('new_task_priority'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div id="priority_additional"></div>

                <?= render_input('name', 'task_priority_label'); ?>

                <div class="form-group">
                    <label for="color"><?= _l('task_priority_color'); ?></label>
                    <div class="input-group">
                        <input type="color" class="form-control" name="color" id="priority_color" value="#333333">
                        <span class="input-group-addon">
                            <span id="priority_color_value" class="tw-font-mono">#333333</span>
                        </span>
                    </div>
                </div>

                <?= render_input('sort_order', 'task_master_sort_order', 0, 'number'); ?>

                <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="active" id="priority_active" checked>
                    <label for="priority_active"><?= _l('active'); ?></label>
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

    initDataTable('.table-task-priority-master', admin_url + 'task_masters/priority', [2, 4, 5], [2, 5], undefined, [3, 'asc']);

    appValidateForm($('#priority-option-form'), {
        name: 'required',
        color: 'required'
    }, manage_priority_option);

    $('#priority_color').on('input change', function() {
        $('#priority_color_value').text($(this).val());
    });

    $('#priority_option_modal').on('hidden.bs.modal', function() {
        $('#priority_additional').html('');
        $('#priority-option-form')[0].reset();
        $('#priority_color').val('#333333').trigger('change');
        $('input[name="sort_order"]').val(0);
        $('#priority_active').prop('checked', true);
        $('.add-title').removeClass('hide');
        $('.edit-title').removeClass('hide');
    });
});

function manage_priority_option(form) {
    "use strict";
    $.post(form.action, $(form).serialize()).done(function(response) {
        response = JSON.parse(response);
        if (response.success == true) {
            alert_float('success', response.message);
            $('#priority_option_modal').modal('hide');
        } else {
            alert_float('danger', response.message);
        }
        $('.table-task-priority-master').DataTable().ajax.reload(null, false);
    }).fail(function(data) {
        alert_float('danger', data.responseText);
    });

    return false;
}

function new_priority_option() {
    "use strict";
    $('#priority_option_modal').modal('show');
    $('.edit-title').addClass('hide');
}

function edit_priority_option(invoker, id) {
    "use strict";
    $('#priority_additional').append(hidden_input('id', id));

    $('input[name="name"]').val($(invoker).data('name'));
    $('#priority_color').val($(invoker).data('color')).trigger('change');
    $('input[name="sort_order"]').val($(invoker).data('sort-order'));
    $('#priority_active').prop('checked', $(invoker).data('active') == 1);

    $('#priority_option_modal').modal('show');
    $('.add-title').addClass('hide');
}
</script>
</body>

</html>
