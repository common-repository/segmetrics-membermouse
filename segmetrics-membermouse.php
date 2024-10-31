<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://segmetrics.io
 * @since             1.0.0
 * @package           Segmetrics_Membermouse
 *
 * @wordpress-plugin
 * Plugin Name:       SegMetrics MemberMouse Add-On
 * Plugin URI:        https://segmetrics.io/integration/membermouse
 * Description:       Connect SegMetrics to your MemberMouse data
 * Version:           1.1.0
 * Author:            SegMetrics
 * Author URI:        https://segmetrics.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       segmetrics-membermouse
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SEGMETRICS_MEMBERMOUSE_VERSION', '2.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-segmetrics-membermouse-activator.php
 */
function activate_segmetrics_membermouse() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-segmetrics-membermouse-activator.php';
	Segmetrics_Membermouse_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-segmetrics-membermouse-deactivator.php
 */
function deactivate_segmetrics_membermouse() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-segmetrics-membermouse-deactivator.php';
	Segmetrics_Membermouse_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_segmetrics_membermouse' );
register_deactivation_hook( __FILE__, 'deactivate_segmetrics_membermouse' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-segmetrics-membermouse.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_segmetrics_membermouse() {

	$plugin = new Segmetrics_Membermouse();
	$plugin->run();

}
run_segmetrics_membermouse();
