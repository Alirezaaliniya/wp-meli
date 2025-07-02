<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://nias.ir
 * @since      1.0.0
 *
 * @package    Ns_Connection_Blocker
 * @subpackage Ns_Connection_Blocker/admin/partials
 */

// Security check to prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$plugin_name_slug = 'ns-connection-blocker'; // Make sure this matches the menu slug in Ns_Connection_Blocker_Admin
$settings_option_name = $plugin_name_slug . '_blocked_hosts';
$logged_requests_option_name = $plugin_name_slug . '_logged_requests_transient'; // Ensure consistency

?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields( $plugin_name_slug ); // Option group, should match register_setting
        // do_settings_sections( $this->plugin_name ); // Page slug, should match add_options_page
        // Note: The above line might cause issues if $this is not in the correct scope.
        // We will manually render the section for more control, or ensure $this->plugin_name is available.
        // For simplicity, let's assume $plugin_name_slug is the correct slug.
        do_settings_sections( $plugin_name_slug );
        ?>
        <hr/>
        <div id="ns_connection_list_dynamic_area">
            <?php
            // This area will be populated by AJAX or if the 'Check Connections' button was pressed without AJAX.
            // We can also pre-populate it if there are existing logged connections or settings.

            $active_connections = get_transient( $logged_requests_option_name );
            $blocked_hosts_settings = get_option( $settings_option_name, array() );

            if ( ! empty( $active_connections ) || ! empty( $blocked_hosts_settings ) ) {
                echo '<h2>' . __( 'اتصالات شناسایی شده و پیکربندی شده', 'ns-connection-blocker' ) . '</h2>';
                echo '<p>' . __( 'برای مسدود کردن یک اتصال، کلید مربوطه را فعال کرده و سپس روی "ذخیره تغییرات" کلیک کنید.', 'ns-connection-blocker' ) . '</p>';
                echo '<table class="form-table ns-connections-table"><tbody>';

                // Merge active connections and saved settings to display all relevant hosts
                $all_display_hosts = array();
                if(is_array($active_connections)) $all_display_hosts = array_merge($all_display_hosts, $active_connections);
                if(is_array($blocked_hosts_settings)) $all_display_hosts = array_merge($all_display_hosts, array_keys($blocked_hosts_settings));
                $all_display_hosts = array_unique(array_filter($all_display_hosts));
                sort($all_display_hosts);


                if ( empty( $all_display_hosts ) ) {
                     echo '<tr><td colspan="2">' . __( 'هنوز هیچ اتصالی شناسایی یا پیکربندی نشده است. برای اسکن درخواست‌های خروجی، روی "بررسی اتصالات" کلیک کنید.', 'ns-connection-blocker' ) . '</td></tr>';
                } else {
                    foreach ( $all_display_hosts as $host ) {
                        if (empty($host)) continue; // Skip empty host entries
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
                                           name="<?php echo esc_attr( $settings_option_name . '[' . $host . ']' ); ?>"
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
                 echo '<p id="ns_no_connections_message">' . __( 'برای اسکن درخواست‌های خروجی، روی "بررسی اتصالات" کلیک کنید. نتایج در اینجا ظاهر خواهند شد.', 'ns-connection-blocker' ) . '</p>';
            }
            ?>
        </div>
        <?php submit_button( __( 'ذخیره تغییرات', 'ns-connection-blocker' ) ); ?>
    </form>
    <p>
        <em><?php _e( '<strong>توجه:</strong> دکمه "بررسی اتصالات" مجموعه‌ای از درخواست‌های آزمایشی را به سرویس‌های خارجی رایج (مانند wordpress.org) ارسال می‌کند تا به شناسایی اتصالات خروجی بالقوه از سایت شما کمک کند. اتصالات قبلاً ثبت شده برای مدت کوتاهی ذخیره می‌شوند.', 'ns-connection-blocker' ); ?></em>
    </p>
</div>

<style>
    .ns-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }
    .ns-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .ns-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }
    .ns-slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }
    input:checked + .ns-slider {
        background-color: #2196F3;
    }
    input:focus + .ns-slider {
        box-shadow: 0 0 1px #2196F3;
    }
    input:checked + .ns-slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }
    .ns-slider.round {
        border-radius: 34px;
    }
    .ns-slider.round:before {
        border-radius: 50%;
    }
    .ns-connections-table th, .ns-connections-table td {
        padding-top: 10px;
        padding-bottom: 10px;
    }
</style>
