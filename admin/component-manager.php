<?php
if (!defined('ABSPATH')) {
    exit;
}

class CES_Admin_Components {
    
    public static function render() {
        global $wpdb;
        
        self::handle_form_submission();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $component_id = isset($_GET['component_id']) ? intval($_GET['component_id']) : 0;
        
        echo '<div class="wrap">';
        echo '<h1>Component Manager</h1>';
        
        if ($action === 'edit' && $component_id > 0) {
            self::render_edit_form($component_id);
        } elseif ($action === 'add') {
            self::render_add_form();
        } elseif ($action === 'options' && $component_id > 0) {
            self::render_options_manager($component_id);
        } else {
            self::render_list();
        }
        
        echo '</div>';
    }
    
    private static function handle_form_submission() {
        if (!isset($_POST['ces_component_nonce']) || !wp_verify_nonce($_POST['ces_component_nonce'], 'ces_component_action')) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'costume_components';
        
        if (isset($_POST['submit_component'])) {
            $name = sanitize_text_field($_POST['component_name']);
            $description = sanitize_textarea_field($_POST['component_description']);
            $type = sanitize_text_field($_POST['component_type']);
            $status = sanitize_text_field($_POST['component_status']);
            
            if (isset($_POST['component_id']) && $_POST['component_id'] > 0) {
                $wpdb->update(
                    $table,
                    array(
                        'name' => $name,
                        'description' => $description,
                        'component_type' => $type,
                        'status' => $status
                    ),
                    array('id' => intval($_POST['component_id']))
                );
                echo '<div class="notice notice-success"><p>Component updated successfully!</p></div>';
            } else {
                $wpdb->insert(
                    $table,
                    array(
                        'name' => $name,
                        'description' => $description,
                        'component_type' => $type,
                        'status' => $status
                    )
                );
                echo '<div class="notice notice-success"><p>Component added successfully!</p></div>';
            }
        }
        
        if (isset($_POST['submit_options'])) {
            $options_table = $wpdb->prefix . 'component_options';
            $component_id = intval($_POST['component_id']);
            $template_id = intval($_POST['template_id']);
            
            $colors = $_POST['colors'] ? explode(',', sanitize_text_field($_POST['colors'])) : array();
            $fabrics = $_POST['fabrics'] ? explode(',', sanitize_text_field($_POST['fabrics'])) : array();
            $sizes = $_POST['sizes'] ? explode(',', sanitize_text_field($_POST['sizes'])) : array();
            $is_required = isset($_POST['is_required']) ? 1 : 0;
            $sort_order = intval($_POST['sort_order']);
            
            $colors_json = json_encode(array_map('trim', $colors));
            $fabrics_json = json_encode(array_map('trim', $fabrics));
            $sizes_json = json_encode(array_map('trim', $sizes));
            
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $options_table WHERE component_id = %d AND template_id = %d",
                $component_id, $template_id
            ));
            
            if ($existing) {
                $wpdb->update(
                    $options_table,
                    array(
                        'colors' => $colors_json,
                        'fabrics' => $fabrics_json,
                        'sizes' => $sizes_json,
                        'is_required' => $is_required,
                        'sort_order' => $sort_order
                    ),
                    array('id' => $existing->id)
                );
            } else {
                $wpdb->insert(
                    $options_table,
                    array(
                        'template_id' => $template_id,
                        'component_id' => $component_id,
                        'colors' => $colors_json,
                        'fabrics' => $fabrics_json,
                        'sizes' => $sizes_json,
                        'is_required' => $is_required,
                        'sort_order' => $sort_order
                    )
                );
            }
            
