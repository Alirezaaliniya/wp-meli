<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://nias.ir
 * @since      1.0.0
 *
 * @package    Ns_Connection_Blocker
 * @subpackage Ns_Connection_Blocker/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Ns_Connection_Blocker
 * @subpackage Ns_Connection_Blocker/includes
 * @author     Nias.ir Alireza Aliniya <info@nias.ir>
 */
class Ns_Connection_Blocker {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Ns_Connection_Blocker_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'NS_CONNECTION_BLOCKER_VERSION' ) ) {
            $this->version = NS_CONNECTION_BLOCKER_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'ns-connection-blocker';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        // $this->define_public_hooks(); // No public hooks needed for now

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Ns_Connection_Blocker_Loader. Orchestrates the hooks of the plugin.
     * - Ns_Connection_Blocker_i18n. Defines internationalization functionality.
     * - Ns_Connection_Blocker_Admin. Defines all hooks for the admin area.
     * - Ns_Connection_Blocker_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once NS_CONNECTION_BLOCKER_PLUGIN_DIR . 'includes/class-ns-connection-blocker-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once NS_CONNECTION_BLOCKER_PLUGIN_DIR . 'includes/class-ns-connection-blocker-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once NS_CONNECTION_BLOCKER_PLUGIN_DIR . 'admin/class-ns-connection-blocker-admin.php';

        $this->loader = new Ns_Connection_Blocker_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Ns_Connection_Blocker_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Ns_Connection_Blocker_i18n();

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Ns_Connection_Blocker_Admin( $this->get_plugin_name(), $this->get_version() );

        // Add menu page
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

        // Register settings
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

        // Hook to capture requests
        $this->loader->add_action( 'http_api_debug', $plugin_admin, 'log_http_requests', 10, 5 );

        // Filter to block requests
        $this->loader->add_filter( 'pre_http_request', $plugin_admin, 'filter_http_requests', 100, 3 ); // High priority

        // Add settings link to plugin page
        $plugin_basename = plugin_basename( NS_CONNECTION_BLOCKER_PLUGIN_DIR . $this->plugin_name . '.php' );
        $this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_settings_link_to_plugins_page' );

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Ns_Connection_Blocker_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
