<?php
/**
 * FullThrottle Premium Support Client
 * Curious? Send me an email and you can beta it for me (glenn@fullthrottledevelopment.com)
 */
 
if ( ! class_exists( 'FT_Premium_Support_Client' ) ) {
	
	class FT_Premium_Support_Client {
		
		var $server_url;
		var $product_id;
		var $plugin_support_page_ids;
		var $confirming_request;
		var $receiving_sso;
		var $learn_more_link;
		var $ps_status = false;
		var $sso_status = false;
		var $paypal_button = false;
		var $site_url;
		var $plugin_basename = false;
		var $plugin_slug = false;
		
		// Constructer fires on admin page loads
		function ft_premium_support_client( $config=array() ) {

			// Populate properties
			$this->server_url 				= isset( $config['server_url'] ) ? $config['server_url'] : false;
			$this->product_id 				= isset( $config['product_id'] ) ? $config['product_id'] : false;
			$this->plugin_support_page_ids 	= isset( $config['plugin_support_page_ids'] ) ? $config['plugin_support_page_ids'] : false;
			$this->plugin_basename			= isset( $config['plugin_basename'] ) ? $config['plugin_basename'] : false;
			$this->plugin_slug				= isset( $config['plugin_slug'] ) ? $config['plugin_slug'] : false;
			$this->learn_more_link			= isset( $config['learn_more_link'] ) ? $config['learn_more_link'] : false;
			$this->confirming_request 		= isset( $_GET['ft-ps-confirm-request'] ) ? $_GET['ft-ps-confirm-request'] : false;
			$this->receiving_sso 			= isset( $_POST['ft-ps-receiving-sso'] ) ? $_POST['ft-ps-receiving-sso'] : false;
			$this->site_url					= site_url();

			// Register actions
			add_action( 'admin_head', array( &$this, 'init' ) );
			add_action( 'init', array( &$this, 'check_premium_upgrades' ) );

		}
		
		// Checks for premium upgrades
		function check_premium_upgrades() {
			// Build key
			$exp_option_key = '_ftpssu_' . md5( 'ftpssue-' . $this->product_id . '-' . sanitize_title_with_dashes( $this->site_url ) . '-' . sanitize_title_with_dashes( $this->server_url ) );

			// Check server for auto update?
			if ( $expires = get_option( $exp_option_key ) ) {

				if ( $expires > strtotime( 'now' ) )
					$ft_ps_client_auto_update = new FT_Premium_Support_PluginUpdate_Checker( $this->server_url . '?ft-pss-upgrade-request=1&ft-pss-upgrade-request-product-id=' . $this->product_id . '&ft-pss-upgrade-request-site=' . $this->site_url . '&exp=' . $expires, $this->plugin_basename, $this->plugin_slug, 1 );
				else
					delete_option( 'external_updates-' . $this->plugin_slug );
			
			}
		
		}
		
		// Inits the client if we're on the support page or receiving a request from the server
		function init() {
		
			global $current_screen;

			// Return false if we don't have initial config settings and this isn't a request from the host server
			if ( ( ! $this->server_url || ! $this->product_id || ! isset( $this->plugin_support_page_ids ) ) && ( ! $this->confirming_request || ! $this->receiving_sso ) )
				return;

			// Fire in the hole!
			if ( $this->receiving_sso )
				$this->receive_sso();
			elseif ( $this->confirming_request )
				$this->confirm_request();
			elseif ( in_array( $current_screen->id, $this->plugin_support_page_ids ) )
				$this->init_premium_support();
		
		}
		
		// This function inits premium support process
		function init_premium_support() {

			global $current_user;
			wp_get_current_user();
			
			// Check for premium support, sso, and paypal button transients
			$status_key = md5( 'ft_premium_support_' . $this->product_id . '_' . sanitize_title_with_dashes( $this->site_url )  . '_' . sanitize_title_with_dashes( $this->server_url ) ) ;
			$sso_key = md5( 'ft_premium_sso_' . $current_user->ID . '_' . $this->product_id . '_' . sanitize_title_with_dashes( $this->site_url )  . '_' . sanitize_title_with_dashes( $this->server_url ) );
			$paypal_button_key = md5( 'ft_premium_signup_' . $this->product_id . '_' . sanitize_title_with_dashes( $this->site_url )  . '_' . sanitize_title_with_dashes( $this->server_url ) );
			$exp_option_key = '_ftpssu_' . md5( 'ftpssue-' . $this->product_id . '-' . sanitize_title_with_dashes( $this->site_url ) . '-' . sanitize_title_with_dashes( $this->server_url ) );

			// If we haven't set the status in the last 24 hours, or we haven't set a SSO for the current user in the last 3 hours, do it now.
			if ( false === ( $ps_status = get_transient( $status_key ) ) || false === ( $sso_status = get_transient( $sso_key ) ) ) {

				$body['ft-ps-status-request'] = true;
				$body['site'] = urlencode( $this->site_url );
				$body['user'] = $current_user->ID;
				$body['product'] = $this->product_id;
				$body['email'] = urlencode( $current_user->user_email );
				$body['nicename'] = urlencode( $current_user->user_nicename );

				// Ping server for response
				if ( $request = wp_remote_post( $this->server_url, array( 'body' => $body, 'timeout' => 20 ) ) ) {

					if ( isset( $request['response']['code'] ) && 200 == $request['response']['code'] ) {

						// Response found a server, lets see if it hit a script we recognize
						$response = json_decode( $request['body'] );

						// Set the paypal button
						if ( ! empty( $response->paypal_button->args ) && ! empty( $response->paypal_button->base) )
							$this->paypal_button = add_query_arg( get_object_vars( $response->paypal_button->args ), $response->paypal_button->base );

						// Set the expired flag
						$this->support_expired = isset( $response->support_expired ) ? $response->support_expired : false;

						// Do we have a premium status?
						if ( isset( $response->support_status ) && $response->support_status ) {
							
							// We have a premium support license for this domain / product combination. Set the transient and property
							set_transient( $status_key, $response->support_status, 60*60*24 );	
							//set_transient( $status_key, $response->support_status, 60 );	
							$this->ps_status = $response->support_status;

							// Did we get a user sso back as well? Set the property if we did
							if ( isset( $response->user_sso ) && '' != $response->user_sso ) {
								set_transient( $sso_key, $response->user_sso, 60*60*2.9 );
								//set_transient( $sso_key, $response->user_sso, 60 );
								$this->sso_status = $response->user_sso;
							}
							
							// Set an auto update option with expiration date
							if ( isset( $response->support_status->exp_date )  && ! empty( $response->support_status->exp_date ) ) {
								update_option( $exp_option_key, $response->support_status->exp_date );
								
								// Check server for auto update?
								if ( $this->ps_status ) {
									delete_option( 'external_updates-' . $this->plugin_slug );
									$ft_ps_client_auto_update = new FT_Premium_Support_PluginUpdate_Checker( $this->server_url . '?ft-pss-premium-request=1&ft-pss-upgrade-request-product-id=' . $this->product_id . '&ft-pss-upgrade-request-site=' . $this->site_url . '&exp=' . $response->support_status->exp_date, SIMPLEMAP_PATH, $this->plugin_slug, 1 );
								}
							
							}
										
						} else {

							// No premium support so lets delete the keys
							delete_option( 'external_updates-' . $this->plugin_slug );
							delete_option( $exp_option_key );
						}
					}
				}
			} else {
				
				// Transients exist, therefore permission exists, set properties
				$this->ps_status = $ps_status;
				$this->sso_status = $sso_status;
				
			}

			// Check server for auto update?
			if ( $this->ps_status )
				$ft_ps_client_auto_update = new FT_Premium_Support_PluginUpdate_Checker( $this->server_url . '?ft-pss-upgrade-request=1&ft-pss-upgrade-request-product-id=' . $this->product_id . '&ft-pss-upgrade-request-site=' . $this->site_url, $this->plugin_basename, 'simplemap', 1 	);

                        // Maybe Nag Renewal
                        $this->maybe_trigger_renewal_nag(); 
		
		}

                function maybe_trigger_renewal_nag() {
                        // Has support expired?
                        if ( ! empty( $this->support_expired ) && ! is_object( $this->support_expired ) ) {
                                add_action( 'admin_notices', array( $this, 'support_expired' ) );
                                return;
                        }

                        if ( ! empty( $this->ps_status->exp_date ) ) {
                                $onemonthout = $this->ps_status->exp_date - 2628000;
                                // If we are within a month of expiration date
                                if ( $onemonthout <= strtotime( 'now' ) ) {
                                       add_action( 'admin_notices', array( $this, 'renew_soon' ) ); 
                                }
                        }

                }

                function support_expired() {
                        $link = ( $this->paypal_button ) ? esc_url( $this->paypal_button ) : 'http://simplemap-plugin.com';
                        echo "<div class='update-nag'>" . sprintf( __( "<strong style='color:red;'>Your license for SimpleMap has expired!</strong><br />You need to renew your license now for continued support and upgrades: <a href='%s' target='_blank'>Renew my license now</a>." ), $link ) . "</div>";  
                }

                function renew_soon() {
                        $link = ( $this->paypal_button ) ? esc_url( $this->paypal_button ) : 'http://simplemap-plugin.com';
                        echo "<div class='update-nag'>" . sprintf( __( "<strong style='color:red;'>SimpleMap is expiring soon!</strong><br />You will need to renew your license for continued support and upgrades: <a href='%s' target='_blank'>Renew my license now</a>." ), $link ) . "</div>";  
                }
		
	}
	
}

