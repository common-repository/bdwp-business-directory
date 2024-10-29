<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bdwp_set_title( $title )
{
	global $bdwp_branch, $bdwp_query;
    //disabling seo plugins
    remove_action('wp_head','jetpack_og_tags'); // JetPack
    global $wpseo_front;  // Yoast SEO
    if(defined($wpseo_front)){
        remove_action('wp_head',array($wpseo_front,'head'),1);
    }
    elseif (class_exists('WPSEO_Frontend')){
      $wp_thing = WPSEO_Frontend::get_instance();
      remove_action('wp_head',array($wp_thing,'head'),1);
    }
    if (defined('AIOSEOP_VERSION')) { // All-In-One SEO
        global $aiosp;
        remove_action('wp_head',array($aiosp,'wp_head'));
    }

    if ($bdwp_query->have_branches()) {
        $bdwp_query->the_branch();
        $title['title'] = $bdwp_branch->title;
        $bdwp_query->rewind_branches();
    }

    return $title;
}

function bdwp_set_doctype_open_graph($attr) {
    if (strpos($attr, 'xmlns:og') == false) {
        $attr .= 'xmlns:og="http://opengraphprotocol.org/schema/"';
    }
    if (strpos($attr, 'xmlns:fb') == false) {
        $attr .= 'xmlns:fb="http://www.facebook.com/2008/fbml"';
    }
    if (strpos($attr, 'prefix="og: http://ogp.me/ns#"') == false) {
            $attr .= 'prefix="og: http://ogp.me/ns#"';
    }

    return $attr;
}

function bdwp_get_address_string ()
{
    global $bdwp_branch;
    $address = [];
    if ($bdwp_branch->location['address']) array_push($address, $bdwp_branch->location['address']);
    if ($bdwp_branch->location['city']) array_push($address, $bdwp_branch->location['city']);
    if ($bdwp_branch->location['zip']) array_push($address, $bdwp_branch->location['zip']);
    if ($bdwp_branch->location['country']) array_push($address, $bdwp_branch->location['country']);
    return implode(', ', $address) . '.';
}

function bdwp_get_social_title()
{
    global $bdwp_branch;
    $title_parts = [];
    if ($bdwp_branch->title) array_push($title_parts, $bdwp_branch->title);
    if ($bdwp_branch->slogan) array_push($title_parts, $bdwp_branch->slogan);
    return implode(' - ', $title_parts);
}

function bdwp_gather_open_graph_data()
{
    global $bdwp_branch;
    $data = array(
        array(
            'tag' => 'title',
            'value' => bdwp_get_social_title()
        ),
        array(
            'tag' => 'description',
            'value' => bdwp_get_address_string()
        ),
        array(
            'tag' => 'url',
            'value' => $bdwp_branch->get_permalink()
        ),
        array(
            'tag' => 'image',
            'value' => $bdwp_branch->get_image_src()
        )
    );


    return $data;
}

function bdwp_set_open_graph_tag($tag, $value)
{
    if ($value) {
        echo "<meta property='og:{$tag}' content='{$value}'/>";
    }
}

function bdwp_set_open_graph_block()
{
    global $bdwp_query;
    if (!$bdwp_query->is_single) return;
    $open_graph_data = bdwp_gather_open_graph_data();
    foreach ($open_graph_data as $tag_data) {
        bdwp_set_open_graph_tag($tag_data['tag'], $tag_data['value']);
    }
}

function bdwp_gather_twitter_card_data()
{
    global $bdwp_branch;
    $data = array(
        array(
            'tag' => 'card',
            'value' => 'summary'
        ),
        array(
            'tag' => 'title',
            'value' => bdwp_get_social_title()
        ),
        array(
            'tag' => 'description',
            'value' => bdwp_get_address_string()
        ),
        array(
            'tag' => 'image',
            'value' => $bdwp_branch->get_image_src()
        )
    );


    return $data;
}

function bdwp_set_twitter_card_tag($tag, $value)
{
    if ($value) {
        echo "<meta name='twitter:{$tag}' content='{$value}' />";
    }
}

function bdwp_set_twitter_card_block()
{
    global $bdwp_query;
    if (!$bdwp_query->is_single) return;
    $twitter_card_data = bdwp_gather_twitter_card_data();
    foreach ($twitter_card_data as $tag_data) {
        bdwp_set_twitter_card_tag($tag_data['tag'], $tag_data['value']);
    }
}
?>