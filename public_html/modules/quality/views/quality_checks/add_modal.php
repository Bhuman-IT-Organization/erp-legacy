    <div class="modal fade z-index-none" id="quality_checkModal">
        <div class="modal-dialog setting-quality_check-table modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo html_entity_decode($title) ?></h4>
                </div>
                <?php 
                $id = '';
                
                if(isset($quality_check)){
                    $id = $quality_check->id;
                }

                ?>
                <?php echo form_open_multipart(admin_url('quality/add_edit_quality_check/'.$id), array('id' => 'add_edit_quality_check', 'autocomplete'=>'off')); ?>
                <?php 

                $control_point_id = '';
                $company = '';
                $name = '';
                $item_id = '';
                $operation = '';
                $work_order_id = '';
                $control_per = 'quantity';
                $partial_transfer_test = '';
                $percentage = '0.00';
                $type = 'pass_fail';
                $team_id = '';
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
                $control_periodically_percent_hide = ' hide';
                $message_if_failure_hide = '';
                $goods_receipt_hide = ' hide';
                $goods_delivery_hide = ' hide';
                $internal_delivery_hide = ' hide';
                $goods_receipt_id = '';
                $goods_delivery_id = '';
                $internal_delivery_id = '';
                $failure_location_hide = '';
                $failure_location_ids = '';
                $percentage_hide = '';

                if(isset($quality_check)){
                    $failure_location_ids = !is_null($quality_check->failure_location_ids) ? explode(',', $quality_check->failure_location_ids) : [] ;
                    $control_point_id = $quality_check->control_point_id;
                    $company = $quality_check->company;
                    $name = $quality_check->name;
                    $item_id = $quality_check->item_id;
                    $operation = $quality_check->operation;
                    $work_order_id = $quality_check->work_order_id;
                    $control_per = $quality_check->control_per;
                    $partial_transfer_test = $quality_check->partial_transfer_test;
                    $percentage = $quality_check->percentage;
                    $type = $quality_check->type;
                    $team_id = $quality_check->team_id;
                    $instructions = $quality_check->instructions;
                    $message_if_failure = $quality_check->message_if_failure;
                    $note = $quality_check->note;
                    $datecreated = _dt($quality_check->datecreated);
                    $type_of_unit_of_measurement = $quality_check->type_of_unit_of_measurement;
                    $norm = $quality_check->norm;
                    $unit_of_measurement = $quality_check->unit_of_measurement;
                    $tolerance_from = $quality_check->tolerance_from;
                    $tolerance_to = $quality_check->tolerance_to;
                    $goods_receipt_id = $quality_check->goods_receipt_id;
                    $goods_delivery_id = $quality_check->goods_delivery_id;
                    $internal_delivery_id = $quality_check->internal_delivery_id;

                    if($quality_check->partial_transfer_test == 1){
                        $is_partial_transfer_test = 'checked';
                        $percentage_hide = '';
                    }else{
                        $is_partial_transfer_test = '';
                        $percentage_hide = 'hide';
                    }
                    if($quality_check->type == 'measure'){
                        $measure_hide = '';
                    }
                    if($quality_check->operation == 'manufacturing'){
                        $work_order_hide = '';
                    }elseif($quality_check->operation == 'receipts'){
                        $goods_receipt_hide = '';
                    }elseif($quality_check->operation == 'delivery_orders'){
                        $goods_delivery_hide = '';
                    }elseif($quality_check->operation == 'internal_transfers'){
                        $internal_delivery_hide = '';
                    }

                    if($quality_check->control_per == 'quantity'){
                        $control_per_qty_hide = '';
                    }else{
                        $control_per_qty_hide = ' hide';
                    }
                    
                    if($quality_check->type == 'pass_fail' || $quality_check->type == 'measure'){
                        $message_if_failure_hide = '';
                    }else{
                        $message_if_failure_hide = ' hide';
                    }

                    if($quality_check->control_per == 'quantity' && $quality_check->operation != 'manufacturing'){
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

                            $next_quality_check_number = get_option('quality_check_number');
                            $format               = get_option('quality_check_number_format');

                            if (isset($quality_check)) {
                                $format = $quality_check->number_format;
                            }

                            $prefix = get_option('quality_check_prefix');

                            if ($format == 1) {
                                $__number = $next_quality_check_number;
                                if (isset($quality_check)) {
                                    $__number = $quality_check->number;
                                    $prefix   = '<span id="prefix">' . $quality_check->prefix . '</span>';
                                }
                            } elseif ($format == 2) {
                                if (isset($quality_check)) {
                                    $__number = $quality_check->number;
                                    $prefix   = $quality_check->prefix;
                                    $prefix   = '<span id="prefix">' . $prefix . '</span><span id="prefix_year">' . date('Y', strtotime($quality_check->datecreated)) . '</span>/';
                                } else {
                                    $__number = $next_quality_check_number;
                                    $prefix   = $prefix . '<span id="prefix_year">' . date('Y') . '</span>/';
                                }
                            } elseif ($format == 3) {
                                if (isset($quality_check)) {
                                    $yy       = date('y', strtotime($quality_check->datecreated));
                                    $__number = $quality_check->number;
                                    $prefix   = '<span id="prefix">' . $quality_check->prefix . '</span>';
                                } else {
                                    $yy       = date('y');
                                    $__number = $next_quality_check_number;
                                }
                            } elseif ($format == 4) {
                                if (isset($quality_check)) {
                                    $yyyy     = date('Y', strtotime($quality_check->datecreated));
                                    $mm       = date('m', strtotime($quality_check->datecreated));
                                    $__number = $quality_check->number;
                                    $prefix   = '<span id="prefix">' . $quality_check->prefix . '</span>';
                                } else {
                                    $yyyy     = date('Y');
                                    $mm       = date('m');
                                    $__number = $next_quality_check_number;
                                }
                            }

                            $_quality_check_number     = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
                            $isedit               = isset($quality_check) ? 'true' : 'false';
                            $data_original_number = isset($quality_check) ? $quality_check->number : 'false';
                            ?>
                            <div class="form-group">
                                <label for="number"><?php echo _l('quality_check_number_label'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-addon">

                                        <?php 
                                        echo new_html_entity_decode($prefix);
                                        ?>
                                    </span>
                                    <input type="text" name="number" id="number" class="form-control" value="<?php echo e($_quality_check_number); ?>"
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
                                    <?php echo render_select('control_point_id', $control_points, ['id', ['name']], 'quality_control_point', $control_point_id); ?>

                                    <?php echo render_input('name', 'quality_name', $name); ?>
                                    <?php echo render_select('item_id', $items, ['itemid', ['description']], 'quality_item', $item_id); ?>
                                    <?php echo render_select('operation', $quality_operations, ['name', ['label']], 'quality_operation', $operation); ?>

                                    <div class="work_order_hide <?php echo html_entity_decode($work_order_hide); ?>">
                                        <?php echo render_select('work_order_id', $work_orders, ['id', 'name', 'description'], 'quality_work_order', $work_order_id); ?>
                                    </div>
                                    <div class="goods_receipt_hide <?php echo html_entity_decode($goods_receipt_hide); ?>">
                                        <?php echo render_select('goods_receipt_id', $goods_receipts, ['id', 'name', 'description'], 'quality_goods_receipt', $goods_receipt_id); ?>
                                    </div>
                                    <div class="goods_delivery_hide <?php echo html_entity_decode($goods_delivery_hide); ?>">
                                        <?php echo render_select('goods_delivery_id', $goods_deliverys, ['id', 'name', 'description'], 'quality_goods_delivery', $goods_delivery_id); ?>
                                    </div>
                                    <div class="internal_delivery_hide <?php echo html_entity_decode($internal_delivery_hide); ?>">
                                        <?php echo render_select('internal_delivery_id', $internal_deliverys, ['id', 'name', 'description'], 'quality_internal_delivery', $internal_delivery_id); ?>
                                    </div>
                                    <?php echo render_select('team_id', $teams, ['id', ['name']], 'quality_team', $team_id); ?>
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
                                        <div class="col-md-6 percentage_hide <?php echo html_entity_decode($percentage_hide); ?>">
                                            <?php echo render_input('percentage', 'quality_percentage_of_products', $percentage, 'number', ['step' => 'any', 'min' => '0', 'max' => 100]); ?>
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
                                    <?php echo render_input('company', 'quality_company', $company); ?>

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
                            <?php   echo render_textarea('instructions','quality_instructions', $instructions, array('rows'=>6,'placeholder'=>_l('quality_add_instructions'),'data-task-ae-editor'=>true, !is_mobile() ? 'onclick' : 'onfocus'=>(!isset($quality_check) || isset($quality_check) && $quality_check->instructions == '' ? 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' : 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' )),array(),'','tinymce'); ?>
                        </div>
                    </div>
                    <div class="row message_if_failure_hide <?php echo html_entity_decode($message_if_failure_hide); ?>">
                        <div class="col-md-12">
                            <?php   echo render_textarea('message_if_failure','quality_message_if_failure', $message_if_failure, array('rows'=>6,'placeholder'=>_l('quality_add_message_if_failure'))); ?>
                        </div>
                    </div>
                    
                    
                    <div class="row">
                        <div class="col-md-12">
                            <?php   echo render_textarea('note','quality_note', $note, array('rows'=>6,'placeholder'=>_l('quality_add_note'),'data-task-ae-editor'=>true, !is_mobile() ? 'onclick' : 'onfocus'=>(!isset($quality_check) || isset($quality_check) && $quality_check->note == '' ? 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' : 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' )),array(),'','tinymce'); ?>
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
                                <?php if( isset($quality_check_attachments) && count($quality_check_attachments) > 0){ ?>
                                    <?php foreach ($quality_check_attachments as $product_attachment) { ?>

                                        <div class="pdf_attachment dz-preview dz-image-preview image_old <?php echo new_html_entity_decode($product_attachment['id']) ?>">
                                            <div class="dz-image">
                                                <?php if(is_image(QUALITY_CHECK_FOLDER . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>
                                                <?php if(file_exists(QUALITY_CHECK_FOLDER . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>

                                                    <img class="dz-image" src="<?php echo site_url('modules/quality/uploads/quality_checks/' . $product_attachment['rel_id'] . '/' . $product_attachment['file_name']) . '" alt="' . $product_attachment['file_name'] ?>" >

                                                <?php } ?>
                                                <?php } ?>
                                            </div>
                                            <?php if(!is_image(QUALITY_CHECK_FOLDER . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>
                                            <div class="dz-details dz-active"><div class="dz-size"><span data-dz-size=""></span></div>    <div class="dz-filename"><span data-dz-name=""><?php echo html_entity_decode($product_attachment['file_name']); ?></span></div>  </div>
                                                <?php } ?>



                                            <div class="dz-error-mark">
                                                <a class="dz-remove" data-dz-remove>Remove file</a>
                                            </div>
                                            <div class="remove_file">
                                                <a href="#" class="text-danger" onclick="delete_quality_check_attachment(this,<?php echo new_html_entity_decode($product_attachment['id']); ?>); return false;"><i class="fa fa fa-times"></i></a>
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
                    <button type="submit" class="btn btn-info quality_check_submit_button"><?php echo _l('submit'); ?></button>
                </div>
            </div>

        </div>

        <?php echo form_close(); ?>
    </div>
</div>

<?php require 'modules/quality/assets/js/quality_checks/add_modal_js.php';  ?>
