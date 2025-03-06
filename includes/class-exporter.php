<?php
class WPContentToVectorExporter {
    private static $instance = null;
    private $chunk_size = 10;
    private $current_batch = 0;
    private $total_batches = 0;
    private $processed_items = 0;
    private $total_items = 0;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function exportContent() {
        global $wpdb;
        
        $selected_types = get_option('wpcv_export_types', ['posts', 'pages']);
        $post_types = array_map('esc_sql', $selected_types);
        $post_types_str = "'" . implode("','", $post_types) . "'";
        
        $query = $wpdb->prepare(
            "SELECT ID, post_title, post_content, post_type, post_modified 
            FROM {$wpdb->posts} 
            WHERE post_type IN ($post_types_str) 
            AND post_status = 'publish'"
        );
        
        $items = $wpdb->get_results($query);
        $this->total_items = count($items);
        $this->total_batches = ceil($this->total_items / $this->chunk_size);
        
        foreach (array_chunk($items, $this->chunk_size) as $batch) {
            $this->current_batch++;
            foreach ($batch as $item) {
                $this->processItem($item);
                $this->processed_items++;
                update_option('wpcv_export_progress', [
                    'processed' => $this->processed_items,
                    'total' => $this->total_items,
                    'current_batch' => $this->current_batch,
                    'total_batches' => $this->total_batches
                ]);
            }
        }
    }

    private function processItem($item) {
        $content_hash = md5($item->post_title . $item->post_content . $item->post_modified);
        
        if ($this->isAlreadyExported($content_hash)) {
            return;
        }
        
        try {
            $vector_data = $this->generateVector($item);
            $this->saveToVectorDB($vector_data);
            $this->logExport($item, $content_hash);
        } catch (Exception $e) {
            WPContentToVectorErrorRecovery::getInstance()->handleError(
                $e->getMessage(),
                ['item_id' => $item->ID, 'type' => 'processing_error']
            );
        }
    }

    private function isAlreadyExported($content_hash) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_history';
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE content_hash = %s",
            $content_hash
        ));
    }

    private function generateVector($item) {
        $content = strip_tags($item->post_content);
        $title = strip_tags($item->post_title);
        $combined_text = $title . "\n" . $content;
        
        // Here you would integrate with your chosen embedding API
        // For example, OpenAI's text-embedding-ada-002
        return [
            'id' => $item->ID,
            'title' => $title,
            'content' => $content,
            'vector' => [] // Placeholder for actual vector data
        ];
    }

    private function saveToVectorDB($vector_data) {
        $db_type = get_option('wpcv_vector_db_type', 'pinecone');
        $db_url = get_option('wpcv_vector_db_url', '');
        $db_key = get_option('wpcv_vector_db_key', '');
        
        if (empty($db_url) || empty($db_key)) {
            throw new Exception('Vector database configuration is incomplete');
        }
        
        // Here you would implement the actual vector DB insertion
        // This is a placeholder for the actual implementation
        return true;
    }

    private function logExport($item, $content_hash) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vector_export_history';
        
        $wpdb->insert(
            $table_name,
            [
                'content_hash' => $content_hash,
                'content_type' => $item->post_type,
                'content_id' => $item->ID,
                'export_time' => current_time('mysql'),
                'status' => 'completed',
                'processing_time' => 0 // You would calculate actual processing time
            ]
        );
    }
}
