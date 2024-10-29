<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bdwp_generate_sitemap ( $data )
{
    $content = '';
    foreach ($data as $branch_data) {
        $content .= <<<EOT
    <url>
        <loc>
            {$branch_data['link']}
        </loc>
        <lastmod>
            {$branch_data['time']}
        </lastmod>
    </url>

EOT;
    }
    $content = <<<EOT
<?xml version='1.0' encoding='UTF-8' ?>
<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>
{$content}
</urlset>
EOT;

    return $content;
}


function bdwp_save_sitemap ( $file_path, $content )
{
    $file = fopen( $file_path, "w" ) or die( "Unable to open file!" );
    fwrite( $file, $content );
    fclose( $file );
}

function bdwp_refresh_sitemap ( $file_name )
{
    $sitemap_data = bdwp_get_sitemap_data();
    $plugin_path = BDWP_PLUGIN_PATH;
    $file_path = "{$plugin_path}{$file_name}.xml";
    $file_url = get_home_url() . "/{$file_name}.xml";
    $old_file_hash = md5_file( $file_path );
    $sitemap_content = bdwp_generate_sitemap( $sitemap_data );
    bdwp_save_sitemap( $file_path, $sitemap_content);
    $new_file_hash = md5_file( $file_path );
    if ( $old_file_hash !== $new_file_hash ) {
        $formatted_time = date("Y-m-d\Th:i:s+00:00");
        update_option('bdwp-sitemap-updated-at', $formatted_time);
        bdwp_notify_search_engines( $file_url );
    }
}

function bdwp_sitemap_robots_entry ( $sitemap )
{
    $robots_path = ABSPATH . 'robots.txt';
    $sitemap_string = "\nSitemap: {$sitemap}";
    $content = file_get_contents( $robots_path );
    if ( strpos( $content, $sitemap_string ) === false ) {
        $file = fopen( $robots_path, "a" ) or die( "Unable to open file!" );
        fwrite( $file, $sitemap_string );
        fclose( $file );
    }
}

function bdwp_ping_search_engines ( $list, $sitemap )
{
    $sitemap_url = urlencode( $sitemap );
    $ping_url = "ping?sitemap={$sitemap_url}";
    foreach ( $list as $search_engine ) {
        $url = "{$search_engine}{$ping_url}";
        wp_remote_get( $url );
    }
}

function bdwp_notify_search_engines ( $sitemap )
{
    $ini_path = BDWP_PLUGIN_PATH . '/settings/sitemap.ini';
    $settings = parse_ini_file( $ini_path, false, INI_SCANNER_TYPED );
    $settings['list'] = explode( ',', $settings['list']);
    if ( $settings['robots'] ) {
        bdwp_sitemap_robots_entry( $sitemap );
    }
    bdwp_ping_search_engines( $settings['list'], $sitemap );
}

function bdwp_yoast_sitemap_addition() {
    global $bdwp_sitemap_name;
    $url = get_site_url(null, "/{$bdwp_sitemap_name}.xml");
    $lastmod = get_option('bdwp-sitemap-updated-at');
    $sitemap_custom_items = "
<sitemap>
<loc>{$url}</loc>
<lastmod>{$lastmod}</lastmod>
</sitemap>";

    return $sitemap_custom_items;
}

function bdwp_redirect_sitemap() {
    global $bdwp_sitemap_name;
    if ( $_SERVER["REQUEST_URI"] === "/{$bdwp_sitemap_name}.xml" ) {
        $redirect = BDWP_PLUGIN_URL . "{$bdwp_sitemap_name}.xml";
        wp_redirect($redirect);
        exit;
    }
}