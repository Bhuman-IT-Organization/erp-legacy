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
                                <span class="tw-font-semibold tw-text-xl"><?php echo html_entity_decode( format_quality_alert_number($quality_alert->id).' - '.$quality_alert->name); ?></span>
                            </div>
                            <div class="col-md-4 mbot5">
                                <?php if((has_permission('quality_alert', '', 'edit') || has_permission('quality_alert', '', 'create') || is_admin()) && $quality_alert->status == 'new'){ ?>
                                    <a href="javascript:void(0)" onclick="quality_alert_modal(<?php echo html_entity_decode($quality_alert->id); ?>)" class="btn btn-primary pull-right display-block mright5">
                                        <?php echo _l('edit'); ?>
                                    </a>
                                <?php } ?>
                                <a href="<?php echo admin_url('quality/quality_alerts'); ?>" class="btn btn-default pull-right display-block mright5">
                                    <?php echo _l('quality_back'); ?>
                                </a>
                            </div>
                        </div>

                        <div class="clearfix"></div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php $this->load->view('quality_alerts/_template'); ?>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_wrapper"></div>
<input type="hidden" name="quality_alert_detail" value="1">
<input type="hidden" name="quality_alert_id" value="<?php echo html_entity_decode($quality_alert->id); ?>">

<?php init_tail(); ?>
<?php 
require('modules/quality/assets/js/quality_alerts/manage_js.php');
require('modules/quality/assets/js/quality_alerts/quality_alert_detail_js.php');
?>
</body>
</html>
