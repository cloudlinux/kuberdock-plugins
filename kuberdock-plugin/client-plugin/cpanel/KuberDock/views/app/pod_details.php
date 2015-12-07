<div id="app-page" class="container-fluid content">
    <div class="row">
        <div class="col-md-12 splitter">
            <h2>Application "<?php echo $app->getName()?>"</h2>
            <?php if($postDescription):?>
                <div class="alert alert-dismissible alert-success"  role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo $postDescription?>
                </div>
            <?php endif;?>
            <div class="message"><?php echo $this->controller->error?></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <?php if($pod->status == 'stopped') {
                    $statusClass = 'container-start';
                    $statusText = 'Start';
                } else {
                    $statusClass = 'container-stop';
                    $statusText = 'Stop';
                }

                $i = 0;
                while($i < $pod->kubeCount) {
                    $i++;
                }

                $ports = array();
                foreach($pod->containers as $r) {
                    if(!isset($r['ports'])) continue;

                    foreach($r['ports'] as $p) {
                        if(isset($p['hostPort'])) {
                            $ports[] = $p['hostPort'];
                        }
                    }
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
                                Kube type: <?php echo $pod->kubeType['kube_name']?> <br>
                                Kube quantity: <?php echo $pod->kubeCount ?>
                            </td>
                            <td>
                            <?php if($ip = $pod->getPublicIp()):?>
                                <?php echo $ip ?>
                                <?php echo $ports ? '<br>Available ports: ' . implode(', ', $ports) : ''?>
                            <?php else: ?>
                                none
                            <?php endif;?>
                            </td>
                            <td class="col-md-3"><?php echo ucfirst($pod->status) ?></td>
                            <td>
                                <button type="button" class="btn btn-<?php echo $statusText == 'Start' ? 'success' : 'danger' ?> btn-xs <?php echo $statusClass?>" data-target=".confirm-modal" data-app="<?php echo $pod->name?>" title="<?php echo $statusText?>">
                                    <span class="glyphicon glyphicon-<?php echo $statusText == 'Start' ? 'play' : 'stop' ?>" aria-hidden="true"></span>
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
                    </tbody>
                </table>

                <div class="total-price-wrapper">
                    <p class="total-price"><?php echo $pod->totalPrice?></p>
                </div>
            </div>

            <?php if($podsCount > 1):?>
                <a class="pull-left btn btn-default" href="kuberdock.live.php?c=app&a=installPredefined&template=<?php echo $app->getTemplateId()?>?>">
                    Back to "<?php echo $app->getName()?>" apps
                </a>
            <?php else:?>
                <a class="pull-left btn btn-default" href="kuberdock.live.php">Back to main page</a>
            <?php endif;?>
            <a class="pull-right btn btn-primary" href="kuberdock.live.php?c=app&a=installPredefined&new=1&template=<?php echo $app->getTemplateId()?>">
                Add more "<?php echo $app->getName()?>" apps
            </a>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="modal fade bs-example-modal-sm confirm-modal" tabindex="-1" role="dialog" aria-labelledby="Confirm">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">Some text</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-action" data-action="delete">Action</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($start && $pod->status == 'stopped'):?>
<script>
    $(document).ready(function() {
        $('.container-start').trigger('click');
    });
</script>
<?php endif;?>
