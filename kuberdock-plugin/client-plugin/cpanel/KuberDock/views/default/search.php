<link rel="stylesheet" href="assets/script/owl-carousel/owl.carousel.css">
<link rel="stylesheet" href="assets/script/owl-carousel/owl.theme.css">
<script src="assets/script/owl-carousel/owl.carousel.min.js"></script>

<div class="container-fluid content">
    <div class="row">
        <div class="col-md-10">
            <div class="page-header">
                <h2>Search applications</h2>

            <?php if($templates):?>
                <p>
                    Choose available predefined application or search for additional applications in DockerHub
                    or other registries of docker applications.
                </p>

                <div id="owl_carousel" class="owl-carousel">
                <?php foreach($templates as $row):
                    $template = Spyc::YAMLLoadString($row['template']);
                    $imageSrc = isset($template['kuberdock']['icon'])
                        ? $template['kuberdock']['icon'] : 'assets/images/default.png';
                ?>
                    <div class="text-center">
                        <a href="?c=app&a=installPredefined&template=<?php echo $row['id']?>">
                            <img src="<?php echo $imageSrc?>" height="48" width="48">
                        </a>
                        <p>
                            <a href="?c=app&a=installPredefined&template=<?php echo $row['id']?>">
                                <?php echo $template['kuberdock']['name']?>
                            </a>
                        </p>
                    </div>
                <?php endforeach?>
                </div>
            <?php endif;?>
            </div>
        </div>
    </div>

    <div class="message"><?php echo $this->controller->error?></div>

    <div class="row">
        <div class="col-md-10">
            <p>Image registry: <a href="<?php echo $registryUrl?>" target="_blank"><?php echo $registryUrl?></a></p>
        </div>
    </div>

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

<script>
    $(function($) {
        $(document).ready(function() {
            $("#owl_carousel").owlCarousel({items: 5});
        });
    }(_$));
</script>