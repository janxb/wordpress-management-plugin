<?php
/*
 * Plugin Name: brodda.IT Wordpress Management
 * Version: 26
*/

include_once ABSPATH . 'wp-admin/includes/plugin.php';
include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

class Silent_Upgrader_Skin extends WP_Upgrader_Skin
{
    public function feedback($string, ...$args)
    {
        // NOOP
    }
}

function install_and_activate_plugin($plugin_path, $optional = false)
{
    $upgrader = new Plugin_Upgrader(new Silent_Upgrader_Skin());
    $plugin_slug = explode('/', $plugin_path)[0];
    $isInstalled = file_exists(WP_PLUGIN_DIR . "/{$plugin_path}");
    if (!$isInstalled) {
        $isInstalled = $upgrader->install("https://downloads.wordpress.org/plugin/{$plugin_slug}.latest-stable.zip");
    }
    if ($isInstalled && !is_plugin_active($plugin_path) && !$optional) {
        activate_plugin("{$plugin_path}");
    }
}


define('WP_POST_REVISIONS', 10);


// set admin email to fixed value
update_option('admin_email', 'wordpress@brodda.it');
delete_option('new_admin_email');


add_filter('wp_is_application_passwords_available', '__return_false');


// remove default media title during upload
function remove_attachment_title($data, $postarr)
{
    if ($_POST['action'] == 'upload-attachment' && !empty($data['post_title']) && isset($postarr['post_type']) && $postarr['post_type'] === 'attachment') {
        $data['post_title'] = '';
    }
    return $data;
}

add_filter('wp_insert_attachment_data', 'remove_attachment_title', 10, 2);


// set upload filenames to random string and disable date-based upload folders
function force_disable_yearmonth_folders()
{
    if (get_option('uploads_use_yearmonth_folders') != 0) {
        update_option('uploads_use_yearmonth_folders', 0);
    }
}

add_action('admin_init', 'force_disable_yearmonth_folders');

function filename_randomizer__randomize_name($filename)
{
    $name = sha1(random_bytes(32));
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return "$name.$ext";
}

add_filter('sanitize_file_name', 'filename_randomizer__randomize_name', 10);


add_action('admin_init', function () {

    $blog_disabled = avia_get_option('disable_blog') == 'disable_blog';
    if ($blog_disabled) {
        install_and_activate_plugin('disable-comments/disable-comments.php');
        install_and_activate_plugin('disable-blog/disable-blog.php');
    } else {
        deactivate_plugins("disable-comments/disable-comments.php");
        deactivate_plugins("disable-blog/disable-blog.php");
    }
    
    install_and_activate_plugin('disable-search/disable-search.php', true);

    install_and_activate_plugin('two-factor/two-factor.php');
    install_and_activate_plugin('http-headers/http-headers.php');
    install_and_activate_plugin('aryo-activity-log/aryo-activity-log.php');
    install_and_activate_plugin('save-with-keyboard/save_with_keyboard.php');
    install_and_activate_plugin('cache-enabler/cache-enabler.php');
    install_and_activate_plugin('head-footer-code/head-footer-code.php');
    install_and_activate_plugin('redirection/redirection.php');
    install_and_activate_plugin('simple-xml-sitemap-generator/index.php');
    install_and_activate_plugin('user-switching/user-switching.php');
    install_and_activate_plugin('user-role-editor/user-role-editor.php');
    install_and_activate_plugin('admin-menu-editor/menu-editor.php');
    install_and_activate_plugin('simple-custom-post-order/simple-custom-post-order.php');
    install_and_activate_plugin('disable-wp-rest-api/disable-wp-rest-api.php');

    deactivate_plugins("logdash-activity-log/logdash-activity-log.php");
});


// disable unwanted two factor providers
add_filter(
    'two_factor_providers',
    function ($providers) {
        return array_diff_key($providers, array('Two_Factor_FIDO_U2F' => null));
    }
);


// disable debug output for enfold theme
add_filter("avf_debugging_info", function ($debug) {
    return '';
}, 10, 1);


// remove default media sizes and define custom ones
add_action('init', function () {
    update_option('thumbnail_size_w', 80, true);
    update_option('thumbnail_size_h', 80, true);
    update_option('thumbnail_crop', true, true);
    update_option('medium_size_w', 300, true);
    update_option('medium_size_h', 300, true);
    update_option('large_size_w', 500, true);
    update_option('large_size_h', 500, true);

    $blog_disabled = avia_get_option('disable_blog') == 'disable_blog';
    if ($blog_disabled) {
        remove_image_size('entry_with_sidebar');
        remove_image_size('entry_without_sidebar');
    }
    remove_image_size('featured');
    remove_image_size('featured_large');
    remove_image_size('portfolio');
    remove_image_size('portfolio_small');
    remove_image_size('1536x1536');
    remove_image_size('2048x2048');
    remove_image_size('widget');
    remove_image_size('square');
    remove_image_size('extra_large');
    remove_image_size('gallery');
    remove_image_size('magazine');
    remove_image_size('masonry');
    remove_image_size('medium');
    remove_image_size('large');
    remove_image_size('thumbnail');
    add_image_size('custom-square-300', 300, 300, true);
    add_image_size('custom-banner-350', 350, 300, true);
    add_image_size('custom-banner-600', 600, 170, true);
    add_image_size('custom-800', 800, 800);
    add_image_size('medium', 300, 300);
    add_image_size('large', 500, 500);
    add_image_size('thumbnail', 80, 80);
});
add_filter('image_size_names_choose', 'my_custom_sizes');
function my_custom_sizes($sizes)
{
    return array(
        'thumbnail' => __('80px'),
        'medium' => __('300px'),
        'large' => __('500px'),
        'custom-800' => __('800px'),
        'full' => __('Original'),
    );
}

