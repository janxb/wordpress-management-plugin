<?php
/*
 * Plugin Name: brodda.IT Wordpress Management
 * Requires Plugins: aryo-activity-log, http-headers
*/

define('WP_POST_REVISIONS', 10);

update_option('admin_email', 'wordpress@brodda.it');
delete_option('new_admin_email');

add_action('admin_init', function () {
    remove_submenu_page('logdash_activity_log', 'logdash_settings');
});

add_filter("avf_debugging_info", function ($debug) {
    return '';
}, 10, 1);