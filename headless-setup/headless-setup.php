<?php
/**
 * Plugin Name: Headless Setup
 * Description: Forces headless mode. Disable XML-RPC. REST API and GraphQL authentication. All by default. Can be configure in Settings > Headless Setup.
 * Version: 1.0.0
 * Plugin URI: https://github.com/zdeneksvarc/wordpress-plugin-headless-setup
 */

// Set default options when plugin is activated
register_activation_hook( __FILE__, function() {
    add_option( 'headless_setup_options', [
        'headless_mode'   => 1,
        'xmlrpc_disable'  => 1,
        'rest_protect'    => 1,
        'graphql_protect' => 1,
    ] );
});

// Redirect user to settings page after activation
add_action( 'activated_plugin', function( $plugin ) {
    if ( $plugin === plugin_basename( __FILE__ ) ) {
        wp_safe_redirect( admin_url( 'options-general.php?page=headless-setup' ) );
        exit;
    }
});

// Register settings UI in Settings → Headless Setup
add_action( 'admin_menu', function() {
    add_options_page( 'Headless Setup', 'Headless Setup', 'manage_options', 'headless-setup', function() {
        ?>
        <div class="wrap">
            <h1>Headless Setup</h1>
            <form method="post" action="options.php" id="headless-setup-form">
                <?php
                settings_fields( 'headless_setup_settings' );
                do_settings_sections( 'headless-setup' );
                submit_button();
                ?>
            </form>
            <style>
                #headless-setup-form table.form-table th {
                    width: auto;
                    padding-right: 10px;
                }
                #headless-setup-form table.form-table td {
                    padding: 0 0 10px 0;
                }
                #headless-setup-form input[type="checkbox"] {
                    margin-right: 6px;
                }
                #headless-setup-form label {
                    font-weight: normal;
                }
            </style>
        </div>
        <?php
    });
});

// Define fields and register settings
add_action( 'admin_init', function() {
    register_setting( 'headless_setup_settings', 'headless_setup_options' );

    add_settings_section( 'headless_setup_section', '', null, 'headless-setup' );

    $fields = [
        'headless_mode'   => 'Enable Headless Mode',
        'xmlrpc_disable'  => 'Disable XML-RPC',
        'rest_protect'    => 'Require Auth for REST API',
        'graphql_protect' => 'Require Auth for GraphQL',
    ];

    foreach ( $fields as $key => $label ) {
        add_settings_field( $key, '', function() use ( $key, $label ) {
            $options = get_option( 'headless_setup_options' );
            echo '<label><input type="checkbox" name="headless_setup_options[' . esc_attr( $key ) . ']" value="1"' . checked( 1, $options[ $key ] ?? 0, false ) . ' /> ' . esc_html( $label ) . '</label>';
        }, 'headless-setup', 'headless_setup_section' );
    }
});

// Block frontend templates
add_action( 'template_redirect', function () {
    $options = get_option( 'headless_setup_options' );
    if ( empty( $options['headless_mode'] ) ) return;

    if ( apply_filters( 'headless_template_block_bypass', false ) ) return;

    if (
        is_admin() ||
        defined( 'DOING_CRON' ) ||
        defined( 'REST_REQUEST' ) ||
        defined( 'GRAPHQL_HTTP_REQUEST' )
    ) return;

    if (
        defined( 'REST_REQUEST' ) ||
        defined( 'GRAPHQL_HTTP_REQUEST' )
    ) {
        wp_send_json_error( [ 'message' => 'This site is headless. Please use the API.' ], 403 );
    }

    if ( strpos( $_SERVER['HTTP_ACCEPT'] ?? '', 'application/json' ) !== false ) {
        wp_send_json_error( [ 'message' => 'This site is headless. Please use the API.' ], 403 );
    } else {
        header( 'Content-Type: text/plain; charset=utf-8', true, 403 );
        echo "This site is headless. Please use the API.";
        exit;
    }
});

// Require authentication for REST API
add_filter( 'rest_authentication_errors', function( $result ) {
    $options = get_option( 'headless_setup_options' );
    if ( ! empty( $options['rest_protect'] ) && ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_cannot_access',
            __( 'Only authenticated users can access the REST API.', 'headless-setup' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }
    return $result;
});

// Require authentication for GraphQL
add_action( 'parse_request', function( $wp ) {
    $options = get_option( 'headless_setup_options' );

    if (
        ! empty( $options['graphql_protect'] )
        && isset( $_SERVER['REQUEST_URI'] )
        && preg_match( '#/graphql/?$#', $_SERVER['REQUEST_URI'] )
        && ! is_user_logged_in()
    ) {
        wp_send_json( [
            'errors' => [
                [
                    'message' => 'Only authenticated users can access the GraphQL endpoint.',
                    'extensions' => [ 'category' => 'authentication' ]
                ]
            ]
        ], 401 );
        exit;
    }
});

// Disable XML-RPC
add_action( 'plugins_loaded', function() {
    $options = get_option( 'headless_setup_options' );

    if (
        ! empty( $options['xmlrpc_disable'] )
        && defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST
    ) {
        header( 'HTTP/1.1 403 Forbidden' );
        header( 'Content-Type: text/plain; charset=utf-8' );
        echo 'XML-RPC is disabled on this site.';
        exit;
    }
});