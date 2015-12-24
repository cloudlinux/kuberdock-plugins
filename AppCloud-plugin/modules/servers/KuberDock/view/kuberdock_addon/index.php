<div class="container-fluid">
    <div role="tabpanel">
        <ul class="nav nav-tabs" role="tablist" id="kuber_tab">
            <li role="presentation" class="active"><a href="#kube" aria-controls="relation" role="tab" data-toggle="tab">Kube types</a></li>
            <li role="presentation"><a href="#relation" aria-controls="relation" role="tab" data-toggle="tab">Kube Relations</a></li>
            <li role="presentation"><a href="#price" aria-controls="price" role="tab" data-toggle="tab">Kube types pricing</a></li>
        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="kube">
                <?php $this->renderPartial('kubes', array(
                    'kubes' => $kubes,
                    'servers' => $servers,
                    'productKubes' => $productKubes,
                ))?>
            </div>

            <div role="tabpanel" class="tab-pane" id="relation">
                <?php $this->renderPartial('relations', array(
                    'products' => $products,
                    'servers' => $servers,
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
</div>

<div class="container-fluid support">
    If you have a problem contact our support team via <a href="mailto:helpdesk@kuberdock.com">
        helpdesk@kuberdock.com</a> or create a request in helpdesk <a href="https://helpdesk.cloudlinux.com">
        https://helpdesk.cloudlinux.com</a>
</div>