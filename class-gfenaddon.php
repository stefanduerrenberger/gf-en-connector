<?php
/*
Plugin Name: Gravity Forms Engaging Networks Connector
Plugin URI: 
Description: Sends Gravity Form data to Engaging Networks
Version: 1.0
Author: Stefan Dürrenberger, Greenpeace Switzerland
Text Domain: enaddon
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Gravity Forms Engaging Networks Connector is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Gravity Forms Engaging Networks Connector is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Gravity Forms Engaging Networks Connector. 
If not, see http://www.gnu.org/licenses/gpl-2.0.html
*/

GFForms::include_addon_framework();

class GFEnAddOn extends GFAddOn {

	protected $_version = GF_EN_ADDON_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'enaddon';
	protected $_path = 'gravityforms-en-connector/gravityforms-en-connector.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Engaging Networks Add-On';
	protected $_short_title = 'Engaging Networks';

	private static $_instance = null;

	// Maps form fields to EN fields to export 
	// TODO: should be configurable in wp-admin later on
	protected $formFieldMapping = array(
		1 => array(),
		2 => array(
			'First name' => '1.3',
			'Last name' => '1.6',
			'Email' => '2',
			'EmailConfirm' => '2',
			//'newsletter' => '4.1'
			),
		3 => array(),
	);

	/**
	 * Get an instance of this class.
	 *
	 * @return GFEnAddOn
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFEnAddOn();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 *
	 */
	public function init() {
		parent::init();

		// Add the en status field to the entries list
		add_filter( 'gform_entry_meta', array($this, 'enaddon_entry_meta'), 10, 2);

		// Add the EN status to an entry 
		add_action( 'gform_after_submission', array($this, 'process_new_entry'), 10, 2 );
	}

	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Creates a custom page for this add-on.
	 *
	 */
	public function plugin_page() {
		//$this->runCron();

		// cycle through forms and run new entries for each of them if EN connector is enabled.
		$forms = GFAPI::get_forms();

		echo "<h1>EN data activity</h1>";
		echo "<p>List of all forms with Engaging Networks enabled and the activity of the last 50 entries</p>";
		echo "<p>Data is not send to EN immediately because EN has a limit of 100 entries per 5 minutes and form. if There are new entries in the form, you should see recent activity here.</p>";

		foreach ($forms as $form) {
			if (array_key_exists('enaddon', $form) && $form['enaddon']['enabled'] == "1") {
				echo '<h2>' . $form['title'] . ' (ID ' . $form['id'] . ')</h2>';

				$searchCriteria = array(
					'status' => 'active', // don't get spam and trash entries
					);

				$sorting = null;
				$sorting = array( 'key' => 'en_datetime', 'direction' => 'DESC', 'is_numeric' => false );
				$paging = array( 'offset' => 0, 'page_size' => 20 );
				$total_count = null;
				$entries = GFAPI::get_entries( $form['id'], $searchCriteria, $sorting, $paging, $total_count );

				echo '<ul style="list-style-type: disc; margin-left: 2em;">';
				foreach ($entries as $entry) {
					echo '<li>' . ucfirst($entry['en_status']) .  ' (';
					if ($entry['en_status'] == 'new') {
						echo 'queued';
					}
					else {
						echo 'status update ' . $entry['en_datetime'];
					}
					echo ')' . ': ' . $entry['1.3'] . ' ' . $entry['1.6'] . ' (Created: ' . $entry['date_created'] .  ')</li>';
				}
				echo '</ul>';
				
			}
		}

	}

