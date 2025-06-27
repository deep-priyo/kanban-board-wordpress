<?php
/**
 * Plugin Name: WPTaskBoard
 * Plugin URI: https://yoursite.com/wptaskboard
 * Description: A full-stack Kanban task manager with Gutenberg blocks
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wptaskboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPTASKBOARD_VERSION', '1.0.0');
define('WPTASKBOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPTASKBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Debug: Add error logging
error_log('WPTaskBoard: Plugin loading started');

// Main plugin class
class WPTaskBoard {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Debug: Add admin notice
        add_action('admin_notices', array($this, 'debug_notices'));
    }

    public function init() {
        error_log('WPTaskBoard: Init method called');

        // Register the block
        $this->register_kanban_block();

        // Load other components
        $this->load_existing_files();
    }

    public function register_kanban_block() {
        error_log('WPTaskBoard: Registering Kanban block');

        // Register the block type
        register_block_type('wptaskboard/kanban-board', array(
            'attributes' => array(
                'boardTitle' => array(
                    'type' => 'string',
                    'default' => 'My Task Board'
                ),
                'showAddButton' => array(
                    'type' => 'boolean',
                    'default' => true
                )
            ),
            'render_callback' => array($this, 'render_kanban_block'),
            'editor_script' => 'wptaskboard-editor-script',
            'style' => 'wptaskboard-frontend-style',
            'editor_style' => 'wptaskboard-editor-style'
        ));

        error_log('WPTaskBoard: Block registered successfully');
    }

    public function enqueue_block_editor_assets() {
        error_log('WPTaskBoard: Enqueuing block editor assets');

        // Register and enqueue the editor script
        wp_register_script(
            'wptaskboard-editor-script',
            WPTASKBOARD_PLUGIN_URL . 'blocks/kanban-board/index.js',
            array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n'),
            WPTASKBOARD_VERSION,
            true
        );

        wp_enqueue_script('wptaskboard-editor-script');

        // Register and enqueue editor styles
        wp_register_style(
            'wptaskboard-editor-style',
            WPTASKBOARD_PLUGIN_URL . 'assets/css/kanban-board.css',
            array(),
            WPTASKBOARD_VERSION
        );

        wp_enqueue_style('wptaskboard-editor-style');

        error_log('WPTaskBoard: Block editor assets enqueued');
    }

    public function enqueue_frontend_assets() {
        // Register frontend styles
        wp_register_style(
            'wptaskboard-frontend-style',
            WPTASKBOARD_PLUGIN_URL . 'assets/css/kanban-board.css',
            array(),
            WPTASKBOARD_VERSION
        );

        // Register frontend JavaScript
        wp_register_script(
            'wptaskboard-frontend-script',
            WPTASKBOARD_PLUGIN_URL . 'assets/js/kanban-board.js',
            array('jquery'),
            WPTASKBOARD_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('wptaskboard-frontend-script', 'wptaskboard_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wptaskboard_nonce')
        ));
    }

    public function render_kanban_block($attributes) {
        error_log('WPTaskBoard: Rendering Kanban block');

        $board_title = isset($attributes['boardTitle']) ? $attributes['boardTitle'] : 'My Task Board';
        $show_add_button = isset($attributes['showAddButton']) ? $attributes['showAddButton'] : true;

        // Enqueue frontend assets when block is rendered
        wp_enqueue_style('wptaskboard-frontend-style');
        wp_enqueue_script('wptaskboard-frontend-script');

        ob_start();
        ?>
        <div class="wptaskboard-kanban-board">
            <div class="kanban-board-header">
                <h2><?php echo esc_html($board_title); ?></h2>
                <?php if ($show_add_button): ?>
                    <button class="kanban-add-task-btn" onclick="wptaskboard_open_add_task_modal()">
                        + Add Task
                    </button>
                <?php endif; ?>
            </div>

            <div class="kanban-board">
                <!-- To Do Column -->
                <div class="kanban-column kanban-column-todo">
                    <div class="kanban-column-header">
                        <h3>To Do</h3>
                        <span class="task-count">0</span>
                    </div>
                    <div class="kanban-column-content" data-status="todo">
                        <div class="kanban-empty">No tasks yet</div>
                    </div>
                </div>

                <!-- In Progress Column -->
                <div class="kanban-column kanban-column-in-progress">
                    <div class="kanban-column-header">
                        <h3>In Progress</h3>
                        <span class="task-count">0</span>
                    </div>
                    <div class="kanban-column-content" data-status="in-progress">
                        <div class="kanban-empty">No tasks yet</div>
                    </div>
                </div>

                <!-- Done Column -->
                <div class="kanban-column kanban-column-done">
                    <div class="kanban-column-header">
                        <h3>Done</h3>
                        <span class="task-count">0</span>
                    </div>
                    <div class="kanban-column-content" data-status="done">
                        <div class="kanban-empty">No tasks yet</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Task Modal (if needed) -->
        <div id="wptaskboard-add-task-modal" style="display: none;">
            <!-- Modal content would go here -->
        </div>
        <?php
        return ob_get_clean();
    }

    private function load_existing_files() {
        // Check if files exist before loading them
        if (file_exists(WPTASKBOARD_PLUGIN_DIR . 'includes/class-database.php')) {
            require_once WPTASKBOARD_PLUGIN_DIR . 'includes/class-database.php';
            error_log('WPTaskBoard: Database class loaded');
            if (class_exists('WPTaskBoard_Database')) {
                $database = new WPTaskBoard_Database();
                $database->create_tables();
            }
        } else {
            error_log('WPTaskBoard: Database class file not found');
        }

        if (file_exists(WPTASKBOARD_PLUGIN_DIR . 'includes/class-api.php')) {
            require_once WPTASKBOARD_PLUGIN_DIR . 'includes/class-api.php';
            error_log('WPTaskBoard: API class loaded');
            if (class_exists('WPTaskBoard_API')) {
                $api = new WPTaskBoard_API();
                $api->register_routes();
            }
        } else {
            error_log('WPTaskBoard: API class file not found');
        }

        if (file_exists(WPTASKBOARD_PLUGIN_DIR . 'includes/class-blocks.php')) {
            require_once WPTASKBOARD_PLUGIN_DIR . 'includes/class-blocks.php';
            error_log('WPTaskBoard: Blocks class loaded');
            if (class_exists('WPTaskBoard_Blocks')) {
                $blocks = new WPTaskBoard_Blocks();
                $blocks->register();
            }
        } else {
            error_log('WPTaskBoard: Blocks class file not found');
        }
    }

    public function debug_notices() {
        if (current_user_can('manage_options')) {
            $missing_files = array();
            $present_files = array();

            $files_to_check = array(
                'includes/class-database.php',
                'includes/class-api.php',
                'includes/class-blocks.php',
                'blocks/kanban-board/index.js',
                'assets/css/kanban-board.css'
            );

            foreach ($files_to_check as $file) {
                if (!file_exists(WPTASKBOARD_PLUGIN_DIR . $file)) {
                    $missing_files[] = $file;
                } else {
                    $present_files[] = $file;
                }
            }

            // Check if block is registered
            $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
            $block_registered = isset($registered_blocks['wptaskboard/kanban-board']);

            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>WPTaskBoard Debug Info:</strong></p>';

            if (!empty($present_files)) {
                echo '<p><strong>✅ Present files:</strong></p>';
                echo '<ul>';
                foreach ($present_files as $file) {
                    echo '<li>' . esc_html($file) . '</li>';
                }
                echo '</ul>';
            }

            if (!empty($missing_files)) {
                echo '<p><strong>❌ Missing files:</strong></p>';
                echo '<ul>';
                foreach ($missing_files as $file) {
                    echo '<li>' . esc_html($file) . '</li>';
                }
                echo '</ul>';
            }

            echo '<p><strong>Block Registration Status:</strong> ' . ($block_registered ? '✅ Registered' : '❌ Not Registered') . '</p>';
            echo '<p><strong>Plugin Version:</strong> ' . WPTASKBOARD_VERSION . '</p>';
            echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
            echo '</div>';
        }
    }

    public function activate() {
        error_log('WPTaskBoard: Plugin activated');

        // Simple activation
        add_option('wptaskboard_version', WPTASKBOARD_VERSION);

        // Create tables if database class exists
        if (class_exists('WPTaskBoard_Database')) {
            $database = new WPTaskBoard_Database();
            $database->create_tables();
        }

        flush_rewrite_rules();
    }

    public function deactivate() {
        error_log('WPTaskBoard: Plugin deactivated');
        // Deactivation logic
        flush_rewrite_rules();
    }
}

// Initialize the plugin
new WPTaskBoard();

// Add a simple admin notice to confirm the plugin is working
add_action('admin_notices', function() {
    if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>WPTaskBoard plugin activated successfully!</p>';
        echo '</div>';
    }
});