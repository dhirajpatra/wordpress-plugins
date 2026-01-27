<?php

/**
 * Public-facing handler
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Public class
 */
class ABS_Public
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('abs_booking_calendar', array($this, 'booking_calendar_shortcode'));
        add_shortcode('abs_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('abs_room_list', array($this, 'room_list_shortcode'));
        add_shortcode('abs_user_bookings', array($this, 'user_bookings_shortcode'));
        add_shortcode('abs_login_widget', array($this, 'login_widget_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_abs_load_calendar', array($this, 'ajax_load_calendar'));
        add_action('wp_ajax_nopriv_abs_load_calendar', array($this, 'ajax_load_calendar'));
        add_action('wp_ajax_abs_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_abs_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_abs_cancel_booking', array($this, 'ajax_cancel_booking'));
    }

    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_scripts()
    {
        wp_enqueue_style(
            'abs-public-css',
            ABS_PLUGIN_URL . 'assets/css/abs-public.css',
            array(),
            ABS_VERSION
        );

        wp_enqueue_script(
            'abs-public-js',
            ABS_PLUGIN_URL . 'assets/js/abs-public.js',
            array('jquery'),
            ABS_VERSION,
            true
        );

        wp_localize_script(
            'abs-public-js',
            'absPublic',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('abs_public_nonce'),
                'requiredField' => __('This field is required.', 'advanced-hotel-room-booking-system'),
                'invalidEmail' => __('Please enter a valid email address.', 'advanced-hotel-room-booking-system'),
                'invalidPhone' => __('Please enter a valid phone number.', 'advanced-hotel-room-booking-system'),
                'validationError' => __('Please correct the errors in the form.', 'advanced-hotel-room-booking-system'),
                'errorMessage' => __('An error occurred. Please try again.', 'advanced-hotel-room-booking-system'),
                'submitting' => __('Submitting...', 'advanced-hotel-room-booking-system'),
                'submitLabel' => __('Submit Booking', 'advanced-hotel-room-booking-system'),
                'confirmCancel' => __('Are you sure you want to cancel this booking?', 'advanced-hotel-room-booking-system'),
            )
        );
    }

    /**
     * Booking calendar shortcode
     */
    public function booking_calendar_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'room_id' => '',
        ), $atts);

        ob_start();
