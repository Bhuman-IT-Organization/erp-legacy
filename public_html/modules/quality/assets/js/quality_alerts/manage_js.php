<script type="text/javascript">
    $(function(){
        'use strict';

        var quality_alert_params = {
            "from_date_filter": "[name='from_date_filter']",
            "to_date_filter": "[name='to_date_filter']",
            "item_filter": "[name='item_filter']",
            "status_filter": "[name='status_filter']",
            "quality_alert_type_filter": "[name='quality_alert_type_filter']",
            "quality_check_id_filter": "[name='quality_check_id_filter']",
            "team_filter": "[name='team_filter']",
        };

        var quality_alert_table = $('table.table-quality_alert_table');
        var _table_api = initDataTable(quality_alert_table, admin_url+'quality/quality_alert_table', [0], [0], quality_alert_params, ['0', 'desc']);
        var hidden_columns = [0];
        $('.table-quality_alert_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(quality_alert_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-quality_alert_table').DataTable().ajax.reload();
            });
        });

        $('input[name="from_date_filter"]').on('change', function() {  
            $('.table-quality_alert_table').DataTable().ajax.reload();
        });
        $('input[name="to_date_filter"]').on('change', function() {  
            $('.table-quality_alert_table').DataTable().ajax.reload();
        });

    });

    function quality_alert_modal(quality_alert_id, quality_check_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('quality/load_quality_alert_modal'); ?>", {
          quality_alert_id: quality_alert_id,
          quality_check_id: quality_check_id,
      }, function() {
          $("body").find('#quality_alertModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();
          init_tags_inputs();

      });

    }

    function delete_quality_alert(id) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "quality/delete_quality_alert/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-quality_alert_table').DataTable().ajax.reload();

                    var quality_alert_detail = $('input[name="quality_alert_detail"]').val();
                    if(quality_alert_detail == 1){
                        var repair_job_id = $('input[name="_repair_job_id"]').val();
                        window.location.assign('<?php echo admin_url('quality/repair_job_detail/') ?>'+repair_job_id+'?tab=quality_alert');
                    }
                }
            });
        }
    }

    function delete_quality_alert_attachment(wrapper, attachment_id) {
        "use strict";  

        if (confirm_delete()) {
            $.get(admin_url + 'quality/delete_quality_attachment/' +attachment_id + '/QUALITY_ALERT_FOLDER', function (response) {
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

    function quality_alert_status_mark_as(status, id, type) {
        "use strict"; 
        
        var url = 'quality/quality_alert_status_mark_as/' + status + '/' + id + '/' + type;
        var taskModalVisible = $('#task-modal').is(':visible');
        url += '?single_task=' + taskModalVisible;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {

                var av_tasks_tables = ['table.table-quality_alert_table'];
                $.each(av_tasks_tables, function (i, selector) {
                    if ($.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().ajax.reload(null, false);
                    }
                });
                alert_float('success', response.message);

                var quality_alert_detail = $('input[name="quality_alert_detail"]').val();
                if(quality_alert_detail == 1){
                    window.location.assign('<?php echo admin_url('quality/quality_alert_detail/') ?>'+id+'?tab=detail');
                }

            }
        });
    }

    function quality_alert_priority_mark_as(status, id, type) {
        "use strict"; 
        
        var url = 'quality/quality_alert_priority_mark_as/' + status + '/' + id + '/' + type;
        var taskModalVisible = $('#task-modal').is(':visible');
        url += '?single_task=' + taskModalVisible;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {

                var av_tasks_tables = ['table.table-quality_alert_table'];
                $.each(av_tasks_tables, function (i, selector) {
                    if ($.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().ajax.reload(null, false);
                    }
                });
                alert_float('success', response.message);

                var quality_alert_detail = $('input[name="quality_alert_detail"]').val();
                if(quality_alert_detail == 1){
                    window.location.assign('<?php echo admin_url('quality/quality_alert_detail/') ?>'+id+'?tab=detail');
                }

            }
        });
    }
    
    function quality_alert_root_cause_mark_as(status, id, type) {
        "use strict"; 
        
        var url = 'quality/quality_alert_root_cause_mark_as/' + status + '/' + id + '/' + type;
        var taskModalVisible = $('#task-modal').is(':visible');
        url += '?single_task=' + taskModalVisible;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {

                var av_tasks_tables = ['table.table-quality_alert_table'];
                $.each(av_tasks_tables, function (i, selector) {
                    if ($.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().ajax.reload(null, false);
                    }
                });
                alert_float('success', response.message);

                var quality_alert_detail = $('input[name="quality_alert_detail"]').val();
                if(quality_alert_detail == 1){
                    window.location.assign('<?php echo admin_url('quality/quality_alert_detail/') ?>'+id+'?tab=detail');
                }

            }
        });
    }
    


</script>