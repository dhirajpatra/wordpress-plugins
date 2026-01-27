<?php

/**
 * Login Widget
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Login Widget Class
 */
class ABS_Login_Widget extends WP_Widget
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'abs_login_widget',
            __('Booking Login', 'advanced-hotel-room-booking-system'),
            array(
                'description' => __('Login form for booking system users', 'advanced-hotel-room-booking-system'),
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

        $title = ! empty($instance['title']) ? $instance['title'] : __('Login', 'advanced-hotel-room-booking-system');
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
        <div class="abs-login-widget">
            <form name="loginform" id="abs-loginform" action="<?php echo esc_url(wp_login_url($redirect)); ?>" method="post">
                <div class="abs-form-group">
                    <label for="abs-user-login"><?php esc_html_e('Username or Email', 'advanced-hotel-room-booking-system'); ?></label>
                    <input type="text" name="log" id="abs-user-login" class="input" required>
                </div>
                <div class="abs-form-group">
                    <label for="abs-user-pass"><?php esc_html_e('Password', 'advanced-hotel-room-booking-system'); ?></label>
                    <input type="password" name="pwd" id="abs-user-pass" class="input" required>
                </div>
                <div class="abs-form-group">
                    <label>
                        <input name="rememberme" type="checkbox" id="abs-rememberme" value="forever">
                        <?php esc_html_e('Remember Me', 'advanced-hotel-room-booking-system'); ?>
                    </label>
                </div>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect); ?>">
                <button type="submit" name="wp-submit" id="abs-wp-submit" class="abs-btn abs-btn-primary">
                    <?php esc_html_e('Log In', 'advanced-hotel-room-booking-system'); ?>
                </button>
            </form>

            <?php if (get_option('users_can_register')) : ?>
                <div class="abs-register-link">
                    <a href="<?php echo esc_url(wp_registration_url()); ?>">
                        <?php esc_html_e('Register', 'advanced-hotel-room-booking-system'); ?>
                    </a>
                    |
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">
                        <?php esc_html_e('Lost your password?', 'advanced-hotel-room-booking-system'); ?>
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
        <div class="abs-login-widget">
            <p>
                <?php
                printf(
                    /* translators: %s: user display name */
                    esc_html__('Welcome, %s!', 'advanced-hotel-room-booking-system'),
                    '<strong>' . esc_html($current_user->display_name) . '</strong>'
                );
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url($dashboard_url); ?>" class="abs-btn abs-btn-primary">
                    <?php esc_html_e('My Bookings', 'advanced-hotel-room-booking-system'); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">
                    <?php esc_html_e('Logout', 'advanced-hotel-room-booking-system'); ?>
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
        $title = ! empty($instance['title']) ? $instance['title'] : __('Login', 'advanced-hotel-room-booking-system');
        $redirect = ! empty($instance['redirect']) ? $instance['redirect'] : '';
        $dashboard_url = ! empty($instance['dashboard_url']) ? $instance['dashboard_url'] : '';
    ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'advanced-hotel-room-booking-system'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('redirect')); ?>">
                <?php esc_html_e('Redirect URL after login:', 'advanced-hotel-room-booking-system'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('redirect')); ?>"
                name="<?php echo esc_attr($this->get_field_name('redirect')); ?>"
                type="text" value="<?php echo esc_attr($redirect); ?>"
                placeholder="<?php echo esc_attr(home_url()); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('dashboard_url')); ?>">
                <?php esc_html_e('Dashboard URL:', 'advanced-hotel-room-booking-system'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('dashboard_url')); ?>"
                name="<?php echo esc_attr($this->get_field_name('dashboard_url')); ?>"
                type="text" value="<?php echo esc_attr($dashboard_url); ?>"
                placeholder="<?php echo esc_attr(home_url('/my-bookings/')); ?>">
            <small><?php esc_html_e('URL to display for logged-in users', 'advanced-hotel-room-booking-system'); ?></small>
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