?>
        <div class="abs-calendar-container" data-room-id="<?php echo esc_attr($atts['room_id']); ?>">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is properly escaped in render_calendar method
            echo $this->render_calendar(current_time('n') - 1, current_time('Y'), $atts['room_id']);
            ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<div class="abs-message abs-message-warning">' .
                esc_html__('You must be logged in to make a booking.', 'advanced-hotel-room-booking-system') .
                ' <a href="' . esc_url(wp_login_url(get_permalink())) . '">' .
                esc_html__('Login', 'advanced-hotel-room-booking-system') . '</a></div>';
        }

        $current_user = wp_get_current_user();

        ob_start();
    ?>
        <form class="abs-booking-form" method="post">
            <div class="abs-form-group">
                <label for="abs_room_id"><?php esc_html_e('Select Room', 'advanced-hotel-room-booking-system'); ?> <span class="required">*</span></label>
                <select name="room_id" id="abs_room_id" required>
                    <option value=""><?php esc_html_e('Choose a room...', 'advanced-hotel-room-booking-system'); ?></option>
                    <?php
                    $rooms = ABS_Room::get_all();
                    foreach ($rooms as $room) {
                        echo '<option value="' . esc_attr($room->id) . '">' . esc_html($room->name) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="abs-form-group">
                <label for="abs_booking_date"><?php esc_html_e('Booking Date', 'advanced-hotel-room-booking-system'); ?> <span class="required">*</span></label>
                <input type="date" name="booking_date" id="abs_booking_date" required>
            </div>

            <div class="abs-form-group">
                <label for="abs_first_name"><?php esc_html_e('First Name', 'advanced-hotel-room-booking-system'); ?> <span class="required">*</span></label>
                <input type="text" name="first_name" id="abs_first_name" value="<?php echo esc_attr($current_user->first_name); ?>" required>
            </div>

            <div class="abs-form-group">
                <label for="abs_last_name"><?php esc_html_e('Last Name', 'advanced-hotel-room-booking-system'); ?> <span class="required">*</span></label>
                <input type="text" name="last_name" id="abs_last_name" value="<?php echo esc_attr($current_user->last_name); ?>" required>
            </div>

            <div class="abs-form-group">
                <label for="abs_email"><?php esc_html_e('Email Address', 'advanced-hotel-room-booking-system'); ?> <span class="required">*</span></label>
                <input type="email" name="email" id="abs_email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
            </div>

            <div class="abs-form-group">
                <label for="abs_phone"><?php esc_html_e('Phone Number', 'advanced-hotel-room-booking-system'); ?> <span class="required">*</span></label>
                <input type="tel" name="phone" id="abs_phone" required>
            </div>

            <div class="abs-form-group">
                <label for="abs_notes"><?php esc_html_e('Notes (Optional)', 'advanced-hotel-room-booking-system'); ?></label>
                <textarea name="notes" id="abs_notes" rows="4"></textarea>
            </div>

            <input type="hidden" name="user_id" value="<?php echo esc_attr(get_current_user_id()); ?>">

            <div class="abs-form-actions">
                <button type="submit" class="abs-btn abs-btn-primary"><?php esc_html_e('Submit Booking', 'advanced-hotel-room-booking-system'); ?></button>
            </div>
        </form>
    <?php
        return ob_get_clean();
    }

    /**
     * Room list shortcode
     */
    public function room_list_shortcode($atts)
    {
        $rooms = ABS_Room::get_all();

        if (empty($rooms)) {
            return '<p>' . esc_html__('No rooms available at this time.', 'advanced-hotel-room-booking-system') . '</p>';
        }

        ob_start();
    ?>
        <div class="abs-room-list">
            <?php foreach ($rooms as $room) : ?>
                <div class="abs-room-card" data-room-id="<?php echo esc_attr($room->id); ?>">
                    <h3><?php echo esc_html($room->name); ?></h3>
                    <?php if (! empty($room->description)) : ?>
                        <p><?php echo esc_html($room->description); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * User bookings shortcode
     */
    public function user_bookings_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<div class="abs-message abs-message-warning">' .
                esc_html__('You must be logged in to view your bookings.', 'advanced-hotel-room-booking-system') .
                ' <a href="' . esc_url(wp_login_url(get_permalink())) . '">' .
                esc_html__('Login', 'advanced-hotel-room-booking-system') . '</a></div>';
        }

        $bookings = ABS_Database::get_user_bookings(get_current_user_id());

        if (empty($bookings)) {
            return '<div class="abs-message abs-message-info">' .
                esc_html__('You have no bookings yet.', 'advanced-hotel-room-booking-system') . '</div>';
        }

        ob_start();
    ?>
        <div class="abs-user-bookings">
            <ul class="abs-booking-list">
                <?php foreach ($bookings as $booking) :
                    $room = ABS_Database::get_room($booking->room_id);
                ?>
                    <li class="abs-booking-item status-<?php echo esc_attr($booking->status); ?>">
                        <div class="abs-booking-header">
                            <h4><?php echo esc_html($room ? $room->name : __('Unknown Room', 'advanced-hotel-room-booking-system')); ?></h4>
                            <span class="abs-booking-status <?php echo esc_attr($booking->status); ?>">
                                <?php echo esc_html(ucfirst($booking->status)); ?>
                            </span>
                        </div>
                        <div class="abs-booking-details">
                            <p><strong><?php esc_html_e('Date:', 'advanced-hotel-room-booking-system'); ?></strong>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($booking->booking_date))); ?>
                            </p>
                            <p><strong><?php esc_html_e('Booking ID:', 'advanced-hotel-room-booking-system'); ?></strong>
                                #<?php echo esc_html($booking->id); ?>
                            </p>
                            <?php if ('pending' === $booking->status) : ?>
                                <p>
                                    <button class="abs-btn abs-btn-secondary abs-cancel-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                                        <?php esc_html_e('Cancel Booking', 'advanced-hotel-room-booking-system'); ?>
                                    </button>
                                </p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Login widget shortcode
     */
    public function login_widget_shortcode($atts)
    {
        $widget = new ABS_Login_Widget();
        $instance = shortcode_atts(array(
            'title' => __('Login', 'advanced-hotel-room-booking-system'),
            'redirect' => home_url(),
            'dashboard_url' => home_url('/my-bookings/'),
        ), $atts);

        ob_start();
        $widget->widget(
            array('before_widget' => '', 'after_widget' => '', 'before_title' => '<h3>', 'after_title' => '</h3>'),
            $instance
        );
        return ob_get_clean();
    }

    /**
     * Render calendar
     *
     * @param int    $month Month (0-11).
     * @param int    $year Year.
     * @param string $room_id Room ID.
     * @return string
     */
    private function render_calendar($month, $year, $room_id = '')
    {
        $month = intval($month);
        $year = intval($year);

        $first_day = gmmktime(0, 0, 0, $month + 1, 1, $year);
        $days_in_month = gmdate('t', $first_day);
        $start_day = gmdate('w', $first_day);

        $month_name = date_i18n('F Y', $first_day);

        ob_start();
    ?>
        <div class="abs-calendar-header">
            <h3><?php echo esc_html($month_name); ?></h3>
            <div class="abs-calendar-nav">
                <button class="abs-calendar-prev"><?php esc_html_e('Previous', 'advanced-hotel-room-booking-system'); ?></button>
                <button class="abs-calendar-next"><?php esc_html_e('Next', 'advanced-hotel-room-booking-system'); ?></button>
            </div>
        </div>
        <table class="abs-calendar-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Sun', 'advanced-hotel-room-booking-system'); ?></th>
                    <th><?php esc_html_e('Mon', 'advanced-hotel-room-booking-system'); ?></th>
                    <th><?php esc_html_e('Tue', 'advanced-hotel-room-booking-system'); ?></th>
                    <th><?php esc_html_e('Wed', 'advanced-hotel-room-booking-system'); ?></th>
                    <th><?php esc_html_e('Thu', 'advanced-hotel-room-booking-system'); ?></th>
                    <th><?php esc_html_e('Fri', 'advanced-hotel-room-booking-system'); ?></th>
                    <th><?php esc_html_e('Sat', 'advanced-hotel-room-booking-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $day = 1;
                $today = current_time('Y-m-d');
                $closed_days = ABS_Settings::get('closed_days', array());

                for ($week = 0; $week < 6; $week++) {
                    echo '<tr>';
                    for ($dow = 0; $dow < 7; $dow++) {
                        if (($week === 0 && $dow < $start_day) || $day > $days_in_month) {
                            echo '<td class="abs-date-other-month"></td>';
                        } else {
                            $date = sprintf('%04d-%02d-%02d', $year, $month + 1, $day);
                            $is_past = $date < $today;
                            $is_closed = in_array($dow, $closed_days);

                            $classes = array('abs-date');
                            if ($is_past) {
                                $classes[] = 'abs-date-past';
                            } elseif ($is_closed) {
                                $classes[] = 'abs-date-closed';
                            } else {
                                $classes[] = 'abs-date-available';
                            }

                            echo '<td class="' . esc_attr(implode(' ', $classes)) . '" data-date="' . esc_attr($date) . '">';
                            echo esc_html($day);
                            echo '</td>';
                            $day++;
                        }
                    }
                    echo '</tr>';

                    if ($day > $days_in_month) {
                        break;
                    }
                }
                ?>
            </tbody>
        </table>
<?php
        return ob_get_clean();
    }

    /**
     * AJAX: Load calendar
     */
    public function ajax_load_calendar()
    {
        check_ajax_referer('abs_public_nonce', 'nonce');

        $month = isset($_POST['month']) ? intval($_POST['month']) : current_time('n') - 1;
        $year = isset($_POST['year']) ? intval($_POST['year']) : current_time('Y');
        $room_id = isset($_POST['room_id']) ? sanitize_text_field(wp_unslash($_POST['room_id'])) : '';

        $html = $this->render_calendar($month, $year, $room_id);

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Check availability
     */
    public function ajax_check_availability()
    {
        check_ajax_referer('abs_public_nonce', 'nonce');

        $room_id = isset($_POST['room_id']) ? absint($_POST['room_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';

        $result = ABS_Validation::is_room_available($room_id, $date);

        if ($result['available']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Submit booking
     */
    public function ajax_submit_booking()
    {
        check_ajax_referer('abs_public_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to make a booking.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $data = array(
            'user_id' => get_current_user_id(),
            'room_id' => isset($_POST['room_id']) ? absint($_POST['room_id']) : 0,
            'booking_date' => isset($_POST['booking_date']) ? sanitize_text_field(wp_unslash($_POST['booking_date'])) : '',
            'first_name' => isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '',
            'last_name' => isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '',
            'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
            'phone' => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
            'notes' => isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '',
        );

        $result = ABS_Booking::create($data);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Cancel booking
     */
    public function ajax_cancel_booking()
    {
        check_ajax_referer('abs_public_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $result = ABS_Booking::cancel($booking_id, get_current_user_id());

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
