<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Costume Enquiry System',
            'Costume Enquiry',
            'manage_options',
            'costume-enquiry-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-admin-customizer',
            30
        );
        
        add_submenu_page(
            'costume-enquiry-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'costume-enquiry-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'costume-enquiry-dashboard',
            'Costume Templates',
            'Templates',
            'manage_options',
            'costume-enquiry-templates',
            array($this, 'templates_page')
        );
        
        add_submenu_page(
            'costume-enquiry-dashboard',
            'Component Manager',
            'Components',
            'manage_options',
            'costume-enquiry-components',
            array($this, 'components_page')
        );
        
        add_submenu_page(
            'costume-enquiry-dashboard',
            'Enquiry Manager',
            'Enquiries',
            'manage_options',
            'costume-enquiry-manager',
            array($this, 'enquiries_page')
        );
    }
    
    public function dashboard_page() {
        include_once CES_PLUGIN_PATH . 'admin/dashboard.php';
        CES_Admin_Dashboard::render();
    }
    
    public function templates_page() {
        include_once CES_PLUGIN_PATH . 'admin/costume-templates.php';
        CES_Admin_Templates::render();
    }
    
    public function components_page() {
        include_once CES_PLUGIN_PATH . 'admin/component-manager.php';
        CES_Admin_Components::render();
    }
    
    public function enquiries_page() {
        include_once CES_PLUGIN_PATH . 'admin/enquiry-manager.php';
        CES_Admin_Enquiries::render();
    }
}

new CES_Admin_Menu();