<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/giorginogreg
 * @since             1.0.0
 * @package           Sferanet_Wordpress_Integration
 *
 * @wordpress-plugin
 * Plugin Name:       Sferanet - WordPress Integration
 * Plugin URI:        https://github.com/giorginogreg/sferanet-wp-integration
 * Description:       Integration for WordPress between Sferanet and the CMS.
 * Version:           1.0.0
 * Author:            Gregorio Giorgino
 * Author URI:        https://github.com/giorginogreg
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sferanet-wordpress-integration
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SFERANET_WORDPRESS_INTEGRATION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sferanet-wordpress-integration-activator.php
 */
function activate_sferanet_wordpress_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sferanet-wordpress-integration-activator.php';
	Sferanet_Wordpress_Integration_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sferanet-wordpress-integration-deactivator.php
 */
function deactivate_sferanet_wordpress_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sferanet-wordpress-integration-deactivator.php';
	Sferanet_Wordpress_Integration_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_sferanet_wordpress_integration' );
register_deactivation_hook( __FILE__, 'deactivate_sferanet_wordpress_integration' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-sferanet-wordpress-integration.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_sferanet_wordpress_integration() {

	$plugin = new Sferanet_Wordpress_Integration();
	$plugin->run();

	require_once 'vendor/autoload.php';
}
run_sferanet_wordpress_integration();
