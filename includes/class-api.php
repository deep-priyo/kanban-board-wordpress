<?php
class WPTaskBoard_API {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('wp_ajax_wptaskboard_add_task', array($this, 'ajax_add_task'));
        add_action('wp_ajax_nopriv_wptaskboard_add_task', array($this, 'ajax_add_task'));
        add_action('wp_ajax_wptaskboard_update_task', array($this, 'ajax_update_task'));
        add_action('wp_ajax_nopriv_wptaskboard_update_task', array($this, 'ajax_update_task'));
    }

    public function register_routes() {
        register_rest_route('wptaskboard/v1', '/tasks', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tasks'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('wptaskboard/v1', '/tasks', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_task'),
            'permission_callback' => array($this, 'check_permissions')
        ));

        register_rest_route('wptaskboard/v1', '/tasks/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_task'),
            'permission_callback' => array($this, 'check_permissions')
        ));

        register_rest_route('wptaskboard/v1', '/tasks/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_task'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }

    public function check_permissions() {
        return current_user_can('edit_posts');
    }

    public function get_tasks($request) {
        if (!class_exists('WPTaskBoard_Database')) {
            return new WP_Error('no_database', 'Database class not found', array('status' => 500));
        }

        $database = new WPTaskBoard_Database();
        $tasks = $database->get_tasks();

        return rest_ensure_response($tasks);
    }

    public function create_task($request) {
        if (!class_exists('WPTaskBoard_Database')) {
            return new WP_Error('no_database', 'Database class not found', array('status' => 500));
        }

        $params = $request->get_json_params();

        if (empty($params['title'])) {
            return new WP_Error('missing_title', 'Task title is required', array('status' => 400));
        }

        $database = new WPTaskBoard_Database();
        $task_id = $database->create_task($params);

        if ($task_id) {
            $task = $database->get_task($task_id);
            return rest_ensure_response($task);
        }

        return new WP_Error('create_failed', 'Failed to create task', array('status' => 500));
    }

    public function update_task($request) {
        if (!class_exists('WPTaskBoard_Database')) {
            return new WP_Error('no_database', 'Database class not found', array('status' => 500));
        }

        $id = $request->get_param('id');
        $params = $request->get_json_params();

        $database = new WPTaskBoard_Database();
        $result = $database->update_task($id, $params);

        if ($result !== false) {
            $task = $database->get_task($id);
            return rest_ensure_response($task);
        }

        return new WP_Error('update_failed', 'Failed to update task', array('status' => 500));
    }

    public function delete_task($request) {
        if (!class_exists('WPTaskBoard_Database')) {
            return new WP_Error('no_database', 'Database class not found', array('status' => 500));
        }

        $id = $request->get_param('id');

        $database = new WPTaskBoard_Database();
        $result = $database->delete_task($id);

        if ($result) {
            return rest_ensure_response(array('deleted' => true));
        }

        return new WP_Error('delete_failed', 'Failed to delete task', array('status' => 500));
    }

    // AJAX handlers for legacy support
    public function ajax_add_task() {
        check_ajax_referer('wptaskboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied');
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $priority = sanitize_text_field($_POST['priority'] ?? 'medium');
        $assignee = sanitize_text_field($_POST['assignee'] ?? '');

        if (empty($title)) {
            wp_send_json_error('Title is required');
        }

        if (!class_exists('WPTaskBoard_Database')) {
            wp_send_json_error('Database class not found');
        }

        $database = new WPTaskBoard_Database();
        $task_id = $database->create_task(array(
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'assignee' => $assignee,
            'status' => 'todo'
        ));

        if ($task_id) {
            $task = $database->get_task($task_id);
            wp_send_json_success($task);
        } else {
            wp_send_json_error('Failed to create task');
        }
    }

    public function ajax_update_task() {
        check_ajax_referer('wptaskboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied');
        }

        $task_id = intval($_POST['task_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');

        if (!$task_id || !$status) {
            wp_send_json_error('Task ID and status are required');
        }

        if (!class_exists('WPTaskBoard_Database')) {
            wp_send_json_error('Database class not found');
        }

        $database = new WPTaskBoard_Database();
        $result = $database->update_task($task_id, array('status' => $status));

        if ($result !== false) {
            $task = $database->get_task($task_id);
            wp_send_json_success($task);
        } else {
            wp_send_json_error('Failed to update task');
        }
    }
}