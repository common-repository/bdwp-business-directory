<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bdwp_fetch_branches()
{
    global $bdwp_query, $bdwp_the_query, $bdwp_branch;
    $bdwp_the_query = $bdwp_query = new BDWP_Query($_GET['query_args']);

    ob_start();
    $bdwp_query->render_pagination();
    $pagination = ob_get_contents();
    ob_clean();

    $branch_data = array(
        'error' => $bdwp_query->is_error,
        'branch_count' => $bdwp_query->found_branches,
        'current_page' => $bdwp_query->paged,
        'last_page' => $bdwp_query->max_num_pages,
        'pagination' => $pagination,
        'branches' => array(),
        'locations' => array()
    );

    while ($bdwp_query->have_branches()) {
        $bdwp_query->the_branch();

        $service_slug = sanitize_title_with_dashes($bdwp_branch->service);

        $template = get_option(bdwp_get_service_page($service_slug));
        if (!$template) $template = get_option('bdwp-default-template');


        $contacts = $bdwp_branch->get_contacts('array');

        $grouped_contacts = array_map(function($group){
            return bdwp_find_default_contact($group)->value;
        }, bdwp_group_contacts($contacts));

        $filtered_contacts = ['phone' => '', 'email' => '', 'url' => ''];
        if ($grouped_contacts['phones'] !== null)
            $filtered_contacts['phone'] = $grouped_contacts['phones'];
        if ($grouped_contacts['emails'] !== null || $grouped_contacts['emails'] !== 'NO_EMAIL')
            $filtered_contacts['email'] = $grouped_contacts['emails'];
        if ($grouped_contacts['urls'] !== null)
            $filtered_contacts['url'] = $grouped_contacts['urls'];

        $permalink = $bdwp_branch->get_permalink();
        $current_branch = array(
            'template' => '#' . $template . '_branch_sort',
            'email-form-selector-attrs' => 'data-email-id=' . $bdwp_branch->id . ' data-bdwp-modal-trigger=mail-form',
            'map-link-attrs' => 'href="' . $permalink . '#bdwp-map-tab"',
            'email-section' => false,
            'url-section' => false,
            'map-secton' => false,
            'service' => $bdwp_branch->service,
            'id' => $bdwp_branch->id,
            'permalink' => $permalink,
            'title' => $bdwp_branch->title,
            'uc-title' => $bdwp_branch->filtered_title(),
            'image' => $bdwp_branch->get_image(),
            'slogan' => $bdwp_branch->slogan,
            'address' => $bdwp_branch->get_location(),
            'contacts' => $filtered_contacts,
            'phone' => $filtered_contacts['phone'],
            'categories' => $bdwp_branch->the_categories(', ', $linked = null, false),
            'eshop-section' => get_option('bdwp-eshop-shown'),
            'eshop-url' => get_option('bdwp-eshop-url'),
            'eshop-link' => get_option('bdwp-eshop-link'),
            'geo-status' => $bdwp_branch->location['geo_status']
        );

        if ($current_branch['contacts']['email']) {
            $current_branch['email-section'] = true;
        }

        if ($current_branch['contacts']['url']) {
            $current_branch['url-section'] = true;
        }

        if ($current_branch['geo-status'] != '200') {
            $current_branch['map-section'] = true;
        }

        array_push($branch_data['branches'], $current_branch);

        if ($bdwp_branch->location['geo_status'] != 200) {
            $branch_data['locations'][] = bdwp_get_map_data();
        }
        if (!$current_branch['eshop-link'] || !$current_branch['eshop-url']) {
            $current_branch['eshop-section'] = false;
        }
    }

    echo json_encode($branch_data);
    die();
}

function bdwp_pull_templates () {
    if ($_GET['bdwp_theme'] != 'BD_Theme') {
        $themes_dir = BDWP_THEMES_PATH;
    } else {
        $themes_dir = BDWP_DEFAULT_THEME_PATH;
    }
    $bdwp_theme_templates = glob($themes_dir . $_GET['bdwp_theme'] . '/*_detailed.mustache');
    $bdwp_theme_templates = array_map(function($template){
        $name = basename($template, '_detailed.mustache');
        return [
            'value' => $name,
            'title' => ucwords(str_replace('_', ' ', $name))
        ];
    }, $bdwp_theme_templates);
    echo json_encode($bdwp_theme_templates);
    die();
}

function bdwp_send_mail()
{
    $url = get_option('bdwp-api-url') . 'email/send';
    $body = wp_parse_args($_POST['form_data']);

    $args = array(
        'body' => $body,
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array( 'Authorization' => get_option('bdwp-api-key') ),
        'cookies' => array()
    );

    $response = wp_remote_post($url, $args);
    $response_body = wp_remote_retrieve_body( $response );

    echo $response_body;
    wp_die();
}

function bdwp_fetch_locations()
{
    switch ($_POST['bdwp_location_type']) {
        case 'country':
            echo json_encode(
                bdwp_get_countries(true)
            );
            break;

        case 'area':
            echo json_encode(
                bdwp_get_areas($_POST['bdwp_location_id'], true)
            );
            break;

        case 'prefecture':
            echo json_encode(
                bdwp_get_prefectures($_POST['bdwp_location_id'], true)
            );
            break;

        case 'city':
            echo json_encode(
                bdwp_get_cities($_POST['bdwp_location_id'], true)
            );
            break;
        
        default:
            echo json_encode(
                bdwp_get_countries(true)
            );
            break;
    }

    wp_die();
}