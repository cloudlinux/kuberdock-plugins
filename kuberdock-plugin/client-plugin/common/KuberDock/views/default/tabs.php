<?php
$tabs = array(
    'index' => 'My apps',
    'applications' => 'Applications',
);
?>

<h3>KuberDock plugin</h3>

<div class="row tabs">
    <div class="col-md-12">
        <ul class="nav nav-tabs" role="tablist">
            <?php foreach ($tabs as $tab => $title):?>
                <li role="presentation" <?php if ($tab==$active): ?>class="active" <?php endif;?>>
                    <a href="/CMD_PLUGINS/KuberDock?a=<?php echo $tab;?>">
                        <?php echo $title;?>
                    </a>
                </li>
            <?php endforeach;?>
        </ul>
    </div>
</div>
