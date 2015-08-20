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
                <td><a href="javascript:void(0)" class="show-container-details"><?php echo $pod->name?></a></td>
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

            <tr class="container-details">
                <td colspan="4">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-4">
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

                            <div class="col-sm-6">
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

                            <div class="col-sm-2">
                                <b><?php echo $pod->totalPrice?></b>
                            </div>
                        </div>

                        <div class="row">
                            &nbsp;
                        </div>

                        <div class="row">
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
                    </div>

                </td>
            </tr>
        <?php endforeach;?>
    </table>
</div>