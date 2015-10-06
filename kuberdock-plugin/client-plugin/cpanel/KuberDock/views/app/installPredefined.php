<link rel="stylesheet" type="text/css" href="assets/script/slider/jquery.nouislider.min.css">
<script src="assets/script/slider/jquery.nouislider.all.min.js"></script>
<script src="assets/script/install.js"></script>

<div class="container-fluid content">
    <div class="row">
        <p>
            You are about to launch new dedicated application. Please, select amount of resources you want to make
            available for that application. After your application will be launched it will be available during
            one minute.
        </p>

        <div class="page-header">
            <h2>Setup "<?php echo $image?>"</h2>
        </div>
    </div>

    <div class="message"><?php echo $this->controller->error?></div>

    <div class="row">
        <form class="form-horizontal container-install" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
            <div class="row">
                <div class="col-sm-12 text-center">
                    <button type="submit" class="btn btn-primary">Start your App</button>
                    <div class="ajax-loader buttons hidden"></div>
                </div>
            </div>
        </form>
    </div>
</div>