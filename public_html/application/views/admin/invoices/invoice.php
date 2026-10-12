<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?= form_open($this->uri->uri_string(), ['id' => 'invoice-form', 'class' => '_transaction_form invoice-form']); ?>
            <?php if (isset($invoice)) {
                echo form_hidden('isedit');
            } ?>
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    <span>
                        <?= e(isset($invoice) ? format_invoice_number($invoice) : _l('create_new_invoice')); ?>
                    </span>
                    <?= isset($invoice) ? format_invoice_status($invoice->status) : ''; ?>
                </h4>
                <?php $this->load->view('admin/invoices/invoice_template'); ?>
            </div>
            <?= form_close(); ?>
            <?php $this->load->view('admin/invoice_items/item'); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        validate_invoice_form();
        // Init accountacy currency symbol
        init_currency();
        // Project ajax search
        init_ajax_project_search_by_customer_id();
        // Maybe items ajax search
        init_ajax_search('items', '#item_select.ajax-search', undefined, admin_url + 'items/search');

        // Load sales orders when customer changes (user action only)
        var _so_loaded = false;
        $('body').on('change', '#clientid', function(){
            var clientid = $(this).val();
            load_sales_orders(clientid);
            _so_loaded = true;
            // Clear delivery vouchers
            $('#delivery_voucher_ids').find('option').remove();
            $('#delivery_voucher_ids').selectpicker('refresh');
        });

        // Load on page load if customer already selected (only once)
        setTimeout(function(){
            if(!_so_loaded){
                var initial_client = $('#clientid').val();
                if(initial_client && initial_client != ''){
                    load_sales_orders(initial_client);
                }
            }
        }, 1000);
    });

    function load_sales_orders(clientid){
        var $so = $('#sales_order_id');
        $so.find('option').remove();
        $so.append('<option value=""></option>');
        if(clientid && clientid != ''){
            $.get(admin_url + 'invoices/get_sales_orders_by_customer/' + clientid, function(resp){
                var data = JSON.parse(resp);
                if(data.html){
                    $so.append(data.html);
                }
                <?php if(isset($invoice) && !empty($invoice->sales_order_id)){ ?>
                $so.val('<?php echo $invoice->sales_order_id; ?>');
                <?php } ?>
                $so.selectpicker('refresh');
                <?php if(isset($invoice) && !empty($invoice->sales_order_id)){ ?>
                // Load delivery vouchers for the linked sales order (display only, no item reload)
                setTimeout(function(){
                    var so_id = '<?php echo $invoice->sales_order_id; ?>';
                    var $dv = $('#delivery_voucher_ids');
                    $dv.find('option').remove();
                    $.get(admin_url + 'invoices/get_delivery_vouchers_by_estimate/' + so_id, function(resp){
                        var data = JSON.parse(resp);
                        if(data.html){ $dv.html(data.html); }
                        <?php 
                        if(!empty($invoice->delivery_voucher_ids)){
                            $dv_ids_arr = json_decode($invoice->delivery_voucher_ids, true);
                            if(!is_array($dv_ids_arr)) $dv_ids_arr = [];
                        } else {
                            $dv_ids_arr = [];
                        }
                        if(!empty($dv_ids_arr)){ ?>
                        $dv.selectpicker('val', <?php echo json_encode(array_map('strval', $dv_ids_arr)); ?>);
                        <?php } ?>
                        $dv.selectpicker('refresh');
                    });
                }, 500);
                <?php } ?>
            });
        } else {
            $so.selectpicker('refresh');
        }
    }

    function sales_order_change(el){
        var so_id = $(el).val();
        var $dv = $('#delivery_voucher_ids');
        $dv.find('option').remove();
        if(so_id && so_id != ''){
            $.get(admin_url + 'invoices/get_delivery_vouchers_by_estimate/' + so_id, function(resp){
                var data = JSON.parse(resp);
                if(data.html){
                    $dv.html(data.html);
                }
                $dv.selectpicker('refresh');
            });
        } else {
            $dv.selectpicker('refresh');
        }
    }

    function load_delivery_vouchers(clientid){
        // Kept for backward compatibility
    }

    function delivery_voucher_change(){
        // Don't reload items if in edit mode (items already exist in form)
        <?php if(isset($invoice)){ ?>
        return;
        <?php } ?>
        var dv_ids = $('#delivery_voucher_ids').val();
        if(dv_ids && dv_ids.length > 0){
            // Clear existing items from table before loading new ones
            $('table.items tbody tr.item').remove();
            
            var estimate_id = $('#sales_order_id').val() || '';
            $.post(admin_url + 'invoices/copy_delivery_voucher_items', {dv_ids: dv_ids, estimate_id: estimate_id}, function(resp){
                var data = JSON.parse(resp);
                if(data.items && data.items.length > 0){
                    // Add items to invoice
                    $.each(data.items, function(i, item){
                        add_item_to_table(item, 'undefined', 'invoice');
                    });
                }
            });
        }
    }
</script>
</body>

</html>