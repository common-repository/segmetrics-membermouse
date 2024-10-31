<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://segmetrics.io
 * @since      1.0.0
 *
 * @package    Segmetrics_Membermouse
 * @subpackage Segmetrics_Membermouse/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Segmetrics_Membermouse
 * @subpackage Segmetrics_Membermouse/public
 * @author     SegMetrics <support@segmetrics.>
 */
class Segmetrics_Membermouse_Public {

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

	private $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->api = new Segmetrics_Membermouse_Api();

	}

	public function register_routes()
    {
        // https://www.sitepoint.com/creating-custom-endpoints-for-the-wordpress-rest-api/
        register_rest_route('segmetrics/v1', '/(?P<table>[a-zA-Z0-9-_]+)', array(
            'methods'  => 'GET',
            'callback' => [$this->api, 'query'],
            'permission_callback' => [$this->api, 'authorize']
        ));
    }
}
