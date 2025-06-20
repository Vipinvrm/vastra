<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Admin_Templates {
    
    public static function render() {
        global $wpdb;
        
        // Handle form submissions
        self::handle_form_submission();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
        
        echo '<div class="wrap">';
        echo '<h1>Costume Templates</h1>';
        
        if ($action === 'edit' && $template_id > 0) {
            self::render_edit_form($template_id);
        } elseif ($action === 'add') {
            self::render_add_form();
        } else {
            self::render_list();
        }
        
        echo '</div>';
    }
    
    private static function handle_form_submission() {
        if (!isset($_POST['ces_template_nonce']) || !wp_verify_nonce($_POST['ces_template_nonce'], 'ces_template_action')) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'costume_templates';
        
        $name = sanitize_text_field($_POST['template_name']);
        $description = sanitize_textarea_field($_POST['template_description']);
        $image_url = esc_url_raw($_POST['template_image']);
        $status = sanitize_text_field($_POST['template_status']);
        
        if (isset($_POST['template_id']) && $_POST['template_id'] > 0) {
            // Update existing template
            $wpdb->update(
                $table,
                array(
                    'name' => $name,
                    'description' => $description,
                    'image_url' => $image_url,
                    'status' => $status
                ),
                array('id' => intval($_POST['template_id']))
            );
            echo '<div class="notice notice-success"><p>Template updated successfully!</p></div>';
        } else {
            // Add new template
            $wpdb->insert(
                $table,
                array(
                    'name' => $name,
                    'description' => $description,
                    'image_url' => $image_url,
                    'status' => $status
                )
            );
            echo '<div class="notice notice-success"><p>Template added successfully!</p></div>';
        }
    }
    
    private static function render_list() {
        global $wpdb;
        $table = $wpdb->prefix . 'costume_templates';
        $templates = $wpdb->get_results("SELECT * FROM $table ORDER BY name");
        
        echo '<a href="?page=costume-enquiry-templates&action=add" class="button button-primary">Add New Template</a>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($templates as $template) {
            echo '<tr>';
            echo '<td>' . esc_html($template->name) . '</td>';
            echo '<td>' . esc_html($template->description) . '</td>';
            echo '<td>' . esc_html(ucfirst($template->status)) . '</td>';
            echo '<td>';
            echo '<a href="?page=costume-enquiry-templates&action=edit&template_id=' . $template->id . '" class="button">Edit</a> ';
            echo '<a href="?page=costume-enquiry-templates&action=delete&template_id=' . $template->id . '" class="button button-link-delete" onclick="return confirm(\'Are you sure?\')">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private static function render_add_form() {
        self::render_form();
    }
    
    private static function render_edit_form($template_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'costume_templates';
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $template_id));
        
        if (!$template) {
            echo '<div class="notice notice-error"><p>Template not found!</p></div>';
            return;
        }
        
        self::render_form($template);
    }
    
    private static function render_form($template = null) {
        $is_edit = $template !== null;
        $form_title = $is_edit ? 'Edit Template' : 'Add New Template';
        
        ?>
        <h2><?php echo $form_title; ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('ces_template_action', 'ces_template_nonce'); ?>
            <?php if ($is_edit): ?>
                <input type="hidden" name="template_id" value="<?php echo $template->id; ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="template_name">Template Name</label></th>
                    <td><input type="text" id="template_name" name="template_name" value="<?php echo $is_edit ? esc_attr($template->name) : ''; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="template_description">Description</label></th>
                    <td><textarea id="template_description" name="template_description" rows="4" cols="50"><?php echo $is_edit ? esc_textarea($template->description) : ''; ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="template_image">Image URL</label></th>
                    <td>
                        <input type="url" id="template_image" name="template_image" value="<?php echo $is_edit ? esc_url($template->image_url) : ''; ?>" class="regular-text">
                        <button type="button" class="button" id="upload_image_button">Upload Image</button>
                    </td>
                </tr>
                <tr>
                    <th><label for="template_status">Status</label></th>
                    <td>
                        <select id="template_status" name="template_status">
                            <option value="active" <?php echo ($is_edit && $template->status === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($is_edit && $template->status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button button-primary" value="<?php echo $is_edit ? 'Update Template' : 'Add Template'; ?>">
                <a href="?page=costume-enquiry-templates" class="button">Cancel</a>
            </p>
        </form>
        <?php
    }
}