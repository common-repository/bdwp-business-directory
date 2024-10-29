<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bdwp_render_template($theme = null, $map = null, $form = true, $pagination = true) {
    global $bdwp_branch, $bdwp_query;
    $data = array();
    if (!$theme) $theme = get_option('bdwp-theme');
    $theme = bdwp_sanitize_theme($theme);
    if ($theme == 'BD_Theme') {
        $theme_path = BDWP_DEFAULT_THEME_PATH;
    } else {
        $theme_path = BDWP_THEMES_PATH;
    }
    ob_start();
    if ( dynamic_sidebar('bdwp_widget_area_1') ) : else : endif;
    $widget_area_1 = ob_get_contents();
    ob_end_clean();
    ob_start();
    if ( dynamic_sidebar('bdwp_widget_area_2') ) : else : endif;
    $widget_area_2 = ob_get_contents();
    ob_end_clean();
    ob_start();
    if ( dynamic_sidebar('bdwp_widget_area_3') ) : else : endif;
    $widget_area_3 = ob_get_contents();
    ob_end_clean();

    $mini_map = '';
    $full_map = '';
    if ($map !== '0') {
        $mini_map = bdwp_map('mini');
        $full_map = bdwp_map('full');
    }
    $icons = bdwp_get_icons_by_subtype();
    if ($bdwp_query->is_listing) {
        //template for mustache to render
        $template = 'listing';
        //fetching templates for branches
        $listing_templates = glob($theme_path . $theme . '/*_branch_sort.mustache');
        //outputting templates on page to use with ajax
        foreach ($listing_templates as $listing_template) {
            $name = basename($listing_template, '.mustache');
            $template_string = file_get_contents($listing_template);
            bdwp_output_template($name, $template_string);
        }
        bdwp_output_template('warning', file_get_contents($theme_path . $theme . '/warning.mustache'));
        //saving page variable to use with ajax (need rework)
        echo '<span style="display: none;" id="bdwp_page">' . $_GET['bdwp_page'] . '</span>';
        $listing_url = get_the_permalink(get_option('bdwp-cl-page'));
        $category_options = bdwp_form_select_options(bdwp_get_categories_names_and_aliases(), $_GET['bdwp_category']);
        $languages_options = bdwp_form_select_options(bdwp_get_languages(), $_GET['bdwp_language']);
        $data = array(
            'listing-content-wrapper-attrs' => 'data-bdwp-selectors=listing-content',
            'search-form-attrs' => 'data-bdwp-selectors=search-form action=' . $listing_url . ' method=get autocomplete=off',
            'search-clear-attrs' => 'data-bdwp-selectors=search-clear',
            'search-submit-attrs' => 'data-bdwp-selectors=search-submit',
            'listing-loader-attrs' => 'data-bdwp-selectors=listing-loader',
            'listing-count-attrs' => 'data-bdwp-selection=listing-count',
            'listing-wrapper-attrs' => 'data-bdwp-selectors=listing-wrapper',
            'pagination-wrapper-attrs' => 'data-bdwp-selectors=pagination-wrapper',
            'email-trigger-attrs' => 'data-bdwp-selectors=email-trigger',
            'category-label-attrs' => 'for=bdwp_category[]',
            'category-select-attrs' => 'name=bdwp_category[] multiple=multiple data-bdwp-selectors=select2',
            'form-section' => $form,
            'pagination-section' => $pagination,
            'category-options' => $category_options,
            'category-description' => get_option('bdwp-category-description', 'Enter category'),
            'country-select' => "<div class='location-loader'></div><select name='bdwp_country' id='bdwp_country' data-bdwp-selectors='select2country' data-bdwp-location='country'></select>",
            'area-select' => "<div class='location-loader'></div><select name='bdwp_area' id='bdwp_area' data-bdwp-selectors='select2area' data-bdwp-location='area'></select>",
            'prefecture-select' => "<div class='location-loader'></div><select name='bdwp_prefecture' id='bdwp_prefecture' data-bdwp-selectors='select2prefecture' data-bdwp-location='prefecture'></select>",
            'city-select' => "<div class='location-loader'></div><select name='bdwp_city[]' id='bdwp_city' data-bdwp-selectors='select2city' multiple data-bdwp-location='city'></select>",
            'location-section' => false,
            'location-label' => true,
            'location-description' => get_option('bdwp-location-description', 'Enter city'),
            'languages-section' => (bool)get_option('bdwp-searchbox-language', true),
            'languages-label-attrs' => 'for=bdwp_language[]',
            'languages-select-attrs' => 'name=bdwp_language[] multiple=multiple data-bdwp-selectors=select2',
            'languages-options' => $languages_options,
            'languages-description' => get_option('bdwp-language-description', 'Select languages'),
            'listing-url' => $listing_url,
            'banner-a' => '',
            'banner-b' => '',
        );
        
        if (get_option('bdwp-searchbox-location', true)) {
            $data['location-section'] = true;
            $location_types = ['country', 'area', 'prefecture', 'city'];
            $location_functions = ['countries', 'areas', 'prefectures', 'cities'];

            $load_start_index = 0;
            $ancestor_value = 0;

            if (get_option('bdwp-set-default-location', false)) {

                foreach ($location_types as $index => $type) {
                    $default_location_value = get_option("bdwp-default-{$type}", 'none');
                    if (empty($default_location_value)) 
                        $default_location_value = 'none';
                    
                    if ($default_location_value !== 'none') {
                        $ancestor_value = $default_location_value;
                        $load_start_index = $index + 1;
                        $data["{$type}-select"] = '';
                    } else {
                        break;
                    }
                }
            }

            for ($load_start_index; $load_start_index < count($location_types); $load_start_index++) {

                $location_type = $location_types[$load_start_index];
                $location_function = 'bdwp_get_' . $location_functions[$load_start_index];
                $location_value = null;

                if (!empty($_GET["bdwp_{$location_type}"])) {
                    $location_value = $_GET["bdwp_{$location_type}"];
                }

                $location_options = call_user_func(
                    $location_function,
                    $ancestor_value,
                    true
                );
                if ($location_type == 'city') {
                    $multiple = 'multiple';
                } else {
                    $multiple = '';
                }
                $data["{$location_type}-select"] = "<div class='location-loader'></div><select name='bdwp_{$location_type}[]' id='bdwp_{$location_type}' data-bdwp-selectors='select2{$location_type}' data-bdwp-location='{$location_type}' {$multiple}><option></option><option value='-1'>Select All</option>";
                foreach ($location_options as $option) {
                    if ($option['value'] == $location_value) {
                        $attr = ' selected';
                    } else {
                        $attr = '';
                    }
                    $data["{$location_type}-select"] .= "<option value='{$option['value']}'{$attr}>{$option['title']}</option>";
                }
                $data["{$location_type}-select"] .= "</select>";

                if (empty($location_value)) {
                    break;
                } else {
                    $ancestor_value = $location_value;
                }

            }
                   
        }   

        if (get_option('bdwp-banner-a-switch', false)) {
            $data['banner-a'] = "<a href='#' class='bdwp-banner'><img src='" . BDWP_ASSETS_URL . "banner-a.jpg' /></a>"; 
        }
        if (get_option('bdwp-banner-b-switch', false)) {
            $data['banner-b'] = "<a href='#' class='bdwp-banner'><img src='" . BDWP_ASSETS_URL . "banner-b.jpg' /></a>"; 
        }
    }
    if ($bdwp_query->is_single) {
        if ( (stripos( $_SERVER['HTTP_REFERER'], $listing_url ) === false) && (stripos( $_SERVER['HTTP_REFERER'], 'bdwp_listing=true' ) === false) ) {
            $listing_url = bdwp_get_listing_url();
        } else {
            $listing_url = $_SERVER['HTTP_REFERER'];
        }
        if ($bdwp_query->is_error) {
            $template = 'linked_warning';
            $data = array(
                'message' => get_option('bdwp-technical-issues'),
                'link' => $listing_url
            );
            if ($bdwp_query->content_missing) {
                $data['message'] = get_option('bdwp-branch-missing');
            }
        } else {
            $bdwp_query->rewind_branches();
            while ($bdwp_query->have_branches()) {
                $bdwp_query->the_branch();
                $template = get_option(bdwp_get_service_page($bdwp_branch->service)) . '_detailed';
                if (!file_exists($theme_path . $theme . '/' . $template . '.mustache')) {
                    if ($template === 'None_detailed') {
                        $template = 'linked_warning';
                        $data = array(
                            'message' => get_option('bdwp-technical-issues'),
                            'link' => $listing_url
                        );
                        break;
                    } else {
                        $template = get_option('bdwp-default-template') . '_detailed';
                    }
                }

                $grouped_contacts = bdwp_group_contacts($bdwp_branch->contacts);
                $default_contact = bdwp_find_default_contact($grouped_contacts['emails']);
                if ($default_contact == null) {
                    $grouped_contacts['emails'] = [];
                } else {
                    $grouped_contacts['emails'] = [$default_contact];
                }
                $default_contact = bdwp_find_default_contact($grouped_contacts['urls']);
                foreach ($grouped_contacts as $type => $group) {
                    foreach ($group as $index => $contact) {
                        $contact_data = array(
                            'value' => $contact->value,
                            'title' => $contact->title
                        );
                        if ($icons[$contact->sub_type]) {
                            $contact_data['icon'] = $icons[$contact->sub_type];
                        } else {
                            $contact_data['icon'] = $icons[$contact->type];
                        }
                        if (strpos($contact_data['icon'], 'fa-') === 0) {
                            $contact_data['icon'] = "<span class='fa fa-lg {$contact_data['icon']}'></span>";
                        } else {
                            $contact_data['icon'] = "<img src='{$contact_data['icon']}' />";
                        }
                        $grouped_contacts[$type][$index] = $contact_data;
                    }
                }

                $sorted_contacts = array(
                    'phones-section' => $grouped_contacts['phones'],
                    'emails-section' => $grouped_contacts['emails'],
                    'urls-section' => $grouped_contacts['urls']
                );
                $photos = $bdwp_branch->photos;
                $photos = array_map(function ($photo) {
                    return array('src' => $photo['origin']);
                }, $photos);
                if ($bdwp_branch->location['coordinates']['lat'] && $bdwp_branch->location['coordinates']['lng']) {
                    $latlong = "{$bdwp_branch->location['coordinates']['lat']},{$bdwp_branch->location['coordinates']['lng']}";
                } else $latlong = false;
                $data = array(
                    'tabs-pool-attrs' => 'data-bdwp-tabs=pool',
                    'full-map-attrs' => 'data-bdwp-selectors=full-map',
                    'mini-map-attrs' => 'data-bdwp-selectors=mini-map',
                    'map-tab-id' => 'bdwp-map-tab',
                    'tabs-trigger' => 'data-bdwp-tabs-trigger',
                    'tabs-target' => 'data-bdwp-tabs-target',
                    'tabs-remote' => 'data-bdwp-tabs-remote',
                    'image-section' => false,
                    'map-section' => false,
                    'photo-section' => false,
                    'short-info-section' => false,
                    'address-section' => false,
                    'categories-section' => false,
                    'description-section' => false,
                    'products-section' => false,
                    'eshop-section' => false,
                    'weather-section' => false,
                    'image' => $bdwp_branch->image['thumbnail'],
                    'title' => $bdwp_branch->title,
                    'uc-title' => $bdwp_branch->filtered_title(),
                    'slogan' => $bdwp_branch->slogan,
                    'address' => $bdwp_branch->get_location('%address% %city% %country% %zip%', ', '),
                    'email-form-selector-attrs' => 'data-email-id=' . $bdwp_branch->id . ' data-bdwp-modal-trigger=mail-form',
                    'photos' => $photos,
                    'categories' => $bdwp_branch->the_categories(', ', true, false),
                    'description' => $bdwp_branch->description,
                    'products' => $bdwp_branch->products_descrition,
                    'eshop-href' => get_option('bdwp-eshop-url'),
                    'eshop-title' => get_option('bdwp-eshop-link'),
                    'weather' => bdwp_get_weather_widget_content($latlong, true),
                    'link' => $bdwp_branch->get_permalink(),
                    'bookmark-attrs' => 'data-bdwp-selectors=bookmark',
                    'back-link' => $listing_url,
                    'map-directions-url' => bdwp_get_google_direction_link()
                );
                if ($data['image']) $data['image-section'] = true;
                if ($bdwp_branch->location['geo_status'] != 200) {
                    $data['map-section'] = true;
                    $data['weather-section'] = true;
                }
                if (count($bdwp_branch->photos)) $data['photo-section'] = true;
                if ($data['map-section'] || $data['photo-section']) $data['short-info-section'] = true;
                if ($data['address']) $data['address-section'] = true;
                if ($data['categories']) $data['categories-section'] = true;
                if ($data['description']) $data['description-section'] = true;
                if ($data['products']) $data['products-section'] = true;
                if ($data['eshop-href'] && $data['eshop-title']) $data['eshop-section'] = true;
                $data = array_merge($data, $sorted_contacts);
            }
            $bdwp_query->rewind_branches();
        }
    }
    $data['widget-area'] = array(
        '1' => $widget_area_1,
        '2' => $widget_area_2,
        '3' => $widget_area_3
    );
    $data['mini-map'] = $mini_map;
    $data['full-map'] = $full_map;
    if ($theme == 'BD_Theme') {
        $data['theme-url'] = plugins_url() . '/bdwp-themes/' . $theme . '/';
    } else {
        $data['theme-url'] = BDWP_PLUGIN_URL . 'themes/' . $theme . '/';
    }
    $mustache = new Mustache_Engine(array(
        'loader' => new Mustache_Loader_FilesystemLoader($theme_path . $theme),
    ));

    echo $mustache->render($template, $data);
    bdwp_branch_mail_form($theme);
}

