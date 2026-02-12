<?php

/**
 * Public-facing handler
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Public class
 */
class IRBSFH_Public
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('irbsfh_booking_calendar', array($this, 'booking_calendar_shortcode'));
        add_shortcode('irbsfh_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('irbsfh_room_list', array($this, 'room_list_shortcode'));
        add_shortcode('irbsfh_user_bookings', array($this, 'user_bookings_shortcode'));
        add_shortcode('irbsfh_login_widget', array($this, 'login_widget_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_irbsfh_load_calendar', array($this, 'ajax_load_calendar'));
        add_action('wp_ajax_nopriv_irbsfh_load_calendar', array($this, 'ajax_load_calendar'));
        add_action('wp_ajax_irbsfh_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_irbsfh_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_irbsfh_cancel_booking', array($this, 'ajax_cancel_booking'));
    }

    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_scripts()
    {
        wp_enqueue_style(
            'irbsfh-public-css',
            IRBSFH_PLUGIN_URL . 'assets/css/irbsfh-public.css',
            array(),
            IRBSFH_VERSION
        );

        wp_enqueue_script(
            'irbsfh-public-js',
            IRBSFH_PLUGIN_URL . 'assets/js/irbsfh-public.js',
            array('jquery'),
            IRBSFH_VERSION,
            true
        );

        wp_localize_script(
            'irbsfh-public-js',
            'irbsfhPublic',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('irbsfh_public_nonce'),
                'requiredField' => __('This field is required.', 'intelligent-room-booking-system-for-hotel'),
                'invalidEmail' => __('Please enter a valid email address.', 'intelligent-room-booking-system-for-hotel'),
                'invalidPhone' => __('Please enter a valid phone number.', 'intelligent-room-booking-system-for-hotel'),
                'validationError' => __('Please correct the errors in the form.', 'intelligent-room-booking-system-for-hotel'),
                'errorMessage' => __('An error occurred. Please try again.', 'intelligent-room-booking-system-for-hotel'),
                'submitting' => __('Submitting...', 'intelligent-room-booking-system-for-hotel'),
                'submitLabel' => __('Submit Booking', 'intelligent-room-booking-system-for-hotel'),
                'confirmCancel' => __('Are you sure you want to cancel this booking?', 'intelligent-room-booking-system-for-hotel'),
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
        <div class="irbsfh-calendar-container" data-room-id="<?php echo esc_attr($atts['room_id']); ?>">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is properly escaped in render_calendar method, but using wp_kses_post for extra safety as requested
            echo wp_kses_post($this->render_calendar(current_time('n') - 1, current_time('Y'), $atts['room_id']));
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
            return '<div class="irbsfh-message irbsfh-message-warning">' .
                esc_html__('You must be logged in to make a booking.', 'intelligent-room-booking-system-for-hotel') .
                ' <a href="' . esc_url(wp_login_url(get_permalink())) . '">' .
                esc_html__('Login', 'intelligent-room-booking-system-for-hotel') . '</a></div>';
        }

        $current_user = wp_get_current_user();

        ob_start();
    ?>
        <form class="irbsfh-booking-form" method="post">
            <div class="irbsfh-form-group">
                <label for="irbsfh_room_id"><?php esc_html_e('Select Room', 'intelligent-room-booking-system-for-hotel'); ?> <span class="required">*</span></label>
                <select name="room_id" id="irbsfh_room_id" required>
                    <option value=""><?php esc_html_e('Choose a room...', 'intelligent-room-booking-system-for-hotel'); ?></option>
                    <?php
                    $rooms = IRBSFH_Room::get_all();
                    foreach ($rooms as $room) {
                        echo '<option value="' . esc_attr($room->id) . '">' . esc_html($room->name) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="irbsfh-form-group">
                <label for="irbsfh_booking_date"><?php esc_html_e('Booking Date', 'intelligent-room-booking-system-for-hotel'); ?> <span class="required">*</span></label>
                <input type="date" name="booking_date" id="irbsfh_booking_date" required>
            </div>

            <div class="irbsfh-form-group">
                <label for="irbsfh_first_name"><?php esc_html_e('First Name', 'intelligent-room-booking-system-for-hotel'); ?> <span class="required">*</span></label>
                <input type="text" name="first_name" id="irbsfh_first_name" value="<?php echo esc_attr($current_user->first_name); ?>" required>
            </div>

            <div class="irbsfh-form-group">
                <label for="irbsfh_last_name"><?php esc_html_e('Last Name', 'intelligent-room-booking-system-for-hotel'); ?> <span class="required">*</span></label>
                <input type="text" name="last_name" id="irbsfh_last_name" value="<?php echo esc_attr($current_user->last_name); ?>" required>
            </div>

            <div class="irbsfh-form-group">
                <label for="irbsfh_email"><?php esc_html_e('Email Address', 'intelligent-room-booking-system-for-hotel'); ?> <span class="required">*</span></label>
                <input type="email" name="email" id="irbsfh_email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
            </div>

            <div class="irbsfh-form-group">
                <label for="irbsfh_phone"><?php esc_html_e('Phone Number', 'intelligent-room-booking-system-for-hotel'); ?> <span class="required">*</span></label>
                <input type="tel" name="phone" id="irbsfh_phone" required>
            </div>

            <div class="irbsfh-form-group">
                <label for="irbsfh_notes"><?php esc_html_e('Notes (Optional)', 'intelligent-room-booking-system-for-hotel'); ?></label>
                <textarea name="notes" id="irbsfh_notes" rows="4"></textarea>
            </div>

            <input type="hidden" name="user_id" value="<?php echo esc_attr(get_current_user_id()); ?>">

            <div class="irbsfh-form-actions">
                <button type="submit" class="irbsfh-btn irbsfh-btn-primary"><?php esc_html_e('Submit Booking', 'intelligent-room-booking-system-for-hotel'); ?></button>
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
        $rooms = IRBSFH_Room::get_all();

        if (empty($rooms)) {
            return '<p>' . esc_html__('No rooms available at this time.', 'intelligent-room-booking-system-for-hotel') . '</p>';
        }

        ob_start();
    ?>
        <div class="irbsfh-room-list">
            <?php foreach ($rooms as $room) : ?>
                <div class="irbsfh-room-card" data-room-id="<?php echo esc_attr($room->id); ?>">
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
            return '<div class="irbsfh-message irbsfh-message-warning">' .
                esc_html__('You must be logged in to view your bookings.', 'intelligent-room-booking-system-for-hotel') .
                ' <a href="' . esc_url(wp_login_url(get_permalink())) . '">' .
                esc_html__('Login', 'intelligent-room-booking-system-for-hotel') . '</a></div>';
        }

        $bookings = IRBSFH_Database::get_user_bookings(get_current_user_id());

        if (empty($bookings)) {
            return '<div class="irbsfh-message irbsfh-message-info">' .
                esc_html__('You have no bookings yet.', 'intelligent-room-booking-system-for-hotel') . '</div>';
        }

        ob_start();
    ?>
        <div class="irbsfh-user-bookings">
            <ul class="irbsfh-booking-list">
                <?php foreach ($bookings as $booking) :
                    $room = IRBSFH_Database::get_room($booking->room_id);
                ?>
                    <li class="irbsfh-booking-item status-<?php echo esc_attr($booking->status); ?>">
                        <div class="irbsfh-booking-header">
                            <h4><?php echo esc_html($room ? $room->name : __('Unknown Room', 'intelligent-room-booking-system-for-hotel')); ?></h4>
                            <span class="irbsfh-booking-status <?php echo esc_attr($booking->status); ?>">
                                <?php echo esc_html(ucfirst($booking->status)); ?>
                            </span>
                        </div>
                        <div class="irbsfh-booking-details">
                            <p><strong><?php esc_html_e('Date:', 'intelligent-room-booking-system-for-hotel'); ?></strong>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($booking->booking_date))); ?>
                            </p>
                            <p><strong><?php esc_html_e('Booking ID:', 'intelligent-room-booking-system-for-hotel'); ?></strong>
                                #<?php echo esc_html($booking->id); ?>
                            </p>
                            <?php if ('pending' === $booking->status) : ?>
                                <p>
                                    <button class="irbsfh-btn irbsfh-btn-secondary irbsfh-cancel-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                                        <?php esc_html_e('Cancel Booking', 'intelligent-room-booking-system-for-hotel'); ?>
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
        $widget = new IRBSFH_Login_Widget();
        $instance = shortcode_atts(array(
            'title' => __('Login', 'intelligent-room-booking-system-for-hotel'),
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
        <div class="irbsfh-calendar-header">
            <h3><?php echo esc_html($month_name); ?></h3>
            <div class="irbsfh-calendar-nav">
                <button class="irbsfh-calendar-prev"><?php esc_html_e('Previous', 'intelligent-room-booking-system-for-hotel'); ?></button>
                <button class="irbsfh-calendar-next"><?php esc_html_e('Next', 'intelligent-room-booking-system-for-hotel'); ?></button>
            </div>
        </div>
        <table class="irbsfh-calendar-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Sun', 'intelligent-room-booking-system-for-hotel'); ?></th>
                    <th><?php esc_html_e('Mon', 'intelligent-room-booking-system-for-hotel'); ?></th>
                    <th><?php esc_html_e('Tue', 'intelligent-room-booking-system-for-hotel'); ?></th>
                    <th><?php esc_html_e('Wed', 'intelligent-room-booking-system-for-hotel'); ?></th>
                    <th><?php esc_html_e('Thu', 'intelligent-room-booking-system-for-hotel'); ?></th>
                    <th><?php esc_html_e('Fri', 'intelligent-room-booking-system-for-hotel'); ?></th>
                    <th><?php esc_html_e('Sat', 'intelligent-room-booking-system-for-hotel'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $day = 1;
                $today = current_time('Y-m-d');
                $closed_days = IRBSFH_Settings::get('closed_days', array());

                for ($week = 0; $week < 6; $week++) {
                    echo '<tr>';
                    for ($dow = 0; $dow < 7; $dow++) {
                        if (($week === 0 && $dow < $start_day) || $day > $days_in_month) {
                            echo '<td class="irbsfh-date-other-month"></td>';
                        } else {
                            $date = sprintf('%04d-%02d-%02d', $year, $month + 1, $day);
                            $is_past = $date < $today;
                            $is_closed = in_array($dow, $closed_days);

                            $classes = array('irbsfh-date');
                            if ($is_past) {
                                $classes[] = 'irbsfh-date-past';
                            } elseif ($is_closed) {
                                $classes[] = 'irbsfh-date-closed';
                            } else {
                                $classes[] = 'irbsfh-date-available';
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
        check_ajax_referer('irbsfh_public_nonce', 'nonce');

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
        check_ajax_referer('irbsfh_public_nonce', 'nonce');

        $room_id = isset($_POST['room_id']) ? absint($_POST['room_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';

        $result = IRBSFH_Validation::is_room_available($room_id, $date);

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
        check_ajax_referer('irbsfh_public_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to make a booking.', 'intelligent-room-booking-system-for-hotel'),
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

        $result = IRBSFH_Booking::create($data);

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
        check_ajax_referer('irbsfh_public_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $result = IRBSFH_Booking::cancel($booking_id, get_current_user_id());

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
