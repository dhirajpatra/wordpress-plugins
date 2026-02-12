<?php

/**
 * Plugin Name: Intelligent Room Booking System for Hotel
 * Plugin URI: https://wordpress.org/plugins/intelligent-room-booking-system-for-hotel
 * Description: Complete booking management system with calendar, user authentication, and email notifications
 * Version: 1.0.3
 * Author: Dhiraj Patra
 * Author URI: https://github.com/dhirajpatra
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: intelligent-room-booking-system-for-hotel
 * Domain Path: /languages
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IRBSFH_VERSION', '1.0.3');
define('IRBSFH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IRBSFH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IRBSFH_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class IRBSFH_Hotel_Booking_System
{

    /**
     * Single instance of the class
     *
     * @var IRBSFH_Hotel_Booking_System
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return IRBSFH_Hotel_Booking_System
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
        require_once IRBSFH_PLUGIN_DIR . 'includes/class-irbsfh-database.php';
        require_once IRBSFH_PLUGIN_DIR . 'includes/class-irbsfh-booking.php';
        require_once IRBSFH_PLUGIN_DIR . 'includes/class-irbsfh-room.php';
        require_once IRBSFH_PLUGIN_DIR . 'includes/class-irbsfh-email.php';
        require_once IRBSFH_PLUGIN_DIR . 'includes/class-irbsfh-settings.php';
        require_once IRBSFH_PLUGIN_DIR . 'includes/class-irbsfh-validation.php';
        require_once IRBSFH_PLUGIN_DIR . 'includes/class-irbsfh-widget.php';
        require_once IRBSFH_PLUGIN_DIR . 'admin/class-irbsfh-admin.php';
        require_once IRBSFH_PLUGIN_DIR . 'public/class-irbsfh-public.php';
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
        IRBSFH_Database::create_tables();
        IRBSFH_Settings::set_defaults();
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
            new IRBSFH_Admin();
        } else {
            new IRBSFH_Public();
        }
    }

    /**
     * Register widgets
     */
    public function register_widgets()
    {
        register_widget('IRBSFH_Login_Widget');
    }
}

/**
 * Initialize the plugin
 */
function irbsfh_init()
{
    return IRBSFH_Hotel_Booking_System::get_instance();
}

// Start the plugin
irbsfh_init();
