<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="aiomatic-modal-content">
    <?php
    if(isset($aiomatic_data) && is_array($aiomatic_data) && count($aiomatic_data)):
        usort($aiomatic_data, function ($item1, $item2) {
            return $item2->created_at <=> $item1->created_at;
        });
        ?>
        <table class="wp-list-table widefat fixed striped table-view-list comments">
            <thead>
            <tr>
                <th>Object</th>
                <th>Level</th>
                <th>Created At</th>
                <th>Message</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach($aiomatic_data as $item){
                ?>
                <tr>
                    <td><?php echo esc_html($item->object)?></td>
                    <td><?php echo esc_html($item->level)?></td>
                    <td><?php echo esc_html(date('Y-m-d H:i:s',$item->created_at))?></td>
                    <td><?php echo esc_html($item->message)?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    <?php
    else:
        ?>
        No events
    <?php
    endif;
    ?>

</div>
