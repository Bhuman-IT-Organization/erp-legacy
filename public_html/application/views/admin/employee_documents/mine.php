<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop">
              <i class="fa fa-solid fa-folder-open"></i>
              <?= $is_own ? _l('empdoc_my_documents') : e($staff_name) . ' - ' . _l('empdoc_documents'); ?>
            </h4>
            <p class="text-muted"><?= _l('empdoc_folder_help'); ?></p>

            <button type="button" class="btn btn-info mbot15" data-toggle="modal" data-target="#doc-upload">
              <i class="fa fa-upload"></i> <?= _l('empdoc_upload'); ?>
            </button>

            <?php if (count($documents) === 0) { ?>
              <p class="text-muted text-center mtop15"><?= _l('empdoc_none'); ?></p>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th><?= _l('empdoc_type'); ?></th>
                      <th><?= _l('empdoc_number'); ?></th>
                      <th><?= _l('empdoc_issue_date'); ?></th>
                      <th><?= _l('empdoc_expiry_date'); ?></th>
                      <th><?= _l('status'); ?></th>
                      <th><?= _l('empdoc_file'); ?></th>
                      <th><?= _l('options'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($documents as $doc) {
                        $expired = $doc['expiry_date'] && strtotime($doc['expiry_date']) < time();
                        $soon = $doc['expiry_date'] && !$expired
                            && strtotime($doc['expiry_date']) < strtotime('+30 days'); ?>
                      <tr class="<?= $doc['status'] === 'superseded' ? 'text-muted' : ''; ?>">
                        <td><?= e((string) $doc['type_name']); ?></td>
                        <td><?= e((string) $doc['document_number']) ?: '&mdash;'; ?></td>
                        <td><?= $doc['issue_date'] ? _d($doc['issue_date']) : '&mdash;'; ?></td>
                        <td>
                          <?php if ($doc['expiry_date']) {
                              echo _d($doc['expiry_date']);
                              if ($expired) { ?>
                                <span class="label label-danger"><?= _l('empdoc_expired'); ?></span>
                              <?php } elseif ($soon) { ?>
                                <span class="label label-warning"><?= _l('empdoc_expiring'); ?></span>
                              <?php }
                          } else {
                              echo '&mdash;';
                          } ?>
                        </td>
                        <td><?php echo empdoc_status_label($doc['status']); ?></td>
                        <td>
                          <?php if (!empty($doc['file_name'])) { ?>
                            <a href="<?= admin_url('employee_documents/download/' . $doc['id']); ?>"
                               class="btn btn-default btn-icon"><i class="fa fa-download"></i></a>
                          <?php } else { echo '&mdash;'; } ?>
                        </td>
                        <td>
                          <?php
                          $can_delete = ($is_own && $doc['status'] === 'pending_approval')
                              || is_admin() || has_permission('hrm_hr_records', '', 'edit');
                          if ($can_delete) { ?>
                            <a href="<?= admin_url('employee_documents/delete/' . $doc['id']); ?>"
                               class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                          <?php } ?>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="doc-upload" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <?= form_open_multipart(admin_url('employee_documents/upload')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title"><?= _l('empdoc_upload'); ?></h4>
      </div>
      <div class="modal-body">
        <?php if (!$is_own) { ?>
          <input type="hidden" name="staff_id" value="<?= (int) $staff_id; ?>">
        <?php } ?>

        <div class="form-group select-placeholder">
          <label for="document_type_id"><?= _l('empdoc_type'); ?></label>
          <select name="document_type_id" class="selectpicker" data-width="100%">
            <?php foreach ($document_types as $t) { ?>
              <option value="<?= (int) $t['id']; ?>"
                      data-has-number="<?= (int) $t['has_number']; ?>"
                      data-has-expiry="<?= (int) $t['has_expiry']; ?>">
                <?= e($t['name']); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <?= render_input('document_number', 'empdoc_number'); ?>
        <?= render_date_input('issue_date', 'empdoc_issue_date'); ?>
        <?= render_date_input('expiry_date', 'empdoc_expiry_date'); ?>

        <div class="form-group">
          <label for="document"><?= _l('empdoc_file'); ?></label>
          <input type="file" name="document" class="form-control"
                 accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.webp">
          <p class="text-muted"><?= _l('empdoc_file_help'); ?></p>
        </div>

        <?= render_textarea('note', 'note'); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
        <button type="submit" class="btn btn-info"><?= _l('empdoc_upload'); ?></button>
      </div>
    </div>
    <?= form_close(); ?>
  </div>
</div>

<?php init_tail(); ?>
</body>
</html>
