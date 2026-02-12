<?php

/**
 * Email handler class
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Email class
 */
class IRBSFH_Email
{

    /**
     * Send booking confirmation email to user
     *
     * @param int $booking_id Booking ID.
     * @return bool
     */
    public static function send_user_confirmation($booking_id)
    {
        $booking = IRBSFH_Database::get_booking($booking_id);
        if (! $booking) {
            return false;
        }

        $room = IRBSFH_Database::get_room($booking->room_id);
        $subject = self::parse_template_tags(
            IRBSFH_Settings::get('email_user_confirmation_subject', __('Booking Confirmed', 'intelligent-room-booking-system-for-hotel')),
            $booking,
            $room
        );

        $message = self::parse_template_tags(
            IRBSFH_Settings::get('email_user_confirmation_body', self::get_default_user_confirmation_template()),
            $booking,
            $room
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($booking->email, $subject, $message, $headers);
    }

    /**
     * Send booking denial email to user
     *
     * @param int $booking_id Booking ID.
     * @return bool
     */
    public static function send_user_denial($booking_id)
    {
        $booking = IRBSFH_Database::get_booking($booking_id);
        if (! $booking) {
            return false;
        }

        $room = IRBSFH_Database::get_room($booking->room_id);
        $subject = self::parse_template_tags(
            IRBSFH_Settings::get('email_user_denial_subject', __('Booking Request Declined', 'intelligent-room-booking-system-for-hotel')),
            $booking,
            $room
        );

        $message = self::parse_template_tags(
            IRBSFH_Settings::get('email_user_denial_body', self::get_default_user_denial_template()),
            $booking,
            $room
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($booking->email, $subject, $message, $headers);
    }

    /**
     * Send new booking notification to admin
     *
     * @param int $booking_id Booking ID.
     * @return bool
     */
    public static function send_admin_notification($booking_id)
    {
        $booking = IRBSFH_Database::get_booking($booking_id);
        if (! $booking) {
            return false;
        }

        $room = IRBSFH_Database::get_room($booking->room_id);
        $admin_email = IRBSFH_Settings::get('admin_email', get_option('admin_email'));

        $subject = self::parse_template_tags(
            IRBSFH_Settings::get('email_admin_notification_subject', __('New Booking Request', 'intelligent-room-booking-system-for-hotel')),
            $booking,
            $room
        );

        $message = self::parse_template_tags(
            IRBSFH_Settings::get('email_admin_notification_body', self::get_default_admin_notification_template()),
            $booking,
            $room
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($admin_email, $subject, $message, $headers);
    }

    /**
     * Parse template tags in email content
     *
     * @param string $content Email content.
     * @param object $booking Booking object.
     * @param object $room Room object.
     * @return string
     */
    private static function parse_template_tags($content, $booking, $room)
    {
        $tags = array(
            '{first_name}' => esc_html($booking->first_name),
            '{last_name}' => esc_html($booking->last_name),
            '{email}' => esc_html($booking->email),
            '{phone}' => esc_html($booking->phone),
            '{room_name}' => esc_html($room->name),
            '{booking_title}' => esc_html($room->booking_title),
            '{booking_date}' => esc_html(date_i18n(get_option('date_format'), strtotime($booking->booking_date))),
            '{booking_time}' => $booking->start_time ? esc_html(date_i18n(get_option('time_format'), strtotime($booking->start_time))) : __('All day', 'intelligent-room-booking-system-for-hotel'),
            '{booking_id}' => absint($booking->id),
            '{notes}' => esc_html($booking->notes),
            '{status}' => esc_html(ucfirst($booking->status)),
            '{site_name}' => esc_html(get_bloginfo('name')),
            '{site_url}' => esc_url(home_url()),
            '{manage_url}' => esc_url(admin_url('admin.php?page=irbsfh-bookings&action=edit&id=' . $booking->id)),
        );

        return str_replace(array_keys($tags), array_values($tags), $content);
    }

    /**
     * Get default user confirmation email template
     *
     * @return string
     */
    private static function get_default_user_confirmation_template()
    {
        $template = '<html><body>';
        $template .= '<p>' . __('Dear {first_name} {last_name},', 'intelligent-room-booking-system-for-hotel') . '</p>';
        $template .= '<p>' . __('Your booking has been confirmed!', 'intelligent-room-booking-system-for-hotel') . '</p>';
        $template .= '<p><strong>' . __('Booking Details:', 'intelligent-room-booking-system-for-hotel') . '</strong></p>';
        $template .= '<ul>';
        $template .= '<li>' . __('Booking ID:', 'intelligent-room-booking-system-for-hotel') . ' {booking_id}</li>';
        $template .= '<li>' . __('Room:', 'intelligent-room-booking-system-for-hotel') . ' {room_name}</li>';
        $template .= '<li>' . __('Date:', 'intelligent-room-booking-system-for-hotel') . ' {booking_date}</li>';
        $template .= '<li>' . __('Time:', 'intelligent-room-booking-system-for-hotel') . ' {booking_time}</li>';
        $template .= '</ul>';
        $template .= '<p>' . __('Thank you for your booking!', 'intelligent-room-booking-system-for-hotel') . '</p>';
        $template .= '<p>' . __('Best regards,', 'intelligent-room-booking-system-for-hotel') . '<br>{site_name}</p>';
        $template .= '</body></html>';

        return $template;
    }

    /**
     * Get default user denial email template
     *
     * @return string
     */
    private static function get_default_user_denial_template()
    {
        $template = '<html><body>';
        $template .= '<p>' . __('Dear {first_name} {last_name},', 'intelligent-room-booking-system-for-hotel') . '</p>';
        $template .= '<p>' . __('We regret to inform you that your booking request has been declined.', 'intelligent-room-booking-system-for-hotel') . '</p>';
        $template .= '<p><strong>' . __('Booking Details:', 'intelligent-room-booking-system-for-hotel') . '</strong></p>';
        $template .= '<ul>';
        $template .= '<li>' . __('Booking ID:', 'intelligent-room-booking-system-for-hotel') . ' {booking_id}</li>';
        $template .= '<li>' . __('Room:', 'intelligent-room-booking-system-for-hotel') . ' {room_name}</li>';
        $template .= '<li>' . __('Date:', 'intelligent-room-booking-system-for-hotel') . ' {booking_date}</li>';
        $template .= '</ul>';
        $template .= '<p>' . __('Please contact us if you have any questions.', 'intelligent-room-booking-system-for-hotel') . '</p>';
        $template .= '<p>' . __('Best regards,', 'intelligent-room-booking-system-for-hotel') . '<br>{site_name}</p>';
        $template .= '</body></html>';

        return $template;
    }

    /**
     * Get default admin notification email template
     *
     * @return string
     */
    private static function get_default_admin_notification_template()
    {
        $template = '<html><body>';
        $template .= '<p>' . __('A new booking request has been received.', 'intelligent-room-booking-system-for-hotel') . '</p>';
        $template .= '<p><strong>' . __('Booking Details:', 'intelligent-room-booking-system-for-hotel') . '</strong></p>';
        $template .= '<ul>';
        $template .= '<li>' . __('Booking ID:', 'intelligent-room-booking-system-for-hotel') . ' {booking_id}</li>';
        $template .= '<li>' . __('Customer:', 'intelligent-room-booking-system-for-hotel') . ' {first_name} {last_name}</li>';
        $template .= '<li>' . __('Email:', 'intelligent-room-booking-system-for-hotel') . ' {email}</li>';
        $template .= '<li>' . __('Phone:', 'intelligent-room-booking-system-for-hotel') . ' {phone}</li>';
        $template .= '<li>' . __('Room:', 'intelligent-room-booking-system-for-hotel') . ' {room_name}</li>';
        $template .= '<li>' . __('Date:', 'intelligent-room-booking-system-for-hotel') . ' {booking_date}</li>';
        $template .= '<li>' . __('Time:', 'intelligent-room-booking-system-for-hotel') . ' {booking_time}</li>';
        $template .= '<li>' . __('Status:', 'intelligent-room-booking-system-for-hotel') . ' {status}</li>';
        $template .= '</ul>';
        $template .= '<p><a href="{manage_url}">' . __('Manage this booking', 'intelligent-room-booking-system-for-hotel') . '</a></p>';
        $template .= '</body></html>';

        return $template;
    }
}