if ( !class_exists( 'FT_Premium_Support_PluginUpdate_Checker' ) ) :
	
/**
 * A custom plugin update checker. 
 * 
 * @author Janis Elsts
 * @copyright 2010
 * @version 1.0
 * @access public
 */
class FT_Premium_Support_PluginUpdate_Checker {
	
	var $metadataUrl = ''; //The URL of the plugin's metadata file.
	var $pluginFile = '';  //Plugin filename relative to the plugins directory.
	var $slug = '';        //Plugin slug.
	var $checkPeriod = 12; //How often to check for updates (in hours).
	var $optionName = '';  //Where to store the update info.
	
	/**
	 * Class constructor.
	 * 
	 * @param string $metadataUrl The URL of the plugin's metadata file.
	 * @param string $pluginFile Fully qualified path to the main plugin file.
	 * @param string $slug The plugin's 'slug'. If not specified, the filename part of $pluginFile sans '.php' will be used as the slug.
	 * @param integer $checkPeriod How often to check for updates (in hours). Defaults to checking every 12 hours. Set to 0 to disable automatic update checks.
	 * @param string $optionName Where to store book-keeping info about update checks. Defaults to 'external_updates-$slug'. 
	 * @return void
	 */
	function __construct( $metadataUrl, $pluginFile, $slug = '', $checkPeriod = 12, $optionName = '' ){
		
		$this->metadataUrl 	= $metadataUrl;
		$this->pluginFile 	= $pluginFile;
		$this->checkPeriod 	= $checkPeriod;
		$this->slug 		= $slug;
		
		//If no slug is specified, use the name of the main plugin file as the slug.
		//For example, 'my-cool-plugin/cool-plugin.php' becomes 'cool-plugin'.
		if ( empty( $this->slug ) ){
			$this->slug = basename( $this->pluginFile, '.php' );
		}
		
		if ( empty( $this->optionName ) ) {
			$this->optionName = 'external_updates-' . $this->slug;
		}
		
		if ( '' == $optionName )
			$this->optionName = 'external_updates-' . $this->slug;
		else
			$this->optionName 	= $optionName;
		
		$this->installHooks();		
	}
	
