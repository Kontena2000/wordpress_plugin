<?php
class WPContentToVectorStats {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getExportStats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_history';
        
        return [
            'total_exports' => $this->getTotalExports(),
            'exports_by_type' => $this->getExportsByType(),
            'recent_exports' => $this->getRecentExports(),
            'error_rate' => $this->getErrorRate(),
            'processing_times' => $this->getProcessingTimes(),
            'daily_stats' => $this->getDailyStats(),
            'content_coverage' => $this->getContentCoverage()
        ];
    }

    private function getTotalExports() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_history';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    private function getExportsByType() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_history';
        return $wpdb->get_results("
            SELECT content_type, COUNT(*) as count,
            AVG(processing_time) as avg_time
            FROM $table_name 
            GROUP BY content_type
        ");
    }

    private function getRecentExports() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_history';
        return $wpdb->get_results("
            SELECT * FROM $table_name 
            ORDER BY export_time DESC 
            LIMIT 10
        ");
    }

    private function getErrorRate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_history';
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $errors = $wpdb->get_var("
            SELECT COUNT(*) FROM $table_name 
            WHERE status = 'error'
        ");
        
        return $total > 0 ? ($errors / $total) * 100 : 0;
    }

    private function getProcessingTimes() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_history';
        return $wpdb->get_row("
            SELECT 
                AVG(processing_time) as avg_time,
                MIN(processing_time) as min_time,
                MAX(processing_time) as max_time
            FROM $table_name
            WHERE processing_time IS NOT NULL
        ");
    }

    private function getDailyStats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_history';
        return $wpdb->get_results("
            SELECT 
                DATE(export_time) as export_date,
                COUNT(*) as total_exports,
                COUNT(CASE WHEN status = 'error' THEN 1 END) as errors,
                AVG(processing_time) as avg_processing_time
            FROM $table_name
            GROUP BY DATE(export_time)
            ORDER BY export_date DESC
            LIMIT 30
        ");
    }

    private function getContentCoverage() {
        global $wpdb;
        $posts_table = $wpdb->prefix . 'posts';
        $history_table = $wpdb->prefix . 'vector_export_history';
        
        $total_content = $wpdb->get_var("
            SELECT COUNT(*) FROM $posts_table 
            WHERE post_type IN ('post', 'page')
            AND post_status = 'publish'
        ");
        
        $exported_content = $wpdb->get_var("
            SELECT COUNT(DISTINCT content_hash) 
            FROM $history_table
        ");
        
        return [
            'total_content' => $total_content,
            'exported_content' => $exported_content,
            'coverage_percentage' => $total_content > 0 ? 
                ($exported_content / $total_content) * 100 : 0
        ];
    }
}
