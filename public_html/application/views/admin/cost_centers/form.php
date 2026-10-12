<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading" />

                        <?php echo form_open($this->uri->uri_string(), array('id' => 'cost-center-form')); ?>

                        <?php $name = isset($cost_center) ? $cost_center->name : ''; ?>
                        <?php echo render_input('name', 'cost_center_name', $name, 'text', array('required' => true)); ?>

                        <div class="text-right">
                            <a href="<?php echo admin_url('cost_centers'); ?>" class="btn btn-default mright5"><?php echo _l('cancel'); ?></a>
                            <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function(){
        appValidateForm($('#cost-center-form'), {name: 'required'});
    });
</script>
</body>
</html>