	/**
	 * Install the hooks required to run periodic update checks and inject update info 
	 * into WP data structures. 
	 * 
	 * @return void
	 */
	function installHooks(){

		//Override requests for plugin information
		add_filter( 'plugins_api', array( &$this, 'injectInfo' ), 10, 3 );
		
		//Insert our update info into the update array maintained by WP
		add_filter( 'site_transient_update_plugins', array( &$this,'injectUpdate'  )); //WP 3.0+
		add_filter( 'transient_update_plugins', array( &$this,'injectUpdate' ) ); //WP 2.8+
		
		//Set up the periodic update checks
		$cronHook = 'check_plugin_updates-' . $this->slug;

		if ( $this->checkPeriod > 0 ) {

			//Trigger the check via Cron
			add_filter( 'cron_schedules', array( &$this, '_addCustomSchedule' ) );

			if ( ! wp_next_scheduled( $cronHook ) && ! defined( 'WP_INSTALLING' ) ) {

				$scheduleName = 'every' . $this->checkPeriod . 'hours';
				wp_schedule_event( time(), $scheduleName, $cronHook );
				
			}
			add_action( $cronHook, array( &$this, 'checkForUpdates' ) );
			
			//In case Cron is disabled or unreliable, we also manually trigger 
			//the periodic checks while the user is browsing the Dashboard. 
			add_action( 'admin_init', array( &$this, 'maybeCheckForUpdates' ) );
			
		} else {
			//Periodic checks are disabled.
			wp_clear_scheduled_hook( $cronHook );
		}		
	}
	
