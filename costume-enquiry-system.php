<?php
/**
 * Plugin Name: Costume Enquiry System
 * Plugin URI: https://yourwebsite.com
 * Description: A comprehensive costume enquiry and customization system for dance costumes
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: costume-enquiry-system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CES_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CES_VERSION', '1.0.0');

class CostumeEnquirySystem {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load plugin files
        $this->load_dependencies();
        
        // Initialize admin area
        if (is_admin()) {
            $this->load_admin();
        }
        
        // Initialize frontend
        $this->load_frontend();
        
        // Load AJAX handlers
        $this->load_ajax_handlers();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function load_dependencies() {
        require_once CES_PLUGIN_PATH . 'includes/database.php';
        require_once CES_PLUGIN_PATH . 'includes/email-functions.php';
    }
    
    public function load_admin() {
        require_once CES_PLUGIN_PATH . 'admin/admin-menu.php';
        require_once CES_PLUGIN_PATH . 'admin/costume-templates.php';
        require_once CES_PLUGIN_PATH . 'admin/component-manager.php';
        require_once CES_PLUGIN_PATH . 'admin/enquiry-manager.php';
        require_once CES_PLUGIN_PATH . 'admin/dashboard.php';
    }
    
    public function load_frontend() {
        require_once CES_PLUGIN_PATH . 'frontend/shortcode.php';
        require_once CES_PLUGIN_PATH . 'frontend/enquiry-form.php';
    }
    
    public function load_ajax_handlers() {
        require_once CES_PLUGIN_PATH . 'includes/ajax-handlers.php';
    }
    
    public function enqueue_frontend_assets() {
        // Force jQuery to load
        wp_enqueue_script('jquery');
        
        wp_enqueue_style('ces-frontend-style', CES_PLUGIN_URL . 'assets/css/frontend-style.css', array(), CES_VERSION);
        wp_enqueue_script('ces-frontend-script', CES_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery'), CES_VERSION, true);
        wp_enqueue_style('wp-color-picker');
        
        wp_localize_script('ces-frontend-script', 'ces_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ces_nonce')
        ));
        
        // Also localize for jQuery directly in case our script doesn't load
        wp_add_inline_script('jquery', '
            window.ces_ajax = {
                ajax_url: "' . admin_url('admin-ajax.php') . '",
                nonce: "' . wp_create_nonce('ces_nonce') . '"
            };
            console.log("CES: AJAX vars set via jQuery");
        ');
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'costume-enquiry') !== false) {
            wp_enqueue_style('ces-admin-style', CES_PLUGIN_URL . 'assets/css/admin-style.css', array(), CES_VERSION);
            wp_enqueue_script('ces-admin-script', CES_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'wp-color-picker'), CES_VERSION, true);
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_media();
            
            wp_localize_script('ces-admin-script', 'ces_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ces_admin_nonce')
            ));
        }
    }
    
    public function activate() {
        // Load database class for activation
        require_once CES_PLUGIN_PATH . 'includes/database.php';
        CES_Database::create_tables();
        CES_Database::insert_sample_data();
    }
    
    public function deactivate() {
        // Clean up if needed
    }
}

// Initialize the plugin
new CostumeEnquirySystem();