<div class="row">
    <div class="page-header">
        <h2>Application "<?php echo $pod->name?>"</h2>
    </div>

    <?php if($postDescription):?>
        <div class="alert alert-dismissible alert-success"  role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <strong><?php echo $postDescription?></strong>
        </div>
    <?php endif;?>
</div>

<div class="row">
    <?php
        if($pod->status == 'stopped') {
            $statusClass = 'container-start';
            $statusText = 'Start';
        } else {
            $statusClass = 'container-stop';
            $statusText = 'Stop';
        }
    ?>
    <div class="col-md-3">
        <a href="javascript:void(0)"><?php echo $pod->name?></a>
    </div>

    <div class="col-md-4">
        <?php echo 'Public IP: ' . (isset($pod->public_ip) && $pod->public_ip ? $pod->public_ip :
            (isset($pod->labels['kuberdock-public-ip']) ? $pod->labels['kuberdock-public-ip'] : 'none'))?>
    </div>

    <div class="col-md-1">
        <button type="button" class="btn btn-default btn-xs <?php echo $statusClass?>" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="<?php echo $statusText?>">
            <span class="glyphicon glyphicon-<?php echo $statusText == 'Stop' ? 'stop' : 'play' ?>" aria-hidden="true"></span> <?php echo $statusText?>
        </button>
    </div>

    <div class="col-md-2 text-right">
        <button type="button" class="btn btn-default btn-xs container-edit" data-app="<?php echo $pod->name?>" title="Edit">
            <span class="glyphicon glyphicon-edit" aria-hidden="true"></span> Edit
        </button>

        <button type="button" class="btn btn-default btn-xs container-delete" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="Delete">
            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Destroy
        </button>

        <div class="ajax-loader pod buttons hidden"></div>
    </div>
</div>

<div class="row top-offset">
    <div class="col-md-3">
        <p class="kube-name">
            <strong><?php echo $pod->kubeType['kube_name']?></strong>
        </p>

        <div class="kube-count">
            <?php
            $i = 0;
            while($i < $pod->kubeCount) {
                if($i % 2 == 0) {
                    echo '<div>';
                }
                echo '<span aria-hidden="true" class="glyphicon glyphicon-stop"></span>';
                if($i % 2 != 0 || ($i+1) == $pod->kubeCount) {
                    echo '</div>';
                }
                $i++;
            }
            ?>
        </div>

        <div>
            CPU: <?php echo $pod->kubeType['cpu_limit'] * $pod->kubeCount.' '.$pod->units['cpu']?><br>
            Local storage: <?php echo $pod->kubeType['hdd_limit'] * $pod->kubeCount.' '.$pod->units['hdd']?><br>
            Memory: <?php echo $pod->kubeType['memory_limit'] * $pod->kubeCount.' '.$pod->units['memory']?><br>
            Traffic: <?php echo $pod->kubeType['traffic_limit'] * $pod->kubeCount.' '.$pod->units['traffic']?><br>
        </div>
    </div>

    <div class="col-md-4">
        <strong>Ports</strong>
        <table class="table table-bordered">
            <tr>
                <th><small>container</small></th>
                <th><small>protocol</small></th>
                <th>
                    <small>host</small>
                    <span class="glyphicon glyphicon-info-sign" aria-hidden="true" data-toggle="tooltip" data-placement="right"
                          title="Host port is external port of a container used to access container port from using public_ip:host_port">
                    </span>
                </th>
                <th><small>public</small></th>
            </tr>

            <?php foreach($pod->containers as $row):?>
                <?php foreach($row['ports'] as $port):
                    if(!isset($port['hostPort']) && isset($port['containerPort']))
                        $port['hostPort'] = $port['containerPort'];
                    if(!isset($ports['isPublic']))
                        $port['isPublic'] = false;
                    if(!isset($ports['protocol']))
                        $port['protocol'] = 'tcp';
                    ?>
                    <tr>
                        <td><small><?php echo $port['containerPort']?></small></td>
                        <td><small><?php echo $port['protocol']?></small></td>
                        <td><small><?php echo $port['hostPort']?></small></td>
                        <td class="text-center"><input type="checkbox" disabled<?php echo $port['isPublic'] ? ' checked' : ''?>></td>
                    </tr>
                <?php endforeach;?>
            <?php endforeach;?>
        </table>
    </div>

    <div class="col-md-3 text-right">
        <p><b><?php echo $pod->totalPrice?></b></p>
    </div>
</div>

<div class="row top-offset">
    <div class="col-sm-10">
        <strong>Volumes</strong>
        <table class="table table-bordered">
            <tr>
                <th><small>container path</small></th>
                <th><small>persistent</small></th>
                <th><small>name</small></th>
                <th><small>size (<?php echo $pod->units['hdd']?>)</small></th>
            </tr>

            <?php foreach($pod->containers as $row):?>
                <?php foreach($row['volumeMounts'] as $volume):
                    $pd = $pod->getPersistentStorageByName($volume['name']);
                    ?>
                    <tr>
                        <td><small><?php echo $volume['mountPath']?></small></td>
                        <td class="text-center"><input type="checkbox" disabled<?php echo isset($volume['readOnly']) && !$volume['readOnly'] ? ' checked' : ''?>></td>
                        <td><small><?php echo isset($pd['persistentDisk']) ? $pd['persistentDisk']['pdName'] : '-'?></small></td>
                        <td><small><?php echo isset($pd['persistentDisk']) ? $pd['persistentDisk']['pdSize'] : '-'?></small></td>
                    </tr>
                <?php endforeach;?>
            <?php endforeach;?>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-sm-10">
        <strong>Environment variables</strong>
        <table class="table table-bordered">
            <tr>
                <th><small>name</small></th>
                <th><small>value</small></th>
            </tr>

            <?php foreach($pod->containers as $row):?>
                <?php foreach($row['env'] as $env):?>
                    <tr>
                        <td><small><?php echo $env['name']?></small></td>
                        <td><small><?php echo $env['value']?></small></td>
                    </tr>
                <?php endforeach;?>
            <?php endforeach;?>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <a href="?kuberdock.live.php">< < Back to apps</a>
    </div>

    <div class="col-md-5 text-right">
        <a href="?kuberdock.live.php?a=search"><button type="button" class="btn btn-primary">Add more apps</button></a>
    </div>
</div>

<div class="modal fade bs-example-modal-sm confirm-modal" tabindex="-1" role="dialog" aria-labelledby="Confirm">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                Some text
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-action" data-action="delete">Action</button>
            </div>
        </div>
    </div>
</div>