<script type="text/javascript">
    var CheckDetailDropzone, quality_check_detail_id, checklists_processed;
    $(function(){
        'use strict';

        $( document ).ready(function() {

            <?php if($quality_check->type == 'measure'){ ?>
                $('#add_edit_quality_check_detail').appFormValidator({
                    rules: {
                        actual_size: 'required',
                        quality_check_results: 'required',
                    },
                    onSubmit: SubmitHandler,
                    messages: {
                    },
                });
            <?php }else{ ?>
                $('#add_edit_quality_check_detail').appFormValidator({
                    rules: {
                        quality_check_results: 'required',
                    },
                    onSubmit: SubmitHandler,
                    messages: {
                    },
                });
            <?php } ?>


            $('#wizard-picture').on('change', function() {
                "use strict";

                readURL(this);
            });

            if($('#dropzoneDragArea').length > 0){
                CheckDetailDropzone = new Dropzone("#add_edit_quality_check_detail", appCreateDropzoneOptions({
                    autoProcessQueue: false,
                    clickable: '#dropzoneDragArea',
                    previewsContainer: '.dropzone-previews',
                    addRemoveLinks: true,
                    maxFiles: 20,

                    success:function(file,response){
                        response = JSON.parse(response);
                        if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                            if(response.success == true || response.success == 'true'){
                                alert_float('success', response.message);
                            }
                            $("#quality_check_detailModal").modal("hide");
                            $('.table-detailed_checklist_table').DataTable().ajax.reload();
                        }else{
                            CheckDetailDropzone.processQueue();
                        }
                    },
                }));
            }

            $("body").on("click", ".quality_check_detail_submit_button", function () {
                var that = $(this);
                var form = $('#add_edit_quality_check_detail');
                if (form.valid()) {
                    if (that.hasClass("quality_check_pass")) {
                        form.append(hidden_input("quality_check", "passed"));
                    } else if (that.hasClass("quality_check_fail")) {
                        form.append(hidden_input("quality_check", "failed"));
                    } else if (that.hasClass("quality_check_validate")) {
                        form.append(hidden_input("quality_check", "validate"));
                    }
                }
            form.submit();
        });


            $('#add_edit_quality_check_detail input[name="actual_size"]').on('change', function() {
                "use strict";

                var actual_size = $(this).val();
                var check_norm = $('#add_edit_quality_check_detail input[name="check_norm"]').val();
                var check_tolerance_from = $('#add_edit_quality_check_detail input[name="check_tolerance_from"]').val();
                var check_tolerance_to = $('#add_edit_quality_check_detail input[name="check_tolerance_to"]').val();
                var unit_of_measurement = $('#add_edit_quality_check_detail input[name="unit_of_measurement"]').val();

                $('.measure_warning').addClass('hide');

                if(parseFloat(check_tolerance_from) <= parseFloat(actual_size) && parseFloat(actual_size) <= parseFloat(check_tolerance_to)){

                }else{
                    $('.measure_warning').removeClass('hide');
                    $('.measure_warning').html('<?php echo _l('quality_you_measured') ?>'+ actual_size+' '+unit_of_measurement+' ' + '<?php echo _l('quality_and_it_should_be_between') ?>'+ check_tolerance_from + ' '+unit_of_measurement+' '+ '<?php echo _l('quality_and') ?>' +' '+check_tolerance_to+ ' '+ unit_of_measurement+' ');
                }

            });


        });

    });

    function SubmitHandler(form) {
        "use strict";

        var quality_check_detail = $('#add_edit_quality_check_detail input[name="quality_check"]').val();
        var quality_check_type = '<?php echo html_entity_decode($quality_check->type); ?>';
        var message_if_failure = '';
        <?php if($quality_check->type == 'take_a_picture' && $quality_check_detail->status == 'to_do'){ ?>
            if(CheckDetailDropzone.getQueuedFiles().length == 0){
                alert_float('warning', '<?php echo _l('quality_please_attach_pictures'); ?>');
                return;
            }
        <?php } ?>

        <?php if($quality_check->type == 'pass_fail' || $quality_check->type == 'measure'){ ?>
            if(quality_check_detail == 'failed'){
                message_if_failure = '<?php echo html_entity_decode($quality_check->message_if_failure) ?>';
            }
        <?php } ?>

        form = $('#add_edit_quality_check_detail');

        var quality_check_detail_id = 0;
        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);

        $('#box-loading').show();
        $('.quality_check_submit_button').attr( "disabled", "disabled" );

        $.ajax({
            type: $(form).attr("method"),
            data: formData,
            mimeType: $(form).attr("enctype"),
            contentType: false,
            cache: false,
            processData: false,
            url: formURL,
        }).done(function(response) {
            var response = JSON.parse(response);
            $('#box-loading').addClass('hide');

            if (response.quality_check_detail_id) {
                quality_check_detail_id = response.quality_check_detail_id;
                checklists_processed = response.checklists_processed;

                if(typeof(CheckDetailDropzone) !== 'undefined'){
                    if (CheckDetailDropzone.getQueuedFiles().length > 0) {

                        CheckDetailDropzone.options.url = admin_url + 'quality/add_quality_check_detail_attachment/' + response.quality_check_detail_id;
                        CheckDetailDropzone.processQueue();
                    } else {
                        if(response.success == true || response.success == 'true'){
                            if(message_if_failure != ''){ 
                                alert_float('warning', message_if_failure);
                            }else{ 
                                alert_float('success', response.message);
                            } 

                            $("#quality_check_detailModal").modal("hide");
                            $('.table-detailed_checklist_table').DataTable().ajax.reload();
                        }
                    }
                } else {
                    if(response.success == true || response.success == 'true'){
                        if(message_if_failure != ''){ 
                            alert_float('warning', message_if_failure);
                        }else{ 
                            alert_float('success', response.message);
                        }
                    }
                }
                if(checklists_processed == true || checklists_processed == 'true'){
                    location.reload();
                }
            } else {
                if(response.success == true || response.success == 'true'){
                    if(message_if_failure != ''){ 
                        alert_float('warning', message_if_failure);
                    }else{ 
                        alert_float('success', response.message);
                    }
                }
            }

            if(response.success == true || response.success == 'true'){
                if(message_if_failure != ''){ 
                    alert_float('warning', message_if_failure);
                }else{ 
                    alert_float('success', response.message);
                }
            }
            $("#quality_check_detailModal").modal("hide");
            $('.table-detailed_checklist_table').DataTable().ajax.reload();

        });
        return false;
    }

    function readURL(input) {
        "use strict";

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#wizardPicturePreview').attr('src', e.target.result).fadeIn('slow');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function preview_file(invoker){
        'use strict';

        var id = $(invoker).attr('id');
        var rel_id = $(invoker).attr('rel_id');
        view_file(id, rel_id);
    }

    function view_file(id, rel_id) {   
        'use strict';

        $('#quality_check_detail_pdf_file_data').empty();
        $("#quality_check_detail_pdf_file_data").load(admin_url + 'quality/preview_file/' + id + '/' + rel_id, function(response, status, xhr) {
            if (status == "error") {
                alert_float('danger', xhr.statusText);
            }
        });
    }


</script>