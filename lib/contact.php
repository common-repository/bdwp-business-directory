<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BDWP_Contact
{

    public $value = null;
    public $title = null;
    public $type = null;
    public $sub_type = null;
    protected $default;
    public $created_at = null;
    public $updated_at = null;

    public function __construct($value, $title, $type, $sub_type, $default = false, $created_at = null, $updated_at = null)
    {
        $this->value = $value;
        $this->title = $title;
        $this->type = $type;
        $this->sub_type = $sub_type;
        $this->default = (bool)$default;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public function __toString()
    {
        global $bdwp_branch;
        $classes = ['bdwp_contact'];
        $classes[] = 'bdwp_contact-' . strtolower($this->type);
        $classes = apply_filters('bdwp_branch_contact_classes', $classes, $this, $bdwp_branch);
        $classes = array_map('sanitize_html_class', $classes);
        $classes = implode(' ', $classes);

        if (in_array($this->type, ['Url', 'Email'])) {
            if ($this->type === 'Email') {
                $url = '#';
            } else {
                $url = $this->value;
            }

            $title = $this->title;
            if (empty($title)) {
                $title = $this->type;
            }

            $value = '<a href="' . $url . '" class="' . $classes . '" target="_blank">' . $title . '</a>';

        } else {
            $value = '<span class="' . $classes . '">' . $this->value . '</span>';
        }
        return $value;
    }

    public function isDefault()
    {
        return $this->default;
    }
}

function bdwp_group_contacts($contacts)
{
    $grouped_contacts = [
        'phones' => [],
        'emails' => [],
        'urls'   => []
    ];
    foreach ($contacts as $contact) {
        switch ($contact->type) {
            case 'Phone':
                array_push($grouped_contacts['phones'], $contact);
                break;
            case 'Email':
                if ($contact->value !== 'NO_EMAIL') {
                    array_push($grouped_contacts['emails'], $contact);
                }
                break;
            case 'Url':
                array_push($grouped_contacts['urls'], $contact);
        }
    }
    return $grouped_contacts;
}

function bdwp_find_default_contact($contact_group)
{
    foreach ($contact_group as $contact) {
        if ($contact->isDefault()) {
            return $contact;
        }
    }

    if (!empty($contact_group[0])) {
        return $contact_group[0];
    }

    return null;
}