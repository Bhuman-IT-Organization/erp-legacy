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
                                <h4><?php echo _l('quality_control_points'); ?></h4>
                            </div>
                            <?php if(has_permission('quality_control_point', '', 'create')){ ?>
                                <div class="col-md-6">
                                    <a href="#" onclick="control_point_modal(0); return false;" class="btn btn-info pull-right display-block">
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
                                <?php echo render_select('quality_control_frequency_filter', $quality_control_frequency, ['name', 'label'], '', '', ['data-none-selected-text' => _l('quality_control_frequency')]); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_select('control_point_type_filter', $quality_control_type, ['name', 'label'], '', '', ['data-none-selected-text' => _l('quality_type')]); ?>
                            </div>
                           
                           
                        </div>
                        <div class="clearfix"></div>
                        <hr>

                        <?php 
                        render_datatable(
                            array(
                                _l('id'),
                                [
                                    'name'  => _l('quality_code'),
                                    'th_attrs' => [
                                        'style' => 'min-width:100px',
                                    ],
                                ],
                                [
                                    'name'  => _l('quality_name'),
                                    'th_attrs' => [
                                        'style' => 'min-width:200px',
                                    ],
                                ],
                                
                                _l('quality_item'),
                                [
                                    'name'  => _l('quality_operation'),
                                    'th_attrs' => [
                                        'style' => 'min-width:100px',
                                    ],
                                ],
                                [
                                    'name'  => _l('quality_control_per'),
                                    'th_attrs' => [
                                        'style' => 'min-width:70px',
                                    ],
                                ],
                                [
                                    'name'  => _l('quality_percentage'),
                                    'th_attrs' => [
                                        'style' => 'min-width:40px',
                                    ],
                                ],
                                _l('quality_control_frequency'),
                                _l('quality_type'),
                                _l('quality_team'),
                                _l('quality_responsible'),
                                _l('quality_status'),
                                _l('quality_datecreated'),
                            ),'control_point_table'
                        );
                        ?>

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
require('modules/quality/assets/js/control_points/manage_js.php');
?>
</body>
</html>
