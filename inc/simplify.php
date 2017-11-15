<?php
function fobit_remove_post_type( $post_type ) {
	global $wp_post_types;

	if ( isset( $wp_post_types[ $post_type ] ) ) {
		$wp_post_types[ $post_type ]->show_in_menu      = false;
		$wp_post_types[ $post_type ]->show_in_admin_bar = false;
	}
}

add_action( 'admin_init', function() {
	remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
} );

add_action( 'admin_menu', function() {
	remove_menu_page( 'index.php' );
	remove_menu_page( 'upload.php' );
	remove_menu_page( 'edit-comments.php' );
	remove_menu_page( 'tools.php' );

	remove_submenu_page( 'themes.php', 'customize.php' );
	remove_submenu_page( 'themes.php', 'widgets.php' );
	remove_submenu_page( 'themes.php', 'nav-menus.php' );

	remove_submenu_page( 'options-general.php', 'options-writing.php' );
	remove_submenu_page( 'options-general.php', 'options-reading.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	remove_submenu_page( 'options-general.php', 'options-media.php' );
	remove_submenu_page( 'options-general.php', 'options-permalink.php' );
} );

add_action( 'init', function() {
	fobit_remove_post_type( 'post' );
	fobit_remove_post_type( 'page' );
	fobit_remove_post_type( 'attachment' );
} );
