<div class="container-fluid">
    <div class="row">
        <h3 class="section">KuberDock kube types pricing</h3>

        <div class="col-md-8">
        <?php $this->renderPartial('price_form', array(
            'productId' => $productId,
            'products' => $products,
            'priceKubes' => array(),
        ))?>
        </div>
    </div>
</div>