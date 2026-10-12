<script>
	var product_tabs;
	var data_color = <?php echo json_encode($data_color); ?>;
	var signaturePad;
	var croppedCtx;

	(function($) {
		"use strict";

		var BomChangeLogParams={
			"manufacturing_order_id": "[name='manufacturing_order_id']",
		};
		var bom_change_log_table = $('.table-bom_change_log_table');
		initDataTable(bom_change_log_table, admin_url+'manufacturing/bom_change_log_table',[0],[0], BomChangeLogParams, [0 ,'desc']);
	//hide first column
		var hidden_columns = [0];
		$('.table-bom_change_log_table').DataTable().columns(hidden_columns).visible(false, false);

		<?php if(isset($product_tab_details)){ ?>
			var dataObject_pu = <?php echo json_encode($product_tab_details) ; ?>;
		<?php }else{?>
			var dataObject_pu = [];
		<?php } ?>

		var hotElement1 = document.getElementById('product_tab_hs');

		product_tabs = new Handsontable(hotElement1, {
			licenseKey: 'non-commercial-and-evaluation',

			contextMenu: true,
			manualRowMove: true,
			manualColumnMove: true,
			stretchH: 'all',
			autoWrapRow: true,
			rowHeights: 30,
			defaultRowHeight: 100,
			minRows: 10,
			maxRows: 40,
			width: '100%',

			rowHeaders: true,
			cells: function(row, col, prop) {
				var cellProperties = {};
				if (col > 2) {
					cellProperties.renderer = firstRowRenderer; 
				}
				return cellProperties;
			},
			colHeaders: true,
			autoColumnSize: {
				samplingRatio: 23
			},

			filters: true,
			manualRowResize: true,
			manualColumnResize: true,
			allowInsertRow: true,
			allowRemoveRow: true,
			columnHeaderHeight: 40,

			rowHeights: 30,
			rowHeaderWidth: [44],
			minSpareRows: 1,
			hiddenColumns: {
				columns: [0],
				indicators: true
			},

			columns: [
			{
				type: 'text',
				data: 'id',
			},
			{
				type: 'text',
				data: 'product_id',
				renderer: customDropdownRenderer,
				editor: "chosen",
				chosenOptions: {
					data: <?php echo json_encode($product_for_hansometable); ?>
				},
			},
			{
				type: 'text',
				data: 'unit_id',
				renderer: customDropdownRenderer,
				editor: "chosen",
				chosenOptions: {
					data: <?php echo json_encode($unit_for_hansometable); ?>
				},
			},
			
			{
				data: 'qty_to_consume',
				type: 'numeric',
				numericFormat: {
					pattern: '0,0.00',
				},
			},
			{
				data: 'qty_reserved',
				type: 'numeric',
				numericFormat: {
					pattern: '0,0.00',
				},
			},

			{
				data: 'qty_done',
				type: 'numeric',
				numericFormat: {
					pattern: '0,0.00',
				},
			},

			{
				type: 'text',
				data: 'lot_number',
			},
			{
				type: 'text',
				data: 'expiry_date',
			},
			{
				type: 'text',
				data: 'serial_numbers',
			},
			
			],

			colHeaders: [

				'<?php echo _l('id'); ?>',
				'<?php echo _l('product_label'); ?>',
				'<?php echo _l('unit_id'); ?>',
				'<?php echo _l('qty_to_consume'); ?>',
				'<?php echo _l('qty_reserved'); ?>',
				'<?php echo _l('qty_done'); ?>',
				'<?php echo _l('lot_number'); ?>',
				'<?php echo _l('expiry_date'); ?>',
				'<?php echo _l('wh_serial_numbers'); ?>',

				],

			data: dataObject_pu,
		});



		var data_send_mail = {};
		<?php if(isset($send_mail_approve)){ 
			?>
			data_send_mail = <?php echo json_encode($send_mail_approve); ?>;
			data_send_mail.rel_id = <?php echo new_html_entity_decode($manufacturing_order->id); ?>;
			data_send_mail.rel_type = '1';

			data_send_mail.addedfrom = <?php echo new_html_entity_decode($manufacturing_order->addedfrom); ?>;

			$.get(admin_url+'manufacturing/send_mail', data_send_mail).done(function(response){
				response = JSON.parse(response);

			}).fail(function(error) {

			});
		<?php } ?>

		SignaturePad.prototype.toDataURLAndRemoveBlanks = function() {
			var canvas = this._ctx.canvas;
       // First duplicate the canvas to not alter the original
			var croppedCanvas = document.createElement('canvas');
			croppedCtx = croppedCanvas.getContext('2d');

			croppedCanvas.width = canvas.width;
			croppedCanvas.height = canvas.height;
			croppedCtx.drawImage(canvas, 0, 0);

       // Next do the actual cropping
			var w = croppedCanvas.width,
			h = croppedCanvas.height,
			pix = {
				x: [],
				y: []
			},
			imageData = croppedCtx.getImageData(0, 0, croppedCanvas.width, croppedCanvas.height),
			x, y, index;

			for (y = 0; y < h; y++) {
				for (x = 0; x < w; x++) {
					index = (y * w + x) * 4;
					if (imageData.data[index + 3] > 0) {
						pix.x.push(x);
						pix.y.push(y);

					}
				}
			}
			pix.x.sort(function(a, b) {
				return a - b
			});
			pix.y.sort(function(a, b) {
				return a - b
			});
			var n = pix.x.length - 1;

			w = pix.x[n] - pix.x[0];
			h = pix.y[n] - pix.y[0];
			var cut = croppedCtx.getImageData(pix.x[0], pix.y[0], w, h);

			croppedCanvas.width = w;
			croppedCanvas.height = h;
			croppedCtx.putImageData(cut, 0, 0);

			return croppedCanvas.toDataURL();
		};

		var canvas = document.getElementById("signature");
		signaturePad = new SignaturePad(canvas, {
			maxWidth: 2,
			onEnd:function(){
				signaturePadChanged();
			}
		});

		$('#identityConfirmationForm').on('submit', function () {
			signaturePadChanged();
		});


	})(jQuery);

	function firstRowRenderer(instance, td, row, col, prop, value, cellProperties) {
		
		"use strict";
		Handsontable.renderers.TextRenderer.apply(this, arguments);
		td.style.background = '#fff';
		if(data_color[row] != undefined){
			td.style.color = data_color[row];
			td.className = 'htRight';

		}
	}

	function customDropdownRenderer(instance, td, row, col, prop, value, cellProperties) {
		"use strict";
		var selectedId;
		var optionsList = cellProperties.chosenOptions.data;

		if(typeof optionsList === "undefined" || typeof optionsList.length === "undefined" || !optionsList.length) {
			Handsontable.cellTypes.text.renderer(instance, td, row, col, prop, value, cellProperties);
			return td;
		}

		var values = (value + "").split("|");
		value = [];
		for (var index = 0; index < optionsList.length; index++) {

			if (values.indexOf(optionsList[index].id + "") > -1) {
				selectedId = optionsList[index].id;
				value.push(optionsList[index].label);
			}
		}
		value = value.join(", ");

		Handsontable.cellTypes.text.renderer(instance, td, row, col, prop, value, cellProperties);
		return td;
	}

	
	$('.mark_as_todo').on('click', function() {
		"use strict";

		var id = $("input[name='id']").val();
		$.get(admin_url + 'manufacturing/mo_mark_as_todo/' + id+'/mark_as_todo', function (response) {
			if(response.status == 'warning'){
				alert_float(response.status, response.message, 5000);
				setTimeout(function(){ location.reload(); }, 5000);
			}else{
				alert_float(response.status, response.message);
				location.reload();
			}
		}, 'json');

	});

	$('.mark_check_availability').on('click', function() {
		"use strict";

		var id = $("input[name='id']").val();
		$.get(admin_url + 'manufacturing/mo_mark_as_todo/' + id+'/check_availability', function (response) {
			if(response.status == 'warning'){
				alert_float(response.status, response.message, 5000);
				setTimeout(function(){ location.reload(); }, 5000);
			}else{
				alert_float(response.status, response.message);
				location.reload();
			}
		}, 'json');

	});

	$('.mark_as_done').on('click', function() {

		"use strict";

		$('#show_detail').modal('show');

	});


	$('.btn_mark_as_done').on('click', function() {
		"use strict";


		var id = $("input[name='id']").val();
		var quantity = $("input[name='change_product_qty']").val();

		if(quantity != undefined && quantity != ''){

			$('.mark_as_done').attr( "disabled", "disabled" );
			$('.btn_mark_as_done').attr( "disabled", "disabled" );
			var data = {};
			data.id = id;
			data.quantity = quantity;
			
			$.post(admin_url + 'manufacturing/mo_mark_as_done', data).done(function(response) {
				response = JSON.parse(response);

				location.reload();
			});
		}else{
			alert_float('warning', '<?php echo _l('please_enter_quantity_produced'); ?>');
		}

	});

	$('.mark_as_planned').on('click', function() {
		"use strict";
		var id = $("input[name='id']").val();
		$.get(admin_url + 'manufacturing/mo_mark_as_planned/' + id, function (response) {
			alert_float(response.status, response.message);

			location.reload();
		}, 'json');

	});

	$('.mark_as_unreserved').on('click', function() {
		"use strict";

		$('.mark_as_unreserved').attr( "disabled", "disabled" );

		var id = $("input[name='id']").val();
		$.get(admin_url + 'manufacturing/mo_mark_as_unreserved/' + id, function (response) {
			alert_float(response.status, response.message);

			location.reload();
		}, 'json');

	});
	
	$('.mark_as_cancel').on('click', function() {
		"use strict";

		$('.mark_as_cancel').attr( "disabled", "disabled" );

		var id = $("input[name='id']").val();
		$.get(admin_url + 'manufacturing/mo_mark_as_cancel/' + id, function (response) {
			alert_float(response.status, response.message);

			location.reload();
		}, 'json');

	});
	
	$('.mo_create_purchase_request').on('click', function() {
		"use strict";

		$('.mo_create_purchase_request').attr( "disabled", "disabled" );

		var id = $("input[name='id']").val();
		$.get(admin_url + 'manufacturing/mo_create_purchase_request/' + id, function (response) {
			alert_float(response.status, response.message);

			location.reload();
		}, 'json');

	});

	function signaturePadChanged() {
		"use strict";

		var input = document.getElementById('signatureInput');
		var $signatureLabel = $('#signatureLabel');
		$signatureLabel.removeClass('text-danger');

		if (signaturePad.isEmpty()) {
			$signatureLabel.addClass('text-danger');
			input.value = '';
			return false;
		}

		$('#signatureInput-error').remove();
		var partBase64 = signaturePad.toDataURLAndRemoveBlanks();
		partBase64 = partBase64.split(',')[1];
		input.value = partBase64;
	}


	function signature_clear(){
		"use strict";
		var canvas = document.getElementById("signature");
		var signaturePad = new SignaturePad(canvas, {
			maxWidth: 2,
			onEnd:function(){

			}
		});
		signaturePad.clear();
		$('input[name="signature"]').val('');

	}

	function sign_request(id){
		"use strict";
		var signature_val = $('input[name="signature"]').val();
		if(signature_val.length > 0){
			change_request_approval_status(id,1, true);
			$('.sign_request_class').prop('disabled', true);
			$('.sign_request_class').html('<?php echo _l('wait_text'); ?>');
			$('.clear').prop('disabled', true);
		}else{
			alert_float('warning', '<?php echo _l('please_sign_the_form'); ?>');
			$('.sign_request_class').prop('disabled', false);
			$('.clear').prop('disabled', false);
		}
	}
	function approve_request(id){
		"use strict";
		change_request_approval_status(id,1);
	}
	function deny_request(id){
		"use strict";
		change_request_approval_status(id,-1);
	}

	function change_request_approval_status(id, status, sign_code){
		"use strict";

		var data = {};
		data.rel_id = id;
		data.rel_type = '1';

		data.approve = status;
		if(sign_code == true){
			data.signature = $('input[name="signature"]').val();
		}else{
			data.note = $('textarea[name="reason"]').val();
		}
		$.post(admin_url + 'manufacturing/approve_request/' + id, data).done(function(response){
			response = JSON.parse(response); 
			if (response.success === true || response.success == 'true') {
				alert_float('success', response.message);
				window.location.reload();
			}
		});
	}

	function send_request_approve(id){
		"use strict";
		var data = {};
		data.rel_id = <?php echo new_html_entity_decode($manufacturing_order->id); ?>;
		data.rel_type = '1';

		data.addedfrom = <?php echo new_html_entity_decode($manufacturing_order->addedfrom); ?>;
		$("body").append('<div class="dt-loader"></div>');
		$.post(admin_url + 'manufacturing/send_request_approve', data).done(function(response){
			response = JSON.parse(response);
			$("body").find('.dt-loader').remove();
			if (response.success === true || response.success == 'true') {
				alert_float('success', response.message);
				window.location.reload();
			}else{
				alert_float('warning', response.message);
				window.location.reload();
			}
		});
	}

	function accept_action() {
		"use strict";
		$('#add_action').modal('show');
	}


</script>