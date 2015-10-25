<div id="all-apps" class="container-fluid content">
    <div class="row">
        <div class="col-md-12">
            <h2>Your Apps</h2>
        </div>
        <div class="col-md-12">
            <p>You are about to launch new dedicated application. Please, select amount of resources you want to make
            available for that application. After your application will be launched it will be available during
            one minute.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="message"><?php echo $this->controller->error?></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table apps-list">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>IP version</th>
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
            <a href="?a=search" class="add-new-app blue pull-right">Add new App</a>
            <div class="clearfix"></div>
        </div>
    </div>
</div>

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