<?php
$tabs = array(
    'pre_apps' => 'Existing apps',
    'defaults' => 'Application defaults',
    'kubecli' => 'Edit kubecli.conf',
);

$variables = compact(array_diff(
    array_keys(get_defined_vars()),
    array('viewPath')
));
?>

<h3>KuberDock plugin</h3>

<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-tabs" role="tablist">
            <?php foreach ($tabs as $tab => $title):?>
                <li role="presentation">
                    <a href="#<?php echo $tab;?>" aria-controls="<?php echo $tab;?>" data-toggle="tab" role="tab">
                        <?php echo $title;?>
                    </a>
                </li>
            <?php endforeach;?>
        </ul>
    </div>
</div>

<div class="row">
    <div class="tab-content">
        <?php foreach ($tabs as $tab => $title):?>
            <div role="tabpanel" class="tab-pane" id="<?php echo $tab;?>">
                <?php $this->renderPartial($tab, $variables)?>
            </div>
        <?php endforeach;?>
    </div>
</div>