function bdwp_output_template($id, $template) {
    echo "<script id='$id' class='bdwp-template' type='x-tmpl-mustache'>$template</script>";
}

function bdwp_form_select_options($_options, $queried_options) {
    $data = array();
    $_options = array_merge($_options, (array)$queried_options);
    $options = array_unique($_options);
    foreach ($options as $option) {
        if ($option && $option !== '') {
            if (in_array($option, (array)$queried_options)) {
                array_push($data, array('name' => $option, 'selected' => 'selected'));
            } else {
                array_push($data, array('name' => $option));
            }
        }
    }
    return $data;
}

function bdwp_get_weather_widget_content($latlong, $metric_units)
{
    if ($latlong) {
        $forecast = bdwp_get_forecast($latlong, $metric_units);
        $forecast = bdwp_translate_forecast($forecast);
        $count = 0;
        while ($forecast->query->results->channel->item == null && $count < 5) {
            $forecast = bdwp_get_forecast($latlong, $metric_units);
            $count++;
        }
        if ($forecast->query->results->channel->item != null) {
            $mustache = new Mustache_Engine(array(
                'loader' => new Mustache_Loader_FilesystemLoader(BDWP_PLUGIN_PATH . '/templates'),
            ));
            return $mustache->render('weather', (array)$forecast->query->results->channel);
        } else {
            return 'There was a problem retrieving the latest weather information.';
        }
    } else {
        return false;
    }
}

