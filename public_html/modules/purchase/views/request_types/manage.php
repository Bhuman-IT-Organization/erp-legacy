<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <a href="<?php echo admin_url('purchase/request_type'); ?>" class="btn btn-info pull-left mright10">
                                <i class="fa fa-plus"></i> <?php echo _l('add_request_type'); ?>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />

                        <table class="table dt-table">
                            <thead>
                                <tr>
                                    <th><?php echo _l('id'); ?></th>
                                    <th><?php echo _l('request_type_name'); ?></th>
                                    <th><?php echo _l('type_code'); ?></th>
                                    <th>GL Account Debit</th>
                                    <th>GL Account Credit</th>
                                    <th><?php echo _l('options'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($request_types as $rt){ 
                                $debit_label = '';
                                $credit_label = '';
                                $CI =& get_instance();
                                if(!empty($rt['ledger_account'])){
                                    $CI->db->where('id', $rt['ledger_account']);
                                    $acc = $CI->db->get(db_prefix().'acc_accounts')->row();
                                    if($acc){
                                        $debit_label = (!empty($acc->number) ? $acc->number . ' - ' : '') . (!empty($acc->name) ? $acc->name : _l($acc->key_name));
                                    }
                                }
                                if(!empty($rt['credit_ledger'])){
                                    $CI->db->where('id', $rt['credit_ledger']);
                                    $acc2 = $CI->db->get(db_prefix().'acc_accounts')->row();
                                    if($acc2){
                                        $credit_label = (!empty($acc2->number) ? $acc2->number . ' - ' : '') . (!empty($acc2->name) ? $acc2->name : _l($acc2->key_name));
                                    }
                                }
                            ?>
                                <tr>
                                    <td><?php echo $rt['id']; ?></td>
                                    <td><?php echo htmlspecialchars($rt['name']); ?></td>
                                    <td><?php echo htmlspecialchars($rt['type_code']); ?></td>
                                    <td><?php echo htmlspecialchars($debit_label); ?></td>
                                    <td><?php echo htmlspecialchars($credit_label); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('purchase/request_type/'.$rt['id']); ?>" class="btn btn-default btn-icon"><i class="fa fa-pencil-square-o"></i></a>
                                        <a href="<?php echo admin_url('purchase/delete_request_type/'.$rt['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
