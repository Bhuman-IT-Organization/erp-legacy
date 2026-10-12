<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
	<div class="col-md-9">
		<h4 class="h4-color no-margin"><?php echo _l('quality_permissions'); ?></h4>
	</div>
  <div class="col-md-3">
    <?php if(has_permission('quality_setting', '', 'create')){ ?>
      <a href="#" onclick="quality_permissions_update(0,0,' hide'); return false;" class="btn btn-info pull-right"><?php echo _l('quality_new'); ?></a>
    <?php } ?>
  </div>
</div>
<br>

<table class="table table-hr-profile-permission">
  <thead>
    <th><?php echo _l('clients_list_full_name'); ?></th>
    <th><?php echo _l('role'); ?></th>
    <th><?php echo _l('staff_dt_email'); ?></th>
    <th><?php echo _l('quality_branch_phone'); ?></th>
    <th><?php echo _l('options'); ?></th>
  </thead>
  <tbody>
  </tbody>
</table>
<div id="modal_wrapper"></div>

