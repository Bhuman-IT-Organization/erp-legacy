    <div class="modal fade z-index-none" id="control_pointModal">
        <div class="modal-dialog setting-control_point-table modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo html_entity_decode($title) ?></h4>
                </div>
                <?php 
                $id = '';
                
                if(isset($control_point)){
                    $id = $control_point->id;
                }

                ?>
                <?php echo form_open_multipart(admin_url('quality/add_edit_control_point/'.$id), array('id' => 'add_edit_control_point', 'autocomplete'=>'off')); ?>
                <?php 

                $name = '';
                $item_id = '';
                $item_group = '';
                $operation = '';
                $mrp_routing_detail_id = '';
                $control_per = 'quantity';
                $partial_transfer_test = '';
                $percentage = '0.00';
                $control_frequency = 'all';
                $every_percent_of_transfer = '';
                $periodically_every_custom = '';
                $periodically_type_custom = '';
                $type = 'pass_fail';
                $team_id = '';
                $responsible_id = get_staff_user_id();
                $step_document = '';
                $worksheet_page = '';
                $instructions = '';
                $message_if_failure = '';
                $note = '';
                $datecreated = _dt('Y-m-d H:i:s');
                $is_partial_transfer_test = 'checked';
                $type_of_unit_of_measurement = '';
                $norm = '0';
                $unit_of_measurement = '';
                $tolerance_from = '0';
                $tolerance_to = '0';
                $measure_hide = ' hide';
                $work_order_hide = ' hide';
                $control_per_qty_hide = '';
                $control_frequency_percent_hide = ' hide';
                $control_periodically_percent_hide = ' hide';
                $message_if_failure_hide = '';
                $failure_location_hide = '';
                $failure_location_ids = '';
                $percentage_hide = '';
                $work_orders = isset($list_work_orders) ? $list_work_orders : [];

                if(isset($control_point)){
                    $name = $control_point->name;
                    $failure_location_ids = !is_null($control_point->failure_location_ids) ? explode(',', $control_point->failure_location_ids) : [] ;
                    $item_id = $control_point->item_id;
                    $item_group = $control_point->item_group;
                    $operation = $control_point->operation;
                    $mrp_routing_detail_id = $control_point->mrp_routing_detail_id;
                    $control_per = $control_point->control_per;
                    $partial_transfer_test = $control_point->partial_transfer_test;
                    $percentage = $control_point->percentage;
                    $control_frequency = $control_point->control_frequency;
                    $every_percent_of_transfer = $control_point->every_percent_of_transfer;
                    $periodically_every_custom = $control_point->periodically_every_custom;
                    $periodically_type_custom = $control_point->periodically_type_custom;
                    $type = $control_point->type;
                    $team_id = $control_point->team_id;
                    $responsible_id = $control_point->responsible_id;
                    $step_document = $control_point->step_document;
                    $worksheet_page = $control_point->worksheet_page;
                    $instructions = $control_point->instructions;
                    $message_if_failure = $control_point->message_if_failure;
                    $note = $control_point->note;
                    $datecreated = _dt($control_point->datecreated);
                    $type_of_unit_of_measurement = $control_point->type_of_unit_of_measurement;
                    $norm = $control_point->norm;
                    $unit_of_measurement = $control_point->unit_of_measurement;
                    $tolerance_from = $control_point->tolerance_from;
                    $tolerance_to = $control_point->tolerance_to;

                    if($control_point->partial_transfer_test == 1){
                        $is_partial_transfer_test = 'checked';
                        $percentage_hide = '';
                    }else{
                        $is_partial_transfer_test = '';
                        $percentage_hide = 'hide';

                    }

                    if($control_point->type == 'measure'){
                        $measure_hide = '';
                    }
                    if($control_point->operation == 'manufacturing'){
                        $work_order_hide = '';
                    }
                    if($control_point->control_per == 'quantity'){
                        $control_per_qty_hide = '';
                    }else{
                        $control_per_qty_hide = ' hide';
                    }
                    

                    if($control_point->control_frequency == 'all'){
                        $control_frequency_percent_hide = ' hide';
                        $control_periodically_percent_hide = ' hide';
                    }elseif($control_point->control_frequency == 'randomly'){
                        $control_frequency_percent_hide = ' ';
                        $control_periodically_percent_hide = ' hide';

                    }elseif($control_point->control_frequency == 'periodically'){
                        $control_frequency_percent_hide = ' hide';
                        $control_periodically_percent_hide = ' ';
                    }else{
                        $control_frequency_percent_hide = ' hide';
                        $control_periodically_percent_hide = ' hide';
                    }

                    if($control_point->type == 'pass_fail' || $control_point->type == 'measure'){
                        $message_if_failure_hide = '';
                    }else{
                        $message_if_failure_hide = ' hide';
                    }

                    if($control_point->control_per == 'quantity'){
                        $failure_location_hide = '';
                    }else{
                        $failure_location_hide = ' hide';
                    }
                    
                }
        

                ?>
               
                <input type="hidden" name="id" value="<?php echo html_entity_decode($id); ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?php

                            $next_control_point_number = get_option('quality_control_point_number');
                            $format               = get_option('quality_control_point_number_format');

                            if (isset($control_point)) {
                                $format = $control_point->number_format;
                            }

                            $prefix = get_option('quality_control_point_prefix');

                            if ($format == 1) {
                                $__number = $next_control_point_number;
                                if (isset($control_point)) {
                                    $__number = $control_point->number;
                                    $prefix   = '<span id="prefix">' . $control_point->prefix . '</span>';
                                }
                            } elseif ($format == 2) {
                                if (isset($control_point)) {
                                    $__number = $control_point->number;
                                    $prefix   = $control_point->prefix;
                                    $prefix   = '<span id="prefix">' . $prefix . '</span><span id="prefix_year">' . date('Y', strtotime($control_point->datecreated)) . '</span>/';
                                } else {
                                    $__number = $next_control_point_number;
                                    $prefix   = $prefix . '<span id="prefix_year">' . date('Y') . '</span>/';
                                }
                            } elseif ($format == 3) {
                                if (isset($control_point)) {
                                    $yy       = date('y', strtotime($control_point->datecreated));
                                    $__number = $control_point->number;
                                    $prefix   = '<span id="prefix">' . $control_point->prefix . '</span>';
                                } else {
                                    $yy       = date('y');
                                    $__number = $next_control_point_number;
                                }
                            } elseif ($format == 4) {
                                if (isset($control_point)) {
                                    $yyyy     = date('Y', strtotime($control_point->datecreated));
                                    $mm       = date('m', strtotime($control_point->datecreated));
                                    $__number = $control_point->number;
                                    $prefix   = '<span id="prefix">' . $control_point->prefix . '</span>';
                                } else {
                                    $yyyy     = date('Y');
                                    $mm       = date('m');
                                    $__number = $next_control_point_number;
                                }
                            }

                            $_control_point_number     = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
                            $isedit               = isset($control_point) ? 'true' : 'false';
                            $data_original_number = isset($control_point) ? $control_point->number : 'false';
                            ?>
                            <div class="form-group">
                                <label for="number"><?php echo _l('quality_control_point_number_label'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-addon">

                                        <?php 
                                        echo new_html_entity_decode($prefix);
                                        ?>
                                    </span>
                                    <input type="text" name="number" id="number" class="form-control" value="<?php echo e($_control_point_number); ?>"
                                    data-isedit="<?php echo e($isedit); ?>"
                                    data-original-number="<?php echo e($data_original_number); ?>">
                                    <?php if ($format == 3) { ?>
                                        <span class="input-group-addon">
                                            <span id="prefix_year" class="format-n-yy"><?php echo e($yy); ?></span>
                                        </span>
                                    <?php } elseif ($format == 4) { ?>
                                        <span class="input-group-addon">
                                            <span id="prefix_month" class="format-mm-yyyy"><?php echo e($mm); ?></span>
                                            /
                                            <span id="prefix_year" class="format-mm-yyyy"><?php echo e($yyyy); ?></span>
                                        </span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php echo render_select('control_per', $quality_control_pers, ['name', 'label'], 'quality_control_per', $control_per, [], [], '', '', false); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php echo render_input('name', 'quality_name', $name); ?>
                                    <?php echo render_select('item_id', $items, ['itemid', ['description']], 'quality_item', $item_id); ?>
                                    <?php echo render_select('operation', $quality_operations, ['name', ['label']], 'quality_operation', $operation); ?>

                                    <div class="work_order_hide <?php echo html_entity_decode($work_order_hide); ?>">
                                        <?php echo render_select('mrp_routing_detail_id', $work_orders, ['id', ['operation']], 'quality_work_order', $mrp_routing_detail_id); ?>
                                    </div>

                                    <?php echo render_select('team_id', $teams, ['id', ['name']], 'quality_team', $team_id); ?>
                                    <?php echo render_select('responsible_id', $staffs, ['staffid', ['firstname', 'lastname']], 'quality_responsible', $responsible_id); ?>

                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row control_per_qty_hide <?php echo html_entity_decode($control_per_qty_hide); ?>">
                                        <div class="col-md-6">
                                            <div class="form-group mtop30">
                                                <div class="checkbox checkbox-primary">
                                                    <input type="checkbox" id="partial_transfer_test" name="partial_transfer_test" value="1" <?php echo html_entity_decode($is_partial_transfer_test); ?>>
                                                    <label for="partial_transfer_test"><?php echo _l('quality_partial_transfer_test'); ?></label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 percentage_hide <?php echo  html_entity_decode($percentage_hide); ?>">
                                            <?php echo render_input('percentage', 'quality_percentage_of_products', $percentage, 'number', ['step' => 'any', 'min' => '0', 'max' => 100]); ?>
                                        </div>
                                    </div>

                                    <?php echo render_select('control_frequency', $quality_control_frequency, ['name', ['label']], 'quality_control_frequency', $control_frequency, [], [], '', '', false); ?>

                                    <div class="control_frequency_percent_hide <?php echo html_entity_decode($control_frequency_percent_hide); ?>">
                                        <?php echo render_input('every_percent_of_transfer', 'quality_every_percent_of_transfer', $every_percent_of_transfer, 'number', ['step' => 'any', 'min' => '0', 'max' => 100]); ?>
                                    </div>
                                    <div class="row control_periodically_percent_hide <?php echo html_entity_decode($control_periodically_percent_hide); ?>">
                                        <div class="col-md-6">
                                            <?php echo render_input('periodically_every_custom', 'quality_periodically_every_custom', $periodically_every_custom, 'number', ['step' => '1', 'min' => '0']); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php echo render_select('periodically_type_custom', $quality_interval_types, ['name', ['label']], 'quality_periodically_type_custom', $periodically_type_custom); ?>
                                            
                                        </div>
                                    </div>
                                    <?php echo render_select('type', $quality_control_type, ['name', ['label']], 'quality_type', $type, [], [], '', '', false); ?>

                                    <div class="row failure_location_hide <?php echo html_entity_decode($failure_location_hide); ?>">
                                        <div class="col-md-12">
                                            <?php echo render_select('failure_location_ids[]', $failure_locations, ['warehouse_id', 'warehouse_code', 'warehouse_name'], 'quality_failure_location', $failure_location_ids, ['multiple' => true], [], '', '', false); ?>
                                        </div>
                                    </div>

                                    <div class="row measure_hide <?php echo html_entity_decode($measure_hide); ?>">
                                        <div class="col-md-12">
                                            <?php echo render_select('type_of_unit_of_measurement', $quality_type_of_unit_of_measurements, ['name', 'label', 'units'], 'quality_type_of_unit_of_measurement', $type_of_unit_of_measurement); ?>

                                        </div>
                                        <div class="col-md-6">
                                            <?php echo render_input('norm', 'quality_norm', $norm, 'number', ['step' => 'any', 'min' => '0.00']); ?>

                                        </div>
                                        <div class="col-md-6">
                                            <?php echo render_input('unit_of_measurement', 'quality_unit_of_measurement', $unit_of_measurement, 'text'); ?>

                                        </div>
                                        <div class="col-md-6">
                                            <?php echo render_input('tolerance_from', 'quality_tolerance_from', $tolerance_from, 'number', ['step' => 'any', 'min' => '0.00']); ?>

                                        </div>
                                        <div class="col-md-6">
                                            <?php echo render_input('tolerance_to', 'quality_tolerance_to', $tolerance_to, 'number', ['step' => 'any', 'min' => '0.00']); ?>

                                        </div>
                                        
                                    </div>


                                    <?php echo render_datetime_input('datecreated', 'quality_datecreated', $datecreated); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <?php   echo render_textarea('instructions','quality_instructions', $instructions, array('rows'=>6,'placeholder'=>_l('quality_add_instructions'),'data-task-ae-editor'=>true, !is_mobile() ? 'onclick' : 'onfocus'=>(!isset($control_point) || isset($control_point) && $control_point->instructions == '' ? 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' : 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' )),array(),'','tinymce'); ?>
                        </div>
                    </div>
                    <div class="row message_if_failure_hide <?php echo html_entity_decode($message_if_failure_hide); ?>">
                        <div class="col-md-12">
                            <?php   echo render_textarea('message_if_failure','quality_message_if_failure', $message_if_failure, array('rows'=>6,'placeholder'=>_l('quality_add_message_if_failure'))); ?>
                        </div>
                    </div>
                    
                    
                    <div class="row">
                        <div class="col-md-12">
                            <?php   echo render_textarea('note','quality_note', $note, array('rows'=>6,'placeholder'=>_l('quality_add_note'),'data-task-ae-editor'=>true, !is_mobile() ? 'onclick' : 'onfocus'=>(!isset($control_point) || isset($control_point) && $control_point->note == '' ? 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' : 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' )),array(),'','tinymce'); ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5><?php echo _l('quality_attachments'); ?></h5>

                            <div id="dropzoneDragArea" class="dz-default dz-message">
                                <span><?php echo _l('quality_attachments'); ?></span>
                            </div>
                            <div class="dropzone-previews"></div>

                            <div id="images_old_preview">
                                <?php if( isset($control_point_attachments) && count($control_point_attachments) > 0){ ?>
                                    <?php foreach ($control_point_attachments as $product_attachment) { ?>

                                        <div class="pdf_attachment dz-preview dz-image-preview image_old <?php echo new_html_entity_decode($product_attachment['id']) ?>">
                                            <div class="dz-image">
                                                <?php if(is_image(CONTROL_POINT_FOLDER . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>
                                                <?php if(file_exists(CONTROL_POINT_FOLDER . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>

                                                    <img class="dz-image" src="<?php echo site_url('modules/quality/uploads/control_points/' . $product_attachment['rel_id'] . '/' . $product_attachment['file_name']) . '" alt="' . $product_attachment['file_name'] ?>" >

                                                <?php } ?>
                                                <?php } ?>
                                            </div>
                                            <?php if(!is_image(CONTROL_POINT_FOLDER . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>
                                            <div class="dz-details dz-active"><div class="dz-size"><span data-dz-size=""></span></div>    <div class="dz-filename"><span data-dz-name=""><?php echo html_entity_decode($product_attachment['file_name']); ?></span></div>  </div>
                                                <?php } ?>



                                            <div class="dz-error-mark">
                                                <a class="dz-remove" data-dz-remove>Remove file</a>
                                            </div>
                                            <div class="remove_file">
                                                <a href="#" class="text-danger" onclick="delete_control_point_attachment(this,<?php echo new_html_entity_decode($product_attachment['id']); ?>); return false;"><i class="fa fa fa-times"></i></a>
                                            </div>
                                        </div>

                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                    <button type="submit" class="btn btn-info control_point_submit_button"><?php echo _l('submit'); ?></button>
                </div>
            </div>

        </div>

        <?php echo form_close(); ?>
    </div>
</div>

<?php require 'modules/quality/assets/js/control_points/add_modal_js.php';  ?>
