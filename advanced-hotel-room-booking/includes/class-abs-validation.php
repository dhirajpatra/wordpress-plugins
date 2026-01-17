<?php
/**
 * Validation handler class
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Validation class
 */
class ABS_Validation {

    /**
     * Validate booking form data
     *
     * @param array $data Form data.
     * @return array Array with 'valid' boolean and 'errors' array.
     */
    public static function validate_booking_form( $data ) {
        $errors = array();

        // Validate first name
        if ( empty( $data['first_name'] ) ) {
            $errors['first_name'] = __( 'First name is required.', 'advanced-hotel-room-booking' );
        } elseif ( strlen( $data['first_name'] ) > 100 ) {
            $errors['first_name'] = __( 'First name must be less than 100 characters.', 'advanced-hotel-room-booking' );
        }

        // Validate last name
        if ( empty( $data['last_name'] ) ) {
            $errors['last_name'] = __( 'Last name is required.', 'advanced-hotel-room-booking' );
        } elseif ( strlen( $data['last_name'] ) > 100 ) {
            $errors['last_name'] = __( 'Last name must be less than 100 characters.', 'advanced-hotel-room-booking' );
        }

        // Validate email
        if ( empty( $data['email'] ) ) {
            $errors['email'] = __( 'Email address is required.', 'advanced-hotel-room-booking' );
        } elseif ( ! is_email( $data['email'] ) ) {
            $errors['email'] = __( 'Please enter a valid email address.', 'advanced-hotel-room-booking' );
        }

        // Validate phone
        if ( empty( $data['phone'] ) ) {
            $errors['phone'] = __( 'Phone number is required.', 'advanced-hotel-room-booking' );
        } elseif ( ! self::validate_phone( $data['phone'] ) ) {
            $errors['phone'] = __( 'Please enter a valid phone number.', 'advanced-hotel-room-booking' );
        }

        // Validate room ID
        if ( empty( $data['room_id'] ) || ! is_numeric( $data['room_id'] ) ) {
            $errors['room_id'] = __( 'Please select a valid room.', 'advanced-hotel-room-booking' );
        } else {
            $room = ABS_Database::get_room( intval( $data['room_id'] ) );
            if ( ! $room ) {
                $errors['room_id'] = __( 'Selected room does not exist.', 'advanced-hotel-room-booking' );
            }
        }

        // Validate booking date
        if ( empty( $data['booking_date'] ) ) {
            $errors['booking_date'] = __( 'Booking date is required.', 'advanced-hotel-room-booking' );
        } else {
            $date_validation = self::validate_booking_date( $data['booking_date'] );
            if ( ! $date_validation['valid'] ) {
                $errors['booking_date'] = $date_validation['message'];
            }
        }

        return array(
            'valid' => empty( $errors ),
            'errors' => $errors,
        );
    }

    /**
     * Validate phone number
     *
     * @param string $phone Phone number.
     * @return bool
     */
    public static function validate_phone( $phone ) {
        // Remove common formatting characters
        $cleaned = preg_replace( '/[\s\-\(\)\+]/', '', $phone );
        
        // Check if it contains only digits and has reasonable length
        return preg_match( '/^\d{7,15}$/', $cleaned );
    }

