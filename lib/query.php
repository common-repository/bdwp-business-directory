<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BDWP_Query
{
    private $_current_page_id;
    /* @var BDWP_API */
    private $_api;

    /* @var BDWP_Branch[] */
    public $branches;
    /* @var BDWP_Branch */
    public $branch;
    public $branch_count = 0;
    public $current_branch = -1;
    public $found_branches = 0;
    public $max_num_pages = 0;
    public $branch_per_page;
    public $paged;

    public $in_the_loop = false;
    public $is_single = false;
    public $is_listing = false;
    public $is_404 = false;
    public $is_error = false;
    public $content_missing = false;
    public $is_category = false;
    public $debug;

    protected $category_filter;
    protected $location_filter;
    protected $services_filter;
    protected $language_filter;
    protected $keyword;

    public function __construct($args = null, $shortcode = false)
    {
        $this->_current_page_id = get_the_ID();

        $listing_page_id = (int)get_option('bdwp-cl-page');
        $branch_page_ids = bdwp_get_all_service_pages();

        if ($this->_current_page_id === $listing_page_id) {
            $this->is_listing = true;
        }

        if ($_GET['action'] === 'bdwp_fetch_branches') {
            $this->is_listing = true;
        }

        if ($shortcode) {
            $this->is_listing = true;
        }

        if ($this->_current_page_id == get_option('bdwp-branch-page')) {
            $this->is_single = true;
        }

        $this->query($args);
        $this->init();
    }

    public function query($args = null)
    {
        $query_args = [];

        $query_args['service'] = array_keys(array_filter(bdwp_get_all_service_pages()));

        $query_args['service'] = array_map(function ($service) {
            $bdwp_services = get_option('bdwp_received_services');
            return $bdwp_services[$service];
        }, $query_args['service']);

        if ($this->is_single) {
            $id = $_GET['b'];
            if (!$id) $id = $_GET['bdwp_branch'];
            $id = bdwp_hash_decode($id);
            if (filter_var($id, FILTER_VALIDATE_INT)) {
                $query_args['id'] = $id;
            }
        }
        if ($args['pagination'] === '0') {
            $query_args['branch_per_page'] = -1;
        } else {
            $query_args['branch_per_page'] = get_option('bdwp-cl-per-page');
        }
        if (isset($_GET['bdwp_page']) && $page = filter_var($_GET['bdwp_page'], FILTER_VALIDATE_INT)) {
            $query_args['paged'] = $page;
        } else {
            $query_args['paged'] = 1;
        }

        if (isset($_GET['bdwp_category_id']) && $category_id = filter_var($_GET['bdwp_category_id'], FILTER_VALIDATE_INT)) {
            $query_args['category'] = [
                'compare' => '=',
                'terms' => $category_id
            ];
        } elseif (!empty($_GET['bdwp_category'])) {
            $categories = array_filter($_GET['bdwp_category'], 'sanitize_text_field');

            $query_args['keyword'] = implode(' ', $categories);

            $categories_names = bdwp_get_categories_names_and_aliases();
            $categories = [];
            foreach ($_GET['bdwp_category'] as $category) {
                if (in_array($category, $categories_names)) {
                    array_push($categories, $category);
                }
            }
            $categories = implode(',', $categories);
            if (!empty($categories)) {
                $query_args['category'] = [
                    'compare' => 'like',
                    'terms' => $categories
                ];
            }
        }

        $location_types = ['city', 'prefecture', 'area', 'country'];
        $default_location_type = null;
        $default_location_value = null;

        if (get_option('bdwp-set-default-location')) {
            foreach ($location_types as $type_index => $type) {
                $value = get_option("bdwp-default-{$type}");
                if (!empty($value) && $value != 'none') {
                    $default_location_type = $type;
                    $default_location_value = $value;
                    break;
                }
            }
        }

        foreach ($location_types as $index => $type) {
            $this->debug .= ' 1: ' . $type;
            if ($type == $default_location_type) {
                $query_args['location'] = [
                    'compare' => '=',
                    'terms' => $default_location_value,
                    'type' => $type
                ];
                break;
            } elseif (!empty($_GET["bdwp_{$type}"]) && !empty($_GET["bdwp_{$type}"][0])) {
                $query_args['location'] = [
                    'compare' => '=',
                    'terms' => $_GET["bdwp_{$type}"],
                    'type' => $type
                ];
                break;
            }
        }

        if (!empty($_GET['bdwp_language'])) {
            $languages = array_filter($_GET['bdwp_language'], 'sanitize_text_field');
            $query_args['languages'] = [
                'compare' => '=',
                'terms' => $languages
            ];
        }

        if (!empty($_GET['bdwp_keyword'])) {
            $query_args['keyword'] = sanitize_text_field($_GET['bdwp_keyword']);
        }

        if ($args === null) {
            $args = [];
        }
        $args = array_merge($args, $query_args);


        if (isset($args['id']) && $id = filter_var($args['id'], FILTER_VALIDATE_INT)) {
            $this->is_single = $id;
        }

        if (isset($args['branch_per_page']) && $branch_per_page = filter_var($args['branch_per_page'], FILTER_VALIDATE_INT)) {
            $this->branch_per_page = $branch_per_page;
        }

        if (isset($args['paged']) && $page = filter_var($args['paged'], FILTER_VALIDATE_INT)) {
            $this->paged = $page;
        } else {
            $this->paged = 1;
        }

        if (isset($args['category']) && !empty($args['category']['terms'])) {
            if ($args['category']['compare'] !== 'like') {
                $terms = $args['category']['terms'];
                if (!is_array($terms)) {
                    $terms = explode(',', $terms);
                }
                if (count($terms) === 1) {
                    $this->is_category = current($terms);
                }
            } else {
                $args['category']['terms'] = '%'.$args['category']['terms'].'%';
            }

            if (is_array($args['category']['terms'])) {
                $args['category']['terms'] = implode(',', $args['category']['terms']);
            }

            $this->category_filter = $args['category'];
        }

        if (isset($args['location']) && !empty($args['location']['terms'])) {
            if (is_array($args['location']['terms'])) {
                $args['location']['terms'] = implode(',', $args['location']['terms']);
            }
            if ($args['location']['compare'] == 'like') {
                $args['location']['terms'] = '%'.$args['location']['terms'].'%';
            }


            $this->location_filter = $args['location'];
        }

        if (isset($args['service'])) {
            $this->services_filter = implode(',', $args['service']);
        }

        if (isset($args['languages']) && !empty($args['languages']['terms'])) {
            if (count($args['languages']) > 1) {
                $args['languages']['terms'] = implode(',', $args['languages']['terms']);
            }

            $this->language_filter = $args['languages'];
        }

        if (isset($args['keyword'])) {
            $this->keyword = $args['keyword'];
        }
    }

    public function init()
    {
        if ($this->is_listing) {
            $this->_api = BDWP_API::getBranches();
            $this->_api->limit = $this->branch_per_page;
            $this->_api->page = $this->paged;

            /*if ($this->services_filter) {
                $this->_api->addFilter('service', '=', $this->services_filter);
            }*/

            if ($this->category_filter) {
                $this->_api->addFilter('category', $this->category_filter['compare'], $this->category_filter['terms']);
            }
            if ($this->language_filter) {
                $this->_api->addFilter('language', $this->language_filter['compare'], $this->language_filter['terms']);
            }
            if ($this->keyword) {
                $this->_api->addKeyword($this->keyword);
            }
            if ($this->location_filter) {
                $this->_api->addLocation($this->location_filter['type'], $this->location_filter['terms']);
            }
        } elseif (is_int($this->is_single) && $id = filter_var($this->is_single, FILTER_VALIDATE_INT)) {
            $this->_api = BDWP_API::getBranch($id);
        }

        if ($this->_api instanceof BDWP_API) {
            $this->_api->execute();
            $this->debug .= ' API: ' . $this->_api->debug;

            if ($this->is_listing) {
                $this->branches = array_map(['BDWP_Branch', 'load'], (array)$this->_api->content['branches']);

                $pagination = $this->_api->pagination;
                $this->paged = $pagination['current_page'];
                $this->max_num_pages = $pagination['last_page'];
                $this->found_branches = $pagination['total'];
                $this->branch_per_page = $pagination['limit'];
            } elseif ($this->is_single) {
                if ($this->_api->status !== false) {
                    $this->branches = [call_user_func(['BDWP_Branch', 'load'], $this->_api->content['branch'])];
                }
            }

            $this->is_error = !$this->_api->status;

            if ($this->_api->http_code === 404) {
                $this->is_error = true;
                $this->is_404 = true;
                if (bdwp_check_api_state() === 1) {
                    $this->content_missing = true;
                }
            }

            if (!$this->is_error) {
                $this->branch_count = count($this->branches);
            } else {
                $this->branch_count = 0;
            }
        } else {
            $this->is_error = true;
            $this->is_404 = true;
            $this->branch_count = 0;
        }
    }

    public function have_branches()
    {
        if ($this->current_branch + 1 < $this->branch_count) {
            return true;
        } elseif ($this->current_branch + 1 == $this->branch_count && $this->branch_count > 0) {
            do_action_ref_array('bdwp_loop_end', array(&$this));
            $this->rewind_branches();
        }

        $this->in_the_loop = false;
        return false;
    }

    public function rewind_branches()
    {
        $this->current_branch = -1;
        if ($this->branch_count > 0) {
            $this->branch = $this->branches[0];
        }
    }

    public function next_branch()
    {
        $this->current_branch++;

        $this->branch = $this->branches[$this->current_branch];
        return $this->branch;
    }

    public function the_branch()
    {
        global $bdwp_branch;
        $this->in_the_loop = true;

        if ($this->current_branch == -1) {
            do_action_ref_array('bdwp_loop_start', array(&$this));
        }

        $bdwp_branch = $this->next_branch();
    }

    public function render_pagination($pagination_items = null)
    {
        $pages = $this->max_num_pages;
        $current_page = $this->paged;

        if (!$pagination_items || !is_int($pagination_items)) {
            $pagination_items = 5;
        }
        if ($pages < 2) return null;

        if ($current_page > floor($pagination_items / 2)) {
            $start_page = $current_page - floor($pagination_items / 2);
        } elseif ($current_page > $pages - floor($pagination_items / 2)) {
            $start_page = $pages - $pagination_items;
        } else {
            $start_page = 1;
        }
        $start_page = max($start_page, 1);
        ?>
        <nav>
            <?php if ($current_page > 2) { ?>
                <a href='#bdwp-search-form' data-bdwp-page="1">
                    <?= apply_filters('bdwp_first_page_label', 'Αρχική'); ?>
                </a>
            <?php }
            if ($current_page > 1) { ?>
                <a href='#bdwp-search-form' data-bdwp-page="<?= $current_page - 1 ?>">
                    <?= apply_filters('bdwp_prev_page_label', 'Προηγούμενη'); ?>
                </a>
            <?php }
            for ($p = $start_page; $p <= $pages && $p <= ($start_page + $pagination_items - 1); $p++) {
                $label = apply_filters('bdwp_page_label', $p);
                ?>
                <?php if ($p == $current_page) { ?>
                    <span><?= $label; ?></span>
                <?php } else { ?>
                    <a href='#bdwp-search-form' data-bdwp-page="<?= $p ?>"><?= $label; ?></a>
                <?php } ?>
            <?php }
            if ($current_page < $pages) { ?>
                <a href='#bdwp-search-form' data-bdwp-page="<?= $current_page + 1 ?>">
                    <?= apply_filters('bdwp_next_page_label', 'Επόμενη'); ?>
                </a>
            <?php }
            if ($current_page < $pages - 1) { ?>
                <a href='#bdwp-search-form' data-bdwp-page="<?= $pages ?>">
                    <?= apply_filters('bdwp_last_page_label', 'Τελευταία'); ?>
                </a>
            <?php }
            ?>
        </nav>
        <?php
    }
}