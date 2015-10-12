<div class="container-fluid content">
    <div class="row">
        <p>
            You are about to launch new dedicated application. Please, select amount of resources you want to make
            available for that application. After your application will be launched it will be available during
            one minute.
        </p>

        <div class="page-header">
            <h2>Your Apps</h2>
        </div>

        <?php if($postDescription):?>
        <div class="alert alert-dismissible alert-success"  role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <strong><?php echo $postDescription?></strong>
        </div>
        <?php endif;?>
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
            <a href="?a=search" class="btn btn-primary">Add new App</a>
        </div>
        <div class="clearfix"></div>
    </div>
</div>