<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php  init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <span class="tw-font-semibold tw-text-xl"><?php echo html_entity_decode( format_quality_check_number($quality_check->id).' - '.$quality_check->name); ?></span>
                            </div>
                            <div class="col-md-4 mbot5">
                                <?php if((has_permission('quality_check', '', 'edit') || has_permission('quality_check', '', 'create') || is_admin()) && $quality_check->status == 'draft'){ ?>
                                    <a href="javascript:void(0)" onclick="quality_check_modal(<?php echo html_entity_decode($quality_check->id); ?>)" class="btn btn-primary pull-right display-block mright5">
                                        <?php echo _l('edit'); ?>
                                    </a>
                                <?php } ?>
                                <?php if((has_permission('quality_alert', '', 'edit') || has_permission('quality_alert', '', 'create') || is_admin()) && $quality_check->status != 'draft'){ ?>
                                    <a href="#" onclick="quality_alert_modal(0, <?php echo html_entity_decode($quality_check->id); ?>); return false;" class="btn btn-warning pull-right display-block mright5">
                                        <?php echo _l('quality_create_quality_alert'); ?>
                                    </a>
                                <?php } ?>
                                <a href="<?php echo admin_url('quality/quality_checks'); ?>" class="btn btn-default pull-right display-block mright5">
                                    <?php echo _l('quality_back'); ?>
                                </a>
                            </div>
                        </div>

                        <div class="clearfix"></div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php $this->load->view('quality_checks/_template'); ?>
                            </div>
                        </div>
                        <hr>

                        <div class="horizontal-scrollable-tabs preview-tabs-top">
                            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                            <div class="horizontal-tabs">
                                <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                                    <li role="presentation" class="<?php if(!$this->input->get('tab') || (!(strpos($this->input->get('tab'),'detail') === false))){echo 'active';} ?>">
                                        <a href="#detail" aria-controls="detail" role="tab" data-toggle="tab">
                                            <span class="fa-solid fa-bolt"></span>&nbsp;<?php echo _l('quality_detailed_checklist'); ?>
                                        </a>
                                    </li>
                                    <li role="presentation"  class="<?php if(!$this->input->get('tab') || $this->input->get('tab') == 'task'){echo 'active';} ?>">
                                        <a href="#quality_alert" aria-controls="quality_alert" role="tab" data-toggle="tab">
                                            <span class="fa fa-history"></span>&nbsp;<?php echo _l('quality_alerts'); ?>
                                        </a>
                                    </li>

                                </ul>
                            </div>
                        </div>

                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane <?php if(!$this->input->get('tab') || (!(strpos($this->input->get('tab'),'detail') === false))){echo 'active';} ?>"  id="detail">
                                <?php if($quality_check->status != 'draft'){ ?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <?php $this->load->view('quality_checks/_detailed_checklist'); ?>
                                        </div>
                                    </div>
                                    <hr>
                                <?php }  ?>
                            </div>
                            <div role="tabpanel" class="tab-pane <?php if(!$this->input->get('tab') || $this->input->get('tab') == 'quality_alert'){echo 'active';} ?>"  id="quality_alert">
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php $this->load->view('quality_alerts/table_html'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_wrapper"></div>
<input type="hidden" name="quality_check_detail" value="1">
<input type="hidden" name="quality_check_id_filter" value="<?php echo html_entity_decode($quality_check->id); ?>">

<?php init_tail(); ?>
<?php 
require('modules/quality/assets/js/quality_checks/manage_js.php');
require('modules/quality/assets/js/quality_alerts/manage_js.php');
require('modules/quality/assets/js/quality_checks/quality_check_detail_js.php');
?>
</body>
</html>
