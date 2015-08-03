<div class="container-fluid content">
    <div class="row">
        <p>
            You are about to launch new dedicated application. Please, select amount of resources you want to make
            available for that application. After your application will be launched it will be available during
            one minute.
        </p>

        <div class="page-header">
            <h2>Search for apps</h2>
        </div>
    </div>

    <div class="message"><?php echo $this->controller->error?></div>

    <div class="row">
        <div class="col-md-10" style="margin-bottom: 10px;">
            <div class="input-group">
                <input type="text" class="form-control" id="image" aria-label="..." placeholder="Search for apps" value="<?php echo $search?>">
                <!--<span class="input-group-addon">
                    <input type="checkbox" aria-label="...">
                    Private
                </span>-->
                <span class="input-group-btn">
                    <button class="btn btn-default image-search" type="button">
                        Search
                        <span class="glyphicon glyphicon-search" aria-hidden="true"></span>
                    </button>
                </span>
            </div>
        </div>
        <span class="ajax-loader hidden"></span>
    </div>

    <?php $this->controller->renderPartial('search_content', array(
        'page' => $page,
        'pagination' => $pagination,
        'images' => $images,
        'search' => $search,
        'registryUrl' => $registryUrl,
    )); ?>

</div>