add_filter('intermediate_image_sizes', function ($sizes) {
    return array_diff($sizes, ['medium_large']);
});


// remove dashboard widgets to avoid customer confusion
add_action('admin_head', 'custom_remove_meta_boxes');
function custom_remove_meta_boxes()
{
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
add_filter('pdb-private_id_length', function ($length) {
    return 30;
});


// remove portfolio from enfold theme
add_action('after_setup_theme', 'remove_portfolio');
function remove_portfolio()
{
    remove_action('init', 'portfolio_register');
}


// remove default wordpress roles to avoid customer confusion
add_filter('editable_roles', function ($roles) {
    unset($roles['contributor']);
    unset($roles['subscriber']);
    unset($roles['author']);
    unset($roles['translator']);
    return $roles;
});


// custom backend CSS to diable some confusion input fields
add_action('admin_head', 'custom_backend_css');
function custom_backend_css()
{
    echo <<<EOL
    <style>
        .file-delete-preference-selector {
            display: none !important;
        }

        .pods-form #titlediv .inside {
            display: none;
        }

        .misc-pub-curtime {
            display: none;
        }

        tr.type-veranstaltung span.view {
            display: none;
        }

        tr.type-mitarbeiterin span.view {
            display: none;
        }

        div.sbi-stck-wdg {
            display: none;
        }

        div.sbi-fb-fs:has(> div.sbi-settings-cta) {
            display: none;
        }

        table:has(#ure_select_other_roles) {
            display: none;
        }

        table:has(.user-description-wrap) {
            display: none;
        }

        tr.user-url-wrap {
            display: none;
        }

        tr.user-nickname-wrap {
            display: none;
        }
	</style>
EOL;
}


// custom backend JS to change default features/settings
add_action('admin_footer', 'custom_backend_js');
function custom_backend_js()
{
    echo <<<EOL
	<script>
		const currentPage = new URLSearchParams(window.location.search).get('page');
		const currentUrl = window.location.pathname.split('/').pop();

		if (currentPage === 'participants-database-edit_participant') {
			document.getElementById("pdb-private_id-field").children[0].setAttribute("disabled", "true");
			document.getElementById("pdb-private_id-field").children[0].classList.add("readonly-field");
			document.getElementById("pdb-last_update_user-field").children[0].setAttribute("disabled", "true");
			document.getElementById("pdb-last_update_user-field").children[0].classList.add("readonly-field");
			jQuery("input[type=submit][value='Nächster']").remove();
			jQuery("input[type=submit][value='Zurück']").remove();
			jQuery(".field-group-submit table tbody tr:nth-of-type(2)").remove();
		}

		jQuery('h3:contains("Zusätzliche Berechtigungen")').remove();

		if (currentUrl === 'options-media.php'){
			jQuery('form').remove();
			jQuery('#wpbody .wrap').append(jQuery("<p></p>").text("Die Medien-Einstellungen dieser Seite werden durch brodda.IT verwaltet."));
		}

		// disable umami analytics tracking for logged in users
		localStorage.setItem('umami.disabled', 1);
	</script>
EOL;
}


// custom plugin updates from Github repository
add_filter('pre_set_site_transient_update_plugins', 'broddait_check_update');
add_action('upgrader_process_complete', 'broddait_clear_cache', 10, 2);
const BRODDAIT_PLUGIN_UPDATE_CACHE_KEY = 'broddait_plugin_update_cache';
function broddait_check_update($transient)
{
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin = (json_decode(file_get_contents(dirname(__FILE__) . "/info.json")));

    $remote = get_transient(BRODDAIT_PLUGIN_UPDATE_CACHE_KEY);

    if ($remote === false) {
        $remote = wp_remote_get(
            $plugin->info_url,
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json'
                )
            )
        );
        set_transient(BRODDAIT_PLUGIN_UPDATE_CACHE_KEY, $remote, 10);
    }

    if (
        is_wp_error($remote)
        || 200 !== wp_remote_retrieve_response_code($remote)
        || empty(wp_remote_retrieve_body($remote))
    ) {
        return $transient;
    }

    $remote = json_decode(wp_remote_retrieve_body($remote));

    if ($remote) {
        $res = new stdClass();
        $res->slug = 'brodda-it';
        $res->id = plugin_basename(__FILE__);
        $res->plugin = plugin_basename(__FILE__);
        $res->new_version = $remote->version;
        $res->package = $remote->download_url;

        if (version_compare($plugin->version, $remote->version, '<')) {
            $transient->response[$res->plugin] = $res;
        } else {
            $transient->no_update[$res->plugin] = $res;
        }
    }

    return $transient;
}

function broddait_clear_cache($upgrader, $options)
{
    if ('update' === $options['action'] && 'plugin' === $options['type']) {
        delete_transient(BRODDAIT_PLUGIN_UPDATE_CACHE_KEY);
    }
}
