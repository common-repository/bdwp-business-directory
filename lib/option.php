<?php
if ( ! defined( 'ABSPATH' ) ) exit;

abstract class bdwp_OptionBase
{
    protected $options;

    public function __set($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function __get($name)
    {
        return $this->options[$name];
    }
}

class bdwp_OptionPage extends bdwp_OptionBase
{
    protected $args = [
        'parent' => 'options-general.php',
        'title' => null,
        'slug' => null,
        'capability' => 'manage_options'
    ];

    public function __construct($title, $slug, $parent = null, $capability = null)
    {
        $this->args = array_merge($this->args, array_filter(compact('parent', 'title', 'capability', 'slug')));
        add_action('admin_menu', [$this, 'registerPage']);
    }

    public function registerPage()
    {
        add_submenu_page($this->args['parent'], $this->args['title'], $this->args['title'], $this->args['capability'], $this->args['slug'], [$this, 'renderPage']);
    }

    public function do_settings_sections()
    {
        global $wp_settings_sections, $wp_settings_fields;

        $page = $this->args['slug'];

        if ( ! isset( $wp_settings_sections[$this->args['slug']] ) )
            return;
        echo '<menu data-bdwp-tabs="pool" class="bdwp-tabs-pool">';
        $first = true;
        foreach ( (array) $wp_settings_sections[$page] as $section ) {
            if ( $section['title'] && strpos($section['id'], 'inner') !== 0) {
                if ($first) {
                    echo "<menuitem data-bdwp-tabs-trigger='{$section['id']}' class='active'><h2>{$section['title']}</h2></menuitem>\n";
                    $first = false;
                } else {
                    echo "<menuitem data-bdwp-tabs-trigger='{$section['id']}'><h2>{$section['title']}</h2></menuitem>\n";
                }
            }
        }
        echo '</menu>';
        echo '<div class="bdwp-tabs-wrapper">';
        foreach ( (array) $wp_settings_sections[$page] as $section ) {
            if ( ! isset( $wp_settings_fields ) || !isset( $wp_settings_fields[$page] ) )
                continue;
            if (strpos($section['id'], 'inner') === 0) {
                $target = substr($section['id'], strpos($section['id'], '-') + 1);
            } else {
                $target = $section['id'];
            }

            echo "<section data-bdwp-tabs-target='$target'><table class='form-table'>";
            if ( $section['callback'] )
                call_user_func( $section['callback'], $section );
            do_settings_fields( $page, $section['id'] );
            echo '</table></section>';
        }
        echo '</div>';
    }

    public function renderPage()
    {
        wp_enqueue_media();
        ?>
        <div class="wrap">
            <h1><?= $this->args['title'] ?></h1>

            <form method="post" action="<?= admin_url('options.php') ?>">
                <?php
                settings_fields($this->args['slug']);
                $this->do_settings_sections();
                submit_button(); ?>
            </form>
            <div id="bdwp-modal-wrapper">
                <div id="bdwp-fa-icon-modal">
                    <select id="fa-icon-name" autocomplete="off"><option></option></select>
                    <div><button id="fa-icon-cancel" class="button">Cancel</button><button id="fa-icon-select" class="button button-primary">Set icon</button></div>
                </div>
            </div>
        </div>
        <?php
    }

    public function addSection($title, $slug, $heading = null, $heading_class = null)
    {
        return new bdwp_OptionSection($title, $slug, $heading, $heading_class, $this->args['slug']);
    }

}

class bdwp_OptionSection extends bdwp_OptionBase
{
    protected $args = [
        'title' => null,
        'slug' => 'default',
        'page' => 'options-general.php',
        'heading' => null,
        'heading_class' => null
    ];

    public function __construct($title, $slug = null, $heading = null, $heading_class = null, $page = null)
    {
        $this->args = array_merge($this->args, array_filter(compact('title', 'page', 'slug', 'heading', 'heading_class')));
        add_action('admin_init', [$this, 'registerSection']);
    }

    public function registerSection()
    {
        add_settings_section($this->args['slug'], $this->args['title'], [$this, 'cb'], $this->args['page']);
    }

    public function addField($title, $slug, $type = null, $error = 'none')
    {
        return new bdwp_OptionField($title, $slug, $type, $error, $this->args['page'], $this->args['slug']);
    }

    public function cb()
    {
        if ($this->args['heading']) echo "<h2 class='{$this->args['heading_class']}'>{$this->args['heading']}</h2>";
    }
}

class bdwp_OptionField extends bdwp_OptionBase
{
    protected $args = [
        'title' => null,
        'slug' => null,
        'type' => 'text',
        'section' => 'default',
        'page' => 'options-general.php'
    ];

    public function __construct($title, $slug, $type = null, $error = 'none', $page = null, $section = null)
    {
        $this->args = array_merge($this->args, array_filter(compact('title', 'page', 'slug', 'section', 'type', 'error')));
        add_action('admin_init', [$this, 'registerField']);
        add_filter('option_' . $slug, [$this, 'setDefaultOptionValue']);
    }

    public function registerField()
    {
        $args = [
            $this->args['title'],
            $this->args['slug']
        ];
        add_settings_field($this->args['slug'], $this->args['title'], [$this, 'render'], $this->args['page'], $this->args['section'], $args);
        register_setting($this->args['page'], $this->args['slug'], [$this, 'sanitize']);
    }

