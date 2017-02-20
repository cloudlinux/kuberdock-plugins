<?php
/**
 * @var $log array Change log
 * @var $tab string Current tab
 */

$variables = compact(array_diff(
    array_keys(get_defined_vars()),
    array('viewPath')
));
?>

<div class="container-fluid">
    <div role="tabpanel">
        <ul class="nav nav-tabs" role="tablist" id="kuber_tab">
            <?php foreach ($tabs as $tab => $title):?>
                <li role="presentation">
                    <a href="#<?php echo $tab;?>" aria-controls="<?php echo $tab;?>" role="tab"><?php echo $title;?></a>
                </li>
            <?php endforeach;?>
        </ul>

        <div class="tab-content">
            <?php foreach ($tabs as $tab => $title):?>
                <div role="tabpanel" class="tab-pane" id="<?php echo $tab;?>">
                    <?php $this->renderPartial($tab, $variables)?>
                </div>

            <?php endforeach;?>
        </div>
    </div>
</div>