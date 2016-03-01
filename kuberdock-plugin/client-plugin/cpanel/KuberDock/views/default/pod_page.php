<div id="app-page" class="container-fluid content">
    <div class="row">
        <div class="col-md-12 splitter">
            <h2>Application "<?php echo $pod->name?>"</h2>
            <div class="message"><?php echo $this->controller->error?></div>
        </div>
    </div>

    <?php $this->renderPartial('pod_details', array(
        'pod' => $pod,
    ))?>

    <div class="modal fade bs-example-modal-sm confirm-modal" tabindex="-1" role="dialog" aria-labelledby="Confirm">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">Some text</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-action" data-action="delete">Action</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bs-example-modal-sm restart-modal" tabindex="-1" role="dialog" aria-labelledby="Confirm">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">Some text</div>
                <div class="modal-footer">
                    <div class="pull-left">
                        <button type="button" class="btn btn-danger" data-action="restart" data-wipe="1">Wipe Out</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary btn-action" data-action="restart">Just Restart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
