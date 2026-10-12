<script type="text/javascript">
	report_by_quality_check('report_by_quality_check', '', '');
	report_by_quality_alert('report_by_quality_alert', '', '');
	function report_by_quality_check(id, value, title_c){
		'use strict';

		var months_report = $('select[name="mo_months-report"]').val(); 
		var report_from = $('input[name="mo_report-from"]').val();
		var report_to = $('input[name="mo_report-to"]').val();

		requestGetJSON('quality/report_by_quality_check?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to).done(function (response) {

			/*get data for hightchart*/

			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fill: 'black'
					}
				},
				colors: [ '#119EFA','#15f34f','#ef370dc7','#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263','#6AF9C4','#50B432','#0d91efc7','#ED561B']
			});
			Highcharts.chart(id, {
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php echo _l('quality_quality_check_by_status'); ?>'
				},
				credits: {
					enabled: false
				},
				xAxis: {
					categories: response.categories,
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: '<?php echo _l('quality_quantity'); ?>'
					}
				},
				
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					}
				},
				series: [{
					name: '<?php echo _l('quality_quantity'); ?>',
					colorByPoint: true,
					data: response.estimated_hours

				}
				]
			});


		});
	}

	function report_by_quality_alert(id, value, title_c){
		'use strict';

		var months_report = $('select[name="mo_months-report"]').val(); 
		var report_from = $('input[name="mo_report-from"]').val();
		var report_to = $('input[name="mo_report-to"]').val();

		requestGetJSON('quality/report_by_quality_alert?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to).done(function (response) {

			/*get data for hightchart*/

			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fill: 'black'
					}
				},
				colors: [ '#119EFA','#15f34f','#ef370dc7','#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263','#6AF9C4','#50B432','#0d91efc7','#ED561B']
			});
			Highcharts.chart(id, {
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php echo _l('quality_quality_alert_by_status'); ?>'
				},
				credits: {
					enabled: false
				},
				xAxis: {
					categories: response.categories,
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: '<?php echo _l('quality_quantity'); ?>'
					}
				},
				
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					}
				},
				series: [{
					name: '<?php echo _l('quality_quantity'); ?>',
					colorByPoint: true,
					data: response.estimated_hours

				}
				]
			});


		});
	}

	var mo_report_from = $('input[name="mo_report-from"]');
	var mo_report_to = $('input[name="mo_report-to"]');
	var mo_date_range = $('#mo_date-range');

	$('select[name="mo_months-report"]').on('change', function() {
		'use strict';

		var val = $(this).val();
		mo_report_to.attr('disabled', true);
		mo_report_to.val('');
		mo_report_from.val('');
		if (val == 'custom') {
			mo_date_range.addClass('fadeIn').removeClass('hide');
			return;
		} else {
			if (!mo_date_range.hasClass('hide')) {
				mo_date_range.removeClass('fadeIn').addClass('hide');
			}
		}
		// repair_job_by_month_gen_reports();
		quality_check_gen_reports();
	});

	mo_report_from.on('change', function() {
		'use strict';

		var val = $(this).val();
		var report_to_val = mo_report_to.val();
		if (val != '') {
			mo_report_to.attr('disabled', false);
			if (report_to_val != '') {
				quality_alert_gen_reports();
				quality_check_gen_reports();

			}
		} else {
			mo_report_to.attr('disabled', true);
		}
	});

	mo_report_to.on('change', function() {
		'use strict';

		var val = $(this).val();
		if (val != '') {
			quality_alert_gen_reports();
			quality_check_gen_reports();

		}
	});

	function  quality_check_gen_reports() {
		'use strict';
		report_by_quality_check('report_by_quality_check', '', '');
	}

	function  quality_alert_gen_reports() {
		'use strict';
		report_by_quality_alert('report_by_quality_alert', '', '');
	}
	
</script>