<?php
/**
 * Plugin Name: NS Connection Blocker
 * Plugin URI: https://nias.ir
 * Description: Recognizes all outgoing connections and allows blocking them individually.
 * Version: 1.0.0
 * Author: Nias.ir Alireza Aliniya
 * Author URI: https://nias.ir
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ns-connection-blocker
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'NS_CONNECTION_BLOCKER_VERSION', '1.0.0' );
define( 'NS_CONNECTION_BLOCKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NS_CONNECTION_BLOCKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require NS_CONNECTION_BLOCKER_PLUGIN_DIR . 'includes/class-ns-connection-blocker.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ns_connection_blocker() {

    $plugin = new Ns_Connection_Blocker();
    $plugin->run();

}
run_ns_connection_blocker();
