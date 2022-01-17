<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/giorginogreg
 * @since      1.0.0
 *
 * @package    Sferanet_Wordpress_Integration
 * @subpackage Sferanet_Wordpress_Integration/admin
 */

use Firebase\JWT\JWT;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sferanet_Wordpress_Integration
 * @subpackage Sferanet_Wordpress_Integration/admin
 * @author     Gregorio Giorgino <g.giorgino@grifomultimedia.it>
 */
class Sferanet_Wordpress_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sferanet_Wordpress_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sferanet_Wordpress_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sferanet-wordpress-integration-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sferanet_Wordpress_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sferanet_Wordpress_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sferanet-wordpress-integration-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function login_sferanet() {
		$base_url = 'https://catture.partnersolution.it';
		$ep       = '/login_check';

		$response = wp_remote_post(
			$base_url . $ep,
			array(
				'body' => array(
					'_username' => 'grifo',
					'_password' => 'aeUDFGkKn',
				),
			)
		); 
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Error Found ( ' . $response->get_error_message() . ' )' );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$is_token_in_body = isset( $body['token'] );
		if ( $is_token_in_body ) {
			update_option( 'sferanet_token', $body['token'] );
		}
		return $is_token_in_body; // Salvare token in wp option e recuperarlo per ogni chiamata
	}

	/**
	 * return if token is at least valid for more than 5 minutes
	 * 
	 * @return [type]
	 */
	public function token_valid()
	{
		$token = get_option('sferanet_token');
	/* 	if( $token )
			$token_decoded = JWT::decode($token );
			$token_decoded->time  */
	}

}
