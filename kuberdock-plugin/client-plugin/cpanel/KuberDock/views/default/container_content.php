<?php foreach($pods as $pod):
    if($pod->status == 'stopped') {
        $statusClass = 'container-start';
        $statusText = 'Start';
    } else {
        $statusClass = 'container-stop';
        $statusText = 'Stop';
    }
    ?>
    <tr>
        <td class="col-md-3"><a href="?a=podDetails&podName=<?php echo $pod->name?>"><?php echo $pod->name?></a></td>
        <td class="col-md-3">
            <?php echo 'Public IP: ' . isset($pod->public_ip) && $pod->public_ip ? $pod->public_ip :
                (isset($pod->labels['kuberdock-public-ip']) ? $pod->labels['kuberdock-public-ip'] : 'none')?>
        </td>
        <td class="col-md-3">
            <button type="button" class="<?php echo $statusClass?>" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="<?php echo $statusText?>"></button>
            <!--<button type="button" class="btn btn-default btn-sm pod-refresh">
                <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
            </button>
            <div class="ajax-loader pod-refresher buttons hidden"></div>-->
        </td>
        <td class="col-md-3">
            <button type="button" class="container-edit" data-app="<?php echo $pod->name?>" title="Edit"></button>
            <button type="button" class="container-delete" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="Delete"></button>
            <div class="ajax-loader pod buttons hidden"></div>
        </td>
    </tr>
<?php endforeach;?>