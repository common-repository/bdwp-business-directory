<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BDWP_URL
{
    public $baseUrl;
    protected $queries = [];

    public function __construct($baseUrl = null)
    {
        $this->baseUrl = $baseUrl;
    }

    public function __set($name, $value)
    {
        $this->queries[$name] = $value;
    }

    public function __get($name)
    {
        if (isset($this->queries[$name])) {
            return $this->queries[$name];
        }
        return null;
    }

    public function __toString()
    {
        return $this->getUrl();
    }

    public function getUrl()
    {
        if ($this->baseUrl) {
            return add_query_arg($this->queries, $this->baseUrl);
        } else {
            return add_query_arg($this->queries);
        }
    }

}