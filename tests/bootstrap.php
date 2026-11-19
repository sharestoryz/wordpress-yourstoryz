<?php

require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/vendor-prefixed/autoload.php';

Brain\Monkey\setUp();

if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}
