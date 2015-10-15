<link rel="stylesheet" type="text/css" href="assets/script/slider/jquery.nouislider.min.css">
<script src="assets/script/slider/jquery.nouislider.all.min.js"></script>
<script src="assets/script/install.js"></script>

<div class="container-fluid content">
    <div class="row">
        <p>
            <?php echo $app->getPreDescription()?>
        </p>

        <div class="page-header">
            <h2>Setup "<?php echo $app->getName()?>"</h2>
        </div>
    </div>

    <div class="message"><?php echo $this->controller->error?></div>

    <form class="form-horizontal container-install predefined" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
        <input type="hidden" name="product_id" id="product_id" value="">

    <div class="row">
        <?php foreach($variables as $variable => $row):?>
            <div class="form-group">
                <label for="<?php echo $variable?>" class="col-sm-3 control-label"><?php echo $row['description']?></label>

                <div class="col-sm-4">
                <?php if(isset($row['data'])):?>
                    <select name="<?php echo $variable?>" id="<?php echo $variable?>">
                    <?php foreach($row['data'] as $k => $r):?>
                        <option value="<?php echo $r['id']?>" data-pid="<?php echo $r['product_id']?>"<?php echo ($k == $row['default']) ? ' selected' : ''?>>
                            <?php echo $r['name']?>
                        </option>
                    <?php endforeach;?>
                    </select>

                <?php else:?>
                    <input type="text" name="<?php echo $variable?>" id="<?php echo $variable?>" value="<?php echo $row['default']?>">
                <?php endif;?>
                </div>
            </div>
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
    var kubes = <?php echo json_encode($app->kuberKubes)?>,
        units = <?php echo json_encode($app->units)?>;
</script>