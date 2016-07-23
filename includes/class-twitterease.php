<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class TwitterEase {

	/**
	 * The single instance of TwitterEase.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Contains bool for validity of request
	 * @var     bool
	 * @access  public
	 * @since   1.0.0
	 */
	public $allset = false;

	/**
	 * OAuth Access Token
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	private $oauth_access_token = '';

	/**
	 * OAuth Access Token Secret
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	private $oauth_access_token_secret = '';

	/**
	 * Consumer Key
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	private $consumer_key = '';

	/**
	 * Consumer Key Secret
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	private $consumer_key_secret = '';

	/**
	 * Tweet amount
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $default_count = '';

	/**
	 *  Twitter Username
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $screen_name = '';

	/**
	 *  JSON Object containing tweets
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $json = '';

	/**
	 *  Expiry time
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $expire = '';

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'twitterease';
		$this->base = TE_BASE;

		// Init some variables & constants
		if ( !defined( 'BLANK' ) ) define( 'BLANK', '' );
		define( 'TE_SECURE_MODE', false );
		define( 'TW_URL', "https://api.twitter.com/1.1/statuses/user_timeline.json" );

		$this->oauth_access_token = get_option( $this->base . 'oauth_access_token' );
		$this->oauth_access_token_secret = get_option( $this->base . 'oauth_access_token_secret' );
		$this->consumer_key = get_option( $this->base . 'consumer_key' );
		$this->consumer_secret = get_option( $this->base . 'consumer_key_secret' );

		$this->screen_name = get_option( $this->base . 'screen_name' );
		$this->default_count = get_option( $this->base . 'default_count' );

		$this->expire = 60 * (int)get_option( $this->base . 'cache_expire' );

		// Check if there is enough information to continue with request
		if ( $this->oauth_access_token 
			&& $this->oauth_access_token_secret
			&& $this->consumer_key
			&& $this->consumer_secret
			&& $this->screen_name
		) $this->allset = true;

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );		

		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new TwitterEase_Admin_API();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Shortcode to add Twitter Feed anywhere
		add_shortcode( 'tweasy', array( $this, 'primary_shortcode' ) );

		// Widget to add Twitter Feed in sidebar
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		// Proceed with twitter request
		if ( $this->allset ) $this->prepare_twitter_request();

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
	} // End __construct ()

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new TwitterEase_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new TwitterEase_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		// wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		
		wp_enqueue_script( $this->_token . '-frontend' );
		$this->enqueue_json_object_in_head();
		
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		// wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'twitterease', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'twitterease';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main TwitterEase Instance
	 *
	 * Ensures only one instance of TwitterEase is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see TwitterEase()
	 * @return Main TwitterEase instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

	/**
	 * Build URI Base String
	 * @param  string $baseURI		Twitter API String
	 * @param  string $method 		GET/POST
	 * @param  array $params 		OAuth Parameters
	 * @return string
	 */
	private function build_base_string($baseURI, $method, $params) {
	    $r = array();
	    ksort($params);
	    foreach($params as $key=>$value){
	        $r[] = "$key=" . rawurlencode($value);
	    }
	    return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
	}

	/**
	 * Build the Auhtorisation Header
	 * @param  string $oauth
	 * @return string $r
	 */
	private function build_authorization_header($oauth) {
	    $r = 'Authorization: OAuth ';
	    $values = array();
	    foreach($oauth as $key=>$value)
	        $values[] = "$key=\"" . rawurlencode($value) . "\"";
	    $r .= implode(', ', $values);
	    return $r;
	}

	/**
	 * Prepare the Twitter Request
	 * @return void
	 */
	private function prepare_twitter_request() {
		$oauth = array(
			'screen_name' => $this->screen_name,
			'count' => $this->default_count,
			'oauth_consumer_key' => $this->consumer_key,
			'oauth_nonce' => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_token' => $this->oauth_access_token,
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0'
		);

		$base_info = $this->build_base_string( TW_URL, 'GET', $oauth );
		$composite_key = rawurlencode( $this->consumer_secret ) . '&' . rawurlencode( $this->oauth_access_token_secret );
		$oauth_signature = base64_encode(hash_hmac( 'sha1', $base_info, $composite_key, true ));
		$oauth['oauth_signature'] = $oauth_signature;

		// Setup Header & Options
		$header = array( $this->build_authorization_header( $oauth ), 'Expect:' );
		$options = array(
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_HEADER => false,
			CURLOPT_URL => TW_URL . '?screen_name=' . $this->screen_name . '&count=' . $this->default_count,
			CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false
		);

		// Setup cache object
		$cache = new Cache(array(
			'name'      => 'TwitterEase',
			'path'      => $this->dir . '/cache/',
			'extension' => '.cache'
		));

		// If Saving Form, clear cache
		if ( isset( $_POST )
			&& isset( $_POST['option_page'] )
			&& $_POST['option_page'] == "twitterease_settings"
		) $cache->eraseAll();
		else {
			$cache->eraseExpired();

			if ( !$cache->isCached( 'twitterfeed' ) ) {

				// Make call
				$feed = curl_init();
				curl_setopt_array( $feed, $options );
				$this->json = curl_exec( $feed );
				curl_close( $feed );

				$cache->store( 'twitterfeed', $this->json, $this->expire );
				
			} else $this->json = $cache->retrieve( 'twitterfeed' );		
		}
	}

	/**
	 * Include the Twitter JSON Object in the head of all pages
	 * @return void
	 */
	private function enqueue_json_object_in_head() {
		if( $this->json ) {
			$json_string = "tWE_tweets = " . json_encode( $this->json ) . ";";
			wp_add_inline_script( $this->_token . '-frontend', $json_string, 'before' ); 
		}
	}

	/**
	 * Print the Twitter wrapper when shortcode is called
	 * @return string 	Twitter wrapper
	 */
	public function primary_shortcode() {
		return '<div id="twitterEase"></div>';
	}

	/**
	 * Register the Twitter Feed Widget
	 * @return void
	 */
	public function register_widget() {
		register_widget( 'Twitter_Ease_Widget' );
	}
}
