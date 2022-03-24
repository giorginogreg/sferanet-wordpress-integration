<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Sferanet_Wordpress_Integration
 */
require __DIR__ . '/../vendor/autoload.php';

WP_Test_Suite::load_plugins( [
    __DIR__ . '/../sferanet-wordpress-integration.php'
] );

WP_Test_Suite::run();