	/**
	 * Add our custom schedule to the array of Cron schedules used by WP.
	 * 
	 * @param array $schedules
	 * @return array
	 */
	function _addCustomSchedule( $schedules ) {

		if ( $this->checkPeriod && ( $this->checkPeriod > 0 ) ) {
			$scheduleName = 'every' . $this->checkPeriod . 'hours';
			$schedules[$scheduleName] = array(
				'interval' => $this->checkPeriod * 3600, 
				'display' => sprintf( 'Every %d hours', $this->checkPeriod ),
			);
		}		

		return $schedules;
	}
	
	/**
	 * Retrieve plugin info from the configured API endpoint.
	 * 
	 * @uses wp_remote_get()
	 * 
	 * @param array $queryArgs Additional query arguments to append to the request. Optional.
	 * @return PluginInfo
	 */
	function requestInfo( $queryArgs = array() ) {
		//Query args to append to the URL. Plugins can add their own by using a filter callback (see addQueryArgFilter()).
		$queryArgs['installed_version'] = $this->getInstalledVersion(); 
		$queryArgs = apply_filters( 'puc_request_info_query_args-' . $this->slug, $queryArgs );

		//Various options for the wp_remote_get() call. Plugins can filter these, too.
		$options = array(
			'timeout' => 10, //seconds
			'headers' => array(
				'Accept' => 'application/json'
			),
		);

		$options = apply_filters( 'puc_request_info_options-' . $this->slug, $options );

		//The plugin info should be at 'http://your-api.com/url/here/$slug/info.json'
		$url = $this->metadataUrl; 
		if ( ! empty( $queryArgs ) ){
			$url = add_query_arg( $queryArgs, $url );
		}

		$result = wp_remote_get(
			$url,
			$options
		);

		//Try to parse the response
		$ft_premium_support_plugin_info = null;
		if ( !is_wp_error( $result ) && isset( $result['response']['code'] ) && ( $result['response']['code'] == 200 ) && !empty( $result['body'] ) ){
			$ft_premium_support_plugin_info = FT_Premium_Support_PluginInfo::fromJson( $result['body'] );
		}
		
		$ft_premium_support_plugin_info = apply_filters( 'puc_request_info_result-' . $this->slug, $ft_premium_support_plugin_info, $result );
		
		return $ft_premium_support_plugin_info;
	}
	
	/**
	 * Retrieve the latest update (if any) from the configured API endpoint.
	 * 
	 * @uses PluginUpdateChecker::requestInfo()
	 * 
	 * @return PluginUpdate An instance of PluginUpdate, or NULL when no updates are available.
	 */
	function requestUpdate() {
		//For the sake of simplicity, this function just calls requestInfo() 
		//and transforms the result accordingly.
		$ft_premium_support_plugin_info = $this->requestInfo( array( 'checking_for_updates' => '1' ) );
		
		if ( $ft_premium_support_plugin_info == null )
			return null;
		
		return FT_Premium_Support_PluginUpdate::fromPluginInfo( $ft_premium_support_plugin_info );
	}
	
