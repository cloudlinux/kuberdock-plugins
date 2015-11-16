<div class="container-fluid">
    <div class="row offset-top">
        <div class="col-md-8">
        <?php $this->renderPartial('price_form', array(
            'productId' => $productId,
            'products' => $products,
            'priceKubes' => array(),
        ))?>
        </div>
    </div>
</div>