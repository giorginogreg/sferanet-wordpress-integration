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

namespace Sferanet_Wp_Integration\Admin;

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
	 * Base url used for API calls
	 *
	 * @var [string] Base url used for API calls
	 */
	protected $base_url;

	/**
	 * Token used for auth in API calls
	 *
	 * @var [string] Token used for auth in API calls
	 */
	protected $token;

	/**
	 * Get the value of token
	 */
	public function get_token() {

		return $token ?? get_option( 'sferanet_token' );
	}
	/**
	 * Set the value of token
	 *
	 * @param mixed $token JWT token.
	 *
	 * @return [type]
	 */
	public function set_token( $token ) {
		update_option( 'sferanet_token', $token );
		$this->token = $token;
		return $this;
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
			$this->validate_token();
		} catch ( \Exception $th ) {
			//phpcs:ignore
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
	 * Make login into management software and return the token
	 *
	 * @throws \Exception Method that throws exception if the http request had some trouble or if the credentials are not valid.
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
			throw new \Exception( 'Error during login call in Sfera Net: ' . $response->get_error_message(), 1 );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$is_token_in_body = isset( $body['token'] );
		if ( $is_token_in_body ) {
			$this->set_token( $body['token'] );
			return $body['token'];
		} else {
			// It can be also credentials mismatch
			throw new \Exception( 'Error Processing Request: token not set in response body', 1 );
		}

	}

	/**
	 * Return if token is at least valid for more than 5 minutes
	 *
	 * @param mixed $token JWT token.
	 * @return [type]
	 */
	public function is_token_valid( $token ) {

		if ( ! $token ) {
			return false;
		}

		list($header, $payload, $signature) = explode( '.', $token );
		// $token_decoded = JWT::decode($token );
		// $token_decoded->time

		$payload = json_decode( base64_decode( $payload ) );
		// $payload->exp; // altri dati: username, iat, roles (array di stringhe)
		return ( $payload->exp > strtotime( '+5 min' ) );

	}

	//phpcs:ignore
	public function validate_token() {

		if ( ! $this->is_token_valid( $this->get_token() ) ) {
			$this->login_sferanet();
		}
	}

	/**
	 * Add passenger to a practice already existent
	 *
	 * @param mixed $passenger - Object with properties.
	 *      - cognome *
	 *      - nome *
	 *      - is_contraente *
	 *      - data_nascita -> format 01/01/1990
	 *      - sesso
	 *      - cellulare.
	 *
	 * @param mixed $practice_id Id of the practice already existent.
	 * @throws \Exception An exception is thrown if the http call had some trouble issues.
	 *
	 * @return array('status'=> true | false, "msg" => "")
	 */
	public function add_passenger_practice( $passenger, $practice_id ) {

		$ep = '/prt_praticapasseggeros';

		$this->validate_token();

		$body = array(
			'pratica'      => "prt_praticas/$practice_id",
			'cognomepax'   => $passenger->surname,
			'nomepax'      => $passenger->name,
			'annullata'    => 0, // ?
			'iscontraente' => $passenger->is_contraente,
		);

		if ( isset( $passenger->data_nascita ) ) {
			$body['datadinascita'] = $passenger->data_nascita;
		}
		if ( isset( $passenger->sesso ) ) {
			$body['sesso'] = $passenger->sesso;
		}
		if ( isset( $passenger->cellulare ) ) {
			$body['cellulare'] = $passenger->cellulare;
		}

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => $body,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->get_token(),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new \Exception( "Error while adding a passenger to the practice. Passenger: $passenger->surname $passenger->name. Error: " . $response->get_error_message(), 1 );
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Passenger associated successfully';
				break;
			case 400:
				$msg = 'Invalid input';
				break;
			case 404:
				$msg = 'Practice id not found.';
				break;
		}

		return array(
			'status' => $status,
			'msg'    => $msg,
		);

	}

	/**
	 * Create a new practice with work in progress status.
	 *
	 * @param mixed $contractor User with the following properties:
	 *  - Surname
	 *  - Name.
	 *
	 * @throws \Exception An exception is thrown if the http call had some trouble issues.
	 * @return [type]
	 */
	public function create_practice( $contractor ) {
		$ep = '/prt_praticas';

		$this->validate_token();
		$date = gmdate( 'Y-m-d\TH:i:s' );
		$body = array(

			'codiceagenzia'      => 'DEMO2',
			'tipocattura'        => 'PSCATTURE',
			// 'passeggeri'      => ['nome e cognome', 'nome2'...],
			// 'codicecliente'      => 'string',
			// 'externalid'         => '123456mioid',

			/*
			   'servizi'          => array(
				  'string',
			  ),
			  */
			// "codextpuntovendita"=> "string", // optional=> length 36
			// "codicecliente"=> "string", // optional=> length 36
			// "capcliente"=> "string", //  optional=> length 10
			// "localitacliente" => "string", //localita cliente length 100
			// "nazionecliente" => "string",  Nazione Cliente iso length 3
			// "externalid" => "string", // id riga Pratica (Vostro id/guid riferimento Pratica/vendita ) Opzionale
			// "delivering" => "string", // di collegamento con Commesse etc.. ex comm:xxx Opzionale
			// 'id'                 => 'string', // id della pratica
			'datacreazione'      => $date,
			'datasaldo'          => $date,
			'datamodifica'       => $date,
			'stato'              => Practice_Status::WORK_IN_PROGRESS,
			'descrizionepratica' => '',
			'noteinterne'        => '',
			'noteesterne'        => '',

			/*
			 'prtPraticaservizio' => array(
				'string', ??
			),
			*/
			// 'user'               => 'string',
			// 'elaborata'          => 0,

			// Contractor data
			'cognomecliente'     => $contractor->surname,
			'nomecliente'        => $contractor->name,
		);

		if ( isset( $contractor->address ) ) {
			$body['indirizzo'] = $contractor->address;
		}
		if ( isset( $contractor->phone_number ) ) {
			$body['telefonocliente'] = $contractor->phone_number;
		}
		if ( isset( $contractor->email_address ) ) {
			$body['emailcliente'] = $contractor->email_address;
		}

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => $body,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->get_token(),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new \Exception( 'Error while creating a new practice. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		switch ( $response_code ) {
			case 201:
				$status      = true;
				$msg         = 'Practice created successfully';
				$body        = json_decode( wp_remote_retrieve_body( $response ) );
				$practice_id = explode( '/', $body['@id'] );
				$data        = array(
					'practice_id' => $practice_id[ count( $practice_id ) - 1 ],
				);
				break;
			case 400:
				$msg = 'Invalid input';
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
		}

		return array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);
	}

}
