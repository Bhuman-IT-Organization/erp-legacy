<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12" id="small-table">
				<div class="panel_s">
					<div class="panel-body">

						<?php if(isset($goods_receipt->is_deleted) && $goods_receipt->is_deleted == 1){ ?>
						<div class="alert alert-danger text-center">
							<strong><i class="fa fa-ban"></i> <?php echo _l('returned'); ?></strong> - <?php echo _l('this_record_has_been_returned_no_changes_allowed'); ?>
						</div>
						<?php } ?>

						<div class="row">
							<div class="col-md-12">
								<h4 class="no-margin font-bold"><i class="fa fa-clone menu-icon menu-icon" aria-hidden="true"></i> <?php echo _l($title); ?></h4>
								<hr>
							</div>
						</div>
						<div class="row row-margin" >

							<div class="col-md-9">
								<div class="panel panel-info col-md-12 panel-padding" >

									<div class="panel-body">
										<div class="col-md-6">
											<?php $company_name = get_option('invoice_company_name'); 
											$address = get_option('invoice_company_address'); 
											$day = date('d',strtotime($goods_receipt->date_add));
											$month = date('m',strtotime($goods_receipt->date_add));
											$year = date('Y',strtotime($goods_receipt->date_add));?>

										</div>

										<div class="col-md-4">

										</div>
										<div class="col-md-12">
											<p><h2 class="bold text-center" ><?php echo mb_strtoupper(_l('store_input_slip')); ?></h2></p>
										</div>
										<div class="col-md-4">
										</div>

										<div class="col-md-3">
										</div>

										<div class="col-md-12 pull-right">
											<br>
											<div class="row">
												<div class="col-md-3 pull-right">
													<p><span class="bold"><?php echo _l('debit'); ?>: </span>.....................</p>
													<p><span class="bold"><?php echo _l('credit'); ?>: </span>.....................</p>
												</div>
												<div class="col-md-4 pull-right">
													<p><span class="span-font-style"><?php echo _l('days').' '.$day.' '._l('month').' '.$month.' '._l('year') .' '.$year; ?></p>
														<p><span class="bold"><?php echo _l('stock_received_docket_number'); ?>: </span><?php echo new_html_entity_decode($goods_receipt->goods_receipt_code) ?></p>
													</div>
												</div>
											</div>

											<table class="table">
												<tbody>

													<tr class="project-overview">
														<td class="bold" width="30%"><?php echo _l('supplier_name'); ?></td>
														<?php 
														if(get_status_modules_wh('purchase') && ($goods_receipt->supplier_code != '') && ($goods_receipt->supplier_code != 0) ){ ?>
															<td><?php echo new_html_entity_decode(wh_get_vendor_company_name($goods_receipt->supplier_code)) ; ?></td>
														<?php   }else{?>
															<td><?php echo new_html_entity_decode($goods_receipt->supplier_name) ; ?></td>
														<?php   }


														?>

													</tr>
													<tr class="project-overview">
														<td class="bold" width="30%"><?php echo _l('deliver_name'); ?></td>
														<td><?php echo new_html_entity_decode($goods_receipt->deliver_name) ; ?></td>
													</tr>
													<tr class="project-overview">
														<td class="bold"><?php echo _l('requester'); ?></td>
														<td><?php 
                                                            // Show PR requester name
                                                            $display_staff_id = 0;
                                                            if(!empty($goods_receipt->requester) && $goods_receipt->requester > 0){
                                                                $display_staff_id = $goods_receipt->requester;
                                                            }
                                                            if($display_staff_id > 0){
                                                                echo get_staff_full_name($display_staff_id);
                                                            }
                                                        ?></td>
													</tr>

													<?php 
													if (get_status_modules_wh('purchase')) {
														if( ($goods_receipt->pr_order_id != '') && ($goods_receipt->pr_order_id != 0) ){ ?>

															<tr class="project-overview">
																<td class="bold"><?php echo _l('reference_purchase_order'); ?></td>
																<td>
																	<a href="<?php echo admin_url('purchase/purchase_order/'.$goods_receipt->pr_order_id) ?>" ><?php echo get_pur_order_name($goods_receipt->pr_order_id) ?></a>

																</td>
															</tr>

															<?php
															// Show PR Number linked from PO
															$CI_pr = &get_instance();
															$CI_pr->db->select('pur_request');
															$CI_pr->db->where('id', $goods_receipt->pr_order_id);
															$po_info = $CI_pr->db->get(db_prefix().'pur_orders')->row();
															if($po_info && is_numeric($po_info->pur_request) && $po_info->pur_request > 0){
																$CI_pr->db->select('pur_rq_code');
																$CI_pr->db->where('id', $po_info->pur_request);
																$pr_info = $CI_pr->db->get(db_prefix().'pur_request')->row();
																if($pr_info){ ?>
															<tr class="project-overview">
																<td class="bold"><?php echo _l('purchase_request'); ?></td>
																<td>
																	<a href="<?php echo admin_url('purchase/view_pur_request/'.$po_info->pur_request) ?>"><?php echo $pr_info->pur_rq_code; ?></a>
																</td>
															</tr>
															<?php }} ?>

														<?php   }
													} 
													?>

													<tr class="project-overview">
														<td class="bold"><?php echo _l('note_'); ?></td>
														<td><?php 
                                                            $grn_description = isset($goods_receipt->description) ? $goods_receipt->description : '';
                                                            // If description is empty, try to get from PO/PR
                                                            if(empty(trim(strip_tags($grn_description ?? ''))) && !empty($goods_receipt->pr_order_id) && $goods_receipt->pr_order_id > 0){
                                                                $CI_desc = &get_instance();
                                                                $CI_desc->db->reset_query();
                                                                $CI_desc->db->select('po.pur_order_name, po.vendornote');
                                                                $CI_desc->db->from(db_prefix().'pur_orders po');
                                                                $CI_desc->db->where('po.id', $goods_receipt->pr_order_id);
                                                                $po_desc = $CI_desc->db->get()->row();
                                                                if($po_desc){
                                                                    if(!empty($po_desc->vendornote)){
                                                                        $grn_description = $po_desc->vendornote;
                                                                    } elseif(!empty($po_desc->pur_order_name)){
                                                                        $grn_description = $po_desc->pur_order_name;
                                                                    }
                                                                }
                                                                // If still empty, show item names from PO detail
                                                                if(empty(trim(strip_tags($grn_description ?? '')))){
                                                                    $CI_desc->db->reset_query();
                                                                    $CI_desc->db->select('item_name');
                                                                    $CI_desc->db->where('pur_order', $goods_receipt->pr_order_id);
                                                                    $po_items = $CI_desc->db->get(db_prefix().'pur_order_detail')->result_array();
                                                                    $item_names = [];
                                                                    foreach($po_items as $pi){
                                                                        if(!empty($pi['item_name'])) $item_names[] = $pi['item_name'];
                                                                    }
                                                                    if(!empty($item_names)){
                                                                        $grn_description = implode(', ', $item_names);
                                                                    }
                                                                }
                                                                $CI_desc->db->reset_query();
                                                            }
                                                            echo new_html_entity_decode($grn_description);
                                                        ?></td>
													</tr>
													<tr>

														<td class="bold"><?php echo _l('print'); ?></td>
														<td>
															<div class="btn-group">
																<a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-file-pdf"></i><?php if(is_mobile()){echo ' PDF';} ?> <span class="caret"></span></a>
																<ul class="dropdown-menu dropdown-menu-right">
																	<li class="hidden-xs"><a href="<?php echo admin_url('warehouse/stock_import_pdf/'.$goods_receipt->id.'?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
																	<li class="hidden-xs"><a href="<?php echo admin_url('warehouse/stock_import_pdf/'.$goods_receipt->id.'?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
																	<li><a href="<?php echo admin_url('warehouse/stock_import_pdf/'.$goods_receipt->id); ?>"><?php echo _l('download'); ?></a></li>
																	<li>
																		<a href="<?php echo admin_url('warehouse/stock_import_pdf/'.$goods_receipt->id.'?print=true'); ?>" target="_blank">
																			<?php echo _l('print'); ?>
																		</a>
																	</li>
																</ul>
															</div>

														</td>
													</tr>

													<tr class="project-overview">
														<td class="bold"><?php echo _l('invoice_no'); ?></td>
														<td><?php echo new_html_entity_decode($goods_receipt->invoice_no) ; ?></td>
													</tr>
													<tr class="project-overview">
														<td class="bold">BOE</td>
														<td><?php echo isset($goods_receipt->boe) ? new_html_entity_decode($goods_receipt->boe) : ''; ?></td>
													</tr>





												</tbody>
											</table>
											<div class="table-responsive">
												<?php 
												$display_product_image = get_option('display_product_image_receipt_delivery_pdf');
												?>
											<table class="table table-bordered">
												<tbody>
													<tr>

														<th align="center">#</th>
														<?php 
														// Check if PO type is Asset
														$_grn_po_type = '';
														$CI_grn =& get_instance();
														if(isset($goods_receipt->pr_order_id) && is_numeric($goods_receipt->pr_order_id) && $goods_receipt->pr_order_id > 0){
															$CI_grn->db->reset_query();
															$CI_grn->db->select('type');
															$CI_grn->db->where('id', $goods_receipt->pr_order_id);
															$_po_type_row = $CI_grn->db->get(db_prefix().'pur_orders')->row();
															if($_po_type_row) $_grn_po_type = $_po_type_row->type;
														}
														// Also check GRN's own type field as fallback
														if(empty($_grn_po_type) && isset($goods_receipt->type) && !empty($goods_receipt->type)){
															$_grn_po_type = $goods_receipt->type;
														}
														if($_grn_po_type == 'AS'){ ?>
														<th colspan="1"><?php echo _l('asset_id'); ?></th>
														<?php } ?>
														<?php if($display_product_image){ ?>
															<th  colspan="1"><?php echo _l('_images') ?></th>
														<?php } ?>
														<th  colspan="1"><?php echo _l('commodity_code') ?></th>
														<th colspan="1"><?php echo _l('warehouse_name') ?></th>
														<th  colspan="1"><?php echo _l('unit_name') ?></th>
														<th  colspan="1"><?php echo _l('wh_item_description') ?></th>
														<th  colspan="1" class="text-center"><?php echo _l('quantity') ?></th>
														<th align="right" colspan="1" class="hide"><?php echo _l('unit_price') ?></th>
														<th align="right" colspan="1"><?php echo _l('received_qty') ?></th>
														<th align="right" colspan="1"><?php echo _l('pending_qty') ?></th>
														<th align="right" colspan="1" class="hide"><?php echo _l('total_money') ?></th>
														<th align="right" colspan="1" class="hide"><?php echo _l('tax_money') ?></th>
														<th align="right" colspan="1"><?php echo _l('lot_number') ?></th>
														<th align="right" colspan="1"><?php echo _l('expiry_date') ?></th>

													</tr>


													<?php 
                                                    // Pre-load PO detail items (to match by position for duplicate item_codes)
                                                    $_po_detail_items = [];
                                                    $_po_desc_by_item = [];
                                                    if(isset($goods_receipt->pr_order_id) && is_numeric($goods_receipt->pr_order_id) && $goods_receipt->pr_order_id > 0){
                                                        $CI_po_det = &get_instance();
                                                        $CI_po_det->db->reset_query();
                                                        $CI_po_det->db->where('pur_order', $goods_receipt->pr_order_id);
                                                        $CI_po_det->db->order_by('id', 'asc');
                                                        $_po_detail_items = $CI_po_det->db->get(db_prefix().'pur_order_detail')->result_array();

                                                        // Fallback lookup for partial receipts where GRN line order
                                                        // does not follow PO line order. Keyed by item so another
                                                        // product's description can never leak in.
                                                        foreach($_po_detail_items as $_po_seed){
                                                            $_po_seed_code = (string)$_po_seed['item_code'];
                                                            if($_po_seed_code !== '' && !isset($_po_desc_by_item[$_po_seed_code]) && !empty($_po_seed['description'])){
                                                                $_po_desc_by_item[$_po_seed_code] = $_po_seed['description'];
                                                            }
                                                        }
                                                        $CI_po_det->db->reset_query();
                                                    }
                                                    ?>

													<?php foreach (json_decode($goods_receipt_detail) as $receipt_key => $receipt_value) {
														$receipt_key++;
														$quantities = (isset($receipt_value) ? $receipt_value->quantities : '');
														$unit_price = (isset($receipt_value) ? $receipt_value->unit_price : '');
														$unit_price = (isset($receipt_value) ? $receipt_value->unit_price : '');
														$goods_money = (isset($receipt_value) ? $receipt_value->goods_money : '');

														$commodity_code = '';
														$commodity_name = '';
														$commodity_description = '';
														
														// First try to get name from GRN detail commodity_name field
														$grn_detail_name = isset($receipt_value->commodity_name) ? $receipt_value->commodity_name : '';
														
														if($_grn_po_type == 'AS'){
															// Asset type - get name from asset creation table or commodity_name field
															$commodity_name = $grn_detail_name;
															if(empty($commodity_name) && is_numeric($receipt_value->commodity_code)){
																try {
																	$CI_grn->db->reset_query();
																	$CI_grn->db->select('asset_title, descriptions');
																	$CI_grn->db->where('id', $receipt_value->commodity_code);
																	$_ac_grn = $CI_grn->db->get(db_prefix().'fe_asset_creation')->row();
																	if($_ac_grn){
																		$commodity_name = $_ac_grn->asset_title . ($_ac_grn->descriptions != '' ? ' - '.$_ac_grn->descriptions : '');
																	}
																} catch(\Throwable $e) {
																	// fe_asset_creation table may not exist
																}
															}
														} else {
															$get_commodity_name = get_commodity_name($receipt_value->commodity_code);
															if($get_commodity_name){
																$commodity_code = $get_commodity_name->commodity_code;
																$commodity_name = $get_commodity_name->description;
															} else {
																// Item not in tblitems - use commodity_name from GRN detail
																$commodity_name = $grn_detail_name;
															}
														}

														// Line description: GRN line's own item_description, else the
														// matching PO line, else blank. Never the item master's
														// long_description - that is generic catalogue text for a
														// possibly unrelated tblitems row.
														$commodity_description = '';

														$_grn_line_code = (string)$receipt_value->commodity_code;

														if(!empty($receipt_value->item_description)){
															$commodity_description = $receipt_value->item_description;
														} else {
															$_grn_po_line = isset($_po_detail_items[$receipt_key - 1]) ? $_po_detail_items[$receipt_key - 1] : null;

															// Position first (handles repeated item codes), then verify
															// it is the same item.
															if($_grn_po_line
															   && (string)$_grn_po_line['item_code'] === $_grn_line_code
															   && !empty($_grn_po_line['description'])){
																$commodity_description = $_grn_po_line['description'];
															} elseif(isset($_po_desc_by_item[$_grn_line_code])){
																// Partial receipt - positions do not line up.
																$commodity_description = $_po_desc_by_item[$_grn_line_code];
															}
														}

														$unit_name = '';
														if(is_numeric($receipt_value->unit_id) && (float)$receipt_value->unit_id > 0){
														$unit_name = get_unit_type($receipt_value->unit_id) != null ? get_unit_type($receipt_value->unit_id)->unit_name : '';
														}

														$warehouse_code = get_warehouse_name($receipt_value->warehouse_id) != null ? get_warehouse_name($receipt_value->warehouse_id)->warehouse_name : '';
														$tax_money =(isset($receipt_value) ? $receipt_value->tax_money : '');
														$expiry_date =(isset($receipt_value) ? $receipt_value->expiry_date : '');
														$lot_number =(isset($receipt_value) ? $receipt_value->lot_number : '');
														if($display_product_image){
															$item_image = $this->warehouse_model->get_item_image($receipt_value->commodity_code);
														}
														?>
														<tr>
															<td><?php echo new_html_entity_decode($receipt_key) ?></td>
															<?php if($_grn_po_type == 'AS'){ 
																// Get asset_uid from GRN detail or PO detail
																$_asset_uid_val = isset($receipt_value->asset_uid) ? $receipt_value->asset_uid : '';
																if($_asset_uid_val == '' && is_numeric($receipt_value->commodity_code)){
																	$CI_grn->db->reset_query();
																	$CI_grn->db->select('asset_uid');
																	$CI_grn->db->where('item_code', $receipt_value->commodity_code);
																	$CI_grn->db->where('pur_order', $goods_receipt->pr_order_id);
																	$_po_det = $CI_grn->db->get(db_prefix().'pur_order_detail')->row();
																	if($_po_det && $_po_det->asset_uid) $_asset_uid_val = $_po_det->asset_uid;
																}
															?>
															<td><?php echo $_asset_uid_val; ?></td>
															<?php } ?>
															<?php if($display_product_image){ ?>
																<td ><?php echo new_html_entity_decode($item_image) ?></td>
															<?php } ?>
															<td ><?php 
                                                                $display_item_name = $commodity_name != '' ? $commodity_name : wh_get_item_variatiom($receipt_value->commodity_code);
                                                                if(!empty($commodity_code)){
                                                                    $display_item_name = $commodity_code . ' - ' . $display_item_name;
                                                                }
                                                                echo new_html_entity_decode($display_item_name); 
                                                            ?></td>
															<td ><?php echo new_html_entity_decode($warehouse_code) ?></td>
															<td ><?php echo new_html_entity_decode($unit_name) ?></td>
															<td ><?php echo new_html_entity_decode($commodity_description) ?></td>
															<td class="text-right" ><?php echo new_html_entity_decode($quantities) ?></td>
															<td class="text-right hide"><?php echo app_format_money((float)$unit_price,'') ?></td>
															<td class="text-right"><?php echo isset($receipt_value->received_qty) ? new_html_entity_decode($receipt_value->received_qty) : '0' ?></td>
															<td class="text-right"><?php echo isset($receipt_value->pending_qty) ? new_html_entity_decode($receipt_value->pending_qty) : '0' ?></td>
															<td class="text-right hide"><?php echo app_format_money((float)$goods_money,'') ?></td>
															<td class="text-right hide"><?php echo app_format_money((float)$tax_money,'') ?></td>
															<td class="text-right"><?php echo new_html_entity_decode($lot_number) ?></td>
															<td class="text-right"><?php echo _d($expiry_date) ?></td>
														</tr>
													<?php } ?>
												</tbody>
											</table>
										</div>
											<div class="row pull-right mbot10 hide">
												<div class="col-md-12 ">
													<table class="table">
														<tbody>
															<tr>
																<td class="bold" width="30%"><?php echo _l('total_goods_money')  ?> :</td>
																<td><?php echo  app_format_money((float) $goods_receipt->total_goods_money, $currency)  ?></td>
															</tr>
															<tr>
																<td class="bold"  width="30%"><?php echo  _l('value_of_inventory')  ?> :</td>
																<td><?php echo app_format_money((float) $goods_receipt->value_of_inventory, $currency) ?></td>
															</tr>

															<?php if(isset($goods_receipt) && $tax_data['html_currency'] != ''){
																echo new_html_entity_decode($tax_data['html_currency']);
															} ?>

															<tr>
																<td class="bold " ><?php echo  _l('total_tax_money')  ?> :</td>
																<td><?php echo app_format_money((float) $goods_receipt->total_tax_money, $currency) ?></td>
															</tr>
															<tr>
																<td class="bold " ><?php echo  _l('total_money')  ?>:</td>
																<td><?php echo  app_format_money((float) $goods_receipt->total_money, $currency)  ?></td>
															</tr>
															<tr></tr>
														</tbody>
													</table>
												</div>
											</div>
											<br>


											<div class="row">
												<div class="col-md-12 ">
													<div class="col-md-4 pull-right">
														<p><span class="span-font-style"><?php echo _l('days').' ......... '._l('month').' ......... '._l('year') .' .......... '; ?>
													</div>
												</div>
											</div>
											<br>
											<div class="row">
												<div class="col-md-1">
												</div>
												<div class="col-md-6">
													<p><?php 
                                                        if(isset($goods_receipt->addedfrom) && $goods_receipt->addedfrom > 0){
                                                            $CI_wh2 = &get_instance();
                                                            $CI_wh2->db->reset_query();
                                                            $CI_wh2->db->select('firstname, lastname');
                                                            $CI_wh2->db->where('staffid', $goods_receipt->addedfrom);
                                                            $_wh_staff2 = $CI_wh2->db->get(db_prefix().'staff')->row();
                                                            if($_wh_staff2) echo trim($_wh_staff2->firstname . ' ' . $_wh_staff2->lastname);
                                                        }
                                                    ?></p>
													<p><span class="bold"><?php echo _l('warehouse_in_charge'); ?></span></p>
													<p><span class="span-font-style"><?php echo _l('sign_full_name') ?></span></p>
												</div>
												<div class="col-md-5">
													<p><?php 
                                                        if(isset($goods_receipt->department) && is_numeric($goods_receipt->department) && $goods_receipt->department > 0){
                                                            $CI_wh2->db->reset_query();
                                                            $CI_wh2->db->select('name');
                                                            $CI_wh2->db->where('departmentid', $goods_receipt->department);
                                                            $_dept2 = $CI_wh2->db->get(db_prefix().'departments')->row();
                                                            if($_dept2) echo $_dept2->name;
                                                        }
                                                    ?></p>
													<p><span class="bold">Requesting Department</span></p>
													<p><span class="span-font-style"><?php echo _l('sign_full_name') ?></span></p>
												</div>
											</div>

																	<br>
																	<br>
																	<br>
																	<br>


																	<div class="project-overview-right">
																		<?php if(count($list_approve_status) > 0){ ?>

																			<div class="row">
																				<div class="col-md-12 project-overview-expenses-finance">
																					<div class="col-md-4 text-center">
																					</div>
																					<?php 
																					$this->load->model('staff_model');
																					$enter_charge_code = 0;
																					foreach ($list_approve_status as $value) {
																						$value['staffid'] = new_explode(', ',$value['staffid']);
																						if($value['action'] == 'sign'){
																							?>
																							<div class="col-md-3 text-center">
																								<p class="text-uppercase text-muted no-mtop bold">
																									<?php
																									$staff_name = '';
																									$st = _l('status_0');
																									$color = 'warning';
																									foreach ($value['staffid'] as $key => $val) {
																										if($staff_name != '')
																										{
																											$staff_name .= ' or ';
																										}
																										$get_name = $this->staff_model->get($val);
																										if($get_name){
																											$staff_name .= $get_name->firstname;
																										}
																									
																									}
																									echo new_html_entity_decode($staff_name); 
																								?></p>
																								<?php if($value['approve'] == 1){ 
																									?>
																									<?php if (file_exists(WAREHOUSE_STOCK_IMPORT_MODULE_UPLOAD_FOLDER . $goods_receipt->id . '/signature_'.$value['id'].'.png') ){ ?>

																										<img src="<?php echo site_url('modules/warehouse/uploads/stock_import/'.$goods_receipt->id.'/signature_'.$value['id'].'.png'); ?>" class="img-width-height">

																									<?php }else{ ?>
																										<img src="<?php echo site_url('modules/warehouse/uploads/image_not_available.jpg'); ?>" class="img-width-height">
																									<?php } ?>


																								<?php }
																								?>    
																							</div>
																						<?php }else{ ?>
																							<div class="col-md-3 text-center">
																								<p class="text-uppercase text-muted no-mtop bold">
																									<?php
																									$staff_name = '';
																									foreach ($value['staffid'] as $key => $val) {
																										if($staff_name != '')
																										{
																											$staff_name .= ' or ';
																										}
																										$staff_name .= $this->staff_model->get($val)->firstname;
																									}
																									echo new_html_entity_decode($staff_name); 
																								?></p>
																								<?php if($value['approve'] == 1){ 
																									?>
																									<img src="<?php echo site_url('modules/warehouse/uploads/approval/approved.png') ; ?>" class="img-width-height">
																								<?php }elseif($value['approve'] == -1){ ?>
																									<img src="<?php echo site_url('modules/warehouse/uploads/approval/rejected.png') ; ?>" class="img-width-height">
																								<?php }
																								?>
																								<p class="text-muted no-mtop bold">  
																									<?php echo new_html_entity_decode($value['note']) ?>
																								</p>    
																							</div>
																						<?php }
																					} ?>
																				</div>

																			</div>

																		<?php } ?>
																	</div>
																	<div class="pull-right">

																		<?php 
																		if($goods_receipt->approval != 1 && ($check_approve_status == false ))

																			{ ?>
																				<?php if($check_appr && $check_appr != false){ ?>
																					<a data-toggle="tooltip" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-success lead-top-btn lead-view" data-placement="top" href="#" onclick="send_request_approve(<?php echo new_html_entity_decode($goods_receipt->id); ?>); return false;"><?php echo _l('send_request_approve'); ?></a>
																				<?php } ?>

																			<?php }
																			if(isset($check_approve_status['staffid'])){
																				?>
																				<?php 
              if((in_array(get_staff_user_id(), $check_approve_status['staffid']) && $goods_receipt->approval == 0 && !isset($check_approve_status['action']) && !in_array(get_staff_user_id(), $get_staff_sign) ) || (  in_array(get_staff_user_id(), $check_approve_status['staffid']) && $goods_receipt->approval == 0 && isset($check_approve_status['action']) && $check_approve_status['action'] == 'approve') ){ ?>
																					<div class="btn-group" >
																						<a href="#" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo _l('approve'); ?><span class="caret"></span></a>
																						<ul class="dropdown-menu dropdown-menu-right menu-width-height" >
																							<li>
																								<div class="col-md-12">
																									<?php echo render_textarea('reason', 'reason'); ?>
																								</div>
																							</li>
																							<li>
																								<div class="row text-right col-md-12">
																									<a href="#" data-loading-text="<?php echo _l('wait_text'); ?>" onclick="approve_request(<?php echo new_html_entity_decode($goods_receipt->id); ?>); return false;" class="btn btn-success button-margin"><?php echo _l('approve'); ?></a>
																									<a href="#" data-loading-text="<?php echo _l('wait_text'); ?>" onclick="deny_request(<?php echo new_html_entity_decode($goods_receipt->id); ?>); return false;" class="btn btn-warning"><?php echo _l('deny'); ?></a></div>
																								</li>
																							</ul>
																						</div>
																					<?php }
																					?>

																					<?php
                 if((in_array(get_staff_user_id(), $check_approve_status['staffid']) && in_array(get_staff_user_id(), $get_staff_sign) && $goods_receipt->approval == 0 && !isset($check_approve_status['action'])) || (in_array(get_staff_user_id(), $check_approve_status['staffid']) && in_array(get_staff_user_id(), $get_staff_sign) && $goods_receipt->approval == 0 && isset($check_approve_status['action']) && $check_approve_status['action'] == 'sign')  ){ ?>
																						<button onclick="accept_action();" class="btn btn-success pull-right action-button"><?php echo _l('e_signature_sign'); ?></button>
																					<?php }
																					?>
																					<?php 
																				}
																				?>
																			</div>
																		</div>
																	</div>
																	<?php $value = (isset($payslip->record) ? $payslip->record : []) ?>
																	<?php $record = $value; ?>
																	<?php $value = (isset($payslip->spending) ? $payslip->spending : []) ?>
																	<?php $spending = $value; ?>


																</div>

																<div class="col-md-3">
																	<div class="panel panel-info col-md-12 panel-padding">
																		<div class="panel-heading "><?php echo _l('approval_information'); ?></div>
																		<div class="panel-body">
																			<h5 class="no-margin">
																				<?php echo _l('approver_list'); ?>:
																			</h5>
																			<?php 
																			$stt = 1;
																			foreach ($list_approve_status as $key => $value) {
																				$value['staffid'] = new_explode(', ',$value['staffid']);
																				$approve = '<span class="label label-tag tag-id-1 label-tab2" class="status-border-color">' . _l('not_yet_approve') . '</span>';
																				if($value['approve'] == 1){
																					$approve = '<span class="label label-tag tag-id-1 label-tab1" class="status-border-color1">' . _l('approved') . '</span>';
																				}elseif ($value['approve'] == -1) {
																					$approve = '<span class="label label-tag tag-id-1 label-tab3" class="status-border-color2">' . _l('reject') . '</span>';
																				}
																				$staff_name = '';
																				foreach ($value['staffid'] as $key => $val) {
																					if($staff_name != '')
																					{
																						$staff_name .= ' or ';
																					}
																					$staff_name .= get_staff_full_name($val);
																				}
																				echo new_html_entity_decode($stt.': '.$staff_name.' '.$approve.'<br>');
																				$stt++;
																			}
																			?>

																			<hr class="hr-panel-heading" />
																			<h5 class="no-margin">
																				<?php echo _l('activity_log'); ?>
																			</h5>
																			<div class="activity-feed">
																				<?php $enter_code = 0;
																				foreach($payslip_log as $log){
																					$approve = '';



																					?>
																					<div class="feed-item">
																						<div class="row">
																							<div class="col-md-12">
																								<div class="date"><span class="text-has-action" data-toggle="tooltip" data-title="<?php echo _dt($log['date']); ?>"><?php echo time_ago($log['date']); ?></span></div>
																								<div class="text">
																									<?php

																									$fullname = get_staff_full_name($log['staffid']);
																									if($log['staffid'] != 0){ ?>
																										<a href="<?php echo admin_url('profile/'.$log['staffid']); ?>"><?php echo staff_profile_image($log['staffid'],array('staff-profile-xs-image','pull-left mright10')); ?></a>
																									<?php }?>

																									<p class="mtop10 no-mbot"><?php echo new_html_entity_decode($fullname) . ' - <b>'.
																								_l($log['note']).'</b>'; ?></p>

																							</div>
																						</div>

																						<div class="clearfix"></div>
																						<div class="col-md-12">
																							<hr class="hr-10" />
																						</div>
																					</div>
																				</div>
																				<?php 

																			} ?>
																		</div>
																	</div>
																</div>
															</div>

														</div>
													</div>

													<div class="row">
														<div class="col-md-12 mtop15">
															<div class="panel-body ">
																<div class="btn-bottom-toolbar text-right">
																	<a href="<?php echo admin_url('warehouse/manage_purchase'); ?>"class="btn btn-default text-right mright5 close_button"><?php echo _l('close'); ?></a>
																</div>
															</div>
														</div>
													</div>

												</div>

											</div>

										</div>
									</div>
								</div>
							</div>

							<div class="modal fade" id="add_action" tabindex="-1" role="dialog">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-body">
											<p class="bold" id="signatureLabel"><?php echo _l('signature'); ?></p>
											<div class="signature-pad--body">
												<canvas id="signature" height="130" width="550"></canvas>
											</div>
											<input type="text" class="sig-input-style" tabindex="-1" name="signature" id="signatureInput">
											<div class="dispay-block">
												<button type="button" class="btn btn-default btn-xs clear" tabindex="-1" onclick="signature_clear();"><?php echo _l('clear'); ?></button>
											</div>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
											<button onclick="sign_request(<?php echo new_html_entity_decode($goods_receipt->id); ?>);" autocomplete="off" class="btn btn-success sign_request_class"><?php echo _l('e_signature_sign'); ?></button>
										</div>
									</div>
								</div>
							</div>

						</div>
					</div>
				</div>


				<?php init_tail(); ?>
				<?php if(isset($goods_receipt) && $goods_receipt->approval == 1){ ?>
				<script>
				$(function(){
				  $('form').find('input, select, textarea').prop('disabled', true).prop('readonly', true);
				  $('form').find('.selectpicker').selectpicker('refresh');
				  $('button[type="submit"], .btn-submit, #obgy_btn2').hide();
				  $('a._delete, a[href*="delete_grn_attachment"]').hide();
				  $('input[type="file"]').closest('.form-group').hide();
				});
				</script>
				<?php } ?>
				<?php require 'modules/warehouse/assets/js/edit_purchase_js.php';?>
			</body>
			</html>


