<div class="login-link">
    <input type="button" value="Login to KuberDock" class="btn" onclick="window.open('<?php echo $service->getLoginByTokenLink();?>', '_blank')">
</div>

<?php if($trialExpired):?>
<div class="alert alert-danger">
    Your trial expired <?php echo $trialExpired ?>. Please upgrade package.
</div>
<?php endif; ?>

<div class="center" style="margin-top: 10px"><h3>Kubes</h3></div>

<table class="client product-info">
    <tr>
        <th>Kube type name</th>
        <th>Price</th>
        <th>CPU limit (<?php echo \components\KuberDock_Units::getCPUUnits()?>)</th>
        <th>Memory limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</th>
        <th>Disk Usage limit (<?php echo \components\KuberDock_Units::getHDDUnits()?>)</th>
        <th>Traffic limit (<?php echo \components\KuberDock_Units::getTrafficUnits()?>)</th>
    </tr>

    <?php foreach($kubes as $kube): ?>
        <tr<?php echo !$kube['available'] ? ' class="unavailable"' : ''?>>
            <td><?php echo $kube['name'] ?></td>
            <td><?php echo $currency->getFullPrice($kube['kube_price']) . ' / ' . $product->getReadablePaymentType()?></td>
            <td><?php echo $kube['cpu'] ?></td>
            <td><?php echo $kube['memory'] ?></td>
            <td><?php echo $kube['disk_space'] ?></td>
            <td><?php echo $kube['included_traffic'] ?></td>
        </tr>
    <?php endforeach;?>
</table>