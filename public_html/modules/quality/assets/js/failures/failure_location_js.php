<script>

  function btn_select(){
    "use strict";
    
    var is_check = false;
    var check_required = false;
    var data = {};
    var products = [];
    var ids, message;
    var rows = $('.damaged_products').find('tbody tr');
    $.each(rows, function() {
      var checkbox = $($(this).find('td').eq(8)).find('input');
      if (checkbox.prop('checked') === true) {
        is_check = true;
        ids = checkbox.val();

        var commodity_code = $($(this).find('td').eq(8)).find('div.article_search_result input[name="commodity_code"]').val();
        var commodity_name = $($(this).find('td').eq(8)).find('div.article_search_result input[name="commodity_name"]').val();
        var unit_id = $($(this).find('td').eq(8)).find('div.article_search_result input[name="unit_id"]').val();
        var warehouse_id = $($(this).find('td').eq(8)).find('div.article_search_result input[name="warehouse_id"]').val();
        var available_quantity = $($(this).find('td').eq(8)).find('div.article_search_result input[name="quantities"]').val();
        var quantities = $($(this).find('td').eq(5)).find('div input[name="quantity_of_damaged"]').val();
        var unit_price = $($(this).find('td').eq(8)).find('div.article_search_result input[name="unit_price"]').val();
        var serial_number = $($(this).find('td').eq(6)).find('div select[name="serial_number[]"]').val();
        var to_warehouse_id = $($(this).find('td').eq(7)).find('div select[name="to_stock_name"]').val();
        var expiry_date = $($(this).find('td').eq(8)).find('div.article_search_result input[name="expiry_date"]').val();
        var lot_number = $($(this).find('td').eq(8)).find('div.article_search_result input[name="lot_number"]').val();

        var product = [
          commodity_code,
          commodity_name,
          unit_id,
          warehouse_id,
          quantities,
          unit_price,
          to_warehouse_id,
          serial_number,
          expiry_date,
          lot_number,
          ];
        products.push(product);
        if(quantities == 0 || quantities == '0'){
            check_required = true;
            message = '<?php echo _l('quality_please_fill_quantity_of_damaged_products'); ?>';

        }
        if(parseFloat(quantities) > parseFloat(available_quantity)){
          check_required = true;
          message = '<?php echo _l('quality_please_fill_quantity_of_damaged_products_less_than_the_actual_quantity'); ?>';
        }
        if(to_warehouse_id == "" || to_warehouse_id == null){
            check_required = true;
            message = '<?php echo _l('quality_please_fill_faulty_goods_warehouse'); ?>';
        }
     
      }
    });
    if(!is_check){
      message = '<?php echo _l('quality_please_select_product'); ?>';
    }

    if(check_required == true || !is_check){
      alert_float('warning', message);
      return false;
    }

    data.products = products;
    data.quality_check_id = <?php echo html_entity_decode($quality_check_id); ?>;
    $.post(admin_url + "quality/send_product_to_warehouse", data).done(function (response) {
      response = JSON.parse(response);

      if (response.success === true || response.success == "true") {
        alert_float('success', response.message)
      }
      location.reload();
    });
  }

</script>