	/**
	 * Get the currently installed version of the plugin.
	 * 
	 * @return string Version number.
	 */
	function getInstalledVersion() {
		
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$allPlugins = get_plugins();

		if ( array_key_exists( $this->pluginFile, $allPlugins ) && array_key_exists( 'Version', $allPlugins[$this->pluginFile] ) )
			return $allPlugins[$this->pluginFile]['Version']; 
		else
			return ''; //This should never happen.

	}
	
	/**
	 * Check for plugin updates. 
	 * The results are stored in the DB option specified in $optionName.
	 * 
	 * @return void
	 */
	function checkForUpdates(){
		$state = get_option( $this->optionName );

		if ( empty($state) ){
			$state = new StdClass;
			$state->lastCheck = 0;
			$state->checkedVersion = '';
			$state->update = null;
		}
		
		$state->lastCheck = time();
		$state->checkedVersion = $this->getInstalledVersion();
		update_option( $this->optionName, $state ); //Save before checking in case something goes wrong 
		
		$state->update = $this->requestUpdate();
		update_option( $this->optionName, $state );
	}
	
	/**
	 * Check for updates only if the configured check interval has already elapsed.
	 * 
	 * @return void
	 */
	function maybeCheckForUpdates(){

		if ( empty( $this->checkPeriod ) )
			return;
		
		$state = get_option( $this->optionName );

		$shouldCheck =
			empty( $state ) ||
			! isset( $state->lastCheck ) || 
			( ( time() - $state->lastCheck ) >= $this->checkPeriod*3600 );
		
		if ( $shouldCheck ){
			$this->checkForUpdates();
		}
	}
	
	/**
	 * Intercept plugins_api() calls that request information about our plugin and 
	 * use the configured API endpoint to satisfy them. 
	 * 
	 * @see plugins_api()
	 * 
	 * @param mixed $result
	 * @param string $action
	 * @param array|object $args
	 * @return mixed
	 */
	function injectInfo( $result, $action = null, $args = null ) {

    	$relevant = ( $action == 'plugin_information') && isset( $args->slug ) && ( $args->slug == $this->slug );
	
		if ( !$relevant ){
			return $result;
		}

		$ft_premium_support_plugin_info = $this->requestInfo();

		if ( $ft_premium_support_plugin_info )
			return $ft_premium_support_plugin_info->toWpFormat();
				
		return $result;
	}
	
	/**
	 * Insert the latest update (if any) into the update list maintained by WP.
	 * 
	 * @param array $updates Update list.
	 * @return array Modified update list.
	 */
	function injectUpdate( $updates ){

		$state = get_option( $this->optionName );

		//Is there an update to insert?
		if ( ! empty( $state ) && isset( $state->update ) && ! empty( $state->update ) ) {
			
			//Only insert updates that are actually newer than the currently installed version.
			if ( version_compare( $state->update->version, $this->getInstalledVersion(), '>' ) )
				$updates->response[$this->pluginFile] = $state->update->toWpFormat();

		}
				
		return $updates;
	}
	
	/**
	 * Register a callback for filtering query arguments. 
	 * 
	 * The callback function should take one argument - an associative array of query arguments.
	 * It should return a modified array of query arguments.
	 * 
	 * @uses add_filter() This method is a convenience wrapper for add_filter().
	 * 
	 * @param callback $callback 
	 * @return void
	 */
	function addQueryArgFilter( $callback ) {
		add_filter( 'puc_request_info_query_args-' . $this->slug, $callback );
	}
	
	/**
	 * Register a callback for filtering arguments passed to wp_remote_get().
	 * 
	 * The callback function should take one argument - an associative array of arguments -
	 * and return a modified array or arguments. See the WP documentation on wp_remote_get()
	 * for details on what arguments are available and how they work. 
	 * 
	 * @uses add_filter() This method is a convenience wrapper for add_filter().
	 * 
	 * @param callback $callback
	 * @return void
	 */
	function addHttpRequestArgFilter($callback) {
		add_filter( 'puc_request_info_options-' . $this->slug, $callback );
	}
	
