<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <a href="<?php echo admin_url('cost_centers/create'); ?>" class="btn btn-info pull-left mright10">
                                <i class="fa fa-plus"></i> <?php echo _l('add_cost_center'); ?>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />

                        <table class="table dt-table">
                            <thead>
                                <tr>
                                    <th><?php echo _l('id'); ?></th>
                                    <th><?php echo _l('cost_center_name'); ?></th>
                                    <th><?php echo _l('date_created'); ?></th>
                                    <th><?php echo _l('options'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($cost_centers as $cc){ ?>
                                <tr>
                                    <td><?php echo $cc['id']; ?></td>
                                    <td><?php echo htmlspecialchars($cc['name']); ?></td>
                                    <td><?php echo _d($cc['datecreated']); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('cost_centers/edit/'.$cc['id']); ?>" class="btn btn-default btn-icon"><i class="fa fa-pencil-square-o"></i></a>
                                        <a href="<?php echo admin_url('cost_centers/delete/'.$cc['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
