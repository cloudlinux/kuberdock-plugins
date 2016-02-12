<?php
/**
 * @var $log array Change log
 * @var $currency \base\models\CL_Currency
 */
?>

<div class="container-fluid">
    <div role="tabpanel">
        <ul class="nav nav-tabs" role="tablist" id="kuber_tab">
            <li role="presentation" class="active">
                <a href="#kubes" aria-controls="kubes" role="tab" data-toggle="tab">Kube types</a>
            </li>
        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="kubes">
                <?php $this->renderPartial('kubes', array(
                    'kubes' => $kubes,
                    'products' => $products,
                    'brokenPackages' => $brokenPackages,
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