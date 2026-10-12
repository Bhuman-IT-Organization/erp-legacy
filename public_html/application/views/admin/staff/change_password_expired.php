<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>

<body class="tw-bg-neutral-100 login_admin">

    <div class="tw-max-w-md tw-mx-auto tw-pt-24 authentication-form-wrapper tw-relative tw-z-20">
        <div class="company-logo text-center">
            <?php get_dark_company_logo(); ?>
        </div>

        <div class="text-center tw-mb-5">
            <h1 class="tw-text-neutral-800 tw-text-2xl tw-font-bold tw-mb-1">
                <?= _l('password_expired_heading'); ?>
            </h1>
            <p class="tw-text-neutral-600">
                <?= _l('password_expired_message', $password_expiry_days); ?>
            </p>
        </div>

        <div class="tw-bg-white tw-mx-2 sm:tw-mx-6 tw-py-8 tw-px-6 sm:tw-px-8 tw-shadow-sm tw-rounded-lg tw-border tw-border-solid tw-border-neutral-600/20">

            <?php $this->load->view('authentication/includes/alerts'); ?>

            <?= form_open(admin_url('staff/change_password_expired')); ?>

            <?= validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

            <div class="alert alert-warning text-center">
                <i class="fa fa-exclamation-triangle"></i>
                <?= _l('password_expired_warning'); ?>
            </div>

            <div class="form-group">
                <label for="oldpassword" class="control-label !tw-mb-3">
                    <?= _l('staff_edit_profile_change_old_password'); ?>
                </label>
                <input type="password" id="oldpassword" name="oldpassword" class="form-control" autofocus="1" required>
            </div>

            <div class="form-group tw-mt-6">
                <label for="newpassword" class="control-label !tw-mb-3">
                    <?= _l('staff_edit_profile_change_new_password'); ?>
                </label>
                <input type="password" id="newpassword" name="newpassword" class="form-control" required>
            </div>

            <div class="form-group tw-mt-6">
                <label for="newpasswordr" class="control-label !tw-mb-3">
                    <?= _l('staff_edit_profile_change_repeat_new_password'); ?>
                </label>
                <input type="password" id="newpasswordr" name="newpasswordr" class="form-control" required>
            </div>

            <div class="tw-mt-6">
                <button type="submit" class="btn btn-primary btn-block tw-font-semibold tw-py-2">
                    <?= _l('password_expired_change_button'); ?>
                </button>
            </div>

            <div class="tw-mt-4 text-center">
                <a href="<?= admin_url('authentication/logout'); ?>" class="text-muted">
                    <i class="fa fa-sign-out"></i> <?= _l('nav_logout'); ?>
                </a>
            </div>

            <?= form_close(); ?>
        </div>
    </div>

</body>

</html>
