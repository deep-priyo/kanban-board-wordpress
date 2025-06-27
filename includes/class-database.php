<?php
class WPTaskBoard_Database {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wptaskboard_tasks';
    }

    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'todo',
            priority varchar(10) DEFAULT 'medium',
            assignee varchar(100),
            due_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function get_tasks() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
    }

    public function get_task($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));
    }

    public function create_task($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'title' => sanitize_text_field($data['title']),
                'description' => sanitize_textarea_field($data['description'] ?? ''),
                'status' => sanitize_text_field($data['status'] ?? 'todo'),
                'priority' => sanitize_text_field($data['priority'] ?? 'medium'),
                'assignee' => sanitize_text_field($data['assignee'] ?? ''),
                'due_date' => $data['due_date'] ?? null
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result !== false) {
            return $wpdb->insert_id;
        }
        return false;
    }

    public function update_task($id, $data) {
        global $wpdb;

        $update_data = array();
        $update_format = array();

        if (isset($data['title'])) {
            $update_data['title'] = sanitize_text_field($data['title']);
            $update_format[] = '%s';
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $update_format[] = '%s';
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $update_format[] = '%s';
        }
        if (isset($data['priority'])) {
            $update_data['priority'] = sanitize_text_field($data['priority']);
            $update_format[] = '%s';
        }
        if (isset($data['assignee'])) {
            $update_data['assignee'] = sanitize_text_field($data['assignee']);
            $update_format[] = '%s';
        }
        if (isset($data['due_date'])) {
            $update_data['due_date'] = $data['due_date'];
            $update_format[] = '%s';
        }

        return $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );
    }

    public function delete_task($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $id), array('%d'));
    }
}