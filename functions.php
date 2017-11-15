<?php
require_once __DIR__ . '/inc/roles.php';
require_once __DIR__ . '/inc/simplify.php';

function fobit_user_profile( $user ) {
	wp_nonce_field( 'fobit_user_profile_update', 'fobit_user_profile_update_nonce' );

	$investor_types = fobit_get_investor_types();
	?>
<table class="form-table">
	<tr>
		<th>
			<label for="fobit_investor_type"><?php esc_html_e( 'Investor Type', 'fobit' ); ?></label>
		</th>
		<td>
			<select name="fobit_investor_type" id="fobit_investor_type">
				<?php foreach ( $investor_types as $investor_type ) { ?>
				<option value="<?php echo esc_attr( $investor_type['code'] ); ?>"<?php selected( $investor_type['code'], get_the_author_meta( 'fobit_investor_type', $user->ID ) ); ?>><?php echo esc_html( $investor_type['description'] ); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
</table>
	<?php
}

function fobit_user_profile_update( $user_id ) {
	if ( ! isset( $_POST['fobit_user_profile_update_nonce'] ) ) {
		return false;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['fobit_user_profile_update_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'fobit_user_profile_update' ) ) {
		return false;
	}

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	if ( isset( $_POST['fobit_investor_type'] ) ) {
		$investor_types = fobit_get_investor_types();
		$investor_type  = sanitize_text_field( wp_unslash( $_POST['fobit_user_profile_update_nonce'] ) );

		if ( in_array( $investor_type, $investor_types ) ) {
			update_user_meta( $user_id, 'fobit_investor_type', $investor_type );
		}
	}
}

function fobit_get_investor_types() {
	return get_option( 'fobit_investor_types', array() );
}

add_action( 'admin_menu', function() {
	add_options_page(
		__( 'Fobit Settings', 'fobit' ),
		__( 'Fobit Settings', 'fobit' ),
		'manage_options',
		'fobit_settings',
		function() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
			}

			?>
<div class="wrap">
	<h2><?php esc_html_e( 'Fobit Settings', 'fobit' ); ?></h2>
	<form action="options.php" method="post">
		<?php settings_fields( 'fobit_settings' ); ?>
		<?php do_settings_sections( 'fobit_settings' ); ?>
		<?php submit_button(); ?>
	</form>
</div>
			<?php
		}
	);
} );

add_action( 'admin_init', function() {
	add_settings_section(
		'fobit_investor_types',
		null,
		null,
		'fobit_settings'
	);

	add_settings_field(
		'fobit_investor_types',
		__( 'Investor Types', 'fobit' ),
		function() {
			printf(
				'<a id="fobit_investor_types_add" href="#" class="button-secondary">%s</a><div id="fobit_investor_types"></div>',
				esc_html__( 'Add Investor Type', 'fobit' )
			);
		},
		'fobit_settings',
		'fobit_investor_types'
	);

	register_setting(
		'fobit_settings',
		'fobit_investor_types',
		function( $values ) {
			$investor_types = array();

			if ( is_array( $values ) ) {
				foreach ( $values as $value ) {
					$code               = isset( $value['code'] ) ? sanitize_text_field( $value['code'] ) : null;
					$description        = isset( $value['description'] ) ? sanitize_text_field( $value['description'] ) : null;
					$entrance_commision = isset( $value['entrance_commision'] ) ? abs( floatval( $value['entrance_commision'] ) ) : 0;
					$profit_commision   = isset( $value['profit_commision'] ) ? abs( floatval( $value['profit_commision'] ) ) : 0;

					if ( $code ) {
						$investor_types[] = array(
							'code'               => $code,
							'description'        => $description,
							'entrance_commision' => $entrance_commision,
							'profit_commision'   => $profit_commision,
						);
					}
				}
			}

			return $investor_types;
		}
	);
} );

add_action( 'admin_enqueue_scripts', function( $hook ) {
	wp_register_script(
		'fobit-settings',
		get_template_directory_uri() . '/js/settings.js'
	);

	wp_register_style(
		'fobit-settings',
		get_template_directory_uri() . '/css/settings.css'
	);

	if ( 'settings_page_fobit' === $hook ) {
		wp_enqueue_style( 'fobit-settings' );

		wp_enqueue_script( 'fobit-settings' );

		wp_localize_script(
			'fobit-settings',
			'fobit_settings',
			array(
				'i18n'           => array(
					'investor_type_code'               => __( 'Code', 'fobit' ),
					'investor_type_description'        => __( 'Description', 'fobit' ),
					'investor_type_entrance_commision' => __( 'Entrance Commision ', 'fobit' ),
					'investor_type_profit_commision'   => __( 'Profit Commision ', 'fobit' ),
					'investor_type_remove'             => __( 'Remove', 'fobit' ),
				),
				'investor_types' => fobit_get_investor_types(),
			)
		);
	}
} );

add_action( 'after_setup_theme', function() {
	load_theme_textdomain( 'fobit', get_template_directory() . '/languages' );
} );

add_action( 'show_user_profile', 'fobit_user_profile' );
add_action( 'edit_user_profile', 'fobit_user_profile' );

add_action( 'personal_options_update', 'fobit_user_profile_update' );
add_action( 'edit_user_profile_update', 'fobit_user_profile_update' );

add_action( 'init', function() {
	register_post_type(
		'fobit_cashflow',
		array(
			'label'  => __( 'Cashflow', 'fobit' ),
			'labels' => array(
			),
			'public'      => false,
			'show_ui'     => true,
			'menu_icon'   => 'dashicons-upload',
			'supports'    => array( 'author' ),
		)
	);
} );

add_action( 'wp', function() {
	global $pagenow;

	if ( ! is_user_logged_in() && 'wp-login.php' !== $pagenow ) {
		auth_redirect();
	}
} );
