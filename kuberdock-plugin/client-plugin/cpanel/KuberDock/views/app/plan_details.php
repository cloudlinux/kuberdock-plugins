<?php
use Kuberdock\classes\components\Units;
?>

<div class="product-description hidden">
    <b>CPU:</b> <?php echo number_format($totalCPU, 2) . ' ' . Units::getCPUUnits(); ?><br>
    <b>Memory:</b> <?php echo $totalMemory . ' ' . Units::getMemoryUnits(); ?><br>
    <b>Storage:</b> <?php echo $totalHDD . ' ' . Units::getHDDUnits(); ?><br>
<?php if($totalPDSize):?>
    <b>Persistent Storage:</b> <?php echo $totalPDSize . ' ' . Units::getHDDUnits(); ?><br>
<?php endif;?>
<?php if($publicIp):?>
    <b>Public IP:</b> yes<br>
<?php endif;?>
</div>