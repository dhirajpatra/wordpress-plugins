<?php

/**
 * Login Widget
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Login Widget Class
 */
class IRBSFH_Login_Widget extends WP_Widget
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'irbsfh_login_widget',
            __('Booking Login', 'intelligent-room-booking-system-for-hotel'),
            array(
                'description' => __('Login form for booking system users', 'intelligent-room-booking-system-for-hotel'),
            )
        );
    }

    /**
     * Widget output
     *
     * @param array $args Widget arguments.
     * @param array $instance Widget instance.
     */
    public function widget($args, $instance)
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget arguments are trusted
        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : __('Login', 'intelligent-room-booking-system-for-hotel');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget arguments are trusted
        echo $args['before_title'] . esc_html(apply_filters('widget_title', $title)) . $args['after_title'];

        if (is_user_logged_in()) {
            $this->display_logged_in_content($instance);
        } else {
            $this->display_login_form($instance);
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget arguments are trusted
        echo $args['after_widget'];
    }

    /**
     * Display login form
     *
     * @param array $instance Widget instance.
     */
    private function display_login_form($instance)
    {
        $redirect = ! empty($instance['redirect']) ? esc_url($instance['redirect']) : '';
?>
        <div class="irbsfh-login-widget">
            <form name="loginform" id="irbsfh-loginform" action="<?php echo esc_url(wp_login_url($redirect)); ?>" method="post">
                <div class="irbsfh-form-group">
                    <label for="irbsfh-user-login"><?php esc_html_e('Username or Email', 'intelligent-room-booking-system-for-hotel'); ?></label>
                    <input type="text" name="log" id="irbsfh-user-login" class="input" required>
                </div>
                <div class="irbsfh-form-group">
                    <label for="irbsfh-user-pass"><?php esc_html_e('Password', 'intelligent-room-booking-system-for-hotel'); ?></label>
                    <input type="password" name="pwd" id="irbsfh-user-pass" class="input" required>
                </div>
                <div class="irbsfh-form-group">
                    <label>
                        <input name="rememberme" type="checkbox" id="irbsfh-rememberme" value="forever">
                        <?php esc_html_e('Remember Me', 'intelligent-room-booking-system-for-hotel'); ?>
                    </label>
                </div>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect); ?>">
                <button type="submit" name="wp-submit" id="irbsfh-wp-submit" class="irbsfh-btn irbsfh-btn-primary">
                    <?php esc_html_e('Log In', 'intelligent-room-booking-system-for-hotel'); ?>
                </button>
            </form>

            <?php if (get_option('users_can_register')) : ?>
                <div class="irbsfh-register-link">
                    <a href="<?php echo esc_url(wp_registration_url()); ?>">
                        <?php esc_html_e('Register', 'intelligent-room-booking-system-for-hotel'); ?>
                    </a>
                    |
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">
                        <?php esc_html_e('Lost your password?', 'intelligent-room-booking-system-for-hotel'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php
    }

    /**
     * Display logged in content
     *
     * @param array $instance Widget instance.
     */
    private function display_logged_in_content($instance)
    {
        $current_user = wp_get_current_user();
        $dashboard_url = ! empty($instance['dashboard_url']) ? esc_url($instance['dashboard_url']) : home_url('/my-bookings/');
    ?>
        <div class="irbsfh-login-widget">
            <p>
                <?php
                printf(
                    /* translators: %s: user display name */
                    esc_html__('Welcome, %s!', 'intelligent-room-booking-system-for-hotel'),
                    '<strong>' . esc_html($current_user->display_name) . '</strong>'
                );
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url($dashboard_url); ?>" class="irbsfh-btn irbsfh-btn-primary">
                    <?php esc_html_e('My Bookings', 'intelligent-room-booking-system-for-hotel'); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">
                    <?php esc_html_e('Logout', 'intelligent-room-booking-system-for-hotel'); ?>
                </a>
            </p>
        </div>
    <?php
    }

    /**
     * Widget form
     *
     * @param array $instance Widget instance.
     */
    public function form($instance)
    {
        $title = ! empty($instance['title']) ? $instance['title'] : __('Login', 'intelligent-room-booking-system-for-hotel');
        $redirect = ! empty($instance['redirect']) ? $instance['redirect'] : '';
        $dashboard_url = ! empty($instance['dashboard_url']) ? $instance['dashboard_url'] : '';
    ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'intelligent-room-booking-system-for-hotel'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('redirect')); ?>">
                <?php esc_html_e('Redirect URL after login:', 'intelligent-room-booking-system-for-hotel'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('redirect')); ?>"
                name="<?php echo esc_attr($this->get_field_name('redirect')); ?>"
                type="text" value="<?php echo esc_attr($redirect); ?>"
                placeholder="<?php echo esc_attr(home_url()); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('dashboard_url')); ?>">
                <?php esc_html_e('Dashboard URL:', 'intelligent-room-booking-system-for-hotel'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('dashboard_url')); ?>"
                name="<?php echo esc_attr($this->get_field_name('dashboard_url')); ?>"
                type="text" value="<?php echo esc_attr($dashboard_url); ?>"
                placeholder="<?php echo esc_attr(home_url('/my-bookings/')); ?>">
            <small><?php esc_html_e('URL to display for logged-in users', 'intelligent-room-booking-system-for-hotel'); ?></small>
        </p>
<?php
    }

    /**
     * Update widget
     *
     * @param array $new_instance New instance.
     * @param array $old_instance Old instance.
     * @return array
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = ! empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['redirect'] = ! empty($new_instance['redirect']) ? esc_url_raw($new_instance['redirect']) : '';
        $instance['dashboard_url'] = ! empty($new_instance['dashboard_url']) ? esc_url_raw($new_instance['dashboard_url']) : '';

        return $instance;
    }
}
