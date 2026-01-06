<?php
/**
 * List all logs paginated
 * Join with task table to get task name and url
 */
// Check if user has permission to access this page
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get current page number
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$per_page = 10;
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : null;

// Get all logs paginated
$logs = Scraper_Log::get_all_task_logs($task_id, $page, $per_page);
?>
<div class="wrap">
	<h1>Logs</h1>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<!-- <th>ID</th> -->
				<th>Task Name</th>
				<th>Task URL</th>
				<th width="70">Post ID</th>
				<th width="150">Running Status</th>
				<th>Running Log</th>
				<th width="140">Created At</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($logs as $log) : ?>
				<?php
				$post_link = get_edit_post_link($log->post_id);
				$task_status_icon = $log->running_status === 'success' ? 'dashicons-yes' : 'dashicons-no';
				?>
				<tr>
					<!-- <td><?php echo $log->id; ?></td> -->
					<td><?php echo $log->task_name ?? 'N/A'; ?> <small>(<?php echo (TDX_CONST['schedule_interval'][$log->schedule] ?? '--'); ?>)</small></td>
					<td><?php echo $log->url; ?></td>
					<td><a href="<?php echo $post_link; ?>"><?php echo $log->post_id; ?></a></td>
					<td>
						<?php
						printf('<span class="dashicons %s"></span>', $task_status_icon);
						echo $log->running_status;
						?>
					</td>
					<td><?php echo $log->running_log; ?></td>
					<td><?php echo $log->created_at; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<div class="tablenav-pages">
	<?php
	// Pagination
	$total_logs = Scraper_Log::get_total_logs_count($task_id);
	$total_pages = ceil($total_logs / $per_page);
	$page_url = admin_url('admin.php?page=scraper-plugin-logs');
	echo paginate_links([
		'base' => $page_url . '%_%',
		'format' => '&p=%#%',
		'current' => $page,
		'total' => $total_pages,
	]);
	?>
	</div>
</div>
