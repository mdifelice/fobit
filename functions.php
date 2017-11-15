<?php
require_once __DIR__ . '/inc/roles.php';
require_once __DIR__ . '/inc/simplify.php';

function fobit_get_movements( $user_id, $type = 'any' ) {
	$parameters = array(
		'post_type'      => 'fobit_cashflow',
		'posts_per_page' => -1,
		'author'         => $user_id,
		'order'          => 'asc',
	);

	if ( 'any' !== $type ) {
		$parameters['meta_key']   = 'fobit_cashflow_type';
		$parameters['meta_value'] = $type;
	}

	$query = new WP_Query( $parameters );

	return $query->posts;
}

function fobit_get_movements_total( $user_id, $type = 'any' ) {
	$movements = fobit_get_movements( $user_id, $type );
	$total     = 0;

	foreach ( $movements as $movement ) {
		$type   = get_post_meta( $movement->ID, 'fobit_cashflow_type', true );
		$amount = get_post_meta( $movement->ID, 'fobit_cashflow_amount', true );

		if ( 'deposit' === $type ) {
			$total += $amount;
		} else {
			$total -= $amount;
		}
	}

	return $total;
}

function fobit_get_cashflow_types() {
	return array(
		'deposit'    => __( 'Deposit', 'fobit' ),
		'withdrawal' => __( 'Withdrawal', 'fobit' ),
	);
}

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
	try {
		if ( ! isset( $_POST['fobit_user_profile_update_nonce'] ) ) {
			throw new Exception();
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['fobit_user_profile_update_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'fobit_user_profile_update' ) ) {
			throw new Exception();
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			throw new Exception();
		}

		if ( isset( $_POST['fobit_investor_type'] ) ) {
			$investor_types = fobit_get_investor_types();
			$investor_type  = sanitize_text_field( wp_unslash( $_POST['fobit_investor_type'] ) );

			foreach ( $investor_types as $possible_investor_type ) {
				if ( $possible_investor_type['code'] === $investor_type ) {
					update_user_meta( $user_id, 'fobit_investor_type', $investor_type );
					
					break;
				}
			}
		}
	} catch( Exception $e ) {
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

	if ( 'settings_page_fobit_settings' === $hook ) {
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
			'label'                => __( 'Cashflow', 'fobit' ),
			'labels'               => array(
				'name'               => __( 'Cashflow', 'fobit' ),
				'singular_name'      => __( 'Cashflow', 'fobit' ),
				'add_new'            => __( 'Add new movement', 'fobit' ),
				'add_new_item'       => __( 'Add New Movement', 'fobit' ),
				'edit_item'          => __( 'Edit Movement', 'fobit' ),
				'view_item'          => __( 'View Movement', 'fobit' ),
				'view_items'         => __( 'View Movements', 'fobit' ),
				'search_items'       => __( 'Search Movements', 'fobit' ),
				'not_found'          => __( 'No movements found', 'fobit' ),
				'not_found_in_trash' => __( 'No movements found in trash', 'fobit' ),
				'all_items'          => __( 'All movements', 'fobit' ),
			),
			'public'               => false,
			'show_ui'              => true,
			'menu_icon'            => 'dashicons-upload',
			'register_meta_box_cb' => function() {
				add_meta_box(
					'fobit_cashflow',
					__( 'Movement Information', 'fobit' ),
					function( $post ) {
						wp_nonce_field( 'fobit_cashflow', 'fobit_cashflow_nonce' );

						$types          = fobit_get_cashflow_types();
						?>
<p>
	<label for="fobit_cashflow_type"><?php esc_html_e( 'Type', 'fobit' ); ?></label>
	<select name="fobit_cashflow_type" id="fobit_cashflow_type" class="widefat">
		<?php foreach ( $types as $type => $caption ) { ?>
		<option value="<?php echo esc_attr( $type ); ?>"<?php selected( $type, get_post_meta( $post->ID, 'fobit_cashflow_type', true ) ); ?>><?php echo esc_html( $caption ); ?></option>
		<?php } ?>
	</select>
</p>
<p>
	<label for="fobit_cashflow_amount"><?php esc_html_e( 'Amount', 'fobit' ); ?></label>
	<input id="fobit_cashflow_amount" name="fobit_cashflow_amount" type="number" step="0.01" min="0" value="<?php echo esc_attr( get_post_meta( $post->ID, 'fobit_cashflow_amount', true ) ); ?>" class="widefat" />
</p>
						<?php
					}, 
					'fobit_cashflow',
					'normal'
				);
			},
			'supports'             => array( 'author' ),
		)
	);
} );

