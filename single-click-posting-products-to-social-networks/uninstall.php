<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

global $wpdb;

delete_option('scppsn_settings');

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}scppsn_logs");

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_scppsn_posted'");
