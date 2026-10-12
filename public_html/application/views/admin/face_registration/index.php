<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                            <i class="fa fa-user-circle"></i> <?= $title; ?>
                        </h4>
                        <hr />
                        <div class="table-responsive">
                            <table class="table table-striped dt-table" data-order-col="0" data-order-type="asc">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Staff Member</th>
                                        <th>Email</th>
                                        <th>Face Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_members as $member) { ?>
                                    <tr>
                                        <td><?= $member['staffid']; ?></td>
                                        <td>
                                            <?= staff_profile_image($member['staffid'], ['staff-profile-image-small', 'mright5']); ?>
                                            <?= $member['firstname'] . ' ' . $member['lastname']; ?>
                                        </td>
                                        <td><?= $member['email']; ?></td>
                                        <td>
                                            <?php if ($member['has_face']) { ?>
                                                <span class="label label-success"><i class="fa fa-check"></i> Registered</span>
                                            <?php } else { ?>
                                                <span class="label label-danger"><i class="fa fa-times"></i> Not Registered</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <a href="<?= admin_url('face_registration/register/' . $member['staffid']); ?>" 
                                               class="btn btn-<?= $member['has_face'] ? 'default' : 'primary'; ?> btn-sm">
                                                <i class="fa fa-camera"></i>
                                                <?= $member['has_face'] ? 'Update Face' : 'Register Face'; ?>
                                            </a>
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
</div>
<?php init_tail(); ?>
</body>
</html>
