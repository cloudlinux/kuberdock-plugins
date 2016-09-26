<?php $this->renderPartial('tabs', array('active' => 'applications'));?>

<div id="apps">
    <?php foreach($apps as $app): ?>
        <div class="text-center" style="display: inline-block; margin: 5px 20px">
            <a href="<?php echo $app['path']?>">
                <img src="<?php echo $app['icon']; ?>" height="48" width="48">
            </a>
            <p>
                <a href="<?php echo $app['path']?>">
                    <?php echo $app['name']?>
                </a>
            </p>
        </div>
    <?php endforeach; ?>
</div>