	/**
	 * Register a callback for filtering the plugin info retrieved from the external API.
	 * 
	 * The callback function should take two arguments. If the plugin info was retrieved 
	 * successfully, the first argument passed will be an instance of  PluginInfo. Otherwise, 
	 * it will be NULL. The second argument will be the corresponding return value of 
	 * wp_remote_get (see WP docs for details).
	 *  
	 * The callback function should return a new or modified instance of PluginInfo or NULL.
	 * 
	 * @uses add_filter() This method is a convenience wrapper for add_filter().
	 * 
	 * @param callback $callback
	 * @return void
	 */
	function addResultFilter( $callback ) {
		add_filter( 'puc_request_info_result-' . $this->slug, $callback, 10, 2 );
	}
}
	
endif;

if ( ! class_exists( 'FT_Premium_Support_PluginInfo' ) ) :

/**
 * A container class for holding and transforming various plugin metadata.
 * 
 * @author Janis Elsts
 * @copyright 2010
 * @version 1.0
 * @access public
 */
class FT_Premium_Support_PluginInfo {
	
	//Most fields map directly to the contents of the plugin's info.json file.
	//See the relevant docs for a description of their meaning.  
	var $name;
	var $slug;
	var $version;
	var $homepage;
	var $sections;
	var $download_url;

	var $author;
	var $author_homepage;
	
	var $requires;
	var $tested;
	var $upgrade_notice;
	
	var $rating;
	var $num_ratings;
	var $downloaded;
	var $last_updated;
	
	var $id = 0; //The native WP.org API returns numeric plugin IDs, but they're not used for anything.
		
	/**
	 * Create a new instance of PluginInfo from JSON-encoded plugin info 
	 * returned by an external update API.
	 * 
	 * @param string $json Valid JSON string representing plugin info. 
	 * @return PluginInfo New instance of PluginInfo, or NULL on error.
	 */
	function fromJson( $json ){
		$apiResponse = json_decode( $json );
		if ( empty( $apiResponse ) || !is_object( $apiResponse ) )
			return null;
		
		//Very, very basic validation.
		$valid = isset( $apiResponse->name ) && ! empty( $apiResponse->name ) && isset( $apiResponse->version ) && ! empty( $apiResponse->version );
		if ( ! $valid )
			return null;
		
		$info = new FT_Premium_Support_PluginInfo();
		foreach( get_object_vars( $apiResponse ) as $key => $value ) {
			$info->$key = $value;
		}
		
		return $info;		
	}
	
	/**
	 * Transform plugin info into the format used by the native WordPress.org API
	 * 
	 * @return object
	 */
	function toWpFormat(){
		$info = new StdClass;
		
		//The custom update API is built so that many fields have the same name and format
		//as those returned by the native WordPress.org API. These can be assigned directly. 
		$sameFormat = array(
			'name', 'slug', 'version', 'requires', 'tested', 'rating', 'upgrade_notice',
			'num_ratings', 'downloaded', 'homepage', 'last_updated',
		);
		
		foreach( $sameFormat as $field ){
			if ( isset( $this->$field ) )
				$info->$field = $this->$field;
		}
		
		//Other fields need to be renamed and/or transformed.
		$info->download_link = $this->download_url;
		
		if ( ! empty( $this->author_homepage ) )
			$info->author = sprintf( '<a href="%s">%s</a>', $this->author_homepage, $this->author );
		else
			$info->author = $this->author;
		
		if ( is_object( $this->sections ) ){
			$info->sections = get_object_vars( $this->sections );
		} elseif ( is_array( $this->sections ) ) {
			$info->sections = $this->sections;
		} else {
			$info->sections = array( 'description' => '' );
		}
				
		return $info;
	}
}
	
endif;

if ( ! class_exists( 'FT_Premium_Support_PluginUpdate' ) ):

/**
 * A simple container class for holding information about an available update.
 * 
 * @author Janis Elsts
 * @copyright 2010
 * @version 1.0
 * @access public
 */
class FT_Premium_Support_PluginUpdate {
	
