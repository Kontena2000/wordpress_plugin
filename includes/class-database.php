<?php
class WPContentToVectorDatabase {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $history_table = $wpdb->prefix . 'vector_export_history';
        $errors_table = $wpdb->prefix . 'vector_export_errors';
        $status_table = $wpdb->prefix . 'vector_export_status';

        $sql = [];

        $sql[] = "CREATE TABLE IF NOT EXISTS $history_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_hash varchar(32) NOT NULL,
            content_type varchar(20) NOT NULL,
            content_id bigint(20) NOT NULL,
            export_time datetime NOT NULL,
            status varchar(20) NOT NULL,
            processing_time float NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY content_hash (content_hash),
            KEY content_type (content_type),
            KEY status (status)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS $errors_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            error_message text NOT NULL,
            error_context text NOT NULL,
            created_at datetime NOT NULL,
            retry_count int NOT NULL DEFAULT 0,
            last_retry datetime DEFAULT NULL,
            status varchar(20) NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS $status_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            process_id varchar(32) NOT NULL,
            start_time datetime NOT NULL,
            end_time datetime DEFAULT NULL,
            total_items int NOT NULL,
            processed_items int NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL,
            last_updated datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY process_id (process_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    public function dropTables() {
        global $wpdb;
        $tables = [
            $wpdb->prefix . 'vector_export_history',
            $wpdb->prefix . 'vector_export_errors',
            $wpdb->prefix . 'vector_export_status'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
}