            echo '<div class="notice notice-success"><p>Component options updated successfully!</p></div>';
        }
    }
    
    private static function render_list() {
        global $wpdb;
        $table = $wpdb->prefix . 'costume_components';
        $components = $wpdb->get_results("SELECT * FROM $table ORDER BY component_type, name");
        
        echo '<a href="?page=costume-enquiry-components&action=add" class="button button-primary">Add New Component</a>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Type</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($components as $component) {
            echo '<tr>';
            echo '<td>' . esc_html($component->name) . '</td>';
            echo '<td>' . esc_html($component->component_type) . '</td>';
            echo '<td>' . esc_html($component->description) . '</td>';
            echo '<td>' . esc_html(ucfirst($component->status)) . '</td>';
            echo '<td>';
            echo '<a href="?page=costume-enquiry-components&action=edit&component_id=' . $component->id . '" class="button">Edit</a> ';
            echo '<a href="?page=costume-enquiry-components&action=options&component_id=' . $component->id . '" class="button">Options</a> ';
            echo '<a href="?page=costume-enquiry-components&action=delete&component_id=' . $component->id . '" class="button button-link-delete" onclick="return confirm(\'Are you sure?\')">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private static function render_add_form() {
        self::render_form();
    }
    
    private static function render_edit_form($component_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'costume_components';
        $component = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $component_id));
        
        if (!$component) {
            echo '<div class="notice notice-error"><p>Component not found!</p></div>';
            return;
        }
        
        self::render_form($component);
    }
    
    private static function render_form($component = null) {
        $is_edit = $component !== null;
        $form_title = $is_edit ? 'Edit Component' : 'Add New Component';
        
        $component_types = array(
            'topwear' => 'Top Wear',
            'bottomwear' => 'Bottom Wear',
            'jewellery' => 'Jewellery',
            'accessories' => 'Accessories',
            'footwear' => 'Footwear'
        );
        
        ?>
        <h2><?php echo $form_title; ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('ces_component_action', 'ces_component_nonce'); ?>
            <?php if ($is_edit): ?>
                <input type="hidden" name="component_id" value="<?php echo $component->id; ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="component_name">Component Name</label></th>
                    <td><input type="text" id="component_name" name="component_name" value="<?php echo $is_edit ? esc_attr($component->name) : ''; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="component_description">Description</label></th>
                    <td><textarea id="component_description" name="component_description" rows="4" cols="50"><?php echo $is_edit ? esc_textarea($component->description) : ''; ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="component_type">Component Type</label></th>
                    <td>
                        <select id="component_type" name="component_type" required>
                            <option value="">Select Type</option>
                            <?php foreach ($component_types as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($is_edit && $component->component_type === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="component_status">Status</label></th>
                    <td>
                        <select id="component_status" name="component_status">
                            <option value="active" <?php echo ($is_edit && $component->status === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($is_edit && $component->status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit_component" class="button button-primary" value="<?php echo $is_edit ? 'Update Component' : 'Add Component'; ?>">
                <a href="?page=costume-enquiry-components" class="button">Cancel</a>
            </p>
        </form>
        <?php
    }
    
    private static function render_options_manager($component_id) {
        global $wpdb;
        
        $component = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}costume_components WHERE id = %d", $component_id
        ));
        
        $templates = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}costume_templates WHERE status = 'active' ORDER BY name");
        
        $selected_template = isset($_GET['template_id']) ? intval($_GET['template_id']) : ($templates ? $templates[0]->id : 0);
        
        if ($selected_template) {
            $options = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}component_options WHERE component_id = %d AND template_id = %d",
                $component_id, $selected_template
            ));
        }
        
        ?>
        <h2>Component Options: <?php echo esc_html($component->name); ?></h2>
        
        <div class="ces-template-selector">
            <label for="template_selector">Select Template:</label>
            <select id="template_selector" onchange="window.location.href='?page=costume-enquiry-components&action=options&component_id=<?php echo $component_id; ?>&template_id=' + this.value;">
                <?php foreach ($templates as $template): ?>
                    <option value="<?php echo $template->id; ?>" <?php selected($selected_template, $template->id); ?>>
                        <?php echo esc_html($template->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($selected_template): ?>
            <form method="post" action="">
                <?php wp_nonce_field('ces_component_action', 'ces_component_nonce'); ?>
                <input type="hidden" name="component_id" value="<?php echo $component_id; ?>">
                <input type="hidden" name="template_id" value="<?php echo $selected_template; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="colors">Available Colors</label></th>
                        <td>
                            <input type="text" id="colors" name="colors" value="<?php echo $options ? esc_attr(implode(', ', json_decode($options->colors, true))) : '#FF0000, #00FF00, #0000FF, #FFFF00, #FF00FF'; ?>" class="regular-text">
                            <p class="description">Enter color hex codes separated by commas (e.g., #FF0000, #00FF00, #0000FF)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fabrics">Available Fabrics</label></th>
                        <td>
                            <input type="text" id="fabrics" name="fabrics" value="<?php echo $options ? esc_attr(implode(', ', json_decode($options->fabrics, true))) : 'Silk, Cotton, Georgette, Chiffon'; ?>" class="regular-text">
                            <p class="description">Enter fabric names separated by commas</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sizes">Available Sizes</label></th>
                        <td>
                            <input type="text" id="sizes" name="sizes" value="<?php echo $options ? esc_attr(implode(', ', json_decode($options->sizes, true))) : 'XS, S, M, L, XL, XXL'; ?>" class="regular-text">
                            <p class="description">Enter sizes separated by commas</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="is_required">Required Component</label></th>
                        <td>
                            <input type="checkbox" id="is_required" name="is_required" value="1" <?php echo ($options && $options->is_required) ? 'checked' : ''; ?>>
                            <label for="is_required">This component is required for this costume type</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sort_order">Sort Order</label></th>
                        <td>
                            <input type="number" id="sort_order" name="sort_order" value="<?php echo $options ? $options->sort_order : 0; ?>" min="0">
                            <p class="description">Lower numbers appear first</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit_options" class="button button-primary" value="Save Options">
                    <a href="?page=costume-enquiry-components" class="button">Back to Components</a>
                </p>
            </form>
        <?php endif; ?>
        <?php
    }
}