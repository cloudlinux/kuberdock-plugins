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
                <button type="submit" class="btn btn-primary" name="save">
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