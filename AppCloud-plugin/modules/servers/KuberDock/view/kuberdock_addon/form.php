<form class="form-horizontal" method="post">
    <?php echo \base\CL_Csrf::render(); ?>
    <div class="new-kube<?php echo $kubes->kuber_kube_id ? ' hidden' : ''?>">
        <div class="form-group">
            <label for="server_id" class="col-md-4 control-label">Server</label>
            <div class="col-md-8">
                <select class="form-control" name="server_id" id="server_id" data-validation="required">
                <?php foreach($servers as $row):?>
                    <option value="<?php echo $row->id?>"<?php echo ($kube->server_id == $row->id || $row->active) ? ' selected' : ''?>>
                        <?php echo $row->name?>
                    </option>
                <?php endforeach;?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="kube_name" class="col-md-4 control-label">Kube type name</label>
            <div class="col-md-8">
                <input type="text" class="form-control" name="kube_name" id="kube_name" value="<?php echo $kube->kube_name?>" data-validation="required">
            </div>
        </div>

        <div class="form-group">
            <label for="cpu_limit" class="col-md-4 control-label">CPU limit (<?php echo \components\KuberDock_Units::getCPUUnits()?>)</label>
            <div class="col-md-8">
                <input type="text" class="form-control" name="cpu_limit" id="cpu_limit" value="<?php echo $kube->cpu_limit?>" data-validation="required">
            </div>
        </div>

        <div class="form-group">
            <label for="memory_limit" class="col-md-4 control-label">Memory limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</label>
            <div class="col-md-8">
                <input type="text" class="form-control" name="memory_limit" id="memory_limit" value="<?php echo $kube->memory_limit?>" data-validation="required">
            </div>
        </div>

        <div class="form-group">
            <label for="hdd_limit" class="col-md-4 control-label">HDD limit (<?php echo \components\KuberDock_Units::getHDDUnits()?>)</label>
            <div class="col-md-8">
                <input type="text" class="form-control" name="hdd_limit" id="hdd_limit" value="<?php echo $kube->hdd_limit?>" data-validation="required">
            </div>
        </div>

        <div class="form-group">
            <label for="traffic_limit" class="col-md-4 control-label">Traffic limit (<?php echo \components\KuberDock_Units::getTrafficUnits()?>)</label>
            <div class="col-md-8">
                <input type="text" class="form-control" name="traffic_limit" id="traffic_limit" value="<?php echo $kube->traffic_limit?>" data-validation="required">
            </div>
        </div>
    </div>

    <div class="row" style="margin-left: 0; margin-right: 0;">
        <div class="pull-left">
            <a href="?module=KuberDock"><button type="button" class="btn btn-default">Back</button></a>
        </div>

        <div class="pull-right">
            <button type="submit" class="btn btn-default">Add</button>
        </div>
    </div>
    <div class="clearfix"></div>
</form>

<script>
    $$.validate();
</script>