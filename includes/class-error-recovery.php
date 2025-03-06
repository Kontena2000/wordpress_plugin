<?php
class WPContentToVectorErrorRecovery {
    private static $instance = null;
    private $max_retries = 3;
    private $retry_delay = 300; // 5 minutes
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function handleError($error, $context = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_errors';
        
        $wpdb->insert(
            $table_name,
            array(
                'error_message' => $error,
                'error_context' => json_encode($context),
                'created_at' => current_time('mysql'),
                'retry_count' => 0,
                'status' => 'pending'
            )
        );
        
        $this->scheduleRetry($wpdb->insert_id);
        $this->notifyAdmin($error, $context);
    }

    private function scheduleRetry($error_id) {
        if (!wp_next_scheduled('wp_content_to_vector_retry', array($error_id))) {
            wp_schedule_single_event(
                time() + $this->retry_delay,
                'wp_content_to_vector_retry',
                array($error_id)
            );
        }
    }

    public function retryFailedExport($error_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_errors';
        
        $error = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $error_id
        ));
        
        if (!$error || $error->retry_count >= $this->max_retries) {
            return false;
        }
        
        try {
            // Attempt recovery based on error context
            $context = json_decode($error->error_context, true);
            $success = $this->performRecoveryAction($context);
            
            if ($success) {
                $wpdb->update(
                    $table_name,
                    array('status' => 'resolved'),
                    array('id' => $error_id)
                );
                return true;
            }
        } catch (Exception $e) {
            $wpdb->update(
                $table_name,
                array(
                    'retry_count' => $error->retry_count + 1,
                    'last_retry' => current_time('mysql')
                ),
                array('id' => $error_id)
            );
            
            if ($error->retry_count + 1 < $this->max_retries) {
                $this->scheduleRetry($error_id);
            }
        }
        
        return false;
    }

    private function performRecoveryAction($context) {
        // Implement specific recovery actions based on error context
        switch ($context['type']) {
            case 'db_connection':
                return $this->recoverDBConnection();
            case 'api_error':
                return $this->recoverAPIError($context);
            case 'processing_error':
                return $this->recoverProcessingError($context);
            default:
                return false;
        }
    }

    private function notifyAdmin($error, $context) {
        $to = get_option('admin_email');
        $subject = 'Vector Export Error';
        $message = sprintf(
            "An error occurred during vector export:\n\nError: %s\nContext: %s",
            $error,
            json_encode($context, JSON_PRETTY_PRINT)
        );
        
        wp_mail($to, $subject, $message);
    }
}
