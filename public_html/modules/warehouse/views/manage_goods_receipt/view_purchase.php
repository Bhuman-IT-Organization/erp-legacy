<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_hidden('_attachment_sale_id',$goods_receipt->id); ?>
<?php echo form_hidden('_attachment_sale_type','estimate'); ?>
<div class="col-md-12 no-padding">
   <div class="panel_s">
      <div class="panel-body">
         <?php if($goods_receipt->approval == 0){ ?>
           <div class="ribbon info"><span><?php echo _l('not_yet_approve'); ?></span></div>
       <?php }elseif($goods_receipt->approval == 1){ ?>
         <div class="ribbon success"><span><?php echo _l('approved'); ?></span></div>
       <?php }elseif($goods_receipt->approval == -1){ ?>  
         <div class="ribbon danger"><span><?php echo _l('reject'); ?></span></div>
       <?php } ?>
         <div class="horizontal-scrollable-tabs preview-tabs-top">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
               <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                  <li role="presentation" class="active">
                     <a href="#tab_estimate" aria-controls="tab_estimate" role="tab" data-toggle="tab">
                     <?php echo _l('stock_import'); ?>
                     </a>
                  </li>  

                  <li role="presentation">
                     <a href="#tab_tasks" onclick="init_rel_tasks_table(<?php echo new_html_entity_decode($goods_receipt->id); ?>,'stock_import'); return false;" aria-controls="tab_tasks" role="tab" data-toggle="tab">
                     <?php echo _l('tasks'); ?>
                     </a>
                  </li>

                  <li role="presentation">
                     <a href="#tab_grn_attachments" aria-controls="tab_grn_attachments" role="tab" data-toggle="tab">
                     <?php 
                     $CI_grn_att =& get_instance();
                     $CI_grn_att->db->where('rel_id', $goods_receipt->id);
                     $CI_grn_att->db->where('rel_type', 'goods_receipt');
                     $grn_att_files = $CI_grn_att->db->get(db_prefix().'files')->result_array();
                     echo _l('attachments') . ' (' . count($grn_att_files) . ')';
                     ?>
                     </a>
                  </li>

                  <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>" class="tab-separator toggle_view">
                     <a href="#" onclick="small_table_full_view(); return false;">
                     <i class="fa fa-expand"></i></a>
                  </li>
                  <?php hooks()->do_action('after_inventory_receipt_tab', $goods_receipt); ?>
               </ul>
            </div>
         </div>

         <div class="clearfix"></div>
         <div class="tab-content">
            <?php hooks()->do_action('after_inventory_receipt_tab_content', isset($goods_receipt) ? $goods_receipt : false); ?>
            <div role="tabpanel" class="tab-pane ptop10 active" id="tab_estimate">
                  <div class="row">
                    <div class="col-md-4">
                      <?php 
                      // Show key reference info in compact format like PO detail page
                      if (get_status_modules_wh('purchase') && ($goods_receipt->supplier_code != '') && ($goods_receipt->supplier_code != 0)){
                          echo '<p class="bold p_mar">'._l('vendor').': <a href="'.admin_url('purchase/vendor/'.$goods_receipt->supplier_code).'">'.wh_get_vendor_company_name($goods_receipt->supplier_code).'</a></p>';
                      } else {
                          echo '<p class="bold p_mar">'._l('supplier_name').': '.new_html_entity_decode($goods_receipt->supplier_name).'</p>';
                      }
                      
                      if(($goods_receipt->pr_order_id != '') && ($goods_receipt->pr_order_id != 0)){
                          echo '<p class="bold p_mar">'._l('reference_purchase_order').': <a href="'.admin_url('purchase/purchase_order/'.$goods_receipt->pr_order_id).'">'.get_pur_order_name($goods_receipt->pr_order_id).'</a></p>';
                          
                          // PR Number
                          $CI_v = &get_instance();
                          $CI_v->db->select('pur_request');
                          $CI_v->db->where('id', $goods_receipt->pr_order_id);
                          $po_data = $CI_v->db->get(db_prefix().'pur_orders')->row();
                          if($po_data && is_numeric($po_data->pur_request) && $po_data->pur_request > 0){
                              $CI_v->db->select('pur_rq_code');
                              $CI_v->db->where('id', $po_data->pur_request);
                              $pr_data = $CI_v->db->get(db_prefix().'pur_request')->row();
                              if($pr_data){
                                  echo '<p class="bold p_mar">'._l('purchase_request').': <a href="'.admin_url('purchase/view_pur_request/'.$po_data->pur_request).'">'.$pr_data->pur_rq_code.'</a></p>';
                              }
                          }
                      }

                      // Show Invoices linked to this GRN
                      $CI_v->db->select('id, invoice_number, grn_id');
                      $CI_v->db->where('is_deleted', 0);
                      $CI_v->db->where('pur_order', $goods_receipt->pr_order_id);
                      $linked_invoices = $CI_v->db->get(db_prefix().'pur_invoices')->result_array();
                      $inv_links = [];
                      foreach($linked_invoices as $linv){
                          $grn_ids = json_decode($linv['grn_id'], true);
                          if(!is_array($grn_ids)) $grn_ids = explode(',', $linv['grn_id']);
                          if(in_array($goods_receipt->id, array_map('trim', $grn_ids))){
                              $inv_links[] = '<a href="'.admin_url('purchase/purchase_invoice/'.$linv['id']).'">'.$linv['invoice_number'].'</a>';
                          }
                      }
                      if(!empty($inv_links)){
                          echo '<p class="bold p_mar">'._l('pur_invoice').': '.implode(', ', $inv_links).'</p>';
                      }
                      ?>
                    </div>
                    <div class="col-md-8">
                       <div class="pull-right _buttons">
                          <?php if(has_permission('wh_stock_import','','edit')){ ?>
                          <a href="<?php echo admin_url('warehouse/edit_purchase/'.$goods_receipt->id); ?>" class="btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo _l('view'); ?>" data-placement="bottom"><i class="fa fa-eye"></i></a>
                          <?php } ?>

                       </div>

                    </div>
                 </div>
                 
               <div id="estimate-preview">

          <div class="col-md-12 panel-padding">
            <table class="table border table-striped table-margintop" >
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
                    <td class="bold"><?php echo _l('Buyer'); ?></td>
                    <td><?php 
                        // Use addedfrom (creator) as fallback when buyer_id is NULL/empty
                        $display_staff_id = (!empty($goods_receipt->buyer_id) && $goods_receipt->buyer_id > 0) ? $goods_receipt->buyer_id : $goods_receipt->addedfrom;
                        if($display_staff_id > 0){
                            $CI = &get_instance();
                            $CI->db->select('firstname, lastname');
                            $CI->db->where('staffid', $display_staff_id);
                            $staff_row = $CI->db->get(db_prefix() . 'staff')->row();
                            echo $staff_row ? trim($staff_row->firstname . ' ' . $staff_row->lastname) : '';
                        }
                    ?></td>
                 </tr>
                <tr class="project-overview">
                    <td class="bold"><?php echo _l('stock_received_docket_code'); ?></td>
                    <td><?php echo new_html_entity_decode($goods_receipt->goods_receipt_code) ; ?></td>
                 </tr>
                <tr class="project-overview">
                    <td class="bold"><?php echo _l('note_'); ?></td>
                    <td><?php echo new_html_entity_decode($goods_receipt->description) ; ?></td>
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
        </div>
        <?php 
        $display_product_image = get_option('display_product_image_receipt_delivery_pdf');
        ?>
                  <div class="row">
                     <div class="col-md-12">
                        <div class="table-responsive">
                           <table class="table items items-preview estimate-items-preview" data-type="estimate">
                              <thead>
                                 <tr>
                                    <th align="center">#</th>
                                    <?php 
                                    // Check PO type for asset
                                    $_vp_po_type = '';
                                    if(isset($goods_receipt) && is_numeric($goods_receipt->pr_order_id) && $goods_receipt->pr_order_id > 0){
                                      $CI_vp =& get_instance();
                                      $CI_vp->db->select('type');
                                      $CI_vp->db->where('id', $goods_receipt->pr_order_id);
                                      $_vp_po_row = $CI_vp->db->get(db_prefix().'pur_orders')->row();
                                      if($_vp_po_row) $_vp_po_type = $_vp_po_row->type;
                                    }
                                    if($_vp_po_type == 'AS'){ ?>
                                    <th colspan="1"><?php echo _l('asset_id'); ?></th>
                                    <?php } ?>
                                    <?php if($display_product_image){ ?>
                                      <th  colspan="1"><?php echo _l('_images') ?></th>
                                    <?php } ?>
                                    <th  colspan="1"><?php echo _l('commodity_code') ?></th>
                                     <th colspan="1"><?php echo _l('warehouse_name') ?></th>
                                     <th  colspan="1"><?php echo _l('unit_name') ?></th>
                                     <th  colspan="1"><?php echo _l('wh_item_description') ?></th>
                                     <th  colspan="2" class="text-center"><?php echo _l('quantity') ?></th>
                                     <th align="right" colspan="1" class="hide"><?php echo _l('unit_price') ?></th>
                                     <th align="right" colspan="1"><?php echo _l('received_qty') ?></th>
                                     <th align="right" colspan="1"><?php echo _l('pending_qty') ?></th>
                                     <th align="right" colspan="1" class="hide"><?php echo _l('total_money') ?></th>
                                     <th align="right" colspan="1" class="hide"><?php echo _l('tax_money') ?></th>
                                     <th align="right" colspan="1"><?php echo _l('lot_number') ?></th>
                                     <th align="right" colspan="1"><?php echo _l('expiry_date') ?></th>
                                 </tr>
                              </thead>
                              <tbody class="ui-sortable">
                                
                              <?php 
                              // Pre-load PO detail items for position matching
                              $_vp_po_items = [];
                              if(isset($goods_receipt->pr_order_id) && is_numeric($goods_receipt->pr_order_id) && $goods_receipt->pr_order_id > 0){
                                $CI_vp_po = &get_instance();
                                $CI_vp_po->db->reset_query();
                                $CI_vp_po->db->where('pur_order', $goods_receipt->pr_order_id);
                                $CI_vp_po->db->order_by('id', 'asc');
                                $_vp_po_items = $CI_vp_po->db->get(db_prefix().'pur_order_detail')->result_array();
                              }

                              // Fallback lookup for partial receipts, where GRN line order does not
                              // follow PO line order. Keyed by item so we can never show another
                              // product's description. First non-empty description per item wins.
                              $_vp_po_desc_by_item = [];
                              foreach($_vp_po_items as $_vp_po_line_seed){
                                $_vp_seed_code = (string)$_vp_po_line_seed['item_code'];
                                if($_vp_seed_code !== '' && !isset($_vp_po_desc_by_item[$_vp_seed_code]) && !empty($_vp_po_line_seed['description'])){
                                  $_vp_po_desc_by_item[$_vp_seed_code] = $_vp_po_line_seed['description'];
                                }
                              }

                              foreach ($goods_receipt_detail as $receipt_key => $receipt_value) {

                                $receipt_key++;
                             $quantities = (isset($receipt_value) ? $receipt_value['quantities'] : '');
                             $unit_price = (isset($receipt_value) ? $receipt_value['unit_price'] : '');
                             $unit_price = (isset($receipt_value) ? $receipt_value['unit_price'] : '');
                             $goods_money = (isset($receipt_value) ? $receipt_value['goods_money'] : '');

                             $commodity_code = '';
                             $commodity_name = '';
                             $get_commodity_name = get_commodity_name($receipt_value['commodity_code']);
                             if($get_commodity_name){
                              $commodity_code = $get_commodity_name->commodity_code;
                              $commodity_name = $get_commodity_name->description;
                            }

                            // Line description, in order of authority:
                            //   1. the GRN line's own item_description
                            //   2. the matching PO line's description
                            // Deliberately NO fallback to the item master's long_description: that is
                            // generic catalogue text for whatever tblitems row commodity_code points at,
                            // which is unrelated to this line and shows up as garbage. Blank is correct
                            // when no description was entered.
                            $commodity_description = '';

                            $_vp_line_code = (string)$receipt_value['commodity_code'];

                            if(!empty($receipt_value['item_description'])){
                              $commodity_description = $receipt_value['item_description'];
                            } else {
                              $_vp_po_line = isset($_vp_po_items[$receipt_key - 1]) ? $_vp_po_items[$receipt_key - 1] : null;

                              // Match by position first (handles repeated item codes on one PO), then
                              // verify it is the same item so a partial receipt cannot pick up another
                              // line's description.
                              if($_vp_po_line
                                 && (string)$_vp_po_line['item_code'] === $_vp_line_code
                                 && !empty($_vp_po_line['description'])){
                                $commodity_description = $_vp_po_line['description'];
                              } elseif(isset($_vp_po_desc_by_item[$_vp_line_code])){
                                // Positions do not line up (partial receipt) - fall back to the same
                                // item's description from anywhere on the PO.
                                $commodity_description = $_vp_po_desc_by_item[$_vp_line_code];
                              }
                            }
                            
                             $unit_name ='';
                             if(is_numeric($receipt_value['unit_id'])){
                               $unit_name = (get_unit_type($receipt_value['unit_id']) != null && isset(get_unit_type($receipt_value['unit_id'])->unit_name)) ? get_unit_type($receipt_value['unit_id'])->unit_name : '';

                             }

                              $warehouse_code = get_warehouse_name($receipt_value['warehouse_id']) != null ? get_warehouse_name($receipt_value['warehouse_id'])->warehouse_name : '';
                              $tax_money =(isset($receipt_value) ? $receipt_value['tax_money'] : '');
                              $expiry_date =(isset($receipt_value) ? $receipt_value['expiry_date'] : '');
                              $lot_number =(isset($receipt_value) ? $receipt_value['lot_number'] : '');
                              $commodity_name = $receipt_value['commodity_name'];
                              if(new_strlen($commodity_name ?? '') == 0){
                                $commodity_name = wh_get_item_variatiom($receipt_value['commodity_code']);
                              }

                              if(new_strlen($receipt_value['serial_number'] ?? '') > 0){
                                $name_serial_number_tooltip = _l('wh_serial_number').': '.$receipt_value['serial_number'];
                              }else{
                                $name_serial_number_tooltip = '';
                              }

                              if($display_product_image){
                                $item_image = $this->warehouse_model->get_item_image($receipt_value['commodity_code']);
                              }
                            ?>
          
                              <tr data-toggle="tooltip" data-original-title="<?php echo new_html_entity_decode($name_serial_number_tooltip); ?>">
                              <td ><?php echo new_html_entity_decode($receipt_key) ?></td>
                              <?php if($_vp_po_type == 'AS'){ 
                                $_vp_asset_uid = '';
                                if(isset($receipt_value['asset_uid']) && $receipt_value['asset_uid'] != ''){
                                  $_vp_asset_uid = $receipt_value['asset_uid'];
                                } else {
                                  $CI_vp->db->select('asset_uid');
                                  $CI_vp->db->where('item_code', $receipt_value['commodity_code']);
                                  $CI_vp->db->where('pur_order', $goods_receipt->pr_order_id);
                                  $_vp_pd = $CI_vp->db->get(db_prefix().'pur_order_detail')->row();
                                  if($_vp_pd && $_vp_pd->asset_uid) $_vp_asset_uid = $_vp_pd->asset_uid;
                                }
                              ?>
                              <td><?php echo $_vp_asset_uid; ?></td>
                              <?php } ?>
                              <?php if($display_product_image){ ?>
                                <td ><?php echo new_html_entity_decode($item_image) ?></td>
                              <?php } ?>
                                  <td ><?php echo new_html_entity_decode($commodity_name) ?></td>
                                  <td ><?php echo new_html_entity_decode($warehouse_code) ?></td>
                                  <td ><?php echo new_html_entity_decode($unit_name) ?></td>
                                  <td ><?php echo new_html_entity_decode($commodity_description) ?></td>
                                  <td ></td>
                                  <td class="text-right" ><?php echo new_html_entity_decode($quantities) ?></td>
                                  <td class="text-right hide"><?php echo app_format_money((float)$unit_price, $currency) ?></td>
                                  <td class="text-right"><?php echo isset($receipt_value['received_qty']) ? new_html_entity_decode($receipt_value['received_qty']) : '0' ?></td>
                                  <td class="text-right"><?php echo isset($receipt_value['pending_qty']) ? new_html_entity_decode($receipt_value['pending_qty']) : '0' ?></td>
                                  <td class="text-right hide"><?php echo app_format_money((float)$goods_money, $currency) ?></td>
                                  <td class="text-right hide"><?php echo app_format_money((float)$tax_money, $currency) ?></td>
                                  <td class="text-right"><?php echo new_html_entity_decode($lot_number) ?></td>
                                  <td class="text-right"><?php echo _d($expiry_date) ?></td>
                                </tr>
                             <?php  } ?>
                              </tbody>
                           </table>
                        </div>
                     </div>

                     <div class="col-md-6 col-md-offset-6">
                      <table class="table text-right table-margintop hide">
                        <tbody>
                          <tr class="project-overview" id="subtotal">
                            <td class="td_style"><span class="bold"><?php echo _l('total_goods_money'); ?></span>
                            </td>
                            <?php $total_goods_money = (isset($goods_receipt) ? $goods_receipt->total_goods_money : '');?>
                            <td><?php echo app_format_money((float)$total_goods_money, $currency); ?></td>
                          </tr>

                          <tr class="project-overview">
                            <td class="td_style"><span class="bold"><?php echo _l('value_of_inventory'); ?></span>
                            </td>
                            <?php $value_of_inventory = (isset($goods_receipt) ? $goods_receipt->value_of_inventory : '');?>
                            <td><?php echo app_format_money((float)$value_of_inventory, $currency); ?></td>
                          </tr>
                          
                          <?php if(isset($goods_receipt) && $tax_data['html_currency'] != ''){
                            echo new_html_entity_decode($tax_data['html_currency']);
                          } ?>
                          
                          <tr class="project-overview">
                            <td class="td_style"><span class="bold"><?php echo _l('total_tax_money'); ?></span>
                            </td>
                            <?php $total_tax_money = (isset($goods_receipt) ? $goods_receipt->total_tax_money : '');?>
                            <td><?php echo app_format_money((float)$total_tax_money, $currency); ?></td>
                          </tr>

                          <tr class="project-overview">
                            <td class="td_style"><span class="bold"><?php echo _l('total_money'); ?></span>
                            </td>
                            <?php $total_money = (isset($goods_receipt) ? $goods_receipt->total_money : '');?>
                            <td><?php echo app_format_money((float)$total_money, $currency); ?></td>

                          </tr>
                        </tbody>
                      </table>
                    </div>


                     <div class="col-md-12">
                      <div class="project-overview-right">
    <?php if(count($list_approve_status) > 0){ ?>
      
     <div class="row">
       <div class="col-md-12 project-overview-expenses-finance">
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
                if($this->staff_model->get($val)){
                  $staff_name .= $this->staff_model->get($val)->full_name;
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
                if($this->staff_model->get($val)){
                  $staff_name .= $this->staff_model->get($val)->full_name;
                }
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
                         <a href="#" class="btn btn-success dropdown-toggle mright5" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo _l('approve'); ?><span class="caret"></span></a>
                         <ul class="dropdown-menu dropdown-menu-right menu-width-height">
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
               </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_tasks">
               <?php init_relation_tasks_table(array('data-new-rel-id'=>$goods_receipt->id,'data-new-rel-type'=>'stock_import')); ?>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_grn_attachments">
               <div class="col-md-12 mtop15">
                  <?php if(count($grn_att_files) > 0){ ?>
                     <?php foreach($grn_att_files as $gf){ 
                        $grn_file_url = site_url('modules/warehouse/uploadsgoods_receipt/'.$gf['rel_id'].'/'.$gf['file_name']);
                     ?>
                     <div class="mbot15 row inline-block full-width" data-attachment-id="<?php echo $gf['id']; ?>">
                        <div class="col-md-8">
                           <a href="javascript:void(0);" onclick="preview_grn_attachment(this); return false;" rel_id="<?php echo $gf['rel_id']; ?>" file_name="<?php echo $gf['file_name']; ?>" file_type="<?php echo $gf['filetype']; ?>" class="mbot10 mright5 btn btn-success pull-left" data-toggle="tooltip" title="<?php echo _l('preview_file'); ?>"><i class="fa fa-eye"></i></a>
                           <div class="pull-left"><i class="<?php echo get_mime_class($gf['filetype']); ?>"></i></div>
                           <a href="<?php echo $grn_file_url; ?>" target="_blank" download><?php echo $gf['file_name']; ?></a>
                           <br><small class="text-muted"><?php echo $gf['filetype']; ?></small>
                        </div>
                        <div class="col-md-2">
                           <small class="text-muted"><i class="fa fa-user"></i> <?php echo get_staff_full_name($gf['staffid']); ?></small>
                           <br><small class="text-muted"><i class="fa fa-calendar"></i> <?php echo _d($gf['dateadded']); ?></small>
                        </div>
                        <div class="col-md-2 text-right">
                           <?php if(($gf['staffid'] == get_staff_user_id() || is_admin()) && $goods_receipt->approval != 1){ ?>
                           <a href="<?php echo admin_url('warehouse/delete_grn_attachment/'.$gf['id'].'/'.$goods_receipt->id); ?>" class="text-danger _delete"><i class="fa fa-times"></i></a>
                           <?php } ?>
                        </div>
                     </div>
                     <?php } ?>
                  <?php } else { ?>
                     <p class="text-muted text-center"><?php echo _l('no_attachments_found'); ?></p>
                  <?php } ?>
               </div>
               <div id="grn_file_preview_data"></div>

               <?php
               // Show PR & PO attachments in GRN view
               if(isset($goods_receipt->pr_order_id) && is_numeric($goods_receipt->pr_order_id) && $goods_receipt->pr_order_id > 0){
                   $CI_att =& get_instance();
                   $CI_att->load->model('purchase/purchase_model');
                   
                   // Get PO info
                   $po_for_att = $CI_att->purchase_model->get_pur_order($goods_receipt->pr_order_id);
                   
                   // PO Attachments
                   $po_attachments = $CI_att->purchase_model->get_purchase_order_attachments($goods_receipt->pr_order_id);
                   if(!empty($po_attachments)){ ?>
                   <div class="col-md-12 mtop15">
                       <h5 class="bold"><i class="fa fa-paperclip"></i> <?php echo _l('purchase_order'); ?> <?php echo _l('attachments'); ?></h5>
                       <hr />
                       <?php foreach($po_attachments as $pof){
                           $po_file_url = site_url(PURCHASE_PATH.'pur_order/'.$pof['rel_id'].'/'.$pof['file_name']);
                       ?>
                       <div class="mbot10 row">
                           <div class="col-md-8">
                               <i class="<?php echo get_mime_class($pof['filetype']); ?>"></i>
                               <a href="<?php echo $po_file_url; ?>" target="_blank" download><?php echo $pof['file_name']; ?></a>
                           </div>
                           <div class="col-md-4"><small class="text-muted"><?php echo get_staff_full_name($pof['staffid']); ?> | <?php echo _d($pof['dateadded']); ?></small></div>
                       </div>
                       <?php } ?>
                   </div>
                   <?php }
                   
                   // PR Attachments
                   if($po_for_att && is_numeric($po_for_att->pur_request) && $po_for_att->pur_request > 0){
                       $pr_attachments = $CI_att->purchase_model->get_purchase_request_attachments($po_for_att->pur_request);
                       if(!empty($pr_attachments)){ ?>
                       <div class="col-md-12 mtop15">
                           <h5 class="bold"><i class="fa fa-paperclip"></i> <?php echo _l('pur_request'); ?> <?php echo _l('attachments'); ?></h5>
                           <hr />
                           <?php foreach($pr_attachments as $prf){
                               $pr_file_url = site_url(PURCHASE_PATH.'pur_request/'.$prf['rel_id'].'/'.$prf['file_name']);
                           ?>
                           <div class="mbot10 row">
                               <div class="col-md-8">
                                   <i class="<?php echo get_mime_class($prf['filetype']); ?>"></i>
                                   <a href="<?php echo $pr_file_url; ?>" target="_blank" download><?php echo $prf['file_name']; ?></a>
                               </div>
                               <div class="col-md-4"><small class="text-muted"><?php echo get_staff_full_name($prf['staffid']); ?> | <?php echo _d($prf['dateadded']); ?></small></div>
                           </div>
                           <?php } ?>
                       </div>
                       <?php }
                   }
               }
               ?>
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
           <button onclick="sign_request(<?php echo new_html_entity_decode($goods_receipt->id); ?>);"  autocomplete="off" class="btn btn-success sign_request_class"><?php echo _l('e_signature_sign'); ?></button>
          </div>


      </div>
   </div>
</div>

      </div>
   </div>
</div>

<?php require 'modules/warehouse/assets/js/view_purchase_js.php';?>
<?php hooks()->do_action('inventory_receipt_load_js'); ?>

</body>
</html>