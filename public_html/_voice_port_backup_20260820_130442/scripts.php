<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include_once APPPATH . 'views/admin/includes/helpers_bottom.php'; ?>

<!-- Idle timeout: logout after 5 minutes of no activity -->
<script>
(function(){
    var idleTimeout = 300000; // 5 minutes in milliseconds
    var heartbeatFreq = 60000; // ping every 60 seconds
    var idleTimer = null;
    var heartbeatInterval = null;
    var isIdle = false;

    function resetIdleTimer(){
        isIdle = false;
        if(idleTimer) clearTimeout(idleTimer);
        idleTimer = setTimeout(function(){
            isIdle = true;
            if(heartbeatInterval){ clearInterval(heartbeatInterval); heartbeatInterval = null; }
            window.location.href = admin_url + 'authentication/logout';
        }, idleTimeout);
    }

    function sendPing(){
        try {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', admin_url + 'authentication/ping?t=' + Date.now(), true);
            xhr.send();
        } catch(e){}
    }

    function startHeartbeat(){
        if(heartbeatInterval) return;
        heartbeatInterval = setInterval(function(){
            if(!isIdle) sendPing();
        }, heartbeatFreq);
    }

    // Listen for ANY user activity
    var events = ['mousemove','mousedown','keypress','keydown','scroll','touchstart','click','input','change','focus'];
    for(var i = 0; i < events.length; i++){
        document.addEventListener(events[i], resetIdleTimer, {passive: true});
    }
    // Also listen on window focus (user switches back to this tab)
    window.addEventListener('focus', function(){ resetIdleTimer(); sendPing(); });

    resetIdleTimer();
    startHeartbeat();
    // Initial ping to confirm session is alive
    sendPing();
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

<script type="text/javascript">
(function() {
    "use strict";
    var sessionTimeout = parseInt(app.options.session_timeout) * 1000; // Convert to milliseconds
    if (!sessionTimeout || sessionTimeout <= 0) return;

    var warningTime = 60000; // Show warning 60 seconds before expiry
    var timeoutTimer, warningTimer;
    var modalShown = false;

    function resetTimers() {
        clearTimeout(timeoutTimer);
        clearTimeout(warningTimer);

        // Warning timer - show alert before session expires
        if (sessionTimeout > warningTime) {
            warningTimer = setTimeout(function() {
                if (modalShown) return;
                modalShown = true;
                var keepAlive = confirm('Your session will expire in 60 seconds due to inactivity. Click OK to stay logged in.');
                if (keepAlive) {
                    // Send an AJAX request to keep session alive
                    $.get(admin_url + 'authentication/keep_alive');
                    modalShown = false;
                    resetTimers();
                } else {
                    // Log out immediately
                    window.location.href = admin_url + 'authentication/logout';
                }
            }, sessionTimeout - warningTime);
        }

        // Final timeout - force logout
        timeoutTimer = setTimeout(function() {
            window.location.href = admin_url + 'authentication/logout';
        }, sessionTimeout);
    }

    // Reset timers on user activity
    var activityEvents = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];
    var throttleTimer;

    function onActivity() {
        if (throttleTimer) return;
        throttleTimer = setTimeout(function() {
            throttleTimer = null;
        }, 30000); // Throttle activity resets to every 30 seconds
        modalShown = false;
        resetTimers();
    }

    activityEvents.forEach(function(event) {
        document.addEventListener(event, onActivity, { passive: true });
    });

    // Also reset on AJAX requests (user interacting with the app)
    $(document).ajaxComplete(function() {
        modalShown = false;
        resetTimers();
    });

    // Initialize
    resetTimers();
})();
</script>