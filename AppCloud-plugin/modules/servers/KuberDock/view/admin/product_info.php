<table class="datatable product-info">
    <tr>
        <th>Kube name</th>
        <th>Price</th>
        <th>CPU limit (<?php echo \components\KuberDock_Units::getCPUUnits()?>)</th>
        <th>Memory limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</th>
        <th>Disk Usage limit (<?php echo \components\KuberDock_Units::getHDDUnits()?>)</th>
        <?php /* AC-3783
        <th>Traffic limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</th>
        */ ?>
    </tr>

    <?php foreach($kubes as $kube):?>
    <tr<?php echo !$kube['available'] ? ' class="unavailable"' : ''?>>
        <td><?php echo $kube['name'] ?></td>
        <td><?php echo $currency->getFullPrice($kube['kube_price']) . ' / ' . $product->getReadablePaymentType()?></td>
        <td><?php echo $kube['cpu'] ?></td>
        <td><?php echo $kube['memory'] ?></td>
        <td><?php echo $kube['disk_space'] ?></td>
        <?php /* AC-3783
        <td><?php echo $kube['included_traffic'] ?></td>
        */ ?>
    </tr>
    <?php endforeach;?>
</table>