function bdwp_get_forecast($latlong, $metric_units)
{
    $baseUrl = 'http://query.yahooapis.com/v1/public/yql';
    $query = "select * from weather.forecast where woeid in (SELECT woeid FROM geo.places WHERE text='({$latlong})')";
    if ($metric_units) {
        $query .= ' and u="c"';
    }
    $query = urlencode($query);
    $json = file_get_contents("{$baseUrl}?q={$query}&format=json");
    $forecast = json_decode($json);
    return $forecast;
}

function bdwp_translate_forecast($forecast)
{
    $ini_path = BDWP_PLUGIN_PATH . '/settings/translations/weather_gr.ini';
    $ini = file_get_contents($ini_path);
    $translations = parse_ini_string($ini);
    $forecast->query->results->channel->item->condition->text = $translations[$forecast->query->results->channel->item->condition->code];
    foreach ($forecast->query->results->channel->item->forecast as $day_forecast) {
        $day_forecast->day = $translations[$day_forecast->day];
    }
    return $forecast;
}

function bdwp_branch_mail_form($theme = null)
{
    global $bdwp_form_created;
    if ($bdwp_form_created) return;
    if (!$theme) $theme = bdwp_sanitize_theme(get_option('bdwp-theme'));
    if ($theme == 'BD_Theme') {
        $theme_path = BDWP_DEFAULT_THEME_PATH;
    } else {
        $theme_path = BDWP_THEMES_PATH;
    }
    $path = $theme_path . $theme;
    $mustache_form = new Mustache_Engine(array(
        'loader' => new Mustache_Loader_FilesystemLoader($path),
    ));

    $data = array(
        'mail-form-id' => 'bdwp-mail-form',
        'mail-form-attrs' => 'data-bdwp-modal=mail-form style=display:none;',
        'modal-close-shortcut' => 'data-bdwp-selectors=modal-close-shortcut',
        'modal-close-attrs' => 'data-bdwp-selectors=modal-close',
        'header-message-attrs' => 'data-bdwp-selectors=email-form-header-message',
        'loader-attrs' => 'data-bdwp-selectors=email-form-loader',
        'form-data' => '<input type="text" name="branch_id" value="" style="display: none;" />',
        'form-content-attrs' => 'data-bdwp-selectors=email-form-content',
        'name-label-attrs' => 'for=name',
        'name-field-attrs' => 'data-bdwp-selectors=email-form-field id=name type=text name=name required',
        'name-error-attrs' => 'data-bdwp-selectors=email-form-error id=bdwp-mail-form_name_errorloc',
        'email-label-attrs' => 'for=email',
        'email-field-attrs' => 'data-bdwp-selectors=email-form-field id=email type=email name=email required',
        'email-error-attrs' => 'data-bdwp-selectors=email-form-error id=bdwp-mail-form_email_errorloc',
        'subject-label-attrs' => 'for=subject',
        'subject-field-attrs' => 'data-bdwp-selectors=email-form-field id=subject type=text name=subject required',
        'subject-error-attrs' => 'data-bdwp-selectors=email-form-error id=bdwp-mail-form_subject_errorloc',
        'message-label-attrs' => 'for=message',
        'message-field-attrs' => 'data-bdwp-selectors=email-form-field id=message name=message required',
        'message-error-attrs' => 'data-bdwp-selectors=email-form-error id=bdwp-mail-form_message_errorloc',
        'form-modal-attrs' => 'data-bdwp-selectors=email-form-modal',
        'modal-message-attrs' => 'data-bdwp-selectors=email-form-modal-message',
        'modal-ok-btn-attrs' => 'data-bdwp-selectors=email-form-modal-ok'
    );

    echo $mustache_form->render('form', $data);
    $bdwp_form_created = true;
}