	/**
	 * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => esc_html__( 'Engaging Networks Settings', 'enaddon' ),
				'fields' => array(
					array(
						'label'   => esc_html__( 'Enable Engaging Networks', 'enaddon' ),
						'type'    => 'checkbox',
						'name'    => 'enabled',
						'tooltip' => esc_html__( 'Enable Engaging Networks to send the form data to Engaging Networks. Make sure to fill in the settings below.', 'enaddon' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'enaddon' ),
								'name'  => 'enabled',
							),
						),
					),
					array(
						'label'             => esc_html__( 'EN Client ID', 'enaddon' ),
						'type'              => 'text',
						'name'              => 'en_client_id',
						'tooltip'           => esc_html__( 'The client ID can be found in the generated form in Engaging Networks', 'enaddon' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'EN Campaign ID', 'enaddon' ),
						'type'              => 'text',
						'name'              => 'en_campaign_id',
						'tooltip'           => esc_html__( 'The ID of the campaign in Engaging Networks (Can also be found in the URL of the generated form)', 'enaddon' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
				),
			),
		);
	}

	/**
	 * Define the markup for the my_custom_field_type type field.
	 *
	 * @param array $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed.
	 */
	public function settings_my_custom_field_type( $field, $echo = true ) {
		echo '<div>' . esc_html__( 'My custom field contains a few settings:', 'enaddon' ) . '</div>';

		// get the text field settings from the main field and then render the text field
		$text_field = $field['args']['text'];
		$this->settings_text( $text_field );

		// get the checkbox field settings from the main field and then render the checkbox field
		$checkbox_field = $field['args']['checkbox'];
		$this->settings_checkbox( $checkbox_field );
	}


	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * The feedback callback for the 'mytextbox' setting on the plugin settings page and the 'mytext' setting on the form settings page.
	 *
	 * @param string $value The setting value.
	 *
	 * @return bool
	 */
	public function is_valid_setting( $value ) {
		return strlen( $value ) < 50;
	}


	// # Engaging Networks meta fields -------------------------------------------------------------------------------

	/*
	* Adds the meta fields for entries
	*/
	function enaddon_entry_meta($entry_meta, $form_id){
		// The meta fields we'd like to use

		// new, success, error, failed
		$entry_meta['en_status'] = array(
			'label' => 'EN Status',
			'is_numeric' => false,
			'update_entry_meta_callback' => array($this, 'update_entry_meta_status'), 
			'is_default_column' => true
		);
		$entry_meta['en_detail'] = array(
			'label' => 'EN Detail',
			'is_numeric' => false,
			'update_entry_meta_callback' => array($this, 'update_entry_meta_detail'), 
			'is_default_column' => false
		);

		$entry_meta['en_datetime'] = array(
			'label' => 'EN Date/Time',
			'is_numeric' => false,
			'update_entry_meta_callback' => array($this, 'update_entry_meta_datetime'),  
			'is_default_column' => false
		);


		return $entry_meta;
	}

	function update_entry_meta_status ($key, $lead, $form ){
		$value = 'new';
		return $value;
	}
	function update_entry_meta_detail ($key, $lead, $form ){
		$value = '';
		return $value;
	}
	function update_entry_meta_datetime ($key, $lead, $form ){
		$value = '';
		return $value;
	}

	/*
	* Adds an en_status of 'new' to submitted entries
	*/
	public function process_new_entry($entry, $form ) {
		//if ($form['enaddon']['enabled'] == '1')	{
			gform_update_meta( $entry['id'], 'en_status', 'new' );
			$entry['en_status'] = 'new';
		//}

		return $entry;
	}


	// # Engaging Networks Cron -----------------------------------------------------------------------------------------

	/*
	* This is run by cron, cycles through the forms and picks the entries to send to EN
	*/
	public function runCron() {
		// cycle through forms and run new entries for each of them if EN connector is enabled.
		$forms = GFAPI::get_forms();

		foreach ($forms as $form) {
			if (array_key_exists('enaddon', $form) && $form['enaddon']['enabled'] == "1") {

				$searchCriteria = array(
					'status' => 'active', // don't get spam and trash entries
					'field_filters' => array(
						array(
							'key' => 'en_status',
							'operator' => 'not in',
							'value' => array('success', 'error'),
							)
						)
					);

				$sorting = null;
				$paging = array( 'offset' => 0, 'page_size' => 95 ); // Get 95 entries max
				$total_count = null;
				$entries = GFAPI::get_entries( $form['id'], $searchCriteria, $sorting, $paging, $total_count );

				foreach ($entries as $entry) {
					$this->sendtoEN($entry, $form);
				}
			}
		}
	}

