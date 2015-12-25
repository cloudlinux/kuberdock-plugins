<div class="container-fluid content preapp-plan-page">
    <div class="row">
        <div class="page-header">
            <div class="col-xs-1 nopadding"><span class="header-ico-preapp-install"></span></div>
            <h2><?php echo $app->getName()?></h2>
        </div>
        <div class="col-xs-11 col-xs-offset-1 nopadding">
            <p class="pre-app-desc"><?php echo $app->getPreDescription();?></p>
        </div>
    </div>

    <div class="message"><?php echo $this->controller->error?></div>

    <form class="form-horizontal container-install predefined palans" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
        <div class="col-xs-11 col-xs-offset-1 nopadding">
            <div class="row">
                <strong>Choose plan:</strong>
            </div>

            <div class="row col-xs-12 nopadding">
                <?php foreach($plans as $k => $plan):?>
                    <?php if(isset($plan['recommended'])):?>
                        <div class="col-xs-3 item recommended">
                    <?php else:?>
                        <div class="col-xs-3 item">
                    <?php endif;?>
                        <img src="img path">
                        <div class="plan-name"><?php echo $plan['name']?></div>
                        <div class="description">
                            <strong>Good for</strong><br/>
                            <span><?php echo $plan['goodfor']?></span>
                        </div>

                        <a class="show-details">Show details</a>

                        <?php echo $app->renderTotalByPlanId($k);?>

                        <div class="margin-top">
                            <a href="kuberdock.live.php?c=app&a=installPredefined&planDetails=1&template=<?php echo $app->getTemplateId()?>&plan=<?php echo $k?>" class="btn btn-primary">
                                Choose plan
                            </a>
                        </div>
                    </div>
                <?php endforeach;?>
            </div>
        </form>

        <div class="row col-xs-12 info-description">You can choose another plan at any time</div>
    </div>
</div>