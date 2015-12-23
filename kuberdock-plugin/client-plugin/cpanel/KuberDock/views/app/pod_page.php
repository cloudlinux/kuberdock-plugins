<div id="app-page" class="container-fluid content">
    <div class="row">
        <div class="col-md-12 splitter">
            <h2>Application "<?php echo $app->getName()?>"</h2>
            <?php if($postDescription):?>
                <div class="alert alert-dismissible alert-success"  role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <pre><?php echo $postDescription?></pre>
                </div>
            <?php endif;?>
            <div class="message"><?php echo $this->controller->error?></div>
        </div>
    </div>

    <?php $this->renderPartial('pod_details', array(
        'app' => $app,
        'pod' => $pod,
        'podsCount' => $podsCount,
        'domains' => $domains,
    ))?>

    <div class="modal fade bs-example-modal-sm confirm-modal" tabindex="-1" role="dialog" aria-labelledby="Confirm">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">Some text</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-action" data-action="delete">Action</button>
                </div>
            </div>
        </div>
    </div>
</div>