	var $id = 0;
	var $slug;
	var $version;
	var $homepage;
	var $download_url;
	var $upgrade_notice;
	
	/**
	 * Create a new instance of PluginUpdate from its JSON-encoded representation.
	 * 
	 * @param string $json
	 * @return PluginUpdate
	 */
	function fromJson( $json ){
		//Since update-related information is simply a subset of the full plugin info,
		//we can parse the update JSON as if it was a plugin info string, then copy over
		//the parts that we care about.
		$ft_premium_support_plugin_info = FT_Premium_Support_PluginInfo::fromJson( $json );
		if ( $ft_premium_support_plugin_info != null )
			return FT_Premium_Support_PluginUpdate::fromPluginInfo( $ft_premium_support_plugin_info );
		else
			return null;
			
	}
	
	/**
	 * Create a new instance of PluginUpdate based on an instance of PluginInfo.
	 * Basically, this just copies a subset of fields from one object to another.
	 * 
	 * @param PluginInfo $info
	 * @return PluginUpdate
	 */
	function fromPluginInfo( $info ){
		
		$update = new FT_Premium_Support_PluginUpdate();
		$copyFields = array( 'id', 'slug', 'version', 'homepage', 'download_url', 'upgrade_notice' );
		foreach( $copyFields as $field ) {
			$update->$field = $info->$field;
		}
		
		return $update;
		
	}
	
	/**
	 * Transform the update into the format used by WordPress native plugin API.
	 * 
	 * @return object
	 */
	function toWpFormat(){
		
		$update = new StdClass;
		
		$update->id	 			= $this->id;
		$update->slug 			= $this->slug;
		$update->new_version 	= $this->version;
		$update->url 			= $this->homepage;
		$update->package 		= $this->download_url;
		
		if ( ! empty( $this->upgrade_notice ) )
			$update->upgrade_notice = $this->upgrade_notice;
		
		return $update;
		
	}
	
}
	
endif;

/**
 * Checks for premium support
 */
function url_has_ftps_for_item( &$ps_object ) {
	
	if ( is_object( $ps_object ) && $ps_object->ps_status )
		return true;
		
	return false;

}

/**
 * Return paypal button
 */
function get_ftps_paypal_button( &$ps_object ) {

	if ( is_object( $ps_object ) && isset( $ps_object->paypal_button ) )
		return $ps_object->paypal_button;
		
	return false;
}

/**
 * Return learn more link
 */
function get_ftps_learn_more_link( &$ps_object ) {

	if ( is_object( $ps_object ) && isset( $ps_object->learn_more_link ) )
		return $ps_object->learn_more_link;
		
	return false;
}

/**
 * Return SSO key
 */
function get_ftps_sso_key( &$ps_object ) {

	if ( is_object( $ps_object ) && isset( $ps_object->sso_status ) )
		return $ps_object->sso_status;
		
	return false;
}

/**
 * Return this site URL that has premium support
 */
function get_ftps_site( &$ps_object ) {
	
	if ( is_object( $ps_object ) && isset( $ps_object->ps_status->site ) )
		return $ps_object->ps_status->site;
		
	return false;
}

/**
 * Return purchase date
 */
function get_ftps_purchase_date( &$ps_object ) {

	if ( is_object( $ps_object ) && isset( $ps_object->ps_status->purchase_date ) )
		return $ps_object->ps_status->purchase_date;
		
	return false;
}

/**
 * Return expiration date
 */
function get_ftps_exp_date( &$ps_object ) {

	if ( is_object( $ps_object ) && isset( $ps_object->ps_status->exp_date ) )
		return $ps_object->ps_status->exp_date;
		
	return false;
}

/**
 * Return email of person who purchased premium support
 */
function get_ftps_email( &$ps_object ) {

	if ( is_object( $ps_object ) && isset( $ps_object->ps_status->email ) )
		return $ps_object->ps_status->email;
		
	return false;
}

/**
 * Return name of person who purchased premium support
 */
function get_ftps_name( &$ps_object ) {

	if ( is_object( $ps_object ) && isset( $ps_object->ps_status->name ) )
		return $ps_object->ps_status->name;
		
	return false;
}

?>
