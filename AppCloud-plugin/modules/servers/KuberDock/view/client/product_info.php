<div class="login-link">
    <input type="button" value="Login into KuberDock" class="btn" onclick="window.open('<?php echo $service->getLoginByTokenLink();?>', '_blank')">
</div>

<?php if($trialExpired):?>
<div class="alert alert-danger">
    Your trial expired <?php echo $trialExpired ?>. Please upgrade package.
</div>
<?php endif; ?>

<table class="client product-info">
    <tr>
        <th>Kube type name</th>
        <th>Price</th>
        <th>CPU limit (<?php echo KuberDock_Units::getCPUUnits()?>)</th>
        <th>Memory limit (<?php echo KuberDock_Units::getMemoryUnits()?>)</th>
        <th>Disk Usage limit (<?php echo KuberDock_Units::getHDDUnits()?>)</th>
        <th>Traffic limit (<?php echo KuberDock_Units::getTrafficUnits()?>)</th>
    </tr>

    <?php foreach($kubes as $kube):?>
        <tr>
            <td><?php echo $kube['kube_name'] ?></td>
            <td><?php echo $currency->getFullPrice($kube['kube_price']) . ' \ ' . $product->getReadablePaymentType()?></td>
            <td><?php echo $kube['cpu_limit'] ?></td>
            <td><?php echo $kube['memory_limit'] ?></td>
            <td><?php echo $kube['hdd_limit'] ?></td>
            <td><?php echo $kube['traffic_limit'] ?></td>
        </tr>
    <?php endforeach;?>
</table>