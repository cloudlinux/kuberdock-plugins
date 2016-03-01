<div id="all-apps" class="container-fluid content">
    <div class="row">
        <div class="col-md-12">
            <h2>Application "<?php echo $app->template->getName()?>"</h2>
        </div>
        <div class="col-md-12">
            <p>
                A list of your applications below. Click "Add new App" to set up new application or click on the name
                of application to go to application page with detailed information of application configuration.
                Use control buttons to start, edit or delete your application.
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="message"><?php echo $this->controller->error?></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table apps-list pod-list">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Public IP</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $this->controller->renderPartial('container_content', array('pods' => $pods));?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <a href="kuberdock.live.php?c=app&a=installPredefined&new=1&template=<?php echo $app->getTemplateId()?>" class="btn btn-primary add-new-app pull-right">
                Add new app "<?php echo $app->template->getName()?>"</a>
            <div class="clearfix"></div>
        </div>
    </div>
</div>

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