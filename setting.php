<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (is_admin() && !defined('DOING_AJAX')) {
    $business = new bdwp_OptionPage('Business Directory', 'bdwp-settings');

    $admin_path = bdwp_get_admin_path();
    if ((in_array($admin_path . 'options-general.php', get_included_files()) && $_GET['page'] == 'bdwp-settings') || in_array($admin_path . 'options.php', get_included_files())) {

        $bdwp_api_state = bdwp_check_api_state();

        call_user_func(function ($business) {
            /* @var bdwp_OptionPage $business */
            $api_setting = $business->addSection('API Settings', 'api-setting');

            $api_setting->addField('API Key', 'bdwp-api-key');
            $api_setting->addField('Google Map API Key', 'bdwp-google-map-key');
            $api_setting->addField('API URL', 'bdwp-api-url');
        }, $business);

        if (bdwp_check_api()) {
            if ($bdwp_api_state === 1) {
                $found_themes_values = array_map(function ($theme) {
                    return basename($theme);
                }, glob(BDWP_THEMES_PATH . '*', GLOB_ONLYDIR));

                $found_themes = array_map(function ($theme) {
                    return [
                        'value' => $theme,
                        'title' => str_replace('_', ' ', $theme)
                    ];
                }, $found_themes_values);
                array_unshift($found_themes, [
                    'value' => 'BD_Theme',
                    'title' => 'BD Theme'
                ]);
                $theme_exist = in_array(get_option('bdwp-theme'), $found_themes_values);
                call_user_func(function ($business, $found_themes, $theme_exist) {
                    $admin_path = bdwp_get_admin_path();
                    if (in_array($admin_path . 'options-general.php', get_included_files())) {
                        $bdwp_services = bdwp_get_all_services();
                        update_option('bdwp_received_services', $bdwp_services);
                    } else {
                        $bdwp_services = get_option('bdwp_received_services');
                    }

                    /* @var bdwp_OptionPage $business */
                    $pages_setting = $business->addSection('Pages Settings', 'pages-setting');

                    $listing_page = $pages_setting->addField('Catalogue Listing Page', 'bdwp-cl-page', 'extSelect');

                    $options = array_map(function ($elem) {
                        return [
                            'value' => $elem->ID,
                            'title' => $elem->post_title
                        ];
                    }, get_posts(['post_type' => 'page', 'numberposts' => -1]));
                    $options = array_merge([[
                        'value' => 0,
                        'title' => 'None'
                    ]], $options);
                    $listing_page->select_options = $options;

                    $services = $bdwp_services;

                    $branch_page = $pages_setting->addField('Branch Page', 'bdwp-branch-page', 'extSelect');
                    $branch_page->select_options = $options;

                    $bdwp_theme = $pages_setting->addField('Select Theme', 'bdwp-theme', 'select');

                    $bdwp_theme->select_options = $found_themes;

                    if ($theme_exist && (get_option('bdwp-theme') != 'BD_Theme')) {
                        $bdwp_theme_templates = glob(BDWP_THEMES_PATH . get_option('bdwp-theme') . '/*_detailed.mustache');
                    } else {
                        $bdwp_theme_templates = glob(BDWP_DEFAULT_THEME_PATH . 'BD_Theme/*_detailed.mustache');
                    }

                    $bdwp_theme_templates = array_map(function ($template){
                        return basename($template, '_detailed.mustache');
                    }, $bdwp_theme_templates);
                    if (in_array('Free', $bdwp_theme_templates)) {
                        $default_template = 'Free';
                    } elseif (in_array('Basic', $bdwp_theme_templates)) {
                        $default_template = 'Basic';
                    } else {
                        $default_template = 'None';
                    }
                    update_option('bdwp-default-template', $default_template);
                    $bdwp_theme_templates = array_map(function ($template) {
                        return [
                            'value' => $template,
                            'title' => ucwords(str_replace('_', ' ', $template))
                        ];
                    }, $bdwp_theme_templates);

                    $bdwp_theme_templates = array_merge([[
                        'value' => 0,
                        'title' => 'None'
                    ]], $bdwp_theme_templates);

                    $templates_setting = $business->addSection('Link services: ', 'innerTemplate-pages-setting', 'Link services: ');

                    foreach ($services as $service_slug => $service_title) {
                        $templates_setting->addField($service_title, bdwp_get_service_page($service_slug), 'templateSelect')->select_options = $bdwp_theme_templates;
                    }

                    $query_settings = $business->addSection('Query Settings', 'query-setting');
                    $cl_per_page = $query_settings->addField('Number of result/page', 'bdwp-cl-per-page', 'number');
                    $cl_per_page->min = 1;
                    $cl_per_page->default = 10;

                    $query_settings->addField('Set default location', 'bdwp-set-default-location', 'checkbox');

                    $no_locations = false;
                    $none_option = [
                        ['value' => 'none', 'title' => 'none']
                    ];

                    $countries = bdwp_get_countries(null, true);

                    $country_choose = $query_settings->addField('Country', 'bdwp-default-country', 'select');
                    $country_choose->select_options = array_merge($none_option, $countries);
                    $country_choose->default = current($country_choose->select_options)['value'];

                    $areas = bdwp_get_areas(
                        get_option('bdwp-default-country'),
                        true
                    );

                    $area_choose = $query_settings->addField('Area', 'bdwp-default-area', 'select');
                    if ($no_locations || empty($areas)) {
                        $no_locations = true;
                        $area_choose->select_options = $none_option;
                        $area_choose->default = 'none';
                    } else {
                        $area_choose->select_options = array_merge($none_option, $areas);
                        $area_choose->default = current($area_choose->select_options)['value'];
                    }

                    $prefectures = bdwp_get_prefectures(
                        get_option('bdwp-default-area'),
                        true
                    );

                    $prefecture_choose = $query_settings->addField('Prefecture', 'bdwp-default-prefecture', 'select');
                    if ($no_locations || empty($prefectures)) {
                        $no_locations = true;
                        $prefecture_choose->select_options = $none_option;
                        $prefecture_choose->default = 'none';
                    } else {
                        $prefecture_choose->select_options = array_merge($none_option, $prefectures);
                        $prefecture_choose->default = current($prefecture_choose->select_options)['value'];
                    }

                    $cities = bdwp_get_cities(
                        get_option('bdwp-default-prefecture'),
                        true
                    );

                    $city_choose = $query_settings->addField('City', 'bdwp-default-city', 'select');
                    if ($no_locations || empty($cities)) {
                        $no_locations = true;
                        $city_choose->select_options = $none_option;
                        $city_choose->default = 'none';
                    } else {
                        $city_choose->select_options = array_merge($none_option, $cities);
                        $city_choose->default = current($city_choose->select_options)['value'];
                    }

                    $query_settings->addField('Show location searchbox', 'bdwp-searchbox-location', 'checkbox');

                    $query_settings->addField('Show languages searchbox', 'bdwp-searchbox-language', 'checkbox');

                    $query_settings->addField('Category and keywords', 'bdwp-category-description');
                    $query_settings->addField('Location', 'bdwp-location-description');
                    $query_settings->addField('Languages', 'bdwp-language-description');

                    $eshop_setting = $business->addSection('eShop Settings', 'eshop-setting');

                    $eshop_setting->addField('eShop url', 'bdwp-eshop-url');
                    $eshop_setting->addField('eShop link text', 'bdwp-eshop-link');
                    $eshop_setting->addField('Show eShop link', 'bdwp-eshop-shown', 'checkbox');

                    $icon_setting = $business->addSection('Icon Settings', 'icons-setting', 'Select or upload your own icons for each contact group');
                    $icon_groups = bdwp_get_icon_groups();

                    $grouped_groups = ['Phones' => [], 'Emails' => [], 'Urls' => []];
                    foreach ($icon_groups as $group) {
                        switch ($group['type']) {
                            case 'Phone':
                                array_push($grouped_groups['Phones'], $group);
                                break;
                            case 'Email':
                                array_push($grouped_groups['Emails'], $group);
                                break;
                            case 'Url':
                                array_push($grouped_groups['Urls'], $group);
                                break;
                        }
                    }

                    $group_sections = [];

                    foreach ($grouped_groups as $type => $group) {
                        $group_sections[$type] = $business->addSection($type, 'inner' . $type . '-icons-setting', $type, 'underlined');
                        foreach ($group as $icon_group) {
                            $group_sections[$type]->addField($icon_group['name'], 'bdwp-icon-' . $icon_group['sub_type'], 'iconUpload')->default = $icon_group['icon'];
                        }
                    }
                    $warning_setting = $business->addSection('Warning messages', 'warnings-setting');
                    $warning_setting->addField('Branch missing', 'bdwp-branch-missing', 'textarea');
                    $warning_setting->addField('Technical issues', 'bdwp-technical-issues', 'textarea');

                    $banners_setting = $business->addSection('Banners', 'banners-setting');
                    $banners_setting->addField('Enable banner A', 'bdwp-banner-a-switch', 'checkbox');
                    $banners_setting->addField('Enable banner B', 'bdwp-banner-b-switch', 'checkbox');
                }, $business, $found_themes, $theme_exist);
            }
        }
    }
    if (in_array($admin_path . 'options-general.php', get_included_files()) && $_GET['page'] == 'bdwp-settings') {
        if (!bdwp_check_api()) {
            if (!get_option('bdwp-api-key')) {
                add_action('admin_notices', 'bdwp_error_missing_api_key');
            }
            if (!get_option('bdwp-api-url')) {
                add_action('admin_notices', 'bdwp_error_missing_api_url');
            }
        } else {
            if ($bdwp_api_state === 2) {
                add_action('admin_notices', 'bdwp_error_wrong_api_key');
            } elseif ($bdwp_api_state === 0) {
                add_action('admin_notices', 'bdwp_error_wrong_api_url');
            } else {
                if (get_option('bdwp-cl-page') == 0) {
                    add_action('admin_notices', 'bdwp_warning_missing_listing_page');
                } else {
                    if (get_option('bdwp-branch-page') == 0) {
                        add_action('admin_notices', 'bdwp_error_missing_branch_page');
                    }
                }
                if (!$theme_exist && (get_option('bdwp-theme') != 'BD_Theme')) {
                    add_action('admin_notices', 'bdwp_error_missing_theme');
                } else {
                    $services = get_option('bdwp_received_services');
                    $template_selected = false;
                    foreach ($services as $service_slug => $service_title) {
                        if (get_option(bdwp_get_service_page($service_slug))) {
                            $template_selected = true;
                            break;
                        }
                    }
                    if (!$template_selected) {
                        add_action('admin_notices', 'bdwp_error_set_template');
                    }
                }
            }
        }
    }
}

