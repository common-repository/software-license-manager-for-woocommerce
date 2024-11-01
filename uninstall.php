<?php
/**
 * Uninstall
 *
 * @package Software License Manager for WooCommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$option_name = 'slmforwoocommerce';

/* For Single site */
if ( ! is_multisite() ) {
	delete_option( $option_name );
} else {
	/* For Multisite */
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->prefix}blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blogid ) {
		switch_to_blog( $blogid );
		delete_option( $option_name );
	}
	switch_to_blog( $original_blog_id );

	/* For site options. */
	delete_site_option( $option_name );
}


