<?php
/*
 * Plugin Name: Business Directory for WordPress
 * Version: 0.7.4.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BDWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BDWP_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'BDWP_ASSETS_URL', BDWP_PLUGIN_URL . 'assets/');
define( 'BDWP_SETTINGS_URL', BDWP_PLUGIN_URL . 'settings/');
define( 'BDWP_DEFAULT_THEME_PATH', plugin_dir_path(__FILE__) . 'themes/');
define( 'BDWP_THEMES_PATH', WP_PLUGIN_DIR . '/bdwp-themes/');

/* @var BDWP_Branch $bdwp_query */
global $bdwp_branch;
/* @var BDWP_Query $bdwp_query Current global instance of BDWP_Query */
global $bdwp_query;
/* @var BDWP_Query $bdwp_the_query Initial global instance of BDWP_Query */
global $bdwp_the_query;

if ( ! class_exists('Mustache_Autoloader') ) require_once 'mustache/src/Mustache/Autoloader.php';
global $bdwp_sitemap_name;
$bdwp_sitemap_name = 'bdwp-sitemap';

require_once 'lib/helper.php';
require_once 'lib/url.php';
require_once 'lib/option.php';
require_once 'lib/api.php';
require_once 'lib/hash.php';
require_once 'lib/branch.php';
require_once 'lib/query.php';
require_once 'lib/filters.php';
require_once 'lib/widgets.php';
require_once 'lib/templating.php';
require_once 'lib/ajax.php';
require_once 'lib/shortcodes.php';
require_once 'lib/sitemap.php';
require_once 'setting.php';

Mustache_Autoloader::register();

add_option('bdwp_received_services', array());
add_option('bdwp-sitemap-updated-at', '');

add_action('wp', function () {
    global $bdwp_query, $bdwp_the_query;
    $bdwp_the_query = $bdwp_query = new BDWP_Query();
});

add_action( 'wp_loaded', 'bdwp_redirect_sitemap' );
add_filter( 'wpseo_sitemap_index', 'bdwp_yoast_sitemap_addition' );

if ($bdwp_query->is_single) {
    add_filter('document_title_parts', 'bdwp_set_title', 50);
    add_filter('language_attributes', 'bdwp_set_doctype_open_graph', 50);
    add_action('wp_head', 'bdwp_set_open_graph_block');
    add_action('wp_head', 'bdwp_set_twitter_card_block');
}

add_action('wp_ajax_nopriv_bdwp_fetch_branches', 'bdwp_fetch_branches');
add_action('wp_ajax_bdwp_fetch_branches', 'bdwp_fetch_branches');
add_action('wp_ajax_nopriv_bdwp_send_mail', 'bdwp_send_mail');
add_action('wp_ajax_bdwp_send_mail', 'bdwp_send_mail');
add_action('wp_ajax_nopriv_bdwp_pull_templates', 'bdwp_pull_templates');
add_action('wp_ajax_bdwp_pull_templates', 'bdwp_pull_templates');
add_action('wp_ajax_nopriv_bdwp_fetch_locations', 'bdwp_fetch_locations');
add_action('wp_ajax_bdwp_fetch_locations', 'bdwp_fetch_locations');

add_filter( 'template_include', 'bdwp_template_linker', 99 );

add_action( 'widgets_init', 'bdwp_widgets_init' );

add_action( 'bdwp_cron_hook', 'bdwp_refresh_sitemap', 10, 1 );
if ( ! wp_next_scheduled( 'bdwp_cron_hook', [$bdwp_sitemap_name] ) ) {
    wp_schedule_event( time(), 'daily', 'bdwp_cron_hook', [$bdwp_sitemap_name] );
}

add_action('admin_enqueue_scripts', function ( $hook ) {
    if ( $hook == 'settings_page_bdwp-settings' ) {
        wp_enqueue_style('font_awesome', BDWP_ASSETS_URL . 'font-awesome.css', '', '4.7.0');
        wp_enqueue_style('select2', BDWP_ASSETS_URL . 'select2.min.css', '', '4.0.3');
        wp_enqueue_style('bdwp_admin_style', BDWP_ASSETS_URL . 'admin-style.css', '', '1.30');

        wp_register_script('select2', BDWP_ASSETS_URL . 'select2.min.js', ['jquery'], '4.0.3');
        wp_enqueue_script('bdwp_admin', BDWP_ASSETS_URL . 'admin.js', ['select2'], '1.88');
        wp_localize_script('bdwp_admin', 'BDWP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'tabs_url' => BDWP_SETTINGS_URL . 'tabs.json',
            'fa_names' => BDWP_SETTINGS_URL . 'fa-names.json'
        ]);
        wp_enqueue_script('bdwp_tabs', BDWP_ASSETS_URL . 'tabs.js', ['bdwp_admin'], '1.5');
    }
});

