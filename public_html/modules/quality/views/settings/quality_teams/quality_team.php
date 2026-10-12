<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div> 
	
	<div class="row">
		<div class="col-md-6">
			<h4><?php echo _l('quality_teams'); ?></h4>
		</div>
		<?php if(has_permission('quality_setting', '', 'create')){ ?>
			<div class="col-md-6">
				<a href="#" onclick="new_quality_team(); return false;" class="btn btn-info pull-right display-block">
					<?php echo _l('quality_new'); ?>
				</a>
			</div>
		<?php } ?>
	</div>
	<div class="clearfix"></div>
	<hr>
	
	<?php 
	render_datatable(
		array(
			_l('id'),
			_l('quality_quality_label'),
			_l('quality_team_member'),
			_l('quality_status'),
			_l('quality_options'),
		),'quality_team_table'
	);
	?>

	<div class="modal fade" id="quality_team" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<?php echo form_open_multipart(admin_url('quality/quality_team'), array('id'=>'add_edit_quality_team')); ?>

			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">
						<span class="edit-title"><?php echo _l('quality_edit_team'); ?></span>
						<span class="add-title"><?php echo _l('quality_add_team'); ?></span>
					</h4>
				</div>

				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<div id="quality_team_additional"></div>
							<?php echo render_input('name', 'quality_name'); ?>
							<?php echo render_select('team_member', $staffs, ['staffid', ['firstname', 'lastname']], 'quality_team_member', '', ['data-actions-box' => true, 'data-live-search' => true]); ?>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
					<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
				</div>
			</div><!-- /.modal-content -->
			<?php echo form_close(); ?>
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->


</body>
</html>
