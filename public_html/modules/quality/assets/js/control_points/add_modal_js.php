<script type="text/javascript">
    var controlPointDropzone, control_point_id;
    $(function(){
        'use strict';

        $( document ).ready(function() {
            form_init_editor('.tinymce', {height:150, auto_focus: true});

        control_point_appFormValidator();


        $('#wizard-picture').on('change', function() {
            "use strict";

            readURL(this);
        });

        if($('#dropzoneDragArea').length > 0){
            controlPointDropzone = new Dropzone("#add_edit_control_point", appCreateDropzoneOptions({
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
                        $("#control_pointModal").modal("hide");
                        $('.table-control_point_table').DataTable().ajax.reload();

                        var control_point_detail = $('input[name="control_point_detail"]').val();
                        if(control_point_detail == 1){
                            window.location.assign('<?php echo admin_url('quality/control_point_detail/') ?>'+control_point_id+'?tab=detail');
                        }

                    }else{
                        controlPointDropzone.processQueue();
                    }
                },
            }));
        }

        $("body").on("change", "select[name='operation']", function () {
            var operation = $(this).val();
            if(operation == 'manufacturing'){
                get_operation();
                $('.work_order_hide').removeClass('hide');
            }else{
                $('.work_order_hide').addClass('hide');
            }
            control_point_appFormValidator();
        });

        $("body").on("change", "select[name='control_per']", function () {
            var control_per = $(this).val();
            if(control_per == 'quantity'){
                $('.control_per_qty_hide').removeClass('hide');
            }else{
                $('.control_per_qty_hide').addClass('hide');
            }

            control_point_appFormValidator();
        });

        $("body").on("change", "select[name='control_frequency']", function () {
            var control_frequency = $(this).val();

            if(control_frequency == 'all'){
                $('.control_frequency_percent_hide').addClass('hide');
                $('.control_periodically_percent_hide').addClass('hide');
            }else if(control_frequency == 'randomly'){
                $('.control_frequency_percent_hide').removeClass('hide');
                $('.control_periodically_percent_hide').addClass('hide');
            }else if(control_frequency == 'periodically'){
                $('.control_frequency_percent_hide').addClass('hide');
                $('.control_periodically_percent_hide').removeClass('hide');
            }else {
                $('.control_frequency_percent_hide').addClass('hide');
                $('.control_periodically_percent_hide').addClass('hide');
            }
        });

        $("body").on("change", "select[name='type']", function () {
            var type = $(this).val();
            var control_per = $('select[name="control_per"]').val();

            $('.measure_hide').addClass('hide');
            $('.message_if_failure_hide').addClass('hide');
            if(type == 'measure'){
                $('.measure_hide').removeClass('hide');
                $('.message_if_failure_hide').removeClass('hide');

            }else if(type == 'pass_fail'){
                $('.message_if_failure_hide').removeClass('hide');
            }

            $('.failure_location_hide').addClass('hide');
            if(control_per == 'quantity'){
                $('.failure_location_hide').removeClass('hide');
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

            get_operation();
            
        });


        });


    });

    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_control_point');
        
        var control_point_id = 0;
        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);
        formData.append("instructions", tinyMCE.get('instructions').getContent());
        formData.append("note", tinyMCE.get('note').getContent());

        $('#box-loading').show();
        $('.control_point_submit_button').attr( "disabled", "disabled" );

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

            if (response.control_point_id) {
                control_point_id = response.control_point_id;
                if(typeof(controlPointDropzone) !== 'undefined'){
                    if (controlPointDropzone.getQueuedFiles().length > 0) {
                        
                        controlPointDropzone.options.url = admin_url + 'quality/add_control_point_attachment/' + response.control_point_id;
                        controlPointDropzone.processQueue();
                    } else {
                        if(response.success == true || response.success == 'true'){
                            alert_float('success', response.message);

                            $("#control_pointModal").modal("hide");
                            $('.table-control_point_table').DataTable().ajax.reload();

                            var control_point_detail = $('input[name="control_point_detail"]').val();
                            if(control_point_detail == 1){
                                window.location.assign('<?php echo admin_url('quality/control_point_detail/') ?>'+control_point_id+'?tab=detail');
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
            $("#control_pointModal").modal("hide");
            $('.table-control_point_table').DataTable().ajax.reload();

            var control_point_detail = $('input[name="control_point_detail"]').val();
            if(control_point_detail == 1){
                window.location.assign('<?php echo admin_url('quality/control_point_detail/') ?>'+control_point_id+'?tab=detail');
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

    var item_id = $("#add_edit_control_point select[name='item_id']").val();

    $.get(admin_url + 'quality/get_operation/' + item_id, function (response) {
        $("#add_edit_control_point select[name='mrp_routing_detail_id']").html('');
        $('#add_edit_control_point select[name="mrp_routing_detail_id"]').append(response.product_option_html);

        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');

    }, 'json');
}

function control_point_appFormValidator() {
    "use strict";
    
    var control_per = $('select[name="control_per"]').val();
    var operation = $('select[name="operation"]').val();

    $('.failure_location_hide').addClass('hide');

    if(operation == 'manufacturing'){
        $('#add_edit_control_point').appFormValidator({
            rules: {
                number: 'required',
                name: 'required',
                responsible_id: 'required',
                datecreated: 'required',
                team_id: 'required',
                operation: 'required',
                mrp_routing_detail_id: 'required',
            },
            onSubmit: SubmitHandler,
            messages: {
            },
        });
    }else{
        if(control_per == 'quantity'){
            $('.failure_location_hide').removeClass('hide');
            $('#add_edit_control_point').appFormValidator({
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
        }else{
            $('#add_edit_control_point').appFormValidator({
                rules: {
                    number: 'required',
                    name: 'required',
                    responsible_id: 'required',
                    datecreated: 'required',
                    team_id: 'required',
                    operation: 'required',
                },
                onSubmit: SubmitHandler,
                messages: {
                },
            });
        }
    }
}

</script>