function bdwp_template_linker($template) {
    global $bdwp_query;

    if ( $bdwp_query->is_listing || $bdwp_query->is_single) {
        $new_template = locate_template( array( 'bdwp_page_template.php' ) );
        if ( '' != $new_template ) {
            return $new_template;
        } else {
            return BDWP_PLUGIN_PATH . 'templates/bdwp_page_template.php';
        }
    }

    return $template;
}

function bdwp_get_map_data() {
    global $bdwp_branch;

    if ($bdwp_branch->location['geo_status'] == 200) return;
    $branch_data = [];
    $info = '<h2 class="bdwp_map-widget-title"><a href="' . $bdwp_branch->get_permalink() . '">' . $bdwp_branch->title . '</a></h2>';
    $info .= '<div class="bdwp_map-widget-slogan"><em>'.$bdwp_branch->slogan.'</em></div>';
    $info .= '<div class="bdwp_map-widget-location">'.$bdwp_branch->get_location().'</div>';
    $branch_data['info'] = $info;
    $branch_data['type'] = $bdwp_branch->service;
    $branch_data['location']['lat'] = (float)$bdwp_branch->get_location('%coordinates.lat%');
    $branch_data['location']['lng'] = (float)$bdwp_branch->get_location('%coordinates.lng%');

    return $branch_data;
}

