<div id="contents"></div>

<script>
    var userPackage = <?php echo $package ?>;
    var packages = <?php echo $packages ?>;
    var maxKubes = <?php echo $maxKubes ?>;
    var rootURL = '<?php echo $rootURL ?>';
    var imageRegistryURL = '<?php echo $imageRegistryURL ?>';
</script>

<?php echo \Kuberdock\classes\Base::model()->getStaticPanel()->getAssets()->renderScripts(); ?>