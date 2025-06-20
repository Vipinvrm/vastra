<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Frontend_Enquiry_Form {
    
    public function __construct() {
        add_action('wp_ajax_ces_get_enquiry_form', array($this, 'get_enquiry_form'));
        add_action('wp_ajax_nopriv_ces_get_enquiry_form', array($this, 'get_enquiry_form'));
        
        add_action('wp_ajax_ces_validate_enquiry_step', array($this, 'validate_enquiry_step'));
        add_action('wp_ajax_nopriv_ces_validate_enquiry_step', array($this, 'validate_enquiry_step'));
    }
    
    public static function render_customer_details_form($template_id = 0) {
        ob_start();
        ?>
        <div class="ces-enquiry-form-container">
            <h3>Complete Your Enquiry</h3>
            
            <div class="ces-form-section">
                <h4>Personal Information</h4>
                <div class="ces-form-row">
                    <div class="ces-form-group">
                        <label for="customer_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" required>
                        <span class="ces-error-message" id="customer_name_error"></span>
                    </div>
                    
                    <div class="ces-form-group">
                        <label for="customer_email">Email Address <span class="required">*</span></label>
                        <input type="email" id="customer_email" name="customer_email" required>
                        <span class="ces-error-message" id="customer_email_error"></span>
                    </div>
                </div>
                
                <div class="ces-form-row">
                    <div class="ces-form-group">
                        <label for="customer_phone">Phone Number</label>
                        <input type="tel" id="customer_phone" name="customer_phone" placeholder="+1 (555) 123-4567">
                        <span class="ces-error-message" id="customer_phone_error"></span>
                    </div>
                    
                    <div class="ces-form-group">
                        <label for="customer_whatsapp">WhatsApp Number</label>
                        <input type="tel" id="customer_whatsapp" name="customer_whatsapp" placeholder="+1 (555) 123-4567">
                        <small class="ces-form-help">If different from phone number</small>
                    </div>
                </div>
            </div>
            
            <div class="ces-form-section">
                <h4>Contact Details</h4>
                <div class="ces-form-group">
                    <label for="customer_address">Complete Address</label>
                    <textarea id="customer_address" name="customer_address" rows="3" placeholder="Street address, City, State, Postal Code, Country"></textarea>
                    <span class="ces-error-message" id="customer_address_error"></span>
                </div>
                
                <div class="ces-form-row">
                    <div class="ces-form-group">
                        <label for="preferred_contact">Preferred Contact Method</label>
                        <select id="preferred_contact" name="preferred_contact">
                            <option value="email">Email</option>
                            <option value="phone">Phone Call</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="any">Any Method</option>
                        </select>
                    </div>
                    
                    <div class="ces-form-group">
                        <label for="best_time_to_contact">Best Time to Contact</label>
                        <select id="best_time_to_contact" name="best_time_to_contact">
                            <option value="morning">Morning (9 AM - 12 PM)</option>
                            <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                            <option value="evening">Evening (5 PM - 8 PM)</option>
                            <option value="anytime">Anytime</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="ces-form-section">
                <h4>Event Details</h4>
                <div class="ces-form-row">
                    <div class="ces-form-group">
                        <label for="event_type">Event Type</label>
                        <select id="event_type" name="event_type">
                            <option value="">Select event type</option>
                            <option value="performance">Dance Performance</option>
                            <option value="competition">Competition</option>
                            <option value="wedding">Wedding</option>
                            <option value="festival">Festival</option>
                            <option value="photoshoot">Photoshoot</option>
                            <option value="class">Dance Class</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="ces-form-group">
                        <label for="event_date">Event Date (if known)</label>
                        <input type="date" id="event_date" name="event_date" min="<?php echo date('Y-m-d'); ?>">
                        <small class="ces-form-help">This helps us prioritize your order</small>
                    </div>
                </div>
                
                <div class="ces-form-group">
                    <label for="urgency_level">Urgency Level</label>
                    <div class="ces-radio-group">
                        <label class="ces-radio-option">
                            <input type="radio" name="urgency_level" value="flexible" checked>
                            <span class="ces-radio-custom"></span>
                            Flexible - No rush
                        </label>
                        <label class="ces-radio-option">
                            <input type="radio" name="urgency_level" value="moderate">
                            <span class="ces-radio-custom"></span>
                            Moderate - Within 2-4 weeks
                        </label>
                        <label class="ces-radio-option">
                            <input type="radio" name="urgency_level" value="urgent">
                            <span class="ces-radio-custom"></span>
                            Urgent - Within 1-2 weeks
                        </label>
                        <label class="ces-radio-option">
                            <input type="radio" name="urgency_level" value="emergency">
                            <span class="ces-radio-custom"></span>
                            Emergency - ASAP
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="ces-form-section">
                <h4>Additional Requirements</h4>
                <div class="ces-form-group">
                    <label for="special_notes">Special Requirements or Notes</label>
                    <textarea id="special_notes" name="special_notes" rows="4" placeholder="Please include any specific requirements, measurements, design preferences, or other important details..."></textarea>
                    <span class="ces-error-message" id="special_notes_error"></span>
                </div>
                
                <div class="ces-form-group">
                    <label for="budget_range">Budget Range (Optional)</label>
                    <select id="budget_range" name="budget_range">
                        <option value="">Prefer not to specify</option>
                        <option value="under_500">Under $500</option>
                        <option value="500_1000">$500 - $1,000</option>
                        <option value="1000_2000">$1,000 - $2,000</option>
                        <option value="2000_5000">$2,000 - $5,000</option>
                        <option value="over_5000">Over $5,000</option>
                        <option value="custom">Custom Budget</option>
                    </select>
                    <small class="ces-form-help">This helps us provide appropriate options</small>
                </div>
                
                <div class="ces-form-group">
                    <div class="ces-checkbox-group">
                        <label class="ces-checkbox-option">
                            <input type="checkbox" name="include_accessories" value="1">
                            <span class="ces-checkbox-custom"></span>
                            Include matching accessories (hair ornaments, footwear suggestions)
                        </label>
                        
                        <label class="ces-checkbox-option">
                            <input type="checkbox" name="alteration_service" value="1">
                            <span class="ces-checkbox-custom"></span>
                            I may need alteration services
                        </label>
                        
                        <label class="ces-checkbox-option">
                            <input type="checkbox" name="measurement_help" value="1">
                            <span class="ces-checkbox-custom"></span>
                            I need help with taking measurements
                        </label>
                        
                        <label class="ces-checkbox-option">
                            <input type="checkbox" name="design_consultation" value="1">
                            <span class="ces-checkbox-custom"></span>
                            I would like a design consultation
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="ces-form-section">
                <h4>Communication Preferences</h4>
                <div class="ces-checkbox-group">
                    <label class="ces-checkbox-option">
                        <input type="checkbox" name="newsletter_signup" value="1">
                        <span class="ces-checkbox-custom"></span>
                        Subscribe to our newsletter for costume care tips and new designs
                    </label>
                    
                    <label class="ces-checkbox-option">
                        <input type="checkbox" name="photo_permission" value="1">
                        <span class="ces-checkbox-custom"></span>
                        I give permission to use photos of my costume for portfolio/marketing (optional)
                    </label>
                </div>
            </div>
            
            <div class="ces-form-section ces-terms-section">
                <div class="ces-checkbox-group">
                    <label class="ces-checkbox-option required-checkbox">
                        <input type="checkbox" name="terms_agreement" value="1" required>
                        <span class="ces-checkbox-custom"></span>
                        I agree to the <a href="#" class="ces-terms-link">Terms & Conditions</a> and <a href="#" class="ces-privacy-link">Privacy Policy</a> <span class="required">*</span>
                    </label>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function render_enquiry_summary($selected_components, $template_name) {
        ob_start();
        ?>
        <div class="ces-enquiry-summary-detailed">
            <h4>Your Costume Selection Summary</h4>
            
            <div class="ces-summary-header">
                <div class="ces-template-info">
                    <h5>Costume Type: <?php echo esc_html($template_name); ?></h5>
                </div>
            </div>
            
            <div class="ces-components-summary">
                <?php if (empty($selected_components)): ?>
                    <p class="ces-no-selection">No components selected yet.</p>
                <?php else: ?>
                    <?php foreach ($selected_components as $component_id => $component_data): ?>
                        <div class="ces-component-summary-item">
                            <div class="ces-component-header">
                                <h6><?php echo esc_html($component_data['name'] ?? 'Component ' . $component_id); ?></h6>
                            </div>
                            
                            <div class="ces-component-details">
                                <?php if (!empty($component_data['color'])): ?>
                                    <div class="ces-detail-item">
                                        <span class="ces-detail-label">Color:</span>
                                        <div class="ces-color-display">
                                            <span class="ces-color-swatch" style="background-color: <?php echo esc_attr($component_data['color']); ?>"></span>
                                            <span class="ces-color-code"><?php echo esc_html($component_data['color']); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($component_data['fabric'])): ?>
                                    <div class="ces-detail-item">
                                        <span class="ces-detail-label">Fabric:</span>
                                        <span class="ces-detail-value"><?php echo esc_html($component_data['fabric']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($component_data['size'])): ?>
                                    <div class="ces-detail-item">
                                        <span class="ces-detail-label">Size:</span>
                                        <span class="ces-detail-value"><?php echo esc_html($component_data['size']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($component_data['custom_attributes'])): ?>
                                    <?php foreach ($component_data['custom_attributes'] as $attr_name => $attr_value): ?>
                                        <?php if (!empty($attr_value)): ?>
                                            <div class="ces-detail-item">
                                                <span class="ces-detail-label"><?php echo esc_html($attr_name); ?>:</span>
                                                <span class="ces-detail-value"><?php echo esc_html($attr_value); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="ces-summary-note">
                <p><strong>Note:</strong> This is your preliminary selection. Our team will contact you to discuss details, measurements, pricing, and timeline.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function get_enquiry_form() {
        check_ajax_referer('ces_nonce', 'nonce');
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $selected_components = json_decode(stripslashes($_POST['selected_components'] ?? '{}'), true);
        
        global $wpdb;
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}costume_templates WHERE id = %d",
            $template_id
        ));
        
        $template_name = $template ? $template->name : 'Unknown Template';
        
        ob_start();
        ?>
        <div class="ces-enquiry-form-wrapper">
            <div class="ces-form-columns">
                <div class="ces-form-left">
                    <?php echo self::render_customer_details_form($template_id); ?>
                </div>
                
                <div class="ces-form-right">
                    <?php echo self::render_enquiry_summary($selected_components, $template_name); ?>
                </div>
            </div>
        </div>
        <?php
        
        wp_send_json_success(ob_get_clean());
    }
    
    public function validate_enquiry_step() {
        check_ajax_referer('ces_nonce', 'nonce');
        
        $step = intval($_POST['step'] ?? 1);
        $data = $_POST['data'] ?? array();
        
        $errors = array();
        
        switch ($step) {
            case 1:
                $errors = $this->validate_step_1($data);
                break;
            case 2:
                $errors = $this->validate_step_2($data);
                break;
            case 3:
                $errors = $this->validate_step_3($data);
                break;
        }
        
        if (empty($errors)) {
            wp_send_json_success(array('message' => 'Validation passed'));
        } else {
            wp_send_json_error(array('errors' => $errors));
        }
    }
    
    private function validate_step_1($data) {
        $errors = array();
        
        if (empty($data['template_id'])) {
            $errors['template'] = 'Please select a costume type.';
        }
        
        return $errors;
    }
    
    private function validate_step_2($data) {
        $errors = array();
        
        $selected_components = json_decode(stripslashes($data['selected_components'] ?? '{}'), true);
        
        if (empty($selected_components)) {
            $errors['components'] = 'Please select and customize at least one component.';
        }
        
        // Validate required components
        global $wpdb;
        $template_id = intval($data['template_id'] ?? 0);
        
        $required_components = $wpdb->get_results($wpdb->prepare("
            SELECT co.component_id, c.name
            FROM {$wpdb->prefix}component_options co
            JOIN {$wpdb->prefix}costume_components c ON co.component_id = c.id
            WHERE co.template_id = %d AND co.is_required = 1
        ", $template_id));
        
        foreach ($required_components as $required) {
            if (!isset($selected_components[$required->component_id])) {
                $errors['required_' . $required->component_id] = $required->name . ' is required for this costume type.';
            }
        }
        
        return $errors;
    }
    
    private function validate_step_3($data) {
        $errors = array();
        
        // Required fields
        $required_fields = array(
            'customer_name' => 'Full name is required.',
            'customer_email' => 'Email address is required.'
        );
        
        foreach ($required_fields as $field => $message) {
            if (empty($data[$field])) {
                $errors[$field] = $message;
            }
        }
        
        // Email validation
        if (!empty($data['customer_email']) && !is_email($data['customer_email'])) {
            $errors['customer_email'] = 'Please enter a valid email address.';
        }
        
        // Phone validation (if provided)
        if (!empty($data['customer_phone']) && !$this->validate_phone($data['customer_phone'])) {
            $errors['customer_phone'] = 'Please enter a valid phone number.';
        }
        
        // Terms agreement
        if (empty($data['terms_agreement'])) {
            $errors['terms_agreement'] = 'You must agree to the terms and conditions.';
        }
        
        return $errors;
    }
    
    private function validate_phone($phone) {
        // Basic phone validation - adjust regex as needed
        $phone = preg_replace('/[^\d+]/', '', $phone);
        return preg_match('/^[\+]?[1-9][\d]{7,14}$/', $phone);
    }
    
    public static function get_form_styles() {
        return "
        .ces-enquiry-form-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .ces-form-columns {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin: 20px 0;
        }
        
        .ces-form-section {
            background: #fff;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #0073aa;
        }
        
        .ces-form-section h4 {
            margin: 0 0 20px 0;
            color: #0073aa;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .ces-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .ces-form-group {
            margin-bottom: 20px;
        }
        
        .ces-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .required {
            color: #d63638;
        }
        
        .ces-form-help {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .ces-radio-group,
        .ces-checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .ces-radio-option,
        .ces-checkbox-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 10px;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .ces-radio-option:hover,
        .ces-checkbox-option:hover {
            background: #f8f9fa;
        }
        
        .ces-radio-custom,
        .ces-checkbox-custom {
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            margin-right: 10px;
            position: relative;
            flex-shrink: 0;
        }
        
        .ces-radio-custom {
            border-radius: 50%;
        }
        
        .ces-checkbox-custom {
            border-radius: 3px;
        }
        
        input[type='radio']:checked + .ces-radio-custom::after {
            content: '';
            width: 8px;
            height: 8px;
            background: #0073aa;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        input[type='checkbox']:checked + .ces-checkbox-custom::after {
            content: '✓';
            color: #0073aa;
            font-weight: bold;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
        }
        
        .ces-error-message {
            color: #d63638;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        .ces-enquiry-summary-detailed {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            position: sticky;
            top: 20px;
        }
        
        .ces-component-summary-item {
            background: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border-left: 3px solid #0073aa;
        }
        
        .ces-component-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .ces-detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ces-detail-label {
            font-weight: 600;
            min-width: 60px;
            color: #666;
        }
        
        .ces-color-display {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ces-color-swatch {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        
        .ces-terms-section {
            border-left-color: #d63638;
        }
        
        .ces-terms-link,
        .ces-privacy-link {
            color: #0073aa;
            text-decoration: none;
        }
        
        .ces-terms-link:hover,
        .ces-privacy-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .ces-form-columns {
                grid-template-columns: 1fr;
            }
            
            .ces-form-row {
                grid-template-columns: 1fr;
            }
            
            .ces-enquiry-summary-detailed {
                position: static;
            }
        }
        ";
    }
}

new CES_Frontend_Enquiry_Form();