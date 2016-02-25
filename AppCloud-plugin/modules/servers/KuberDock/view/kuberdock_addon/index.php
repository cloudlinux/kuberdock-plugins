<?php
/**
 * @var $log array Change log
 * @var $tab string Current tab
 * @var $currency \base\models\CL_Currency
 */
?>

<div class="container-fluid">
    <div role="tabpanel">
        <ul class="nav nav-tabs" role="tablist" id="kuber_tab">
            <li role="presentation">
                <a href="#kubes" aria-controls="kubes" role="tab">Kube types</a>
            </li>
            <li role="presentation">
                <a href="#log" aria-controls="log" role="tab">Changes log</a>
            </li>
        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane" id="kubes">
                <?php $this->renderPartial('kubes', array(
                    'kubes' => $kubes,
                    'products' => $products,
                    'brokenPackages' => $brokenPackages,
                ))?>
            </div>
            <div role="tabpanel" class="tab-pane" id="log">
                <?php $this->renderPartial('log', array(
                    'logs' => $logs,
                    'paginator' => $paginator,
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