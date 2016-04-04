<?php
/**
 * @var $kubes \KuberDock_Addon_Kube[]
 * @var array $brokenPackages
 */
?>

<div class="container-fluid">
    <div class="row offset-top">
        <p class="text-right">
            <?php if ($updateDb): ?>
                <button type="button" class="btn btn-default btn-lg migration">
                    Update database
                </button>
            <?php endif;?>

            <a href="<?php echo \base\CL_Base::model()->baseUrl?>&a=add">
                <button type="button" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add kube type</button>
            </a>
        </p>
    </div>

    <div class="row">
        <table id="kubes_table" class="tablesorter table table-bordered">
        <thead>
        <tr class="active sorted">
            <th class="col-md-3">Kube type (id)</th>
            <th class="col-md-2">CPU limit (<?php echo \components\KuberDock_Units::getCPUUnits()?>)</th>
            <th class="col-md-3">Memory limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</th>
            <th class="col-md-2">HDD limit (<?php echo \components\KuberDock_Units::getHDDUnits()?>)</th>
            <th class="col-md-1">Traffic limit (<?php echo \components\KuberDock_Units::getTrafficUnits()?>)</th>
        </tr>
        </thead>
            <?php foreach($kubes as $kube): ?>
                <?php $this->renderPartial('kube', array('kube' => $kube))?>
            <?php endforeach; ?>
        </table>
    </div>

    <?php $this->renderPartial('broken', array('brokenPackages' => $brokenPackages))?>

</div>

<?php if ($updateDb): ?>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endif;?>

