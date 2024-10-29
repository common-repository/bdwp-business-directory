<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('bdwp_listing', function ($atts) {
    $services = get_option('bdwp_received_services');
    if ($atts['theme']) {
        if ($atts['theme'] === get_option('bdwp-theme')) {
            $services = array_map(function ($service) {
                return array( 'name' => 'template_' . $service, 'value' => get_option(bdwp_get_service_page($service), ''));
            }, array_keys($services));
        }
    } else {
        $services = array_map(function ($service) {
            return array( 'name' => 'template_' . $service, 'value' => '');
        }, array_keys($services));
    }
    $templates = [];
    foreach ($services as $service) {
        $templates[$service['name']] = $service['value'];
    }
    $atts = shortcode_atts(array_merge(array(
        'what' => '',
        'country' => '',
        'area' => '',
        'prefecture' => '',
        'city' => '',
        'theme' => '',
        'search_form' => '1',
        'pagination' => '1',
        'map' => '1'
    ), $templates), $atts);

    $keyword = str_replace(',', ' ', $atts['what']);

    $location_types = ['city', 'prefecture', 'area', 'country'];
    $location = ['type' => '', 'terms' => ''];

    foreach ($location_types as $type) {
        if (!empty($atts[$type])) {
            $location['type'] = $type;
            $location['terms'] = $atts[$type];
        }
    }

    $query_args = array(
        'category' => array(
            'compare' => 'like',
            'terms' => $atts['what']
        ),
        'keyword' => $keyword,
        'country' => $atts['country'],
        'area' => $atts['area'],
        'prefecture' => $atts['prefecture'],
        'city' => $atts['city'],
        'pagination' => $atts['pagination'],
        'location' => $location
    );

    global $bdwp_query;
    $bdwp_query = new BDWP_Query(null, true);

    wp_enqueue_style('bdwp_modal_style', BDWP_ASSETS_URL . 'modal.css', '', '1.10');
    if ($atts['theme']) {
        $theme = bdwp_sanitize_theme($atts['theme'], false);
        $services_map = [];
        foreach ($templates as $key => $value) {
            $service = str_replace('template_', '', $key);
            if ($atts[$key] == '') {
                $services_map[$service] = 0;
            } else {
                $services_map[$service] = $atts[$key];
            }
        }
        $services_map['default'] = bdwp_find_default_template($theme);
    } else {
        $theme = bdwp_sanitize_theme(get_option('bdwp-theme'));
        $services_map = null;
    }
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
        'listing_url' => get_permalink(),
        'ajax_url' => admin_url('admin-ajax.php'),
        'tabs_url' => BDWP_SETTINGS_URL . 'tabs.json',
        'maps' => [],
        'service_map' => $services_map,
        'query_args' => $query_args,
        'warning' => get_option('bdwp-technical-issues')
    ]);
    wp_enqueue_script('bdwp_map');
    wp_enqueue_style('select2', BDWP_ASSETS_URL . 'select2.min.css', '', '4.0.3');
    wp_register_script('select2', BDWP_ASSETS_URL . 'select2.min.js', ['jquery'], '4.0.3');
    wp_enqueue_script('bdwp_select2_setup', BDWP_ASSETS_URL . 'select.js', ['select2'], '1.3');
    wp_register_script('serializeObject', BDWP_ASSETS_URL . 'jquery.serializeObject.min.js', ['jquery'], '2.0.3');
    wp_enqueue_script('bdwp_search', BDWP_ASSETS_URL . 'smart-search.js', ['serializeObject'], '3.106');

    ob_start();

    bdwp_render_template($atts['theme'], $atts['map'], $atts['search_form'], $atts['pagination']);

    return ob_get_clean();
});

add_shortcode('bdwp_map', function ($atts) {
    $atts = shortcode_atts(array(
        'width' => '',
        'height' => '',
    ), $atts);

    return bdwp_map('shortcode', $atts['width'], $atts['height']);
});