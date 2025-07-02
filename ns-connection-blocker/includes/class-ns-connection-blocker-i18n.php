<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://nias.ir
 * @since      1.0.0
 *
 * @package    Ns_Connection_Blocker
 * @subpackage Ns_Connection_Blocker/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Ns_Connection_Blocker
 * @subpackage Ns_Connection_Blocker/includes
 * @author     Nias.ir Alireza Aliniya <info@nias.ir>
 */
class Ns_Connection_Blocker_i18n {


    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain(
            'ns-connection-blocker',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );

    }



}
