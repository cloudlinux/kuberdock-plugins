<?php echo \Kuberdock\classes\Base::model()->getStaticPanel()->getAssets()->renderScripts(); ?>

<?php
$variables = compact(array_diff(
    array_keys(get_defined_vars()),
    array('viewPath')
));
?>

<div id="admin-plugin">

    <?php foreach ($messages as $message => $type) : ?>
        <div role="alert" class="alert alert-<?php echo $type;?>">
            <?php echo $message;?>
        </div>
    <?php endforeach;?>

    <?php if (isset($error) && $error == true) :?>
        <?php $this->renderPartial('kubecli', array('kubeCli' => $kubeCli, 'error' => $error));?>
    <?php else : ?>
        <?php $this->renderPartial('tabs', $variables);?>
        <script>
            var defaults = <?php echo $defaults;?>;
            var packagesKubes = <?php echo $packagesKubes;?>;
            var activeTab = '<?php echo $activeTab;?>';
        </script>
    <?php endif; ?>

    <div class="container-fluid support text-center">
        Plugin version: <?php echo \Kuberdock\classes\DI::get('Version')->get();?>
    </div>
</div>