	/*
	* Prepairs the entries for EN and sends them
	*/
	protected function sendtoEN($entry, $form) {
		echo 'sendtoEN';
		// init response return object
		$response = new stdClass;
		$response->httpCode = 0;
		$response->body = '';

		// get from settings
		$engagingClientId = $form['enaddon']['en_client_id'];
		$engagingCampaignId = $form['enaddon']['en_campaign_id'];

		$params = array(
			'ea_requested_action' => 'ea_submit_user_form',
			'ea_javascript_enabled' => 'true',
			'ea.AJAX.submit' => 'false',
			'ea.client.id' => urlencode($engagingClientId),
			'ea.campaign.id' => urlencode($engagingCampaignId),
			//'ea.form.id' => urlencode($engagingFormId),
			'ea.campaign' => 'TISA',
			'ea.submitted.page' => '1',
			/*'First name' => 'Andreas',
			'Last name' => urlencode('Dürrenberger'),
			'Email' => 'stduerre@greenpeace.org',
			'EmailConfirm' => 'stduerre@greenpeace.org',*/
			//'country' => 'CH',
			'language' => 'de',
			'email_ok' => 'y',
			//'phone_number' => $this->entities($supporter->phone),
			//'language_pref' => $contextKey,*/
			//'legacy_optin_details' => $legacy_optin_details

			);
		
		foreach ($this->formFieldMapping[$form['id']] as $name => $key) {
			$params[$name] = $entry[$key];
		}

		// Post data to EN
		$url = 'http://e-activist.com/ea-action/action';
		$post_data = http_build_query($params);

		$response = $this->queryUrl($url, $post_data);

		if ($response->httpCode == 200) {
			gform_update_meta( $entry['id'], 'en_status', 'success' );
		}
		else {
			if ($entry['en_status'] == 'error'){
				gform_update_meta( $entry['id'], 'en_status', 'failed' ); // mark as failed to retry
			}
			else {
				gform_update_meta( $entry['id'], 'en_status', 'error' ); // mark as error, don't try again
			}
		}
		
		gform_update_meta( $entry['id'], 'en_detail', 'Status-Code: ' . $response->httpCode );
		gform_update_meta( $entry['id'], 'en_datetime', date(DateTime::ATOM) );
	}

	/**
	 * Performs a GET or POST request to a given URL (cURL wrapper).
	 *
	 * @access private
	 * @param string $url
	 * @param array $postArgs An array of POST parameters (if not null it will perform a POST request).
	 * @param array $options An array of curl options.
	 * @return object The response ->header, ->body, ->httpCode, ->rawResponse and ->responseInfo.
	 */
	
	private function queryUrl($url, $postArgs = NULL, $options = NULL) {

		$resource = new stdClass;

		$ch = curl_init($url);

		if ( ! is_null($postArgs)) {
			curl_setopt ($ch, CURLOPT_POST, TRUE);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $postArgs);
		}

		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // for the moment, until Change.org fixes its stuff!!!

		if (is_array($options)) {
			foreach ($options as $key => $value) {
				curl_setopt($ch, constant('CURLOPT_'.strtoupper($key)), $value);
			}
		}

		$resource->header = '';
		$resource->body = '';
		$resource->httpCode = '';
		$resource->rawResponse = curl_exec($ch);
		$resource->responseInfo = '';

		list($resource->header, $resource->body) = explode("\r\n\r\n", $resource->rawResponse."\r\n\r\n", 2);

		$responseInfo = curl_getinfo($ch);
		curl_close($ch);

		$resource->httpCode = $responseInfo['http_code'];
		$resource->responseInfo = $responseInfo;

		return $resource;
	}
	
}