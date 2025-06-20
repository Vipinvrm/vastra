<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Ajax_Handlers {
    
    public function __construct() {
        // Frontend AJAX actions
        add_action('wp_ajax_ces_load_components', array($this, 'load_components'));
        add_action('wp_ajax_nopriv_ces_load_components', array($this, 'load_components'));
        
        add_action('wp_ajax_ces_submit_enquiry', array($this, 'submit_enquiry'));
        add_action('wp_ajax_nopriv_ces_submit_enquiry', array($this, 'submit_enquiry'));
        
        // Admin AJAX actions
        add_action('wp_ajax_ces_update_component_options', array($this, 'update_component_options'));
        add_action('wp_ajax_ces_delete_template', array($this, 'delete_template'));
        add_action('wp_ajax_ces_delete_component', array($this, 'delete_component'));
    }
    
    public function load_components() {
        check_ajax_referer('ces_nonce', 'nonce');
        
        $template_id = intval($_POST['template_id']);
        
        global $wpdb;
        $components_query = "
            SELECT c.*, co.colors, co.fabrics, co.sizes, co.custom_attributes, co.is_required 
            FROM {$wpdb->prefix}costume_components c
            LEFT JOIN {$wpdb->prefix}component_options co ON c.id = co.component_id
            WHERE co.template_id = %d AND c.status = 'active'
            ORDER BY co.sort_order, c.name
        ";
        
        $components = $wpdb->get_results($wpdb->prepare($components_query, $template_id));
        
        ob_start();
        foreach ($components as $component) {
            $colors = json_decode($component->colors, true) ?: array();
            $fabrics = json_decode($component->fabrics, true) ?: array();
            $sizes = json_decode($component->sizes, true) ?: array();
            $custom_attrs = json_decode($component->custom_attributes, true) ?: array();
            ?>
            <div class="ces-component-section" data-component-id="<?php echo $component->id; ?>">
                <div class="ces-component-header">
                    <h4><?php echo esc_html($component->name); ?></h4>
                    <label class="ces-toggle">
                        <input type="checkbox" class="ces-component-toggle" data-component-id="<?php echo $component->id; ?>" <?php echo $component->is_required ? 'checked disabled' : ''; ?>>
                        <span class="ces-slider"></span>
                    </label>
                </div>
                
                <div class="ces-component-options" style="<?php echo $component->is_required ? '' : 'display: none;'; ?>">
                    <!-- Color Selection -->
                    <?php if (!empty($colors)): ?>
                        <div class="ces-option-group">
                            <label>Color:</label>
                            <input type="color" class="ces-color-picker" data-component-id="<?php echo $component->id; ?>" data-option="color" value="<?php echo $colors[0]; ?>">
                            <span class="ces-color-value"><?php echo $colors[0]; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Fabric Selection -->
                    <?php if (!empty($fabrics)): ?>
                        <div class="ces-option-group">
                            <label>Fabric:</label>
                            <select class="ces-fabric-select" data-component-id="<?php echo $component->id; ?>" data-option="fabric">
                                <option value="">Select Fabric</option>
                                <?php foreach ($fabrics as $fabric): ?>
                                    <option value="<?php echo esc_attr($fabric); ?>"><?php echo esc_html($fabric); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Size Selection -->
                    <?php if (!empty($sizes)): ?>
                        <div class="ces-option-group">
                            <label>Size:</label>
                            <select class="ces-size-select" data-component-id="<?php echo $component->id; ?>" data-option="size">
                                <option value="">Select Size</option>
                                <?php foreach ($sizes as $size): ?>
                                    <option value="<?php echo esc_attr($size); ?>"><?php echo esc_html($size); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Custom Attributes -->
                    <?php foreach ($custom_attrs as $attr_name => $attr_options): ?>
                        <div class="ces-option-group">
                            <label><?php echo esc_html($attr_name); ?>:</label>
                            <?php if (is_array($attr_options)): ?>
                                <select class="ces-custom-select" data-component-id="<?php echo $component->id; ?>" data-option="<?php echo esc_attr($attr_name); ?>">
                                    <option value="">Select <?php echo esc_html($attr_name); ?></option>
                                    <?php foreach ($attr_options as $option): ?>
                                        <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" class="ces-custom-input" data-component-id="<?php echo $component->id; ?>" data-option="<?php echo esc_attr($attr_name); ?>" placeholder="Enter <?php echo esc_attr($attr_name); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
        
        wp_send_json_success(ob_get_clean());
    }
    
    public function submit_enquiry() {
        check_ajax_referer('ces_enquiry_submit', 'nonce');
        
        global $wpdb;
        
        // Sanitize input data
        $template_id = intval($_POST['template_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $customer_phone = sanitize_text_field($_POST['customer_phone']);
        $customer_address = sanitize_textarea_field($_POST['customer_address']);
        $special_notes = sanitize_textarea_field($_POST['special_notes']);
        $selected_items = json_decode(stripslashes($_POST['selected_items']), true);
        
        // Generate unique enquiry ID
        $enquiry_id = 'CES' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert enquiry
        $enquiry_result = $wpdb->insert(
            $wpdb->prefix . 'costume_enquiries',
            array(
                'enquiry_id' => $enquiry_id,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'customer_address' => $customer_address,
                'template_id' => $template_id,
                'notes' => $special_notes,
                'status' => 'pending'
            )
        );
        
        if ($enquiry_result === false) {
            wp_send_json_error('Failed to save enquiry. Please try again.');
        }
        
        // Insert enquiry items
        foreach ($selected_items as $item) {
            $wpdb->insert(
                $wpdb->prefix . 'costume_enquiry_items',
                array(
                    'enquiry_id' => $enquiry_id,
                    'component_id' => intval($item['component_id']),
                    'selected_color' => sanitize_text_field($item['color']),
                    'selected_fabric' => sanitize_text_field($item['fabric']),
                    'selected_size' => sanitize_text_field($item['size']),
                    'custom_attributes' => json_encode($item['custom_attributes']),
                    'quantity' => 1
                )
            );
        }
        
        // Send email notifications
        $this->send_enquiry_emails($enquiry_id, $customer_email, $customer_name);
        
        wp_send_json_success(array(
            'enquiry_id' => $enquiry_id,
            'message' => 'Your enquiry has been submitted successfully! We will contact you soon.'
        ));
    }
    
    private function send_enquiry_emails($enquiry_id, $customer_email, $customer_name) {
        // This would integrate with the email functions
        // For now, just send basic emails
        
        $admin_email = get_option('admin_email');
        $subject = 'New Costume Enquiry - ' . $enquiry_id;
        
        // Admin notification
        $admin_message = "New costume enquiry received:\n\n";
        $admin_message .= "Enquiry ID: {$enquiry_id}\n";
        $admin_message .= "Customer: {$customer_name}\n";
        $admin_message .= "Email: {$customer_email}\n\n";
        $admin_message .= "Please check the admin panel for full details.";
        
        wp_mail($admin_email, $subject, $admin_message);
        
        // Customer confirmation
        $customer_subject = 'Costume Enquiry Confirmation - ' . $enquiry_id;
        $customer_message = "Dear {$customer_name},\n\n";
        $customer_message .= "Thank you for your costume enquiry. Your enquiry ID is: {$enquiry_id}\n\n";
        $customer_message .= "We will review your requirements and contact you soon.\n\n";
        $customer_message .= "Best regards,\nThe Costume Team";
        
        wp_mail($customer_email, $customer_subject, $customer_message);
    }
}

new CES_Ajax_Handlers();