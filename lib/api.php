<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @property array $pagination
 * @property integer $http_code
 * @property integer $content
 * @property boolean $status;
 *
 * @property BDWP_URL $url;
 */
class BDWP_API
{
    protected $status;
    protected $content;
    protected $http_code;
    protected $pagination;

    public $page = 1;
    public $limit;
    public $debug;

    protected $url;
    protected $filters = [];

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return null;
    }

    public function addFilter($name, $condition, $value)
    {
        if (!in_array($condition, ['<', '>', '=', '<>', 'like'])) return false;
        if (!$value) return false;

        $value = urlencode($value);

        $this->filters[$name] = [$name, $condition, $value];
        $this->url->filters = array_values(array_map(function ($elem) {
            return implode('|', $elem);
        }, $this->filters));
        return $this;
    }

    public function addKeyword($keyword)
    {
        $this->url->keyword = urlencode($keyword);
    }

    public function addLocation($type, $value)
    {
        $field = "{$type}_id";
        $this->url->$field = urlencode($value);
    }

    protected function __construct($section)
    {
        $API_URL = get_option('bdwp-api-url');

        $baseUrl = $API_URL . $section;
        $this->url = new BDWP_URL($baseUrl);
    }

    public static function request($url)
    {
        $API_KEY = get_option('bdwp-api-key');

        $args = array(
        	'timeout' => 30,
            'headers' => array(
                'Authorization' => $API_KEY
            )
        );
        $response = wp_remote_get($url, $args);
        //echo '<div style="display: none;">' . $url . '</div>';
        //echo '<div style="display: none;">' . var_export($response, true) . '</div>';
        $http_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response = ['http_code' => $http_code, 'body' => $response_body];
        return $response;
    }

    public function execute()
    {
        if ($this->page > 1 && is_int($this->page)) {
            $this->url->page = $this->page;
        }

        if ($this->limit) {
            $this->url->limit = $this->limit;
        }

        $url = $this->url->getUrl();

        $this->debug = $url;
        $response = $this->request($url);

        if ($response['body'] && in_array($response['http_code'], [200, 201])) {
            $response = json_decode($response['body'], true);
            $this->content = $response['data'];
            $this->status = (bool)$response['status'];
            $this->http_code = $response['meta']['http_code'];
            $this->pagination = $response['meta']['pagination'];
        } else {
            $this->status = false;
            $this->http_code = $response['http_code'];
        }

        return $this;
    }

    public static function getCountries()
    {
        return new self('locations/countries');
    }

    public static function getAreas($country_id)
    {
        return new self("locations/countries/{$country_id}/areas");
    }

    public static function getPrefectures($area_id)
    {
        return new self("locations/areas/{$area_id}/prefectures");
    }

    public static function getCities($prefecture_id)
    {
        return new self("locations/prefectures/{$prefecture_id}/cities");
    }

    public static function legacyGetCities()
    {
        return new self('cities');
    }

    public static function getBranches()
    {
        return new self('branches');
    }

    public static function getBranch($id)
    {
        return new self('branches/' . $id);
    }

    public static function getCategories()
    {
        return new self('categories');
    }

    public static function getServices()
    {
        return new self('services');
    }

    public static function getLanguages()
    {
        return new self('languages');
    }

    public static function getIconGroups()
    {
        return new self('groups');
    }

    public static function ping()
    {
        return new self('ping');
    }
}

function bdwp_get_all_services()
{
    if(!bdwp_check_api()) {
        return array();
    }
    $api = BDWP_API::getServices();
    $api->execute();
    $services = (array) $api->content['services'];
    $services = array_map(function ($service) {
        return $service['service_group'];
    }, $services);
    $services = array_unique($services);

    $services = array_merge(['Free'], $services);

    $service_titles = $services;
    $service_slugs = array_map('sanitize_title_with_dashes', $services);
    $services = array_combine($service_slugs, $service_titles);

    return $services;
}

function bdwp_get_languages()
{
    if(!bdwp_check_api()) {
        return array();
    }
    $api = BDWP_API::getLanguages();
    $api->url->sort = 'language|asc';
    $api->execute();
    $languages = (array) $api->content['languages'];
    $languages = array_unique($languages);

    return $languages;
}

function bdwp_get_categories_names_and_aliases ()
{
    $api = BDWP_API::getCategories();
    $api->limit = -1;
    $api->url->fields = 'title,aliases';
    $api->execute();

    $categories = $api->content['categories'];
    $names = [];
    foreach ($categories as $category) {
        array_push($names, $category['title']);
        if ($category['aliases']) {
            $aliases = explode(',', $category['aliases']);
            $names = array_merge($names, $aliases);
        }
    }
    sort($names);
    return $names;
}

