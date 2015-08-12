<div class="container-fluid">
    <div class="row">
        <h3>KuberDock kube types</h3>

        <p class="text-right">
            <a href="<?php echo CL_Base::model()->baseUrl?>&a=add">
                <button type="button" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add kube type</button>
            </a>
        </p>

        <table class="table table-bordered">
            <tr class="active">
                <th>Kube type name</th>
                <th>CPU limit (<?php echo KuberDock_Units::getCPUUnits()?>)</th>
                <th>Memory limit (<?php echo KuberDock_Units::getMemoryUnits()?>)</th>
                <th>HDD limit (<?php echo KuberDock_Units::getHDDUnits()?>)</th>
                <th></th>
            </tr>

            <?php foreach($kubes as $kube):
                $productKubes = CL_Tools::getKeyAsField($productKubes, 'kuber_kube_id');
            ?>
            <tr>
                <td><?php echo $kube['kube_name']?></td>
                <td><?php echo $kube['cpu_limit']?></td>
                <td><?php echo $kube['memory_limit']?></td>
                <td><?php echo $kube['hdd_limit']?></td>
                <td class="text-center">
                    <?php if(!isset($productKubes[$kube['kuber_kube_id']])):?>
                        <a href="<?php echo CL_Base::model()->baseUrl?>&a=delete&id=<?php echo $kube['id']?>">
                            <button type="button" class="btn btn-default btn-xs">
                                <span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Remove
                            </button>
                        </a>
                    <?php endif;?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>