function bdwp_error_wrong_api_key() {
    add_settings_error(
        'bdwp_errors',
        esc_attr('bdwp_wrong_api_key'),
        __("Invalid API key."),
        'error'
    );
}

function bdwp_error_wrong_api_url() {
    add_settings_error(
        'bdwp_errors',
        esc_attr('bdwp_api_not_responding'),
        __("Invalid API URL or API isn't responding."),
        'error'
    );
}

function bdwp_error_missing_api_key() {
    add_settings_error(
        'bdwp_errors',
        esc_attr('bdwp_missing_api_key'),
        __("API key is missing."),
        'error'
    );
}

function bdwp_error_missing_api_url() {
    add_settings_error(
        'bdwp_errors',
        esc_attr('bdwp_missing_api_url'),
        __("API URL is missing."),
        'error'
    );
}

function bdwp_warning_missing_listing_page() {
    add_settings_error(
        'bdwp_errors',
        esc_attr('bdwp_missing_listing_page'),
        __("Select page to display listing."),
        'notice-warning'
    );
}

function bdwp_error_missing_branch_page() {
    add_settings_error(
        'bdwp_errors',
        esc_attr('bdwp_missing_branch_page'),
        __("Select page to display branches."),
        'error'
    );
}

function bdwp_error_missing_theme() {
    $saved_theme = str_replace("_", " ", get_option('bdwp-theme'));
    add_settings_error(
        'bdwp_errors',
        esc_attr('bdwp_missing_theme'),
        __("$saved_theme is missing, select one of existing theme and assign templates."),
        'error'
    );
}

function bdwp_error_set_template() {
    add_settings_error(
        'bdwp_errors',
        esc_attr('bdwp_set_template'),
        __("You haven't selected any template for branches"),
        'error'
    );
}