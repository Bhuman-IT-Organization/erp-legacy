<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Attachment preview modal for a Bid Waiver form.
 *
 * PDFs are inlined in an iframe, which is the point of the feature - the form is
 * built around attaching supplier quotations as PDF.
 */

$relative = PURCHASE_PATH . 'bid_waivers/' . $file->rel_id . '/' . rawurlencode($file->file_name);
$absolute = PURCHASE_MODULE_UPLOAD_FOLDER . '/bid_waivers/' . $file->rel_id . '/' . $file->file_name;
$extension = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
?>
<div class="modal fade _project_file modal_index" tabindex="-1" role="dialog" data-toggle="modal">
  <div class="modal-dialog full-screen-modal dialog_withd" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" onclick="bw_close_preview(); return false;"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo e($file->file_name); ?></h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12 border-right project_file_area">
            <?php if (is_image($absolute)) { ?>
            <img src="<?php echo base_url($relative); ?>" class="img img-responsive">
            <?php } elseif ($extension === 'pdf') { ?>
            <iframe src="<?php echo base_url($relative); ?>" height="100%" width="100%" frameborder="0"></iframe>
            <?php } elseif (in_array($extension, ['doc', 'docx', 'xls', 'xlsx'], true)) { ?>
            <?php // Office files have no native browser viewer, so they go through Microsoft's ?>
            <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=<?php echo urlencode(base_url($relative)); ?>"
                    width="100%" height="100%" frameborder="0"></iframe>
            <?php } else { ?>
            <p class="text-muted"><?php echo _l('preview_file'); ?></p>
            <a href="<?php echo base_url($relative); ?>" class="btn btn-info" target="_blank" download>
              <i class="fa fa-download"></i> <?php echo e($file->file_name); ?>
            </a>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  // Runs as soon as the partial is injected, so the modal opens by itself
  $('._project_file').modal('show');
</script>
