<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <a href="<?php echo admin_url('purchase/term'); ?>" class="btn btn-info pull-left mright10">
                                <i class="fa fa-plus"></i> <?php echo _l('add_new_term'); ?>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />

                        <table class="table dt-table">
                            <thead>
                                <th><?php echo _l('id'); ?></th>
                                <th><?php echo _l('name'); ?></th>
                                <th><?php echo _l('description'); ?></th>
                                <th><?php echo _l('date_created'); ?></th>
                                <th><?php echo _l('options'); ?></th>
                            </thead>
                            <tbody>
                            <?php foreach($terms as $term){ ?>
                                <tr>
                                    <td><?php echo $term['id']; ?></td>
                                    <td><?php echo pur_html_entity_decode($term['name']); ?></td>
                                    <td><?php echo mb_substr(strip_tags($term['content']), 0, 100); ?></td>
                                    <td><?php echo _d($term['datecreated']); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('purchase/term/'.$term['id']); ?>" class="btn btn-default btn-icon"><i class="fa fa-pencil-square"></i></a>
                                        <a href="<?php echo admin_url('purchase/delete_term/'.$term['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
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
