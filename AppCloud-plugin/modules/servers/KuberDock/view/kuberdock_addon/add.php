<div class="container-fluid">
    <div class="row">
        <h3 class="section">Adding kube type</h3>

        <div class="col-md-4">
            <?php if($this->controller->error):?>
                <div class="alert alert-danger" role="alert"><?php echo $this->controller->error?></div>
            <?php endif; ?>

            <?php $this->renderPartial('form', array(
                'products' => $products,
                'kube' => $kube,
                'kubes' => $kubes,
                'servers' => $servers,
            )); ?>
        </div>
    </div>
</div>
