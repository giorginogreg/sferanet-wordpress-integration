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
	protected $base_url;
	protected $token;

	/**
	 * Set the value of token
	 *
	 * @return  self
	 */
	public function set_token( $token ) {
		update_option( 'sferanet_token', $token );
		$this->token = $token;

		return $this;
	}

	/**
	 * Get the value of token
	 */
	public function get_token() {
		return $this->token;
	}
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
		$this->base_url    = 'https://catture.partnersolution.it';
		try {
			$this->token = $this->login_sferanet();
		} catch ( Exception $th ) {
			wp_die( 'Login error: ' . $th->getMessage() );
		}
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

	/**
	 * Make login into management software and return the token, or throw an exception if the http request had some trouble or if the credentials are not valid
	 * @return Token
	 */
	public function login_sferanet() {

		$ep = '/login_check';

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body' => array(
					'_username' => 'grifo',
					'_password' => 'aeUDFGkKn',
				),
			)
		); 
		if ( is_wp_error( $response ) ) {
			throw new Exception( 'Error during login call in Sfera Net: ' . $response->get_error_message(), 1 );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$is_token_in_body = isset( $body['token'] );
		if ( $is_token_in_body ) {
			$this->set_token( $body['token'] );
			return $body['token'];
		} else {
			// It can be also credentials mismatch
			throw new Exception( 'Error Processing Request: token not set in response body', 1 );
		}

	}

	/**
	 * return if token is at least valid for more than 5 minutes
	 * 
	 * @return [type]
	 */
	public function is_token_valid( $token ) {

		list($header, $payload, $signature) = explode( '.', $token );
		// $token_decoded = JWT::decode($token );
		// $token_decoded->time

		$payload = json_decode( base64_decode( $payload ) );
		// $payload->exp; // altri dati: username, iat, roles (array di stringhe)
		return ( $payload->exp > strtotime( '+5 min' ) );

	}

	public function validate_token() {

		if ( ! $this->is_token_valid( $this->get_token() ) ) {
			$this->login_sferanet();
		}
	}
	}

}
