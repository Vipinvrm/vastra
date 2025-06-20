<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Admin_Enquiries {
    
    public static function render() {
        global $wpdb;
        
        self::handle_actions();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $enquiry_id = isset($_GET['enquiry_id']) ? sanitize_text_field($_GET['enquiry_id']) : '';
        
        echo '<div class="wrap">';
        echo '<h1>Enquiry Manager</h1>';
        
        if ($action === 'view' && $enquiry_id) {
            self::render_enquiry_details($enquiry_id);
        } elseif ($action === 'export') {
            self::export_enquiries();
        } else {
            self::render_list();
        }
        
        echo '</div>';
    }
    
    private static function handle_actions() {
        if (!isset($_POST['ces_enquiry_nonce']) || !wp_verify_nonce($_POST['ces_enquiry_nonce'], 'ces_enquiry_action')) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'costume_enquiries';
        
        if (isset($_POST['update_status'])) {
            $enquiry_id = sanitize_text_field($_POST['enquiry_id']);
            $status = sanitize_text_field($_POST['status']);
            $notes = sanitize_textarea_field($_POST['admin_notes']);
            
            $result = $wpdb->update(
                $table,
                array(
                    'status' => $status,
                    'notes' => $notes
                ),
                array('enquiry_id' => $enquiry_id)
            );
            
            if ($result !== false) {
                echo '<div class="notice notice-success"><p>Enquiry updated successfully!</p></div>';
                
                // Send status update email to customer
                self::send_status_update_email($enquiry_id, $status);
            } else {
                echo '<div class="notice notice-error"><p>Failed to update enquiry.</p></div>';
            }
        }
        
        if (isset($_POST['bulk_action']) && isset($_POST['selected_enquiries'])) {
            $action = sanitize_text_field($_POST['bulk_action']);
            $enquiry_ids = array_map('sanitize_text_field', $_POST['selected_enquiries']);
            
            if ($action === 'delete') {
                foreach ($enquiry_ids as $enquiry_id) {
                    $wpdb->delete($table, array('enquiry_id' => $enquiry_id));
                    $wpdb->delete($wpdb->prefix . 'costume_enquiry_items', array('enquiry_id' => $enquiry_id));
                }
                echo '<div class="notice notice-success"><p>' . count($enquiry_ids) . ' enquiries deleted.</p></div>';
            } elseif (in_array($action, array('pending', 'processing', 'completed', 'cancelled'))) {
                foreach ($enquiry_ids as $enquiry_id) {
                    $wpdb->update($table, array('status' => $action), array('enquiry_id' => $enquiry_id));
                }
                echo '<div class="notice notice-success"><p>' . count($enquiry_ids) . ' enquiries updated to ' . $action . '.</p></div>';
            }
        }
    }
    
    private static function render_list() {
        global $wpdb;
        
        // Get filter parameters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        // Build query
        $where_conditions = array('1=1');
        $where_values = array();
        
        if ($status_filter) {
            $where_conditions[] = 'e.status = %s';
            $where_values[] = $status_filter;
        }
        
        if ($date_from) {
            $where_conditions[] = 'DATE(e.created_at) >= %s';
            $where_values[] = $date_from;
        }
        
        if ($date_to) {
            $where_conditions[] = 'DATE(e.created_at) <= %s';
            $where_values[] = $date_to;
        }
        
        if ($search) {
            $where_conditions[] = '(e.enquiry_id LIKE %s OR e.customer_name LIKE %s OR e.customer_email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT e.*, t.name as template_name,
                   COUNT(ei.id) as item_count
            FROM {$wpdb->prefix}costume_enquiries e
            LEFT JOIN {$wpdb->prefix}costume_templates t ON e.template_id = t.id
            LEFT JOIN {$wpdb->prefix}costume_enquiry_items ei ON e.enquiry_id = ei.enquiry_id
            WHERE {$where_clause}
            GROUP BY e.id
            ORDER BY e.created_at DESC
        ";
        
        if (!empty($where_values)) {
            $enquiries = $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            $enquiries = $wpdb->get_results($query);
        }
        
        // Render filters
        ?>
        <div class="ces-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="costume-enquiry-manager">
                
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                    <option value="processing" <?php selected($status_filter, 'processing'); ?>>Processing</option>
                    <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                    <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelled</option>
                </select>
                
                <label for="date_from">From:</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                
                <label for="date_to">To:</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" value="<?php echo esc_attr($search); ?>" placeholder="Enquiry ID, Name, or Email">
                
                <input type="submit" class="button" value="Filter">
                <a href="?page=costume-enquiry-manager" class="button">Clear</a>
            </form>
        </div>
        
        <div class="ces-actions">
            <a href="?page=costume-enquiry-manager&action=export" class="button">Export CSV</a>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('ces_enquiry_action', 'ces_enquiry_nonce'); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="bulk_action">
                        <option value="">Bulk Actions</option>
                        <option value="pending">Mark as Pending</option>
                        <option value="processing">Mark as Processing</option>
                        <option value="completed">Mark as Completed</option>
                        <option value="cancelled">Mark as Cancelled</option>
                        <option value="delete">Delete</option>
                    </select>
                    <input type="submit" class="button action" value="Apply">
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="check-column"><input type="checkbox" id="cb-select-all"></td>
                        <th>Enquiry ID</th>
                        <th>Customer</th>
                        <th>Template</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enquiries)): ?>
                        <tr><td colspan="8">No enquiries found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enquiries as $enquiry): ?>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox" name="selected_enquiries[]" value="<?php echo esc_attr($enquiry->enquiry_id); ?>">
                                </th>
                                <td><strong><?php echo esc_html($enquiry->enquiry_id); ?></strong></td>
                                <td>
                                    <?php echo esc_html($enquiry->customer_name); ?><br>
                                    <small><?php echo esc_html($enquiry->customer_email); ?></small>
                                </td>
                                <td><?php echo esc_html($enquiry->template_name); ?></td>
                                <td><?php echo intval($enquiry->item_count); ?> items</td>
                                <td>
                                    <span class="ces-status ces-status-<?php echo esc_attr($enquiry->status); ?>">
                                        <?php echo esc_html(ucfirst($enquiry->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($enquiry->created_at)); ?></td>
                                <td>
                                    <a href="?page=costume-enquiry-manager&action=view&enquiry_id=<?php echo esc_attr($enquiry->enquiry_id); ?>" class="button button-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            $('#cb-select-all').on('change', function() {
                $('input[name="selected_enquiries[]"]').prop('checked', this.checked);
            });
        });
        </script>
        <?php
    }
    
    private static function render_enquiry_details($enquiry_id) {
        global $wpdb;
        
        $enquiry = $wpdb->get_row($wpdb->prepare("
            SELECT e.*, t.name as template_name
            FROM {$wpdb->prefix}costume_enquiries e
            LEFT JOIN {$wpdb->prefix}costume_templates t ON e.template_id = t.id
            WHERE e.enquiry_id = %s
        ", $enquiry_id));
        
        if (!$enquiry) {
            echo '<div class="notice notice-error"><p>Enquiry not found!</p></div>';
            return;
        }
        
        $items = $wpdb->get_results($wpdb->prepare("
            SELECT ei.*, c.name as component_name, c.component_type
            FROM {$wpdb->prefix}costume_enquiry_items ei
            LEFT JOIN {$wpdb->prefix}costume_components c ON ei.component_id = c.id
            WHERE ei.enquiry_id = %s
        ", $enquiry_id));
        
        ?>
        <div class="ces-enquiry-details">
            <h2>Enquiry Details: <?php echo esc_html($enquiry_id); ?></h2>
            
            <div class="ces-enquiry-info">
                <div class="ces-customer-info">
                    <h3>Customer Information</h3>
                    <table class="form-table">
                        <tr><th>Name:</th><td><?php echo esc_html($enquiry->customer_name); ?></td></tr>
                        <tr><th>Email:</th><td><a href="mailto:<?php echo esc_attr($enquiry->customer_email); ?>"><?php echo esc_html($enquiry->customer_email); ?></a></td></tr>
                        <tr><th>Phone:</th><td><?php echo esc_html($enquiry->customer_phone ?: 'Not provided'); ?></td></tr>
                        <tr><th>Address:</th><td><?php echo nl2br(esc_html($enquiry->customer_address ?: 'Not provided')); ?></td></tr>
                    </table>
                </div>
                
                <div class="ces-order-info">
                    <h3>Order Information</h3>
                    <table class="form-table">
                        <tr><th>Enquiry ID:</th><td><?php echo esc_html($enquiry->enquiry_id); ?></td></tr>
                        <tr><th>Template:</th><td><?php echo esc_html($enquiry->template_name); ?></td></tr>
                        <tr><th>Status:</th><td><span class="ces-status ces-status-<?php echo esc_attr($enquiry->status); ?>"><?php echo esc_html(ucfirst($enquiry->status)); ?></span></td></tr>
                        <tr><th>Created:</th><td><?php echo date('Y-m-d H:i:s', strtotime($enquiry->created_at)); ?></td></tr>
                        <tr><th>Updated:</th><td><?php echo date('Y-m-d H:i:s', strtotime($enquiry->updated_at)); ?></td></tr>
                    </table>
                </div>
            </div>
            
            <div class="ces-items-list">
                <h3>Selected Items</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr><th>Component</th><th>Type</th><th>Color</th><th>Fabric</th><th>Size</th><th>Custom Attributes</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->component_name); ?></td>
                                <td><?php echo esc_html($item->component_type); ?></td>
                                <td>
                                    <?php if ($item->selected_color): ?>
                                        <span style="background: <?php echo esc_attr($item->selected_color); ?>; padding: 2px 8px; color: white; border-radius: 3px;">
                                            <?php echo esc_html($item->selected_color); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($item->selected_fabric ?: '-'); ?></td>
                                <td><?php echo esc_html($item->selected_size ?: '-'); ?></td>
                                <td>
                                    <?php 
                                    $custom_attrs = json_decode($item->custom_attributes, true);
                                    if ($custom_attrs && is_array($custom_attrs)) {
                                        foreach ($custom_attrs as $key => $value) {
                                            echo esc_html($key) . ': ' . esc_html($value) . '<br>';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="ces-update-status">
                <h3>Update Enquiry</h3>
                <form method="post" action="">
                    <?php wp_nonce_field('ces_enquiry_action', 'ces_enquiry_nonce'); ?>
                    <input type="hidden" name="enquiry_id" value="<?php echo esc_attr($enquiry_id); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="status">Status:</label></th>
                            <td>
                                <select name="status" id="status">
                                    <option value="pending" <?php selected($enquiry->status, 'pending'); ?>>Pending</option>
                                    <option value="processing" <?php selected($enquiry->status, 'processing'); ?>>Processing</option>
                                    <option value="completed" <?php selected($enquiry->status, 'completed'); ?>>Completed</option>
                                    <option value="cancelled" <?php selected($enquiry->status, 'cancelled'); ?>>Cancelled</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="admin_notes">Admin Notes:</label></th>
                            <td><textarea name="admin_notes" id="admin_notes" rows="4" cols="50"><?php echo esc_textarea($enquiry->notes); ?></textarea></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="update_status" class="button button-primary" value="Update Enquiry">
                        <a href="?page=costume-enquiry-manager" class="button">Back to List</a>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    private static function export_enquiries() {
        global $wpdb;
        
        $enquiries = $wpdb->get_results("
            SELECT e.*, t.name as template_name
            FROM {$wpdb->prefix}costume_enquiries e
            LEFT JOIN {$wpdb->prefix}costume_templates t ON e.template_id = t.id
            ORDER BY e.created_at DESC
        ");
        
        $filename = 'costume_enquiries_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Enquiry ID', 'Customer Name', 'Customer Email', 'Customer Phone',
            'Template', 'Status', 'Created Date', 'Notes'
        ));
        
        // CSV data
        foreach ($enquiries as $enquiry) {
            fputcsv($output, array(
                $enquiry->enquiry_id,
                $enquiry->customer_name,
                $enquiry->customer_email,
                $enquiry->customer_phone,
                $enquiry->template_name,
                $enquiry->status,
                $enquiry->created_at,
                $enquiry->notes
            ));
        }
        
        fclose($output);
        exit;
    }
    
    private static function send_status_update_email($enquiry_id, $status) {
        global $wpdb;
        
        $enquiry = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}costume_enquiries WHERE enquiry_id = %s
        ", $enquiry_id));
        
        if (!$enquiry) return;
        
        $subject = 'Enquiry Status Update - ' . $enquiry_id;
        $message = "Dear {$enquiry->customer_name},\n\n";
        $message .= "Your costume enquiry {$enquiry_id} status has been updated to: " . ucfirst($status) . "\n\n";
        $message .= "We will keep you informed of any further updates.\n\n";
        $message .= "Best regards,\nThe Costume Team";
        
        wp_mail($enquiry->customer_email, $subject, $message);
    }
}