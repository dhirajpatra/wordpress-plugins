<?php

/**
 * Admin Rooms View
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Get all rooms
$rooms = ABS_Database::get_rooms();

// Get statistics for each room
$room_stats = ABS_Database::get_room_stats();
?>

<div class="wrap abs-admin-wrap">
    <div class="abs-admin-header">
        <h1><?php esc_html_e('Rooms Management', 'advanced-hotel-room-booking-system'); ?></h1>
        <div class="abs-admin-actions">
            <button type="button" id="abs-add-room" class="button button-primary">
                <?php esc_html_e('Add New Room', 'advanced-hotel-room-booking-system'); ?>
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=abs-bookings')); ?>" class="button">
                <?php esc_html_e('View Bookings', 'advanced-hotel-room-booking-system'); ?>
            </a>
        </div>
    </div>

    <div class="abs-settings-section">
        <h2><?php esc_html_e('All Rooms', 'advanced-hotel-room-booking-system'); ?></h2>
        <div class="abs-section-content">
            <?php if (empty($rooms)) : ?>
                <div class="abs-message abs-message-info">
                    <p><?php esc_html_e('No rooms have been created yet. Click "Add New Room" to get started.', 'advanced-hotel-room-booking-system'); ?></p>
                </div>
            <?php else : ?>
                <div class="abs-rooms-grid">
                    <?php foreach ($rooms as $room) :
                        $stats = $room_stats[$room->id];
                    ?>
                        <div class="abs-room-card">
                            <h3><?php echo esc_html($room->name); ?></h3>

                            <?php if (! empty($room->description)) : ?>
                                <div class="abs-room-meta">
                                    <p><?php echo esc_html(wp_trim_words($room->description, 20)); ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="abs-room-meta">
                                <p>
                                    <strong><?php esc_html_e('Booking Title:', 'advanced-hotel-room-booking-system'); ?></strong>
                                    <?php echo esc_html($room->booking_title); ?>
                                </p>
                                <p>
                                    <strong><?php esc_html_e('Max Bookings per User:', 'advanced-hotel-room-booking-system'); ?></strong>
                                    <?php echo esc_html($room->max_bookings_per_user); ?>
                                </p>
                                <p>
                                    <strong><?php esc_html_e('Capacity:', 'advanced-hotel-room-booking-system'); ?></strong>
                                    <?php echo esc_html($room->capacity); ?>
                                </p>
                                <p>
                                    <strong><?php esc_html_e('Status:', 'advanced-hotel-room-booking-system'); ?></strong>
                                    <span class="abs-status-badge <?php echo esc_attr($room->status); ?>">
                                        <?php echo esc_html(ucfirst($room->status)); ?>
                                    </span>
                                </p>
                            </div>

                            <div class="abs-room-meta" style="padding-top: 10px; border-top: 1px solid #f0f0f0;">
                                <p><strong><?php esc_html_e('Booking Statistics:', 'advanced-hotel-room-booking-system'); ?></strong></p>
                                <p>
                                    <?php esc_html_e('Total:', 'advanced-hotel-room-booking-system'); ?> <?php echo esc_html($stats['total']); ?> |
                                    <?php esc_html_e('Pending:', 'advanced-hotel-room-booking-system'); ?> <?php echo esc_html($stats['pending']); ?> |
                                    <?php esc_html_e('Confirmed:', 'advanced-hotel-room-booking-system'); ?> <?php echo esc_html($stats['confirmed']); ?>
                                </p>
                            </div>

                            <div class="abs-room-actions">
                                <button type="button" class="button abs-edit-room" data-room-id="<?php echo esc_attr($room->id); ?>">
                                    <?php esc_html_e('Edit', 'advanced-hotel-room-booking-system'); ?>
                                </button>
                                <?php if (0 === intval($stats['total'])) : ?>
                                    <button type="button" class="button abs-delete-room" data-room-id="<?php echo esc_attr($room->id); ?>">
                                        <?php esc_html_e('Delete', 'advanced-hotel-room-booking-system'); ?>
                                    </button>
                                <?php else : ?>
                                    <button type="button" class="button" disabled title="<?php esc_attr_e('Cannot delete room with existing bookings', 'advanced-hotel-room-booking-system'); ?>">
                                        <?php esc_html_e('Delete', 'advanced-hotel-room-booking-system'); ?>
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=abs-bookings&room_id=' . $room->id)); ?>" class="button">
                                    <?php esc_html_e('View Bookings', 'advanced-hotel-room-booking-system'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Help Section -->
    <div class="abs-settings-section">
        <h2><?php esc_html_e('Room Management Tips', 'advanced-hotel-room-booking-system'); ?></h2>
        <div class="abs-section-content">
            <ul>
                <li><?php esc_html_e('Each room can have its own booking title (e.g., "Room", "Court", "Chalet", "Conference Room").', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('Set the maximum number of active bookings allowed per user for each room.', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('You can only delete rooms that have no bookings associated with them.', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('Inactive rooms will not appear in the booking calendar for users.', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('Use descriptive names for rooms to help users identify them easily.', 'advanced-hotel-room-booking-system'); ?></li>
            </ul>
        </div>
    </div>

    <!-- Shortcode Help -->
    <div class="abs-settings-section">
        <h2><?php esc_html_e('Display Rooms on Your Site', 'advanced-hotel-room-booking-system'); ?></h2>
        <div class="abs-section-content">
            <p><?php esc_html_e('Use these shortcodes to display rooms and booking functionality on your pages:', 'advanced-hotel-room-booking-system'); ?></p>

            <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0;">
                <p><strong>[abs_booking_calendar]</strong> - <?php esc_html_e('Display interactive booking calendar', 'advanced-hotel-room-booking-system'); ?></p>
                <p><strong>[abs_booking_form]</strong> - <?php esc_html_e('Display booking submission form', 'advanced-hotel-room-booking-system'); ?></p>
                <p><strong>[abs_room_list]</strong> - <?php esc_html_e('Display list of all active rooms', 'advanced-hotel-room-booking-system'); ?></p>
                <p><strong>[abs_user_bookings]</strong> - <?php esc_html_e('Display user booking history (requires login)', 'advanced-hotel-room-booking-system'); ?></p>
                <p><strong>[abs_login_widget]</strong> - <?php esc_html_e('Display login form', 'advanced-hotel-room-booking-system'); ?></p>
            </div>

            <p><?php esc_html_e('You can also add the "Booking Login" widget to any widget area from Appearance > Widgets.', 'advanced-hotel-room-booking-system'); ?></p>
        </div>
    </div>
</div>