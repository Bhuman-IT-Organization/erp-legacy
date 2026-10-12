<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12" id="small-table">
				<div class="panel_s">
					<?php echo form_open_multipart(admin_url('warehouse/manage_goods_receipt'), array('id'=>'add_goods_receipt')); ?>
					<div class="panel-body">
						<div class="row">
							<div class="col-md-12">
								<h4 class="no-margin font-bold"><i class="fa fa-clone menu-icon menu-icon" aria-hidden="true"></i> <?php echo _l($title); ?></h4>
								<hr>
							</div>
						</div>

						<?php 
						$id = '';
						$currency = 0;
						$currency_exchange_rate = 0;
						if(isset($goods_receipt)){
							$id = $goods_receipt->id;
							$currency = $goods_receipt->currency;
							$currency_exchange_rate = $goods_receipt->currency_exchange_rate;
							echo form_hidden('isedit');
						}
						?>

						<input type="hidden" name="id" value="<?php echo new_html_entity_decode($id); ?>">
						<input type="hidden" name="save_and_send_request" value="false">
						<input type="hidden" name="currency" value="<?php echo html_entity_decode($currency); ?>">
						<input type="hidden" name="currency_exchange_rate" value="<?php echo html_entity_decode($currency_exchange_rate); ?>">

						<!-- start-->
						<div class="row">
							<div class="col-md-6">
								<?php $goods_receipt_code =isset($goods_receipt) ? $goods_receipt->goods_receipt_code : (isset($goods_code) ? $goods_code : '');?>
								<?php echo render_input('goods_receipt_code', 'stock_received_docket_number',$goods_receipt_code,'',array('disabled' => 'true')) ?>
							</div>
							<div class="col-md-3">
								<?php $date_c =  isset($goods_receipt) ? $goods_receipt->date_c : $current_day?>
								<?php echo render_date_input('date_c','accounting_date', _d($date_c)) ?>
							</div>
							<div class="col-md-3">
								<?php $date_add =  isset($goods_receipt) ? $goods_receipt->date_add : $current_day?>
								<?php echo render_date_input('date_add','day_vouchers', _d($date_add)) ?>
							</div>

							<div class="col-md-6 <?php if($pr_orders_status == false){ echo 'hide';} ;?>" >
								<div class="form-group">
									<label for="pr_order_id"><?php echo _l('reference_purchase_order'); ?></label>
									<select name="pr_order_id" id="pr_order_id" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php foreach($pr_orders as $pr_order) { ?>
											<option value="<?php echo new_html_entity_decode($pr_order['id']); ?>" <?php if(isset($goods_receipt) && ($goods_receipt->pr_order_id == $pr_order['id'])){ echo 'selected' ;} ?>><?php echo new_html_entity_decode($pr_order['pur_order_number'].' - '.$pr_order['pur_order_name']); ?></option>
										<?php } ?>
									</select>
								</div>
							</div>

							<div class="col-md-6 <?php if($pr_orders_status == false){ echo 'hide';} ;?>" >
								<div class="f_supplier_code">
									<div class="form-group select-placeholder">
										<label for="supplier_code" class="control-label"><?php echo _l('supplier_name'); ?></label>
										<select id="supplier_code" name="supplier_code" data-live-search="true" data-width="100%" class="selectpicker" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
										<option value=""></option>
										<?php if(isset($vendors) && is_array($vendors)){ 
											$selected = (isset($goods_receipt) ? $goods_receipt->supplier_code : '');
											foreach($vendors as $v){ ?>
												<option value="<?php echo $v['userid']; ?>" <?php if($selected == $v['userid']){ echo 'selected'; } ?>><?php echo $v['company']; ?></option>
											<?php } 
										} ?>
									</select>
								</div>
							</div>
						</div>

							<div class="col-md-6 <?php if($pr_orders_status == true){ echo 'hide';} ;?>" >

								<?php $supplier_name =  isset($goods_receipt) ? $goods_receipt->supplier_name : ''?>
								<?php 
								echo render_input('supplier_name','supplier_name', $supplier_name) ?>
							</div>

							<!-- <div class=" col-md-3">
								<div class="form-group">
									<label for="buyer_id" class="control-label"><?php echo _l('Buyer'); ?></label>
									<select name="buyer_id" class="selectpicker" id="buyer_id" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>"> 
										<option value=""></option> 
										<?php foreach($staff as $s){ ?>
											<option value="<?php echo new_html_entity_decode($s['staffid']); ?>" <?php if(isset($goods_receipt) && ($goods_receipt->buyer_id == $s['staffid'])){ echo 'selected' ;} ?>> <?php echo new_html_entity_decode($s['firstname'].''.$s['lastname']); ?></option>                  
										<?php }?>
									</select>
								</div>
							</div> -->

							<?php if(ACTIVE_PROPOSAL == true){ ?>
								<!-- <div class="col-md-3 form-group <?php if($pr_orders_status == false){ echo 'hide';} ;?>" >
									<label for="project"><?php echo _l('project'); ?></label>
									<select name="project" id="project" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>

										<?php if(isset($projects)){ ?>
											<?php foreach($projects as $s) { ?>
												<option value="<?php echo new_html_entity_decode($s['id']); ?>" <?php if(isset($goods_receipt) && $s['id'] == $goods_receipt->project){ echo 'selected'; } ?>><?php echo new_html_entity_decode($s['name']); ?></option>
											<?php } ?>
										<?php } ?>
									</select>
								</div> -->

								<div class="col-md-3 form-group <?php if($pr_orders_status == false){ echo 'hide';} ;?>" >
									<label for="type"><?php echo _l('type_label'); ?></label>
									<?php 
									$CI_wh =& get_instance();
									$CI_wh->load->model('purchase/purchase_model');
									$request_types_wh = $CI_wh->purchase_model->get_request_types();
									?>
									<select name="type" id="type" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php foreach($request_types_wh as $rt_w){ ?>
										<option value="<?php echo new_html_entity_decode($rt_w['type_code']); ?>" <?php if(isset($goods_receipt) && isset($goods_receipt->type) && $goods_receipt->type == $rt_w['type_code']){ echo 'selected';} ?>><?php echo new_html_entity_decode($rt_w['type_code'].' - '.$rt_w['name']); ?></option>
										<?php } ?>
									</select>
								</div>

								<div class="col-md-3 form-group <?php if($pr_orders_status == false){ echo 'hide';} ;?>" >
									<label for="department"><?php echo _l('department'); ?></label>
									<select name="department" id="department" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php if(isset($departments)){ ?>
											<?php foreach($departments as $s) { ?>
												<option value="<?php echo new_html_entity_decode($s['departmentid']); ?>" <?php if(isset($goods_receipt) && $s['departmentid'] == $goods_receipt->department){ echo 'selected'; } ?>><?php echo new_html_entity_decode($s['name']); ?></option>
											<?php } ?>

										<?php } ?>

									</select>
								</div>

								<div class="col-md-3 form-group <?php if($pr_orders_status == false){ echo 'hide';} ;?>" >
									<label for="requester"><?php echo _l('requester'); ?></label>
									<select name="requester" id="requester" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php if(isset($staffs)){ ?>
											<?php foreach($staffs as $s) { ?>
												<option value="<?php echo new_html_entity_decode($s['staffid']); ?>" <?php if(isset($goods_receipt) && $s['staffid'] == $goods_receipt->requester){ echo 'selected'; } ?>><?php echo new_html_entity_decode($s['lastname'] . ' '. $s['firstname']); ?></option>
											<?php } ?>
										<?php }?>
									</select>
								</div>

							<?php } ?>

							<div class=" col-md-3">
								<?php $deliver_name = (isset($goods_receipt) ? $goods_receipt->deliver_name : '');
								echo render_input('deliver_name','deliver_name',$deliver_name) ?>
							</div>

							<div class="col-md-3 ">
								<?php $warehouse_id_value = (isset($goods_receipt) ? $goods_receipt->warehouse_id : '');?>
								<a href="#" class="pull-right display-block input_method"><i class="fa fa-question-circle skucode-tooltip"  data-toggle="tooltip" title="" data-original-title="<?php echo _l('goods_receipt_warehouse_tooltip'); ?>"></i></a>
								<?php echo render_select('warehouse_id_m',$warehouses,array('warehouse_id','warehouse_name'),'warehouse_name_location', $warehouse_id_value); ?>
							</div>

							<?php if(ACTIVE_PROPOSAL == true){ ?>
								<div class="col-md-3 <?php if($pr_orders_status == false){ echo 'hide';} ;?>">
									<?php $expiry_date =  isset($goods_receipt) ? $goods_receipt->expiry_date : $current_day?>
									<?php echo render_date_input('expiry_date_m','expiry_date', _d($expiry_date)) ?>
								</div>
							<?php } ?>
							<div class="col-md-3 form-group" >
								<?php $invoice_no = (isset($goods_receipt) ? $goods_receipt->invoice_no : '');
								echo render_input('invoice_no','invoice_no',$invoice_no, 'text', ['required' => true]) ?>
							</div>
							<div class="col-md-3 form-group" >
								<?php $boe = (isset($goods_receipt) ? $goods_receipt->boe : '');
								echo render_input('boe','BOE',$boe) ?>
							</div>
						</div>
					</div>
					<div class="panel-body mtop10 invoice-item">
						<div class="row">
							<div class="col-md-4">
								<?php $this->load->view('warehouse/item_include/main_item_select'); ?>
							</div>
							<div class="col-md-8 text-right">
								<label class="bold mtop10 text-right" data-toggle="tooltip" title="" data-original-title="<?php echo _l('support_barcode_scanner_tooltip'); ?>"><?php echo _l('support_barcode_scanner'); ?>
								<i class="fa fa-question-circle i_tooltip"></i></label>
							</div>
						</div>

						<div class="table-responsive s_table ">
							<table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
								<thead>
									<tr>
										<th></th>
										<th class="asset_id_col hide" width="8%" align="left"><?php echo _l('asset_id'); ?></th>
										<th width="15%" align="left"><i class="fa fa-exclamation-circle" aria-hidden="true" data-toggle="tooltip" data-title="<?php echo _l('item_description_new_lines_notice'); ?>"></i> <?php echo _l('invoice_table_item_heading'); ?></th>
										<th width="12%" align="left"><?php echo _l('description'); ?></th>
										<th width="10%" align="left"><?php echo _l('warehouse_name'); ?></th>
										<th width="8%" align="right" class="qty"><?php echo _l('quantity'); ?></th>
										<th width="8%" align="right" class="hide"><?php echo _l('unit_price'); ?></th>
										<th width="7%" align="right"><?php echo _l('received_qty'); ?></th>
										<th width="7%" align="right"><?php echo _l('pending_qty'); ?></th>
										<th width="10%" align="right" class="hide"><?php echo _l('invoice_table_tax_heading'); ?></th>
										<th width="7%" align="right"><?php echo _l('lot_number'); ?></th>
										<th width="7%" align="right"><?php echo _l('batch_number'); ?></th>
										<th width="7%" align="right"><?php echo _l('date_manufacture'); ?></th>
										<th width="7%" align="right"><?php echo _l('expiry_date'); ?></th>
										<th width="8%" align="right" class="hide"><?php echo _l('invoice_table_amount_heading'); ?></th>

										<th align="center"><i class="fa fa-cog"></i></th>
										<th align="center"></th>
									</tr>
								</thead>
								<tbody>
									<?php echo new_html_entity_decode($goods_receipt_row_template); ?>
								</tbody>
							</table>
						</div>
						<div class="col-md-8 col-md-offset-4 hide">
							<table class="table text-right">
								<tbody>
									<tr id="subtotal">
										<td><span class="bold"><?php echo _l('total_goods_money'); ?> :</span>
										</td>
										<td class="wh-subtotal">
										</td>
									</tr>
									<tr id="totalmoney">
										<td><span class="bold"><?php echo _l('total_money'); ?> :</span>
										</td>
										<td class="wh-total">
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div id="removed-items"></div>
					</div>

					<div class="row">
						<div class="col-md-12 mtop15">
							<div class="panel-body ">
								<?php $description = (isset($goods_receipt) ? $goods_receipt->description : ''); ?>
								<?php echo render_textarea('description','note',$description,array(),array(),'mtop15'); ?>

								<div class="btn-bottom-toolbar text-right">
									<a href="<?php echo admin_url('warehouse/manage_purchase'); ?>"class="btn btn-default text-right mright5"><?php echo _l('close'); ?></a>

									<?php if(wh_check_approval_setting('1') != false) { ?>
										<?php if(isset($goods_receipt) && $goods_receipt->approval != 1){ ?>
											<a href="javascript:void(0)"class="btn btn-info pull-right mright5 add_goods_receipt_send" ><?php echo _l('save_send_request'); ?></a>
										<?php }elseif(!isset($goods_receipt)){ ?>
											<a href="javascript:void(0)"class="btn btn-info pull-right mright5 add_goods_receipt_send" ><?php echo _l('save_send_request'); ?></a>
										<?php } ?>
									<?php } ?>

									<?php if (is_admin() || has_permission('wh_stock_import', '', 'edit') || has_permission('wh_stock_import', '', 'create')) { ?>
										<?php if(isset($goods_receipt) && $goods_receipt->approval == 0){ ?>
											<a href="javascript:void(0)"class="btn btn-info pull-right mright5 add_goods_receipt" ><?php echo _l('submit'); ?></a>
										<?php }elseif(!isset($goods_receipt)){ ?>
											<a href="javascript:void(0)"class="btn btn-info pull-right mright5 add_goods_receipt" ><?php echo _l('submit'); ?></a>
										<?php } ?>
									<?php } ?>
								</div>
							</div>
							<div class="btn-bottom-pusher"></div>
						</div>
					</div>

 			</div>

 			<?php echo form_close(); ?>

 			<!-- Attachments Section -->
 			<div class="panel_s mtop15">
 				<div class="panel-body">
 					<h4 class="no-margin font-bold"><i class="fa fa-paperclip"></i> <?php echo _l('attachments'); ?> <span class="text-danger">*</span></h4>
 					<hr>

 					<!-- Upload new attachments -->
 					<div class="row">
 						<div class="col-md-8">
 							<input type="file" name="attachments[]" id="grn_attachments" form="add_goods_receipt" multiple class="form-control" <?php if(!isset($goods_receipt)){ echo 'required'; } ?>>
 							<small class="text-muted"><?php echo _l('upload'); ?> <?php if(!isset($goods_receipt)){ echo '(' . _l('required') . ')'; } ?></small>
 						</div>
 					</div>

 					<!-- GRN Attachments (edit mode) -->
 					<?php if(isset($goods_receipt)){
 						$CI =& get_instance();
 						$CI->db->where('rel_id', $goods_receipt->id);
 						$CI->db->where('rel_type', 'goods_receipt');
 						$grn_files = $CI->db->get(db_prefix().'files')->result_array();
 						if(count($grn_files) > 0){ ?>
 						<div class="mtop15">
 							<h5 class="bold"><?php echo _l('goods_receipt'); ?> <?php echo _l('attachments'); ?></h5>
 							<?php foreach($grn_files as $gf){ 
 								$grn_file_url = site_url('modules/warehouse/uploadsgoods_receipt/'.$gf['rel_id'].'/'.$gf['file_name']);
 							?>
 							<div class="mbot10 row grn-existing-file">
 								<div class="col-md-8">
 									<a href="javascript:void(0);" onclick="preview_grn_file(this); return false;" data-url="<?php echo $grn_file_url; ?>" data-name="<?php echo $gf['file_name']; ?>" class="mright5 btn btn-success btn-xs" data-toggle="tooltip" title="<?php echo _l('preview_file'); ?>"><i class="fa fa-eye"></i></a>
 									<i class="<?php echo get_mime_class($gf['filetype']); ?>"></i>
 									<a href="<?php echo $grn_file_url; ?>" target="_blank" download><?php echo $gf['file_name']; ?></a>
 								</div>
 								<div class="col-md-2"><small class="text-muted"><?php echo _d($gf['dateadded']); ?></small></div>
 								<div class="col-md-2 text-right">
 									<?php if(($gf['staffid'] == get_staff_user_id() || is_admin()) && (!isset($goods_receipt) || $goods_receipt->approval != 1)){ ?>
 									<a href="<?php echo admin_url('warehouse/delete_grn_attachment/'.$gf['id'].'/'.$goods_receipt->id); ?>" class="text-danger _delete"><i class="fa fa-times"></i></a>
 									<?php } ?>
 								</div>
 							</div>
 							<?php } ?>
 						</div>
 						<?php }
 					} ?>

 					<!-- GRN Attachment Preview Modal -->
 					<div id="grn_inline_preview"></div>

 					<!-- PR & PO Attachments (loaded via AJAX when PO selected, or from DB on edit) -->
 					<div id="po-pr-attachments">
 					<?php
 					$po_id_for_attachments = '';
 					if(isset($goods_receipt) && isset($goods_receipt->pr_order_id) && is_numeric($goods_receipt->pr_order_id) && $goods_receipt->pr_order_id > 0){
 						$po_id_for_attachments = $goods_receipt->pr_order_id;
 					}
 					if($po_id_for_attachments != ''){
 						$CI =& get_instance();
 						$CI->db->select('pur_request');
 						$CI->db->where('id', $po_id_for_attachments);
 						$po_row = $CI->db->get(db_prefix().'pur_orders')->row();
 						if($po_row && is_numeric($po_row->pur_request) && $po_row->pur_request > 0){
 							$CI->db->where('rel_id', $po_row->pur_request);
 							$CI->db->where('rel_type', 'pur_request');
 							$pr_files = $CI->db->get(db_prefix().'files')->result_array();
 							if(count($pr_files) > 0){ ?>
 							<div class="mtop15">
 								<h5 class="bold"><i class="fa fa-file-text-o"></i> <?php echo _l('pur_request'); ?> <?php echo _l('attachments'); ?></h5>
 								<?php foreach($pr_files as $prf){ ?>
 								<div class="mbot10 row">
 									<div class="col-md-8">
 										<a href="javascript:void(0);" onclick="preview_grn_file(this); return false;" data-url="<?php echo site_url(PURCHASE_PATH.'pur_request/'.$prf['rel_id'].'/'.$prf['file_name']); ?>" data-name="<?php echo $prf['file_name']; ?>" class="mright5 btn btn-success btn-xs" data-toggle="tooltip" title="<?php echo _l('preview_file'); ?>"><i class="fa fa-eye"></i></a>
 										<i class="<?php echo get_mime_class($prf['filetype']); ?>"></i>
 										<a href="<?php echo site_url(PURCHASE_PATH.'pur_request/'.$prf['rel_id'].'/'.$prf['file_name']); ?>" target="_blank" download><?php echo $prf['file_name']; ?></a>
 									</div>
 									<div class="col-md-4"><small class="text-muted"><i class="fa fa-user"></i> <?php echo get_staff_full_name($prf['staffid']); ?> | <?php echo _d($prf['dateadded']); ?></small></div>
 								</div>
 								<?php } ?>
 							</div>
 							<?php }
 						}
 						// PO Attachments
 						$CI->db->where('rel_id', $po_id_for_attachments);
 						$CI->db->where('rel_type', 'pur_order');
 						$po_files = $CI->db->get(db_prefix().'files')->result_array();
 						if(count($po_files) > 0){ ?>
 						<div class="mtop15">
 							<h5 class="bold"><i class="fa fa-shopping-cart"></i> <?php echo _l('pur_order'); ?> <?php echo _l('attachments'); ?></h5>
 							<?php foreach($po_files as $pof){ ?>
 							<div class="mbot10 row">
 								<div class="col-md-8">
 									<a href="javascript:void(0);" onclick="preview_grn_file(this); return false;" data-url="<?php echo site_url(PURCHASE_PATH.'pur_order/'.$pof['rel_id'].'/'.$pof['file_name']); ?>" data-name="<?php echo $pof['file_name']; ?>" class="mright5 btn btn-success btn-xs" data-toggle="tooltip" title="<?php echo _l('preview_file'); ?>"><i class="fa fa-eye"></i></a>
 									<i class="<?php echo get_mime_class($pof['filetype']); ?>"></i>
 									<a href="<?php echo site_url(PURCHASE_PATH.'pur_order/'.$pof['rel_id'].'/'.$pof['file_name']); ?>" target="_blank" download><?php echo $pof['file_name']; ?></a>
 								</div>
 								<div class="col-md-4"><small class="text-muted"><i class="fa fa-user"></i> <?php echo get_staff_full_name($pof['staffid']); ?> | <?php echo _d($pof['dateadded']); ?></small></div>
 							</div>
 							<?php } ?>
 						</div>
 						<?php }
 					} ?>
 					</div>
 				</div>
 			</div>

 		</div>
 	</div>
 </div>
</div>
</div>
</div>
<div id="modal_wrapper"></div>


<?php init_tail(); ?>
<?php if(isset($goods_receipt) && $goods_receipt->approval == 1){ ?>
<script>
$(function(){
  // Disable all form fields when GRN is approved
  $('form').find('input, select, textarea').prop('disabled', true).prop('readonly', true);
  $('form').find('.selectpicker').selectpicker('refresh');
  // Hide submit/save buttons and upload form
  $('button[type="submit"], .btn-submit, #obgy_btn2').hide();
  // Hide delete attachment buttons
  $('a._delete, a[href*="delete_grn_attachment"]').hide();
  // Hide file upload inputs
  $('input[type="file"]').closest('.form-group').hide();
});
</script>
<?php } ?>
<?php require 'modules/warehouse/assets/js/purchase_js.php';?>
</body>
</html>

