<script type="text/javascript">
    $(function(){
        'use strict';

        var quality_check_params = {
            "quality_check_id": "[name='quality_check_id_filter']",
        };

        var detailed_checklist_table = $('table.table-detailed_checklist_table');
        var _table_api = initDataTable(detailed_checklist_table, admin_url+'quality/detailed_checklist_table', [0], [0], quality_check_params, ['0', 'desc']);
        var hidden_columns = [0, 10];
        $('.table-detailed_checklist_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(quality_check_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-detailed_checklist_table').DataTable().ajax.reload();
            });
        });

    });

    function quality_check_detail_status_mark_as(status, id, type) {
        "use strict"; 
        
        var url = 'quality/quality_check_detail_status_mark_as/' + status + '/' + id + '/' + type;
        var taskModalVisible = $('#task-modal').is(':visible');
        url += '?single_task=' + taskModalVisible;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {

                var av_tasks_tables = ['table.table-detailed_checklist_table'];
                $.each(av_tasks_tables, function (i, selector) {
                    if ($.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().ajax.reload(null, false);
                    }
                });
                alert_float('success', response.message);

            }
        });
    }

    function quality_check_detail_modal(quality_check_detail_id, quality_check_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('quality/load_quality_check_detail_modal'); ?>", {
          quality_check_detail_id: quality_check_detail_id,
          quality_check_id: quality_check_id,
      }, function() {
          $("body").find('#quality_check_detailModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }

    function send_products_to_location_modal(quality_check_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('quality/load_products_to_location_modal'); ?>", {
          quality_check_id: quality_check_id,
      }, function() {
          $("body").find('#products_to_locationModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }
    

</script>

