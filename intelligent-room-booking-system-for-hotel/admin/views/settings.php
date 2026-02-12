<?php

/**
 * Admin Settings View
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Get current settings
$irbsfh_start_day = IRBSFH_Settings::get('start_day_of_week', 0);
$irbsfh_closed_days = IRBSFH_Settings::get('closed_days', array());
$irbsfh_admin_email = IRBSFH_Settings::get('admin_email', get_option('admin_email'));

// Email templates
$irbsfh_user_confirmation_subject = IRBSFH_Settings::get('email_user_confirmation_subject', __('Booking Confirmed - {site_name}', 'intelligent-room-booking-system-for-hotel'));
$irbsfh_user_confirmation_body = IRBSFH_Settings::get('email_user_confirmation_body', '');
$irbsfh_user_denial_subject = IRBSFH_Settings::get('email_user_denial_subject', __('Booking Request Declined - {site_name}', 'intelligent-room-booking-system-for-hotel'));
$irbsfh_user_denial_body = IRBSFH_Settings::get('email_user_denial_body', '');
$irbsfh_admin_notification_subject = IRBSFH_Settings::get('email_admin_notification_subject', __('New Booking Request - {site_name}', 'intelligent-room-booking-system-for-hotel'));
$irbsfh_admin_notification_body = IRBSFH_Settings::get('email_admin_notification_body', '');

$irbsfh_days_of_week = array(
    0 => __('Sunday', 'intelligent-room-booking-system-for-hotel'),
    1 => __('Monday', 'intelligent-room-booking-system-for-hotel'),
    2 => __('Tuesday', 'intelligent-room-booking-system-for-hotel'),
    3 => __('Wednesday', 'intelligent-room-booking-system-for-hotel'),
    4 => __('Thursday', 'intelligent-room-booking-system-for-hotel'),
    5 => __('Friday', 'intelligent-room-booking-system-for-hotel'),
    6 => __('Saturday', 'intelligent-room-booking-system-for-hotel'),
);
?>

<div class="wrap irbsfh-admin-wrap">
    <div class="irbsfh-admin-header">
        <h1><?php esc_html_e('Booking System Settings', 'intelligent-room-booking-system-for-hotel'); ?></h1>
    </div>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- specific check for settings update flag
    if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'intelligent-room-booking-system-for-hotel'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="irbsfh-settings-form">
        <?php wp_nonce_field('irbsfh_save_settings', 'irbsfh_settings_nonce'); ?>

        <!-- General Settings -->
        <div class="irbsfh-settings-section">
            <h2><?php esc_html_e('General Settings', 'intelligent-room-booking-system-for-hotel'); ?></h2>
            <div class="irbsfh-section-content">

                <div class="irbsfh-setting-row">
                    <div class="irbsfh-setting-label">
                        <label for="start_day_of_week"><?php esc_html_e('Week Start Day', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <p class="description"><?php esc_html_e('First day of the week in calendar', 'intelligent-room-booking-system-for-hotel'); ?></p>
                    </div>
                    <div class="irbsfh-setting-input">
                        <select name="start_day_of_week" id="start_day_of_week">
                            <?php foreach ($irbsfh_days_of_week as $irbsfh_day_num => $irbsfh_day_name) : ?>
                                <option value="<?php echo esc_attr($irbsfh_day_num); ?>" <?php selected($irbsfh_start_day, $irbsfh_day_num); ?>>
                                    <?php echo esc_html($irbsfh_day_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="irbsfh-setting-row">
                    <div class="irbsfh-setting-label">
                        <label><?php esc_html_e('Closed Days', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <p class="description"><?php esc_html_e('Days when bookings are not available', 'intelligent-room-booking-system-for-hotel'); ?></p>
                    </div>
                    <div class="irbsfh-setting-input">
                        <div class="irbsfh-days-checkboxes">
                            <?php foreach ($irbsfh_days_of_week as $irbsfh_day_num => $irbsfh_day_name) : ?>
                                <div class="irbsfh-day-checkbox">
                                    <input type="checkbox"
                                        name="closed_days[]"
                                        id="closed_day_<?php echo esc_attr($irbsfh_day_num); ?>"
                                        value="<?php echo esc_attr($irbsfh_day_num); ?>"
                                        <?php checked(in_array($irbsfh_day_num, $irbsfh_closed_days)); ?>>
                                    <label for="closed_day_<?php echo esc_attr($irbsfh_day_num); ?>">
                                        <?php echo esc_html($irbsfh_day_name); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="irbsfh-setting-row">
                    <div class="irbsfh-setting-label">
                        <label for="admin_email"><?php esc_html_e('Admin Email', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <p class="description"><?php esc_html_e('Email address for booking notifications', 'intelligent-room-booking-system-for-hotel'); ?></p>
                    </div>
                    <div class="irbsfh-setting-input">
                        <input type="email"
                            name="admin_email"
                            id="admin_email"
                            value="<?php echo esc_attr($irbsfh_admin_email); ?>"
                            required>
                    </div>
                </div>

            </div>
        </div>

        <!-- Email Templates -->
        <div class="irbsfh-settings-section">
            <h2><?php esc_html_e('Email Templates', 'intelligent-room-booking-system-for-hotel'); ?></h2>
            <div class="irbsfh-section-content">

                <!-- User Confirmation Email -->
                <div class="irbsfh-setting-row">
                    <div class="irbsfh-setting-label">
                        <label for="email_user_confirmation_subject"><?php esc_html_e('User Confirmation Email', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <p class="description"><?php esc_html_e('Sent when admin confirms a booking', 'intelligent-room-booking-system-for-hotel'); ?></p>
                    </div>
                    <div class="irbsfh-setting-input">
                        <label><?php esc_html_e('Subject:', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <input type="text"
                            name="email_user_confirmation_subject"
                            id="email_user_confirmation_subject"
                            value="<?php echo esc_attr($irbsfh_user_confirmation_subject); ?>">

                        <label style="margin-top: 10px; display: block;"><?php esc_html_e('Body:', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <textarea name="email_user_confirmation_body"
                            rows="10"><?php echo esc_textarea($irbsfh_user_confirmation_body); ?></textarea>

                        <div class="irbsfh-email-template-vars">
                            <h4><?php esc_html_e('Available Template Tags:', 'intelligent-room-booking-system-for-hotel'); ?></h4>
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
                <div class="irbsfh-setting-row">
                    <div class="irbsfh-setting-label">
                        <label for="email_user_denial_subject"><?php esc_html_e('User Denial Email', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <p class="description"><?php esc_html_e('Sent when admin denies a booking', 'intelligent-room-booking-system-for-hotel'); ?></p>
                    </div>
                    <div class="irbsfh-setting-input">
                        <label><?php esc_html_e('Subject:', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <input type="text"
                            name="email_user_denial_subject"
                            id="email_user_denial_subject"
                            value="<?php echo esc_attr($irbsfh_user_denial_subject); ?>">

                        <label style="margin-top: 10px; display: block;"><?php esc_html_e('Body:', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <textarea name="email_user_denial_body"
                            rows="10"><?php echo esc_textarea($irbsfh_user_denial_body); ?></textarea>

                        <div class="irbsfh-email-template-vars">
                            <h4><?php esc_html_e('Available Template Tags:', 'intelligent-room-booking-system-for-hotel'); ?></h4>
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
                <div class="irbsfh-setting-row">
                    <div class="irbsfh-setting-label">
                        <label for="email_admin_notification_subject"><?php esc_html_e('Admin Notification Email', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <p class="description"><?php esc_html_e('Sent to admin when a new booking is created', 'intelligent-room-booking-system-for-hotel'); ?></p>
                    </div>
                    <div class="irbsfh-setting-input">
                        <label><?php esc_html_e('Subject:', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <input type="text"
                            name="email_admin_notification_subject"
                            id="email_admin_notification_subject"
                            value="<?php echo esc_attr($irbsfh_admin_notification_subject); ?>">

                        <label style="margin-top: 10px; display: block;"><?php esc_html_e('Body:', 'intelligent-room-booking-system-for-hotel'); ?></label>
                        <textarea name="email_admin_notification_body"
                            rows="10"><?php echo esc_textarea($irbsfh_admin_notification_body); ?></textarea>

                        <div class="irbsfh-email-template-vars">
                            <h4><?php esc_html_e('Available Template Tags:', 'intelligent-room-booking-system-for-hotel'); ?></h4>
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
                <?php esc_html_e('Save Settings', 'intelligent-room-booking-system-for-hotel'); ?>
            </button>
        </p>

    </form>

    <!-- System Information -->
    <div class="irbsfh-settings-section">
        <h2><?php esc_html_e('System Information', 'intelligent-room-booking-system-for-hotel'); ?></h2>
        <div class="irbsfh-section-content">
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Plugin Version:', 'intelligent-room-booking-system-for-hotel'); ?></strong></td>
                        <td><?php echo esc_html(IRBSFH_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('WordPress Version:', 'intelligent-room-booking-system-for-hotel'); ?></strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('PHP Version:', 'intelligent-room-booking-system-for-hotel'); ?></strong></td>
                        <td><?php echo esc_html(phpversion()); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Database Prefix:', 'intelligent-room-booking-system-for-hotel'); ?></strong></td>
                        <td><?php global $wpdb;
                            echo esc_html($wpdb->prefix); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Total Bookings:', 'intelligent-room-booking-system-for-hotel'); ?></strong></td>
                        <td>
                            <?php
                            $irbsfh_stats = IRBSFH_Database::get_booking_stats();
                            echo esc_html($irbsfh_stats->total);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Total Rooms:', 'intelligent-room-booking-system-for-hotel'); ?></strong></td>
                        <td>
                            <?php
                            $irbsfh_count = IRBSFH_Database::get_total_rooms();
                            echo esc_html($irbsfh_count);
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Documentation -->
    <div class="irbsfh-settings-section">
        <h2><?php esc_html_e('Quick Documentation', 'intelligent-room-booking-system-for-hotel'); ?></h2>
        <div class="irbsfh-section-content">
            <h3><?php esc_html_e('Available Shortcodes', 'intelligent-room-booking-system-for-hotel'); ?></h3>
            <ul>
                <li><code>[irbsfh_booking_calendar]</code> - <?php esc_html_e('Display interactive booking calendar', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>[irbsfh_booking_form]</code> - <?php esc_html_e('Display booking submission form', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>[irbsfh_room_list]</code> - <?php esc_html_e('Display list of available rooms', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>[irbsfh_user_bookings]</code> - <?php esc_html_e('Display user booking history', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>[irbsfh_login_widget]</code> - <?php esc_html_e('Display login form', 'intelligent-room-booking-system-for-hotel'); ?></li>
            </ul>

            <h3><?php esc_html_e('Email Template Tags', 'intelligent-room-booking-system-for-hotel'); ?></h3>
            <p><?php esc_html_e('You can use these tags in your email templates. They will be automatically replaced with actual values:', 'intelligent-room-booking-system-for-hotel'); ?></p>
            <ul>
                <li><code>{first_name}</code> - <?php esc_html_e('Customer first name', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>{last_name}</code> - <?php esc_html_e('Customer last name', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>{email}</code> - <?php esc_html_e('Customer email', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>{phone}</code> - <?php esc_html_e('Customer phone', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>{room_name}</code> - <?php esc_html_e('Name of the booked room', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>{booking_date}</code> - <?php esc_html_e('Booking date', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>{booking_id}</code> - <?php esc_html_e('Unique booking ID', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>{site_name}</code> - <?php esc_html_e('Your site name', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><code>{manage_url}</code> - <?php esc_html_e('Link to manage booking (admin only)', 'intelligent-room-booking-system-for-hotel'); ?></li>
            </ul>

            <h3><?php esc_html_e('How It Works', 'intelligent-room-booking-system-for-hotel'); ?></h3>
            <ol>
                <li><?php esc_html_e('Users must register and login before making bookings', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('Users select a room and date, then fill out the booking form', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('Booking is created with "pending" status', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('Admin receives email notification about new booking', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('Admin can confirm or deny the booking', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('User receives email with confirmation or denial', 'intelligent-room-booking-system-for-hotel'); ?></li>
                <li><?php esc_html_e('Users can view and cancel their own bookings', 'intelligent-room-booking-system-for-hotel'); ?></li>
            </ol>
        </div>
    </div>
</div>