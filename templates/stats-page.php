<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>Export Statistics</h1>
    
    <div class="stats-container">
        <div class="stat-box">
            <h3>Overview</h3>
            <p>Total Exports: <?php echo esc_html($stats['total_exports']); ?></p>
            <p>Error Rate: <?php echo number_format($stats['error_rate'], 2); ?>%</p>
            <p>Content Coverage: <?php echo number_format($stats['content_coverage']['coverage_percentage'], 2); ?>%</p>
        </div>
        
        <div class="stat-box">
            <h3>Processing Times</h3>
            <p>Average: <?php echo number_format($stats['processing_times']->avg_time, 2); ?>s</p>
            <p>Fastest: <?php echo number_format($stats['processing_times']->min_time, 2); ?>s</p>
            <p>Slowest: <?php echo number_format($stats['processing_times']->max_time, 2); ?>s</p>
        </div>
        
        <div class="stat-box">
            <h3>Schedule Information</h3>
            <p>Current Schedule: <?php echo esc_html($schedule_info['schedule']); ?></p>
            <p>Next Run: <?php echo esc_html($schedule_info['next_run']); ?></p>
        </div>
    </div>
    
    <div class="stats-charts">
        <canvas id="dailyExportsChart"></canvas>
        <canvas id="contentTypeChart"></canvas>
    </div>
    
    <h2>Recent Exports</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Status</th>
                <th>Processing Time</th>
                <th>Export Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stats['recent_exports'] as $export): ?>
            <tr>
                <td><?php echo esc_html($export->id); ?></td>
                <td><?php echo esc_html($export->content_type); ?></td>
                <td><?php echo esc_html($export->status); ?></td>
                <td><?php echo number_format($export->processing_time, 2); ?>s</td>
                <td><?php echo esc_html($export->export_time); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
