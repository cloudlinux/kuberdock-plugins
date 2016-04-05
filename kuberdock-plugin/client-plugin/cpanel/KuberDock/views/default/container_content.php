<?php foreach($pods as $pod):
    if($pod->status == 'stopped') {
        $statusClass = 'container-start';
        $statusText = 'Start';
        $buttonClass = 'success';
        $iconClass = 'play';
    } elseif($pod->status == 'unpaid') {
        $statusClass = 'container-pay';
        $statusText = 'Pay and Start';
        $buttonClass = 'success';
        $iconClass = 'play';
    } else {
        $statusClass = 'container-stop';
        $statusText = 'Stop';
        $buttonClass = 'danger';
        $iconClass = 'stop';
    }
    ?>
    <tr>
        <td class="col-md-2">
        <?php if($pod->template_id):?>
            <a href="?c=app&a=installPredefined&podName=<?php echo $pod->name?>&template=<?php echo $pod->template_id?>"><?php echo $pod->name?></a>
        <?php else:?>
            <a href="?a=podDetails&podName=<?php echo $pod->name?>"><?php echo $pod->name?></a>
        <?php endif;?>
        </td>
        <td class="col-md-2">
            <?php echo isset($pod->public_ip) && $pod->public_ip ? $pod->public_ip :
                (isset($pod->labels['kuberdock-public-ip']) ? $pod->labels['kuberdock-public-ip'] : 'none')?>
        </td>
        <td class="col-md-2"><?php echo $pod->podIP ?></td>
        <td class="col-md-2"><?php echo ucfirst($pod->status) ?></td>
        <td class="col-md-4">
            <button type="button" class="btn btn-<?php echo $buttonClass ?> btn-xs <?php echo $statusClass?>" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="<?php echo $statusText?>">
                <span class="glyphicon glyphicon-<?php echo $iconClass?>" aria-hidden="true"></span>
                <span><?php echo $statusText?></span>
            </button>
            <button type="button" class="btn btn-primary btn-xs container-edit" data-app="<?php echo $pod->name?>" title="Edit">
                <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                <span>Edit</span>
            </button>
            <button type="button" class="btn btn-danger btn-xs container-delete" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="Delete">
                <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                <span>Delete</span>
            </button>
            <div class="ajax-loader pod buttons hidden"></div>
        </td>
    </tr>
<?php endforeach;?>