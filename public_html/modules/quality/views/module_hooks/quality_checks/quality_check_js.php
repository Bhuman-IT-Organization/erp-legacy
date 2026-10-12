<script type="text/javascript">
	'use strict';

	var quality_check_params = {};
	 $(document).ready(function() {
	 	init_service_table()
	 })

	function init_service_table() {
		'use strict';

		var quality_check_table = $('table.table-quality_check_table');
		var quality_check_params = {
			"rel_type_filter": "[name='rel_type']",
			"rel_id_filter": "[name='rel_id']",
		};

		initDataTable(quality_check_table, admin_url+'quality/quality_check_table', [0], [0], quality_check_params, [1, 'asc']);
		var hidden_columns = [0];
		quality_check_table.DataTable().columns(hidden_columns).visible(false, false);
	}
	function quality_check_modal(quality_check_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('quality/load_quality_check_modal'); ?>", {
          quality_check_id: quality_check_id,
      }, function() {
          $("body").find('#quality_checkModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }

    function delete_quality_check(id) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "quality/delete_quality_check/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-quality_check_table').DataTable().ajax.reload();

                    var quality_check_detail = $('input[name="quality_check_detail"]').val();
                    if(quality_check_detail == 1){
                        var repair_job_id = $('input[name="_repair_job_id"]').val();
                        window.location.assign('<?php echo admin_url('quality/repair_job_detail/') ?>'+repair_job_id+'?tab=quality_check');
                    }
                }
            });
        }
    }

    function delete_quality_check_attachment(wrapper, attachment_id) {
        "use strict";  

        if (confirm_delete()) {
            $.get(admin_url + 'quality/delete_quality_attachment/' +attachment_id + '/QUALITY_CHECK_FOLDER', function (response) {
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

    function quality_check_status_mark_as(status, id, type) {
        "use strict"; 
        
        var url = 'quality/quality_check_status_mark_as/' + status + '/' + id + '/' + type;
        var taskModalVisible = $('#task-modal').is(':visible');
        url += '?single_task=' + taskModalVisible;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {

                var av_tasks_tables = ['table.table-quality_check_table'];
                $.each(av_tasks_tables, function (i, selector) {
                    if ($.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().ajax.reload(null, false);
                    }
                });
                alert_float('success', response.message);

                var quality_check_detail = $('input[name="quality_check_detail"]').val();
                if(quality_check_detail == 1){
                    window.location.assign('<?php echo admin_url('quality/quality_check_detail/') ?>'+id+'?tab=detail');
                }

            }
        });
    }


</script>