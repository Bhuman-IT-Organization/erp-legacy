<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body">
                <?php $csrf = array(
                'name' => $this->security->get_csrf_token_name(),
                'hash' => $this->security->get_csrf_hash(),
              );
              ?>

              <input type="hidden" id="csrf_token_name" name="<?=$csrf['name'];?>" value="<?=$csrf['hash'];?>" />
                <?php if($pur_request->currency != 0){
                  
                  $base_currency = pur_get_currency_by_id($pur_request->currency);
                }else{
                  $base_currency = $base_currency;
                } ?>
                <?php if($pur_request->status == 1){ ?>
                    <div class="ribbon info"><span class="fontz9" ><?php echo _l('purchase_draft'); ?></span></div>
                <?php }elseif($pur_request->status == 2){ ?>
                  <div class="ribbon success"><span><?php echo _l('purchase_approved'); ?></span></div>
                <?php }elseif($pur_request->status == 3){ ?>  
                  <div class="ribbon danger"><span><?php echo _l('purchase_reject'); ?></span></div>
                <?php } ?>
                  <h4 class=""><?php echo _l($title); ?>
                  </h4>
                  <div class="row">
                    <div class="horizontal-scrollable-tabs preview-tabs-top">
                      <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                      <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                      <div class="horizontal-tabs">
                         <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                            <li role="presentation" class="<?php if($this->input->get('tab') != 'attachment' && $this->input->get('tab') != 'logic_notes'){ echo 'active'; } ?>">
                               <a href="#information" aria-controls="information" role="tab" data-toggle="tab">
                               <?php echo _l('pur_information'); ?>
                               </a>
                            </li>

                            <li role="presentation" class="<?php if($this->input->get('tab') == 'attachment'){ echo 'active'; } ?> ">
                               <a href="#attachment" aria-controls="attachment" role="tab" data-toggle="tab">
                               <?php echo _l('pur_attachment'); ?> (<?php echo count($pur_request_attachments); ?>)
                               </a>
                            </li>  

                            <?php if(isset($pur_request) && (float)$pur_request->total > 5000){ ?>
                            <li role="presentation" class="<?php if($this->input->get('tab') == 'vendor_recommendations'){ echo 'active'; } ?>">
                               <a href="#vendor_recommendations" aria-controls="vendor_recommendations" role="tab" data-toggle="tab">
                               <i class="fa fa-file-text"></i> <?php echo _l('logic_notes'); ?>
                               <?php 
                               $CI_vr =& get_instance();
                               $CI_vr->db->where('pur_request_id', $pur_request->id);
                               $vr_count = $CI_vr->db->count_all_results(db_prefix().'pur_vendor_recommendations');
                               if($vr_count > 0) echo '('.$vr_count.')';
                               ?>
                               </a>
                            </li>
                            <?php } ?>

                            
                            <?php $quotations = get_quotations_by_pur_request($pur_request->id); ?>
                            <li role="presentation" class="">
                               <a href="#compare_quotes" aria-controls="compare_quotes" role="tab" data-toggle="tab">
                               <?php echo _l('compare_quotes').'('.count($quotations).')'; ?>
                               </a>
                            </li> 
                            
                         </ul>
                      </div>
                   </div>
                   <div class="tab-content">
                    <div role="tabpanel" class="tab-pane ptop10 <?php if($this->input->get('tab') != 'attachment' && $this->input->get('tab') != 'logic_notes'){ echo 'active'; } ?>" id="information">

                    <div class="row">
                  <div class="col-md-12">
                    <p class="bold col-md-9 p_style"><?php echo _l('information'); ?></p>
                    <div class="col-md-3 pull-right">
                        <div class="task-info task-status task-info-status pull-right">
                            <h5>
                               <i class="fa fa-<?php if($pur_request->status == 2){echo 'star';} else if($pur_request->status == 1){echo 'star-o';} else {echo 'star-half-o';} ?> pull-left task-info-icon fa-fw fa-lg mtop10"></i><?php echo _l('task_status'); ?>:
                               <?php if(has_permission('purchase_request_change_approve_status', '', 'edit')) { ?>
                               <span class="task-single-menu task-menu-status">
                                  <span class="trigger pointer manual-popover text-has-action">
                                  <?php echo pur_format_approve_status($pur_request->status,true); ?>
                                  </span>
                                  <span class="content-menu hide">
                                     <ul>
                                        <?php
                                           for($pur_status = 1; $pur_status <= 3;  $pur_status++){ ?>
                                        <?php if($pur_request->status != $pur_status){ ?>
                                        <li>
                                           <a href="#" onclick="purchase_request_mark_as(<?php echo $pur_status; ?>,<?php echo $pur_request->id; ?>); return false;">
                                           <?php echo _l('purchase_request_mark_as', get_status_approve_str($pur_status)); ?>
                                           </a>
                                        </li>
                                        <?php } ?>
                                        <?php } ?>
                                     </ul>
                                  </span>
                               </span>
                               <?php } else { ?>
                               <?php echo pur_format_approve_status($pur_request->status,true); ?>
                               <?php } ?>
                            </h5>
                         </div>
                    </div>
                  <div class=" col-md-12">
                    <hr class="hr_style" />
                  </div>
                  </div></div>

                  <div class=" col-md-12">
                      <table class="table border table-striped martop0">
                    <tbody>
                       <tr class="project-overview">
                          <td class="bold" width="30%"><?php echo _l('pur_rq_code'); ?></td>
                          <td><?php echo pur_html_entity_decode($pur_request->pur_rq_code); ?></td>
                       </tr>
                       <tr class="project-overview">
                          <td class="bold"><?php echo _l('pur_rq_name'); ?></td>
                          <td><?php echo _l($pur_request->pur_rq_name); ?></td>
                       </tr>
                       <tr class="project-overview">
                          <td class="bold"><?php echo _l('purchase_requestor'); ?></td>
                          <td><?php 
                          if(!empty($pur_request->requester)){
                            $_data = '<a href="' . admin_url('staff/profile/' . $pur_request->requester) . '">' . staff_profile_image($pur_request->requester, [
                'staff-profile-image-small',
                ]) . '</a>';
            $_data .= ' <a href="' . admin_url('staff/profile/' . $pur_request->requester) . '">' . get_staff_full_name($pur_request->requester) . '</a>';
          } else {
            $_data = '<span class="text-muted">-</span>';
          }
            echo pur_html_entity_decode($_data);
            ?></td>
                       </tr>
                       
                       <tr class="project-overview">
                          <td class="bold"><?php echo _l('date_request'); ?></td>
                          <td><?php echo _d(date('Y-m-d', strtotime($pur_request->request_date))); ?></td>
                       </tr>
                       <?php $CI =& get_instance(); ?>
                       <?php if(isset($pur_request->cost_center) && $pur_request->cost_center > 0){ ?>
                       <tr class="project-overview">
                          <td class="bold"><?php echo _l('cost_center'); ?></td>
                          <td><?php 
                            $CI->db->where('id', $pur_request->cost_center);
                            $cc = $CI->db->get(db_prefix().'cost_centers')->row();
                            echo $cc ? htmlspecialchars($cc->name) : '-';
                          ?></td>
                       </tr>
                       <?php } ?>
                       <?php if(isset($pur_request->ledger_account) && $pur_request->ledger_account > 0){ ?>
                       <tr class="project-overview">
                          <td class="bold"><?php echo _l('ledger_account'); ?></td>
                          <td><?php 
                            $CI->db->where('id', $pur_request->ledger_account);
                            $acc = $CI->db->get(db_prefix().'acc_accounts')->row();
                            echo $acc ? htmlspecialchars($acc->number . ' - ' . $acc->name) : '-';
                          ?></td>
                       </tr>
                       <?php } ?>
                       <tr>
                        <td class="bold"><?php echo _l('pdf'); ?></td>
                        <td><div class="btn-group">
                           <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-file-pdf"></i><?php if(is_mobile()){echo ' PDF';} ?> <span class="caret"></span></a>
                           <ul class="dropdown-menu dropdown-menu-right">
                              <li class="hidden-xs"><a href="<?php echo admin_url('purchase/pur_request_pdf/'.$pur_request->id.'?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
                              <li class="hidden-xs"><a href="<?php echo admin_url('purchase/pur_request_pdf/'.$pur_request->id.'?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
                              <li><a href="<?php echo admin_url('purchase/pur_request_pdf/'.$pur_request->id); ?>"><?php echo _l('download'); ?></a></li>
                           </ul>
                           </div>
                               
                              
                        </td>
                      </tr>
                      <tr class="project-overview">
                          <td class="bold"><?php echo _l('public_link'); ?></td>
                          <td>
                            <div class="pull-right _buttons mright5">
                              <a href="javascript:void(0)" onclick="copy_public_link(<?php echo pur_html_entity_decode($pur_request->id); ?>); return false;" class="btn btn-warning btn-with-tooltip" data-toggle="tooltip" title="<?php echo _l('copy_public_link'); ?>" data-placement="bottom"><i class="fa fa-clone "></i></a>
                           </div>
                            <div class="col-md-9">
                              <?php if($pur_request->hash != '' && $pur_request->hash != null){
                               echo render_input('link_public','', site_url('purchase/vendors_portal/pur_request/'.$pur_request->id.'/'.$pur_request->hash)); 
                              }else{
                                  echo render_input('link_public','', ''); 
                              } ?>
                           </div>
                           </td>
                       </tr>
                       <tr class="project-overview">
                          <td class="bold"><?php echo _l('rq_description'); ?></td>
                          <td><?php echo pur_html_entity_decode($pur_request->rq_description); ?></td>
                       </tr>

                    </tbody>
                </table>
              </div>

                  

                  
                  
                  <div class="col-md-12">
                    <p class=" p_style"><?php echo _l('pur_detail'); ?></p>
                    <hr class="hr_style" />
                    
                    <div class="table-responsive">
                           <table class="table items items-preview estimate-items-preview" data-type="estimate">
                              <thead>
                                 <tr>
                               
                                  <?php if(isset($pur_request) && $pur_request->type == 'AS'){ ?>
                                  <th width="10%" align="left"><?php echo _l('asset_id'); ?></th>
                                  <?php } ?>
                                  <th width="20%" align="left"><?php echo _l('debit_note_table_item_heading'); ?></th>
                                  <th width="15%" align="left"><?php echo _l('description'); ?></th>
                                  <th width="8%" align="right" class="qty"><?php echo _l('purchase_quantity'); ?></th>
                                  <th width="7%" align="right"><?php echo _l('unit'); ?></th>
                                  <th width="10%" align="right"><?php echo _l('unit_price'); ?></th>
                                  
                                  <th width="10%" align="right"><?php echo _l('subtotal_before_tax'); ?></th>
                                  <th width="15%" align="right"><?php echo _l('debit_note_table_tax_heading'); ?></th>
                                  <th width="10%" align="right"><?php echo _l('tax_value'); ?></th>
                                  <th width="10%" align="right"><?php echo _l('debit_note_total'); ?></th>
                                 </tr>
                              </thead>
                              <tbody class="ui-sortable">

                                 <?php $_subtotal = 0;
                                 $_total = 0;
                                 if(count($pur_request_detail) > 0){
                                    $count = 1;
                                    $t_mn = 0;
                                 foreach($pur_request_detail as $es) { 
                                    $_subtotal += $es['into_money'];
                                    $_total += $es['total'];
                                  ?>
                                 <tr nobr="true" class="sortable">
                                    
                                    <?php if(isset($pur_request) && $pur_request->type == 'AS'){ ?>
                                    <td align="left"><?php echo isset($es['asset_uid']) ? $es['asset_uid'] : ''; ?></td>
                                    <?php } ?>
                                    <td class="description" align="left;"><span><strong><?php 
                                    if(isset($pur_request) && $pur_request->type == 'AS'){
                                       // Asset type - get name from asset creation table or item_text
                                       if(!empty($es['item_text'])){
                                          echo pur_html_entity_decode($es['item_text']);
                                       } else {
                                          $CI_pr =& get_instance();
                                          $CI_pr->db->select('asset_title, descriptions');
                                          $CI_pr->db->where('id', $es['item_code']);
                                          $_ac_item = $CI_pr->db->get(db_prefix().'fe_asset_creation')->row();
                                          if($_ac_item){
                                             echo pur_html_entity_decode($_ac_item->asset_title . ($_ac_item->descriptions != '' ? ' - '.$_ac_item->descriptions : ''));
                                          } else {
                                             echo pur_html_entity_decode($es['item_text']);
                                          }
                                       }
                                    } else {
                                       $item = get_item_hp($es['item_code']); 
                                       if(isset($item) && isset($item->commodity_code) && isset($item->description)){
                                          echo pur_html_entity_decode($item->commodity_code.' - '.$item->description);
                                       }else{
                                          echo pur_html_entity_decode($es['item_text']);
                                       }
                                    }
                                    ?></strong></td>
                                    <?php
                                       $unit_name = pur_get_unit_name($es['unit_id']);
                                       // Fallback: if unit_id is 0 or empty, get from item
                                       if(empty($unit_name) && is_numeric($es['item_code']) && $es['item_code'] > 0){
                                          $CI_unit =& get_instance();
                                          $CI_unit->db->select('unit_id');
                                          $CI_unit->db->where('id', $es['item_code']);
                                          $_item_unit = $CI_unit->db->get(db_prefix().'items')->row();
                                          if($_item_unit && $_item_unit->unit_id > 0){
                                             $unit_name = pur_get_unit_name($_item_unit->unit_id);
                                          }
                                       }
                                    ?>
                                    <td align="left"><?php echo isset($es['description']) ? pur_html_entity_decode($es['description']) : ''; ?></td>
                                    <td align="right"><?php echo pur_html_entity_decode($es['quantity']); ?></td>
                                    <td align="right"><?php echo pur_html_entity_decode($unit_name); ?></td>
                                    <td align="right"><?php echo app_format_money($es['unit_price'],$base_currency->name); ?></td>
                                    <td align="right"><?php echo app_format_money($es['into_money'],$base_currency->name); ?></td>
                                    <td align="right"><?php 
                                    if($es['tax_name'] != ''){
                                      echo pur_html_entity_decode($es['tax_name']); 
                                    }else{
                                      $this->load->model('purchase/purchase_model');
                                      if($es['tax'] != ''){
                                        $tax_arr =  $es['tax'] != '' ? explode('|', $es['tax'] ?? '') : [];
                                        $tax_str = '';
                                        if(count($tax_arr) > 0){
                                          foreach($tax_arr as $key => $tax_id){
                                            if(($key + 1) < count($tax_arr) ){
                                              $tax_str .= $this->purchase_model->get_tax_name($tax_id).'|';
                                            }else{
                                              $tax_str .= $this->purchase_model->get_tax_name($tax_id);
                                            }
                                          }
                                        }

                                        echo pur_html_entity_decode($tax_str); 
                                      }
                                    }
                                    ?></td>
                                    <td align="right"><?php echo app_format_money($es['tax_value'], $base_currency->name); ?></td>
                                
                                    <td class="amount" align="right"><?php echo app_format_money($es['total'],$base_currency->name); ?></td>
                                 </tr>
                              <?php 
                              
                              } } ?>
                              </tbody>
                           </table>
                        </div>


                  </div>
                   <div class="col-md-6 col-md-offset-6">
                     <table class="table text-right mbot0">
                       <tbody>
                          <tr id="subtotal">
                             <td class="td_style"><span class="bold"><?php echo _l('subtotal'); ?></span>
                             </td>
                             <td width="65%" id="total_td">
                              
                               <?php echo app_format_money($_subtotal, $base_currency->name); ?>
                             </td>
                          </tr>
                        </tbody>
                      </table>

                      <table class="table text-right">
                       <tbody id="tax_area_body">
                          <?php if(isset($pur_request)){ 
                            echo pur_html_entity_decode($taxes_data['html']);
                            ?>
                          <?php } ?>
                       </tbody>
                      </table>

                      <table class="table text-right">
                       <tbody id="tax_area_body">
                          <tr id="total">
                             <td class="td_style"><span class="bold"><?php echo _l('total'); ?></span>
                             </td>
                             <td width="65%" id="total_td">
                               <?php echo app_format_money($_total, $base_currency->name); ?>
                             </td>
                          </tr>
                       </tbody>
                      </table>

                  </div>
                  <?php echo form_hidden('request_detail'); ?>

                  <div class=" col-md-12">
                     <?php if(count($list_approve_status) > 0 ){ ?>
                    <p class=" p_style"><?php echo _l('pur_approval_infor'); ?></p>
                    <hr class="hr_style" />
                      <div class="project-overview-right">
                       
                          
                         <div class="row">
                           <div class="col-md-12 project-overview-expenses-finance">
                            <?php 
                              $this->load->model('staff_model');
                              $enter_charge_code = 0;
                            foreach ($list_approve_status as $value) {
                              $value['staffid'] = explode(', ',$value['staffid'] ?? '');
                              if($value['action'] == 'sign'){
                             ?>
                             <div class="col-md-3 apr_div">
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
                                    $staff_name .= isset($this->staff_model->get($val)->full_name) ? $this->staff_model->get($val)->full_name : '';
                                    $staff_position = pur_get_job_position($val);
                                  }
                                  echo pur_html_entity_decode($staff_name); 
                                  ?></p>
                                  <?php 
                                 
                                 if(isset($staff_position) && $staff_position != ''){
                                     echo '<span>'.$staff_position.'</span><br>';
                                 }
                                 ?>
                                 <?php if($value['approve'] == 2){ 
                                  ?>
                           
                                  <?php if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.png')){ ?>
                                    <img src="<?php echo site_url(PURCHASE_PATH.'pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.png'); ?>" class="img_style">
                                   <?php }else if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.jpg')){ ?>
                                       <img src="<?php echo site_url(PURCHASE_PATH.'pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.jpg'); ?>" class="img_style">
                                    <?php } ?>

                                   <br><br>
                                 <p class="bold text-center text-success"><?php echo _l('signed').' '._dt($value['date']); ?></p>
                                 <?php } ?> 
                                    
                            </div>
                            <?php }else{ ?>
                            <div class="col-md-3 apr_div">
                                 <p class="text-uppercase text-muted no-mtop bold">
                                  <?php
                                  $staff_name = '';
                                  foreach ($value['staffid'] as $key => $val) {
                                    if($staff_name != '')
                                    {
                                      $staff_name .= ' or ';
                                    }
                                    $staff_name .= isset($this->staff_model->get($val)->full_name) ? $this->staff_model->get($val)->full_name : '';
                                    $staff_position = pur_get_job_position($val);
                                  }
                                  echo pur_html_entity_decode($staff_name); 
                                  ?></p>

                                  <?php 
                             
                                 if(isset($staff_position) && $staff_position != ''){
                                     echo '<span>'.$staff_position.'</span><br>';
                                 }
                                 ?>
                                 <?php if($value['approve'] == 2){ 
                                  ?>
                                  <img src="<?php echo site_url(PURCHASE_PATH.'approval/approved.png'); ?>" class="img_style">

                                  <?php if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.png')){ ?>
                                   <br> <img src="<?php echo site_url(PURCHASE_PATH.'pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.png'); ?>" class="img_style">
                                    <?php }else if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.jpg')){ ?>
                                     <br>  <img src="<?php echo site_url(PURCHASE_PATH.'pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.jpg'); ?>" class="img_style">
                                    <?php } ?>

                                 <?php }elseif($value['approve'] == 3){ ?>
                                    <img src="<?php echo site_url(PURCHASE_PATH.'approval/rejected.png'); ?>" class="img_style">

                                    <?php if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.png')){ ?>
                                    <br><img src="<?php echo site_url(PURCHASE_PATH.'pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.png'); ?>" class="img_style">
                                    <?php }else if(file_exists(PURCHASE_MODULE_UPLOAD_FOLDER.'/pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.jpg')){ ?>
                                       <br><img src="<?php echo site_url(PURCHASE_PATH.'pur_request/signature/'.$pur_request->id.'/signature_'.$value['id'].'.jpg'); ?>" class="img_style">
                                    <?php } ?>
                                <?php } ?> 
                                <br><br>
                                <p><?php echo pur_html_entity_decode($value['note']) ?></p>  
                                <p class="bold text-center text-<?php if($value['approve'] == 2){ echo 'success'; }elseif($value['approve'] == 3){ echo 'danger'; } ?>"><?php echo _dt($value['date']); ?></p> 
                            </div>
                            <?php }
                            } ?>
                           </div>
                        </div>
                        
                        
                        </div>
                        <?php } ?>
                        <div class="pull-right">
                            <?php 
                            $has_attachments = (isset($pur_request_attachments) && count($pur_request_attachments) > 0);
                            if($check_appr && $check_appr != false && empty($pur_request->is_deleted)){

                            // Draft, rejected or sent-with-no-approvers may be sent.
                            // Approved and cancelled never show the button again.
                            $can_send_for_approval = pur_can_send_for_approval(
                                $pur_request->status,
                                $check_approve_status,
                                isset($appr_setting) ? $appr_setting : null,
                                isset($list_approve_status) ? $list_approve_status : [],
                                [1, 3, 5]
                            );

                            // Only the person who raised the request, or an admin, may
                            // resubmit it after a rejection.
                            if($can_send_for_approval && (int) $pur_request->status === 3
                                && !($pur_request->requester == get_staff_user_id() || is_admin())){
                                $can_send_for_approval = false;
                            }

                            if($can_send_for_approval){ ?>
                        <a data-toggle="tooltip" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-success lead-top-btn lead-view <?php if(!$has_attachments){ echo 'disabled'; } ?>" data-placement="top" href="#" <?php if(!$has_attachments){ echo 'title="'._l('please_add_attachment_first').'"'; } ?> onclick="<?php if($has_attachments){ ?>send_request_approve(<?php echo pur_html_entity_decode($pur_request->id); ?>);<?php } else { ?>alert('<?php echo _l('please_add_attachment_first'); ?>');<?php } ?> return false;"><?php echo _l('send_request_approve_pur'); ?></a>
                      <?php } }
                        if(isset($check_approve_status['staffid'])){
                            ?>
                            <?php 
                        if(empty($pur_request->is_deleted) && in_array(get_staff_user_id(), $check_approve_status['staffid']) && !in_array(get_staff_user_id(), $get_staff_sign) && in_array($pur_request->status, [1, 5])){ ?>
                            <div class="btn-group" >
                                   <a href="#" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo _l('approve'); ?><span class="caret"></span></a>
                                   <ul class="dropdown-menu dropdown-menu-right ul_style">
                                    <li>
                                      <div class="col-md-12">
                                        <?php echo render_textarea('reason', 'reason'); ?>
                                      </div>

                                      <?php if(get_option('allow_upload_esign_for_approve_type') == 1){ ?>
                                          <div class="col-md-12 mbot15">
                                             <?php echo form_open_multipart(admin_url('purchase/sign_attachment'),array('id'=>'sign_attachment-form')); ?>

                                                <label for="sign_attachment"><?php echo _l('e_sign'); ?></label>
                                                <input type="file" id="sign_attachment_file" accept=".png, .jpg" name="sign_attachment" class="form-control">

                                                <?php echo form_hidden('approve_rel_id', $pur_request->id) ?>
                                                <?php echo form_hidden('approve_rel_type', 'pur_request') ?>

                                             <?php echo form_close(); ?>   
                                          </div> 
                                       <?php } ?>

                                    </li>
                                      <li>
                                        <div class="row text-right col-md-12 pad_right_0 mbot15">
                                          <a href="#" data-loading-text="<?php echo _l('wait_text'); ?>" onclick="approve_request(<?php echo pur_html_entity_decode($pur_request->id); ?>); return false;" class="btn btn-success mright15"><?php echo _l('approve'); ?></a>
                                         <a href="#" data-loading-text="<?php echo _l('wait_text'); ?>" onclick="deny_request(<?php echo pur_html_entity_decode($pur_request->id); ?>); return false;" class="btn btn-warning"><?php echo _l('deny'); ?></a></div>
                                      </li>
                                   </ul>
                                </div>
                          <?php }
                            ?>
                            
                          <?php
                           if(empty($pur_request->is_deleted) && in_array(get_staff_user_id(), $check_approve_status['staffid']) && in_array(get_staff_user_id(), $get_staff_sign) && in_array($pur_request->status, [1, 5])){ ?>
                            <button onclick="accept_action();" class="btn btn-success pull-right action-button"><?php echo _l('e_signature_sign'); ?></button>
                          <?php }
                            ?>
                            <?php 
                             }
                            ?>
                          </div>
                  
                  </div>

                </div>

                <div role="tabpanel" class="tab-pane  <?php if($this->input->get('tab') == 'attachment'){ echo 'active'; } ?>" id="attachment">
                   <?php if($pur_request->status != 2){ ?>
                   <form id="partograph-attachments-upload" enctype="multipart/form-data">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="pur_file_input" class="control-label"><?php echo _l('pur_file'); ?> <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="pur_file_input" class="form-control">
                      </div>
                   </div>
                   <div class="col-md-12">
                       <button id="obgy_btn2" type="submit" class="btn btn-info pull-right"><?php echo _l('submit'); ?></button>
                   </div>
                    </form>
                   <?php } ?>
                   <div class="col-md-12" id="purrequest_pv_file">
                                        <?php
                                            $file_html = '';
                                            if(count($pur_request_attachments) > 0){
                                                $file_html .= '<hr />';
                                                foreach ($pur_request_attachments as $f) {
                                                    $href_url = site_url(PURCHASE_PATH.'pur_request/'.$f['rel_id'].'/'.$f['file_name']).'" download';
                                                                    if(!empty($f['external'])){
                                                                      $href_url = $f['external_link'];
                                                                    }
                                                   $file_html .= '<div class="mbot15 row inline-block full-width" data-attachment-id="'. $f['id'].'">
                                                  <div class="col-md-6">
                                                     <a name="preview-purorder-btn" onclick="preview_purrequest_btn(this); return false;" rel_id = "'. $f['rel_id']. '" id = "'.$f['id'].'" href="Javascript:void(0);" class="mbot10 mright5 btn btn-success pull-left" data-toggle="tooltip" title data-original-title="'. _l('preview_file').'"><i class="fa fa-eye"></i></a>
                                                     <div class="pull-left"><i class="'. get_mime_class($f['filetype']).'"></i></div>
                                                     <a href=" '. $href_url.'" target="_blank" download>'.$f['file_name'].'</a>
                                                     <br />
                                                     <small class="text-muted">'.$f['filetype'].'</small>
                                                  </div>
                                                  <div class="col-md-4">
                                                     <small class="text-muted"><i class="fa fa-user"></i> '.get_staff_full_name($f['staffid']).'</small>
                                                     <br /><small class="text-muted"><i class="fa fa-calendar"></i> '._d($f['dateadded']).'</small>
                                                  </div>
                                                  <div class="col-md-2 text-right">';
                                                    if(($f['staffid'] == get_staff_user_id() || is_admin()) && $pur_request->status != 2){
                                                    $file_html .= '<a href="#" class="text-danger" onclick="delete_purrequest_attachment('. $f['id'].'); return false;" data-toggle="tooltip" title="'._l('delete').'"><i class="fa fa-times"></i></a>';
                                                    } 
                                                   $file_html .= '</div></div>';
                                                }
                                                
                                                echo pur_html_entity_decode($file_html);
                                            } else {
                                                echo '<p class="text-muted text-center mtop15">'._l('no_attachments_found').'</p>';
                                            }
                                         ?>
                                      </div>

                   <div id="purrequest_file_data"></div>
                </div>

                <?php if(isset($pur_request) && (float)$pur_request->total > 5000){ ?>
                <div role="tabpanel" class="tab-pane ptop10 <?php if($this->input->get('tab') == 'vendor_recommendations'){ echo 'active'; } ?>" id="vendor_recommendations">
                   <div class="col-md-12">
                      <div class="alert alert-info">
                         <i class="fa fa-info-circle"></i> Total value exceeds AED 5,000. Logic Notes with quotation documents are required.
                      </div>
                      
                      <!-- Add Recommendation Form -->
                      <?php if($pur_request->status != 2){ ?>
                      <form id="vendor-recommendation-form" enctype="multipart/form-data">
                         <div class="row">
                            <div class="col-md-5">
                               <div class="form-group">
                                  <label>Logic Notes <span class="text-danger">*</span></label>
                                  <input type="file" name="vr_file" class="form-control" required>
                               </div>
                            </div>
                            <div class="col-md-5">
                               <div class="form-group">
                                  <label>Remarks</label>
                                  <input type="text" name="vr_remarks" class="form-control" placeholder="Optional remarks">
                               </div>
                            </div>
                            <div class="col-md-2">
                               <div class="form-group">
                                  <label>&nbsp;</label>
                                  <button type="submit" class="btn btn-info btn-block"><i class="fa fa-plus"></i> Add</button>
                               </div>
                            </div>
                         </div>
                      </form>
                      <?php } ?>

                      <!-- List Recommendations -->
                      <div class="table-responsive mtop15" id="vr_list">
                         <table class="table table-bordered table-striped">
                            <thead>
                               <tr>
                                  <th>#</th>
                                  <th>File</th>
                                  <th>Remarks</th>
                                  <th>Added By</th>
                                  <th>Date</th>
                                  <th>Actions</th>
                               </tr>
                            </thead>
                            <tbody>
                               <?php
                               $CI_vr2 =& get_instance();
                               $CI_vr2->db->where('pur_request_id', $pur_request->id);
                               $CI_vr2->db->order_by('id', 'asc');
                               $vendor_recs = $CI_vr2->db->get(db_prefix().'pur_vendor_recommendations')->result_array();
                               $vr_sno = 1;
                               foreach($vendor_recs as $vr){ ?>
                               <tr>
                                  <td><?php echo $vr_sno++; ?></td>
                                  <td>
                                     <?php if(!empty($vr['file_name'])){ ?>
                                     <a href="javascript:void(0);" onclick="preview_vr_file(this); return false;" data-url="<?php echo site_url(PURCHASE_PATH.'vendor_recommendations/'.$pur_request->id.'/'.$vr['file_name']); ?>" data-name="<?php echo $vr['file_name']; ?>" class="btn btn-success btn-xs"><i class="fa fa-eye"></i></a>
                                     <a href="<?php echo site_url(PURCHASE_PATH.'vendor_recommendations/'.$pur_request->id.'/'.$vr['file_name']); ?>" download class="btn btn-default btn-xs"><i class="fa fa-download"></i> <?php echo $vr['file_name']; ?></a>
                                     <?php } else { echo '-'; } ?>
                                  </td>
                                  <td><?php echo htmlspecialchars($vr['remarks']); ?></td>
                                  <td><?php echo get_staff_full_name($vr['staffid']); ?></td>
                                  <td><?php echo _d($vr['dateadded']); ?></td>
                                  <td>
                                     <?php if(($vr['staffid'] == get_staff_user_id() || is_admin()) && $pur_request->status != 2){ ?>
                                     <a href="<?php echo admin_url('purchase/delete_vendor_recommendation/'.$vr['id'].'/'.$pur_request->id); ?>" class="text-danger _delete"><i class="fa fa-trash"></i></a>
                                     <?php } ?>
                                  </td>
                               </tr>
                               <?php } ?>
                               <?php if(empty($vendor_recs)){ ?>
                               <tr><td colspan="6" class="text-center text-muted">No files added yet.</td></tr>
                               <?php } ?>
                            </tbody>
                         </table>
                      </div>
                      <div id="vr_preview_data"></div>
                   </div>
                </div>
                <?php } ?>

                <div role="tabpanel" class="tab-pane ptop10 " id="compare_quotes">
                  <?php if(total_rows(db_prefix().'pur_estimates', ['pur_request' => $pur_request->id]) > 0){ ?>

                    <div class="btn-group pull-right mright5" data-toggle="tooltip" title="<?php echo _l('compare_quotation_tooltip'); ?>">
                           <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" ><i class="fa fa-file-pdf"></i><span class="caret"></span></a>
                           <ul class="dropdown-menu dropdown-menu-right">
                              <li class="hidden-xs"><a href="<?php echo admin_url('purchase/compare_quotation_pdf/'.$pur_request->id.'?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
                              <li class="hidden-xs"><a href="<?php echo admin_url('purchase/compare_quotation_pdf/'.$pur_request->id.'?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
                              <li><a href="<?php echo admin_url('purchase/compare_quotation_pdf/'.$pur_request->id); ?>"><?php echo _l('download'); ?></a></li>
                           </ul>
                           </div>

                    <div class="col-md-6">
                        <table class="table border table-striped martop0">
                          <tbody>
                             <tr class="project-overview">
                                <td class="bold" width="30%"><?php echo _l('pur_rq_code'); ?></td>
                                <td><?php echo pur_html_entity_decode($pur_request->pur_rq_code); ?></td>
                             </tr>
                             <tr class="project-overview">
                                <td class="bold"><?php echo _l('pur_rq_name'); ?></td>
                                <td><?php echo _l($pur_request->pur_rq_name); ?></td>
                             </tr>
                             <tr class="project-overview">
                                <td class="bold"><?php echo _l('purchase_requestor'); ?></td>
                                <td><?php 
                                if(!empty($pur_request->requester)){
                                  $_data = '<a href="' . admin_url('staff/profile/' . $pur_request->requester) . '">' . staff_profile_image($pur_request->requester, [
                      'staff-profile-image-small',
                      ]) . '</a>';
                  $_data .= ' <a href="' . admin_url('staff/profile/' . $pur_request->requester) . '">' . get_staff_full_name($pur_request->requester) . '</a>';
                } else {
                  $_data = '<span class="text-muted">-</span>';
                }
                  echo pur_html_entity_decode($_data);
                  ?></td>
                             </tr>
                             
                             <tr class="project-overview">
                                <td class="bold"><?php echo _l('request_date'); ?></td>
                                <td><?php echo _dt($pur_request->request_date); ?></td>
                             </tr>
                          </tbody>
                      </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table border table-striped martop0">
                          <tbody>
                             <tr class="project-overview">
                                <td colspan="3" class="bold text-center" width="30%"><?php echo _l('vendors'); ?></td>
                             </tr>
                             <?php 
                             
                             $arr_vendors = get_arr_vendors_by_pr($pur_request->id); ?>
                             <?php foreach($arr_vendors as $vendor){ ?>
                              <tr class="project-overview">
                                <td class="" ><span class="bold"><?php echo pur_html_entity_decode($vendor->company); ?></span></td>
                                <td class="" ><span class="bold"><?php echo _l('vendor_code').': ' ?></span><span class=""><?php echo pur_html_entity_decode($vendor->vendor_code); ?></span></td>
                                <td class="" ><span class="bold"><?php echo _l('phonenumber').': '; ?></span><span class=""><?php echo pur_html_entity_decode($vendor->phonenumber); ?></span></td>
                             </tr>
                             <?php } ?>
                          </tbody>
                      </table>
                    </div>
                    <div class="col-md-12"><hr></div>
                    <div class="col-md-12">
                      <div class="table-responsive">
                        <?php echo form_open(admin_url('purchase/compare_quote_pur_request/'.$pur_request->id),array('id'=>'compare_quote_pur_request-form'));  ?>
                          <table class="table table-bordered compare_quotes_table">
                              <thead class="bold">
                               <tr>
                                <th rowspan="2" scope="col"><span class="bold"><?php echo _l('items'); ?></span></th>
                                <th rowspan="2" scope="col"><span class="bold"><?php echo _l('pur_qty'); ?></span></th>
                                <th rowspan="2" scope="col"><span class="bold"><?php echo _l('unit'); ?></span></th>
                                <th rowspan="2" scope="col"><span class="bold"><?php echo _l('description'); ?></span></th>

                                <?php foreach($quotations as $quote){ ?>
                                <th colspan="2" class="text-center"><span class="bold text-danger"><?php echo format_pur_estimate_number($quote['id']). ' - '.get_vendor_company_name($quote['vendor']); ?></span></th>
                                 <?php } ?>
                              </tr>

                              <tr>
                                <?php foreach($quotations as $quote){ ?>
                                <th class="text-right"><span class="bold"><?php echo _l('purchase_unit_price'); ?></span></th>
                                <th class="text-right"><span class="bold"><?php echo _l('total') ?></span></th>
                                 <?php } ?>
                                
                              </tr>
                              </thead>
                            <tbody>
                              <?php 
                                $this->load->model('purchase/purchase_model'); 
                                $list_items = $this->purchase_model->get_pur_request_detail($pur_request->id);
                              ?>
                              <?php foreach($list_items as $key => $item){ ?>
                                <tr>
                                  <td><?php echo pur_html_entity_decode($key + 1); ?></td>
                                  <td><?php echo pur_html_entity_decode($item['quantity']); ?></td>
                                  <td><?php $unit_name = isset(get_unit_type_item($item['unit_id'])->unit_name) ? get_unit_type_item($item['unit_id'])->unit_name : '';
                                  echo pur_html_entity_decode($unit_name); ?></td>
                                  <td><?php $item_name = isset(get_item_hp($item['item_code'])->description) ? get_item_hp($item['item_code'])->description : '';
                                  echo pur_html_entity_decode($item_name); ?></td>

                                  <?php foreach($quotations as $quote){ ?>
                                    <?php 
                                        $_currency = $base_currency;
                                        if($quote['currency'] != 0){
                                          $_currency = pur_get_currency_by_id($quote['currency']);
                                        }
                                     ?>
                                    <?php  $item_quote = get_item_detail_in_quote($item['item_code'], $quote['id']); ?>
                                    <?php if(isset($item_quote)){ ?>
                                      <td class="text-right"><?php echo app_format_money($item_quote->unit_price, $_currency->name); ?></td>
                                      <td class="text-right"><?php echo app_format_money($item_quote->total_money, $_currency->name); ?></td>
                                    <?php }else{ ?>
                                      <td>-</td>
                                      <td>-</td>
                                    <?php } ?>
                                  <?php } ?>

                                </tr>
                              <?php } ?>
                              <tr>
                                <td colspan="4" class="text-center"><span class="bold"><?php echo _l('mark_a_contract'); ?></span></td>
                                <?php foreach($quotations as $quote){ ?>
                                  <td colspan="2"><input name="mark_a_contract[<?php echo pur_html_entity_decode($quote['id']); ?>]" type="text" value="<?php echo pur_html_entity_decode($quote['make_a_contract']); ?>" /></td>
                                <?php } ?>
                              </tr>
                              <tr>
                                <td colspan="4" class="text-center"><span class="bold"><?php echo _l('total_purchase_amount'); ?></span></td>
                                <?php foreach($quotations as $quote){ ?>
                                  <?php 
                                        $_currency = $base_currency;
                                        if($quote['currency'] != 0){
                                          $_currency = pur_get_currency_by_id($quote['currency']);
                                        }
                                     ?>
                                  <td colspan="2" class="text-right">
                                    <span class="bold text-info"><?php echo app_format_money($quote['total'], $_currency->name); ?></span>
                                    <?php 
                                      if($_currency->id != $base_currency->id){
                                          $convert_rate = pur_get_currency_rate($_currency->name, $base_currency->name);
                                          $convert_value = round(($quote['total'] * $convert_rate), 2 );
                                          echo '<br><i class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="'._l('pur_convert_from').' '. $_currency->name. ' '._l('pur_to'). ' '.$base_currency->name.' '._l('pur_with_currency_rate').': '.$convert_rate. '"></i>&nbsp;&nbsp;<span class="bold text-info">'.app_format_money($convert_value, $base_currency->name).'</span>';
                                      }
                                    ?>
                                  </td>
                                <?php } ?>
                              </tr>
                            </tbody>  
                   
                          </table>
                        </div>
                    </div>
                    <div class="col-md-12">
                      <br>
                      <p><span class="bold"><?php echo _l('purchase_request_description').': '; ?></span><span><?php echo pur_html_entity_decode($pur_request->rq_description); ?></span></p>
                      <?php echo render_textarea('compare_note', 'comparison_notes', clear_textarea_breaks($pur_request->compare_note)) ?>
                    </div>
                    <div class="col-md-12">
                    <button id="sm_btn" class="btn btn-info save_detail pull-right"><?php echo _l('pur_confirm'); ?></button>
                    </div>
                  <?php echo form_close(); ?>

                  <?php }else{ ?>
                   
                      <div class="col-md-12">
                        <span class="text-bold"><?php echo _l('this_purchase_request_does_not_have_a_quote_yet'); ?></span>
                      </div>
                      
                    
                  <?php } ?>
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

         <div class="form-group">
             <div class="radio radio-primary radio-inline">
                 <input type="radio" id="sign" name="sign_type" value="1" checked>
                 <label for="sign">
                    <?php echo _l('sign'); ?>      
                  </label>
             </div>
             <div class="radio radio-primary radio-inline">
                 <input type="radio" id="upload" name="sign_type" value="0" >
                 <label for="upload">
                     <?php echo _l('pur_upload'); ?>    
                  </label>
             </div>
         </div>


            <div id="upload_sign" class="mbot15 hide">
               <?php echo form_open_multipart(admin_url('purchase/sign_attachment'),array('id'=>'sign_attachment-form')); ?>

                  <input type="file" id="sign_attachment_file" accept=".png, .jpg" name="sign_attachment" class="form-control">

                  <?php echo form_hidden('approve_rel_id', $pur_request->id) ?>
                  <?php echo form_hidden('approve_rel_type', 'pur_request') ?>

               <?php echo form_close(); ?>    
            </div>

            <div id="sign_pad" >
               <div class="signature-pad--body">
                 <canvas id="signature" height="130" width="550"></canvas>
               </div>
               <input type="text" class="ip_style" tabindex="-1" name="signature" id="signatureInput">

               <div class="dispay-block">
                 <button type="button" class="btn btn-default btn-xs clear" tabindex="-1" onclick="signature_clear();"><?php echo _l('clear'); ?></button>
               
               </div>
            </div>
            

           

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
           <button id="sign_button" onclick="sign_request(<?php echo pur_html_entity_decode($pur_request->id); ?>);" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" class="btn btn-success"><?php echo _l('e_signature_sign'); ?></button>
          </div>

      </div><!-- /.modal-content -->
   </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php init_tail(); ?> 
</body>
</html>
<?php require 'modules/purchase/assets/js/view_pur_request_js.php';?>