add_action( 'wp', function() {
	global $pagenow;

	if ( ! is_user_logged_in() && 'wp-login.php' !== $pagenow ) {
		auth_redirect();
	}
} );

add_filter( 'gettext', function( $translated, $original, $domain ) {
	switch ( $original ) {
		case 'Author':
			$translated = __( 'User', 'fobit' );

			break;
	}

	return $translated;
}, 10, 3 );

add_filter( 'wp_insert_post_data', function( $data, $postarr ) {
	try {
		if ( ! isset( $_POST['fobit_cashflow_nonce'] ) ) {
			throw new Exception();
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['fobit_cashflow_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'fobit_cashflow' ) ) {
			throw new Exception();
		}

		$post_id = $postarr['ID'];

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			throw new Exception();
		}

		$amount = 0;
		$type   = null;

		if ( isset( $_POST['fobit_cashflow_type'] ) ) {
			$types = fobit_get_cashflow_types();

			$possible_type = sanitize_text_field( wp_unslash( $_POST['fobit_cashflow_type'] ) );

			if ( isset( $types[ $possible_type ] ) ) {
				$type = $possible_type;
			}
		}

		if ( isset( $_POST['fobit_cashflow_amount'] ) ) {
			$amount = abs( floatval( wp_unslash( $_POST['fobit_cashflow_amount'] ) ) );
		}

		update_post_meta( $post_id, 'fobit_cashflow_type', $type );
		update_post_meta( $post_id, 'fobit_cashflow_amount', $amount );

		$data['post_title'] = sprintf( __( '$%1s %2s', 'fobit' ), number_format( $amount ), $type ); 
	} catch( Exception $e ) {
	}

	return $data;
}, 10, 2 );

add_filter( 'manage_users_columns', function( $columns ) {
	$columns['fobit_investor_type']  = __( 'Investor Type', 'fobit' );
	$columns['fobit_first_movement'] = __( 'First Movement', 'fobit' );
	$columns['fobit_deposits']       = __( 'Deposits', 'fobit' );
	$columns['fobit_withdrawals']    = __( 'Withdrawals', 'fobit' );

	unset( $columns['posts'] );

	return $columns;
} );

add_filter( 'manage_users_custom_column', function( $value, $column_name, $user_id ) {
	$investor_types = fobit_get_investor_types();

	switch ( $column_name ) {
		case 'fobit_investor_type':
			$investor_type = get_the_author_meta( 'fobit_investor_type', $user_id );

			foreach ( $investor_types as $possible_investor_type ) {
				$code = $possible_investor_type['code'];

				if ( $code === $investor_type ) {
					$value = $possible_investor_type['description'];

					break;
				}
			}

			break;
		case 'fobit_first_movement':
			$movements = fobit_get_movements( $user_id );

			if ( ! empty( $movements ) ) {
				$date = $movements[0]->post_date;

				$value = date( get_option( 'date_format' ), strtotime( $date ) ); 
			} else {
				$value = __( 'This user has not registered moments yet.', 'fobit' );
			}
			break;
		case 'fobit_deposits':
			$total = fobit_get_movements_total( $user_id, 'deposit' );

			$value = sprintf( '$%s', number_format( $total ) );
			break;
		case 'fobit_withdrawals':
			$total = fobit_get_movements_total( $user_id, 'withdrawal' );

			$value = sprintf( '$%s', number_format( $total * -1 ) );
			break;
	}

	return $value;
}, 10, 3 );
