<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://github.com/giorginogreg
 * @since 1.0.0
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
class Sferanet_WordPress_Integration_Admin {


	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
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
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	private $logger;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		require_once plugin_dir_path( __FILE__ ) . '../logs/class-sferanet-wordpress-integration-logs-admin.php';
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->base_url    = 'https://catture.partnersolution.it';

		$this->logger = Sferanet_Wordpress_Integration_Logs_Admin::getInstance();
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	  * Registers a new settings page under Settings.
	  */
	public function admin_menu() {

		add_options_page(
			__( 'SferaNet Settings', 'sferanet' ),
			__( 'SferaNet Settings', 'sferanet' ),
			'manage_options',
			'options_sferanet',
			array( $this, 'settings_page' )
		);
	}


	public function admin_register_setting() {

		register_setting( 'sferanet-settings-group', 'sferanet-settings' );

		add_settings_section(
			'settings', // section ID
			__( 'Settings', 'sferanet' ), // title (if needed)
			'', // callback function (if needed)
			'sferanet-settings-group' // page slug
		);

		add_settings_field(
			'agency_code_field',
			__( 'Agency code', 'sferanet' ),
			array( $this, 'agency_code_field_render' ),
			'sferanet-settings-group', // page slug
			'settings', // section ID
		);
	}

