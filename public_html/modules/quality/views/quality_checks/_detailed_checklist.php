<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
	<div class="col-md-6">
		<h4 class="tw-font-semibold"><?php echo _l('quality_detailed_checklist') ?></h4>
	</div>
	<div class="col-md-6">
		<?php 
		$check_list_detail_status = true;
		if(isset($quality_check->details) && count($quality_check->details) > 0){
			foreach ($quality_check->details as $key => $value) {
				if($value['status'] == 'to_do' || $value['status'] == 'validate'){
					$check_list_detail_status = false;
				}
			}
		}
		?>
		<?php if($quality_check->control_per == 'quantity' && $quality_check->operation != 'manufacturing' && $quality_check->send_product_failure_location == 0 && $check_list_detail_status){ ?>
			<a href="#" onclick="send_products_to_location_modal(<?php echo html_entity_decode($quality_check->id); ?>); return false;" class="btn btn-danger pull-right display-block">
				<?php echo _l('quality_send_products_to_failure_location '); ?>
			</a>
		<?php } ?>
		
		<?php if($quality_check->send_product_failure_location > 0){ ?>
			<?php if(quality_get_status_modules('warehouse')){ 

				if($quality_check->operation == 'receipts' || $quality_check->operation == 'internal_transfers'){
					$operation_detail = quality_get_operation_detail('internal_transfers', $quality_check->send_product_failure_location);
				}elseif($quality_check->operation == 'delivery_orders'){
					$operation_detail = quality_get_operation_detail('receipts', $quality_check->send_product_failure_location);
				}

				$internal_delivery_name = '';
				$internal_delivery = get_internal_delivery_code($quality_check->send_product_failure_location);
				if($internal_delivery){
					$internal_delivery_name = $internal_delivery->internal_delivery_code;

				}
				?>
				<span class="tw-font-semibold pull-right"><?php echo _l('quality_transaction_send_products_to_failure_location'); ?>: <?php echo html_entity_decode($operation_detail); ?></span>
			<?php } ?>
		<?php } ?>
	</div>
</div>

<?php 
render_datatable(
	array(
		_l('id'),
		[
			'name'  => _l('quality_check_number'),
			'th_attrs' => [
				'style' => 'min-width:150px',
			],
		],
		[
			'name'  => _l('quality_work_order'),
			'th_attrs' => [
				'style' => 'min-width:200px',
			],
		],

		_l('quality_item'),
		[
			'name'  => _l('quality_actual_size'),
			'th_attrs' => [
				'style' => 'min-width:100px',
			],
		],
		[
			'name'  => _l('quality_description'),
			'th_attrs' => [
				'style' => 'min-width:150px',
			],
		],
		[
			'name'  => _l('quality_check_results'),
			'th_attrs' => [
				'style' => 'min-width:50px',
			],
		],
		_l('quality_status'),
		_l('quality_completion_date'),
		_l('quality_datecreated'),
		_l('quality_created_by'),
		[
			'name'  => _l('quality_actions'),
			'th_attrs' => [
				'style' => 'min-width:80px',
			],
		],
	),'detailed_checklist_table'
);
?>
