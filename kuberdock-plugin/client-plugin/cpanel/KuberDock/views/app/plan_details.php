<div class="product-description hidden">
    <b>Reserved memory:</b> <?php echo $totalKubes * $kube['memory_limit'] . ' ' . Units::getMemoryUnits(); ?><br>
    <b>Reserved storage:</b> <?php echo $totalKubes * $kube['hdd_limit'] . ' ' . Units::getHDDUnits(); ?><br>
    <b>High availability:</b> Yes<br>
</div>