<link rel="stylesheet" type="text/css" href="assets/script/slider/jquery.nouislider.min.css">
<script src="assets/script/slider/jquery.nouislider.all.min.js"></script>
<script src="assets/script/install.js"></script>

<div class="container-fluid content" id="app-page">
    <div class="container-fluid content">
        <div class="row">
            <p>
                You are about to launch new dedicated application. Please, select amount of resources you want to make
                available for that application. After your application will be launched it will be available during
                one minute.
            </p>

            <div class="page-header">
                <h2>Setup "<?php echo $image?>"</h2>
            </div>
        </div>

        <div class="message"><?php echo $this->controller->error?></div>

        <div class="row">
            <form class="form-horizontal container-install" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
                <input type="hidden" name="image" value="<?php echo $image?>">
                <input type="hidden" name="kube_count" id="kube_count" value="1">
                <input type="hidden" name="product_id" id="product_id" value="0">

                <div class="form-group">
                    <label for="kuber_kube_id" class="col-sm-3 control-label">Type</label>

                    <div class="col-sm-4">
                        <select class="form-control" name="kuber_kube_id" id="kuber_kube_id">
                        <?php foreach($pod->getPanel()->billing->getProducts() as $product):?>
                            <?php foreach($product['kubes'] as $kube):?>
                            <option value="<?php echo $kube['kuber_kube_id']?>" data-pid="<?php echo $product['id']?>">
                                <?php echo $kube['kube_name'] . ' ('.$product['name'].')'?>
                            </option>
                            <?php endforeach;?>
                        <?php endforeach;?>
                        </select>
                    </div>

                    <div class="col-sm-4">
                        <div class="product-description"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="kube_count" class="col-sm-3 control-label">Size</label>

                    <div class="col-sm-4">
                        <div class="kube-slider"></div>
                        <span class="kube-slider-value"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ports" class="col-sm-3 control-label">Ports</label>

                    <div class="col-sm-8">
                        <p style="padding-top: 7px;">
                            <button type="button" class="btn btn-default btn-xs" id="add_port">
                                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add port
                            </button>
                        </p>

                        <table class="table table-stripped table-condensed<?php echo !isset($pod->containers[0]['ports']) ? ' hidden' : ''?>" id="port_table">
                            <tr>
                                <th><small>Container port</small></th>
                                <th><small>Protocol</small></th>
                                <th><small>
                                    Host port
                                    <span class="glyphicon glyphicon-info-sign" aria-hidden="true" data-toggle="tooltip" data-placement="right"
                                        title="Host port is external port of a container used to access container port from using public_ip:host_port">
                                    </span>
                                </small></th>
                                <th><small>
                                        Public
                                    <span class="glyphicon glyphicon-info-sign" aria-hidden="true" data-toggle="tooltip" data-placement="right"
                                          title="Provides application public IP for marked ports">
                                    </span>
                                </small></th>
                                <th></th>
                            </tr>

                        <?php if(isset($pod->containers[0]['ports'])):?>
                        <?php foreach($pod->containers[0]['ports'] as $k => $port): ?>
                            <tr>
                                <td><input type="text" name="Ports[<?php echo $k?>][containerPort]" placeholder="Empty" value="<?php echo $port['containerPort']?>"></td>
                                <td>
                                    <select name="Ports[<?php echo $k?>][protocol]">
                                    <?php foreach(array('tcp', 'udp') as $p):?>
                                        <option value="<?php echo $p?>"<?php echo $p == $port['protocol'] ? ' selected' : ''?>><?php echo $p?></option>
                                    <?php endforeach;?>
                                    </select>
                                </td>
                                <td><input type="text" name="Ports[<?php echo $k?>][hostPort]" placeholder="Empty" value="<?php echo $port['hostPort']?>"></td>
                                <td class="text-center">
                                    <input type="checkbox" name="Ports[<?php echo $k?>][isPublic]" class="is-public" value="1"<?php echo $port['isPublic'] ? ' checked' : ''?>>
                                </td>
                                <td><button type="button" class="btn btn-default btn-sm delete-port old-button">
                                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                </button></td>
                            </tr>
                        <?php endforeach;?>
                        <?php endif;?>
                        </table>
                    </div>
                </div>

                <div class="form-group">
                    <label for="env" class="col-sm-3 control-label">Environment variables</label>

                    <div class="col-sm-8">
                        <p style="padding-top: 7px;">
                            <button type="button" class="btn btn-default btn-xs" id="add_env">
                                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add variable
                            </button>

                            <a href="<?php echo $pod->getImageUrl($image)?>" target="_blank" class="env-more-link">Learn more about this application</a>
                        </p>

                        <table class="table table-stripped table-condensed<?php echo !isset($pod->containers[0]['env']) ? ' hidden' : ''?>" id="env_table">
                            <tr>
                                <th><small>Name</small></th>
                                <th><small>Value</small></th>
                                <th></th>
                            </tr>

                            <?php if(isset($pod->containers[0]['env'])):?>
                                <?php foreach($pod->containers[0]['env'] as $k => $env): ?>
                                    <tr>
                                        <td><input type="text" name="Env[<?php echo $k?>][name]" placeholder="Empty" value="<?php echo $env['name']?>"></td>
                                        <td><input type="text" name="Env[<?php echo $k?>][value]" placeholder="Empty" value="<?php echo $env['value']?>"></td>
                                        <td>
                                            <button type="button" class="btn btn-default btn-sm delete-env">
                                                <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach;?>
                            <?php endif;?>
                        </table>
                    </div>
                </div>

                <div class="form-group">
                    <label for="env" class="col-sm-3 control-label">Volumes</label>

                    <div class="col-sm-8">
                        <p style="padding-top: 7px;">
                            <button type="button" class="btn btn-default btn-xs" id="add_volume">
                                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add volume
                            </button>
                        </p>

                        <table class="table table-stripped table-condensed<?php echo !isset($pod->containers[0]['volumeMounts']) ? ' hidden' : ''?>" id="volume_table">
                            <tr>
                                <th><small>Container path</small></th>
                                <th><small>Persistent</small></th>
                                <th><small>Disc name</small></th>
                                <th><small>Disc size</small></th>
                                <th></th>
                                <th></th>
                            </tr>

                            <?php if(isset($pod->containers[0]['volumeMounts'])):?>
                                <?php foreach($pod->containers[0]['volumeMounts'] as $k => $volume): ?>
                                    <tr>
                                        <td><input type="text" class="middle" name="Volume[<?php echo $k?>][mountPath]" placeholder="Empty" value="<?php echo $volume['mountPath']?>"></td>
                                        <td class="text-center">
                                            <input type="checkbox" name="Volume[<?php echo $k?>][persistent]" class="set-persistent" value="1">
                                        </td>
                                        <td>
                                            <input type="text" name="Volume[<?php echo $k?>][name]" class="short volume-name" autocomplete="off" placeholder="Empty" disabled>
                                        </td>
                                        <td>
                                            <input type="text" name="Volume[<?php echo $k?>][size]" class="short volume-size" placeholder="Empty" disabled>
                                        </td>
                                        <td class="text-center"><small><?php echo Units::getHDDUnits()?></small></td>
                                        <td>
                                            <button type="button" class="btn btn-default btn-sm delete-volume old-button">
                                                <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach;?>
                            <?php endif;?>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <strong>Total: </strong><span id="priceBlock"></span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12 text-center">
                        <button type="submit" class="btn btn-primary start-button">
                            Start your App
                        </button>
                        <div class="ajax-loader buttons hidden"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var kubes = <?php echo json_encode($pod->getPanel()->billing->getKubes())?>,
        maxKubes = <?php echo $maxKubes?>;
        units = <?php echo json_encode($pod->units)?>;
</script>