    /**
     * Validate booking date
     *
     * @param string $date Date string.
     * @return array
     */
    public static function validate_booking_date( $date ) {
        // Validate date format
        $date_obj = DateTime::createFromFormat( 'Y-m-d', $date );
        if ( ! $date_obj || $date_obj->format( 'Y-m-d' ) !== $date ) {
            return array(
                'valid' => false,
                'message' => __( 'Invalid date format.', 'advanced-hotel-room-booking' ),
            );
        }

        // Check if date is in the past
        $today = new DateTime( 'today' );
        if ( $date_obj < $today ) {
            return array(
                'valid' => false,
                'message' => __( 'Cannot book dates in the past.', 'advanced-hotel-room-booking' ),
            );
        }

        // Check closed days (e.g., Sundays)
        $closed_days = ABS_Settings::get( 'closed_days', array() );
        $day_of_week = $date_obj->format( 'w' ); // 0 = Sunday, 6 = Saturday
        
        if ( in_array( $day_of_week, $closed_days ) ) {
            $day_names = array(
                0 => __( 'Sunday', 'advanced-hotel-room-booking' ),
                1 => __( 'Monday', 'advanced-hotel-room-booking' ),
                2 => __( 'Tuesday', 'advanced-hotel-room-booking' ),
                3 => __( 'Wednesday', 'advanced-hotel-room-booking' ),
                4 => __( 'Thursday', 'advanced-hotel-room-booking' ),
                5 => __( 'Friday', 'advanced-hotel-room-booking' ),
                6 => __( 'Saturday', 'advanced-hotel-room-booking' ),
            );
            
            return array(
                'valid' => false,
                'message' => sprintf(
                    /* translators: %s: day name */
                    __( 'Bookings are not available on %s.', 'advanced-hotel-room-booking' ),
                    $day_names[ $day_of_week ]
                ),
            );
        }

        return array(
            'valid' => true,
            'message' => '',
        );
    }

    /**
     * Check if user can make booking
     *
     * @param int $user_id User ID.
     * @param int $room_id Room ID.
     * @return array
     */
    public static function can_user_book( $user_id, $room_id ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return array(
                'can_book' => false,
                'message' => __( 'You must be logged in to make a booking.', 'advanced-hotel-room-booking' ),
            );
        }

        // Get room details
        $room = ABS_Database::get_room( $room_id );
        if ( ! $room ) {
            return array(
                'can_book' => false,
                'message' => __( 'Invalid room selected.', 'advanced-hotel-room-booking' ),
            );
        }

        // Check booking limit
        $max_bookings = isset( $room->max_bookings_per_user ) ? intval( $room->max_bookings_per_user ) : 3;
        $current_bookings = ABS_Database::count_user_active_bookings( $user_id, $room_id );
        
        if ( $current_bookings >= $max_bookings ) {
            return array(
                'can_book' => false,
                'message' => sprintf(
                    /* translators: %d: maximum number of bookings */
                    __( 'You have reached the maximum of %d active bookings for this room.', 'advanced-hotel-room-booking' ),
                    $max_bookings
                ),
            );
        }

        return array(
            'can_book' => true,
            'message' => '',
        );
    }

    /**
     * Check if room is available for date
     *
     * @param int    $room_id Room ID.
     * @param string $date Date in Y-m-d format.
     * @param int    $exclude_booking_id Booking ID to exclude from check.
     * @return array
     */
    public static function is_room_available( $room_id, $date, $exclude_booking_id = null ) {
        $bookings = ABS_Database::get_room_bookings( $room_id, $date );
        
        if ( $exclude_booking_id ) {
            $bookings = array_filter( $bookings, function( $booking ) use ( $exclude_booking_id ) {
                return intval( $booking->id ) !== intval( $exclude_booking_id );
            });
        }
        
        // Check for confirmed bookings
        $confirmed_bookings = array_filter( $bookings, function( $booking ) {
            return 'confirmed' === $booking->status;
        });
        
        if ( ! empty( $confirmed_bookings ) ) {
            return array(
                'available' => false,
                'message' => __( 'This room is already booked for the selected date.', 'advanced-hotel-room-booking' ),
            );
        }
        
        return array(
            'available' => true,
            'message' => '',
        );
    }

    /**
     * Sanitize booking data
     *
     * @param array $data Raw booking data.
     * @return array
     */
    public static function sanitize_booking_data( $data ) {
        return array(
            'user_id' => isset( $data['user_id'] ) ? absint( $data['user_id'] ) : 0,
            'room_id' => isset( $data['room_id'] ) ? absint( $data['room_id'] ) : 0,
            'booking_date' => isset( $data['booking_date'] ) ? sanitize_text_field( $data['booking_date'] ) : '',
            'start_time' => isset( $data['start_time'] ) ? sanitize_text_field( $data['start_time'] ) : null,
            'end_time' => isset( $data['end_time'] ) ? sanitize_text_field( $data['end_time'] ) : null,
            'first_name' => isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '',
            'last_name' => isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '',
            'phone' => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
            'email' => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
            'notes' => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
            'status' => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'pending',
        );
    }
}