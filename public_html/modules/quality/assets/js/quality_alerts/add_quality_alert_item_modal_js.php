<script type="text/javascript">
    var CheckDetailDropzone, quality_check_detail_id;
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
                }
            }
            form.submit();
        });


        });

    });

    function SubmitHandler(form) {
        "use strict";

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
                if(typeof(CheckDetailDropzone) !== 'undefined'){
                    if (CheckDetailDropzone.getQueuedFiles().length > 0) {

                        CheckDetailDropzone.options.url = admin_url + 'quality/add_quality_check_detail_attachment/' + response.quality_check_detail_id;
                        CheckDetailDropzone.processQueue();
                    } else {
                        if(response.success == true || response.success == 'true'){
                            alert_float('success', response.message);

                            $("#quality_check_detailModal").modal("hide");
                            $('.table-detailed_checklist_table').DataTable().ajax.reload();
                        }
                    }
                } else {
                    if(response.success == true || response.success == 'true'){
                        alert_float('success', response.message);
                    }
                }
            } else {
                if(response.success == true || response.success == 'true'){
                    alert_float('success', response.message);
                }
            }

            if(response.success == true || response.success == 'true'){
                alert_float('success', response.message);
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