<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading" />

                        <?php echo form_open($this->uri->uri_string(), array('id' => 'term-form')); ?>

                        <?php $name = isset($term) ? $term->name : ''; ?>
                        <?php echo render_input('name', 'name', $name, 'text', array('required' => true)); ?>

                        <div class="form-group">
                            <label for="content" class="control-label"><?php echo _l('content'); ?></label>
                            <?php $content = isset($term) ? $term->content : ''; ?>
                            <textarea name="content" id="content" class="form-control" rows="10"><?php echo pur_html_entity_decode($content); ?></textarea>
                        </div>

                        <div class="text-right">
                            <a href="<?php echo admin_url('purchase/terms'); ?>" class="btn btn-default mright5"><?php echo _l('cancel'); ?></a>
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
        init_editor('#content');
        appValidateForm($('#term-form'), {name: 'required'});
    });
</script>
</body>
</html>
