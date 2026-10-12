<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php echo form_open_multipart(admin_url('quality/prefix_number'),array('class'=>'prefix_number','autocomplete'=>'off')); ?>

<div class="row">
	<div class="col-md-12">
		<h4 class="bold"><?php echo _l('quality_prefixes') ?></h4>
		<hr class="hr-color">
	</div>
</div>

<h4 class="bold"><?php echo _l('quality_control_point') ?></h4>
<div class="form-group">
	<label><?php echo _l('quality_control_point_prefix'); ?></label>
	<div  class="form-group" app-field-wrapper="quality_control_point_prefix">
		<input type="text" id="quality_control_point_prefix" name="quality_control_point_prefix" class="form-control" value="<?php echo get_option('quality_control_point_prefix'); ?>">
	</div>
</div>

<div class="form-group">
	<label><?php echo _l('quality_control_point_number'); ?></label>
	<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('settings_sales_next_invoice_number_tooltip'); ?>"></i>
	<div  class="form-group" app-field-wrapper="quality_control_point_number">
		<input type="number" min="0" id="quality_control_point_number" name="quality_control_point_number" class="form-control" value="<?php echo get_option('quality_control_point_number'); ?>">
	</div>
</div>

<div class="form-group">
	<label for="quality_control_point_number_format" class="control-label clearfix"><?php echo _l('quality_control_point_number_format'); ?></label>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_control_point_number_format" value="1" id="quality_control_point_number_based" <?php if (get_option('quality_control_point_number_format') == '1') {
			echo 'checked';
		} ?>>
		<label for="quality_control_point_number_based"><?php echo _l('settings_sales_estimate_number_format_number_based'); ?></label>
	</div>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_control_point_number_format" value="2" id="quality_control_point_year_based" <?php if (get_option('quality_control_point_number_format') == '2') {
			echo 'checked';
		} ?>>
		<label for="quality_control_point_year_based"><?php echo _l('settings_sales_estimate_number_format_year_based'); ?>(YYYY/000001)</label>
	</div>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_control_point_number_format" value="3" id="quality_control_point_short_year_based" <?php if (get_option('quality_control_point_number_format') == '3') {
			echo 'checked';
		} ?>>
		<label for="quality_control_point_short_year_based">000001-YY</label>
	</div>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_control_point_number_format" value="4" id="quality_control_point_year_month_based" <?php if (get_option('quality_control_point_number_format') == '4') {
			echo 'checked';
		} ?>>
		<label for="quality_control_point_year_month_based">000001/MM/YYYY</label>
	</div>
	<hr />
</div>

<div class="clearfix"></div>

<h4 class="bold"><?php echo _l('quality_check') ?></h4>
<div class="form-group">
	<label><?php echo _l('quality_check_prefix'); ?></label>
	<div  class="form-group" app-field-wrapper="quality_check_prefix">
		<input type="text" id="quality_check_prefix" name="quality_check_prefix" class="form-control" value="<?php echo get_option('quality_check_prefix'); ?>">
	</div>
</div>

<div class="form-group">
	<label><?php echo _l('quality_check_number'); ?></label>
	<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('settings_sales_next_invoice_number_tooltip'); ?>"></i>
	<div  class="form-group" app-field-wrapper="quality_check_number">
		<input type="number" min="0" id="quality_check_number" name="quality_check_number" class="form-control" value="<?php echo get_option('quality_check_number'); ?>">
	</div>
</div>

<div class="form-group">
	<label for="quality_check_number_format" class="control-label clearfix"><?php echo _l('quality_check_number_format'); ?></label>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_check_number_format" value="1" id="quality_check_number_based" <?php if (get_option('quality_check_number_format') == '1') {
			echo 'checked';
		} ?>>
		<label for="quality_check_number_based"><?php echo _l('settings_sales_estimate_number_format_number_based'); ?></label>
	</div>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_check_number_format" value="2" id="quality_check_year_based" <?php if (get_option('quality_check_number_format') == '2') {
			echo 'checked';
		} ?>>
		<label for="quality_check_year_based"><?php echo _l('settings_sales_estimate_number_format_year_based'); ?>(YYYY/000001)</label>
	</div>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_check_number_format" value="3" id="quality_check_short_year_based" <?php if (get_option('quality_check_number_format') == '3') {
			echo 'checked';
		} ?>>
		<label for="quality_check_short_year_based">000001-YY</label>
	</div>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_check_number_format" value="4" id="quality_check_year_month_based" <?php if (get_option('quality_check_number_format') == '4') {
			echo 'checked';
		} ?>>
		<label for="quality_check_year_month_based">000001/MM/YYYY</label>
	</div>
	<hr />
</div>

<div class="clearfix"></div>

<h4 class="bold"><?php echo _l('quality_alert') ?></h4>
<div class="form-group">
	<label><?php echo _l('quality_alert_prefix'); ?></label>
	<div  class="form-group" app-field-wrapper="quality_alert_prefix">
		<input type="text" id="quality_alert_prefix" name="quality_alert_prefix" class="form-control" value="<?php echo get_option('quality_alert_prefix'); ?>">
	</div>
</div>

<div class="form-group">
	<label><?php echo _l('quality_alert_number'); ?></label>
	<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('settings_sales_next_invoice_number_tooltip'); ?>"></i>
	<div  class="form-group" app-field-wrapper="quality_alert_number">
		<input type="number" min="0" id="quality_alert_number" name="quality_alert_number" class="form-control" value="<?php echo get_option('quality_alert_number'); ?>">
	</div>
</div>

<div class="form-group">
	<label for="quality_alert_number_format" class="control-label clearfix"><?php echo _l('quality_alert_number_format'); ?></label>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_alert_number_format" value="1" id="quality_alert_number_based" <?php if (get_option('quality_alert_number_format') == '1') {
			echo 'checked';
		} ?>>
		<label for="quality_alert_number_based"><?php echo _l('settings_sales_estimate_number_format_number_based'); ?></label>
	</div>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_alert_number_format" value="2" id="quality_alert_year_based" <?php if (get_option('quality_alert_number_format') == '2') {
			echo 'checked';
		} ?>>
		<label for="quality_alert_year_based"><?php echo _l('settings_sales_estimate_number_format_year_based'); ?>(YYYY/000001)</label>
	</div>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_alert_number_format" value="3" id="quality_alert_short_year_based" <?php if (get_option('quality_alert_number_format') == '3') {
			echo 'checked';
		} ?>>
		<label for="quality_alert_short_year_based">000001-YY</label>
	</div>
	<div class="radio radio-primary radio-inline">
		<input type="radio" name="quality_alert_number_format" value="4" id="quality_alert_year_month_based" <?php if (get_option('quality_alert_number_format') == '4') {
			echo 'checked';
		} ?>>
		<label for="quality_alert_year_month_based">000001/MM/YYYY</label>
	</div>
	<hr />
</div>

<div class="clearfix"></div>


<div class="btn-bottom-toolbar text-right">
	<?php if(has_permission('quality_setting', '', 'create') || has_permission('quality_setting', '', 'edit') ){ ?>
		<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
	<?php } ?>
</div>
<?php echo form_close(); ?>

</body>
</html>


