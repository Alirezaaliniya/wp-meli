<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://nias.ir
 * @since      1.0.0
 *
 * @package    Ns_Connection_Blocker
 * @subpackage Ns_Connection_Blocker/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ns_Connection_Blocker
 * @subpackage Ns_Connection_Blocker/admin
 * @author     Nias.ir Alireza Aliniya <info@nias.ir>
 */
class Ns_Connection_Blocker_Admin {

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
     * Option name for storing logged requests.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $logged_requests_option_name
     */
    private $logged_requests_option_name;

    /**
     * Option name for storing settings (blocked hosts).
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $settings_option_name
     */
    private $settings_option_name;


    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->logged_requests_option_name = $this->plugin_name . '_logged_requests_transient'; // Changed to be more specific
        $this->settings_option_name = $this->plugin_name . '_blocked_hosts';

    }

    /**
     * Add the plugin's settings page to the WordPress admin menu.
     *
     * @since 1.0.0
     */
    public function add_plugin_admin_menu() {
        $hook_suffix = add_options_page(
            __( 'تنظیمات WPmeli', 'ns-connection-blocker' ), // Page title
            __( 'WPmeli نت ملی', 'ns-connection-blocker' ),          // Menu title
            'manage_options',                                                 // Capability
            $this->plugin_name,                                               // Menu slug
            array( $this, 'display_plugin_setup_page' )                      // Callback function
        );
        // Load assets only on plugin's settings page
        add_action( 'admin_print_styles-' . $hook_suffix, array( $this, 'enqueue_styles' ) );
        add_action( 'admin_print_scripts-' . $hook_suffix, array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_setup_page() {
        include_once( 'partials/' . $this->plugin_name . '-admin-display.php' );
    }

    /**
     * Register the plugin's settings.
     *
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting(
            $this->plugin_name, // Option group
            $this->settings_option_name, // Option name
            array( $this, 'sanitize_settings' ) // Sanitize callback
        );

        add_settings_section(
            $this->plugin_name . '_general_section', // ID
            __( 'قوانین مسدود سازی اتصالات', 'ns-connection-blocker' ), // Title
            array( $this, 'general_section_callback' ), // Callback
            $this->plugin_name // Page
        );

        // This field will be dynamically populated based on detected connections
        // We'll add a placeholder here or handle it entirely in display_plugin_setup_page

        // AJAX handler for pinging (to make HTTP requests for logging)
        add_action( 'wp_ajax_ns_ping_test', array( $this, 'ajax_ping_test' ) );
        // AJAX handler for getting detected connections
        add_action( 'wp_ajax_ns_get_detected_connections', array( $this, 'ajax_get_detected_connections' ) );
    }

    /**
     * AJAX handler for ns_ping_test.
     * Its main purpose is to make an HTTP request that our http_api_debug hook can log.
     * @since 1.0.0
     */
    public function ajax_ping_test() {
        check_ajax_referer( 'ns_check_connections_nonce', '_ajax_nonce' );

        $target_host = isset($_GET['target']) ? sanitize_text_field($_GET['target']) : 'wordpress.org';

        // The actual request for "Check Connections" context.
        // The 'action' => 'ns_ping_test' in the JS call helps log_http_requests identify this.
        wp_remote_get( 'http://' . $target_host, array(
            'timeout' => 5, // Short timeout, we don't care about the response
            'sslverify' => false // Some test targets might not have SSL or be http
        ));

        // It's important that this AJAX call itself (to admin-ajax.php) isn't logged as an external connection.
        // log_http_requests should filter out requests to the site's own admin_url().

        wp_send_json_success( array('message' => sprintf(__( 'Ping attempt made to %s', 'ns-connection-blocker' ), $target_host) ) );
    }


    /**
     * AJAX handler for getting detected connections.
     * @since 1.0.0
     */
    public function ajax_get_detected_connections() {
        check_ajax_referer( 'ns_check_connections_nonce', 'nonce' );

        // Trigger a final dummy request to ensure the logger runs one last time in this AJAX context
        // This helps capture any connections that might be made by WordPress during AJAX calls.
        // We need to make sure this call itself is not logged.
        wp_remote_get( 'http://127.0.0.1/ns-dummy-request-for-log-refresh', array('timeout' => 1, 'sslverify' => false) );


        $active_connections = get_transient( $this->logged_requests_option_name );
        $blocked_hosts_settings = get_option( $this->settings_option_name, array() );

        ob_start();
        if ( ! empty( $active_connections ) || ! empty( $blocked_hosts_settings ) ) {
            echo '<h2>' . __( 'اتصالات شناسایی شده و پیکربندی شده', 'ns-connection-blocker' ) . '</h2>';
            echo '<p>' . __( 'برای مسدود کردن یک اتصال، کلید مربوطه را فعال کرده و سپس روی "ذخیره تغییرات" کلیک کنید.', 'ns-connection-blocker' ) . '</p>';
            echo '<table class="form-table ns-connections-table"><tbody>';

            $all_display_hosts = array();
            if(is_array($active_connections)) $all_display_hosts = array_merge($all_display_hosts, $active_connections);
            if(is_array($blocked_hosts_settings)) $all_display_hosts = array_merge($all_display_hosts, array_keys($blocked_hosts_settings));
            $all_display_hosts = array_unique(array_filter($all_display_hosts));
            sort($all_display_hosts);

            if ( empty( $all_display_hosts ) ) {
                 echo '<tr><td colspan="2">' . __( 'هیچ اتصال خارجی جدیدی در طول بررسی شناسایی نشد. میزبان‌هایی که قبلاً پیکربندی شده‌اند (در صورت وجود) نمایش داده می‌شوند.', 'ns-connection-blocker' ) . '</td></tr>';
            } else {
                foreach ( $all_display_hosts as $host ) {
                    if (empty($host)) continue;
                    $host_id = 'ns_host_' . esc_attr( str_replace( '.', '_', $host ) );
                    $is_blocked = isset( $blocked_hosts_settings[ $host ] ) && $blocked_hosts_settings[ $host ] === 'on';
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo $host_id; ?>"><?php echo esc_html( $host ); ?></label>
                        </th>
                        <td>
                            <label class="ns-switch">
                                <input type="checkbox"
                                       id="<?php echo $host_id; ?>"
                                       name="<?php echo esc_attr( $this->settings_option_name . '[' . $host . ']' ); ?>"
                                       <?php checked( $is_blocked, true ); ?>
                                       />
                                <span class="ns-slider round"></span>
                            </label>
                        </td>
                    </tr>
                    <?php
                }
            }
            echo '</tbody></table>';
        } else {
             echo '<p id="ns_no_connections_message">' . __( 'هیچ اتصال خارجی در طول بررسی شناسایی نشد. اگر افزونه‌هایی دارید که تماس‌های خارجی برقرار می‌کنند، پس از کلیک روی "بررسی اتصالات" باید در اینجا ظاهر شوند.', 'ns-connection-blocker' ) . '</p>';
        }
        $html = ob_get_clean();

        // Clear the transient after displaying, so the next "Check" is fresh,
        // but only if the button was the trigger.
        // However, we need to keep it for a bit so that if the user saves the form, the toggles are still there.
        // So, we won't delete it here. It will expire or be overwritten.
        // delete_transient( $this->logged_requests_option_name );


        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Add settings link to the plugins page.
     *
     * @since 1.0.1
     * @param array $links Array of existing action links.
     * @return array Array of modified action links.
     */
    public function add_settings_link_to_plugins_page( $links ) {
        $settings_link = '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __( 'تنظیمات', 'ns-connection-blocker' ) . '</a>';
        array_unshift( $links, $settings_link ); // Add to the beginning of the links array
        return $links;
    }


    /**
     * Sanitize the settings input.
     *
     * @since 1.0.0
     * @param array $input Contains all settings fields as array keys
     * @return array Sanitized input
     */
    public function sanitize_settings( $input ) {
        $sanitized_input = array();
        if ( isset( $input ) && is_array( $input ) ) {
            foreach ( $input as $host => $value ) {
                // Value should be 'on' or not set. We store 'on' for blocked hosts.
                if ( $value === 'on' ) {
                    $sanitized_input[ sanitize_text_field( $host ) ] = 'on';
                }
            }
        }
        return $sanitized_input;
    }

    /**
     * Callback for the general settings section.
     *
     * @since 1.0.0
     */
    public function general_section_callback() {
        echo '<p>' . __( 'کلید مربوط به هر اتصال را برای مسدود کردن آن فعال کنید.', 'ns-connection-blocker' ) . '</p>';
        echo '<p><button type="button" id="ns_check_connections_button" class="button button-secondary">' . __( 'بررسی اتصالات', 'ns-connection-blocker' ) . '</button></p>';
        echo '<div id="ns_connection_list_container"></div>'; // Container for AJAX loaded connections
    }


    /**
     * Log HTTP requests.
     *
     * @since 1.0.0
     * @param mixed  $response The HTTP response.
     * @param string $context  Context for the hook.
     * @param string $class    Transport class name.
     * @param array  $args     HTTP request arguments.
     * @param string $url      The request URL.
     */
    public function log_http_requests( $response, $context, $class, $args, $url ) {
        // Check if this logging is initiated by our "Check Connections" process
        $is_check_connections_context = false;
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'ns_ping_test' && isset($_REQUEST['_ajax_nonce'])) { // AJAX ping
            // Nonce check for AJAX ping test is handled by wp_ajax_ns_ping_test if we add it
             $is_check_connections_context = true;
        } elseif (isset($_POST['ns_check_connections_button_submit'])) { // Non-AJAX button submission
            $is_check_connections_context = true;
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'ns_get_detected_connections' && isset($_REQUEST['nonce'])) {
            // This is the AJAX call to *get* connections, we might make some calls during this too.
            // Or more accurately, the JS might have just made calls *before* this.
            // We want to make sure the transient is fresh.
             $is_check_connections_context = true;
        }


        // Only log if we are in the "Check Connections" context or if explicitly told to always log (future enhancement)
        if ( ! $is_check_connections_context ) {
            // If not in "check connections" context, don't log.
            // This prevents logging every single HTTP request WordPress makes during normal operation,
            // unless the "Check Connections" button was clicked.
            return;
        }

        $logged_requests = get_transient( $this->logged_requests_option_name );
        if ( false === $logged_requests || !is_array($logged_requests) ) {
            $logged_requests = array();
        }

        $host = parse_url( $url, PHP_URL_HOST );

        // Don't log requests back to the site itself (admin-ajax.php for example)
        $site_host = parse_url( admin_url(), PHP_URL_HOST );
        if ( $host && $host !== $site_host && ! in_array( $host, $logged_requests, true ) ) {
            $logged_requests[] = $host;
        }

        // Always update the transient, even if the list hasn't changed, to refresh its expiry.
        set_transient( $this->logged_requests_option_name, $logged_requests, 15 * MINUTE_IN_SECONDS ); // Store for 15 minutes
    }


    /**
     * Filter HTTP requests to block specified hosts.
     *
     * @since 1.0.0
     * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value. Default false.
     * @param array                $r       HTTP request arguments.
     * @param string               $url     The request URL.
     * @return false|true|WP_Error True to block the request, false or WP_Error to allow.
     */
    public function filter_http_requests( $preempt, $r, $url ) {
        $blocked_hosts = get_option( $this->settings_option_name, array() );
        $host = parse_url( $url, PHP_URL_HOST );

        if ( $host && is_array($blocked_hosts) && array_key_exists( $host, $blocked_hosts ) && $blocked_hosts[ $host ] === 'on' ) {
            // If the host is in our blocked list and the toggle is 'on'
            return true; // Block the request
        }

        return $preempt; // Otherwise, don't interfere
    }

    /**
     * Enqueue admin styles.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ns-connection-blocker-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Enqueue admin scripts.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ns-connection-blocker-admin.js', array( 'jquery' ), $this->version, false );
        wp_localize_script(
            $this->plugin_name,
            'ns_connection_blocker_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'ns_check_connections_nonce' ),
                'checking_message' => __('در حال بررسی اتصالات، لطفاً منتظر بمانید...', 'ns-connection-blocker'),
                'error_message' => __('خطایی رخ داد.', 'ns-connection-blocker'),
            )
        );
    }
}
