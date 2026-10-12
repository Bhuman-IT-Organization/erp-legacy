<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include_once APPPATH . 'views/admin/includes/helpers_bottom.php'; ?>

<?php /* Idle timeout script removed: session expiry is handled by CodeIgniter (config sess_expiration). */ ?>

<!-- Back button after a session timeout must not redisplay an admin screen.
     Cache-Control: no-store covers the HTTP cache, but a browser's back/forward
     cache can still restore a page from memory. A page restored that way fires
     pageshow with persisted = true; reloading it goes back to the server, where
     the session guard redirects to login. Only the restored-from-memory case is
     handled, so an ordinary Back with a valid session is not loaded twice. -->
<script>
(function () {
    "use strict";
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
})();
</script>

<?php hooks()->do_action('before_js_scripts_render'); ?>

<?= app_compile_scripts();

/**
 * Global function for custom field of type hyperlink
 */
echo get_custom_fields_hyperlink_js_function(); ?>
<?php
/**
 * Check for any alerts stored in session
 */
app_js_alerts();
?>

<!-- Session expiry during an AJAX call.
     AdminController answers an unauthenticated AJAX request with 401 and a
     session_expired marker rather than the login page HTML. Nothing acted on that,
     so once a session timed out the screen just appeared to break: datatables came
     back empty, saves did nothing and modals stayed blank. This sends the browser to
     the login page, matching what a normal page load already does.
     Placed after app_compile_scripts() because it needs jQuery. -->
<script>
(function ($) {
    "use strict";

    if (!$) {
        return;
    }

    var redirecting = false;

    $(document).ajaxError(function (event, jqXHR) {
        if (redirecting || !jqXHR || jqXHR.status !== 401) {
            return;
        }

        var payload = jqXHR.responseJSON;

        // responseJSON is only populated when the response parsed as JSON. A 401 from
        // a proxy or web server would not, hence the manual attempt.
        if (!payload && jqXHR.responseText) {
            try {
                payload = JSON.parse(jqXHR.responseText);
            } catch (e) {
                payload = null;
            }
        }

        // Only act on our own marker. Other endpoints may legitimately answer 401
        // for reasons that have nothing to do with the session.
        if (!payload || payload.session_expired !== true) {
            return;
        }

        // A page can have several requests in flight when the session goes. Only the
        // first needs to navigate, otherwise they fight over the location.
        redirecting = true;

        window.location.href = payload.login_url || '<?= admin_url('authentication'); ?>';
    });
})(window.jQuery);
</script>

<!-- Voice commands. The microphone button in the header calls toggleVoiceCommand(),
     which lives in this file. Loaded after app_compile_scripts() because it needs
     jQuery, the admin_url global and alert_float. Versioned on the file's own mtime so
     a change is picked up without anyone having to clear their cache. -->
<script src="<?= base_url('assets/js/voice-command.js'); ?>?v=<?= @filemtime(FCPATH . 'assets/js/voice-command.js') ?: 1; ?>"></script>

<?php
/**
 * Check pusher real time notifications
 */
if (get_option('pusher_realtime_notifications') == 1) { ?>
<script type="text/javascript">
    $(function() {
        // Enable pusher logging - don't include this in production
        // Pusher.logToConsole = true;
        <?php $pusher_options = hooks()->apply_filters('pusher_options', [['disableStats' => true]]);
    if (! isset($pusher_options['cluster']) && get_option('pusher_cluster') != '') {
        $pusher_options['cluster'] = get_option('pusher_cluster');
    }
    ?>
        var
            pusher_options = <?= json_encode($pusher_options); ?> ;
        var pusher = new Pusher(
            "<?= get_option('pusher_app_key'); ?>",
            pusher_options);
        var channel = pusher.subscribe(
            'notifications-channel-<?= get_staff_user_id(); ?>'
        );
        channel.bind('notification', function(data) {
            fetch_notifications();
        });
    });
</script>
<?php } ?>
<?php app_admin_footer(); ?>
