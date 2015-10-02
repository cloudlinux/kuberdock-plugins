<div class="container-fluid">
    <div class="row">
        <h3>KuberDock kube relations</h3>

        <p class="text-right">
            <a href="<?php echo CL_Base::model()->baseUrl?>&a=add">
                <button type="button" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add kube type</button>
            </a>
        </p>

        <table class="table table-bordered">
            <tr class="active">
                <th>Package name</th>
                <th>Kube type name</th>
                <th>CPU limit (<?php echo KuberDock_Units::getCPUUnits()?>)</th>
                <th>Memory limit (<?php echo KuberDock_Units::getMemoryUnits()?>)</th>
                <th>HDD limit (<?php echo KuberDock_Units::getHDDUnits()?>)</th>
                <th>Traffic limit (<?php echo KuberDock_Units::getTrafficUnits()?>)</th>
                <th>Price</th>
                <th>Price type</th>
            </tr>

            <?php foreach($brokenPackages as $row):
                $product = KuberDock_Product::model()->loadByParams($row);
            ?>
            <tr class="danger">
                <td><a href="configproducts.php?action=edit&id=<?php echo $row['id']?>" target="_blank"><?php echo $row['name']?></a></td>
                <td colspan="6">
                    <span class="glyphicon  glyphicon-exclamation-sign" aria-hidden="true"></span>
                    Package not added to KuberDock. Please edit <a href="configproducts.php?action=edit&id=<?php echo $row['id']?>">product</a>
                </td>
                <td>per <?php echo $product->getReadablePaymentType()?></td>
            </tr>
            <?php endforeach;?>

            <?php foreach($productKubes as $kube):
                $product = KuberDock_Product::model()->loadByParams($products[$kube['product_id']]);
            ?>
            <tr>
                <td><?php echo $product->name?></td>
                <td><?php echo $kube['kube_name']?></td>
                <td><?php echo $kube['cpu_limit']?></td>
                <td><?php echo $kube['memory_limit']?></td>
                <td><?php echo $kube['hdd_limit']?></td>
                <td><?php echo $kube['traffic_limit']?></td>
                <?php echo $kube['kube_price'] ? '<td>'.$kube['kube_price'].'</td>' : '<td class="danger text-center">Empty</td>'; ?>
                <td>per <?php echo $product->getReadablePaymentType()?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>