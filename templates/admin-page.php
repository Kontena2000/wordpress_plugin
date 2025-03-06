<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>WordPress Content to Vector Exporter</h1>
    
    <form method="post" action="<?php echo admin_url('options.php'); ?>">
        <?php settings_fields('wp-content-to-vector-settings'); ?>
        <table class="form-table">
            <tr>
                <th scope="row">Vector Database Type</th>
                <td>
                    <select name="wpcv_vector_db_type">
                        <option value="pinecone" <?php selected($vector_db_type, 'pinecone'); ?>>Pinecone</option>
                        <option value="weaviate" <?php selected($vector_db_type, 'weaviate'); ?>>Weaviate</option>
                        <option value="milvus" <?php selected($vector_db_type, 'milvus'); ?>>Milvus</option>
                        <option value="qdrant" <?php selected($vector_db_type, 'qdrant'); ?>>Qdrant</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">Vector Database URL</th>
                <td>
                    <input type="text" name="wpcv_vector_db_url" value="<?php echo esc_attr($vector_db_url); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">API Key</th>
                <td>
                    <input type="password" name="wpcv_vector_db_key" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">Export Schedule</th>
                <td>
                    <select name="wpcv_export_schedule">
                        <option value="hourly" <?php selected($export_schedule, 'hourly'); ?>>Hourly</option>
                        <option value="daily" <?php selected($export_schedule, 'daily'); ?>>Daily</option>
                        <option value="weekly" <?php selected($export_schedule, 'weekly'); ?>>Weekly</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">Content Types to Export</th>
                <td>
                    <label>
                        <input type="checkbox" name="export_types[]" value="pages" checked> Pages
                    </label><br>
                    <label>
                        <input type="checkbox" name="export_types[]" value="posts" checked> Posts
                    </label><br>
                    <label>
                        <input type="checkbox" name="export_types[]" value="custom" checked> Custom Post Types
                    </label>
                </td>
            </tr>
        </table>
        <?php submit_button('Save Settings'); ?>
    </form>
    
    <hr>
    
    <h2>Manual Export</h2>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="run_vector_export">
        <?php submit_button('Run Export Now', 'primary', 'run-export'); ?>
    </form>
</div>
