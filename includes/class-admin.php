<?php
class WPContentToVectorAdmin {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function renderAdminPage() {
        $vector_db_type = get_option('wpcv_vector_db_type', 'pinecone');
        $vector_db_url = get_option('wpcv_vector_db_url', '');
        $export_schedule = get_option('wpcv_export_schedule', 'daily');
        
        include(plugin_dir_path(__FILE__) . '../templates/admin-page.php');
    }

    public function renderLogsPage() {
        $export_log_file = WPContentToVector::getInstance()->getExportLogFile();
        $api_log_file = WPContentToVector::getInstance()->getApiLogFile();
        
        include(plugin_dir_path(__FILE__) . '../templates/logs-page.php');
    }

    public function renderStatsPage() {
        $stats = WPContentToVectorStats::getInstance()->getExportStats();
        $schedule_info = WPContentToVectorScheduler::getInstance()->getCurrentSchedule();
        
        include(plugin_dir_path(__FILE__) . '../templates/stats-page.php');
    }
}
