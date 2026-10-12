<script type="text/javascript">
    $(function(){
        'use strict';

        var control_point_params = {
            "from_date_filter": "[name='from_date_filter']",
            "to_date_filter": "[name='to_date_filter']",
            "item_filter": "[name='item_filter']",
            "quality_control_frequency_filter": "[name='quality_control_frequency_filter']",
            "control_point_type_filter": "[name='control_point_type_filter']",
        };

        var control_point_table = $('table.table-control_point_table');
        var _table_api = initDataTable(control_point_table, admin_url+'quality/control_point_table', [0], [0], control_point_params, ['0', 'desc']);
        var hidden_columns = [0];
        $('.table-control_point_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(control_point_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-control_point_table').DataTable().ajax.reload();
            });
        });

        $('input[name="from_date_filter"]').on('change', function() {  
            $('.table-control_point_table').DataTable().ajax.reload();
        });
        $('input[name="to_date_filter"]').on('change', function() {  
            $('.table-control_point_table').DataTable().ajax.reload();
        });

    });

    function control_point_modal(control_point_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('quality/load_control_point_modal'); ?>", {
          control_point_id: control_point_id,
      }, function() {
          $("body").find('#control_pointModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }

    function delete_control_point(id) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "quality/delete_control_point/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-control_point_table').DataTable().ajax.reload();

                    var control_point_detail = $('input[name="control_point_detail"]').val();
                    if(control_point_detail == 1){
                        var repair_job_id = $('input[name="_repair_job_id"]').val();
                        window.location.assign('<?php echo admin_url('quality/repair_job_detail/') ?>'+repair_job_id+'?tab=control_point');
                    }
                }
            });
        }
    }

    function delete_control_point_attachment(wrapper, attachment_id) {
        "use strict";  

        if (confirm_delete()) {
            $.get(admin_url + 'quality/delete_quality_attachment/' +attachment_id + '/CONTROL_POINT_FOLDER', function (response) {
                if (response.success == true) {
                    $(wrapper).parents('.pdf_attachment').remove();

                    var totalAttachmentsIndicator = $('.pdf_attachment'+attachment_id);
                    var totalAttachments = totalAttachmentsIndicator.text().trim();

                    if(totalAttachments == 1) {
                        totalAttachmentsIndicator.remove();
                    } else {
                        totalAttachmentsIndicator.text(totalAttachments-1);
                    }
                    alert_float('success', "<?php echo _l('quality_deleted_file_successfully') ?>");

                } else {
                    alert_float('danger', "<?php echo _l('quality_deleted_file_failed') ?>");
                }
            }, 'json');
        }
        return false;
    }

    function preview_file(invoker){
        'use strict';

        var id = $(invoker).attr('id');
        var rel_id = $(invoker).attr('rel_id');
        view_file(id, rel_id);
    }

    function view_file(id, rel_id) {   
        'use strict';

        $('#pdf_file_data').empty();
        $("#pdf_file_data").load(admin_url + 'quality/preview_file/' + id + '/' + rel_id, function(response, status, xhr) {
            if (status == "error") {
                alert_float('danger', xhr.statusText);
            }
        });
    }

    function control_point_status_mark_as(status, id, type) {
        "use strict"; 
        
        var url = 'quality/control_point_status_mark_as/' + status + '/' + id + '/' + type;
        var taskModalVisible = $('#task-modal').is(':visible');
        url += '?single_task=' + taskModalVisible;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {

                var av_tasks_tables = ['table.table-control_point_table'];
                $.each(av_tasks_tables, function (i, selector) {
                    if ($.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().ajax.reload(null, false);
                    }
                });
                alert_float('success', response.message);

                var control_point_detail = $('input[name="control_point_detail"]').val();
                if(control_point_detail == 1){
                    window.location.assign('<?php echo admin_url('quality/control_point_detail/') ?>'+id+'?tab=detail');
                }

            }
        });
    }


</script>