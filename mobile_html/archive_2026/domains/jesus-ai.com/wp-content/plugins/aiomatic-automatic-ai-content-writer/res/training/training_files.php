<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="aiomatic-modal-content">
<?php
if(isset($aiomatic_data) && is_array($aiomatic_data) && count($aiomatic_data)):
    ?>
    <table class="wp-list-table widefat fixed striped table-view-list comments">
        <thead>
        <tr>
            <th>ID</th>
            <th>Purpose</th>
            <th>Size</th>
            <th>Created At</th>
            <th>Filename</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach($aiomatic_data as $item){
            ?>
            <tr>
                <td><?php echo esc_html($item->id)?></td>
                <td><?php echo esc_html($item->purpose)?></td>
                <td><?php echo esc_html(size_format($item->bytes))?></td>
                <td><?php echo esc_html(date('Y-m-d H:i:s',$item->created_at))?></td>
                <td><?php echo esc_html($item->filename)?></td>
                <td><?php echo esc_html($item->status)?></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
<?php
else:
    ?>
    No training file
<?php
endif;
?>

</div>
