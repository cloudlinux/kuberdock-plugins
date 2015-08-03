$(function() {
    var calculateTotal = function() {
        var el = $('#kuber_kube_id option:selected'),
            kubeCount = $('#kube_count').val(),
            productId = el.data('pid'),
            kube = kubes[productId].kubes[el.val()],
            currency = kubes[productId].currency,
            price = wNumb({
                decimals: 2,
                prefix: kubes[productId].currency.prefix,
                postfix: kubes[productId].currency.suffix  + ' / ' + kubes[productId].paymentType.replace('ly', '')
            }).to(kube.kube_price * kubeCount);

        $('.product-description').html(kubeTemplate({
            cpu: getFormattedValue(kube.cpu_limit * kubeCount, units.cpu),
            memory: getFormattedValue(kube.memory_limit * kubeCount, units.memory, 0),
            traffic: getFormattedValue(kube.traffic_limit * kubeCount, units.traffic)
        }));

        $('#product_id').val(productId);
        $('#priceBlock').html(price);
    };

    $('.kube-slider').noUiSlider({
        start: [ 1 ],
        range: {
            min: [ 1 ],
            max: [ 10 ]
        },
        format: wNumb({
            decimals: 0,
            thousand: '.'
        })
    });

    $('.kube-slider').Link('lower').to($('.kube-slider-value'));
    $('.kube-slider').Link('lower').to($('#kube_count'));

    $(document).ready(calculateTotal);
    $(document).on('change', '#kuber_kube_id', calculateTotal);
    $(document).on('change', '.kube-slider', calculateTotal);

    // Ports
    $(document).on('click', 'button#add_port', function(e) {
        e.preventDefault();
        var table = $('#port_table'),
            k = table.find('tr').length - 1,
            template = '<tr><td><input type="text" name="Ports[' + k + '][containerPort]" placeholder="Empty"></td>' +
                '<td><select name="Ports[' + k + '][protocol]">' +
                '<option value="tcp">tcp</option><option value="udp">udp</option>' +
                '</select></td>' +
                '<td><input type="text" name="Ports[' + k + '][hostPort]" placeholder="Empty"></td>' +
                '<td class="text-center"><input type="checkbox" value="1" name="Ports[' + k + '][isPublic]"></td>' +
                '<td><button type="button" class="btn btn-default btn-sm delete-port">' +
                '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>' +
                '</button></td></tr>';

        table.removeClass('hidden');
        table.append(template);
    });

    // Environment variables
    $(document).on('click', 'button#add_env', function(e) {
        e.preventDefault();
        var table = $('#env_table'),
            k = table.find('tr').length - 1,
            template = '<tr><td><input type="text" name="Env[' + k + '][name]" placeholder="Empty"></td>' +
                '<td><input type="text" name="Env[' + k + '][value]" placeholder="Empty"></td>' +
                '<td><button type="button" class="btn btn-default btn-sm delete-port">' +
                '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>' +
                '</button></td></tr>';

        table.removeClass('hidden');
        table.append(template);
    });

    // Volume mounts
    $(document).on('click', 'button#add_volume', function(e) {
        e.preventDefault();
        var table = $('#volume_table'),
            k = table.find('tr').length - 1,
            template = '<tr><td><input type="text" class="middle" name="Volume[' + k + '][mountPath]" placeholder="Empty"></td>' +
                '<td class="text-center"><input type="checkbox" name="Volume[' + k + '][persistent]" value="1" disabled></td>' +
                '<td><input type="text" class="short" name="Volume[' + k + '][name]" placeholder="Empty" disabled></td>' +
                '<td><input type="text" class="short" name="Volume[' + k + '][size]" placeholder="Empty" disabled></td>' +
                '<td><small>MB</small></td>' +
                '<td><button type="button" class="btn btn-default btn-sm delete-port">' +
                '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>' +
                '</button></td></tr>';

        table.removeClass('hidden');
        table.append(template);
    });

    $(document).on('click', 'button.delete-port, button.delete-env, button.delete-volume', function(e) {
        e.preventDefault();

        var table = $(this).parents('table:eq(0)');
        $(this).parents('tr').remove();
        if(table.find('tr').length <= 1) {
            table.addClass('hidden');
        }
    });
});