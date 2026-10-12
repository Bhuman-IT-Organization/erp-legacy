<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading" />

                        <?php echo form_open($this->uri->uri_string(), array('id' => 'request-type-form')); ?>

                        <?php $name = isset($request_type) ? $request_type->name : ''; ?>
                        <?php echo render_input('name', 'request_type_name', $name, 'text', array('required' => true)); ?>

                        <?php $type_code = isset($request_type) ? $request_type->type_code : ''; ?>
                        <?php echo render_input('type_code', 'type_code', $type_code, 'text', array('required' => true)); ?>

                        <div class="form-group select-placeholder">
                            <label for="ledger_account" class="control-label">GL Account Debit</label>
                            <select name="ledger_account" id="ledger_account" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                <option value=""></option>
                                <?php foreach($expense_accounts as $acc) { 
                                    $acc_label = '';
                                    if(!empty($acc['number'])) $acc_label .= $acc['number'] . ' - ';
                                    if(!empty($acc['name'])) $acc_label .= $acc['name'];
                                    elseif(!empty($acc['key_name'])) $acc_label .= _l($acc['key_name']);
                                    else $acc_label .= 'Account #' . $acc['id'];
                                    $selected = (isset($request_type) && $request_type->ledger_account == $acc['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $acc['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($acc_label); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group select-placeholder">
                            <label for="credit_ledger" class="control-label">GL Account Credit</label>
                            <select name="credit_ledger" id="credit_ledger" class="selectpicker" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                <option value=""></option>
                                <?php foreach($expense_accounts as $acc) { 
                                    $acc_label = '';
                                    if(!empty($acc['number'])) $acc_label .= $acc['number'] . ' - ';
                                    if(!empty($acc['name'])) $acc_label .= $acc['name'];
                                    elseif(!empty($acc['key_name'])) $acc_label .= _l($acc['key_name']);
                                    else $acc_label .= 'Account #' . $acc['id'];
                                    $selected = (isset($request_type) && isset($request_type->credit_ledger) && $request_type->credit_ledger == $acc['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $acc['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($acc_label); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="text-right">
                            <a href="<?php echo admin_url('purchase/request_types'); ?>" class="btn btn-default mright5"><?php echo _l('cancel'); ?></a>
                            <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
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
        appValidateForm($('#request-type-form'), {name: 'required', type_code: 'required'});
    });
</script>
</body>
</html>