	/**
	 * Settings page display callback.
	 */
	private function settings_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/settings.php';
	}

	private function agency_code_field_render() {
		$options = get_option( 'sferanet-settings' ); ?>
		<input type='text' name='sferanet-settings[agency_code_field]' value='<?php echo $options['agency_code_field']; ?>'>
		<?php
	}
	/**
	 * Make login into management software and return the token
	 *
	 * @throws \Exception Method that throws exception if the http request had some trouble or if the credentials are not valid.
	 * @return Token
	 */
	public function login_sferanet() {

		$this->logger->sferanet_logs( 'Logging into sferanet...' );

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
			$this->logger->sferanet_logs( 'Token successfully acquired.' );

			$this->set_token( $body['token'] );
			return $body['token'];
		} else {
			// It can be also credentials mismatch
			$this->logger->sferanet_logs( 'Error Processing Request: token not set in response body' );

			throw new \Exception( 'Error Processing Request: token not set in response body', 1 );
		}

	}

	/**
	 * Return if token is at least valid for more than 5 minutes
	 *
	 * @param  mixed $token JWT token.
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
			$this->logger->sferanet_logs( "Token Not Valid, value: {$this->get_token()}" );
			$this->logger->sferanet_logs( 'Refreshing token...' );

			$this->login_sferanet();
			$this->logger->sferanet_logs( 'Token refreshed successfully' );
		}
	}

	private function get_all_accounts( $contractor = null ) {
		$ep = '/accounts';
		$this->logger->sferanet_logs( 'Getting all accounts.' );

		$this->validate_token();
		$response = wp_remote_get(
			$this->base_url . $ep,
			array(
				'headers' => $this->build_headers(),
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while getting all accounts. Error: ' . $response->get_error_message() );

			throw new \Exception( 'Error while getting all accounts. Error: ' . $response->get_error_message(), 1 );
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		switch ( $response_code ) {
			case 200:
				$status = true;
				$msg    = 'Accounts retrieved successfully.';
				$body   = json_decode( wp_remote_retrieve_body( $response ) );
				$data   = $body->{'hydra:member'};
				break;

			default:
				$msg = 'Generic error, debug please.';
		}
		$this->logger->sferanet_logs( 'Response from get_accounts API :' );
		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);
		$this->logger->sferanet_logs( $response );

		return $response;
	}

	/**
	 * id can be VAT code or TAX code
	 *
	 * @param [type] $contractor
	 * @return bool | stdClass
	 */
	public function get_user_by_id( $id, $is_business = false ) {
		$result   = $this->get_all_accounts();
		$accounts = $result['data'];
		$found    = false;
		$field    = $is_business ? 'partitaiva' : 'codicefiscale';
		foreach ( $accounts as $account ) {
			if ( $account->$field === $id ) {
				return $account;
			}
		}

		return $found;
	}

	/**
	 * Add passenger to a practice already existent
	 *
	 * @param mixed $passenger   - Object with properties.
	 *                           - cognome * - nome * -
	 *                           is_contraente * -
	 *                           data_nascita -> format
	 *                           01/01/1990 - sesso -
	 *                           cellulare.
	 *
	 * @param  mixed $practice_id Id of the practice already existent.
	 * @throws \Exception An exception is thrown if the http call had some trouble issues.
	 *
	 * @return array('status'=> true | false, "msg" => "")
	 */
	public function add_passenger_practice( $passenger, $practice_id ) {

		$ep = '/prt_praticapasseggeros';

		$this->validate_token();
		$this->logger->sferanet_logs( 'Calling add_passenger_practice API.' );
		$body = array(
			'pratica'      => "prt_praticas/$practice_id",
			'cognomepax'   => $passenger->surname,
			'nomepax'      => $passenger->name,
			'annullata'    => 0, // ?
			'iscontraente' => 0,
		);

		if ( isset( $passenger->birthday ) ) {
			$body['datadinascita'] = $passenger->birthday;
		}
		if ( isset( $passenger->sex ) ) {
			$body['sesso'] = $passenger->sex;
		}
		if ( isset( $passenger->phone_number ) ) {
			$body['cellulare'] = $passenger->phone_number;
		}

		if ( isset( $passenger->attachments ) ) {
			// TODO: Call api for inserting attachments for each att.
			foreach ( $passenger->attachments as $attachment ) {
				$this->add_allegatos();
			}
		}

		$this->logger->sferanet_logs( 'Payload: ' . json_encode( $body ) );
		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( "Error while adding a passenger to the practice. Passenger: $passenger->surname $passenger->name. Error: " . $response->get_error_message() );
			throw new \Exception( "Error while adding a passenger to the practice. Passenger: $passenger->surname $passenger->name. Error: " . $response->get_error_message(), 1 );
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = "Passenger associated successfully to practice $practice_id";
				break;
			case 400:
				$msg = 'Invalid input';
				break;
			case 404:
				$msg = 'Practice id not found.';
				break;
			default:
				$msg = 'Generic error, debug please.';
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => wp_remote_retrieve_body( $response ),
		);
		$this->logger->sferanet_logs( 'Response: ' . json_encode( $response ) );

		return $response;
	}

	/**
	 * Create a new practice with work in progress status.
	 *
	 * @param mixed $contractor User with the following properties:
	 *                          - Surname
	 *                          - Name.
	 *
	 * @throws \Exception An exception is thrown if the http call had some trouble issues.
	 * @return [type]
	 */
	public function create_practice( $contractor, $practice_data = null ) {

		$ep = '/prt_praticas';
		$this->validate_token();

		$this->logger->sferanet_logs( 'Creating a new practice.' );

		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		include_once 'statuses/Practice_Status.php';
		$options = get_option( 'sferanet-settings' );

		$body = array(
			'codiceagenzia'      => $options['agency_code_field'],
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
			'descrizionepratica' => $practice_data->description, // Ciò che apparirà sulla fattura
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

		$this->logger->sferanet_logs( 'Payload: ' . json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while creating a new practice. Error: ' . $response->get_error_message() );

			throw new \Exception( 'Error while creating a new practice. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		switch ( $response_code ) {
			case 201:
				$status      = true;
				$msg         = 'Practice created successfully';
				$body        = json_decode( wp_remote_retrieve_body( $response ) );
				$practice_id = explode( '/', $body->{@'id'} );
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

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);
		$this->logger->sferanet_logs( 'Response: ' . json_encode( $response ) );

		return $response;
	}


	/**
	 * Adds a new customer into the managerial software SferaNet.
	 *
	 * @param mixed $customer Customer object.
	 *
	 * @return [type]
	 */
	public function create_account( $customer ) {

		$ep = '/accounts';
		$this->validate_token();

		$this->logger->sferanet_logs( 'Creating a new account. ' );

		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		include_once 'statuses/Account_Status.php';
		$options = get_option( 'sferanet-settings' );
		$body    = array(
			'codiceagenzia'      => $options['agency_code_field'],
			'tipocattura'        => 'PSCATTURE',
			'cognome'            => $customer->surname, // Surname or business name
			'flagpersonafisica'  => $customer->is_physical_person,
			'codicefiscale'      => $customer->fiscal_code, // Can be also VAT number
			'iscliente'          => 0,
			'isfornitore'        => 0,
			'ispromotore'        => 0,
			'creazione'          => $date,
			'indirizzo1'         => $customer->first_address,
			'stato'              => Account_Status::INSERTING,
			'emailcomunicazioni' => $customer->email_address,
			'datanascita'        => $customer->birthday,
		);

		$optional_values = array(
			// Managerial sw key 	 => $object key

			'partitaiva'             => 'VAT_number',
			'externalid'             => 'external_id', // Univoque id from the supplier
			'nome'                   => 'name',
			'localitanascitacitta'   => 'born_city',
			'localitaresidenzacitta' => 'residence_city',
			'nazione'                => 'nation',
			'cap'                    => 'postal_code',

			'indirizzo2'             => 'additional_address',
			'sex'                    => 'sex',
			'id'                     => 'id',
			'user'                   => 'user',
		);

		foreach ( $optional_values as $mgr_sw_key => $obj_key ) {
			if ( isset( $customer->$obj_key ) ) {
				$body[ $mgr_sw_key ] = $customer->$obj_key;
			}
		}

		$this->logger->sferanet_logs( 'Payload: ' . json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while creating a new customer. Error: ' . $response->get_error_message() );
			throw new \Exception( 'Error while creating a new customer. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Customer created successfully';
				$body   = json_decode( wp_remote_retrieve_body( $response ) );
				$data   = array(
					'account_created' => $body,
				);
				break;
			case 400:
				$msg = 'Invalid input';
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);

		$this->logger->sferanet_logs( 'Response: ' . json_encode( $response ) );

		return $response;
	}

	public function add_service( $service, $practice_id = '' ) {
		// Tipo pacchetto: catalogo o crociera
		// Tipo vendita: ORG (Netta? - Default), INT (Commissionabile?) TODO: Chiedere a savio se in fase di creazione è corretta
		// TODO: campo itinerario not found

		// Opzionali

		/*
			// Per voli aerei e per pacchetti tour operator

			clausole idoneità esigenze specifiche del viaggiatore (non trovato su api) - Da inserire anche in ecommerce

			prtPraticaservizioquota (Array[string], optional): Add prtQuote
			pratica (string, optional): di appartenenza @ORM\ManyToOne(targetEntity="PrtPratica",inversedBy="servizi") ,
			quote (Array[string], optional): One servizio has many quote.
			prtServizio (object, optional, read only),
			prtQuote (object, optional, read only),
			externalid (string, optional): id riga servizio (Vostro id/guid riferimento praticaservizio ) Opzionale ,
			regimevendita (string, optional): Regime Vendita "74T", "ORD"
			codiceisodestinazione (string, optional): Codice Iso Destinazione length 10 ,
			codicefornitore (string): length 36
			// - Codice del fornitore del servizio. Nel sistema del provider può corrispondere al codice provider
				// Se possibile utilizzare come codice, in ordine di priorità, la Partita Iva del fornitore o
				// il Codice Fiscale (se persona fisica senza Partita Iva) o
				// il codice identificativo del fornitore nel sistema di origine (meglio se univoco)
			brand (string, optional): Brand servizio length 10
			localitaDescrizioneLibera (string, optional): Localita descrizione servizio length 255
			riferimentopressofornitore (string, optional): Person / email length 50
			nomestrutturavoucher (string, optional),
			indirizzostrutturavoucher (string, optional),
			mailstrutturavoucher (string, optional),
			telefonostrutturavoucher (string, optional),

			noteinterne (string, optional),
			noteesterne (string, optional),
			bookingagencyrefext (string, optional): Riferimento Pcc (se attivo B2b) ,
			codiceagenzianetwork (string, optional),
			codicechannel (string, optional),
			descrizionechannel (string, optional),
			passeggeri (string, optional),
			fileorigine (string, optional),
			nazionechannel (string, optional): Nazione Channel iso length 3
			nazionefornitore (string, optional): Nazione Fornitore iso length 3
			id (string, optional, read only): id del servizio
			user (string, optional)
		*/

		// Assicurazione annull:
		// -> Proposta ma non accettata: se a pagamento ma non accettata.
		// -> Inclusa: se prezzo è 0
		// -> Stipulata: prezzo non inserito?
		// -> Non prevista

		$ep = '/prt_praticaservizios';
		$this->validate_token();
		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		include_once 'statuses/Account_Status.php';
		$this->logger->sferanet_logs( "Associating a new service to the practice $practice_id" );

		$body = array(
			'annullata'           => 0,
			'datacreazione'       => $date,
			'tipodestinazione'    => $service->destination_type,
			'tiposervizio'        => $service->type,
			'descrizione'         => $service->description, // Ciò che apparirà sulla fattura (stesso della pratica)
			'ragsocfornitore'     => $service->supplier_business_name,
			'codicefornitore'     => $service->supplier_business_code,
			'codicefilefornitore' => $service->supplier_file_code, // Codice di Conferma del fornitore per la prenotazione
			'datainizioservizio'  => $service->start_date,
			'datafineservizio'    => $service->end_date,
			'duratagg'            => $service->duration_days,
			'duratant'            => $service->duration_nights,
			'nrpaxadulti'         => $service->no_pax_adults,
			'nrpaxchild'          => $service->no_pax_childs,
			'nrpaxinfant'         => $service->no_pax_infants,
		);

		$optional_values = array(
			// Managerial sw key 	 => $object key
			'partenzada'                 => '', // descrittivo partenza
			'rientroa'                   => '', // descrittivo rientro
			'destinazione'               => '',
			'sistemazione'               => '', // TODO: La select ha molte opzioni, noi invece inseriamo un testo libero, da discutere
			'struttura'                  => '', // nome struttura solo per pacchetti tour operator
			'trattamento'                => '', // TODO: sull'ecommerce non presente?
			'trasporti'                  => '', // Campo descrittivo (len 255)
			'altriservizi'               => '', // TODO: non trovato su gestionale? - Titoli di quello che ha acquistato
			'quote'                      => '',
			'prtServizio'                => '',
			'prtQuote'                   => '',
			'externalid'                 => '',
			'regimevendita'              => '',
			'codiceisodestinazione'      => '',
			'brand'                      => '',
			'localitaDescrizioneLibera'  => '',
			'riferimentopressofornitore' => '',
			'nomestrutturavoucher'       => '',
			'indirizzostrutturavoucher'  => '',
			'mailstrutturavoucher'       => '',
			'telefonostrutturavoucher'   => '',
			'noteinterne'                => '',
			'noteesterne'                => '',
			'bookingagencyrefext'        => '',
			'codiceagenzianetwork'       => '',
			'codicechannel'              => '',
			'descrizionechannel'         => '',
			'passeggeri'                 => '',
			'fileorigine'                => '',
			'nazionechannel'             => '',
			'nazionefornitore'           => '',
			'id'                         => '',
			'prtPraticaservizioquota'    => '',
			'user'                       => '',
		);

		if ( $practice_id ) {
			$body['pratica'] = "/prt_praticas/$practice_id";
		}

		foreach ( $optional_values as $mgr_sw_key => $obj_key ) {
			if ( isset( $service->$obj_key ) ) {
				$body[ $mgr_sw_key ] = $service->$obj_key;
			}
		}

		$this->logger->sferanet_logs( 'Payload: ' . json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while associating a service to a practice. Error: ' . $response->get_error_message() );
			throw new \Exception( 'Error while associating a service to a practice. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Service associated to practice successfully';
				$data   = array(
					'service_associated' => $response_body,
				);
				break;
			case 400:
				$msg  = 'Invalid input';
				$data = $response_body;
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
			default:
				$msg = 'Generic error, debug please';
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);

		$this->logger->sferanet_logs( 'Response: ' . json_encode( $response ) );

		return $response;
	}

	public function add_quote_service( $sold_services, $service_id ) {
		/*
			servizio (string, optional): di appartenenza @ORM\ManyToOne(targetEntity="PrtPraticaservizio",inversedBy="quote")
			datacambiocosto (string, optional): data del cambio
			codiceisovalutacosto (string, optional)
			tassocambiocosto (number, optional): Valore di cambio alla data indicata da datacambiocosto ,
			cambioineurocosto (integer, optional): (0|1) Vale 0 se il cambio è espresso in valuta estera Vs Euro. Esempio: Dollari necessari per acquistare per 1 euro. Vale 1 se il cambio è espresso in Euro Vs valuta estera. Esempio: Euro necessari per acquistare 1 Dollaro. ,
			id (string, optional, read only),
			user (string, optional)
		*/

		$ep = '/prt_praticaservizioquotas';
		$this->validate_token();
		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		$this->logger->sferanet_logs( 'Adding quote to service with id ' . $service_id );

		$body = array(
			'descrizionequota'                 => 'Test descrizione',
			'datavendita'                      => $date,
			'quantitacosto'                    => 1, // fornitore?
			'costovalutaprimaria'              => 0, // costo fornitore
			'codiceisovalutacosto'             => 'EUR',
			'quantitaricavo'                   => count( $sold_services ), // No. services sold
			'ricavovalutaprimaria'             => array_sum( array_column( $sold_services, 'price' ) ),
			'codiceisovalutaricavo'            => 'EUR', // ??
			'commissioniattivevalutaprimaria'  => 0,
			'commissionipassivevalutaprimaria' => 0,
			'progressivo'                      => 0, // TODO: Cos'è?
			'annullata'                        => 0,
			'servizio'                         => "prt_praticaservizios/$service_id",
		);

		/*
		$optional_values = array(
			// Managerial sw key 	 => $object key
			'datacambiocosto'   => '',
			'tassocambiocosto'  => '',
			'cambioineurocosto' => '',
			'id'                => '',
			'user'              => '',
		);
		*/

		/*
		  foreach ( $optional_values as $mgr_sw_key => $obj_key ) {
			if ( isset( $customer->$obj_key ) ) {
				$body[ $mgr_sw_key ] = $customer->$obj_key;
			}
		}
		*/

		$this->logger->sferanet_logs( 'Payload: ' . json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while creating a practice quote related to a service. Error: ' . $response->get_error_message() );

			throw new \Exception( 'Error while creating a practice quote related to a service. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Quote created and associated to the service successfully';
				$data   = array(
					'quote_created' => $response_body,
				);
				break;
			case 400:
				$msg  = 'Invalid input';
				$data = $response_body;
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
			default:
				$msg = 'Generic error, debug please';
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);
		$this->logger->sferanet_logs( 'Response: ' . json_encode( $response ) );
		return $response;
	}

	public function add_financial_transaction( $financial_transaction, $practice_id, $order_id = null ) {
		/*
			- Manca operatore ADV
			- Deposito finanziario - manca

			stato (string): stato della Movimento ( INS, MOD, CANC)
				- INS quando è stata caricata completamente
				- MOD quando è stata modificata in uno dei suoi elementi
				- WPRELOAD per ricaricaricare completamente gli elementi interni (tutti i child verranno annullati) lo stato dovrà poi essere settato a MOD
			externalid (string): id riga Pratica/lista (Vostro id/guid riferimento Pratica/vendita/lista )

			codicefile (string, optional): Codice di Conferma del fornitore per la prenotazione quando disponibile length 20
			codiceaida (string, optional): Codice carta Aida
			spesebancarie (number, optional),
			id (string, optional): id del Movimento ,
			datamatrimonio (string, optional),
			firma (string, optional),
			dedica (string, optional),
			user (string, optional)
		*/

		$ep = '/mov_finanziarios';
		$this->validate_token();
		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		$this->logger->sferanet_logs( 'Adding financial transaction.' );

		include_once 'statuses/Financial_Transaction_Status.php';
		include_once 'types/Movement_Type.php';
		$options = get_option( 'sferanet-settings' );

		$body = array(
			'codiceagenzia' => $options['agency_code_field'],
			'tipocattura'   => 'PSCATTURE',
			'externalid'    => $order_id ?? wp_unique_id(),
			'codcausale'    => 'POS', // TODO: testare se corretto
			'datamovimento' => $date,
			'datacreazione' => $date,
			'datamodifica'  => $date,
			'descrizione'   => $financial_transaction->description,
			'importo'       => $financial_transaction->total,
			'stato'         => Financial_Transaction_Status::INSERTING,
			'tipomovimento' => Movement_Type::RECESSED,
		);

		/*
		$optional_values = array(
			// Managerial sw key 	 => $object key
			'datacambiocosto'   => '',
			'tassocambiocosto'  => '',
			'cambioineurocosto' => '',
			'id'                => '',
			'user'              => '',
		);
		*/

		/*
		  foreach ( $optional_values as $mgr_sw_key => $obj_key ) {
			if ( isset( $customer->$obj_key ) ) {
				$body[ $mgr_sw_key ] = $customer->$obj_key;
			}
		}
		*/
		$this->logger->sferanet_logs( 'Payload: ' . json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while creating a transactional movement. Error: ' . $response->get_error_message() );
			throw new \Exception( 'Error while creating a transactional movement. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Financial Movement created correctly';
				$data   = array(
					'financial_movement' => $response_body,
				);
				break;
			case 400:
				$msg  = 'Invalid input';
				$data = $response_body;
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
			default:
				$msg = 'Generic error, debug please';
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);

		$this->logger->sferanet_logs( 'Response: ' . json_encode( $response ) );
		return $response;
	}

	public function add_allegatos() {
		$ep = '/allegatos';
		$this->validate_token();
		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		$this->logger->sferanet_logs( 'Adding attachments.' );

		$options = get_option( 'sferanet-settings' );

		$body = array();

		$optional_values = array(
			'id'                            => 'string',
			'agenziaid'                     => 'string',
			'allegatotipoid'                => 'string',
			'visibileingestionedocumentale' => 1,
			'note'                          => 'string',
			'uploaddownload'                => 'string',
			'nometabella'                   => 'string',
			'idrecord'                      => 'string',
			'subidrecord'                   => 'string',
			'nomefile'                      => 'string',
			'descrizione'                   => 'string',
			'datainserimento'               => $date,
			'pubblico'                      => 1,
			'flagannullato'                 => false,
			'data'                          => $date,
			'stato'                         => 'string',
			'codiceagenzia'                 => 'string',
			'user'                          => 'string',
			'userId'                        => 0,
		);

		$this->logger->sferanet_logs( 'Payload: ' . json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while adding an attachment. Error: ' . $response->get_error_message() );
			throw new \Exception( 'Error while adding an attachment. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Attachments created correctly';
				$data   = array(
					// 'financial_movement' => $response_body,
				);
				break;
			case 400:
				$msg  = 'Invalid input';
				$data = $response_body;
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
			default:
				$msg = 'Generic error, debug please';
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);

		$this->logger->sferanet_logs( 'Response: ' . json_encode( $response ) );
		return $response;
	}

	private function build_headers() {
		return array(
			'Authorization' => 'Bearer ' . $this->get_token(),
			'Content-Type'  => 'application/json',
		);
	}
}
