<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop">
              <i class="fa fa-map-marker"></i> <?= _l('attendance_project_locations'); ?>
            </h4>
            <p class="text-muted">
              <?= _l('attendance_project_locations_help'); ?>
            </p>
            <hr />

            <?php if (count($projects) === 0) { ?>
              <p class="text-muted text-center mtop15"><?= _l('attendance_no_projects'); ?></p>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th><?= _l('project'); ?></th>
                      <th><?= _l('attendance_geofence'); ?></th>
                      <th><?= _l('attendance_latitude'); ?></th>
                      <th><?= _l('attendance_longitude'); ?></th>
                      <th><?= _l('attendance_radius_metres'); ?></th>
                      <th><?= _l('address'); ?></th>
                      <th><?= _l('options'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($projects as $project) {
                        $pid = (int) $project['id'];
                        $loc = isset($locations[$pid]) ? $locations[$pid] : null;
                        // A row with coordinates and active = 1 is the only thing that
                        // actually restricts attendance.
                        $enforced = $loc && $loc['latitude'] !== null && (int) $loc['active'] === 1;
                        ?>
                      <tr>
                        <td><?= e($project['name']); ?></td>
                        <td>
                          <?php if ($enforced) { ?>
                            <span class="label label-success"><?= _l('attendance_geofence_on'); ?></span>
                          <?php } elseif ($loc) { ?>
                            <span class="label label-warning"><?= _l('attendance_geofence_suspended'); ?></span>
                          <?php } else { ?>
                            <span class="label label-default"><?= _l('attendance_geofence_off'); ?></span>
                          <?php } ?>
                        </td>
                        <td><?= $loc ? e($loc['latitude']) : '-'; ?></td>
                        <td><?= $loc ? e($loc['longitude']) : '-'; ?></td>
                        <td><?= $loc ? e($loc['radius_metres']) : '-'; ?></td>
                        <td><?= $loc && $loc['address'] ? e($loc['address']) : '-'; ?></td>
                        <td>
                          <button type="button" class="btn btn-default btn-icon edit-location"
                                  data-project-id="<?= $pid; ?>"
                                  data-project-name="<?= e($project['name']); ?>"
                                  data-latitude="<?= $loc ? e($loc['latitude']) : ''; ?>"
                                  data-longitude="<?= $loc ? e($loc['longitude']) : ''; ?>"
                                  data-radius="<?= $loc ? e($loc['radius_metres']) : 200; ?>"
                                  data-address="<?= $loc ? e($loc['address']) : ''; ?>"
                                  data-active="<?= $loc ? (int) $loc['active'] : 1; ?>">
                            <i class="fa fa-pencil"></i>
                          </button>
                          <?php if ($loc) { ?>
                            <a href="<?= admin_url('attendance_locations/delete/' . $pid); ?>"
                               class="btn btn-danger btn-icon _delete"
                               title="<?= _l('attendance_clear_geofence'); ?>">
                              <i class="fa fa-remove"></i>
                            </a>
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

<div class="modal fade" id="location-modal" tabindex="-1" role="dialog" aria-labelledby="location-modal-title">
  <div class="modal-dialog" role="document">
    <?= form_open(admin_url('attendance_locations'), ['id' => 'location-form']); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="location-modal-title"><?= _l('attendance_set_site_location'); ?></h4>
      </div>
      <div class="modal-body">
        <?= form_hidden('project_id', ''); ?>
        <p><strong id="location-project-name"></strong></p>
        <p class="text-muted">
          <?= _l('attendance_clear_to_disable_help'); ?>
        </p>
        <hr />
        <div class="row">
          <div class="col-md-6">
            <?= render_input('latitude', 'attendance_latitude', '', 'text'); ?>
          </div>
          <div class="col-md-6">
            <?= render_input('longitude', 'attendance_longitude', '', 'text'); ?>
          </div>
        </div>
        <?= render_input('radius_metres', 'attendance_radius_metres', 200, 'number'); ?>
        <?= render_input('address', 'address', '', 'text'); ?>
        <div class="checkbox checkbox-primary">
          <input type="checkbox" name="active" id="active" value="1" checked>
          <label for="active"><?= _l('attendance_geofence_active'); ?></label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
        <button type="submit" class="btn btn-info"><?= _l('submit'); ?></button>
      </div>
    </div>
    <?= form_close(); ?>
  </div>
</div>

<?php init_tail(); ?>
<script>
(function ($) {
    "use strict";

    // Populate the modal from the row's data- attributes, so there is one form rather
    // than one per project.
    $('.edit-location').on('click', function () {
        var $b = $(this);
        var $f = $('#location-form');

        $f.find('[name="project_id"]').val($b.data('project-id'));
        $f.find('[name="latitude"]').val($b.data('latitude'));
        $f.find('[name="longitude"]').val($b.data('longitude'));
        $f.find('[name="radius_metres"]').val($b.data('radius'));
        $f.find('[name="address"]').val($b.data('address'));
        $f.find('[name="active"]').prop('checked', String($b.data('active')) === '1');

        $('#location-project-name').text($b.data('project-name'));
        $('#location-modal').modal('show');
    });
})(jQuery);
</script>
