<div class="row pod-details">
    <div class="col-md-12">
    <?php
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
        <div class="splitter last">
            <table class="table apps-list app-table">
                <thead>
                <tr>
                    <th class="col-md-3 podname"><?php echo $pod->name?> </th>
                    <th class="col-md-3">Public IP</th>
                    <th class="col-md-2">Status</th>
                    <th class="col-md-4">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        CPU: <?php echo $pod->kubeType['cpu_limit'] * $pod->kubeCount.' '.$pod->units['cpu'];?><br>
                        Local storage: <?php echo $pod->kubeType['hdd_limit'] * $pod->kubeCount.' '.$pod->units['hdd']?><br>
                        Memory: <?php echo $pod->kubeType['memory_limit'] * $pod->kubeCount.' '.$pod->units['memory']?><br>
                        Traffic: <?php echo $pod->kubeType['traffic_limit'] * $pod->kubeCount.' '.$pod->units['traffic']?><br>
                        Kube Type: <?php echo $pod->kubeType['kube_name']?> <br>
                        Number of Kubes: <?php echo $pod->kubeCount ?>
                    <?php if($domains):?>
                        <br>Domains:
                        <?php foreach($domains as $domain):?>
                            <a href="http://<?php echo $domain?>" target="_blank"><?php echo $domain?></a><br>
                        <?php endforeach;?>
                    <?php endif;?>
                    </td>
                    <td>
                        <?php if($ip = $pod->getPublicIp()):
                            $ports = $pod->getPorts();
                            echo $ip;
                            echo $ports ? '<br>Available ports: ' . implode(', ', $ports) : ''
                            ?>
                        <?php else: ?>
                            none
                        <?php endif;?>
                    </td>
                    <td><?php echo ucfirst($pod->status) ?></td>
                    <td>
                        <button type="button" class="btn btn-<?php echo $buttonClass ?> btn-xs <?php echo $statusClass?>" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="<?php echo $statusText?>">
                            <span class="glyphicon glyphicon-<?php echo $iconClass ?>" aria-hidden="true"></span>
                            <span><?php echo $statusText?></span>
                        </button>

                        <button type="button" class="btn btn-primary btn-xs container-edit" data-app="<?php echo $pod->name?>" title="Edit">
                            <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                            <span>Edit</span>
                        </button>

                        <button type="button" class="btn btn-primary btn-xs pod-restart" data-target=".restart-modal" data-app="<?php echo $pod->name?>" title="Restart">
                            <span class="glyphicon glyphicon-eject" aria-hidden="true"></span>
                            <span>Restart</span>
                        </button>

                        <button type="button" class="btn btn-danger btn-xs container-delete" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="Delete">
                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                            <span>Delete</span>
                        </button>
                        <div class="ajax-loader pod buttons hidden"></div>
                    </td>
                </tr>
                </tbody>
            </table>

            <div class="total-price-wrapper">
                <p class="total-price"><?php echo $pod->totalPrice?></p>
            </div>
        </div>

        <?php if($podsCount > 1):?>
            <a class="pull-left btn btn-default" href="kuberdock.live.php?c=app&a=installPredefined&template=<?php echo $app->getTemplateId()?>">
                Back to "<?php echo $app->template->getName()?>" apps
            </a>
        <?php else:?>
            <a class="pull-left btn btn-default" href="kuberdock.live.php">Back to main page</a>
        <?php endif;?>
        <a class="pull-right btn btn-primary" href="kuberdock.live.php?c=app&a=installPredefined&new=1&template=<?php echo $app->getTemplateId()?>">
            Add more "<?php echo $app->template->getName()?>" apps
        </a>
        <div class="clearfix"></div>
    </div>
</div>