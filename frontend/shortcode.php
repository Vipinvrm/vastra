<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Frontend_Shortcode {
    
    public function __construct() {
        add_shortcode('costume_enquiry', array($this, 'render_shortcode'));
        add_action('wp_footer', array($this, 'add_inline_scripts'));
    }
    
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default'
        ), $atts);
        
        ob_start();
        $this->render_costume_builder();
        return ob_get_clean();
    }
    
    private function render_costume_builder() {
        global $wpdb;
        $templates_table = $wpdb->prefix . 'costume_templates';
        $templates = $wpdb->get_results("SELECT * FROM $templates_table WHERE status = 'active' ORDER BY name");
        
        ?>
        <div id="costume-enquiry-system" class="ces-container">
            <div class="ces-progress-bar">
                <div class="ces-step active" data-step="1">1. Choose Costume</div>
                <div class="ces-step" data-step="2">2. Customize</div>
                <div class="ces-step" data-step="3">3. Enquiry Details</div>
            </div>
            
            <!-- Step 1: Costume Selection -->
            <div id="ces-step-1" class="ces-step-content active">
                <h3>Select Your Costume Type</h3>
                <div class="ces-costume-grid">
                    <?php foreach ($templates as $template): ?>
                        <div class="ces-costume-card" data-template-id="<?php echo $template->id; ?>">
                            <?php if ($template->image_url): ?>
                                <img src="<?php echo esc_url($template->image_url); ?>" alt="<?php echo esc_attr($template->name); ?>">
                            <?php else: ?>
                                <div class="ces-no-image">No Image</div>
                            <?php endif; ?>
                            <h4><?php echo esc_html($template->name); ?></h4>
                            <p><?php echo esc_html($template->description); ?></p>
                            <button class="ces-select-costume" data-template-id="<?php echo $template->id; ?>">Select This Costume</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Step 2: Customization -->
            <div id="ces-step-2" class="ces-step-content">
                <h3>Customize Your Costume</h3>
                <div id="ces-components-container">
                    <!-- Components will be loaded via AJAX -->
                </div>
                <div class="ces-navigation">
                    <button id="ces-back-to-step1" class="button">Back</button>
                    <button id="ces-proceed-to-step3" class="button button-primary">Proceed to Enquiry</button>
                </div>
            </div>
            
            <!-- Step 3: Enquiry Form -->
            <div id="ces-step-3" class="ces-step-content">
                <h3>Complete Your Enquiry</h3>
                <div class="ces-enquiry-summary">
                    <h4>Your Selections:</h4>
                    <div id="ces-cart-summary"></div>
                </div>
                
                <form id="ces-enquiry-form">
                    <?php wp_nonce_field('ces_enquiry_submit', 'ces_enquiry_nonce'); ?>
                    <input type="hidden" id="ces-selected-template" name="template_id" value="">
                    
                    <table class="ces-form-table">
                        <tr>
                            <th><label for="customer_name">Full Name *</label></th>
                            <td><input type="text" id="customer_name" name="customer_name" required></td>
                        </tr>
                        <tr>
                            <th><label for="customer_email">Email Address *</label></th>
                            <td><input type="email" id="customer_email" name="customer_email" required></td>
                        </tr>
                        <tr>
                            <th><label for="customer_phone">Phone Number</label></th>
                            <td><input type="tel" id="customer_phone" name="customer_phone"></td>
                        </tr>
                        <tr>
                            <th><label for="customer_address">Address</label></th>
                            <td><textarea id="customer_address" name="customer_address" rows="4"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="special_notes">Special Requirements</label></th>
                            <td><textarea id="special_notes" name="special_notes" rows="3" placeholder="Any special requirements or notes..."></textarea></td>
                        </tr>
                    </table>
                    
                    <div class="ces-navigation">
                        <button type="button" id="ces-back-to-step2" class="button">Back</button>
                        <button type="submit" class="button button-primary">Submit Enquiry</button>
                    </div>
                </form>
            </div>
            
            <!-- Loading overlay -->
            <div id="ces-loading" class="ces-loading" style="display: none;">
                <div class="ces-spinner"></div>
                <p>Processing your request...</p>
            </div>
        </div>
        <?php
    }
    
    public function add_inline_scripts() {
        if (!is_admin() && (has_shortcode(get_post()->post_content, 'costume_enquiry') || is_page())) {
            ?>
            <script type="text/javascript">
                var cesData = {
                    selectedComponents: {},
                    selectedTemplate: null
                };
            </script>
            <?php
        }
    }
}

new CES_Frontend_Shortcode();