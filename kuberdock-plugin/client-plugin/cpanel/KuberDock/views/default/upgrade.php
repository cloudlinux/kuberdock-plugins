<link rel="stylesheet" type="text/css" href="assets/script/slider/jquery.nouislider.min.css">
<script src="assets/script/slider/jquery.nouislider.all.min.js"></script>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h3>Give more power to your application.</h3>
            <p>
                Add kubes to allocate more resources for application "<strong><?php echo $pod->name?></strong>"<br>
                Price per kube: <strong><?php echo $pod->getKubePrice()?></strong>
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="message"><?php echo $this->controller->error?></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <form method="post" class="upgrade-form">
                <input type="hidden" name="podName" value="<?php echo $pod->name?>">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="col-md-4">Image</th>
                        <th class="col-md-8">Kubes</th>
                    </tr>
                    </thead>
                    <tbody>
                <?php foreach($pod->containers as $container):?>
                    <tr>
                        <th>
                            <input type="hidden" name="container_name[]" value="<?php echo $container['name']?>">
                            <b><?php echo $container['image']?></b>
                        </th>
                        <th>
                            <div class="slider"></div>
                            <div class="slider-value"></div>
                            <input type="hidden" name="container_kubes[]" value="<?php echo $container['kubes']?>">
                            <input type="hidden" class="new-kubes" name="new_container_kubes[]" value="<?php echo $container['kubes']?>">
                        </th>
                    </tr>
                <?php endforeach;?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="center">
                <p><strong>Amount of allocated resources:</strong></p>

                <div class="resources">
                    <span>CPU: X cores</span>
                    <span>HDD: X GB</span>
                    <span>RAM: X MB</span>
                </div>
            </div>

            <div class="pull-right">
                <input type="hidden" class="total-price" value="<?php echo $pod->getTotalPrice(true)?>">
                <strong>Total price: <span class="new-price">X</span> / <?php echo $pod->getPaymentType()?></strong>
            </div>
            <div class="clearfix"></div>

            <div style="margin: 15px auto;">
                <div class="pull-left">
                    <a href="javascript: window.history.back();">
                        <button class="btn btn-default">Cancel</button>
                    </a>
                </div>

                <div class="pull-right">
                    <div class="ajax-loader buttons hidden"></div>
                    <button class="btn btn-primary pod-upgrade">Confirm upgrade</button>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>

<script>
    var kube = <?php echo json_encode($pod->getKubeType())?>;
    var currency = <?php echo json_encode($pod->getPanel()->billing->getCurrency())?>;

    var setResources = function() {
        var kubes = 0;
        var newKubes = 0;
        var totalPrice = parseFloat($('.total-price').val());
        var template = '<span>CPU: %C</span>' +
            '<span>HDD: %H</span>' +
            '<span>RAM: %M</span>';

        $('input[name^="container_kubes"]').each(function() {
            kubes += parseInt($(this).val());
        });

        $('input.new-kubes').each(function() {
            newKubes += parseInt($(this).val());
        });

        var data = {
            '%C': wNumb({
                decimals: 3,
                postfix: ' cores'
            }).to(newKubes * kube.cpu_limit),
            '%H': wNumb({
                decimals: 0,
                postfix: ' GB'
            }).to(newKubes * kube.hdd_limit),
            '%M': wNumb({
                decimals: 0,
                postfix: ' MB'
            }).to(newKubes * kube.memory_limit)

        };

        $('.resources').html(template.replace(/%C|%H|%M/gi, function(e) {
            return data[e];
        }));
        $('.new-price').text(wNumb({
            decimals: 2,
            prefix: currency.prefix,
            postfix: currency.suffix
        }).to(totalPrice + Math.abs(newKubes - kubes) * parseFloat(kube.kube_price)));
    }

    $(document).ready(function() {
        _$('.slider').each(function() {
            var kubes = parseInt(_$(this).parents('th').find('input[name^="container_kubes"]').val());

            _$(this).noUiSlider({
                animate: true,
                start: kubes,
                step: 1,
                range: {
                    min: kubes,
                    max: 20
                },
                format: wNumb({
                    decimals: 0
                })
            });
            _$(this).val(kubes);
            _$(this).Link().to(_$(this).parents('th').find('div.slider-value'));
            _$(this).Link().to(_$(this).parents('th').find('input.new-kubes'));
            _$(this).on('change', function() {
                setResources();
            });
        });

        setResources();
    });
</script>