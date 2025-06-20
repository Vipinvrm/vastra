<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Admin_Dashboard {
    
    public static function render() {
        global $wpdb;
        
        // Get statistics
        $stats = self::get_statistics();
        
        echo '<div class="wrap">';
        echo '<h1>Costume Enquiry System Dashboard</h1>';
        
        // Statistics Cards
        ?>
        <div class="ces-dashboard-stats">
            <div class="ces-stat-card">
                <h3>Total Enquiries</h3>
                <div class="ces-stat-number"><?php echo $stats['total_enquiries']; ?></div>
            </div>
            <div class="ces-stat-card">
                <h3>Pending</h3>
                <div class="ces-stat-number"><?php echo $stats['pending_enquiries']; ?></div>
            </div>
            <div class="ces-stat-card">
                <h3>Processing</h3>
                <div class="ces-stat-number"><?php echo $stats['processing_enquiries']; ?></div>
            </div>
            <div class="ces-stat-card">
                <h3>Completed</h3>
                <div class="ces-stat-number"><?php echo $stats['completed_enquiries']; ?></div>
            </div>
        </div>
        
        <div class="ces-dashboard-content">
            <div class="ces-recent-enquiries">
                <h2>Recent Enquiries</h2>
                <?php self::render_recent_enquiries(); ?>
            </div>
            
            <div class="ces-quick-stats">
                <h2>Quick Statistics</h2>
                <?php self::render_quick_stats($stats); ?>
            </div>
        </div>
        
        <?php
        echo '</div>';
    }
    
    private static function get_statistics() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'costume_enquiries';
        
        $stats = array();
        $stats['total_enquiries'] = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $stats['pending_enquiries'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'");
        $stats['processing_enquiries'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'processing'");
        $stats['completed_enquiries'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'completed'");
        $stats['cancelled_enquiries'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'cancelled'");
        
        $stats['this_month'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['this_week'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE WEEK(created_at) = WEEK(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['today'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE DATE(created_at) = CURRENT_DATE()");
        
        // Popular templates
        $stats['popular_templates'] = $wpdb->get_results("
            SELECT t.name, COUNT(e.id) as enquiry_count
            FROM {$wpdb->prefix}costume_templates t
            LEFT JOIN $table e ON t.id = e.template_id
            GROUP BY t.id
            ORDER BY enquiry_count DESC
            LIMIT 5
        ");
        
        return $stats;
    }
    
    private static function render_recent_enquiries() {
        global $wpdb;
        
        $recent = $wpdb->get_results("
            SELECT e.enquiry_id, e.customer_name, e.customer_email, e.status, e.created_at, t.name as template_name
            FROM {$wpdb->prefix}costume_enquiries e
            LEFT JOIN {$wpdb->prefix}costume_templates t ON e.template_id = t.id
            ORDER BY e.created_at DESC
            LIMIT 10
        ");
        
        if (empty($recent)) {
            echo '<p>No enquiries yet.</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr><th>Enquiry ID</th><th>Customer</th><th>Template</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $enquiry): ?>
                    <tr>
                        <td>
                            <a href="?page=costume-enquiry-manager&action=view&enquiry_id=<?php echo esc_attr($enquiry->enquiry_id); ?>">
                                <?php echo esc_html($enquiry->enquiry_id); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($enquiry->customer_name); ?></td>
                        <td><?php echo esc_html($enquiry->template_name); ?></td>
                        <td><span class="ces-status ces-status-<?php echo esc_attr($enquiry->status); ?>"><?php echo esc_html(ucfirst($enquiry->status)); ?></span></td>
                        <td><?php echo date('M j, Y', strtotime($enquiry->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    private static function render_quick_stats($stats) {
        ?>
        <div class="ces-quick-stats-content">
            <h3>Enquiries by Period</h3>
            <ul>
                <li><strong>Today:</strong> <?php echo $stats['today']; ?></li>
                <li><strong>This Week:</strong> <?php echo $stats['this_week']; ?></li>
                <li><strong>This Month:</strong> <?php echo $stats['this_month']; ?></li>
            </ul>
            
            <h3>Popular Templates</h3>
            <ul>
                <?php foreach ($stats['popular_templates'] as $template): ?>
                    <li><strong><?php echo esc_html($template->name); ?>:</strong> <?php echo $template->enquiry_count; ?> enquiries</li>
                <?php endforeach; ?>
            </ul>
            
            <h3>Status Distribution</h3>
            <ul>
                <li><strong>Pending:</strong> <?php echo $stats['pending_enquiries']; ?></li>
                <li><strong>Processing:</strong> <?php echo $stats['processing_enquiries']; ?></li>
                <li><strong>Completed:</strong> <?php echo $stats['completed_enquiries']; ?></li>
                <li><strong>Cancelled:</strong> <?php echo $stats['cancelled_enquiries']; ?></li>
            </ul>
        </div>
        <?php
    }
}