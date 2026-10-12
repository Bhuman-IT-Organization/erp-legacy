<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">

    <?php echo form_open_multipart(admin_url('purchase/pur_invoice_form'),array('id'=>'pur_invoice-form','class'=>'_pur_invoice_form _transaction_form')); ?>
    	<?php
    		if(isset($pur_order)){
		        echo form_hidden('isedit');
		    }
    	 ?>
      <div class="col-md-12">
        <div class="panel_s accounting-template estimate">
          <div class="panel-body">

          	<div class="row">
             <div class="col-md-12">
              <h4 class="no-margin font-bold"><i class="fa <?php if(isset($pur_invoice)){ echo 'fa-pencil-square';}else{ echo 'fa-plus';} ?>" aria-hidden="true"></i> <?php echo _l($title); ?> <?php if(isset($pur_invoice)){ echo ' '.pur_html_entity_decode($pur_invoice->invoice_number); } ?></h4>
              <hr />
             </div>
            </div>

            <div class="row">
            	<?php $additional_discount = 0; ?>
                  <input type="hidden" name="additional_discount" value="<?php echo pur_html_entity_decode($additional_discount); ?>">
            	<div class="col-md-6">
            		<?php echo form_hidden('id', (isset($pur_invoice) ? $pur_invoice->id : '') ); ?>
	            	<div class="col-md-6 pad_left_0">
	            		<label for="invoice_number"><span class="text-danger">* </span><?php echo _l('invoice_code'); ?></label>
		            	<?php
	                    $prefix = get_purchase_option('pur_inv_prefix');
	                    $next_number = get_purchase_option('next_inv_number');
	                    $number = (isset($pur_invoice) ? $pur_invoice->number : $next_number);
	                    echo form_hidden('number',$number); ?> 
	                           
	                    <?php $invoice_number = ( isset($pur_invoice) ? $pur_invoice->invoice_number : $prefix.str_pad($next_number,5,'0',STR_PAD_LEFT));
	                    echo render_input('invoice_number','',$invoice_number ,'text',array('readonly' => '', 'required' => 'true')); ?>
	                </div>

	                <div class="col-md-6 pad_right_0">
	                	<?php $vendor_invoice_number = ( (isset($pur_invoice) && $pur_invoice->vendor_invoice_number != '') ? $pur_invoice->vendor_invoice_number : $invoice_number); ?>
	                	<label for="vendor_invoice_number"><span class="text-danger">* </span><?php echo _l('invoice_number'); ?></label>
	                	<input type="text" id="vendor_invoice_number" name="vendor_invoice_number" class="form-control" value="<?php echo $vendor_invoice_number; ?>" required="true">
	                </div>

	                <div class="col-md-6 pad_left_0 form-group">
	                	<label for="vendor"><span class="text-danger">* </span><?php echo _l('pur_vendor'); ?></label>
	                    <select name="vendor" id="vendor" class="selectpicker" onchange="pur_vendor_change(this); return false;" required="true" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
	                        <option value=""></option>
	                        <?php 
	                        $selected_vendor = '';
	                        if(isset($pur_invoice)){
	                            $selected_vendor = $pur_invoice->vendor;
	                        } else if(isset($ven) && is_numeric($ven)){
	                            $selected_vendor = $ven;
	                        }
	                        if(isset($vendors) && is_array($vendors)){
	                            foreach($vendors as $v){ ?>
	                                <option value="<?php echo $v['userid']; ?>" <?php if($selected_vendor == $v['userid']){ echo 'selected'; } ?>><?php echo $v['company']; ?></option>
	                            <?php }
	                        } ?>
	                    </select>
	                </div>

	                <!-- <div class="col-md-6 form-group pad_right_0">
	                	<label for="contract"><?php echo _l('contract'); ?></label>
	                    <select name="contract" id="contract" class="selectpicker" onchange="contract_change(this); return false;" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
	                        <option value=""></option>
	                        <?php foreach($contracts as $ct){ ?>
	                        	<option value="<?php echo pur_html_entity_decode($ct['id']); ?>" <?php if(isset($pur_invoice) && $pur_invoice->contract == $ct['id']){ echo 'selected'; } ?>><?php echo pur_html_entity_decode($ct['contract_number']); ?></option>
	                        <?php } ?>
	                    </select>
	                </div> -->
	              
	                <div class="col-md-6 form-group pad_left_0">
	                	<label for="pur_order"><?php echo _l('pur_order'); ?></label>
	                	<?php
	                	// Build array of all PO IDs linked to this invoice (from grn_id field)
	                	$_inv_po_ids = [];
	                	if(isset($pur_invoice)){
	                		if(is_numeric($pur_invoice->pur_order) && $pur_invoice->pur_order > 0){
	                			$_inv_po_ids[] = (int)$pur_invoice->pur_order;
	                		}
	                		if(!empty($pur_invoice->grn_id)){
	                			$_g_arr = json_decode($pur_invoice->grn_id, true);
	                			if(!is_array($_g_arr)) $_g_arr = explode(',', $pur_invoice->grn_id);
	                			$CI_po =& get_instance();
	                			foreach($_g_arr as $_g){
	                				$_g = (int)trim($_g);
	                				if($_g > 0){
	                					$CI_po->db->select('pr_order_id');
	                					$CI_po->db->where('id', $_g);
	                					$_gr = $CI_po->db->get(db_prefix().'goods_receipt')->row();
	                					if($_gr && $_gr->pr_order_id > 0 && !in_array((int)$_gr->pr_order_id, $_inv_po_ids)){
	                						$_inv_po_ids[] = (int)$_gr->pr_order_id;
	                					}
	                				}
	                			}
	                		}
	                	}
	                	?>
	                    <select name="pur_order[]" id="pur_order" class="selectpicker" multiple onchange="pur_order_change_multi(); return false;" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>" data-actions-box="true">
	                        <?php if(isset($pur_invoice)){ ?>
	                        <?php foreach($pur_orders as $ct){ ?>
	                        	<option value="<?php echo pur_html_entity_decode($ct['id']); ?>" <?php if(in_array((int)$ct['id'], $_inv_po_ids)){ echo 'selected'; } ?>><?php echo pur_html_entity_decode($ct['pur_order_number']); ?></option>
	                        <?php } ?>
	                        <?php } ?>
	                    </select>
	                </div>

	                <div class="col-md-6 form-group pad_right_0">
	                	<label for="grn_id"><?php echo _l('grn'); ?></label>
	                    <select name="grn_id[]" id="grn_id" class="selectpicker" multiple onchange="grn_change_multi(); return false;" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('select_grn'); ?>" data-actions-box="true">
	                    <?php
	                    // Pre-populate GRN options on edit
	                    if(isset($pur_invoice) && !empty($pur_invoice->grn_id)){
	                    	$_grn_ids_edit = json_decode($pur_invoice->grn_id, true);
	                    	if(!is_array($_grn_ids_edit)) $_grn_ids_edit = explode(',', $pur_invoice->grn_id);
	                    	$CI_grn =& get_instance();
	                    	foreach($_grn_ids_edit as $_gid_e){
	                    		$_gid_e = (int)trim($_gid_e);
	                    		if($_gid_e > 0){
	                    			$CI_grn->db->select('id, goods_receipt_code, pr_order_id');
	                    			$CI_grn->db->where('id', $_gid_e);
	                    			$_grn_e = $CI_grn->db->get(db_prefix().'goods_receipt')->row();
	                    			if($_grn_e){
	                    				$_po_label = '';
	                    				if($_grn_e->pr_order_id > 0){
	                    					$CI_grn->db->select('pur_order_number');
	                    					$CI_grn->db->where('id', $_grn_e->pr_order_id);
	                    					$_po_r = $CI_grn->db->get(db_prefix().'pur_orders')->row();
	                    					if($_po_r) $_po_label = ' ('.$_po_r->pur_order_number.')';
	                    				}
	                    				echo '<option value="'.$_grn_e->id.'" selected>'.$_grn_e->goods_receipt_code.$_po_label.'</option>';
	                    			}
	                    		}
	                    	}
	                    }
	                    ?>
	                    </select>
	                </div>

	                <div class="col-md-6 pad_right_0">
	                	<label for="invoice_date"><span class="text-danger">* </span><?php echo _l('invoice_date'); ?></label>
	                	<?php $invoice_date = ( isset($pur_invoice) ? _d($pur_invoice->invoice_date) : _d(date('Y-m-d')) );
	                	 echo render_date_input('invoice_date','',$invoice_date,array( 'required' => 'true')); ?>
	                </div>

	                <div class="col-md-6 pad_left_0">
	                	<label for="invoice_date"><?php echo _l('pur_due_date'); ?></label>
	                	<?php $duedate = ( isset($pur_invoice) ? _d($pur_invoice->duedate) : _d(date('Y-m-d')) );
	                	 echo render_date_input('duedate','',$duedate); ?>
	                </div>

	                <div id="recurring_div" class="<?php if(isset($pur_invoice) && $pur_invoice->pur_order != null){ echo 'hide';} ?>">

	                <div class="form-group col-md-12 pad_left_0 pad_right_0">
	                	<label for="recurring"><?php echo _l('invoice_add_edit_recurring'); ?></label>
	                    <select name="recurring" id="recurring" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
	                        <?php for($i = 0; $i <=12; $i++){ ?>
	                        	<?php
                              $selected = '';
                              if(isset($pur_invoice)){
                                
	                             if($pur_invoice->recurring == $i){
	                               $selected = 'selected';
	                             }
                              }
                              if($i == 0){
                               $reccuring_string =  _l('invoice_add_edit_recurring_no');
                              } else if($i == 1){
                               $reccuring_string = _l('invoice_add_edit_recurring_month',$i);
                              } else {
                               $reccuring_string = _l('invoice_add_edit_recurring_months',$i);
                              }
                              ?>

	                        	<option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $reccuring_string; ?></option>
	                        <?php } ?>
	                    </select>
	                </div>

	                <div id="cycles_wrapper" class="<?php if(!isset($pur_invoice) || (isset($pur_invoice) && $pur_invoice->recurring == 0)){echo ' hide';}?>">
	                     <div class="col-md-12 pad_left_0 pad_right_0">
	                        <?php $value = (isset($pur_invoice) ? $pur_invoice->cycles : 0); ?>
	                        <div class="form-group recurring-cycles">
	                          <label for="cycles"><?php echo _l('recurring_total_cycles'); ?>
	                            <?php if(isset($pur_invoice) && $pur_invoice->total_cycles > 0){
	                              echo '<small>' . _l('cycles_passed', $pur_invoice->total_cycles) . '</small>';
	                            }
	                            ?>
	                          </label>
	                          <div class="input-group">
	                            <input type="number" class="form-control"<?php if($value == 0){echo ' disabled'; } ?> name="cycles" id="cycles" value="<?php echo $value; ?>" <?php if(isset($pur_invoice) && $pur_invoice->total_cycles > 0){echo 'min="'.($pur_invoice->total_cycles).'"';} ?>>
	                            <div class="input-group-addon">
	                              <div class="checkbox">
	                                <input type="checkbox"<?php if($value == 0){echo ' checked';} ?> id="unlimited_cycles">
	                                <label for="unlimited_cycles"><?php echo _l('cycles_infinity'); ?></label>
	                              </div>
	                            </div>
	                          </div>
	                        </div>
	                     </div>
	                </div>

	             	</div>

	                
	            </div>

	            <div class="col-md-6">
	            	<div class="col-md-6 pad_left_0">
                     <?php
                        $currency_attr = array('data-show-subtext'=>true, 'required' => true);

                        $selected = '';
                        foreach($currencies as $currency){
                          if(isset($pur_invoice) && $pur_invoice->currency != 0){
                            if($currency['id'] == $pur_invoice->currency){
                              $selected = $currency['id'];
                            }
                          }else{
                           if($currency['isdefault'] == 1){
                             $selected = $currency['id'];
                           }
                          }
                        }
       
                        ?>
                        <label for="currency"><span class="text-danger">* </span><?php echo _l('invoice_add_edit_currency') ?></label>
                     <?php echo render_select('currency', $currencies, array('id','name','symbol'), '', $selected, $currency_attr); ?>
                  	</div>
	                <div class="col-md-6 form-group">
	                    <div id="inputTagsWrapper">
	                       <label for="tags" class="control-label"><i class="fa fa-tag" aria-hidden="true"></i> <?php echo _l('tags'); ?></label>
	                       <input type="text" class="tagsinput" id="tags" name="tags" value="<?php echo (isset($pur_invoice) ? prep_tags_input(get_tags_in($pur_invoice->id,'pur_invoice')) : ''); ?>" data-role="tagsinput">
	                    </div>
	                </div>
	                <div class="col-md-6 pad_left_0">
	                	<?php $transactionid = ( isset($pur_invoice) ? $pur_invoice->transactionid : '');
	                	echo render_input('transactionid','transaction_id',$transactionid); ?>
	                </div>
	                <div class="col-md-6 pad_right_0">
	                	<?php $transaction_date = ( isset($pur_invoice) ? $pur_invoice->transaction_date : '');
	                	echo render_date_input('transaction_date','transaction_date',$transaction_date); ?>
	                </div>


                    <div class="col-md-12  pad_left_0 pad_right_0">
                       <div class="form-group select-placeholder">
                          <label for="discount_type"
                              class="control-label"><?php echo _l('discount_type'); ?></label>
                          <select name="discount_type" class="selectpicker" data-width="100%"
                              data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                             
                              <option value="before_tax" <?php
                        if (isset($pur_invoice)) {
                            if ($pur_invoice->discount_type == 'before_tax') {
                                echo 'selected';
                            }
                        }else {
                          echo 'selected';
                        } ?>><?php echo _l('discount_type_before_tax'); ?></option>
                              <option value="after_tax" <?php if (isset($pur_invoice)) {
                            if ($pur_invoice->discount_type == 'after_tax' || $pur_invoice->discount_type == null) {
                                echo 'selected';
                            }
                        } ?>><?php echo _l('discount_type_after_tax'); ?></option>
                          </select>
                      </div>
                    </div>
                
	                
	                <div class="col-md-12 pad_left_0 pad_right_0">
				        <div class="attachments">
				          <div class="attachment">
				            <div class="mbot15">
				              <div class="form-group">
				                <label for="attachment" class="control-label"><?php echo _l('ticket_add_attachments'); ?> <span class="text-danger">*</span></label>
				                <div class="input-group">
				                  <input type="file" extension="<?php echo str_replace('.','',get_option('ticket_attachments_file_extensions')); ?>" filesize="<?php echo file_upload_max_size(); ?>" class="form-control" name="attachments[0]" accept="<?php echo get_ticket_form_accepted_mimes(); ?>" required>
				                  <span class="input-group-btn">
				                    <button class="btn btn-success add_more_attachments p8-half" data-max="10" type="button"><i class="fa fa-plus"></i></button>
				                  </span>
				                </div>
				              </div>
				            </div>
				          </div>
				        </div>
				    </div>
	            </div>

            </div>

			<?php $rel_id=( isset($pur_invoice) ? $pur_invoice->id : false); ?>
            <?php echo render_custom_fields( 'pur_invoice',$rel_id); ?>
        	
          </div>



          <div class="panel-body mtop10 invoice-item">

		        <div class="row">
		          <div class="col-md-4">
		            <?php $this->load->view('purchase/item_include/main_item_select'); ?>
		          </div>

				          <?php
				        $base_currency = get_base_currency();

		                $po_currency = $base_currency;
		                if(isset($pur_invoice) && $pur_invoice->currency != 0){
		                  $po_currency = pur_get_currency_by_id($pur_invoice->currency);
		                } 

		                $from_currency = (isset($pur_invoice) && $pur_invoice->from_currency != null) ? $pur_invoice->from_currency : $base_currency->id;
		                echo form_hidden('from_currency', $from_currency);

		              ?>
		          <div class="col-md-8 hide" id="currency_rate_div">
		            <div class="col-md-10 text-right">
		              
		              <p class="mtop10"><?php echo _l('currency_rate'); ?><span id="convert_str"><?php echo ' ('.$base_currency->name.' => '.$po_currency->name.'): ';  ?></span></p>
		            </div>
		            <div class="col-md-2 pull-right">
		              <?php $currency_rate = 1;
		                if(isset($pur_invoice) && $pur_invoice->currency != 0){
		                  $currency_rate = $pur_invoice->currency_rate;
		                }
		              echo render_input('currency_rate', '', $currency_rate, 'number', [], [], '', 'text-right'); 
		              ?>
		            </div>
		          </div>
		        </div> 
		        <div class="row">
		          <div class="col-md-12">
		            <div class="table-responsive s_table ">
		              <table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
		                <thead>
		                  <tr>
		                    <th></th>
		                    <th width="12%" align="left"><i class="fa fa-exclamation-circle" aria-hidden="true" data-toggle="tooltip" data-title="<?php echo _l('item_description_new_lines_notice'); ?>"></i> <?php echo _l('invoice_table_item_heading'); ?></th>
		                    <th width="12%" align="left"><?php echo _l('item_description'); ?></th>
		                    <th width="8%" align="right"><?php echo _l('unit_price'); ?><span class="th_currency"><?php echo '('.$po_currency->name.')'; ?></span></th>
		                    <th width="7%" align="right" class="qty"><?php echo _l('quantity'); ?></th>
		                    <th width="7%" align="right"><?php echo _l('received_qty'); ?></th>
		                    <th width="7%" align="right"><?php echo _l('pending_qty'); ?></th>
		                    <th width="10%" align="right"><?php echo _l('invoice_table_tax_heading'); ?></th>
		                    <th width="8%" align="right"><?php echo _l('tax_value'); ?><span class="th_currency"><?php echo '('.$po_currency->name.')'; ?></span></th>
		                    <th width="8%" align="right"><?php echo _l('pur_subtotal_after_tax'); ?><span class="th_currency"><?php echo '('.$po_currency->name.')'; ?></span></th>
		                    <th width="6%" align="right"><?php echo _l('discount').'(%)'; ?></th>
		                    <th width="8%" align="right"><?php echo _l('discount(money)'); ?><span class="th_currency"><?php echo '('.$po_currency->name.')'; ?></span></th>
		                    <th width="8%" align="right"><?php echo _l('total'); ?><span class="th_currency"><?php echo '('.$po_currency->name.')'; ?></span></th>
		                    <th align="center"><i class="fa fa-cog"></i></th>
		                  </tr>
		                </thead>
		                <tbody>
		                  <?php echo $pur_invoice_row_template; ?>
		                </tbody>
		              </table>
		            </div>
		          </div>
		         <div class="col-md-8 col-md-offset-4">
		          <table class="table text-right">
		            <tbody>
		              <tr id="subtotal">
		                <td><span class="bold"><?php echo _l('subtotal'); ?> :</span>
		                  <?php echo form_hidden('total_mn', ''); ?>
		                </td>
		                <td class="wh-subtotal">
		                </td>
		              </tr>
		              
		              <tr id="order_discount_percent">
		                <td>
		                  <div class="row">
		                    <div class="col-md-7">
		                      <span class="bold"><?php echo _l('pur_discount'); ?> <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="<?php echo _l('discount_percent_note'); ?>" ></i></span>
		                    </div>
		                    <div class="col-md-3">
		                      <?php $discount_total = isset($pur_invoice) ? $pur_invoice->discount_total : '';
		                      echo render_input('order_discount', '', $discount_total, 'number', ['onchange' => 'pur_calculate_total()', 'onblur' => 'pur_calculate_total()']); ?>
		                    </div>
		                     <div class="col-md-2">
		                        <select name="add_discount_type" id="add_discount_type" class="selectpicker" onchange="pur_calculate_total(); return false;" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
		                            <option value="percent">%</option>
		                            <option value="amount" selected><?php echo _l('amount'); ?></option>
		                        </select>
		                     </div>
		                  </div>
		                </td>
		                <td class="order_discount_value">

		                </td>
		              </tr>

		              <tr id="total_discount">
		                <td><span class="bold"><?php echo _l('total_discount'); ?> :</span>
		                  <?php echo form_hidden('dc_total', ''); ?>
		                </td>
		                <td class="wh-total_discount">
		                </td>
		              </tr>

		              <tr>
		                <td>
		                 <div class="row">
		                  <div class="col-md-9">
		                   <span class="bold"><?php echo _l('pur_shipping_fee'); ?></span>
		                 </div>
		                 <div class="col-md-3">
		                  <input type="number" onchange="pur_calculate_total()" data-toggle="tooltip" value="<?php if(isset($pur_invoice)){ echo $pur_invoice->shipping_fee; }else{ echo '0';} ?>" class="form-control pull-left text-right" name="shipping_fee">
		                </div>
		              </div>
		              </td>
		              <td class="shiping_fee">
		              </td>
		              </tr>
		              
		              <tr id="totalmoney">
		                <td><span class="bold"><?php echo _l('grand_total'); ?> :</span>
		                  <?php echo form_hidden('grand_total', ''); ?>
		                </td>
		                <td class="wh-total">
		                </td>
		              </tr>
		            </tbody>
		          </table>
		        </div>
		        <div id="removed-items"></div> 
		        </div>
		        </div>

		        <div class="row">
		          <div class="col-md-12 mtop15">
		             <div class="panel-body bottom-transaction">
		             	<div class="col-md-12 pad_left_0 pad_right_0">
	                	<?php $adminnote = ( isset($pur_invoice) ? $pur_invoice->adminnote : '');
		                	echo render_textarea('adminnote','adminnote',$adminnote, array('rows' => 7)) ?>
		                </div>

		                <div class="col-md-12 pad_left_0 pad_right_0">
		                	<?php $vendor_note = ( isset($pur_invoice) ? $pur_invoice->vendor_note : '');
		                	echo render_textarea('vendor_note','vendor_note',$vendor_note, array('rows' => 7)) ?>
		                </div>
		                <div class="col-md-12 pad_left_0 pad_right_0">
		                	<?php $terms = ( isset($pur_invoice) ? $pur_invoice->terms : '');
		                	echo render_textarea('terms','terms',$terms, array('rows' => 7)) ?>
		                </div>

		                <div class="btn-bottom-toolbar text-right">
                  
		                  <button type="button" class="btn-tr save_detail btn btn-info mleft10 transaction-submit">
		                  <?php echo _l('submit'); ?>
		                  </button>
		                </div>

		             </div>
		               <div class="btn-bottom-pusher"></div>
		          </div>
		        </div>
      	</div>

      	<?php echo form_close(); ?>

      	<!-- PR, PO & GRN Attachments -->
      	<div id="invoice-related-attachments">
      	<?php
      	$inv_po_id = isset($pur_invoice) ? $pur_invoice->pur_order : '';
      	if(is_numeric($inv_po_id) && $inv_po_id > 0){
      		$CI =& get_instance();
      		// PR Attachments
      		$CI->db->select('pur_request');
      		$CI->db->where('id', $inv_po_id);
      		$po_row = $CI->db->get(db_prefix().'pur_orders')->row();
      		if($po_row && is_numeric($po_row->pur_request) && $po_row->pur_request > 0){
      			$CI->db->where('rel_id', $po_row->pur_request);
      			$CI->db->where('rel_type', 'pur_request');
      			$pr_files = $CI->db->get(db_prefix().'files')->result_array();
      			if(count($pr_files) > 0){ ?>
      			<div class="panel_s mtop15"><div class="panel-body">
      				<h5 class="bold"><i class="fa fa-file-text-o"></i> <?php echo _l('pur_request'); ?> <?php echo _l('attachments'); ?></h5>
      				<?php foreach($pr_files as $prf){ ?>
      				<div class="mbot10 row">
      					<div class="col-md-8"><i class="<?php echo get_mime_class($prf['filetype']); ?>"></i> <a href="<?php echo site_url(PURCHASE_PATH.'pur_request/'.$prf['rel_id'].'/'.$prf['file_name']); ?>" target="_blank" download><?php echo $prf['file_name']; ?></a></div>
      					<div class="col-md-4"><small class="text-muted"><i class="fa fa-user"></i> <?php echo get_staff_full_name($prf['staffid']); ?> | <?php echo _d($prf['dateadded']); ?></small></div>
      				</div>
      				<?php } ?>
      			</div></div>
      			<?php }
      		}

      		// PO Attachments
      		$CI->db->where('rel_id', $inv_po_id);
      		$CI->db->where('rel_type', 'pur_order');
      		$po_files = $CI->db->get(db_prefix().'files')->result_array();
      		if(count($po_files) > 0){ ?>
      		<div class="panel_s mtop15"><div class="panel-body">
      			<h5 class="bold"><i class="fa fa-shopping-cart"></i> <?php echo _l('pur_order'); ?> <?php echo _l('attachments'); ?></h5>
      			<?php foreach($po_files as $pof){ ?>
      			<div class="mbot10 row">
      				<div class="col-md-8"><i class="<?php echo get_mime_class($pof['filetype']); ?>"></i> <a href="<?php echo site_url(PURCHASE_PATH.'pur_order/'.$pof['rel_id'].'/'.$pof['file_name']); ?>" target="_blank" download><?php echo $pof['file_name']; ?></a></div>
      				<div class="col-md-4"><small class="text-muted"><i class="fa fa-user"></i> <?php echo get_staff_full_name($pof['staffid']); ?> | <?php echo _d($pof['dateadded']); ?></small></div>
      			</div>
      			<?php } ?>
      		</div></div>
      		<?php }

      		// GRN Attachments (from GRNs linked to this invoice)
      		if(isset($pur_invoice) && isset($pur_invoice->grn_id) && $pur_invoice->grn_id != ''){
      			$grn_ids = json_decode($pur_invoice->grn_id, true);
      			if(!is_array($grn_ids)) $grn_ids = explode(',', $pur_invoice->grn_id);
      			$all_grn_files = [];
      			foreach($grn_ids as $gid){
      				$gid = trim($gid);
      				if(is_numeric($gid)){
      					$CI->db->where('rel_id', $gid);
      					$CI->db->where('rel_type', 'goods_receipt');
      					$gf = $CI->db->get(db_prefix().'files')->result_array();
      					$all_grn_files = array_merge($all_grn_files, $gf);
      				}
      			}
      			if(count($all_grn_files) > 0){ ?>
      			<div class="panel_s mtop15"><div class="panel-body">
      				<h5 class="bold"><i class="fa fa-truck"></i> <?php echo _l('goods_receipt'); ?> <?php echo _l('attachments'); ?></h5>
      				<?php foreach($all_grn_files as $grf){ ?>
      				<div class="mbot10 row">
      					<div class="col-md-8"><i class="<?php echo get_mime_class($grf['filetype']); ?>"></i> <a href="<?php echo site_url(WAREHOUSE_PATH.'goods_receipt/'.$grf['rel_id'].'/'.$grf['file_name']); ?>" target="_blank" download><?php echo $grf['file_name']; ?></a></div>
      					<div class="col-md-4"><small class="text-muted"><i class="fa fa-user"></i> <?php echo get_staff_full_name($grf['staffid']); ?> | <?php echo _d($grf['dateadded']); ?></small></div>
      				</div>
      				<?php } ?>
      			</div></div>
      			<?php }
      		}
      	} ?>
      	</div>

  	</div>
  </div>
</div>

<?php init_tail(); ?>
</body>
</html>
<?php require 'modules/purchase/assets/js/pur_invoice_js.php';?>
