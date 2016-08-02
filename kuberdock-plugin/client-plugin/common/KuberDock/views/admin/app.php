<?php echo \Kuberdock\classes\Base::model()->getStaticPanel()->getAssets()->renderScripts(); ?>

<div id="create-new-app" class="container pull-left top-offset">

    <div class="row">
        <div class="col-xs-6 col-md-12">
            <h3><?php if ($id): ?>Update<?php else: ?>Create<?php endif;?> application</h3>
        </div>
    </div>

    <?php foreach ($errors as $error) : ?>
        <div role="alert" class="alert alert-danger">
            <?php echo $error;?>
        </div>
    <?php endforeach;?>

    <form method="post" class="form-inline">
        <input type="hidden" name="id" value="<?php echo $app['id'];?>">
        <div class="row">
            <div class="col-xs-6 col-md-12">
                <label for="app_name">App name</label>
                <input type="text" id="app_name" name="name" class="form-control" value="<?php echo $app['name'];?>"
                       data-validation="custom" data-validation-regexp="^([a-zA-Z_-\s]+)$">
            </div>
        </div>

        <div class="row">
            <div class="col-xs-6 col-md-12">
                <a id="upload_button" class="btn btn-primary">Upload YAML</a>
                <input type="file" id="yaml_file" style="display: none">
            </div>
        </div>

        <div class="row top-offset">
            <div class="col-xs-6 col-md-12">
                <textarea id="template" class="code-editor" name="template"><?php echo $app['template'];?></textarea>
            </div>
        </div>

        <div class="row top-offset">
            <div class="col-xs-6 col-md-12">
                <a href="<?php echo $this->controller->homeUrl?>#pre_apps" class="btn btn-default">Back</a>
                <button type="submit" class="btn btn-primary check-yaml" name="save">
                    <?php if ($id) : ?>
                        Update application
                    <?php else : ?>
                        Add application
                    <?php endif; ?>
                </button>
            <div>
            </div>
            </div>

        </div>
    </form>
</div>

<div class="modal fade" id="validationConfirm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Invalid template</h4>
                <h6 class="modal-explain">Your template contains some errors. Are you sure you want to save it with those errors?</h6>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" id="button-confirm" class="btn btn-primary" data-dismiss="modal">Save anyway</button>
            </div>
        </div>
    </div>
</div>