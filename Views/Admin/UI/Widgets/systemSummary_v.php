<h1><?php echo __('System info'); ?></h1>
<div class="rd-datatable-wrapper">
    <table class="rd-datatable">
        <tbody>
            <?php 
            if (isset($result) && is_array($result) && !empty($result)) {
                foreach ($result as $row) {
                    ?> 
            <tr>
                <td class="text-flow-nowrap"><?php echo $row['name']; ?></td>
                <td><?php echo $row['value']; ?></td>
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