<link rel="stylesheet" type="text/css" href="assets/script/slider/jquery.nouislider.min.css">
<script src="assets/script/slider/jquery.nouislider.all.min.js"></script>
<script src="assets/script/install.js"></script>

<div class="container-fluid content preapp-install-page">
    <div class="row">
        <div class="page-header">
            <div class="col-xs-1 nopadding"><span class="header-ico-preapp-install"></span></div>
            <h2><?php echo $app->template->getName()?></h2>
        </div>
        <div class="col-xs-11 col-xs-offset-1 nopadding">
            <p class="pre-app-desc"><?php echo $app->template->getPreDescription();?></p>
        </div>
    </div>

    <div class="message"><?php echo $this->controller->error?></div>

    <form class="form-horizontal container-install predefined" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
        <input type="hidden" name="plan" id="plan" value="<?php echo $plan?>">
        <input type="hidden" name="product_id" id="product_id" value="<?php echo $app->getPackageId(true)?>">
        <input type="hidden" name="kube_type" id="kube_type" value="<?php echo $app->template->getKubeTypeId($plan)?>" data-pid="<?php echo $app->getPackageId(true)?>">
        <input type="hidden" name="total_kube_count" id="total_kube_count" value="<?php echo $app->template->getTotalKubes($plan)?>">
        <input type="hidden" name="public_ip" id="public_ip" value="<?php echo $app->template->getPublicIP()?>">
        <input type="hidden" name="total_pd_size" id="total_pd_size" value="<?php echo $app->template->getPersistentStorageSize($plan)?>">

        <div class="row">
            <div class="col-xs-11 col-xs-offset-1 nopadding">
                <?php foreach(array_keys($variables) as $variable):?>
                    <?php $app->renderVariable($variable, $this->controller);?>
                <?php endforeach;?>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-11 col-xs-offset-1 nopadding">
                <div class="col-sm-5 nopadding">
                    <div class="product-description"></div>
                </div>
            </div>
        </div>

        <div class="row total-border-wrapper">
            <div class="total-border">Total: <span id="priceBlock"></span></div>
        </div>

        <div class="row" style="margin-bottom: 23px;">
            <div class="text-right">
                <?php if(is_numeric($plan)):?>
                    <a href="kuberdock.live.php?c=app&a=installPredefined&template=<?php echo $app->getTemplateId()?>" class="btn btn-primary">
                        Choose different package
                    </a>
                <?php endif;?>

                <button type="submit" class="btn btn-primary start-button">Start your App</button>
                <div class="ajax-loader buttons hidden"></div>
            </div>
        </div>
    </form>
</div>

<script>
    var kubes = <?php echo json_encode($app->getPanel()->billing->getKubes())?>,
        units = <?php echo json_encode($app->pod->units)?>;
</script>