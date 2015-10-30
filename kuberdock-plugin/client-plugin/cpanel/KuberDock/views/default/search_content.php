<div class="search-content">
<?php if(empty($images)):?>
    <?php if($search):?>
    <h2>Images not founded</h2>
    <?php endif;?>
<?php else:?>
    <div class="row">
        <div class="col-md-10">
            <table class="table table-bordered">
                <?php foreach($images as $k => $image):
                    $i = ($page-1) * $pagination->itemsPerPage + $k + 1;
                    $url = sprintf('%s/%s/%s', $registryUrl, $image['is_official'] ? '_' : 'u', $image['name']);
                    ?>
                    <tr>
                        <td>
                            <div>
                                <?php echo $i;?>. <strong class="text-info"><?php echo $image['name']?></strong>
                            </div>
                            <?php echo $image['description']?>

                            <div class="hidden info">
                                <?php if($image['star_count']):
                                    $starCount = $image['star_count'] > 10 ? 10: $image['star_count'];
                                ?>
                                    <p>
                                        <?php for($i=0; $i<$starCount; $i++):?>
                                            <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
                                        <?php endfor;?>
                                    </p>
                                <?php endif;?>
                                <p><?php echo $image['is_official'] ? 'Official' : 'Unofficial'?></p>
                            </div>

                            <p>
                                <a href="<?php echo $url?>" target="_blank" class="image-more-details">More details</a>
                            </p>
                        </td>
                        <td>
                            <a href="?a=install&image=<?php echo $image['name']?>" class="btn btn-default install-app" app="<?php echo $image['name']?>">Install</a>
                        </td>
                    </tr>
                <?php endforeach;?>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10 text-center">
            <?php $pagination->render()?>
        </div>
    </div>
<?php endif;?>
</div>