<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bdwp_array_group($array, $group_key, $order = null)
{
    $arr = array();

    foreach ($array as $key => $item) {
        $arr[$item[$group_key]][$key] = $item;
    }
    if ($order) {
        uksort($arr, function ($a, $b) use ($order) {
            return array_search($a, $order) > array_search($b, $order);
        });
    }
    return $arr;
}

function bdwp_array_flatten($array)
{
    $arr = array();

    foreach ($array as $item) {
        $arr = array_merge($arr, $item);
    }
    return $arr;
}

function bdwp_get_admin_path() {
    // Replace the site base URL with the absolute path to its installation directory.
    $blogUrl = preg_replace("(^https?://)", "", get_bloginfo( 'url' ));
    $adminUrl = preg_replace("(^https?://)", "", get_admin_url());
    $admin_path = str_replace( $blogUrl . '/', ABSPATH,  $adminUrl);
    // Make it filterable, so other plugins can hook into it.
    $admin_path = apply_filters( 'my_plugin_get_admin_path', $admin_path );
    return $admin_path;
}

function bdwp_get_google_direction_link() {
    global $bdwp_branch;
    $latlng = $bdwp_branch->location['coordinates']['lat'] . ',' . $bdwp_branch->location['coordinates']['lng'];

    return "https://www.google.com/maps/dir/current+location/{$latlng}/";
}