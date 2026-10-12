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
                                <span class="tw-font-semibold tw-text-xl"><?php echo html_entity_decode( format_control_point_number($control_point->id).' - '.$control_point->name); ?></span>
                            </div>
                            <div class="col-md-4 mbot5">
                                <a href="#" onclick="control_point_modal(<?php echo html_entity_decode($control_point->id); ?>)" class="btn btn-primary pull-right display-block mright5">
                                    <?php echo _l('edit'); ?>
                                </a>
                                <a href="<?php echo admin_url('quality/control_points'); ?>" class="btn btn-default pull-right display-block mright5">
                                    <?php echo _l('quality_back'); ?>
                                </a>

                                <a href="<?php echo admin_url('quality/quality_checks?control_point_id='.$control_point->id); ?>" class="btn btn-success pull-right display-block mright5" target="_blank">
                                    <i class="fa-solid fa-check"></i> <?php echo _l('quality_check'); ?>
                                    : <?php echo html_entity_decode($total_quality_check); ?>
                                </a>
                                
                            </div>
                        </div>

                        <div class="clearfix"></div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php $this->load->view('control_points/_template'); ?>
                            </div>
                        </div>
                        <hr>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_wrapper"></div>
<input type="hidden" name="control_point_detail" value="1">

<?php init_tail(); ?>
<?php 
require('modules/quality/assets/js/control_points/manage_js.php');
?>
</body>
</html>
