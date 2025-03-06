<?php
class WPContentToVectorScheduler {
    private static $instance = null;
    private $schedules = [
        'every_15_minutes' => ['interval' => 900, 'display' => 'Every 15 minutes'],
        'every_30_minutes' => ['interval' => 1800, 'display' => 'Every 30 minutes'],
        'twice_daily' => ['interval' => 43200, 'display' => 'Twice Daily'],
        'custom' => ['interval' => 3600, 'display' => 'Custom Schedule']
    ];
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_filter('cron_schedules', array($this, 'addCustomSchedules'));
        add_action('admin_init', array($this, 'setupSchedule'));
    }

    public function addCustomSchedules($schedules) {
        return array_merge($schedules, $this->schedules);
    }

    public function setupSchedule() {
        $schedule = get_option('wpcv_export_schedule', 'daily');
        $custom_time = get_option('wpcv_custom_schedule_time', '');
        
        wp_clear_scheduled_hook('wp_content_to_vector_cron');
        
        if ($schedule === 'custom' && !empty($custom_time)) {
            $this->setupCustomSchedule($custom_time);
        } else {
            wp_schedule_event(time(), $schedule, 'wp_content_to_vector_cron');
        }
    }

    private function setupCustomSchedule($time) {
        $current_time = current_time('timestamp');
        $schedule_time = strtotime($time);
        
        if ($schedule_time < $current_time) {
            $schedule_time = strtotime('tomorrow ' . $time);
        }
        
        wp_schedule_event($schedule_time, 'daily', 'wp_content_to_vector_cron');
    }

    public function getNextScheduledRun() {
        $next_run = wp_next_scheduled('wp_content_to_vector_cron');
        return $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled';
    }

    public function getCurrentSchedule() {
        return [
            'schedule' => get_option('wpcv_export_schedule', 'daily'),
            'custom_time' => get_option('wpcv_custom_schedule_time', ''),
            'next_run' => $this->getNextScheduledRun()
        ];
    }
}
