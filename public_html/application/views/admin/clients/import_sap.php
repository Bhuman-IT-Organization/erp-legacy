<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4>Import SAP Customers</h4>
            <p class="text-danger">Excel columns (Row 1 = Headers):</p>
            <p><strong>A:</strong> Customer | <strong>B:</strong> Sales Org | <strong>C:</strong> Dist Channel | <strong>D:</strong> Division | <strong>E:</strong> Sales Office | <strong>F:</strong> Name 1 (Required) | <strong>G:</strong> City | <strong>H:</strong> Search Term | <strong>I:</strong> Name 2 | <strong>J:</strong> Street | <strong>K:</strong> House Number | <strong>L:</strong> Postal Code | <strong>M:</strong> Country (ISO2) | <strong>N:</strong> Region | <strong>O:</strong> Telephone | <strong>P:</strong> Email | <strong>Q:</strong> Account Group</p>
            <hr>
            <?php echo form_open_multipart(admin_url('clients/import_sap'),array('id'=>'import_form')); ?>
              <div class="form-group">
                <label for="file_csv">Choose Excel File (.xlsx or .xls)</label>
                <input type="file" name="file_csv" id="file_csv" class="form-control" accept=".xlsx,.xls" required>
              </div>
              <button type="submit" class="btn btn-info">Import Customers</button>
              <a href="<?php echo admin_url('clients'); ?>" class="btn btn-default">Cancel</a>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
