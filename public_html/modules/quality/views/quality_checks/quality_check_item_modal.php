    <div class="modal fade z-index-none" id="quality_check_detailModal">
        <div class="modal-dialog setting-quality_check-table modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo html_entity_decode($title) ?>
                    <?php echo render_quality_check_detail_status_html($quality_check_detail->id, '', $quality_check_detail->status, false); ?>
                </h4>
                </div>
                <?php 
                $id = $quality_check_detail->id;
                ?>
                <?php echo form_open_multipart(admin_url('quality/add_edit_quality_check_detail'), array('id' => 'add_edit_quality_check_detail', 'autocomplete'=>'off')); ?>
                <?php 
                $quality_check_results = '';
                $actual_size = '';
                if(isset($quality_check_detail)){
                    $quality_check_results = $quality_check_detail->quality_check_results;
                    $actual_size = $quality_check_detail->actual_size;
                }
                ?>
               
                <input type="hidden" name="id" value="<?php echo html_entity_decode($id); ?>">
                <div class="modal-body">
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h4><?php echo check_for_links($quality_check_detail->description); ?></h4>
                            <h4><span><?php echo _l('quality_type'); ?>: </span><?php echo _l('quality_'.$quality_check->type); ?></h4>
                            <?php if($quality_check->type == 'measure'){ ?>
                                <input type="hidden" disabled name="check_norm" value="<?php echo html_entity_decode($quality_check->norm); ?>">
                                <input type="hidden" disabled name="check_tolerance_from" value="<?php echo html_entity_decode($quality_check->tolerance_from); ?>">
                                <input type="hidden" disabled name="check_tolerance_to" value="<?php echo html_entity_decode($quality_check->tolerance_to); ?>">
                                <input type="hidden" disabled name="unit_of_measurement" value="<?php echo html_entity_decode($quality_check->unit_of_measurement); ?>">
                                
                                <div class="hide measure_warning alert alert-warning project-no-started-timers-found mbot15">
                                </div>
                                <table class="table border table-striped no-mtop">
                                    <tbody>
                                        <tr class="project-overview">
                                            <td class="bold"><?php echo _l('quality_norm'); ?></td>
                                            <td><?php echo html_entity_decode($quality_check->norm.' '.$quality_check->unit_of_measurement) ; ?></td>
                                        </tr>
                                        <tr class="project-overview">
                                            <td class="bold"><?php echo _l('quality_tolerance'); ?></td>
                                            <td><?php echo html_entity_decode($quality_check->tolerance_from.' -> '.$quality_check->tolerance_to.' '.$quality_check->unit_of_measurement) ; ?></td>
                                        </tr>
                                        
                                    </tbody>
                                </table>

                                <?php echo render_input('actual_size', 'quality_actual_size', $actual_size, 'number', ['step' => 'any', 'min' => '0.00']); ?>
                            <?php } ?>
                        </div>
                    </div>
                    
                   
                    <div class="row">
                        <div class="col-md-12">
                            <?php   echo render_textarea('quality_check_results','quality_check_results', $quality_check_results, array('rows'=>6,'placeholder'=>_l('quality_check_results'))); ?>
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
                                <?php if( isset($quality_check_detail_attachments) && count($quality_check_detail_attachments) > 0){ ?>

                                    <?php
                                    $data = '<div class="row" id="attachment_file">';
                                    foreach($quality_check_detail_attachments as $attachment) {
                                        $data .= '<div class="col-md-6 pdf_attachment">';
                                        $href_url = site_url('modules/quality/uploads/quality_check_details/'.$attachment['rel_id'].'/'.$attachment['file_name']).'" download';
                                        if(!empty($attachment['external'])){
                                            $href_url = $attachment['external_link'];
                                        }
                                        $data .= '<div class="col-md-9">';

                                        $data .= '<div>';
                                        $data .= '<div class="pull-left"><i class="'.get_mime_class($attachment['filetype']).'"></i></div>';
                                        $data .= '<a href="'.$href_url.'>'.$attachment['file_name'].'</a>';
                                        $data .= '</div>';
                                        $data .= '</div>';
                                        $data .= '<div class="col-md-3 text-right">';
                                        $data .= '<a class="btn btn-sm" name="preview-btn" onclick="preview_file(this); return false;" rel_id = "'.$attachment['rel_id'].'" id = "'.$attachment['id'].'" data-toggle="tooltip" title data-original-title="'._l("preview_file").'"><i class="fa fa-eye"></i></a>';
                                        if(is_admin() || has_permission('quality_check', '', 'delete') ){
                                            $data .= '<a href="#" class="text-danger btn btn-sm hide" onclick="delete_quality_check_attachment(this,'.$attachment['id'].'); return false;"><i class="fa fa fa-times"></i></a>';
                                        }
                                        $data .= '<a href="'.$href_url.'" class="btn btn-sm" ><i class="fa-solid fa-download"></i></a>';
                                        $data .= '</div>';
                                        $data .= '<div class="clearfix"></div><hr class="mtop1 mbot5">';
                                        $data .= '</div>';
                                    }
                                    $data .= '</div>';
                                    echo new_html_entity_decode($data);
                                    ?>

                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                    <?php if($quality_check->type == 'take_a_picture'){ ?>
                        <?php if($quality_check_detail->status == 'to_do'){ ?>
                            <button type="button" class="btn btn-success quality_check_detail_submit_button quality_check_validate"><?php echo _l('quality_validate'); ?></button>
                        <?php }elseif($quality_check_detail->status == 'validate'){ ?>
                            <button type="button" class="btn btn-success quality_check_detail_submit_button quality_check_pass"><?php echo _l('quality_pass'); ?></button>
                            <button type="button" class="btn btn-danger quality_check_detail_submit_button quality_check_fail"><?php echo _l('quality_fail'); ?></button>
                        <?php } ?>
                    <?php }else{ ?>
                        <?php if($quality_check_detail->status == 'to_do'){ ?>
                            <button type="button" class="btn btn-success quality_check_detail_submit_button quality_check_pass"><?php echo _l('quality_pass'); ?></button>
                            <button type="button" class="btn btn-danger quality_check_detail_submit_button quality_check_fail"><?php echo _l('quality_fail'); ?></button>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

        </div>

        <?php echo form_close(); ?>
    </div>
</div>
<div id="quality_check_detail_pdf_file_data"></div>

<?php require 'modules/quality/assets/js/quality_checks/add_quality_check_item_modal_js.php';  ?>
