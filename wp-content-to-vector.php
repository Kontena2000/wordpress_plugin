
<?php
/**
 * Plugin Name: WordPress Content to Vector Exporter
 * Plugin URI: https://github.com/Kontena2000/wordpress_plugin
 * Description: Export WordPress content to vector databases with comprehensive logging and scheduling capabilities
 * Version: 1.0.0
 * Author: Softgen
 * Author URI: https://github.com/Kontena2000
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-content-to-vector
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-statistics.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-scheduler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-error-recovery.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-exporter.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';

class WPContentToVector {
    private static $instance = null;
    private $logger;
    private $last_export_time;
    private $export_log_file;
    private $api_log_file;
    private $admin;
    private $stats;
    private $scheduler;
    private $error_recovery;
    private $exporter;
    private $database;

    public static function getInstance() {
        if (self::$instance == null) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->export_log_file = $upload_dir['basedir'] . '/vector-export-log.txt';
        $this->api_log_file = $upload_dir['basedir'] . '/vector-api-log.txt';
        
        $this->admin = WPContentToVectorAdmin::getInstance();
        $this->stats = WPContentToVectorStats::getInstance();
        $this->scheduler = WPContentToVectorScheduler::getInstance();
        $this->error_recovery = WPContentToVectorErrorRecovery::getInstance();
        $this->exporter = WPContentToVectorExporter::getInstance();
        $this->database = WPContentToVectorDatabase::getInstance();
        
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('wp_content_to_vector_cron', array($this, 'scheduledExport'));
        add_action('wp_ajax_get_export_progress', array($this, 'getExportProgress'));
        
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
    }

    public function enqueueAdminAssets($hook) {
        if (strpos($hook, 'wp-content-to-vector') === false) {
            return;
        }
        
        wp_enqueue_style('wp-content-to-vector-admin', plugins_url('css/admin.css', __FILE__));
        wp_enqueue_script('wp-content-to-vector-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_localize_script('wp-content-to-vector-admin', 'wpContentToVector', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_content_to_vector_nonce')
        ));
    }

    public function getExportLogFile() { return $this->export_log_file; }
    public function getApiLogFile() { return $this->api_log_file; }

    public function addAdminMenu() {
        add_menu_page(
            'Content to Vector',
            'Content to Vector',
            'manage_options',
            'wp-content-to-vector',
            array($this->admin, 'renderAdminPage'),
            'dashicons-database-export',
            30
        );
        
        add_submenu_page(
            'wp-content-to-vector',
            'Logs',
            'Logs',
            'manage_options',
            'wp-content-to-vector-logs',
            array($this->admin, 'renderLogsPage')
        );
        
        add_submenu_page(
            'wp-content-to-vector',
            'Statistics',
            'Statistics',
            'manage_options',
            'wp-content-to-vector-stats',
            array($this->admin, 'renderStatsPage')
        );
    }

    public function registerSettings() {
        register_setting('wp-content-to-vector-settings', 'wpcv_vector_db_type');
        register_setting('wp-content-to-vector-settings', 'wpcv_vector_db_url');
        register_setting('wp-content-to-vector-settings', 'wpcv_vector_db_key');
        register_setting('wp-content-to-vector-settings', 'wpcv_export_schedule');
        register_setting('wp-content-to-vector-settings', 'wpcv_chunk_size');
        register_setting('wp-content-to-vector-settings', 'wpcv_export_types');
    }

    public function log($message, $type = 'export') {
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] {$message}\n";
        $log_file = $type === 'export' ? $this->export_log_file : $this->api_log_file;
        error_log($log_entry, 3, $log_file);
    }

    public function scheduledExport() {
        $this->log("Starting scheduled export");
        $this->exporter->exportContent();
    }

    public function getExportProgress() {
        check_ajax_referer('wp_content_to_vector_nonce', 'nonce');
        $progress = get_option('wpcv_export_progress', [
            'processed' => 0,
            'total' => 0,
            'current_batch' => 0,
            'total_batches' => 0,
            'status' => 'idle'
        ]);
        wp_send_json($progress);
    }
}

function wp_content_to_vector_init() {
    WPContentToVector::getInstance();
}
add_action('plugins_loaded', 'wp_content_to_vector_init');

register_activation_hook(__FILE__, 'wp_content_to_vector_activate');
register_deactivation_hook(__FILE__, 'wp_content_to_vector_deactivate');

function wp_content_to_vector_activate() {
    WPContentToVectorDatabase::getInstance()->createTables();
    
    $upload_dir = wp_upload_dir();
    $export_log = $upload_dir['basedir'] . '/vector-export-log.txt';
    $api_log = $upload_dir['basedir'] . '/vector-api-log.txt';
    
    if (!file_exists($export_log)) file_put_contents($export_log, "Export Log Initialized: " . current_time('mysql') . "\n");
    if (!file_exists($api_log)) file_put_contents($api_log, "API Log Initialized: " . current_time('mysql') . "\n");

    flush_rewrite_rules();
}

function wp_content_to_vector_deactivate() {
    wp_clear_scheduled_hook('wp_content_to_vector_cron');
    flush_rewrite_rules();
}
