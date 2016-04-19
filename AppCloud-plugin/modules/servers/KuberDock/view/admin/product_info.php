<table class="datatable product-info">
    <tr>
        <th>Kube name</th>
        <th>Price</th>
        <th>CPU limit (<?php echo \components\KuberDock_Units::getCPUUnits()?>)</th>
        <th>Memory limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</th>
        <th>Disk Usage limit (<?php echo \components\KuberDock_Units::getHDDUnits()?>)</th>
        <th>Traffic limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</th>
    </tr>

    <?php foreach($kubes as $kube):?>
    <tr>
        <td><?php echo $kube['kube_name'] ?></td>
        <td><?php echo $currency->getFullPrice($kube['kube_price']) . ' / ' . $product->getReadablePaymentType()?></td>
        <td><?php echo $kube['cpu_limit'] ?></td>
        <td><?php echo $kube['memory_limit'] ?></td>
        <td><?php echo $kube['hdd_limit'] ?></td>
        <td><?php echo $kube['traffic_limit'] ?></td>
    </tr>
    <?php endforeach;?>
</table>