<?php
if ($panelType=='DirectAdmin') {
    $this->renderPartial('tabs', array('active' => 'index'));
}
?>

<div id="contents"></div>

<script>
    var userPackage = <?php echo $package ?>;
    var packages = <?php echo $packages ?>;
    var maxKubes = <?php echo $maxKubes ?>;
    var packageDefaults = <?php echo $packageDefaults ?>;
    var assetsURL = '<?php echo $assetsURL ?>';
    var rootURL = '<?php echo $rootURL ?>';
    var imageRegistryURL = '<?php echo $imageRegistryURL ?>';
    var panelType = '<?php echo $panelType ?>';
    var panelToken = <?php echo $panelToken ?>;
</script>

<?php echo \Kuberdock\classes\Base::model()->getStaticPanel()->getAssets()->renderScripts(); ?>