function bdwp_map($id, $width = '100%', $height = '100%') {
    global $bdwp_query;
    $branches_data = [
        'id' => $id,
        'locations' => []
    ];

    if ($bdwp_query->is_single) {
        $bdwp_query->rewind_branches();
        while ($bdwp_query->have_branches()) {
            $bdwp_query->the_branch();
            $branches_data['locations'][] = bdwp_get_map_data();
        }
        $bdwp_query->rewind_branches();
    }

    $json = json_encode($branches_data);

    $map = <<<EOT

<div id="{$id}-container" class="bdwp-map-container" style="width: $width;">
    <style>
        #{$id}-container:before {
            padding-top: $height !important;
        }
    </style>
    <div class="bdwp-map-container-inner">
        <div id="$id" class="bdwp-map-canvas"></div>
    </div>
    <script>
        jQuery(function($) {
            $(document).ready(function() {
                window.BDWP.maps.push($json);
                window.BDWP.initMap();
            });
        });
    </script>
</div>
EOT;
        
    return $map;
}

function bdwp_sanitize_theme($theme, $change_default_template = true)
{
    if (count(glob(BDWP_THEMES_PATH . $theme . '/*')) < 3) {
        $theme = 'BD_Theme';
        if ($change_default_template) update_option('bdwp-default-template', 'Basic');
    }

    return $theme;
}

function bdwp_find_default_template($theme)
{
    if ($theme == 'BD_Theme') {
        $theme_path = BDWP_DEFAULT_THEME_PATH;
    } else {
        $theme_path = BDWP_THEMES_PATH;
    }
    $templates = glob($theme_path . $theme . '/*detailed.mustache');
    $templates = array_map(function ($template){
        return basename($template, '_detailed.mustache');
    }, $templates);
    if (in_array('Free', $templates)) {
        return 'Free';
    } elseif (in_array('Basic', $templates)) {
        return 'Basic';
    } else {
        return 'None';
    }
}