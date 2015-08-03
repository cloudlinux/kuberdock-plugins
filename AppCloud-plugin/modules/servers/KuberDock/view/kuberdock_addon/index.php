<div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist" id="kuber_tab">
        <li role="presentation" class="active"><a href="#kube" aria-controls="relation" role="tab" data-toggle="tab">Kube types</a></li>
        <li role="presentation"><a href="#relation" aria-controls="relation" role="tab" data-toggle="tab">Packages</a></li>
        <li role="presentation"><a href="#price" aria-controls="price" role="tab" data-toggle="tab">Pricing</a></li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="kube">
            <?php $this->renderPartial('kubes', array(
                'products' => $products,
                'kubes' => $kubes,
                'productKubes' => $productKubes,
            ))?>
        </div>

        <div role="tabpanel" class="tab-pane" id="relation">
            <?php $this->renderPartial('relations', array(
                'products' => $products,
                'search' => $search,
                'productKubes' => $productKubes,
                'brokenPackages' => $brokenPackages,
            ))?>
        </div>

        <div role="tabpanel" class="tab-pane" id="price">
            <?php $this->renderPartial('price', array(
                'products' => $products,
                'kubes' => $kubes,
            ))?>
        </div>
    </div>
</div>