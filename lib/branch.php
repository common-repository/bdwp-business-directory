<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once 'contact.php';

class BDWP_Branch
{
    public $id;
    public $service;
    public $title;
    public $created_at;
    public $updated_at;
    public $location;
    public $image;
    public $slogan;
    public $description;
    public $products_description;
    public $photos;
    public $categories;
    public $contacts;

    public function __construct($branch)
    {
        $this->id = $branch['id'];
        $this->location = [
            'address' => $branch['address'],
            'city' => $branch['city'],
            'zip' => $branch['zip'],
            'country' => $branch['country'],
            'coordinates' => [
                'lat' => $branch['lat'],
                'lng' => $branch['lng']
            ],
            'geo_status' => $branch['geo_status']
        ];
        $this->created_at = $branch['created_at'];
        $this->updated_at = $branch['updated_at'];
        $this->title = $branch['account']['title'];
        if ($branch['service_status'] === 'Active') {
            $this->service = strtolower(sanitize_title_with_dashes($branch['service']));
        } else {
            $this->service = 'free';
        }
        $custom_logo_id = get_theme_mod('custom_logo');
        $image = wp_get_attachment_image_src($custom_logo_id, 'full');
        if (empty($branch['account']['image']) || $this->service == 'free') {
            if ($image[0]) {
                $this->image['thumbnail'] = $image[0];
            }
        } else {
            $this->image = $branch['account']['image'];
        }
        $this->slogan = $branch['account']['slogan'];
        $this->description = $branch['account']['description'];
        $this->products_descrition = $branch['account']['products_services'];
        $this->photos = $branch['photos'];
        $this->categories = array_map(function ($category) {
            return [
                'id' => $category['id'],
                'parent' => $category['parent'],
                'title' => $category['title'],
                'icon' => $category['icon'],
                'created_at' => $category['created_at'],
                'updated_at' => $category['updated_at']
            ];
        }, (array)$branch['account']['categories']);
        if (is_array($branch['contacts'])) {
            $contacts = array_map(function ($contact) {
                return new BDWP_Contact($contact['contact_value'], $contact['contact_title'], $contact['contact_type'], $contact['sub_type'], $contact['default'], $contact['created_at'], $contact['updated_at']);
            }, $branch['contacts']);

            $contacts = array_map(function ($contact) {
                return ['type' => $contact->type, 'value' => $contact];
            }, $contacts);

            $contacts = bdwp_array_group($contacts, 'type', ['Phone', 'Email', 'Url']);

            $contacts = bdwp_array_flatten($contacts);

            $contacts = array_map(function ($contact) {
                return $contact['value'];
            }, $contacts);
            $this->contacts = $contacts;
        }
    }

    public static function load($branch)
    {
        return new self($branch);
    }

    public function get_id($raw = false)
    {
        $raw_id = $this->id;

        if ($raw) {
            return $raw_id;
        }

        $id = bdwp_hash_encode($raw_id);
        return $id;
    }

    public function get_permalink()
    {
        $id = $this->get_id();
        $title = $this->title;

        $service_page_id = get_option('bdwp-branch-page');
        if (!$service_page_id) return null;

        $url = get_the_permalink($service_page_id);
        $url = add_query_arg('b', $id, $url);
        $url = add_query_arg('t', $title, $url);
        return $url;
    }

    public function get_image_src($size = null)
    {
        if ($size === null) {
            $size = 'thumbnail';
        }

        if (isset($this->image[$size])) {
            return $this->image[$size];
        }
        return null;
    }

    public function get_image($size = null)
    {
        if (empty($this->image)) return null;

        $src = $this->get_image_src($size);
        $classes = ['bdwp_image'];
        $classes[] = 'bdwp-image-' . $size;
        $classes = array_map('sanitize_html_class', $classes);
        $classes = implode(' ', $classes);
        $title = esc_attr($this->title);

        return "<img src='$src' alt='$title' class='$classes'>";

    }

    public function get_location($format = '%address% %city% %country%', $devider = ', ')
    {
        $format = str_replace('%address', $this->location['address'], $format);
        $format = str_replace('%city', $this->location['city'], $format);
        $format = str_replace('%country', $this->location['country'], $format);
        $format = str_replace('%zip', $this->location['zip'], $format);
        $format = str_replace('%coordinates.lat', $this->location['coordinates']['lat'], $format);
        $format = str_replace('%coordinates.lng', $this->location['coordinates']['lng'], $format);

        $parts = [];
        $_parts = explode('%', $format);
        foreach ($_parts as $key => $_part) {
            $part = trim($_part);
            if ($part != '') array_push($parts, $part);
        }
        $format = implode($devider, $parts);

        return $format;
    }

    public function has_categories()
    {
        return count($this->categories) > 0;
    }

    public function get_category_querylink($category) {
        $link = get_permalink(get_option('bdwp-cl-page'));
        $title = $category['title'];
        $link = add_query_arg('bdwp_category[]', $title, $link);
        return '<a href="' . $link . '">' . $category['title'] . '</a>';
    }

    public function get_categories($linked = null)
    {
        if ($linked === null) {
            $linked = false;
        }

        $categories = $this->categories;
        $categories = array_map(function ($category) use ($linked) {
            if ($linked) {
                return $this->get_category_querylink($category);
            } else {
                return $category['title'];
            }
        }, $categories);

        return $categories;
    }

    public function the_categories($divider = null, $linked = null, $echo = true)
    {
        if ($divider === null) {
            $divider = ', ';
        }
        $categories = implode($divider, $this->get_categories($linked));
        if ($echo) {
            echo $categories;
        } else {
            return $categories;
        }
    }

    public function get_contacts($array = false)
    {
        $contacts = $this->contacts;
        if ($array) {
            return $contacts;
        } else {
            return implode(PHP_EOL, $contacts);
        }
    }

    public function filtered_title()
    {
        $pairs = [
            'Ά' => 'Α',
            'Έ' => 'Ε',
            'Ή' => 'Η',
            'Ί' => 'Ι',
            'Ό' => 'Ο',
            'Ύ' => 'Υ',
            'Ώ' => 'Ω',
            'ά' => 'α',
            'έ' => 'ε',
            'ή' => 'η',
            'ί' => 'ι',
            'ό' => 'ο',
            'ύ' => 'υ',
            'ώ' => 'ω',
        ];

        $title = $this->title;

        foreach ($pairs as $tonos => $no_tonos) $title = str_replace($tonos, $no_tonos, $title);

        return $title;
    }
}

function bdwp_get_listing_url()
{
    $listing_page_id = (int)get_option('bdwp-cl-page');
    return get_the_permalink($listing_page_id);
}

function bdwp_get_service_page($slug)
{
    return 'bdwp-bp-page-' . $slug;
}

function bdwp_get_all_service_pages()
{
    $services = get_option('bdwp_received_services');
    $service_slugs = array_keys($services);
    $services = array_map('bdwp_get_service_page', $service_slugs);
    $services = array_map('get_option', $services);
    return array_combine($service_slugs, $services);
}