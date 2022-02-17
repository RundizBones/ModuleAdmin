<h1><?php echo __('Users'); ?></h1>

<p><?php echo sprintf(__('Total users: %d'), ($totalUsers ?? 0)); ?></p>
<p><?php echo sprintf(__('Enabled users: %d'), ($totalUsersEnabled ?? 0)); ?></p>
<p><?php echo sprintf(__('Disabled users: %d'), ($totalUsersDisabled ?? 0)); ?></p>