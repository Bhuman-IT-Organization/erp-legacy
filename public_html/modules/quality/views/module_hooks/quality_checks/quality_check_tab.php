<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div role="tabpanel" class="tab-pane" id="quality_check">
	<div class="row">
		<?php if(has_permission('quality_check', '', 'create')){ ?>
			<div class="col-md-12 hide">
				<a href="#" onclick="quality_check_modal(0); return false;" class="btn btn-info pull-right display-block">
					<?php echo _l('quality_new'); ?>
				</a>
			</div>
		<?php } ?>
	</div>
	<div class="row ">
		<div class="col-md-12 ">
			<?php $this->load->view('quality/quality_checks/table_html'); ?>
		</div>
	</div>
</div>

<input type="hidden" name="rel_type" value="<?php echo html_entity_decode($rel_type); ?>">   
<input type="hidden" name="rel_id" value="<?php echo html_entity_decode($rel_id); ?>">   
<div id="modal_wrapper"></div>

