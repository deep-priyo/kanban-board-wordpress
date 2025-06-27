<?php
class WPTaskBoard_Blocks {

    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_assets', array($this, 'enqueue_block_assets'));
    }

    public function register() {
        // This method is called from the main plugin file
        // The actual registration happens in the constructor
    }

    public function register_blocks() {
        // Register the kanban board block
        register_block_type(WPTASKBOARD_PLUGIN_DIR . 'blocks/kanban-board', array(
            'render_callback' => array($this, 'render_kanban_board')
        ));
    }

    public function enqueue_block_assets() {
        // Enqueue the CSS for both editor and frontend
        wp_enqueue_style(
            'wptaskboard-kanban-style',
            WPTASKBOARD_PLUGIN_URL . 'assets/css/kanban-board.css',
            array(),
            WPTASKBOARD_VERSION
        );

        // Enqueue JavaScript for the frontend
        wp_enqueue_script(
            'wptaskboard-kanban-frontend',
            WPTASKBOARD_PLUGIN_URL . 'assets/js/kanban-board.js',
            array('jquery'),
            WPTASKBOARD_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('wptaskboard-kanban-frontend', 'wptaskboard_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wptaskboard_nonce'),
            'rest_url' => rest_url('wptaskboard/v1/')
        ));
    }

    public function render_kanban_board($attributes) {
        $board_title = $attributes['boardTitle'] ?? 'My Task Board';
        $show_add_button = $attributes['showAddButton'] ?? true;

        // Get tasks from database
        if (class_exists('WPTaskBoard_Database')) {
            $database = new WPTaskBoard_Database();
            $tasks = $database->get_tasks();
        } else {
            $tasks = array();
        }

        // Group tasks by status
        $grouped_tasks = array(
            'todo' => array(),
            'in-progress' => array(),
            'done' => array()
        );

        foreach ($tasks as $task) {
            if (isset($grouped_tasks[$task->status])) {
                $grouped_tasks[$task->status][] = $task;
            }
        }

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
                <?php
                $columns = array(
                    'todo' => 'To Do',
                    'in-progress' => 'In Progress',
                    'done' => 'Done'
                );

                foreach ($columns as $status => $title):
                    $column_tasks = $grouped_tasks[$status];
                ?>
                    <div class="kanban-column kanban-column-<?php echo esc_attr($status); ?>">
                        <div class="kanban-column-header">
                            <h3><?php echo esc_html($title); ?></h3>
                            <span class="task-count"><?php echo count($column_tasks); ?></span>
                        </div>
                        <div class="kanban-column-content" data-status="<?php echo esc_attr($status); ?>">
                            <?php if (empty($column_tasks)): ?>
                                <div class="kanban-empty">No tasks</div>
                            <?php else: ?>
                                <?php foreach ($column_tasks as $task): ?>
                                    <div class="kanban-task" data-task-id="<?php echo esc_attr($task->id); ?>" draggable="true">
                                        <div class="task-title"><?php echo esc_html($task->title); ?></div>
                                        <?php if (!empty($task->description)): ?>
                                            <div class="task-description"><?php echo esc_html($task->description); ?></div>
                                        <?php endif; ?>
                                        <div class="task-meta">
                                            <span class="priority-badge priority-<?php echo esc_attr($task->priority); ?>">
                                                <?php echo esc_html($task->priority); ?>
                                            </span>
                                            <?php if (!empty($task->assignee)): ?>
                                                <span class="assignee"><?php echo esc_html($task->assignee); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Add Task Modal (basic version) -->
        <div id="wptaskboard-add-task-modal" style="display: none;">
            <div class="modal-overlay" onclick="wptaskboard_close_add_task_modal()"></div>
            <div class="modal-content">
                <h3>Add New Task</h3>
                <form id="wptaskboard-add-task-form">
                    <input type="text" id="task-title" placeholder="Task Title" required>
                    <textarea id="task-description" placeholder="Task Description"></textarea>
                    <select id="task-priority">
                        <option value="low">Low Priority</option>
                        <option value="medium" selected>Medium Priority</option>
                        <option value="high">High Priority</option>
                    </select>
                    <input type="text" id="task-assignee" placeholder="Assignee (optional)">
                    <div class="modal-actions">
                        <button type="button" onclick="wptaskboard_close_add_task_modal()">Cancel</button>
                        <button type="submit">Add Task</button>
                    </div>
                </form>
            </div>
        </div>

        <style>
        #wptaskboard-add-task-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
        }
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
        }
        .modal-content input, .modal-content textarea, .modal-content select {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .modal-actions button {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal-actions button[type="submit"] {
            background: #007cba;
            color: white;
            border-color: #007cba;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}