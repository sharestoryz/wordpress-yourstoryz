<?php

require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/vendor-prefixed/autoload.php';

// Pre-load Brain Monkey hook functions so they're available globally
Brain\Monkey\setUp();

if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}
