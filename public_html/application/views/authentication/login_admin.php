<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>

<body class="tw-bg-neutral-100 login_admin">

    <div class="tw-max-w-md tw-mx-auto tw-pt-24 authentication-form-wrapper tw-relative tw-z-20">
        <div class="company-logo text-center">
            <?php get_dark_company_logo(); ?>
        </div>

        <div class=" text-center tw-mb-5">
            <h1 class="tw-text-neutral-800 tw-text-2xl tw-font-bold tw-mb-1">
                <?= _l('admin_auth_login_heading'); ?>
            </h1>
            <p class="tw-text-neutral-600">
                <?= _l('welcome_back_sign_in'); ?>
            </p>
        </div>

        <div
            class="tw-bg-white tw-mx-2 sm:tw-mx-6 tw-py-8 tw-px-6 sm:tw-px-8 tw-shadow-sm tw-rounded-lg tw-border tw-border-solid tw-border-neutral-600/20">

            <?php $this->load->view('authentication/includes/alerts'); ?>

            <?= form_open($this->uri->uri_string()); ?>

            <?= validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

            <?php hooks()->do_action('after_admin_login_form_start'); ?>

            <div class="form-group">
                <label for="email" class="control-label !tw-mb-3">
                    <?= _l('admin_auth_login_email'); ?>
                </label>
                <input type="email" id="email" name="email" class="form-control" autofocus="1">
            </div>

            <div class="form-group tw-mt-8">
                <span class="tw-inline-flex tw-justify-between tw-items-end tw-w-full tw-mb-3">
                    <label for="password" class="control-label !tw-m-0">
                        <?= _l('admin_auth_login_password'); ?>
                    </label>
                    <a href="<?= admin_url('authentication/forgot_password'); ?>"
                        class="text-muted">
                        <?= _l('admin_auth_login_fp'); ?>
                    </a>
                </span>

                <input type="password" id="password" name="password" class="form-control">
            </div>

            <?php if (show_recaptcha()) { ?>
            <div class="g-recaptcha tw-mb-4"
                data-sitekey="<?= get_option('recaptcha_site_key'); ?>">
            </div>
            <?php } ?>

            <div class="form-group">
                <div class="checkbox checkbox-inline">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">
                        <?= _l('admin_auth_login_remember_me'); ?></label>
                </div>
            </div>

            <div class="tw-mt-6">
                <button type="submit" class="btn btn-primary btn-block tw-font-semibold tw-py-2">
                    <?= _l('admin_auth_login_button'); ?>
                </button>
            </div>

            <?php
            $CI =& get_instance();
            $CI->config->load('ldap', true);
            $ldap_enabled = $CI->config->item('ldap_enabled', 'ldap');
            ?>

            <div class="tw-mt-4 tw-text-center">
                <div class="tw-flex tw-items-center tw-my-4">
                    <div class="tw-flex-1 tw-border-t tw-border-neutral-200"></div>
                    <span class="tw-px-3 tw-text-sm tw-text-neutral-500">OR</span>
                    <div class="tw-flex-1 tw-border-t tw-border-neutral-200"></div>
                </div>
                <button type="submit" name="ad_login" value="1" class="btn btn-default btn-block tw-font-semibold tw-py-2 tw-border tw-border-solid tw-border-neutral-300" style="background:#f0f4f8;">
                    <i class="fa fa-windows tw-mr-2" style="color:#0078d4;"></i> Sign in with Active Directory
                </button>
                <?php if ($ldap_enabled !== true) { ?>
                <p class="tw-text-xs tw-text-danger tw-mt-2 tw-mb-0">
                    <i class="fa fa-info-circle"></i> AD login is currently disabled. Enable it in config/ldap.php
                </p>
                <?php } ?>
            </div>

            <?php hooks()->do_action('before_admin_login_form_close'); ?>

            <?= form_close(); ?>
        </div>
    </div>

</body>

</html>