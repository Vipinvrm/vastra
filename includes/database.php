<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Costume Templates Table
        $table_costume_templates = $wpdb->prefix . 'costume_templates';
        $sql_templates = "CREATE TABLE $table_costume_templates (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            image_url varchar(500),
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Costume Components Table
        $table_costume_components = $wpdb->prefix . 'costume_components';
        $sql_components = "CREATE TABLE $table_costume_components (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            component_type varchar(100) NOT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Component Options Table
        $table_component_options = $wpdb->prefix . 'component_options';
        $sql_options = "CREATE TABLE $table_component_options (
            id int(11) NOT NULL AUTO_INCREMENT,
            template_id int(11) NOT NULL,
            component_id int(11) NOT NULL,
            colors text,
            fabrics text,
            sizes text,
            custom_attributes text,
            is_required tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY template_id (template_id),
            KEY component_id (component_id)
        ) $charset_collate;";
        
        // Enquiries Table
        $table_enquiries = $wpdb->prefix . 'costume_enquiries';
        $sql_enquiries = "CREATE TABLE $table_enquiries (
            id int(11) NOT NULL AUTO_INCREMENT,
            enquiry_id varchar(20) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(20),
            customer_address text,
            template_id int(11) NOT NULL,
            status enum('pending','processing','completed','cancelled') DEFAULT 'pending',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY enquiry_id (enquiry_id),
            KEY template_id (template_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Enquiry Items Table
        $table_enquiry_items = $wpdb->prefix . 'costume_enquiry_items';
        $sql_items = "CREATE TABLE $table_enquiry_items (
            id int(11) NOT NULL AUTO_INCREMENT,
            enquiry_id varchar(20) NOT NULL,
            component_id int(11) NOT NULL,
            selected_color varchar(100),
            selected_fabric varchar(100),
            selected_size varchar(50),
            custom_attributes text,
            quantity int(11) DEFAULT 1,
            PRIMARY KEY (id),
            KEY enquiry_id (enquiry_id),
            KEY component_id (component_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_templates);
        dbDelta($sql_components);
        dbDelta($sql_options);
        dbDelta($sql_enquiries);
        dbDelta($sql_items);
    }
    
    public static function insert_sample_data() {
        global $wpdb;
        
        $template_table = $wpdb->prefix . 'costume_templates';
        $component_table = $wpdb->prefix . 'costume_components';
        $options_table = $wpdb->prefix . 'component_options';
        
        // Insert sample costume templates
        $templates = array(
            array('Kathak Costume', 'Traditional Kathak dance costume', ''),
            array('Bharatnatyam Costume', 'Classical Bharatnatyam dance attire', ''),
            array('Odissi Costume', 'Elegant Odissi dance costume', ''),
            array('Kuchipudi Costume', 'Traditional Kuchipudi outfit', '')
        );
        
        foreach ($templates as $template) {
            $wpdb->insert($template_table, array(
                'name' => $template[0],
                'description' => $template[1],
                'image_url' => $template[2]
            ));
        }
        
        // Insert sample components
        $components = array(
            array('Choli/Blouse', 'Upper body garment', 'topwear'),
            array('Skirt/Ghagra', 'Lower body garment', 'bottomwear'),
            array('Dupatta/Veil', 'Head covering', 'accessories'),
            array('Necklace', 'Neck jewelry', 'jewellery'),
            array('Earrings', 'Ear jewelry', 'jewellery'),
            array('Bangles', 'Wrist jewelry', 'jewellery'),
            array('Ankle Bells', 'Foot jewelry', 'jewellery'),
            array('Hair Accessories', 'Hair decoration', 'accessories')
        );
        
        foreach ($components as $component) {
            $wpdb->insert($component_table, array(
                'name' => $component[0],
                'description' => $component[1],
                'component_type' => $component[2]
            ));
        }
        
        // Insert sample component options for each template
        $template_ids = $wpdb->get_col("SELECT id FROM $template_table");
        $component_ids = $wpdb->get_col("SELECT id FROM $component_table");
        
        $default_colors = json_encode(array('#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF', '#FFA500', '#800080', '#FFC0CB', '#A52A2A'));
        $default_fabrics = json_encode(array('Silk', 'Cotton', 'Georgette', 'Chiffon', 'Brocade', 'Satin'));
        $default_sizes = json_encode(array('XS', 'S', 'M', 'L', 'XL', 'XXL', 'Custom'));
        
        foreach ($template_ids as $template_id) {
            foreach ($component_ids as $component_id) {
                $wpdb->insert($options_table, array(
                    'template_id' => $template_id,
                    'component_id' => $component_id,
                    'colors' => $default_colors,
                    'fabrics' => $default_fabrics,
                    'sizes' => $default_sizes,
                    'custom_attributes' => '{}',
                    'is_required' => 0,
                    'sort_order' => $component_id
                ));
            }
        }
    }
    
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'costume_enquiry_items',
            $wpdb->prefix . 'costume_enquiries',
            $wpdb->prefix . 'component_options',
            $wpdb->prefix . 'costume_components',
            $wpdb->prefix . 'costume_templates'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
}