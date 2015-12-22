<link rel="stylesheet" type="text/css" href="assets/script/slider/jquery.nouislider.min.css">
<script src="assets/script/slider/jquery.nouislider.all.min.js"></script>
<script src="assets/script/install.js"></script>

<div class="container-fluid content preapp-install-page">
    <div class="row">
        <div class="page-header">
            <div class="col-xs-1 nopadding"><span class="header-ico-preapp-install"></span></div>
            <h2><?php echo $app->getName()?></h2>
        </div>
        <div class="col-xs-11 col-xs-offset-1 nopadding">
            <p class="pre-app-desc"><?php echo $app->getPreDescription();?></p>
        </div>
    </div>

    <div class="message"><?php echo $this->controller->error?></div>

    <form class="form-horizontal container-install predefined" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
        <input type="hidden" name="product_id" id="product_id" value="<?php echo $app->getPackageId(true)?>">
        <input type="hidden" name="kube_type" id="kube_type" value="<?php echo $app->getKubeTypeId()?>" data-pid="<?php echo $app->getPackageId(true)?>">
        <input type="hidden" name="total_kube_count" id="total_kube_count" value="<?php echo $app->getTotalKubes()?>">

        <div class="row">
            <div class="col-xs-11 col-xs-offset-1 nopadding">
                <?php foreach(array_keys($variables) as $variable):?>
                    <?php $app->renderVariable($variable, $this->controller);?>
                <?php endforeach;?>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-11 col-xs-offset-1 nopadding">
                <div class="col-sm-5 no-paddind">
                    <div class="product-description"></div>
                </div>
            </div>
        </div>

        <div class="row total-border-wrapper">
            <div class="total-border">Total: <span id="priceBlock"></span></div>
        </div>

        <div class="row" style="margin-bottom: 23px;">
            <div class="text-right">
                <button type="submit" class="btn btn-primary">Start your App</button>
                <div class="ajax-loader buttons hidden"></div>
            </div>
        </div>
    </form>
</div>

<script>
    var kubes = <?php echo json_encode($app->getApi()->getKubes())?>,
        units = <?php echo json_encode($app->units)?>;
</script>