<div class="row container-content">
    <table class="table table-stripped">
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
                <td><a href="?a=podDetails&podName=<?php echo $pod->name?>"><?php echo $pod->name?></a></td>
                <td>
                    <?php echo 'Public IP: ' . isset($pod->public_ip) && $pod->public_ip ? $pod->public_ip :
                        (isset($pod->labels['kuberdock-public-ip']) ? $pod->labels['kuberdock-public-ip'] : 'none')?>
                </td>
                <td>
                    <button type="button" class="btn btn-default btn-xs <?php echo $statusClass?>" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="<?php echo $statusText?>">
                        <span class="glyphicon glyphicon-<?php echo $statusText == 'Stop' ? 'stop' : 'play' ?>" aria-hidden="true"></span> <?php echo $statusText?>
                    </button>

                    <!--<button type="button" class="btn btn-default btn-sm pod-refresh">
                        <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
                    </button>
                    <div class="ajax-loader pod-refresher buttons hidden"></div>-->
                </td>

                <td>
                    <button type="button" class="btn btn-default btn-xs container-edit" data-app="<?php echo $pod->name?>" title="Edit">
                        <span class="glyphicon glyphicon-edit" aria-hidden="true"></span> Edit
                    </button>

                    <button type="button" class="btn btn-default btn-xs container-delete" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="Delete">
                        <span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Destroy
                    </button>

                    <div class="ajax-loader pod buttons hidden"></div>
                </td>
            </tr>
        <?php endforeach;?>
    </table>
</div>