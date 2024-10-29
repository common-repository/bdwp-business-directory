<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bdwp_widgets_init() {

    register_sidebar( array(
        'name'          => 'BDWP Widget Area 1',
        'id'            => 'bdwp_widget_area_1',
        'before_widget' => '<div id="bdwp-widget-area-1">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="bdwp-widget-title">',
        'after_title'   => '</h2>',
    ) );
    register_sidebar( array(
        'name'          => 'BDWP Widget Area 2',
        'id'            => 'bdwp_widget_area_2',
        'before_widget' => '<div id="bdwp-widget-area-2">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="bdwp-widget-title">',
        'after_title'   => '</h2>',
    ) );
    register_sidebar( array(
        'name'          => 'BDWP Widget Area 3',
        'id'            => 'bdwp_widget_area_3',
        'before_widget' => '<div id="bdwp-widget-area-3">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="bdwp-widget-title">',
        'after_title'   => '</h2>',
    ) );

}