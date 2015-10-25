<div class="container-fluid content">
    <div class="row">
        <div class="page-header">
            <h2>Application "<?php echo $app->getName()?>"</h2>
        </div>

        <p>
            List of running applications below. If you need more quntity of that application click on
            "Add new app" button. Click on the application name to go to application page with additional information.
        </p>
    </div>

    <div class="message"><?php echo $this->controller->error?></div>

    <?php $this->controller->renderPartial('container_content', array(
        'pods' => $pods,
    ));?>

    <div class="modal fade bs-example-modal-sm confirm-modal" tabindex="-1" role="dialog" aria-labelledby="Confirm">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    Some text
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-action" data-action="delete">Action</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="pull-right">
            <a href="kuberdock.live.php?c=app&a=installPredefined&new=1&template=<?php echo $app->getTemplateId()?>" class="btn btn-primary">
                Add new app "<?php echo $app->getName()?>"
            </a>
        </div>
        <div class="clearfix"></div>
    </div>
</div>