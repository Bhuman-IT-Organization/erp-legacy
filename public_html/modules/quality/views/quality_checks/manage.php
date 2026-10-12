<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php  init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4><?php echo _l('quality_checks'); ?></h4>
                            </div>
                            <?php if(has_permission('quality_check', '', 'create')){ ?>
                                <div class="col-md-6">
                                    <a href="#" onclick="quality_check_modal(0); return false;" class="btn btn-info pull-right display-block">
                                        <?php echo _l('quality_new'); ?>
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <?php echo render_date_input('from_date_filter', '', '', ['placeholder' => _l('quality_from_date')]); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_date_input('to_date_filter', '', '', ['placeholder' => _l('quality_to_date')]); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_select('item_filter', $items, ['itemid', 'description'], '', '', ['data-none-selected-text' => _l('quality_item')]); ?>
                            </div>
                            
                            <div class="col-md-3">
                                <?php echo render_select('status_filter', $quality_check_status, ['id', 'name'], '', '', ['data-none-selected-text' => _l('quality_status')]); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_select('quality_check_type_filter', $quality_control_type, ['name', 'label'], '', '', ['data-none-selected-text' => _l('quality_type')]); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_select('team_filter', $teams, ['id', 'name'], '', $team_id, ['data-none-selected-text' => _l('quality_team')]); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_select('control_point_filter', $control_points, ['id', 'name'], '', $control_point_id, ['data-none-selected-text' => _l('quality_control_point')]); ?>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <hr>
                        <?php $this->load->view('quality_checks/table_html'); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="modal_wrapper"></div>
<div id="modal_wrapper1"></div>

<?php init_tail(); ?>
<?php 
require('modules/quality/assets/js/quality_checks/manage_js.php');
?>
</body>
</html>
