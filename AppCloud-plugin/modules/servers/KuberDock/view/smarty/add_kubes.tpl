{$controller->assets->renderScriptFiles()}
{$controller->assets->renderStyleFiles()}

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h3>Give more power to your application.</h3>
            <p>
                Add kubes to allocate more resources for application "<strong>{$pod->name}</strong>"<br>
                Price per kube: <strong>{$kubePrice}</strong>
            </p>

            {if $errorMessage}
            <div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                {$errorMessage}
            </div>
            {/if}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <form method="post" class="upgrade-form">
                {$csrf}
                <table class="table">
                    <thead>
                    <tr>
                        <th class="col-md-4">Image</th>
                        <th class="col-md-8">Kubes</th>
                    </tr>
                    </thead>
                    <tbody>
                {foreach from=$pod->containers item=container}
                    <tr>
                        <th>
                            <input type="hidden" name="container_name[]" value="{$container.name}">
                            <b>{$container.image}</b>
                        </th>
                        <th>
                            <div class="slider"></div>
                            <div class="slider-value"></div>
                            <input type="hidden" name="container_kubes[]" value="{$container.kubes}">
                            <input type="hidden" class="new-kubes" name="new_container_kubes[]" value="{$container.kubes}">
                        </th>
                    </tr>
                {/foreach}
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
                <input type="hidden" class="total-price" value="{$totalPrice}">
                <strong>Total price: <span class="new-price">X</span>/{$paymentType}</strong>
            </div>
            <div class="clearfix"></div>

            <div style="margin: 15px auto;">
                <div class="pull-left">
                    <a href="javascript: window.history.back();">Cancel</a>
                </div>

                <div class="pull-right">
                    <button class="btn btn-default" onclick="$('.upgrade-form').submit()">Confirm upgrade</button>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>

<script>
    $(function() {
        var kube = {$kube};
        var currency = {$currency};

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
            $('.slider').each(function() {
                var kubes = parseInt($(this).parents('th').find('input[name^="container_kubes"]').val());
                $(this).noUiSlider({
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
                $(this).val(kubes);
                $(this).Link().to($(this).parents('th').find('div.slider-value'));
                $(this).Link().to($(this).parents('th').find('input.new-kubes'));
                $(this).on('change', function() {
                    setResources();
                });
            });

            setResources();
        });
    })
</script>