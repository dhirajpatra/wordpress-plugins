<?php

/**
 * Plugin Name: Advanced Hotel Room Booking System
 * Plugin URI: https://wordpress.org/plugins/advanced-hotel-room-booking-system
 * Description: Complete booking management system with calendar, user authentication, and email notifications
 * Version: 1.0.0
 * Author: Dhiraj Patra
 * Author URI: https://github.com/dhirajpatra
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-hotel-room-booking-system
 * Domain Path: /languages
 *
 * @package AdvancedHotelRoomBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ABS_VERSION', '1.0.0');
define('ABS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Advanced_Booking_System
{

    /**
     * Single instance of the class
     *
     * @var Advanced_Booking_System
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Advanced_Booking_System
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes()
    {
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-database.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-booking.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-room.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-email.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-settings.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-validation.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-widget.php';
        require_once ABS_PLUGIN_DIR . 'admin/class-abs-admin.php';
        require_once ABS_PLUGIN_DIR . 'public/class-abs-public.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        add_action('widgets_init', array($this, 'register_widgets'));
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        ABS_Database::create_tables();
        ABS_Settings::set_defaults();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Load plugin textdomain
     */

    public function load_textdomain()
    {
        // load_plugin_textdomain() is not required for plugins hosted on WordPress.org
        // It is automatically handled by the repository
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        if (is_admin()) {
            new ABS_Admin();
        } else {
            new ABS_Public();
        }
    }

    /**
     * Register widgets
     */
    public function register_widgets()
    {
        register_widget('ABS_Login_Widget');
    }
}

/**
 * Initialize the plugin
 */
function abs_init()
{
    return Advanced_Booking_System::get_instance();
}

// Start the plugin
abs_init();
