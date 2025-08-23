<div class="wrap diploma-builder-admin">
    <h1><?php _e('Diploma Builder', 'diploma-builder'); ?></h1>
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-<?php echo esc_attr($_GET['message_type'] ?? 'success'); ?> is-dismissible">
            <p><?php echo esc_html($_GET['message']); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="diploma-builder-header">
        <div class="diploma-builder-stats">
            <?php 
            $stats = DiplomaBuilder_Database::get_statistics();
            ?>
            <div class="stat-card">
                <h3><?php echo esc_html($stats['total']); ?></h3>
                <p><?php _e('Total Diplomas', 'diploma-builder'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo esc_html($stats['unique_users']); ?></h3>
                <p><?php _e('Unique Users', 'diploma-builder'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo esc_html($stats['this_week']); ?></h3>
                <p><?php _e('This Week', 'diploma-builder'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo esc_html($stats['this_month']); ?></h3>
                <p><?php _e('This Month', 'diploma-builder'); ?></p>
            </div>
        </div>
        
        <div class="diploma-builder-actions">
            <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=export_diplomas&nonce=' . wp_create_nonce('export_diplomas'))); ?>" class="button button-primary">
                <?php _e('Export All Diplomas', 'diploma-builder'); ?>
            </a>
        </div>
    </div>
    
    <form method="get" action="">
        <input type="hidden" name="page" value="diploma-builder" />
        <p class="search-box">
            <label class="screen-reader-text" for="diploma-search-input"><?php _e('Search Diplomas:', 'diploma-builder'); ?></label>
            <input type="search" id="diploma-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php _e('Search Diplomas', 'diploma-builder'); ?>">
        </p>
    </form>
    
    <div class="tablenav top">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(__('%s diplomas', 'diploma-builder'), number_format_i18n($total_diplomas)); ?></span>
            <?php if ($total_pages > 1): ?>
                <span class="pagination-links">
                    <?php if ($page > 1): ?>
                        <a class="first-page button" href="<?php echo esc_url(add_query_arg('paged', 1)); ?>">«</a>
                        <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $page - 1)); ?>">‹</a>
                    <?php endif; ?>
                    
                    <span class="paging-input">
                        <span class="tablenav-paging-text">
                            <?php printf(__('%1$s of %2$s', 'diploma-builder'), number_format_i18n($page), number_format_i18n($total_pages)); ?>
                        </span>
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>">›</a>
                        <a class="last-page button" href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>">»</a>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped diplomas">
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column">
                    <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'diploma-builder'); ?></label>
                    <input id="cb-select-all-1" type="checkbox">
                </td>
                <th scope="col" class="manage-column column-school"><?php _e('School Name', 'diploma-builder'); ?></th>
                <th scope="col" class="manage-column column-user"><?php _e('User', 'diploma-builder'); ?></th>
                <th scope="col" class="manage-column column-date"><?php _e('Graduation Date', 'diploma-builder'); ?></th>
                <th scope="col" class="manage-column column-location"><?php _e('Location', 'diploma-builder'); ?></th>
                <th scope="col" class="manage-column column-style"><?php _e('Style', 'diploma-builder'); ?></th>
                <th scope="col" class="manage-column column-downloads"><?php _e('Downloads', 'diploma-builder'); ?></th>
                <th scope="col" class="manage-column column-created"><?php _e('Created', 'diploma-builder'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'diploma-builder'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($diplomas)): ?>
                <tr>
                    <td colspan="9"><?php _e('No diplomas found.', 'diploma-builder'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($diplomas as $diploma): ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="diploma_ids[]" value="<?php echo esc_attr($diploma->id); ?>">
                        </th>
                        <td class="school column-school">
                            <strong><?php echo esc_html($diploma->school_name); ?></strong>
                            <?php if ($diploma->student_name): ?>
                                <br><small><?php echo esc_html($diploma->student_name); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="user column-user">
                            <?php 
                            if ($diploma->user_id) {
                                $user = get_user_by('id', $diploma->user_id);
                                echo $user ? esc_html($user->display_name) : __('Unknown User', 'diploma-builder');
                            } else {
                                echo __('Guest', 'diploma-builder');
                            }
                            ?>
                        </td>
                        <td class="date column-date">
                            <?php echo esc_html($diploma->graduation_date); ?>
                        </td>
                        <td class="location column-location">
                            <?php echo esc_html($diploma->city . ', ' . $diploma->state); ?>
                        </td>
                        <td class="style column-style">
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $diploma->diploma_style))); ?>
                        </td>
                        <td class="downloads column-downloads">
                            <?php echo esc_html($diploma->download_count); ?>
                        </td>
                        <td class="created column-created">
                            <?php echo esc_html(date('M j, Y', strtotime($diploma->created_at))); ?>
                        </td>
                        <td class="actions column-actions">
                            <button type="button" class="button view-diploma" data-diploma-id="<?php echo esc_attr($diploma->id); ?>">
                                <?php _e('View', 'diploma-builder'); ?>
                            </button>
                            <button type="button" class="button delete-diploma" data-diploma-id="<?php echo esc_attr($diploma->id); ?>">
                                <?php _e('Delete', 'diploma-builder'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(__('%s diplomas', 'diploma-builder'), number_format_i18n($total_diplomas)); ?></span>
            <?php if ($total_pages > 1): ?>
                <span class="pagination-links">
                    <?php if ($page > 1): ?>
                        <a class="first-page button" href="<?php echo esc_url(add_query_arg('paged', 1)); ?>">«</a>
                        <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $page - 1)); ?>">‹</a>
                    <?php endif; ?>
                    
                    <span class="paging-input">
                        <span class="tablenav-paging-text">
                            <?php printf(__('%1$s of %2$s', 'diploma-builder'), number_format_i18n($page), number_format_i18n($total_pages)); ?>
                        </span>
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>">›</a>
                        <a class="last-page button" href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>">»</a>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="diploma-preview-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('Diploma Preview', 'diploma-builder'); ?></h2>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="diploma-preview-content"></div>
        </div>
    </div>
</div>