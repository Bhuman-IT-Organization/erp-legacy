<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-solid fa-clipboard-check"></i> <?= _l('empdoc_review_queue'); ?></h4>
            <p class="text-muted"><?= _l('empdoc_review_help'); ?></p>

            <?php if (count($pending) === 0) { ?>
              <p class="text-muted text-center mtop15"><?= _l('empdoc_nothing_to_review'); ?></p>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th><?= _l('staff'); ?></th>
                      <th><?= _l('empdoc_type'); ?></th>
                      <th><?= _l('empdoc_number'); ?></th>
                      <th><?= _l('empdoc_expiry_date'); ?></th>
                      <th><?= _l('empdoc_file'); ?></th>
                      <th><?= _l('empdoc_uploaded_col'); ?></th>
                      <th><?= _l('options'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pending as $doc) { ?>
                      <tr>
                        <td><?= e((string) $doc['staff_name']); ?></td>
                        <td><?= e((string) $doc['type_name']); ?></td>
                        <td><?= e((string) $doc['document_number']) ?: '&mdash;'; ?></td>
                        <td><?= $doc['expiry_date'] ? _d($doc['expiry_date']) : '&mdash;'; ?></td>
                        <td>
                          <?php if (!empty($doc['file_name'])) { ?>
                            <a href="<?= admin_url('employee_documents/download/' . $doc['id']); ?>"
                               class="btn btn-default btn-icon"><i class="fa fa-download"></i></a>
                          <?php } else { echo '&mdash;'; } ?>
                        </td>
                        <td><?= _dt($doc['uploaded_at']); ?></td>
                        <td>
                          <?= form_open(admin_url('employee_documents/review/' . $doc['id']), ['class' => 'form-inline']); ?>
                            <input type="text" name="reason" class="form-control input-sm"
                                   placeholder="<?= _l('empdoc_reject_reason'); ?>" style="width:140px;">
                            <button type="submit" name="decision" value="approve"
                                    class="btn btn-success btn-sm"><?= _l('empdoc_approve'); ?></button>
                            <button type="submit" name="decision" value="reject"
                                    class="btn btn-danger btn-sm"><?= _l('empdoc_reject'); ?></button>
                          <?= form_close(); ?>
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
<?php init_tail(); ?>
</body>
</html>
