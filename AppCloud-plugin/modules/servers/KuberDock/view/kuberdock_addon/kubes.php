<div class="container-fluid">
    <div class="row offset-top">
        <p class="text-right">
            <a href="<?php echo \base\CL_Base::model()->baseUrl?>&a=add">
                <button type="button" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add kube type</button>
            </a>
        </p>
    </div>

    <div class="alert alert-danger alert-dismissible hidden" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <span class="message"></span>
    </div>

    <div class="row">
        <table id="kubes_table" class="tablesorter table table-bordered">
        <thead>
        <tr class="active sorted">
            <th class="col-md-3">Kube type (id)</th>
            <th class="col-md-3">CPU limit (<?php echo \components\KuberDock_Units::getCPUUnits()?>)</th>
            <th class="col-md-3">Memory limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</th>
            <th class="col-md-2">HDD limit (<?php echo \components\KuberDock_Units::getHDDUnits()?>)</th>
            <?php /* AC-3783
            <th class="col-md-1">Traffic limit (<?php echo \components\KuberDock_Units::getTrafficUnits()?>)</th>
            */ ?>
            <th class="col-md-1"></th>
        </tr>
        </thead>
            <?php foreach($kubes as $kube): ?>
                <?php $this->renderPartial('kube', array('kube' => $kube))?>
            <?php endforeach; ?>
        </table>
    </div>

    <?php $this->renderPartial('broken', array('brokenPackages' => $brokenPackages))?>

</div>