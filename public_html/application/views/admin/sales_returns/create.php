<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-mt-0 tw-font-bold"><?php echo $title; ?></h4>
            <hr>
            <?php echo form_open(admin_url('sales_returns/create' . (isset($return) ? '/'.$return->id : '')), ['id' => 'sales-return-form']); ?>
            
            <!-- Row 1: Sales Order, GDV, Return Number -->
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label><span class="text-danger">* </span>Sales Order (Estimate)</label>
                  <select name="estimate_id" id="sr_estimate_id" class="selectpicker" data-live-search="true" data-width="100%" required>
                    <option value=""></option>
                    <?php foreach($estimates as $est){ ?>
                    <option value="<?php echo $est['id']; ?>" <?php echo (isset($return) && $return->estimate_id == $est['id']) ? 'selected' : ''; ?>><?php echo format_estimate_number($est['id']); ?> - <?php echo get_company_name($est['clientid']); ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label><span class="text-danger">* </span>Goods Delivery Voucher</label>
                  <select name="gdv_ids[]" id="sr_gdv_ids" class="selectpicker" multiple data-live-search="true" data-width="100%" data-actions-box="true" required>
                    <?php if(isset($return) && !empty($return->gdv_ids)){
                      $gdv_ids = json_decode($return->gdv_ids, true);
                      if(is_array($gdv_ids)){
                        foreach($gdv_ids as $gid){
                          $this->db->where('id', $gid);
                          $gdv = $this->db->get(db_prefix().'goods_delivery')->row();
                          if($gdv) echo '<option value="'.$gdv->id.'" selected>'.$gdv->goods_delivery_code.'</option>';
                        }
                      }
                    } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <?php $rn = isset($return) ? $return->return_number : 'SR-'.date('YmdHi'); ?>
                <?php echo render_input('return_number', 'Return Number', $rn); ?>
              </div>
            </div>

            <!-- Row 2: Customer, Date -->
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Customer</label>
                  <input type="text" id="sr_customer_name" class="form-control" readonly value="<?php echo isset($return) ? get_company_name($return->clientid) : ''; ?>">
                  <input type="hidden" name="clientid" id="sr_clientid" value="<?php echo isset($return) ? $return->clientid : ''; ?>">
                </div>
              </div>
              <div class="col-md-4">
                <?php $rd = isset($return) ? _d($return->return_date) : _d(date('Y-m-d')); ?>
                <?php echo render_date_input('return_date', 'Return Date', $rd); ?>
              </div>
              <div class="col-md-4">
                <?php echo render_textarea('note', 'Note', isset($return) ? $return->note : '', ['rows' => 2]); ?>
              </div>
            </div>

            <hr>
            <!-- Items Table -->
            <h5 class="font-bold">Return Items</h5>
            <div class="table-responsive">
              <table class="table table-bordered" id="sr_items_table">
                <thead>
                  <tr class="bg-primary" style="color:#fff;">
                    <th>Item</th>
                    <th width="10%">GDV Qty</th>
                    <th width="10%">Return Qty</th>
                    <th width="12%">Unit Price</th>
                    <th width="12%">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if(isset($return_items) && !empty($return_items)){ 
                    foreach($return_items as $i => $item){ ?>
                  <tr>
                    <td><?php echo $item['description']; ?>
                      <input type="hidden" name="items[<?php echo $i; ?>][item_code]" value="<?php echo $item['item_code']; ?>">
                      <input type="hidden" name="items[<?php echo $i; ?>][description]" value="<?php echo htmlspecialchars($item['description']); ?>">
                      <input type="hidden" name="items[<?php echo $i; ?>][unit_id]" value="<?php echo $item['unit_id']; ?>">
                      <input type="hidden" name="items[<?php echo $i; ?>][tax_id]" value="<?php echo $item['tax_id']; ?>">
                      <input type="hidden" name="items[<?php echo $i; ?>][tax_rate]" value="<?php echo $item['tax_rate']; ?>">
                    </td>
                    <td class="text-center">-</td>
                    <td><input type="number" name="items[<?php echo $i; ?>][qty]" class="form-control sr-qty" value="<?php echo (float)$item['qty']; ?>" min="0" step="any"></td>
                    <td><input type="number" name="items[<?php echo $i; ?>][unit_price]" class="form-control" value="<?php echo (float)$item['unit_price']; ?>" readonly></td>
                    <td class="text-right sr-amount"><?php echo app_format_money((float)$item['amount'], ''); ?></td>
                  </tr>
                  <?php }} ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right"><strong id="sr_total"><?php echo isset($return) ? app_format_money($return->total, '') : '0.00'; ?></strong></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <input type="hidden" name="subtotal" id="sr_subtotal" value="<?php echo isset($return) ? $return->subtotal : 0; ?>">
            <input type="hidden" name="total_tax" id="sr_total_tax" value="<?php echo isset($return) ? $return->total_tax : 0; ?>">
            <input type="hidden" name="total" id="sr_total_hidden" value="<?php echo isset($return) ? $return->total : 0; ?>">

            <div class="text-right">
              <a href="<?php echo admin_url('sales_returns'); ?>" class="btn btn-default">Cancel</a>
              <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
            </div>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
  // On estimate select - load GDVs and customer info
  $('#sr_estimate_id').on('change', function(){
    var est_id = $(this).val();
    var $gdv = $('#sr_gdv_ids');
    $gdv.find('option').remove();
    $('#sr_items_table tbody').html('');
    if(est_id){
      $.get(admin_url + 'sales_returns/get_gdv_by_estimate/' + est_id, function(r){
        var data = JSON.parse(r);
        if(data.html) $gdv.html(data.html);
        $gdv.selectpicker('refresh');
      });
      $.get(admin_url + 'sales_returns/get_estimate_info/' + est_id, function(r){
        var data = JSON.parse(r);
        $('#sr_clientid').val(data.clientid);
        $('#sr_customer_name').val(data.client_name);
      });
    } else {
      $gdv.selectpicker('refresh');
    }
  });

  // On GDV select - load items
  $('#sr_gdv_ids').on('changed.bs.select', function(){
    var gdv_ids = $(this).val();
    var est_id = $('#sr_estimate_id').val();
    if(gdv_ids && gdv_ids.length > 0){
      $.post(admin_url + 'sales_returns/get_items_by_gdv', {gdv_ids: gdv_ids, estimate_id: est_id}, function(r){
        var data = JSON.parse(r);
        var html = '';
        var total = 0;
        $.each(data.items, function(i, item){
          var amt = item.available_qty * item.unit_price;
          total += amt;
          html += '<tr>';
          html += '<td>' + item.description;
          html += '<input type="hidden" name="items['+i+'][item_code]" value="'+item.item_code+'">';
          html += '<input type="hidden" name="items['+i+'][description]" value="'+item.description.replace(/"/g,"&quot;")+'">';
          html += '<input type="hidden" name="items['+i+'][unit_id]" value="'+(item.unit_id||'')+'">';
          html += '<input type="hidden" name="items['+i+'][tax_id]" value="'+(item.tax_id||'')+'">';
          html += '<input type="hidden" name="items['+i+'][tax_rate]" value="'+(item.tax_rate||'')+'">';
          html += '</td>';
          html += '<td class="text-center">'+item.delivered_qty+'</td>';
          html += '<td><input type="number" name="items['+i+'][qty]" class="form-control sr-qty" value="'+item.available_qty+'" max="'+item.available_qty+'" min="0" step="any"></td>';
          html += '<td><input type="number" name="items['+i+'][unit_price]" class="form-control" value="'+item.unit_price+'" readonly></td>';
          html += '<td class="text-right sr-amount">'+amt.toFixed(2)+'</td>';
          html += '</tr>';
        });
        $('#sr_items_table tbody').html(html);
        sr_calc_total();
      });
    } else {
      $('#sr_items_table tbody').html('');
      sr_calc_total();
    }
  });

  // Recalculate on qty change
  $(document).on('change keyup', '.sr-qty', function(){
    var row = $(this).closest('tr');
    var qty = parseFloat($(this).val()) || 0;
    var price = parseFloat(row.find('input[name*="unit_price"]').val()) || 0;
    var amt = qty * price;
    row.find('.sr-amount').text(amt.toFixed(2));
    row.find('input[name*="[amount]"]').remove();
    row.find('td:last').append('<input type="hidden" name="'+$(this).attr('name').replace('[qty]','[amount]')+'" value="'+amt.toFixed(2)+'">');
    sr_calc_total();
  });

  function sr_calc_total(){
    var total = 0;
    $('#sr_items_table tbody .sr-amount').each(function(){ total += parseFloat($(this).text()) || 0; });
    $('#sr_total').text(total.toFixed(2));
    $('#sr_subtotal').val(total.toFixed(2));
    $('#sr_total_hidden').val(total.toFixed(2));
  }
});
</script>
</body>
</html>
