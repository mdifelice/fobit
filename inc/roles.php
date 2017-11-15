<?php
add_action( 'init', function() {
	add_role(
		'investor',
		__( 'Investor', 'fobit' ),
		array(
			'read' => true,
		)
	);
} );

add_filter( 'editable_roles', function( $roles ) {
	$fobit_roles = array();

	$allowed_roles = array(
		'administrator',
		'investor',
	);

	foreach ( $roles as $id => $role ) {
		if ( in_array( $id, $allowed_roles ) ) {
			$fobit_roles[ $id ] = $role;
		}
	}

	krsort( $fobit_roles );

	return $fobit_roles;
} );
