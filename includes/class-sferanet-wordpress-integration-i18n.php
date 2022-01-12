<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/giorginogreg
 * @since      1.0.0
 *
 * @package    Sferanet_Wordpress_Integration
 * @subpackage Sferanet_Wordpress_Integration/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Sferanet_Wordpress_Integration
 * @subpackage Sferanet_Wordpress_Integration/includes
 * @author     Gregorio Giorgino <g.giorgino@grifomultimedia.it>
 */
class Sferanet_Wordpress_Integration_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'sferanet-wordpress-integration',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
