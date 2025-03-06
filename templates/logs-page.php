<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>Vector Export Logs</h1>
    
    <h2>Export Log</h2>
    <div class="log-viewer">
        <pre><?php echo esc_html(file_exists($export_log_file) ? file_get_contents($export_log_file) : 'No export logs yet.'); ?></pre>
    </div>
    
    <h2>API Log</h2>
    <div class="log-viewer">
        <pre><?php echo esc_html(file_exists($api_log_file) ? file_get_contents($api_log_file) : 'No API logs yet.'); ?></pre>
    </div>
</div>
