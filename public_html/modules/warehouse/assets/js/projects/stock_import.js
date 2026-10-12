(function($) {
	"use strict";

	var _project_id = $('input[name="_project_id"]').val();
	var GoodsreceiptParams = {
		"day_vouchers": "input[name='date_add']",
		"_project_id": "input[name='_project_id']",
	};

	var table_manage_goods_receipt = $('.table-table_manage_goods_receipt');

	// Column 6 (Invoice Number) is resolved in PHP from pur_invoices.grn_id, so it
	// cannot be sorted or searched at SQL level.
	initDataTable(table_manage_goods_receipt, admin_url+'warehouse/table_manage_goods_receipt', [6], [6], GoodsreceiptParams, [0, 'desc']);
	$('.purchase_sm').DataTable().columns([0]).visible(false, false);

})(jQuery);
