<?php

/**
 * Admin Rooms View
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Get all rooms
$irbsfh_rooms = IRBSFH_Database::get_rooms();

// Get statistics for each room
$irbsfh_room_stats = IRBSFH_Database::get_room_stats();
?>

<div class="wrap irbsfh-admin-wrap">
    <div class="irbsfh-admin-header">
        <h1><?php esc_html_e('Rooms Management', 'intelligent-room-booking-system-for-hotel'); ?></h1>
        <div class="irbsfh-admin-actions">
            <button type="button" id="irbsfh-add-room" class="button button-primary">
                <?php esc_html_e('Add New Room', 'intelligent-room-booking-system-for-hotel'); ?>
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=irbsfh-bookings')); ?>" class="button">
                <?php esc_html_e('View Bookings', 'intelligent-room-booking-system-for-hotel'); ?>
            </a>
        </div>
    </div>

    <div class="irbsfh-settings-section">
        <h2><?php esc_html_e('All Rooms', 'intelligent-room-booking-system-for-hotel'); ?></h2>
        <div class="irbsfh-section-content">
            <?php if (empty($irbsfh_rooms)) : ?>
                <div class="irbsfh-message irbsfh-message-info">
                    <p><?php esc_html_e('No rooms have been created yet. Click "Add New Room" to get started.', 'intelligent-room-booking-system-for-hotel'); ?></p>
                </div>
            <?php else : ?>
                <div class="irbsfh-rooms-grid">
                    <?php foreach ($irbsfh_rooms as $irbsfh_room) :
                        $irbsfh_stats = $irbsfh_room_stats[$irbsfh_room->id];
                    ?>
                        <div class="irbsfh-room-card">
                            <h3><?php echo esc_html($irbsfh_room->name); ?></h3>

                            <?php if (! empty($irbsfh_room->description)) : ?>
                                <div class="irbsfh-room-meta">
                                    <p><?php echo esc_html(wp_trim_words($irbsfh_room->description, 20)); ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="irbsfh-room-meta">
                                <p>
                                    <strong><?php esc_html_e('Booking Title:', 'intelligent-room-booking-system-for-hotel'); ?></strong>
                                    <?php echo esc_html($irbsfh_room->booking_title); ?>
                                </p>
                                <p>
                                    <strong><?php esc_html_e('Max Bookings per User:', 'intelligent-room-booking-system-for-hotel'); ?></strong>
                                    <?php echo esc_html($irbsfh_room->max_bookings_per_user); ?>
                                </p>
                                <p>
                                    <strong><?php esc_html_e('Capacity:', 'intelligent-room-booking-system-for-hotel'); ?></strong>
                                    <?php echo esc_html($irbsfh_room->capacity); ?>
                                </p>
                                <p>
                                    <strong><?php esc_html_e('Status:', 'intelligent-room-booking-system-for-hotel'); ?></strong>
                                    <span class="irbsfh-status-badge <?php echo esc_attr($irbsfh_room->status); ?>">
                                        <?php echo esc_html(ucfirst($irbsfh_room->status)); ?>
                                    </span>
                                </p>
                            </div>

                            <div class="irbsfh-room-meta" style="padding-top: 10px; border-top: 1px solid #f0f0f0;">
                                <p><strong><?php esc_html_e('Booking Statistics:', 'intelligent-room-booking-system-for-hotel'); ?></strong></p>
                                <p>
                                    <?php esc_html_e('Total:', 'intelligent-room-booking-system-for-hotel'); ?> <?php echo esc_html($irbsfh_stats['total']); ?> |
                                    <?php esc_html_e('Pending:', 'intelligent-room-booking-system-for-hotel'); ?> <?php echo esc_html($irbsfh_stats['pending']); ?> |
                                    <?php esc_html_e('Confirmed:', 'intelligent-room-booking-system-for-hotel'); ?> <?php echo esc_html($irbsfh_stats['confirmed']); ?>
                                </p>
                            </div>

                            <div class="irbsfh-room-actions">
                                <button type="button" class="button irbsfh-edit-room" data-room-id="<?php echo esc_attr($irbsfh_room->id); ?>">
                                    <?php esc_html_e('Edit', 'intelligent-room-booking-system-for-hotel'); ?>
                                </button>
                                <?php if (0 === intval($irbsfh_stats['total'])) : ?>
                                    <button type="button" class="button irbsfh-delete-room" data-room-id="<?php echo esc_attr($irbsfh_room->id); ?>">
                                        <?php esc_html_e('Delete', 'intelligent-room-booking-system-for-hotel'); ?>
                                    </button>
                                <?php else : ?>
                                    <button type="button" class="button" disabled title="<?php esc_attr_e('Cannot delete room with existing bookings', 'intelligent-room-booking-system-for-hotel'); ?>">
                                        <?php esc_html_e('Delete', 'intelligent-room-booking-system-for-hotel'); ?>
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=irbsfh-bookings&room_id=' . $irbsfh_room->id)); ?>" class="button">
                                    <?php esc_html_e('View Bookings', 'intelligent-room-booking-system-for-hotel'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Help Section -->
    <div class="irbsfh-settings-section">
        <h2><?php esc_html_e('Room Management Tips', 'intelligent-room-booking-system-for-hotel'); ?></h2>
        <div class="irbsfh-section-content">
            <ul>
                <li><?php esc_html_e('Each room can have its own booking title (e.g., "Room", "Court", "Chalet", "Conference Room").', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('Set the maximum number of active bookings allowed per user for each room.', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('You can only delete rooms that have no bookings associated with them.', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('Inactive rooms will not appear in the booking calendar for users.', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('Use descriptive names for rooms to help users identify them easily.', 'intelligent-room-booking-system-for-hotel'); ?></li>
            </ul>
        </div>
    </div>

    <!-- Shortcode Help -->
    <div class="irbsfh-settings-section">
        <h2><?php esc_html_e('Display Rooms on Your Site', 'intelligent-room-booking-system-for-hotel'); ?></h2>
        <div class="irbsfh-section-content">
            <p><?php esc_html_e('Use these shortcodes to display rooms and booking functionality on your pages:', 'intelligent-room-booking-system-for-hotel'); ?></p>

            <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0;">
                <p><strong>[irbsfh_booking_calendar]</strong> - <?php esc_html_e('Display interactive booking calendar', 'intelligent-room-booking-system-for-hotel'); ?></p>
                <p><strong>[irbsfh_booking_form]</strong> - <?php esc_html_e('Display booking submission form', 'intelligent-room-booking-system-for-hotel'); ?></p>
                <p><strong>[irbsfh_room_list]</strong> - <?php esc_html_e('Display list of all active rooms', 'intelligent-room-booking-system-for-hotel'); ?></p>
                <p><strong>[irbsfh_user_bookings]</strong> - <?php esc_html_e('Display user booking history (requires login)', 'intelligent-room-booking-system-for-hotel'); ?></p>
                <p><strong>[irbsfh_login_widget]</strong> - <?php esc_html_e('Display login form', 'intelligent-room-booking-system-for-hotel'); ?></p>
            </div>

            <p><?php esc_html_e('You can also add the "Booking Login" widget to any widget area from Appearance > Widgets.', 'intelligent-room-booking-system-for-hotel'); ?></p>
        </div>
    </div>
</div>