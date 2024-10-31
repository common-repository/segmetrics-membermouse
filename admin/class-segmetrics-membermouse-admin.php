<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://segmetrics.io
 * @since      1.0.0
 *
 * @package    Segmetrics_Membermouse
 * @subpackage Segmetrics_Membermouse/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Segmetrics_Membermouse
 * @subpackage Segmetrics_Membermouse/admin
 * @author     SegMetrics <support@segmetrics.>
 */
class Segmetrics_Membermouse_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function check_compatibility()
    {
        if(!defined('SEGMETRICS_VERSION')) {

            // Set up the admin notice hook
            add_action( 'admin_notices', function(){
                echo '<div class="error"><p>' . esc_html__(
                        'Please enable the SegMetrics plugin to allow the SegMetrics MemberMouse Add-On plugin to work.',
                        'segmetrics-api-membermouse'
                    ) . '</p></div>';
            } );


            // Turn off the plugin
            deactivate_plugins( 'segmetrics-membermouse/segmetrics-membermouse.php' );
            if ( isset( $_GET['activate'] ) ) { unset( $_GET['activate'] ); }


        }elseif(empty(get_option('seg_auth', [] ))){

            echo '<div class="error"><p>' . esc_html__(
                    'Please configure your API Key and Account Id in the SegMetrics plugin to allow the SegMetrics MemberMouse Add-On plugin to work.',
                    'segmetrics-api-membermouse'
                ) . '</p></div>';
        }
    }

}
