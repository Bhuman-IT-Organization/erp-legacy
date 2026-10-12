    <div class="modal fade z-index-none" id="quality_alertModal">
        <div class="modal-dialog setting-quality_alert-table modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo html_entity_decode($title) ?></h4>
                </div>
                <?php 
                $id = '';
                
                if(isset($quality_alert)){
                    $id = $quality_alert->id;
                }

                ?>
                <?php echo form_open_multipart(admin_url('quality/add_edit_quality_alert/'.$id), array('id' => 'add_edit_quality_alert', 'autocomplete'=>'off')); ?>
                <?php 

                $quality_check_id = '';
                $name = '';
                $item_id = '';
                $operation = '';
                $work_order_id = '';
                $team_id = '';
                $responsible_id = '';
                $description = '';
                $corrective_actions = '';
                $preventive_actions = '';
                $datecreated = _dt('Y-m-d H:i:s');
                $work_order_hide = ' hide';
                $goods_receipt_hide = ' hide';
                $goods_delivery_hide = ' hide';
                $internal_delivery_hide = ' hide';
                $goods_receipt_id = '';
                $goods_delivery_id = '';
                $internal_delivery_id = '';
                $work_center_id = '';
                $root_cause = '';
                $priority = '';

                if(isset($quality_alert)){
                    $quality_check_id = $quality_alert->quality_check_id;
                    $work_center_id = $quality_alert->work_center_id;
                    $root_cause = $quality_alert->root_cause;
                    $priority = $quality_alert->priority;
                    $name = $quality_alert->name;
                    $item_id = $quality_alert->item_id;
                    $operation = $quality_alert->operation;
                    $work_order_id = $quality_alert->work_order_id;
                    $team_id = $quality_alert->team_id;
                    $responsible_id = $quality_alert->responsible_id;
                    $description = $quality_alert->description;
                    $corrective_actions = $quality_alert->corrective_actions;
                    $preventive_actions = $quality_alert->preventive_actions;
                    $datecreated = _dt($quality_alert->datecreated);
                    $goods_receipt_id = $quality_alert->goods_receipt_id;
                    $goods_delivery_id = $quality_alert->goods_delivery_id;
                    $internal_delivery_id = $quality_alert->internal_delivery_id;

                    if($quality_alert->operation == 'manufacturing'){
                        $work_order_hide = '';
                    }elseif($quality_alert->operation == 'receipts'){
                        $goods_receipt_hide = '';
                    }elseif($quality_alert->operation == 'delivery_orders'){
                        $goods_delivery_hide = '';
                    }elseif($quality_alert->operation == 'internal_transfers'){
                        $internal_delivery_hide = '';
                    }
                    
                }
        

                ?>
               
                <input type="hidden" name="id" value="<?php echo html_entity_decode($id); ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?php

                            $next_quality_alert_number = get_option('quality_alert_number');
                            $format               = get_option('quality_alert_number_format');

                            if (isset($quality_alert)) {
                                $format = $quality_alert->number_format;
                            }

                            $prefix = get_option('quality_alert_prefix');

                            if ($format == 1) {
                                $__number = $next_quality_alert_number;
                                if (isset($quality_alert)) {
                                    $__number = $quality_alert->number;
                                    $prefix   = '<span id="prefix">' . $quality_alert->prefix . '</span>';
                                }
                            } elseif ($format == 2) {
                                if (isset($quality_alert)) {
                                    $__number = $quality_alert->number;
                                    $prefix   = $quality_alert->prefix;
                                    $prefix   = '<span id="prefix">' . $prefix . '</span><span id="prefix_year">' . date('Y', strtotime($quality_alert->datecreated)) . '</span>/';
                                } else {
                                    $__number = $next_quality_alert_number;
                                    $prefix   = $prefix . '<span id="prefix_year">' . date('Y') . '</span>/';
                                }
                            } elseif ($format == 3) {
                                if (isset($quality_alert)) {
                                    $yy       = date('y', strtotime($quality_alert->datecreated));
                                    $__number = $quality_alert->number;
                                    $prefix   = '<span id="prefix">' . $quality_alert->prefix . '</span>';
                                } else {
                                    $yy       = date('y');
                                    $__number = $next_quality_alert_number;
                                }
                            } elseif ($format == 4) {
                                if (isset($quality_alert)) {
                                    $yyyy     = date('Y', strtotime($quality_alert->datecreated));
                                    $mm       = date('m', strtotime($quality_alert->datecreated));
                                    $__number = $quality_alert->number;
                                    $prefix   = '<span id="prefix">' . $quality_alert->prefix . '</span>';
                                } else {
                                    $yyyy     = date('Y');
                                    $mm       = date('m');
                                    $__number = $next_quality_alert_number;
                                }
                            }

                            $_quality_alert_number     = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
                            $isedit               = isset($quality_alert) ? 'true' : 'false';
                            $data_original_number = isset($quality_alert) ? $quality_alert->number : 'false';
                            ?>
                            <div class="form-group">
                                <label for="number"><?php echo _l('quality_alert_number_label'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-addon">

                                        <?php 
                                        echo new_html_entity_decode($prefix);
                                        ?>
                                    </span>
                                    <input type="text" name="number" id="number" class="form-control" value="<?php echo e($_quality_alert_number); ?>"
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
                            <div class="form-group">
                                <label for="tags" class="control-label"><i class="fa fa-tag" aria-hidden="true"></i>
                                    <?php echo _l('tags'); ?>
                                </label>
                                <input type="text" class="tagsinput" id="tags" name="tags"
                                value="<?php echo(isset($quality_alert) ? prep_tags_input(get_tags_in($quality_alert->id, 'quality_alert')) : ''); ?>"
                                data-role="tagsinput">
                            </div>                        
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php echo render_select('quality_check_id', $quality_checks, ['id','code', 'name'], 'quality_check', $quality_check_id); ?>

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
                                    <div class="work_order_hide <?php echo html_entity_decode($work_order_hide); ?>">
                                        <?php echo render_select('work_center_id', $work_centers, ['id', 'work_center_code', 'work_center_name'], 'quality_work_center', $work_center_id); ?>
                                    </div>

                                   
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php echo render_select('team_id', $teams, ['id', ['name']], 'quality_team', $team_id); ?>
                                    <?php echo render_select('responsible_id', $staffs, ['staffid', ['firstname', 'lastname']], 'quality_responsible', $responsible_id); ?>
                                    <?php echo render_select('root_cause', $root_causes, ['id', ['name']], 'quality_root_cause', $root_cause, [], [], '', '', false); ?>
                                    <?php echo render_select('priority', $prioritys, ['id', ['name']], 'quality_priority', $priority, [], [], '', '', false); ?>

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
                            <?php   echo render_textarea('description','quality_description', $description, array('rows'=>6,'placeholder'=>_l('quality_add_description'),'data-task-ae-editor'=>true, !is_mobile() ? 'onclick' : 'onfocus'=>(!isset($quality_alert) || isset($quality_alert) && $quality_alert->description == '' ? 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' : 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' )),array(),'','tinymce'); ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <?php   echo render_textarea('corrective_actions','quality_corrective_actions', $corrective_actions, array('rows'=>6,'placeholder'=>_l('quality_add_corrective_actions'),'data-task-ae-editor'=>true, !is_mobile() ? 'onclick' : 'onfocus'=>(!isset($quality_alert) || isset($quality_alert) && $quality_alert->corrective_actions == '' ? 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' : 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' )),array(),'','tinymce'); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?php   echo render_textarea('preventive_actions','quality_preventive_actions', $preventive_actions, array('rows'=>6,'placeholder'=>_l('quality_add_preventive_actions'),'data-task-ae-editor'=>true, !is_mobile() ? 'onclick' : 'onfocus'=>(!isset($quality_alert) || isset($quality_alert) && $quality_alert->preventive_actions == '' ? 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' : 'form_init_editor(\'.tinymce\', {height:200, auto_focus: true});' )),array(),'','tinymce'); ?>
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
                                <?php if( isset($quality_alert_attachments) && count($quality_alert_attachments) > 0){ ?>
                                    <?php foreach ($quality_alert_attachments as $product_attachment) { ?>

                                        <div class="pdf_attachment dz-preview dz-image-preview image_old <?php echo new_html_entity_decode($product_attachment['id']) ?>">
                                            <div class="dz-image">
                                                <?php if(is_image(QUALITY_ALERT_FOLDER . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>
                                                <?php if(file_exists(QUALITY_ALERT_FOLDER . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>

                                                    <img class="dz-image" src="<?php echo site_url('modules/quality/uploads/quality_alerts/' . $product_attachment['rel_id'] . '/' . $product_attachment['file_name']) . '" alt="' . $product_attachment['file_name'] ?>" >

                                                <?php } ?>
                                                <?php } ?>
                                            </div>
                                            <?php if(!is_image(QUALITY_ALERT_FOLDER . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>
                                            <div class="dz-details dz-active"><div class="dz-size"><span data-dz-size=""></span></div>    <div class="dz-filename"><span data-dz-name=""><?php echo html_entity_decode($product_attachment['file_name']); ?></span></div>  </div>
                                                <?php } ?>



                                            <div class="dz-error-mark">
                                                <a class="dz-remove" data-dz-remove>Remove file</a>
                                            </div>
                                            <div class="remove_file">
                                                <a href="#" class="text-danger" onclick="delete_quality_alert_attachment(this,<?php echo new_html_entity_decode($product_attachment['id']); ?>); return false;"><i class="fa fa fa-times"></i></a>
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
                    <button type="submit" class="btn btn-info quality_alert_submit_button"><?php echo _l('submit'); ?></button>
                </div>
            </div>

        </div>

        <?php echo form_close(); ?>
    </div>
</div>

<?php require 'modules/quality/assets/js/quality_alerts/add_modal_js.php';  ?>
