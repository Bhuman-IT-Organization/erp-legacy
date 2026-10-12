<script> 

 $(function(){
  'use strict';

  appValidateForm($('#add_warehouse'), {
    warehouse_code: 'required',
    warehouse_name: 'required',
    order: 'required',

  });

  var warehouseServerParams = {
    "assign_staff_filter": "[name='assign_staff_filter[]']",
    "failure_location_ids_filter": "[name='failure_location_ids[]']",
  };

  var table_warehouse_name = $('table.table-table_warehouse_name');
  var _table_api = initDataTable(table_warehouse_name, admin_url+'warehouse/table_warehouse_name', [0], [0], warehouseServerParams,  [3, 'asc']);
  $.each(warehouseServerParams, function(i, obj) {
    $('select' + obj).on('change', function() {  
      table_warehouse_name.DataTable().ajax.reload();
    });
  });

})


 function edit_warehouse_type(invoker,id){
  "use strict";

  var $warehouseModal = $('#a_warehouse');

  $warehouseModal.find('input[name="warehouse_code"]').val('');
  $warehouseModal.find('input[name="warehouse_name"]').val('');
  $warehouseModal.find('input[name="order"]').val('');

  $warehouseModal.find('textarea[name="warehouse_address"]').val('');
  $warehouseModal.find('textarea[name="note"]').val('');

  $warehouseModal.find('input[name="display"]').prop("checked", false);
  $warehouseModal.find('input[name="hide_warehouse_when_out_of_stock"]').prop("checked", false);

  
  $('#a_warehouse').modal('show');
  $('.edit-title').removeClass('hide');
  $('.add-title').addClass('hide');

  $('#warehouse_id').html('');
  $('#warehouse_id').append(hidden_input('id',id));

        // If id found get the text from the datatable
  if (typeof (id) !== 'undefined') {

    requestGetJSON('warehouse/get_warehouse_by_id/' + id).done(function (response) {

      $warehouseModal.find('input[name="warehouse_code"]').val(response.warehouse_code);
      $warehouseModal.find('input[name="warehouse_name"]').val(response.warehouse_name);
      $warehouseModal.find('input[name="order"]').val(response.order);
      $warehouseModal.find('input[name="city"]').val(response.city);
      $warehouseModal.find('input[name="state"]').val(response.state);
      $warehouseModal.find('input[name="zip_code"]').val(response.zip_code);

      if(response.country != ''){
        $("select[name='country']").val(response.country).change();
      }else{
        $("select[name='country']").val('').change();

      }

      if(response.display == 1){
        $warehouseModal.find('input[name="display"]').prop("checked", true);
      }else{
        $warehouseModal.find('input[name="display"]').prop("checked", false);

      }

      if(response.hide_warehouse_when_out_of_stock == 1){
        $warehouseModal.find('input[name="hide_warehouse_when_out_of_stock"]').prop("checked", true);
      }else{
        $warehouseModal.find('input[name="hide_warehouse_when_out_of_stock"]').prop("checked", false);

      }

      $warehouseModal.find('textarea[name="warehouse_address"]').val(response.warehouse_address.replace(/(<|<)br\s*\/*(>|>)/g, " "));
      $warehouseModal.find('textarea[name="note"]').val(response.note.replace(/(<|<)br\s*\/*(>|>)/g, " "));

      $('#custom_fields_items').html(response.custom_fields_html);

      $("select[name='assign_to_staffs[]']").html(response.assign_to_staff);
      $("select[name='assign_to_staffs[]']").selectpicker('refresh');

      init_selectpicker();

    });

  }
  
  
}

$('#warehouse_code').on('keypress', function() {
  "use strict";
  
  var warehouse_code = $('input[name="warehouse_code"]').val();
  if(warehouse_code.length >= 100){
    alert_float('warning', "<?php echo _l('Maximum_length_warehouse_code_is_100_words') ; ?>", 200);
  }
});


</script>