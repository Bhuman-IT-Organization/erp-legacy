<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">

            <div class="col-md-3">
                <ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked">
                    <?php
                    $i = 0;
                    foreach($tab as $gr){
                        ?>
                        <li<?php if($i == 0){echo " class='active'"; } ?>>
                            <a href="<?php echo admin_url('quality/setting?group='.$gr); ?>" data-group="<?php echo new_html_entity_decode($gr); ?>">
                                <?php

                                $icon['prefixs'] = '';
                                $icon['teams'] = '';
                                $icon['permissions'] = '';
                                $icon['reset_data'] = '';
                                
                                echo new_html_entity_decode($icon[$gr] .' '. _l('quality_'.$gr)); 

                                ?>
                            </a>
                        </li>
                        <?php $i++; } ?>
                    </ul>
                </div>
                <div class="col-md-9">
                    <div class="panel_s">
                        <div class="panel-body">

                            <?php $this->load->view($tabs['view']); ?>

                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
            <?php echo form_close(); ?>
            <div class="btn-bottom-pusher"></div>
        </div>
    </div>
    <div id="new_version"></div>
    <div id="modal_wrapper"></div>
    <?php init_tail(); ?>

    <?php 
    $viewuri = $_SERVER['REQUEST_URI'];
    ?>

    <?php if(!(strpos($viewuri,'admin/quality/setting?group=team') === false)){ 
        require 'modules/quality/assets/js/settings/quality_teams/manage_js.php';
    }elseif(!(strpos($viewuri,'admin/quality/setting?group=permissions') === false)){
        require 'modules/quality/assets/js/settings/permissions/permissions_js.php';
    }elseif(!(strpos($viewuri,'admin/quality/setting?group=reset_data') === false)){
        require 'modules/quality/assets/js/settings/reset_data/reset_data_js.php';
    }
    ?>
</body>
</html>
