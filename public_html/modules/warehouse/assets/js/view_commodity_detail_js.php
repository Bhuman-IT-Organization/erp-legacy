 <script>
   (function($) {
    "use strict";

  $('input[name="commodity_id"]').val("<?php echo new_html_entity_decode($commodity_item->id) ;?>");

    //inventory stock
    var ProposalServerParams = {
        "warehouse_ft": "[name='warehouse_filter[]']",
        "commodity_ft": "[name='commodity_id']",
        "alert_filter": "[name='alert_filter']",
    };
    var table_inventory_stock = $('table.table-table_inventory_stock');

   // Footer has to exist before initDataTable so DataTables owns it - the hidden
   // first column is then hidden in the footer too and the cells stay aligned with
   // the header. Added here rather than in the view because render_datatable()
   // emits no tfoot.
   if(table_inventory_stock.find('tfoot').length === 0){
      var stock_footer_cells = '';
      table_inventory_stock.find('thead th').each(function(){ stock_footer_cells += '<td></td>'; });
      table_inventory_stock.append('<tfoot><tr class="wh-stock-total-row">'+stock_footer_cells+'</tr></tfoot>');
   }

   // Columns 5, 6 and 7 are Ordered Qty / Inventory (received) / Pending Qty and
   // column 11 is Landed Cost. All four are calculated - the quantities from the
   // goods receipts and the landed cost from purchase invoices plus their shipping
   // charges - rather than stored, so none of them can be sorted or searched in SQL.
   var  _table_api = initDataTable(table_inventory_stock, admin_url+'warehouse/table_inventory_stock', [5, 6, 7, 11], [5, 6, 7, 11], ProposalServerParams, [0, 'desc']);

   // Received quantity is the only total asked for. The server sends it already
   // de-duplicated across batch rows that share the same receipts.
   table_inventory_stock.on('xhr.dt', function(e, settings, json){
      var footer_cells = table_inventory_stock.find('tfoot tr').children('td');
      if(!json || footer_cells.length === 0){ return; }

      footer_cells.html('');
      footer_cells.eq(1).html('<span class="bold">'+(json.sReceivedQtyTotalLabel || '')+'</span>');
      footer_cells.eq(6).html('<span class="bold">'+(json.sReceivedQtyTotal || 0)+'</span>');
   });

   $('.table-table_inventory_stock').DataTable().columns([0]).visible(false, false);

    $.each(ProposalServerParams, function(i, obj) {
        $('select' + obj).on('change', function() {  
            table_inventory_stock.DataTable().ajax.reload();
        });
    });

    //expriry date
    var ProposalServerParams = {
        "warehouse_ft": "[name='warehouse_filter[]']",
        "commodity_ft": "[name='commodity_id']",
        "alert_filter": "[name='alert_filter']",
    };
    var table_view_commodity_detail = $('table.table-table_view_commodity_detail');
   var  _table_api = initDataTable(table_view_commodity_detail, admin_url+'warehouse/table_view_commodity_detail', [], [], ProposalServerParams);
    $.each(ProposalServerParams, function(i, obj) {
        $('select' + obj).on('change', function() {  
            table_view_commodity_detail.DataTable().ajax.reload();
        });
    });



//transaction, history
var ProposalServerParams = {
        "warehouse_ft": "[name='warehouse_filter[]']",
        "commodity_ft": "[name='commodity_id']",
        
    };

  var   table_warehouse_history = $('table.table-table_warehouse_history');
   var  _table_api = initDataTable(table_warehouse_history, admin_url+'warehouse/table_warehouse_history', [], [], ProposalServerParams, [0, 'desc']);

   $('.table-table_warehouse_history').DataTable().columns([0]).visible(false, false);

    $.each(ProposalServerParams, function(i, obj) {
        $('select' + obj).on('change', function() {  
            table_warehouse_history.DataTable().ajax.reload();
        });
    });

    init_ajax_search('items','#item_select_print_barcode.ajax-search',undefined,admin_url+'warehouse/wh_commodity_code_search_all');
    
})(jQuery);

</script>