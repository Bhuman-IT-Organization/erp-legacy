<script type="text/javascript">
    var qualityAlertDropzone, quality_alert_id,work_order_id,goods_receipt_id,goods_delivery_id,internal_delivery_id;
    $(function(){
        'use strict';

        $( document ).ready(function() {
            form_init_editor('.tinymce', {height:150, auto_focus: true});

        $('#add_edit_quality_alert').appFormValidator({
            rules: {
                number: 'required',
                name: 'required',
                responsible_id: 'required',
                datecreated: 'required',
                team_id: 'required',
                operation: 'required',
                quality_check_id: 'required',
            },
            onSubmit: SubmitHandler,
            messages: {
            },
        });


        $('#wizard-picture').on('change', function() {
            "use strict";

            readURL(this);
        });

        if($('#dropzoneDragArea').length > 0){
            qualityAlertDropzone = new Dropzone("#add_edit_quality_alert", appCreateDropzoneOptions({
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
                        $("#quality_alertModal").modal("hide");
                        $('.table-quality_alert_table').DataTable().ajax.reload();

                        var quality_alert_detail = $('input[name="quality_alert_detail"]').val();
                        if(quality_alert_detail == 1){
                            window.location.assign('<?php echo admin_url('quality/quality_alert_detail/') ?>'+quality_alert_id+'?tab=detail');
                        }

                    }else{
                        qualityAlertDropzone.processQueue();
                    }
                },
            }));
        }

        $("body").on("change", "select[name='operation']", function () {
            var operation = $(this).val();
            $('.work_order_hide').addClass('hide');
            $('.goods_receipt_hide').addClass('hide');
            $('.goods_delivery_hide').addClass('hide');
            $('.internal_delivery_hide').addClass('hide');
            $("#add_edit_quality_alert select[name='work_order_id']").html('');
            $("#add_edit_quality_alert select[name='goods_receipt_id']").html('');
            $("#add_edit_quality_alert select[name='goods_delivery_id']").html('');
            $("#add_edit_quality_alert select[name='internal_delivery_id']").html('');

            if(operation == 'manufacturing'){
                get_operation();
                $('.work_order_hide').removeClass('hide');

                $('#add_edit_quality_alert').appFormValidator({
                    rules: {
                        number: 'required',
                        name: 'required',
                        responsible_id: 'required',
                        datecreated: 'required',
                        team_id: 'required',
                        operation: 'required',
                        work_order_id: 'required',
                    },
                    onSubmit: SubmitHandler,
                    messages: {
                    },
                });

            }else if(operation == 'receipts'){
                get_inventory_receipt();
               $('.goods_receipt_hide').removeClass('hide');

               $('#add_edit_quality_alert').appFormValidator({
                    rules: {
                        number: 'required',
                        name: 'required',
                        responsible_id: 'required',
                        datecreated: 'required',
                        team_id: 'required',
                        operation: 'required',
                        goods_receipt_id: 'required',
                    },
                    onSubmit: SubmitHandler,
                    messages: {
                    },
                });

            }else if(operation == 'delivery_orders'){
                get_inventory_delivery();
                $('.goods_delivery_hide').removeClass('hide');

                $('#add_edit_quality_alert').appFormValidator({
                    rules: {
                        number: 'required',
                        name: 'required',
                        responsible_id: 'required',
                        datecreated: 'required',
                        team_id: 'required',
                        operation: 'required',
                        goods_delivery_id: 'required',
                    },
                    onSubmit: SubmitHandler,
                    messages: {
                    },
                });

            }else if(operation == 'internal_transfers'){
                get_inventory_internal_delivery();
                $('.internal_delivery_hide').removeClass('hide');
                $('#add_edit_quality_alert').appFormValidator({
                    rules: {
                        number: 'required',
                        name: 'required',
                        responsible_id: 'required',
                        datecreated: 'required',
                        team_id: 'required',
                        operation: 'required',
                        internal_delivery_id: 'required',
                    },
                    onSubmit: SubmitHandler,
                    messages: {
                    },
                });
                
            }
        });

        $("body").on("change", "select[name='control_per']", function () {
            var control_per = $(this).val();
            if(control_per == 'quantity'){
                $('.control_per_qty_hide').removeClass('hide');
            }else{
                $('.control_per_qty_hide').addClass('hide');
            }
        });

        $("body").on("change", "select[name='type']", function () {
            var type = $(this).val();

            $('.measure_hide').addClass('hide');
            $('.message_if_failure_hide').addClass('hide');
            if(type == 'measure'){
                $('.measure_hide').removeClass('hide');
                $('.message_if_failure_hide').removeClass('hide');
                
            }else if(type == 'pass_fail'){
                $('.message_if_failure_hide').removeClass('hide');
            }
        });

        $('select[name="item_id"]').on('change', function() {
            "use strict";

            $("#add_edit_quality_alert select[name='work_order_id']").html('');
            $("#add_edit_quality_alert select[name='goods_receipt_id']").html('');
            $("#add_edit_quality_alert select[name='goods_delivery_id']").html('');
            $("#add_edit_quality_alert select[name='internal_delivery_id']").html('');

            var operation = $('select[name="operation"]').val();
            if(operation == 'manufacturing'){
                get_operation();
            }else if(operation == 'receipts'){
                get_inventory_receipt();
            }else if(operation == 'delivery_orders'){
                get_inventory_delivery();
            }else if(operation == 'internal_transfers'){
                get_inventory_internal_delivery();
            }

        });

        $('select[name="quality_check_id"]').on('change', function() {
            "use strict";
            var quality_check_id = $(this).val();
            $.get(admin_url + 'quality/get_quality_check/' + quality_check_id, function (response) {

                $("#add_edit_quality_alert select[name='item_id']").val('').change();
                $("#add_edit_quality_alert select[name='control_per']").val('quantity').change();
                $("#add_edit_quality_alert select[name='operation']").val('').change();
                $("#add_edit_quality_alert select[name='team_id']").val('').change();
                $("#add_edit_quality_alert select[name='work_order_id']").val('').change();
                $("#add_edit_quality_alert select[name='goods_receipt_id']").val('').change();
                $("#add_edit_quality_alert select[name='goods_delivery_id']").val('').change();
                $("#add_edit_quality_alert select[name='internal_delivery_id']").val('').change();

                if(response.status == true || response.status == 'true'){
                    $("#add_edit_quality_alert select[name='item_id']").val(response.quality_check.item_id).change();
                    $("#add_edit_quality_alert select[name='operation']").val(response.quality_check.operation).change();
                    $("#add_edit_quality_alert select[name='team_id']").val(response.quality_check.team_id).change();

                    if(response.quality_check.operation == 'manufacturing'){
                        work_order_id = response.quality_check.work_order_id;
                    }else if(response.quality_check.operation == 'receipts'){
                        goods_receipt_id = response.quality_check.goods_receipt_id;
                    }else if(response.quality_check.operation == 'delivery_orders'){
                        goods_delivery_id = response.quality_check.goods_delivery_id;
                    }else if(response.quality_check.operation == 'internal_transfers'){
                        internal_delivery_id = response.quality_check.internal_delivery_id;
                    }

                }

                init_selectpicker();
                $(".selectpicker").selectpicker('refresh');

            }, 'json');

        });


        });


    });

    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_quality_alert');
        
        var quality_alert_id = 0;
        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);
        formData.append("description", tinyMCE.get('description').getContent());
        formData.append("corrective_actions", tinyMCE.get('corrective_actions').getContent());
        formData.append("preventive_actions", tinyMCE.get('preventive_actions').getContent());

        $('#box-loading').show();
        $('.quality_alert_submit_button').attr( "disabled", "disabled" );

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

            if (response.quality_alert_id) {
                quality_alert_id = response.quality_alert_id;
                if(typeof(qualityAlertDropzone) !== 'undefined'){
                    if (qualityAlertDropzone.getQueuedFiles().length > 0) {
                        
                        qualityAlertDropzone.options.url = admin_url + 'quality/add_quality_alert_attachment/' + response.quality_alert_id;
                        qualityAlertDropzone.processQueue();
                    } else {
                        if(response.success == true || response.success == 'true'){
                            alert_float('success', response.message);

                            $("#quality_alertModal").modal("hide");
                            $('.table-quality_alert_table').DataTable().ajax.reload();

                            var quality_alert_detail = $('input[name="quality_alert_detail"]').val();
                            if(quality_alert_detail == 1){
                                window.location.assign('<?php echo admin_url('quality/quality_alert_detail/') ?>'+quality_alert_id+'?tab=detail');
                            }
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
            $("#quality_alertModal").modal("hide");
            $('.table-quality_alert_table').DataTable().ajax.reload();

            var quality_alert_detail = $('input[name="quality_alert_detail"]').val();
            if(quality_alert_detail == 1){
                window.location.assign('<?php echo admin_url('quality/quality_alert_detail/') ?>'+quality_alert_id+'?tab=detail');
            }
            
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


    // Function to init the tinymce editor
    function form_init_editor(selector, settings) {

        "use strict";

        if(tinymce.majorVersion + '.' + tinymce.minorVersion == '6.8.3'){
            tinymce.remove(selector);
            initQualityEditor(selector);
        }else{

           tinymce.remove(selector);

           selector = typeof(selector) == 'undefined' ? '.tinymce' : selector;
           var _editor_selector_check = $(selector);

           if (_editor_selector_check.length === 0) { return; }

           $.each(_editor_selector_check, function() {
             if ($(this).hasClass('tinymce-manual')) {
                $(this).removeClass('tinymce');
            }
        });

    // Original settings
           var _settings = {
               branding: false,
               selector: selector,
               browser_spellcheck: true,
               height: 400,
               theme: 'modern',
               skin: 'perfex',
               language: app.tinymce_lang,
               relative_urls: false,
               inline_styles: true,
               verify_html: false,
               cleanup: false,
               autoresize_bottom_margin: 25,
               valid_elements: '+*[*]',
               valid_children: "+body[style], +style[type]",
               apply_source_formatting: false,
               remove_script_host: false,
               removed_menuitems: 'newdocument restoredraft',
               forced_root_block: false,
               autosave_restore_when_empty: false,
               fontsize_formats: '8pt 10pt 12pt 14pt 18pt 24pt 36pt',
               setup: function(ed) {
            // Default fontsize is 12
                ed.on('init', function() {
                   this.getDoc().body.style.fontSize = '12pt';
               });
            },
            table_default_styles: {
            // Default all tables width 100%
                width: '100%',
            },
            plugins: [
                'advlist autoresize autosave lists link image print hr codesample',
                'visualblocks code fullscreen',
                'media save table contextmenu',
                'paste textcolor colorpicker'
                ],
            toolbar1: 'fontselect fontsizeselect | forecolor backcolor | bold italic | alignleft aligncenter alignright alignjustify | image link | bullist numlist | restoredraft',
            file_browser_callback: elFinderBrowser,
        };

    // Add the rtl to the settings if is true
        isRTL == 'true' ? _settings.directionality = 'rtl' : '';
        isRTL == 'true' ? _settings.plugins[0] += ' directionality' : '';

    // Possible settings passed to be overwrited or added
        if (typeof(settings) != 'undefined') {
           for (var key in settings) {
              if (key != 'append_plugins') {
                 _settings[key] = settings[key];
             } else {
                 _settings['plugins'].push(settings[key]);
             }
         }
     }

    // Init the editor
     var editor = tinymce.init(_settings);
     $(document).trigger('app.editor.initialized');

     return editor;
 }
}

function get_operation() {
    "use strict";

    var item_id = $("#add_edit_quality_alert select[name='item_id']").val();

    $.get(admin_url + 'quality/get_work_order/' + item_id, function (response) {
        $("#add_edit_quality_alert select[name='work_order_id']").html('');
        $('#add_edit_quality_alert select[name="work_order_id"]').append(response.product_option_html);
        if(work_order_id != ''){
            $('#add_edit_quality_alert select[name="work_order_id"]').val(work_order_id).change();
        }
        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');

    }, 'json');
}

function get_inventory_receipt() {
    "use strict";

    var item_id = $("#add_edit_quality_alert select[name='item_id']").val();

    $.get(admin_url + 'quality/get_inventory_receipt/' + item_id, function (response) {
        $("#add_edit_quality_alert select[name='goods_receipt_id']").html('');
        $('#add_edit_quality_alert select[name="goods_receipt_id"]').append(response.inventory_receipt_option_html);

        if(goods_receipt_id != ''){
            $('#add_edit_quality_alert select[name="goods_receipt_id"]').val(goods_receipt_id).change();
        }
        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');

    }, 'json');
}

function get_inventory_delivery() {
    "use strict";

    var item_id = $("#add_edit_quality_alert select[name='item_id']").val();

    $.get(admin_url + 'quality/get_inventory_delivery/' + item_id, function (response) {
        $("#add_edit_quality_alert select[name='goods_delivery_id']").html('');
        $('#add_edit_quality_alert select[name="goods_delivery_id"]').append(response.inventory_delivery_option_html);
        if(goods_delivery_id != ''){
            $('#add_edit_quality_alert select[name="goods_delivery_id"]').val(goods_delivery_id).change();
        }
        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');

    }, 'json');
}

function get_inventory_internal_delivery() {
    "use strict";
    
    var item_id = $("#add_edit_quality_alert select[name='item_id']").val();

    $.get(admin_url + 'quality/get_inventory_internal_delivery/' + item_id, function (response) {
        $("#add_edit_quality_alert select[name='internal_delivery_id']").html('');
        $('#add_edit_quality_alert select[name="internal_delivery_id"]').append(response.inventory_internal_delivery_option_html);
        if(internal_delivery_id != ''){
            $('#add_edit_quality_alert select[name="internal_delivery_id"]').val(internal_delivery_id).change();
        }
        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');

    }, 'json');
}



</script>