    public function setDefaultOptionValue($value)
    {
        if (empty($value)) {
            $value = $this->options['default'];
        }
        return $value;
    }

    public function render($args)
    {
        $func = 'render' . ucfirst($this->args['type']);
        if (method_exists($this, $func)) {
            return call_user_func_array([$this, $func], $args);
        } else {
            throw new Exception('Wrong Input Type');
        }
    }

    public function sanitize($value)
    {
        $func = 'sanitize' . ucfirst($this->args['type']);
        $error = 'bdwp_error_' . $this->args['error'];

        if (method_exists($this, $func)) {
            $value = call_user_func_array([$this, $func], func_get_args());
        }
        if (function_exists($error)) {
            call_user_func_array($error, [$value]);
        }

        return $value;
    }

    public function getValue($slug)
    {
        return get_option($slug, $this->options['default']);
    }

    public function renderText($title, $slug)
    {
        $value = $this->getValue($slug);
        ?>
        <input type="text" id="<?= $slug; ?>" placeholder="<?= $title; ?>" value="<?= $value; ?>" name="<?= $slug; ?>"
               class="regular-text">
        <?php
    }

    public function renderSelect($title, $slug)
    {
        $value = $this->getValue($slug);
        $options = $this->options['select_options'];
        ?>
        <select name="<?= $slug; ?>" id="<?= $slug; ?>">
            <?php foreach ($options as $option): ?>
                <option value="<?= $option['value']; ?>" <?php selected($option['value'], $value); ?>><?= $option['title']; ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function renderTemplateSelect($title, $slug)
    {
        $value = $this->getValue($slug);
        if (!$value) $value = get_option('bdwp-default-template');
        $options = $this->options['select_options'];
        ?>
        <select class="bdwp-template-select" name="<?= $slug; ?>" id="<?= $slug; ?>">
            <?php foreach ($options as $option): ?>
                <option value="<?= $option['value']; ?>" <?php selected($option['value'], $value); ?>><?= $option['title']; ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function renderExtSelect($title, $slug)
    {
        $value = $this->getValue($slug);
        $options = $this->options['select_options'];
        ?>
        <select name="<?= $slug; ?>" id="<?= $slug; ?>">
            <?php foreach ($options as $option): ?>
                <option value="<?= $option['value']; ?>" <?php selected($option['value'], $value); ?>><?= $option['title']; ?></option>
            <?php endforeach; ?>
        </select>
        <?php $link = get_the_permalink($value); ?>
        <small style="margin-left: 5em;"><?= $title ?> URL: <a href="<?= $link ?>" target="_blank"><?= $link ?></a></small>
        <?php
    }

    public function renderThemeSelect($title, $slug)
    {
        $value = $this->getValue($slug);
        $options = $this->options['select_options'];
        ?>
        <select name="<?= $slug; ?>" id="<?= $slug; ?>">
            <?php foreach ($options as $option): ?>
                <option value="<?= $option['value']; ?>" <?php selected($option['value'], $value); ?>><?= $option['title']; ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function renderNumber($title, $slug)
    {
        $value = $this->getValue($slug);
        if (isset($this->options['min'])) {
            $min = $this->options['min'];
        }
        if (isset($this->options['max'])) {
            $max = $this->options['max'];
        }
        ?>
        <input type="number" id="<?= $slug; ?>" name="<?= $slug; ?>" value="<?= $value; ?>"
            <?php if (isset($min)) echo 'min="' . $min . '"'; ?>
            <?php if (isset($max)) echo 'max="' . $max . '"'; ?>>
        <?php
    }

    public function renderCheckbox($title, $slug)
    {
        $value = (boolean)$this->getValue($slug);
        ?>
        <label for="<?= $slug; ?>">
            <input type="checkbox" id="<?= $slug; ?>" name="<?= $slug; ?>" <?php checked($value); ?>>
            <?= $title; ?>
        </label>
        <?php
    }

    public function renderIconUpload($title, $slug)
    {
        $value = get_option($slug, 'none');
        ?>
        <div class="bdwp-icon-uploader">
            <input type="hidden" class="bdwp-selected" id="<?= $slug; ?>" value="<?= $value; ?>" name="<?= $slug; ?>" autocomplete="off" />
            <span hidden class="bdwp-default"><?= $this->options['default']; ?></span>
            <i class="fa fa-2x" data-fa-icon="" aria-hidden="true"></i>
            <img src="" />
            <i class="fa fa-2x fa-upload" aria-hidden="true"></i>
            <a class="bdwp-upload-custom-icon" href="#">Upload a new icon</a>
            <i class="fa fa-2x fa-font-awesome" aria-hidden="true"></i>
            <a class="bdwp-use-font-icon" href="#">Select FA icon</a>
            <i class="fa fa-2x fa-trash" aria-hidden="true"></i>
            <a class="bdwp-delete-custom-icon" href="#">Use default</a>
        </div>
        <?php
    }

    public function renderTextarea($title, $slug)
    {
        $value = $this->getValue($slug);
        ?>
        <textarea name="<?= $slug; ?>" id="<?= $slug ?>" rows="5" cols="40"><?= $value; ?></textarea>
        <?php
    }

    public function renderDevider($title, $slug) {
        echo '<hr />';
    }

    public function sanitizeText($text)
    {
        return sanitize_text_field($text);
    }

    public function sanitizeSelect($value)
    {
        if (empty($value)) $value = 'none';
        return $value;
    }
}