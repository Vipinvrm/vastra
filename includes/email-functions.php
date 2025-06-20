<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Email_Functions {
    
    public static function send_enquiry_notification($enquiry_id) {
        global $wpdb;
        
        $enquiry = $wpdb->get_row($wpdb->prepare("
            SELECT e.*, t.name as template_name
            FROM {$wpdb->prefix}costume_enquiries e
            LEFT JOIN {$wpdb->prefix}costume_templates t ON e.template_id = t.id
            WHERE e.enquiry_id = %s
        ", $enquiry_id));
        
        if (!$enquiry) return false;
        
        $items = $wpdb->get_results($wpdb->prepare("
            SELECT ei.*, c.name as component_name
            FROM {$wpdb->prefix}costume_enquiry_items ei
            LEFT JOIN {$wpdb->prefix}costume_components c ON ei.component_id = c.id
            WHERE ei.enquiry_id = %s
        ", $enquiry_id));
        
        // Send admin notification
        self::send_admin_notification($enquiry, $items);
        
        // Send customer confirmation
        self::send_customer_confirmation($enquiry, $items);
        
        return true;
    }
    
    private static function send_admin_notification($enquiry, $items) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[{$site_name}] New Costume Enquiry - {$enquiry->enquiry_id}";
        
        $message = self::get_admin_email_template($enquiry, $items);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>'
        );
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    private static function send_customer_confirmation($enquiry, $items) {
        $site_name = get_bloginfo('name');
        $admin_email = get_option('admin_email');
        
        $subject = "Costume Enquiry Confirmation - {$enquiry->enquiry_id}";
        
        $message = self::get_customer_email_template($enquiry, $items);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>'
        );
        
        wp_mail($enquiry->customer_email, $subject, $message, $headers);
    }
    
    private static function get_admin_email_template($enquiry, $items) {
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=costume-enquiry-manager&action=view&enquiry_id=' . $enquiry->enquiry_id);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>New Costume Enquiry</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .details { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #f5f5f5; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>New Costume Enquiry</h1>
                    <p>Enquiry ID: <?php echo esc_html($enquiry->enquiry_id); ?></p>
                </div>
                
                <div class="content">
                    <div class="details">
                        <h2>Customer Information</h2>
                        <p><strong>Name:</strong> <?php echo esc_html($enquiry->customer_name); ?></p>
                        <p><strong>Email:</strong> <?php echo esc_html($enquiry->customer_email); ?></p>
                        <p><strong>Phone:</strong> <?php echo esc_html($enquiry->customer_phone ?: 'Not provided'); ?></p>
                        <p><strong>Address:</strong> <?php echo nl2br(esc_html($enquiry->customer_address ?: 'Not provided')); ?></p>
                    </div>
                    
                    <div class="details">
                        <h2>Order Details</h2>
                        <p><strong>Template:</strong> <?php echo esc_html($enquiry->template_name); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($enquiry->created_at)); ?></p>
                        
                        <?php if ($enquiry->notes): ?>
                            <p><strong>Special Requirements:</strong></p>
                            <p><?php echo nl2br(esc_html($enquiry->notes)); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="details">
                        <h2>Selected Components</h2>
                        <table>
                            <tr><th>Component</th><th>Color</th><th>Fabric</th><th>Size</th></tr>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo esc_html($item->component_name); ?></td>
                                    <td><?php echo esc_html($item->selected_color ?: '-'); ?></td>
                                    <td><?php echo esc_html($item->selected_fabric ?: '-'); ?></td>
                                    <td><?php echo esc_html($item->selected_size ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="<?php echo esc_url($admin_url); ?>" class="button">View Full Enquiry</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private static function get_customer_email_template($enquiry, $items) {
        $site_name = get_bloginfo('name');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Enquiry Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .details { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #f5f5f5; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Thank You for Your Enquiry!</h1>
                    <p>Enquiry ID: <?php echo esc_html($enquiry->enquiry_id); ?></p>
                </div>
                
                <div class="content">
                    <p>Dear <?php echo esc_html($enquiry->customer_name); ?>,</p>
                    
                    <p>Thank you for your costume enquiry. We have received your request and will review it shortly. Below are the details of your enquiry:</p>
                    
                    <div class="details">
                        <h2>Your Selection</h2>
                        <p><strong>Costume Type:</strong> <?php echo esc_html($enquiry->template_name); ?></p>
                        <p><strong>Enquiry Date:</strong> <?php echo date('F j, Y', strtotime($enquiry->created_at)); ?></p>
                        
                        <h3>Components:</h3>
                        <table>
                            <tr><th>Component</th><th>Color</th><th>Fabric</th><th>Size</th></tr>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo esc_html($item->component_name); ?></td>
                                    <td><?php echo esc_html($item->selected_color ?: '-'); ?></td>
                                    <td><?php echo esc_html($item->selected_fabric ?: '-'); ?></td>
                                    <td><?php echo esc_html($item->selected_size ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <?php if ($enquiry->notes): ?>
                            <p><strong>Your Special Requirements:</strong></p>
                            <p><?php echo nl2br(esc_html($enquiry->notes)); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="details">
                        <h2>What's Next?</h2>
                        <ul>
                            <li>Our team will review your requirements</li>
                            <li>We will contact you within 24-48 hours</li>
                            <li>We'll discuss pricing, timing, and any special customizations</li>
                            <li>Once confirmed, we'll begin creating your custom costume</li>
                        </ul>
                    </div>
                    
                    <p>If you have any questions or need to make changes to your enquiry, please contact us and reference your enquiry ID: <strong><?php echo esc_html($enquiry->enquiry_id); ?></strong></p>
                    
                    <p>Thank you for choosing <?php echo esc_html($site_name); ?>!</p>
                    
                    <p>Best regards,<br>The Costume Team</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}