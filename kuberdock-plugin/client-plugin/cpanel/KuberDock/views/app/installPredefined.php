<link rel="stylesheet" type="text/css" href="assets/script/slider/jquery.nouislider.min.css">
<script src="assets/script/slider/jquery.nouislider.all.min.js"></script>
<script src="assets/script/install.js"></script>

<div class="container-fluid content">
    <div class="row">
        <p>
            <?php echo $app->getPreDescription();?>
        </p>

        <div class="page-header">
            <h2><?php echo $app->getName()?></h2>
        </div>
    </div>

    <div class="message"><?php echo $this->controller->error?></div>

    <form class="form-horizontal container-install predefined" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
        <input type="hidden" name="product_id" id="product_id" value="<?php echo $app->getPackageId(true)?>">
        <input type="hidden" name="kube_type" id="kube_type" value="<?php echo $app->getKubeTypeId()?>" data-pid="<?php echo $app->getPackageId(true)?>">
        <input type="hidden" name="total_kube_count" id="total_kube_count" value="<?php echo $app->getTotalKubes()?>">

    <div class="row">
        <?php foreach(array_keys($variables) as $variable):?>
            <?php $app->renderVariable($variable, $this->controller);?>
        <?php endforeach;?>
    </div>

    <div class="row">
        <div class="col-sm-5">
            <div class="product-description"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <strong>Total: </strong><span id="priceBlock"></span>
        </div>
    </div>

    <div class="row">
        <div class="row">
            <div class="col-sm-12 text-center">
                <button type="submit" class="btn btn-primary">Start your App</button>
                <div class="ajax-loader buttons hidden"></div>
            </div>
        </div>
    </div>
    </form>
</div>

<script>
    var kubes = <?php echo json_encode($app->getApi()->getKubes())?>,
        units = <?php echo json_encode($app->units)?>;
</script>