add_action('wp_enqueue_scripts', function () {
    global $bdwp_query;

    wp_dequeue_script('googlemapapis');
    $api_key = apply_filters('bdwp_google_map_api_key', get_option('bdwp-google-map-key'));
    wp_register_script('google_map', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key);

    if ($bdwp_query->is_listing || $bdwp_query->is_single) {

        wp_enqueue_style('bdwp_modal_style', BDWP_ASSETS_URL . 'modal.css', '', '1.10');
        $theme = bdwp_sanitize_theme(get_option('bdwp-theme'));
        if ($theme == 'BD_Theme') {
            wp_enqueue_style('bdwp_' . $theme, BDWP_PLUGIN_URL . 'themes/' . $theme . '/styles.css', '', $theme . '_1.9');
        } else {
            wp_enqueue_style('bdwp_' . $theme, plugins_url() . '/bdwp-themes/' . $theme . '/styles.css', '', $theme . '_1.9');
        }
        wp_enqueue_style('font_awesome', BDWP_ASSETS_URL . 'font-awesome.css', '', '4.7.0');
        wp_enqueue_style('weather_icons', BDWP_ASSETS_URL . 'weather-icons.css', '', '2.0.8');
        wp_enqueue_style('weather_icons_wind', BDWP_ASSETS_URL . 'weather-icons-wind.css', '', '2.0.8');



        wp_enqueue_script('mustache.js', BDWP_ASSETS_URL . 'mustache.min.js', '', '2.3.0');

        wp_enqueue_script('bdwp_modal', BDWP_ASSETS_URL . 'modal.js', ['jquery'], '1.47');
        wp_register_script('form_validator', BDWP_ASSETS_URL . 'form-validator.js', [], '4.1.1');
        wp_enqueue_script('bdwp_form_setup', BDWP_ASSETS_URL . 'email-form-setup.js', ['jquery', 'form_validator'], '1.18');
        wp_enqueue_script('bdwp_tabs', BDWP_ASSETS_URL . 'tabs.js', ['jquery', 'bdwp_main'], '1.4');
        wp_register_script('bdwp_main', BDWP_ASSETS_URL . 'main.js', ['jquery'], '1.1');
        wp_register_script('_bdwp_map', BDWP_ASSETS_URL . 'map.js', ['bdwp_main'], '1.11');
        wp_register_script('bdwp_map', null, ['_bdwp_map', 'google_map'], '1.0');

        wp_localize_script('bdwp_main', 'BDWP', [
            'listing_url' => bdwp_get_listing_url(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'tabs_url' => BDWP_SETTINGS_URL . 'tabs.json',
            'maps' => [],
            'warning' => get_option('bdwp-technical-issues')
        ]);

        wp_enqueue_script('bdwp_map');
    }

    if ($bdwp_query->is_single) {
        wp_enqueue_script('add_to_bookmarks', BDWP_ASSETS_URL . 'add-to-bookmarks.js', ['jquery'], '1.1');
        wp_enqueue_script('bdwp_branch_maps', BDWP_ASSETS_URL . 'branch-maps.js', ['bdwp_map'], '1.16');
    }

    if ($bdwp_query->is_listing) {
        wp_enqueue_style('select2', BDWP_ASSETS_URL . 'select2.min.css', '', '4.0.3');

        wp_register_script('select2', BDWP_ASSETS_URL . 'select2.min.js', ['jquery'], '4.0.3');
        wp_enqueue_script('bdwp_select2_setup', BDWP_ASSETS_URL . 'select.js', ['select2'], '1.3');
        wp_register_script('serializeObject', BDWP_ASSETS_URL . 'jquery.serializeObject.min.js', ['jquery'], '2.0.3');
        wp_enqueue_script('bdwp_search', BDWP_ASSETS_URL . 'smart-search.js', ['serializeObject'], '3.106');
    }
});