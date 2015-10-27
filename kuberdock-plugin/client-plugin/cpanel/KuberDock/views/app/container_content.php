<?php foreach($pods as $pod): if($pod['status'] == 'stopped') {
        $statusClass = 'container-start';
        $statusText = 'Start';
    } else {
        $statusClass = 'container-stop';
        $statusText = 'Stop';
    }
    ?>
    <tr>
        <td><a href="?a=podDetails&podName=<?php echo $pod['name']?>&templateId=<?php echo $pod['template_id']?>"><?php echo $pod['name']?></a></td>
        <td>
            <?php echo 'Public IP: ' . (isset($pod['public_ip']) && $pod['public_ip'] ? $pod['public_ip'] :
                (isset($pod['labels']['kuberdock-public-ip']) ? $pod['labels']['kuberdock-public-ip'] : 'none'))?>
        </td>
        <td>
            <button type="button" class="<?php echo $statusClass?>" data-target=".confirm-modal" data-app="<?php echo $pod['name']?>" title="<?php echo $statusText?>"></button>
        </td>
        <td>
            <button type="button" class="container-edit" data-app="<?php echo $pod['name']?>" title="Edit"></button>
            <button type="button" class="container-delete" data-target=".confirm-modal" data-app="<?php echo $pod['name']?>" title="Delete"></button>
            <div class="ajax-loader pod buttons hidden"></div>
        </td>
    </tr>
<?php endforeach;?>