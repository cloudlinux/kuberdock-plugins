(function($) {
    $.fn.iselect = function(options) {
        var ISelect = function(el, options) {
            var _this = this;

            this.options = _.extend({
                async: false,
                url: ''
            }, options);
            this.el = '';
            this.$el = $(this.el);
            this.$current = $;
            this.className = this.options.className || 'iselect-popup';
            this.$popup = $('div.' + this.className);
            this.template = _.template('<div class="<%- className %>">' +
                '<% _.each(data, function(v) { %> ' +
                    '<span data-name="<%- v.name %>" data-size="<%- v.size %>"><%- v.name %> (<%- v.size %>)</span>' +
                '<% }); %></div>');
            this.data = [];

            this.init = function(el) {
                this.el = el;
                this.$el = $(el);
                this.createPopup();
                this.bindEvents();
            };

            this.createPopup = function() {
                if(!this.$popup.length) {
                    $.ajax({
                        async: this.options.async || false,
                        method: 'GET',
                        url: this.options.url,
                        dataType: 'json'
                    }).done(function(data) {
                        _this.data = data.data;
                        $('body').append(_this.options.template || _this.template({
                            className: _this.className,
                            data: _this.data
                        }));
                        _this.$popup = $('div.' + _this.className);
                        $(document).on('click', _this.options.filled || _this.filled);
                    });
                }
            };

            this.bindEvents = function() {
                this.$el.off('click').on('click', this.options.clicked || this.clicked);
                this.$el.off('keyup').on('keyup', this.options.filled || this.filled);
                this.$popup.find('span').off('click').on('click', this.options.selected || this.selected);
            };

            this.clicked = function(e) {
                e.stopPropagation();
                _this.setCurrent(e.target);
                _this.popupShow();
            };

            this.filled = function(e) {
                e.stopPropagation();
                if(!$(e.target).is(_this.$current)) {
                    _this.popupHide();
                }
            };

            this.selected = function(e) {
                e.stopPropagation();
                var parent = _this.$current.parents('tr:eq(0)');

                parent.find('input.volume-name').val($(this).data('name'));
                parent.find('input.volume-size').val($(this).data('size'));
                _this.popupHide();
            };

            this.popupShow = function() {
                var offset = this.$current.offset(),
                    top = offset.top + _this.$current.height() + 4,
                    left = offset.left;
                //this.$popup.offset({top: top, left: left});   // in this case on 2nd click values are added, instead replacing
                this.$popup.css({top: top + 'px', left: left + 'px'});
                this.$popup.show();
            };

            this.popupHide = function() {
                this.$popup.hide();
            };

            this.setCurrent = function(el) {
                this.$current = $(el);

                return this;
            };

            this.init(el);
        };

        var S = new ISelect(this, options);
        return this;
    };
}(jQuery));

$(function() {
    var calculateTotal = function() {
        var el = $('#kuber_kube_id option:selected, #KUBE_TYPE option:selected'),
            el = el.length ? el : $('#kube_type'),
            kubeCount = 0,
            kubeEl = $('#kube_count, input[id*="KUBE_COUNT"]').length
                ? $('#kube_count, input[id*="KUBE_COUNT"]') : $('#total_kube_count'),
            productId = el.data('pid'),
            kube = kubes[productId].kubes[el.val()],
            currency = kubes[productId].currency;

        $.each(kubeEl, function() {
            kubeCount += parseInt($(this).val());
        });

        var price = wNumb({
                decimals: 2,
                prefix: kubes[productId].currency.prefix,
                postfix: kubes[productId].currency.suffix  + ' / ' + kubes[productId].paymentType.replace('ly', '')
            }).to(kube.kube_price * kubeCount);

        $('.product-description').html(kubeTemplate({
            cpu: getFormattedValue(kube.cpu_limit * kubeCount, units.cpu),
            memory: getFormattedValue(kube.memory_limit * kubeCount, units.memory, 0),
            traffic: getFormattedValue(kube.traffic_limit * kubeCount, units.traffic),
            hdd: getFormattedValue(kube.hdd_limit * kubeCount, units.hdd)
        }));

        $('#product_id').val(productId);
        $('#priceBlock').html(price);
    };

    var initISelect = function() {
        $("input.volume-name").iselect({
            url: '?a=getPersistentDrives'
        });
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

    $(document).ready(function() {
        calculateTotal();
        initISelect();
    });

    $(document).on('change', '#kuber_kube_id, #KUBE_TYPE', calculateTotal);
    $(document).on('change', '.kube-slider, input[id*="KUBE_COUNT"]', calculateTotal);

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
    $(document).on('click', 'input.set-persistent', function(e) {
        var parent = $(this).parents('tr:eq(0)');

        if($(this).is(':checked')) {
            parent.find('input.volume-name, input.volume-size').prop('disabled', false);
        } else {
            parent.find('input.volume-name, input.volume-size').prop('disabled', true);
        }
    });

    $(document).on('click', 'button#add_volume', function(e) {
        e.preventDefault();
        var table = $('#volume_table'),
            k = table.find('tr').length - 1,
            template = '<tr><td><input type="text" class="middle" name="Volume[' + k + '][mountPath]" placeholder="Empty"></td>' +
                '<td class="text-center"><input type="checkbox" name="Volume[' + k + '][persistent]" class="set-persistent" value="1"></td>' +
                '<td><input type="text" class="short volume-name" name="Volume[' + k + '][name]" autocomplete="off" placeholder="Empty" disabled></td>' +
                '<td><input type="text" class="short volume-size" name="Volume[' + k + '][size]" placeholder="Empty" disabled></td>' +
                '<td><small>MB</small></td>' +
                '<td><button type="button" class="btn btn-default btn-sm delete-port">' +
                '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>' +
                '</button></td></tr>';

        table.removeClass('hidden');
        table.append(template);
        initISelect();
    });

    $(document).on('click', 'button.delete-port, button.delete-env, button.delete-volume', function(e) {
        e.preventDefault();

        var table = $(this).parents('table:eq(0)');
        $(this).parents('tr').remove();
        if(table.find('tr').length <= 1) {
            table.addClass('hidden');
        }

        initISelect();
    });
});