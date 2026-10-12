<script type="text/javascript">
    var controlPointDropzone, quality_check_id;
    $(function(){
        'use strict';

        $( document ).ready(function() {
            form_init_editor('.tinymce', {height:150, auto_focus: true});

            if($('#add_edit_quality_check input[name="id"]').val() != undefined && $('#add_edit_quality_check input[name="id"]').val() != ''){
            // is edit
                quality_check_appFormValidator(false);
            }else{

                quality_check_appFormValidator(true);
            }

        $('#wizard-picture').on('change', function() {
            "use strict";

            readURL(this);
        });

        if($('#dropzoneDragArea').length > 0){
            controlPointDropzone = new Dropzone("#add_edit_quality_check", appCreateDropzoneOptions({
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
                        $("#quality_checkModal").modal("hide");
                        $('.table-quality_check_table').DataTable().ajax.reload();

                        var quality_check_detail = $('input[name="quality_check_detail"]').val();
                        if(quality_check_detail == 1){
                            window.location.assign('<?php echo admin_url('quality/quality_check_detail/') ?>'+quality_check_id+'?tab=detail');
                        }

                    }else{
                        controlPointDropzone.processQueue();
                    }
                },
            }));
        }

        $("body").on("change", "select[name='operation']", function () {
            var operation = $(this).val();
            var control_per = $("select[name='control_per']").val();

            $('.work_order_hide').addClass('hide');
            $('.goods_receipt_hide').addClass('hide');
            $('.goods_delivery_hide').addClass('hide');
            $('.internal_delivery_hide').addClass('hide');
            $('.failure_location_hide').addClass('hide');
            if(control_per == 'quantity' && operation != 'manufacturing'){
                $('.failure_location_hide').removeClass('hide');
            }

            $("#add_edit_quality_check select[name='work_order_id']").html('');
            $("#add_edit_quality_check select[name='goods_receipt_id']").html('');
            $("#add_edit_quality_check select[name='goods_delivery_id']").html('');
            $("#add_edit_quality_check select[name='internal_delivery_id']").html('');

            quality_check_appFormValidator(true);
        });

        $("body").on("change", "select[name='control_per']", function () {
            var control_per = $(this).val();
            var operation = $("select[name='operation']").val();
            if(control_per == 'quantity'){
                $('.control_per_qty_hide').removeClass('hide');
            }else{
                $('.control_per_qty_hide').addClass('hide');
            }

            var type = $('select[name="type"]').val();
            $('.failure_location_hide').addClass('hide');
            if(control_per == 'quantity' && operation != 'manufacturing'){
                $('.failure_location_hide').removeClass('hide');
            }
            quality_check_appFormValidator(true);
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

        $('input[name="partial_transfer_test"]').on('click', function() {
            "use strict";

            if($(this).is(':checked')){
                $('.percentage_hide').removeClass('hide');
            }else{
                $('.percentage_hide').addClass('hide');
            }
            
        });

        $('select[name="item_id"]').on('change', function() {
            "use strict";

            $("#add_edit_quality_check select[name='work_order_id']").html('');
            $("#add_edit_quality_check select[name='goods_receipt_id']").html('');
            $("#add_edit_quality_check select[name='goods_delivery_id']").html('');
            $("#add_edit_quality_check select[name='internal_delivery_id']").html('');

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

        $('select[name="control_point_id"]').on('change', function() {
            "use strict";
            var control_point_id = $(this).val();
            $.get(admin_url + 'quality/get_quality_control_point/' + control_point_id, function (response) {

                $("#add_edit_quality_check select[name='item_id']").val('').change();
                $("#add_edit_quality_check select[name='control_per']").val('quantity').change();
                $("#add_edit_quality_check input[name='partial_transfer_test']").prop('checked', true);
                $("#add_edit_quality_check input[name='percentage']").val(0);
                $("#add_edit_quality_check select[name='operation']").val('').change();
                $("#add_edit_quality_check select[name='type']").val('pass_fail').change();
                $("#add_edit_quality_check select[name='team_id']").val('').change();
                tinyMCE.get('instructions').setContent('');
                tinyMCE.get('note').setContent('');
                $("#add_edit_quality_check textarea[name='message_if_failure']").val('');
                $("#add_edit_quality_check select[name='type_of_unit_of_measurement']").val('').change();
                $("#add_edit_quality_check input[name='norm']").val(0);
                $("#add_edit_quality_check input[name='unit_of_measurement']").val('');
                $("#add_edit_quality_check input[name='tolerance_from']").val(0);
                $("#add_edit_quality_check input[name='tolerance_to']").val(0);

                if(response.status == true || response.status == 'true'){
                    $("#add_edit_quality_check select[name='item_id']").val(response.control_point.item_id).change();
                    $("#add_edit_quality_check select[name='control_per']").val(response.control_point.control_per).change();
                    if(response.control_point.partial_transfer_test == 1 || response.control_point.partial_transfer_test == '1'){
                        $('.percentage_hide').removeClass('hide');
                        $("#add_edit_quality_check input[name='partial_transfer_test']").prop('checked', true);
                    }else{
                        $('.percentage_hide').addClass('hide');
                        $("#add_edit_quality_check input[name='partial_transfer_test']").prop('checked', false);
                    }
                    $("#add_edit_quality_check input[name='percentage']").val(response.control_point.percentage);
                    $("#add_edit_quality_check select[name='operation']").val(response.control_point.operation).change();
                    $("#add_edit_quality_check select[name='type']").val(response.control_point.type).change();
                    $("#add_edit_quality_check select[name='team_id']").val(response.control_point.team_id).change();
                    tinyMCE.get('instructions').setContent(response.control_point.instructions);
                    tinyMCE.get('note').setContent(response.control_point.note);
                    $("#add_edit_quality_check textarea[name='message_if_failure']").val(response.control_point.message_if_failure);
                    $("#add_edit_quality_check select[name='type_of_unit_of_measurement']").val(response.control_point.type_of_unit_of_measurement).change();
                    $("#add_edit_quality_check input[name='norm']").val(response.control_point.norm);
                    $("#add_edit_quality_check input[name='unit_of_measurement']").val(response.control_point.unit_of_measurement);
                    $("#add_edit_quality_check input[name='tolerance_from']").val(response.control_point.tolerance_from);
                    $("#add_edit_quality_check input[name='tolerance_to']").val(response.control_point.tolerance_to);
                    if(response.control_point.failure_location_ids != '' && response.control_point.failure_location_ids != null){
                        var arr_failure_location_ids = response.control_point.failure_location_ids.split(',');
                        setTimeout(function() {

                            $("#add_edit_quality_check select[name='failure_location_ids[]']").val(arr_failure_location_ids).change();
                        }, 1000); 
                    }else{
                        $("#add_edit_quality_check select[name='failure_location_ids[]']").val([]).change();
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

        form = $('#add_edit_quality_check');
        
        var quality_check_id = 0;
        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);
        formData.append("instructions", tinyMCE.get('instructions').getContent());
        formData.append("note", tinyMCE.get('note').getContent());

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

            if (response.quality_check_id) {
                quality_check_id = response.quality_check_id;
                if(typeof(controlPointDropzone) !== 'undefined'){
                    if (controlPointDropzone.getQueuedFiles().length > 0) {
                        
                        controlPointDropzone.options.url = admin_url + 'quality/add_quality_check_attachment/' + response.quality_check_id;
                        controlPointDropzone.processQueue();
                    } else {
                        if(response.success == true || response.success == 'true'){
                            alert_float('success', response.message);

                            $("#quality_checkModal").modal("hide");
                            $('.table-quality_check_table').DataTable().ajax.reload();

                            var quality_check_detail = $('input[name="quality_check_detail"]').val();
                            if(quality_check_detail == 1){
                                window.location.assign('<?php echo admin_url('quality/quality_check_detail/') ?>'+quality_check_id+'?tab=detail');
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
            $("#quality_checkModal").modal("hide");
            $('.table-quality_check_table').DataTable().ajax.reload();

            var quality_check_detail = $('input[name="quality_check_detail"]').val();
            if(quality_check_detail == 1){
                window.location.assign('<?php echo admin_url('quality/quality_check_detail/') ?>'+quality_check_id+'?tab=detail');
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

    var item_id = $("#add_edit_quality_check select[name='item_id']").val();

    $.get(admin_url + 'quality/get_work_order/' + item_id, function (response) {
        $("#add_edit_quality_check select[name='work_order_id']").html('');
        $('#add_edit_quality_check select[name="work_order_id"]').append(response.product_option_html);

        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');

    }, 'json');
}

function get_inventory_receipt() {
    "use strict";

    var item_id = $("#add_edit_quality_check select[name='item_id']").val();

    $.get(admin_url + 'quality/get_inventory_receipt/' + item_id, function (response) {
        $("#add_edit_quality_check select[name='goods_receipt_id']").html('');
        $('#add_edit_quality_check select[name="goods_receipt_id"]').append(response.inventory_receipt_option_html);

        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');

    }, 'json');
}

function get_inventory_delivery() {
    "use strict";

    var item_id = $("#add_edit_quality_check select[name='item_id']").val();

    $.get(admin_url + 'quality/get_inventory_delivery/' + item_id, function (response) {
        $("#add_edit_quality_check select[name='goods_delivery_id']").html('');
        $('#add_edit_quality_check select[name="goods_delivery_id"]').append(response.inventory_delivery_option_html);

        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');

    }, 'json');
}

function get_inventory_internal_delivery() {
    "use strict";

    var item_id = $("#add_edit_quality_check select[name='item_id']").val();

    $.get(admin_url + 'quality/get_inventory_internal_delivery/' + item_id, function (response) {
        $("#add_edit_quality_check select[name='internal_delivery_id']").html('');
        $('#add_edit_quality_check select[name="internal_delivery_id"]').append(response.inventory_internal_delivery_option_html);

        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');

    }, 'json');
}

function quality_check_appFormValidator(create) {
    "use strict";

    var operation = $("select[name='operation']").val();
    var control_per = $("select[name='control_per']").val();

    if(operation == 'manufacturing'){
        if(create){
            get_operation();
        }
        $('.work_order_hide').removeClass('hide');

        if(control_per == 'quantity'){
            $('#add_edit_quality_check').appFormValidator({
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
        }else{
            $('#add_edit_quality_check').appFormValidator({
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
        }

    }else if(operation == 'receipts'){
        if(create){

            get_inventory_receipt();
        }
        $('.goods_receipt_hide').removeClass('hide');

        if(control_per == 'quantity'){
            $('#add_edit_quality_check').appFormValidator({
                rules: {
                    number: 'required',
                    name: 'required',
                    responsible_id: 'required',
                    datecreated: 'required',
                    team_id: 'required',
                    operation: 'required',
                    goods_receipt_id: 'required',
                    'failure_location_ids[]': 'required',
                },
                onSubmit: SubmitHandler,
                messages: {
                },
            });
        }else{

            $('#add_edit_quality_check').appFormValidator({
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
        }

    }else if(operation == 'delivery_orders'){
        if(create){

            get_inventory_delivery();
        }
        $('.goods_delivery_hide').removeClass('hide');

        if(control_per == 'quantity'){
            $('#add_edit_quality_check').appFormValidator({
                rules: {
                    number: 'required',
                    name: 'required',
                    responsible_id: 'required',
                    datecreated: 'required',
                    team_id: 'required',
                    operation: 'required',
                    goods_delivery_id: 'required',
                    'failure_location_ids[]': 'required',
                },
                onSubmit: SubmitHandler,
                messages: {
                },
            });
        }else{

            $('#add_edit_quality_check').appFormValidator({
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
        }

    }else if(operation == 'internal_transfers'){
        if(create){
            get_inventory_internal_delivery();
        }
        $('.internal_delivery_hide').removeClass('hide');
        if(control_per == 'quantity'){
            $('#add_edit_quality_check').appFormValidator({
                rules: {
                    number: 'required',
                    name: 'required',
                    responsible_id: 'required',
                    datecreated: 'required',
                    team_id: 'required',
                    operation: 'required',
                    internal_delivery_id: 'required',
                    'failure_location_ids[]': 'required',

                },
                onSubmit: SubmitHandler,
                messages: {
                },
            });
        }else{

            $('#add_edit_quality_check').appFormValidator({
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

    }else{
        $('#add_edit_quality_check').appFormValidator({
            rules: {
                number: 'required',
                name: 'required',
                responsible_id: 'required',
                datecreated: 'required',
                team_id: 'required',
                operation: 'required',
                'failure_location_ids[]': 'required',
            },
            onSubmit: SubmitHandler,
            messages: {
            },
        });
    }
}


</script>