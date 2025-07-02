(function( $ ) {
    'use strict';

    $(function() {
        $('#ns_check_connections_button').on('click', function() {
            var $button = $(this);
            var $container = $('#ns_connection_list_dynamic_area'); // Target the specific div for updates
            var $noConnectionsMessage = $('#ns_no_connections_message');

            // Clear previous results specifically within the dynamic area and show loading
            $container.html('<p>' + ns_connection_blocker_ajax.checking_message + '</p>');


            // Add a hidden input to the form to indicate the button was clicked,
            // so that log_http_requests can identify the source.
            // This is more of a fallback if AJAX fails or for non-JS scenarios.
            // For AJAX, we'll use a specific action.
            let form = $(this).closest('form');
            if (!form.find('input[name="ns_check_connections_button_submit"]').length) {
                form.append('<input type="hidden" name="ns_check_connections_button_submit" value="1" />');
            }


            // Make a few sample requests to trigger the http_api_debug hook
            // These requests help populate the list of connections.
            // We are not interested in the response of these, just that they are made.
            $.get(ns_connection_blocker_ajax.ajax_url, { action: 'ns_ping_test', _ajax_nonce: ns_connection_blocker_ajax.nonce, target: 'wordpress.org' });
            $.get(ns_connection_blocker_ajax.ajax_url, { action: 'ns_ping_test', _ajax_nonce: ns_connection_blocker_ajax.nonce, target: 'api.wordpress.org' });


            // After a short delay to allow the test pings to be logged (if they are quick),
            // fetch the updated list of connections.
            setTimeout(function() {
                $.ajax({
                    url: ns_connection_blocker_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ns_get_detected_connections',
                        nonce: ns_connection_blocker_ajax.nonce
                    },
                    beforeSend: function() {
                        $button.prop('disabled', true);
                        // Message already shown
                    },
                    success: function(response) {
                        if (response.success) {
                            $noConnectionsMessage.hide(); // Hide the initial message
                            $container.html(response.data.html);
                        } else {
                            $container.html('<p class="error">' + (response.data.message || ns_connection_blocker_ajax.error_message) + '</p>');
                        }
                    },
                    error: function() {
                        $container.html('<p class="error">' + ns_connection_blocker_ajax.error_message + '</p>');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                         // Remove the hidden input after the process
                        form.find('input[name="ns_check_connections_button_submit"]').remove();
                    }
                });
            }, 1500); // Wait for 1.5 seconds for pings to complete
        });


        // AJAX action for pinging (to be caught by http_api_debug via Ns_Connection_Blocker_Admin::log_http_requests)
        // This is a bit of a trick: we need an AJAX action that the http_api_debug hook can see.
        // The actual 'ns_ping_test' action doesn't need to do much itself in PHP
        // as its purpose is to make an outbound HTTP call that our logger can see.
        // However, the 'log_http_requests' needs to be aware that this is a "check connections" context.
        // We will achieve this by adding a specific parameter to the AJAX call,
        // and checking for it in `log_http_requests`.

        // The actual AJAX handler for `ns_get_detected_connections` will be added in Ns_Connection_Blocker_Admin
    });

})( jQuery );