function bdwp_get_sitemap_data()
{
    $api = BDWP_API::getBranches();
    $api->limit = -1;
    $api->url->only = 'id,updated_at,account.title';
    $api->execute();
    $data = $api->content['branches'];
    $base_url = get_the_permalink(get_option('bdwp-branch-page'));
    $sitemap_data = [];
    foreach ($data as $index => $branch_data) {
        $title = urlencode( $branch_data['account']['title'] );
        $hash = bdwp_hash_encode( $branch_data['id'] );
        $link = "{$base_url}?b={$hash}&amp;t={$title}";
        $time = str_replace( ' ', 'T', $branch_data['updated_at'] ) . '+00:00';
        $sitemap_data[$index] = array( 'link' => $link, 'time' => $time );
    }
    return $sitemap_data;
}

function bdwp_locations_api_prepare(&$api, $name_field)
{
    $api->limit = -1;
    $api->url->sort = "{$name_field}|asc";
    $api->execute();
}

function bdwp_location_filter($locations, $ids = false, $name_field = null)
{
    if (empty($locations))
        return [];
    global $field;
    $field = $name_field;
    if ($ids) {
        $locations = array_map(function ($location) {
            global $field;
            return ['value' => $location['id'], 'title' => $location[$field]];
        }, $locations);
    } else {
        $locations = array_map(function ($location) {
            global $field;
            return $location[$field];
        }, $locations);
        $locations = array_unique($locations);
    }
    return $locations;
}

function bdwp_get_countries($dummy = true, $ids = false)
{
    $api = BDWP_API::getCountries();
    bdwp_locations_api_prepare($api, 'country');
    return bdwp_location_filter(
        $api->content['countries'],
        $ids,
        'country'
    );
}

function bdwp_get_areas($country_id, $ids = false)
{
    $api = BDWP_API::getAreas($country_id);
    bdwp_locations_api_prepare($api, 'area_name');
    return bdwp_location_filter(
        $api->content['areas'],
        $ids,
        'area_name'
    );
}

function bdwp_get_prefectures($area_id, $ids = false)
{
    $api = BDWP_API::getPrefectures($area_id);
    bdwp_locations_api_prepare($api, 'prefecture');
    return bdwp_location_filter(
        $api->content['prefectures'],
        $ids,
        'prefecture'
    );
}

function bdwp_get_cities($prefecture_id, $ids = false)
{
    $api = BDWP_API::getCities($prefecture_id);
    bdwp_locations_api_prepare($api, 'city');
    return bdwp_location_filter(
        $api->content['cities'],
        $ids,
        'city'
    );
}

function bdwp_legacy_get_cities($ids = false)
{
    $api = BDWP_API::getCities();
    $api->limit = -1;
    $api->url->sort = 'city|asc';
    $api->execute();
    $cities = $api->content['cities'];
    if ($ids) {
        $cities = array_map(function ($city) {
            return ['value' => $city['id'], 'title' => $city['city']];
        }, $cities);
    } else {
        $cities = array_map(function ($city) {
            return $city['city'];
        }, $cities);
        $cities = array_unique($cities);
    }
    return $cities;
}

function bdwp_get_icon_groups()
{
    $api = BDWP_API::getIconGroups();
    $api->execute();
    $groups = (array)$api->content['groups'];
    $defaults = array(
        array(
            'name' => 'Default',
            'icon' => 'fa-phone',
            'type' => 'Phone',
            'sub_type' => 'Phone'
        ),
        array(
            'name' => 'Default',
            'icon' => 'fa-envelope',
            'type' => 'Email',
            'sub_type' => 'Email'
        ),
        array(
            'name' => 'Default',
            'icon' => 'fa-globe',
            'type' => 'Url',
            'sub_type' => 'Url'
        )
    );
    $groups = array_merge($defaults, $groups);

    return $groups;
}

function bdwp_get_icons_by_subtype()
{
    $icon_groups = bdwp_get_icon_groups();
    $icons_typed = [];
    foreach ($icon_groups as $group) {
        $custom_icon = get_option('bdwp-icon-' . $group['sub_type']);
        if ($custom_icon === 'none' || $custom_icon == false) {
            $icons_typed[$group['sub_type']] = $group['icon'];
        } else {
            $icons_typed[$group['sub_type']] = $custom_icon;
        }
    }
    return $icons_typed;
}

function bdwp_check_api()
{
    return get_option('bdwp-api-key') && get_option('bdwp-api-url');
}

function bdwp_check_api_state()
{
    $url = get_option('bdwp-api-url') . 'ping';

    $response = BDWP_API::request($url);
    if ($response['body'] == 'Unauthorized.') return 2;
    if ($response['body'] && in_array($response['http_code'], [200, 201])) {
        $response = json_decode($response['body'], true);
        if ($response['status'] == 'pong') return 1;
        else return 0;
    } else return 0;
}