<?php

/**
 * Admin Bookings View
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Get filter parameters
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- FILTER PARAMETERS
$status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- FILTER PARAMETERS
$room_filter = isset($_GET['room_id']) ? absint(wp_unslash($_GET['room_id'])) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- FILTER PARAMETERS
$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- FILTER PARAMETERS
$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

// Build query args
$args = array(
    'status' => $status_filter,
    'room_id' => $room_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
);

$bookings = ABS_Database::get_bookings($args);

// Get all rooms for filter
$rooms = ABS_Database::get_rooms();

// Calculate statistics
// Calculate statistics
$stats = ABS_Database::get_booking_stats();
$total_bookings = $stats->total;
$pending_bookings = $stats->pending;
$confirmed_bookings = $stats->confirmed;
$cancelled_bookings = $stats->cancelled;
?>

<div class="wrap abs-admin-wrap">
    <div class="abs-admin-header">
        <h1><?php esc_html_e('Bookings Management', 'advanced-hotel-room-booking-system'); ?></h1>
        <div class="abs-admin-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=abs-rooms')); ?>" class="button">
                <?php esc_html_e('Manage Rooms', 'advanced-hotel-room-booking-system'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=abs-settings')); ?>" class="button">
                <?php esc_html_e('Settings', 'advanced-hotel-room-booking-system'); ?>
            </a>
        </div>
    </div>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filter parameters do not require nonce verification
    if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'advanced-hotel-room-booking-system'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="abs-stats-grid">
        <div class="abs-stat-card">
            <h3><?php esc_html_e('Total Bookings', 'advanced-hotel-room-booking-system'); ?></h3>
            <div class="abs-stat-value"><?php echo esc_html($total_bookings); ?></div>
        </div>
        <div class="abs-stat-card">
            <h3><?php esc_html_e('Pending', 'advanced-hotel-room-booking-system'); ?></h3>
            <div class="abs-stat-value" style="color: #856404;"><?php echo esc_html($pending_bookings); ?></div>
        </div>
        <div class="abs-stat-card">
            <h3><?php esc_html_e('Confirmed', 'advanced-hotel-room-booking-system'); ?></h3>
            <div class="abs-stat-value" style="color: #155724;"><?php echo esc_html($confirmed_bookings); ?></div>
        </div>
        <div class="abs-stat-card">
            <h3><?php esc_html_e('Cancelled', 'advanced-hotel-room-booking-system'); ?></h3>
            <div class="abs-stat-value" style="color: #721c24;"><?php echo esc_html($cancelled_bookings); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="abs-settings-section">
        <h2><?php esc_html_e('Filter Bookings', 'advanced-hotel-room-booking-system'); ?></h2>
        <div class="abs-section-content">
            <form method="get" action="">
                <input type="hidden" name="page" value="abs-bookings">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label><?php esc_html_e('Status', 'advanced-hotel-room-booking-system'); ?></label>
                        <select name="status" id="abs-status-filter">
                            <option value=""><?php esc_html_e('All Statuses', 'advanced-hotel-room-booking-system'); ?></option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php esc_html_e('Pending', 'advanced-hotel-room-booking-system'); ?></option>
                            <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php esc_html_e('Confirmed', 'advanced-hotel-room-booking-system'); ?></option>
                            <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'advanced-hotel-room-booking-system'); ?></option>
                        </select>
                    </div>

                    <div>
                        <label><?php esc_html_e('Room', 'advanced-hotel-room-booking-system'); ?></label>
                        <select name="room_id" id="abs-room-filter">
                            <option value=""><?php esc_html_e('All Rooms', 'advanced-hotel-room-booking-system'); ?></option>
                            <?php foreach ($rooms as $room) : ?>
                                <option value="<?php echo esc_attr($room->id); ?>" <?php selected($room_filter, $room->id); ?>>
                                    <?php echo esc_html($room->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label><?php esc_html_e('Date From', 'advanced-hotel-room-booking-system'); ?></label>
                        <input type="date" name="date_from" id="abs-date-from" value="<?php echo esc_attr($date_from); ?>">
                    </div>

                    <div>
                        <label><?php esc_html_e('Date To', 'advanced-hotel-room-booking-system'); ?></label>
                        <input type="date" name="date_to" id="abs-date-to" value="<?php echo esc_attr($date_to); ?>">
                    </div>
                </div>

                <button type="submit" class="button button-primary" id="abs-apply-date-filter">
                    <?php esc_html_e('Apply Filters', 'advanced-hotel-room-booking-system'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=abs-bookings')); ?>" class="button">
                    <?php esc_html_e('Clear Filters', 'advanced-hotel-room-booking-system'); ?>
                </a>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="abs-settings-section">
        <h2><?php esc_html_e('All Bookings', 'advanced-hotel-room-booking-system'); ?></h2>
        <div class="abs-section-content">
            <?php if (empty($bookings)) : ?>
                <p><?php esc_html_e('No bookings found.', 'advanced-hotel-room-booking-system'); ?></p>
            <?php else : ?>
                <!-- Bulk Actions -->
                <div style="margin-bottom: 15px;">
                    <select id="abs-bulk-action">
                        <option value=""><?php esc_html_e('Bulk Actions', 'advanced-hotel-room-booking-system'); ?></option>
                        <option value="confirm"><?php esc_html_e('Confirm', 'advanced-hotel-room-booking-system'); ?></option>
                        <option value="deny"><?php esc_html_e('Deny', 'advanced-hotel-room-booking-system'); ?></option>
                        <option value="delete"><?php esc_html_e('Delete', 'advanced-hotel-room-booking-system'); ?></option>
                    </select>
                </div>

                <table class="abs-bookings-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="abs-select-all">
                            </th>
                            <th><?php esc_html_e('ID', 'advanced-hotel-room-booking-system'); ?></th>
                            <th><?php esc_html_e('Customer', 'advanced-hotel-room-booking-system'); ?></th>
                            <th><?php esc_html_e('Contact', 'advanced-hotel-room-booking-system'); ?></th>
                            <th><?php esc_html_e('Room', 'advanced-hotel-room-booking-system'); ?></th>
                            <th><?php esc_html_e('Date', 'advanced-hotel-room-booking-system'); ?></th>
                            <th><?php esc_html_e('Status', 'advanced-hotel-room-booking-system'); ?></th>
                            <th><?php esc_html_e('Created', 'advanced-hotel-room-booking-system'); ?></th>
                            <th><?php esc_html_e('Actions', 'advanced-hotel-room-booking-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking) :
                            $room = ABS_Database::get_room($booking->room_id);
                            $user = get_userdata($booking->user_id);
                        ?>
                            <tr id="booking-row-<?php echo esc_attr($booking->id); ?>">
                                <td>
                                    <input type="checkbox" class="abs-booking-checkbox" value="<?php echo esc_attr($booking->id); ?>">
                                </td>
                                <td><strong>#<?php echo esc_html($booking->id); ?></strong></td>
                                <td>
                                    <?php echo esc_html($booking->first_name . ' ' . $booking->last_name); ?>
                                    <?php if ($user) : ?>
                                        <br><small><?php echo esc_html($user->user_login); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($booking->email); ?><br>
                                    <small><?php echo esc_html($booking->phone); ?></small>
                                </td>
                                <td><?php echo esc_html($room ? $room->name : __('Unknown', 'advanced-hotel-room-booking-system')); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($booking->booking_date))); ?></td>
                                <td>
                                    <span class="abs-status-badge <?php echo esc_attr($booking->status); ?>">
                                        <?php echo esc_html(ucfirst($booking->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($booking->created_at))); ?></td>
                                <td>
                                    <div class="abs-actions">
                                        <?php if ('pending' === $booking->status) : ?>
                                            <button class="abs-action-btn abs-confirm-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>" title="<?php esc_attr_e('Confirm Booking', 'advanced-hotel-room-booking-system'); ?>">
                                                ✓
                                            </button>
                                            <button class="abs-action-btn abs-deny-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>" title="<?php esc_attr_e('Deny Booking', 'advanced-hotel-room-booking-system'); ?>">
                                                ✕
                                            </button>
                                        <?php endif; ?>
                                        <button class="abs-action-btn delete abs-delete-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>" title="<?php esc_attr_e('Delete Booking', 'advanced-hotel-room-booking-system'); ?>">
                                            🗑
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php if (! empty($booking->notes)) : ?>
                                <tr>
                                    <td colspan="9" style="background-color: #f9f9f9; padding: 10px;">
                                        <strong><?php esc_html_e('Notes:', 'advanced-hotel-room-booking-system'); ?></strong>
                                        <?php echo esc_html($booking->notes); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .abs-bookings-table {
        width: 100%;
        background: #fff;
    }

    .abs-bookings-table select {
        padding: 5px;
        border: 1px solid #ddd;
    }
</style>