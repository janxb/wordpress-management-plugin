<?php
/*
 * Plugin Name: brodda.IT Wordpress Management
 * x Requires Plugins: aryo-activity-log, http-headers
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


// remove default media sizes and define custom ones
add_action('after_setup_theme', function() {
	remove_image_size('1536x1536');
	remove_image_size('2048x2048');
	remove_image_size('widget');
	remove_image_size('square');
	remove_image_size('featured');
	remove_image_size('featured_large');
	remove_image_size('extra_large');
	remove_image_size('portfolio');
	remove_image_size('portfolio_small');
	remove_image_size('gallery');
	remove_image_size('magazine');
	remove_image_size('masonry');
	remove_image_size('entry_with_sidebar');
	remove_image_size('entry_without_sidebar');
	add_image_size('custom-square-300', 300, 300, true);
	add_image_size('custom-banner-350', 350, 300, true);
}, 999);
add_filter( 'image_size_names_choose', 'my_custom_sizes' );
function my_custom_sizes( $sizes ) {
	unset($sizes['featured_large']);
	return array_merge( $sizes, array(
		'custom-square-300' => __( 'Quadratisch' ),
	));
}
add_filter('intermediate_image_sizes', function($sizes) {
    return array_diff($sizes, ['medium_large']);
});


// remove dashboard widgets to avoid customer confusion
add_action('admin_head', 'custom_remove_meta_boxes');
function custom_remove_meta_boxes(){
   remove_meta_box('dashboard_primary', 'dashboard', 'side');
   remove_meta_box('sb_dashboard_widget', 'dashboard', 'normal');
   remove_action('welcome_panel', 'wp_welcome_panel');
   remove_meta_box('slugdiv', 'page', 'normal'); 
   remove_meta_box('authordiv', 'page', 'normal'); 
   remove_meta_box('postexcerpt', 'page', 'normal'); 
   remove_meta_box('pageparentdiv', 'page', 'side'); 
   remove_meta_box('layout', 'page', 'side'); 
   remove_meta_box('postcustom', 'page', 'normal'); 
   remove_meta_box('postimagediv', 'page', 'side'); 

   // remove slug meta box for custom post types / pods
   remove_meta_box('slugdiv', 'veranstaltung', 'normal'); 
   remove_meta_box('slugdiv', 'mitarbeiterin', 'normal'); 
}


// increase private-id length for WP members database plugin
add_filter( 'pdb-private_id_length', function ( $length ) { return 30; } );


// remove portfolio from enfold theme
add_action('after_setup_theme', 'remove_portfolio');
function remove_portfolio() {
	remove_action('init', 'portfolio_register');
}


// remove default wordpress roles to avoid customer confusion
add_filter( 'editable_roles', function( $roles ) {
	unset( $roles['contributor'] );
	unset( $roles['subscriber'] );
	unset( $roles['author'] );
	unset( $roles['translator'] );
	return $roles;
} );


// custom backend CSS to diable some confusion input fields
add_action('admin_head', 'custom_backend_css');
function custom_backend_css() {?>
  <style>
    .file-delete-preference-selector { display:none !important; }
	.pods-form #titlediv .inside { display:none; }
	.misc-pub-curtime { display:none; }
	tr.type-veranstaltung span.view { display:none; }
	tr.type-mitarbeiterin span.view { display:none; }
	div.sbi-stck-wdg { display:none; }
	div.sbi-fb-fs:has(> div.sbi-settings-cta) { display:none; }
	table:has(#ure_select_other_roles) { display:none; }
	table:has(.user-description-wrap) { display:none; }
	tr.user-url-wrap { display:none; }
	tr.user-nickname-wrap { display:none; }
  </style>
 <?
}


// custom backend JS to change default features/settings
add_action('admin_footer', 'custom_backend_js');
function custom_backend_js() {?>
  <script>
	const currentPage = new URLSearchParams(window.location.search).get('page');
	
	if (currentPage === 'participants-database-edit_participant'){
		document.getElementById("pdb-private_id-field").children[0].setAttribute("disabled", "true");
		document.getElementById("pdb-private_id-field").children[0].classList.add("readonly-field");
		document.getElementById("pdb-last_update_user-field").children[0].setAttribute("disabled", "true");
		document.getElementById("pdb-last_update_user-field").children[0].classList.add("readonly-field");
		jQuery("input[type=submit][value='Nächster']").remove();
		jQuery("input[type=submit][value='Zurück']").remove();
		jQuery(".field-group-submit table tbody tr:nth-of-type(2)").remove();
	}
	
	jQuery('h3:contains("Zusätzliche Berechtigungen")').remove();
	
    // disable umami analytics tracking for logged in users
	localStorage.setItem('umami.disabled', 1);
  </script>
 <?
}


// custom plugin updates from Github repository
add_filter('site_transient_update_plugins', 'misha_push_update');
function misha_push_update($transient)
{
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin = (json_decode(file_get_contents(dirname(__FILE__) . "/info.json")));

    $remote = wp_remote_get(
        'https://raw.githubusercontent.com/janxb/wordpress-management-plugin/refs/heads/main/src/brodda-it/info.json',
        array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json'
            )
        )
    );

    if (
        is_wp_error($remote)
        || 200 !== wp_remote_retrieve_response_code($remote)
        || empty(wp_remote_retrieve_body($remote))
        ) {
        return $transient;
    }

    $remote = json_decode(wp_remote_retrieve_body($remote));

    if ($remote && version_compare($plugin->version, $remote->version, '<')) {
        $res = new stdClass();
        $res->slug = 'brodda-it';
        $res->plugin = plugin_basename(__FILE__);
        $res->new_version = $remote->version;
        $res->package = $remote->download_url;
        $transient->response[$res->plugin] = $res;

        //$transient->checked[$res->plugin] = $remote->version;
    }

    return $transient;
}