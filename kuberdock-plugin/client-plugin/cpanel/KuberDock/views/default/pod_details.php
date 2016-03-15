<?php
use Kuberdock\classes\components\Units;
?>

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
        <div class="splitter">
            <table class="table apps-list app-table">
                <thead>
                <tr>
                    <th class="col-md-3">Limits</th>
                    <th class="col-md-3">Public IP</th>
                    <th class="col-md-2">Status</th>
                    <th class="col-md-4">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        CPU: <?php echo $pod->kubeType['cpu_limit'] * $pod->kubeCount.' '.$pod->units['cpu'];;?><br>
                        Local storage: <?php echo $pod->kubeType['hdd_limit'] * $pod->kubeCount.' '.$pod->units['hdd']?><br>
                        Memory: <?php echo $pod->kubeType['memory_limit'] * $pod->kubeCount.' '.$pod->units['memory']?><br>
                        Traffic: <?php echo $pod->kubeType['traffic_limit'] * $pod->kubeCount.' '.$pod->units['traffic']?><br>
                        Kube Type: <?php echo $pod->kubeType['kube_name']?> <br>
                        Number of Kubes: <?php echo $pod->kubeCount ?>

                        <div class="top-offset">
                            <a href="?a=upgradePod&podName=<?php echo $pod->name?>">
                                <button title="Upgrade" class="btn btn-primary btn-xs" type="button">
                                    <span aria-hidden="true" class="glyphicon glyphicon-refresh"></span>
                                    <span>Upgrade</span>
                                </button>
                            </a>
                        </div>
                    </td>
                    <td>
                        <?php echo (isset($pod->public_ip) && $pod->public_ip ? $pod->public_ip :
                            (isset($pod->labels['kuberdock-public-ip']) ? $pod->labels['kuberdock-public-ip'] : 'none'))?>
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
        </div>
        <div>
            <label class="title">Ports</label>
            <table class="table apps-list app-table">
                <thead>
                <tr>
                    <th class="col-md-3">Container</th>
                    <th class="col-md-3">Protocol</th>
                    <th class="col-md-3">Host
                                <span class="glyphicon glyphicon-info-sign" aria-hidden="true" data-toggle="tooltip" data-placement="right"
                                      title="Host port is external port of a container used to access container port from using public_ip:host_port">
                                </span>
                    </th>
                    <th class="col-md-3">Public</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($pod->containers as $row):?>
                    <?php foreach($row['ports'] as $port):
                        if(!isset($port['hostPort']) && isset($port['containerPort']))
                            $port['hostPort'] = $port['containerPort'];
                        if(!isset($port['isPublic']))
                            $port['isPublic'] = false;
                        if(!isset($ports['protocol']))
                            $port['protocol'] = 'tcp';
                        ?>
                        <tr>
                            <td><small><?php echo $port['containerPort']?></small></td>
                            <td><small><?php echo $port['protocol']?></small></td>
                            <td><small><?php echo $port['hostPort']?></small></td>
                            <td><input type="checkbox" disabled<?php echo $port['isPublic'] ? ' checked' : ''?>></td>
                        </tr>
                    <?php endforeach;?>
                <?php endforeach;?>
                </tbody>
            </table>
        </div>

        <?php if($pod->volumes):?>
            <label class="title">Volumes</label>
            <table class="table apps-list app-table">
                <thead>
                <tr>
                    <th class="col-md-3">Container path</th>
                    <th class="col-md-3">Persistent</th>
                    <th class="col-md-3">Name</th>
                    <th class="col-md-3">Size (<?php echo Units::getHDDUnits()?>)</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($pod->containers as $row):?>
                    <?php foreach($row['volumeMounts'] as $volume):
                        $pd = $pod->getPersistentStorageByName($volume['name']);
                    ?>
                        <tr>
                            <td><?php echo $volume['mountPath']?></td>
                            <td><input type="checkbox" disabled<?php echo isset($pd['persistentDisk']) ? ' checked' : ''?>></td>
                            <td><?php echo isset($pd['persistentDisk']) ? $pd['persistentDisk']['pdName'] : '-'?></td>
                            <td><?php echo isset($pd['persistentDisk']) ? $pd['persistentDisk']['pdSize'] : '-'?></td>
                        </tr>
                    <?php endforeach;?>
                <?php endforeach;?>
                </tbody>
            </table>
        <?php endif;?>

        <div class="splitter last">
            <label class="title">Environment variables</label>
            <table class="table apps-list app-table">
                <thead>
                <tr>
                    <th class="col-md-6">Name</th>
                    <th class="col-md-6">Value</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($pod->containers as $row):?>
                    <?php foreach($row['env'] as $env):?>
                        <tr>
                            <td><small><?php echo $env['name']?></small></td>
                            <td><small><?php echo htmlspecialchars($env['value'], ENT_QUOTES);?></small></td>
                        </tr>
                    <?php endforeach;?>
                <?php endforeach;?>
                </tbody>
            </table>
            <div class="total-price-wrapper">
                <p class="total-price"><?php echo $pod->totalPrice?></p>
            </div>
        </div>

        <a class="pull-left btn btn-default" href="kuberdock.live.php">Back</a>
        <a class="pull-right btn btn-primary" href="kuberdock.live.php?a=search">Add more apps</a>
        <div class="clearfix"></div>
    </div>
</div>