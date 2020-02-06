<h1><?php echo __('Last logged in users'); ?></h1>
<p><?php echo __('Last %1$d logged in users.', ($limitUsers ?? 5)); ?></p>
<div class="rd-datatable-wrapper">
    <table class="rd-datatable h-border">
        <thead>
            <tr>
                <th><?php echo __('Username'); ?></th>
                <th><?php echo __('Date/time'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (isset($result) && is_array($result) && !empty($result)) {
                foreach ($result as $row) {
                    ?> 
            <tr>
                <td><?php if (isset($editPermission) && $editPermission === true) {?><a href="<?php echo ($editUserUrlBase ?? '/admin/users/edit') . '/' . $row->user_id; ?>"><?php } ?><?php echo $row->user_login; ?><?php if (isset($editPermission) && $editPermission === true) {?></a><?php } ?></td>
                <td><?php echo rdbaGetDatetime($row->userlogin_date_gmt, ($rdbadmin_SiteTimezone ?? 'Asia/Bangkok')); ?></td>
            </tr>
                    <?php
                }// endforeach;
                unset($row);
            } else {
                ?> 
            <tr>
                <td colspan="2"><?php echo __('No data'); ?></td>
            </tr>
                <?php
            }
            unset($result);
            ?> 
        </tbody>
    </table>
</div>