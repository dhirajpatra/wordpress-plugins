<?php

/**
 * Admin Bookings View
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Get filter parameters
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- FILTER PARAMETERS
$irbsfh_status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- FILTER PARAMETERS
$irbsfh_room_filter = isset($_GET['room_id']) ? absint(wp_unslash($_GET['room_id'])) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- FILTER PARAMETERS
$irbsfh_date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- FILTER PARAMETERS
$irbsfh_date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

// Build query args
$irbsfh_args = array(
    'status' => $irbsfh_status_filter,
    'room_id' => $irbsfh_room_filter,
    'date_from' => $irbsfh_date_from,
    'date_to' => $irbsfh_date_to,
);

$irbsfh_bookings = IRBSFH_Database::get_bookings($irbsfh_args);

// Get all rooms for filter
$irbsfh_rooms = IRBSFH_Database::get_rooms();

// Calculate statistics
// Calculate statistics
$irbsfh_stats = IRBSFH_Database::get_booking_stats();
$irbsfh_total_bookings = $irbsfh_stats->total;
$irbsfh_pending_bookings = $irbsfh_stats->pending;
$irbsfh_confirmed_bookings = $irbsfh_stats->confirmed;
$irbsfh_cancelled_bookings = $irbsfh_stats->cancelled;
?>

<div class="wrap irbsfh-admin-wrap">
    <div class="irbsfh-admin-header">
        <h1><?php esc_html_e('Bookings Management', 'intelligent-room-booking-system-for-hotel'); ?></h1>
        <div class="irbsfh-admin-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=irbsfh-rooms')); ?>" class="button">
                <?php esc_html_e('Manage Rooms', 'intelligent-room-booking-system-for-hotel'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=irbsfh-settings')); ?>" class="button">
                <?php esc_html_e('Settings', 'intelligent-room-booking-system-for-hotel'); ?>
            </a>
        </div>
    </div>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filter parameters do not require nonce verification
    if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'intelligent-room-booking-system-for-hotel'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <!-- Statistics -->
    <div class="irbsfh-stats-grid">
        <div class="irbsfh-stat-card">
            <h3><?php esc_html_e('Total Bookings', 'intelligent-room-booking-system-for-hotel'); ?></h3>
            <div class="irbsfh-stat-value"><?php echo esc_html($irbsfh_total_bookings); ?></div>
        </div>
        <div class="irbsfh-stat-card">
            <h3><?php esc_html_e('Pending', 'intelligent-room-booking-system-for-hotel'); ?></h3>
            <div class="irbsfh-stat-value" style="color: #856404;"><?php echo esc_html($irbsfh_pending_bookings); ?></div>
        </div>
        <div class="irbsfh-stat-card">
            <h3><?php esc_html_e('Confirmed', 'intelligent-room-booking-system-for-hotel'); ?></h3>
            <div class="irbsfh-stat-value" style="color: #155724;"><?php echo esc_html($irbsfh_confirmed_bookings); ?></div>
        </div>
        <div class="irbsfh-stat-card">
            <h3><?php esc_html_e('Cancelled', 'intelligent-room-booking-system-for-hotel'); ?></h3>
            <div class="irbsfh-stat-value" style="color: #721c24;"><?php echo esc_html($irbsfh_cancelled_bookings); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="irbsfh-settings-section">
        <h2><?php esc_html_e('Filter Bookings', 'intelligent-room-booking-system-for-hotel'); ?></h2>
        <div class="irbsfh-section-content">
            <form method="get" action="">
                <input type="hidden" name="page" value="irbsfh-bookings">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label><?php esc_html_e('Status', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <select name="status" id="irbsfh-status-filter">
                            <option value=""><?php esc_html_e('All Statuses', 'intelligent-room-booking-system-for-hotel'); ?></option>
                            <option value="pending" <?php selected($irbsfh_status_filter, 'pending'); ?>><?php esc_html_e('Pending', 'intelligent-room-booking-system-for-hotel'); ?></option>
                            <option value="confirmed" <?php selected($irbsfh_status_filter, 'confirmed'); ?>><?php esc_html_e('Confirmed', 'intelligent-room-booking-system-for-hotel'); ?></option>
                            <option value="cancelled" <?php selected($irbsfh_status_filter, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'intelligent-room-booking-system-for-hotel'); ?></option>
                        </select>
                    </div>

                    <div>
                        <label><?php esc_html_e('Room', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <select name="room_id" id="irbsfh-room-filter">
                            <option value=""><?php esc_html_e('All Rooms', 'intelligent-room-booking-system-for-hotel'); ?></option>
                            <?php foreach ($irbsfh_rooms as $irbsfh_room) : ?>
                                <option value="<?php echo esc_attr($irbsfh_room->id); ?>" <?php selected($irbsfh_room_filter, $irbsfh_room->id); ?>>
                                    <?php echo esc_html($irbsfh_room->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label><?php esc_html_e('Date From', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <input type="date" name="date_from" id="irbsfh-date-from" value="<?php echo esc_attr($irbsfh_date_from); ?>">
                    </div>

                    <div>
                        <label><?php esc_html_e('Date To', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <input type="date" name="date_to" id="irbsfh-date-to" value="<?php echo esc_attr($irbsfh_date_to); ?>">
                    </div>
                </div>

                <button type="submit" class="button button-primary" id="irbsfh-apply-date-filter">
                    <?php esc_html_e('Apply Filters', 'intelligent-room-booking-system-for-hotel'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=irbsfh-bookings')); ?>" class="button">
                    <?php esc_html_e('Clear Filters', 'intelligent-room-booking-system-for-hotel'); ?>
                </a>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="irbsfh-settings-section">
        <h2><?php esc_html_e('All Bookings', 'intelligent-room-booking-system-for-hotel'); ?></h2>
        <div class="irbsfh-section-content">
            <?php if (empty($irbsfh_bookings)) : ?>
                <p><?php esc_html_e('No bookings found.', 'intelligent-room-booking-system-for-hotel'); ?></p>
            <?php else : ?>
                <!-- Bulk Actions -->
                <div style="margin-bottom: 15px;">
                    <select id="irbsfh-bulk-action">
                        <option value=""><?php esc_html_e('Bulk Actions', 'intelligent-room-booking-system-for-hotel'); ?></option>
                        <option value="confirm"><?php esc_html_e('Confirm', 'intelligent-room-booking-system-for-hotel'); ?></option>
                        <option value="deny"><?php esc_html_e('Deny', 'intelligent-room-booking-system-for-hotel'); ?></option>
                        <option value="delete"><?php esc_html_e('Delete', 'intelligent-room-booking-system-for-hotel'); ?></option>
                    </select>
                </div>

                <table class="irbsfh-bookings-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="irbsfh-select-all">
                            </th>
                            <th><?php esc_html_e('ID', 'intelligent-room-booking-system-for-hotel'); ?></th>
                            <th><?php esc_html_e('Customer', 'intelligent-room-booking-system-for-hotel'); ?></th>
                            <th><?php esc_html_e('Contact', 'intelligent-room-booking-system-for-hotel'); ?></th>
                            <th><?php esc_html_e('Room', 'intelligent-room-booking-system-for-hotel'); ?></th>
                            <th><?php esc_html_e('Date', 'intelligent-room-booking-system-for-hotel'); ?></th>
                            <th><?php esc_html_e('Status', 'intelligent-room-booking-system-for-hotel'); ?></th>
                            <th><?php esc_html_e('Created', 'intelligent-room-booking-system-for-hotel'); ?></th>
                            <th><?php esc_html_e('Actions', 'intelligent-room-booking-system-for-hotel'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($irbsfh_bookings as $irbsfh_booking) :
                            $irbsfh_room = IRBSFH_Database::get_room($irbsfh_booking->room_id);
                            $irbsfh_user = get_userdata($irbsfh_booking->user_id);
                        ?>
                            <tr id="booking-row-<?php echo esc_attr($irbsfh_booking->id); ?>">
                                <td>
                                    <input type="checkbox" class="irbsfh-booking-checkbox" value="<?php echo esc_attr($irbsfh_booking->id); ?>">
                                </td>
                                <td><strong>#<?php echo esc_html($irbsfh_booking->id); ?></strong></td>
                                <td>
                                    <?php echo esc_html($irbsfh_booking->first_name . ' ' . $irbsfh_booking->last_name); ?>
                                    <?php if ($irbsfh_user) : ?>
                                        <br><small><?php echo esc_html($irbsfh_user->user_login); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($irbsfh_booking->email); ?><br>
                                    <small><?php echo esc_html($irbsfh_booking->phone); ?></small>
                                </td>
                                <td><?php echo esc_html($irbsfh_room ? $irbsfh_room->name : __('Unknown', 'intelligent-room-booking-system-for-hotel')); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($irbsfh_booking->booking_date))); ?></td>
                                <td>
                                    <span class="irbsfh-status-badge <?php echo esc_attr($irbsfh_booking->status); ?>">
                                        <?php echo esc_html(ucfirst($irbsfh_booking->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($irbsfh_booking->created_at))); ?></td>
                                <td>
                                <td>
                                    <div class="irbsfh-actions">
                                        <?php if ('pending' === $irbsfh_booking->status) : ?>
                                            <button class="irbsfh-action-btn irbsfh-confirm-booking" data-booking-id="<?php echo esc_attr($irbsfh_booking->id); ?>" title="<?php esc_attr_e('Confirm Booking', 'intelligent-room-booking-system-for-hotel'); ?>">
                                                ✓
                                            </button>
                                            <button class="irbsfh-action-btn irbsfh-deny-booking" data-booking-id="<?php echo esc_attr($irbsfh_booking->id); ?>" title="<?php esc_attr_e('Deny Booking', 'intelligent-room-booking-system-for-hotel'); ?>">
                                                ✕
                                            </button>
                                        <?php endif; ?>
                                        <button class="irbsfh-action-btn delete irbsfh-delete-booking" data-booking-id="<?php echo esc_attr($irbsfh_booking->id); ?>" title="<?php esc_attr_e('Delete Booking', 'intelligent-room-booking-system-for-hotel'); ?>">
                                            🗑
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php if (! empty($irbsfh_booking->notes)) : ?>
                                <tr>
                                    <td colspan="9" style="background-color: #f9f9f9; padding: 10px;">
                                        <strong><?php esc_html_e('Notes:', 'intelligent-room-booking-system-for-hotel'); ?></strong>
                                        <?php echo esc_html($irbsfh_booking->notes); ?>
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