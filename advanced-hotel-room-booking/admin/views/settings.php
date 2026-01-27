<?php

/**
 * Admin Settings View
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Get current settings
$start_day = ABS_Settings::get('start_day_of_week', 0);
$closed_days = ABS_Settings::get('closed_days', array());
$admin_email = ABS_Settings::get('admin_email', get_option('admin_email'));

// Email templates
$user_confirmation_subject = ABS_Settings::get('email_user_confirmation_subject', __('Booking Confirmed - {site_name}', 'advanced-hotel-room-booking-system'));
$user_confirmation_body = ABS_Settings::get('email_user_confirmation_body', '');
$user_denial_subject = ABS_Settings::get('email_user_denial_subject', __('Booking Request Declined - {site_name}', 'advanced-hotel-room-booking-system'));
$user_denial_body = ABS_Settings::get('email_user_denial_body', '');
$admin_notification_subject = ABS_Settings::get('email_admin_notification_subject', __('New Booking Request - {site_name}', 'advanced-hotel-room-booking-system'));
$admin_notification_body = ABS_Settings::get('email_admin_notification_body', '');

$days_of_week = array(
    0 => __('Sunday', 'advanced-hotel-room-booking-system'),
    1 => __('Monday', 'advanced-hotel-room-booking-system'),
    2 => __('Tuesday', 'advanced-hotel-room-booking-system'),
    3 => __('Wednesday', 'advanced-hotel-room-booking-system'),
    4 => __('Thursday', 'advanced-hotel-room-booking-system'),
    5 => __('Friday', 'advanced-hotel-room-booking-system'),
    6 => __('Saturday', 'advanced-hotel-room-booking-system'),
);
?>

<div class="wrap abs-admin-wrap">
    <div class="abs-admin-header">
        <h1><?php esc_html_e('Booking System Settings', 'advanced-hotel-room-booking-system'); ?></h1>
    </div>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- specific check for settings update flag
    if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'advanced-hotel-room-booking-system'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="abs-settings-form">
        <?php wp_nonce_field('abs_save_settings', 'abs_settings_nonce'); ?>

        <!-- General Settings -->
        <div class="abs-settings-section">
            <h2><?php esc_html_e('General Settings', 'advanced-hotel-room-booking-system'); ?></h2>
            <div class="abs-section-content">

                <div class="abs-setting-row">
                    <div class="abs-setting-label">
                        <label for="start_day_of_week"><?php esc_html_e('Week Start Day', 'advanced-hotel-room-booking-system'); ?></label>
                        <p class="description"><?php esc_html_e('First day of the week in calendar', 'advanced-hotel-room-booking-system'); ?></p>
                    </div>
                    <div class="abs-setting-input">
                        <select name="start_day_of_week" id="start_day_of_week">
                            <?php foreach ($days_of_week as $day_num => $day_name) : ?>
                                <option value="<?php echo esc_attr($day_num); ?>" <?php selected($start_day, $day_num); ?>>
                                    <?php echo esc_html($day_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="abs-setting-row">
                    <div class="abs-setting-label">
                        <label><?php esc_html_e('Closed Days', 'advanced-hotel-room-booking-system'); ?></label>
                        <p class="description"><?php esc_html_e('Days when bookings are not available', 'advanced-hotel-room-booking-system'); ?></p>
                    </div>
                    <div class="abs-setting-input">
                        <div class="abs-days-checkboxes">
                            <?php foreach ($days_of_week as $day_num => $day_name) : ?>
                                <div class="abs-day-checkbox">
                                    <input type="checkbox"
                                        name="closed_days[]"
                                        id="closed_day_<?php echo esc_attr($day_num); ?>"
                                        value="<?php echo esc_attr($day_num); ?>"
                                        <?php checked(in_array($day_num, $closed_days)); ?>>
                                    <label for="closed_day_<?php echo esc_attr($day_num); ?>">
                                        <?php echo esc_html($day_name); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="abs-setting-row">
                    <div class="abs-setting-label">
                        <label for="admin_email"><?php esc_html_e('Admin Email', 'advanced-hotel-room-booking-system'); ?></label>
                        <p class="description"><?php esc_html_e('Email address for booking notifications', 'advanced-hotel-room-booking-system'); ?></p>
                    </div>
                    <div class="abs-setting-input">
                        <input type="email"
                            name="admin_email"
                            id="admin_email"
                            value="<?php echo esc_attr($admin_email); ?>"
                            required>
                    </div>
                </div>

            </div>
        </div>

        <!-- Email Templates -->
        <div class="abs-settings-section">
            <h2><?php esc_html_e('Email Templates', 'advanced-hotel-room-booking-system'); ?></h2>
            <div class="abs-section-content">

                <!-- User Confirmation Email -->
                <div class="abs-setting-row">
                    <div class="abs-setting-label">
                        <label for="email_user_confirmation_subject"><?php esc_html_e('User Confirmation Email', 'advanced-hotel-room-booking-system'); ?></label>
                        <p class="description"><?php esc_html_e('Sent when admin confirms a booking', 'advanced-hotel-room-booking-system'); ?></p>
                    </div>
                    <div class="abs-setting-input">
                        <label><?php esc_html_e('Subject:', 'advanced-hotel-room-booking-system'); ?></label>
                        <input type="text"
                            name="email_user_confirmation_subject"
                            id="email_user_confirmation_subject"
                            value="<?php echo esc_attr($user_confirmation_subject); ?>">

                        <label style="margin-top: 10px; display: block;"><?php esc_html_e('Body:', 'advanced-hotel-room-booking-system'); ?></label>
                        <textarea name="email_user_confirmation_body"
                            rows="10"><?php echo esc_textarea($user_confirmation_body); ?></textarea>

                        <div class="abs-email-template-vars">
                            <h4><?php esc_html_e('Available Template Tags:', 'advanced-hotel-room-booking-system'); ?></h4>
                            <ul>
                                <li>{first_name}</li>
                                <li>{last_name}</li>
                                <li>{email}</li>
                                <li>{phone}</li>
                                <li>{room_name}</li>
                                <li>{booking_title}</li>
                                <li>{booking_date}</li>
                                <li>{booking_time}</li>
                                <li>{booking_id}</li>
                                <li>{site_name}</li>
                                <li>{site_url}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- User Denial Email -->
                <div class="abs-setting-row">
                    <div class="abs-setting-label">
                        <label for="email_user_denial_subject"><?php esc_html_e('User Denial Email', 'advanced-hotel-room-booking-system'); ?></label>
                        <p class="description"><?php esc_html_e('Sent when admin denies a booking', 'advanced-hotel-room-booking-system'); ?></p>
                    </div>
                    <div class="abs-setting-input">
                        <label><?php esc_html_e('Subject:', 'advanced-hotel-room-booking-system'); ?></label>
                        <input type="text"
                            name="email_user_denial_subject"
                            id="email_user_denial_subject"
                            value="<?php echo esc_attr($user_denial_subject); ?>">

                        <label style="margin-top: 10px; display: block;"><?php esc_html_e('Body:', 'advanced-hotel-room-booking-system'); ?></label>
                        <textarea name="email_user_denial_body"
                            rows="10"><?php echo esc_textarea($user_denial_body); ?></textarea>

                        <div class="abs-email-template-vars">
                            <h4><?php esc_html_e('Available Template Tags:', 'advanced-hotel-room-booking-system'); ?></h4>
                            <ul>
                                <li>{first_name}</li>
                                <li>{last_name}</li>
                                <li>{email}</li>
                                <li>{phone}</li>
                                <li>{room_name}</li>
                                <li>{booking_title}</li>
                                <li>{booking_date}</li>
                                <li>{booking_id}</li>
                                <li>{site_name}</li>
                                <li>{site_url}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Admin Notification Email -->
                <div class="abs-setting-row">
                    <div class="abs-setting-label">
                        <label for="email_admin_notification_subject"><?php esc_html_e('Admin Notification Email', 'advanced-hotel-room-booking-system'); ?></label>
                        <p class="description"><?php esc_html_e('Sent to admin when a new booking is created', 'advanced-hotel-room-booking-system'); ?></p>
                    </div>
                    <div class="abs-setting-input">
                        <label><?php esc_html_e('Subject:', 'advanced-hotel-room-booking-system'); ?></label>
                        <input type="text"
                            name="email_admin_notification_subject"
                            id="email_admin_notification_subject"
                            value="<?php echo esc_attr($admin_notification_subject); ?>">

                        <label style="margin-top: 10px; display: block;"><?php esc_html_e('Body:', 'advanced-hotel-room-booking-system'); ?></label>
                        <textarea name="email_admin_notification_body"
                            rows="10"><?php echo esc_textarea($admin_notification_body); ?></textarea>

                        <div class="abs-email-template-vars">
                            <h4><?php esc_html_e('Available Template Tags:', 'advanced-hotel-room-booking-system'); ?></h4>
                            <ul>
                                <li>{first_name}</li>
                                <li>{last_name}</li>
                                <li>{email}</li>
                                <li>{phone}</li>
                                <li>{room_name}</li>
                                <li>{booking_title}</li>
                                <li>{booking_date}</li>
                                <li>{booking_time}</li>
                                <li>{booking_id}</li>
                                <li>{status}</li>
                                <li>{notes}</li>
                                <li>{site_name}</li>
                                <li>{site_url}</li>
                                <li>{manage_url}</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Save Button -->
        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                <?php esc_html_e('Save Settings', 'advanced-hotel-room-booking-system'); ?>
            </button>
        </p>

    </form>

    <!-- System Information -->
    <div class="abs-settings-section">
        <h2><?php esc_html_e('System Information', 'advanced-hotel-room-booking-system'); ?></h2>
        <div class="abs-section-content">
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Plugin Version:', 'advanced-hotel-room-booking-system'); ?></strong></td>
                        <td><?php echo esc_html(ABS_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('WordPress Version:', 'advanced-hotel-room-booking-system'); ?></strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('PHP Version:', 'advanced-hotel-room-booking-system'); ?></strong></td>
                        <td><?php echo esc_html(phpversion()); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Database Prefix:', 'advanced-hotel-room-booking-system'); ?></strong></td>
                        <td><?php global $wpdb;
                            echo esc_html($wpdb->prefix); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Total Bookings:', 'advanced-hotel-room-booking-system'); ?></strong></td>
                        <td>
                            <?php
                            $stats = ABS_Database::get_booking_stats();
                            echo esc_html($stats->total);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Total Rooms:', 'advanced-hotel-room-booking-system'); ?></strong></td>
                        <td>
                            <?php
                            $count = ABS_Database::get_total_rooms();
                            echo esc_html($count);
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Documentation -->
    <div class="abs-settings-section">
        <h2><?php esc_html_e('Quick Documentation', 'advanced-hotel-room-booking-system'); ?></h2>
        <div class="abs-section-content">
            <h3><?php esc_html_e('Available Shortcodes', 'advanced-hotel-room-booking-system'); ?></h3>
            <ul>
                <li><code>[abs_booking_calendar]</code> - <?php esc_html_e('Display interactive booking calendar', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>[abs_booking_form]</code> - <?php esc_html_e('Display booking submission form', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>[abs_room_list]</code> - <?php esc_html_e('Display list of available rooms', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>[abs_user_bookings]</code> - <?php esc_html_e('Display user booking history', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>[abs_login_widget]</code> - <?php esc_html_e('Display login form', 'advanced-hotel-room-booking-system'); ?></li>
            </ul>

            <h3><?php esc_html_e('Email Template Tags', 'advanced-hotel-room-booking-system'); ?></h3>
            <p><?php esc_html_e('You can use these tags in your email templates. They will be automatically replaced with actual values:', 'advanced-hotel-room-booking-system'); ?></p>
            <ul>
                <li><code>{first_name}</code> - <?php esc_html_e('Customer first name', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>{last_name}</code> - <?php esc_html_e('Customer last name', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>{email}</code> - <?php esc_html_e('Customer email', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>{phone}</code> - <?php esc_html_e('Customer phone', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>{room_name}</code> - <?php esc_html_e('Name of the booked room', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>{booking_date}</code> - <?php esc_html_e('Booking date', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>{booking_id}</code> - <?php esc_html_e('Unique booking ID', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>{site_name}</code> - <?php esc_html_e('Your site name', 'advanced-hotel-room-booking-system'); ?></li>
                <li><code>{manage_url}</code> - <?php esc_html_e('Link to manage booking (admin only)', 'advanced-hotel-room-booking-system'); ?></li>
            </ul>

            <h3><?php esc_html_e('How It Works', 'advanced-hotel-room-booking-system'); ?></h3>
            <ol>
                <li><?php esc_html_e('Users must register and login before making bookings', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('Users select a room and date, then fill out the booking form', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('Booking is created with "pending" status', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('Admin receives email notification about new booking', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('Admin can confirm or deny the booking', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('User receives email with confirmation or denial', 'advanced-hotel-room-booking-system'); ?></li>
                <li><?php esc_html_e('Users can view and cancel their own bookings', 'advanced-hotel-room-booking-system'); ?></li>
            </ol>
        </div>
    </div>
</div>