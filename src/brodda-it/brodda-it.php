<?php
/*
 * Plugin Name: brodda.IT Wordpress Management
 * Requires Plugins: aryo-activity-log, http-headers
*/


define('WP_POST_REVISIONS', 10);


// set admin email to fixed value
update_option('admin_email', 'wordpress@brodda.it');
delete_option('new_admin_email');


add_filter( 'wp_is_application_passwords_available', '__return_false' );


// set upload filenames to random string and disable date-based upload folders
add_filter( 'pre_option_uploads_use_yearmonth_folders', '__return_zero');
function filename_randomizer__randomize_name($filename) {
    $filenameLength = 20;
    if (preg_match('/^[a-f0-9]{'. $filenameLength . '}-.*/', $filename)) {
        $filename = substr($filename, $filenameLength + 1);
    }
    $key = sha1(random_bytes(32));
    $key = substr($key, 0, $filenameLength);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return "$key.$ext";
}
add_filter('sanitize_file_name', 'filename_randomizer__randomize_name', 10);


// remove settings page for log plugin from menu
add_action('admin_init', function () {
    remove_submenu_page('logdash_activity_log', 'logdash_settings');
});


// disable debug output for enfold theme
add_filter("avf_debugging_info", function ($debug) {
    return '';
}, 10, 1);