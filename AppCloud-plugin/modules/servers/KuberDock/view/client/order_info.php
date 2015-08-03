<?php if($product->getConfigOption('enableTrial')): ?>
Free Trial: <strong><?php echo $product->getConfigOption('trialTime')?> days</strong>
<?php else:?>
Price for IP: <strong><?php echo $currency->getFullPrice($product->getConfigOption('priceIP'))?> / hour</strong><br/>
Price for Persistent Storage: <strong><?php echo $product->getReadablePersistentStorage()?> &nbsp;</strong><br/>
Price for Additional Traffic: <strong><?php echo $product->getReadableOverTraffic()?> &nbsp;</strong><br/>
<?php endif;?>

<?php foreach($kubes as $kube):?>
Kube <?php echo $kube['kube_name'];?>: <strong><?php echo $currency->getFullPrice($kube['kube_price']). ' / '.$product->getReadablePaymentType();?></strong><br/><em>CPU <?php echo $kube['cpu_limit'].' '.KuberDock_Units::getCPUUnits();?>, Memory <?php echo $kube['memory_limit'].' '.KuberDock_Units::getMemoryUnits();?>, <br/>Disk Usage <?php echo $kube['hdd_limit'].' '.KuberDock_Units::getHDDUnits();?>, Traffic <?php echo $kube['traffic_limit'].' '.KuberDock_Units::getTrafficUnits();?></em>
<?php endforeach;?>