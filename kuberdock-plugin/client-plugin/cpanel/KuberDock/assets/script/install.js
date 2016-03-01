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
                if(!$(el).length) return;
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
                parent.find('input.volume-size').val($(this).data('size')).trigger('change');
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
}(_$));

(function($) {
    var calculateTotal = function() {
        var el = $('#kuber_kube_id option:selected, #KUBETYPE option:selected, #KUBE_TYPE option:selected'),
            el = el.length ? el : $('#kube_type'),
            kubeCount = 0,
            pdTotal = 0,
            kubeEl = $('#kube_count, #total_kube_count, input[id*="KUBE_COUNT"], input[id*="KUBES"]'),
            pdEl = $('#total_pd_size, input[id*="PD_SIZE"], input[type="checkbox"][class="set-persistent"]'),
            productId = el.data('pid'),
            kube = kubes[productId].kubes[el.val()],
            currency = kubes[productId].currency,
            publicIp = parseInt($('#public_ip').val()) || 0;

        if($('input.is-public').length) {
            $('input.is-public').each(function() {
                if($(this).prop('checked')) {
                    publicIp = 1;
                }
            });
        }

        $.each(kubeEl, function() {
            kubeCount += parseInt($(this).val());
        });

        $.each(pdEl, function() {
            if(pdEl.is(':checkbox')) {
                if(pdEl.prop('checked')) {
                    pdTotal += parseInt(pdEl.parents('tr').find('.volume-size').val()) || 0;
                }
            } else {
                pdTotal += parseInt($(this).val());
            }
        });

        var additionalPrice = kubes[productId]['priceIP'] * publicIp
            + kubes[productId]['pricePersistentStorage'] * pdTotal;
        var price = wNumb({
                decimals: 2,
                prefix: kubes[productId].currency.prefix,
                postfix: kubes[productId].currency.suffix  + ' / ' + kubes[productId].paymentType.replace('ly', '')
            }).to(kube.kube_price * kubeCount + additionalPrice);

        $('.product-description').html(kubeTemplate({
            cpu: getFormattedValue(kube.cpu_limit * kubeCount, units.cpu),
            memory: getFormattedValue(kube.memory_limit * kubeCount, units.memory, 0),
            traffic: getFormattedValue(kube.traffic_limit * kubeCount, units.traffic),
            hdd: getFormattedValue(kube.hdd_limit * kubeCount, units.hdd),
            ip: publicIp,
            pd: pdTotal ? getFormattedValue(pdTotal, units.hdd, 0) : 0
        }));
        $('#product_id').val(productId);
        $('#priceBlock').html(price);

        if(kubes[productId]['billingType'] == 'Fixed price') {
            $('.start-button').text('Pay and Start your app');
        } else {
            $('.start-button').text('Start your app');
        }
    };

    var initISelect = function() {
        $("input.volume-name").iselect({
            url: '?a=getPersistentDrives'
        });
    };

    $(document).ready(function() {
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

        $('.kube-slider').each(function(k, e) {
            var kubeField = $(this).parent().find('input[id*="KUBE_COUNT"], input[id*="KUBES"]');
            $(this).val(kubeField.val());
            $(this).Link('lower').to(kubeField);
            $(this).Link('lower').to($(this).parent().find('.kube-slider-value'));
        });
        $('.kube-slider').Link('lower').to($('#kube_count'));

        calculateTotal();
        initISelect();
    });

    $(document).on('change', '#kuber_kube_id, #KUBETYPE, #KUBE_TYPE', calculateTotal);
    $(document).on('change', '.kube-slider, input[id*="KUBE_COUNT"], input[id*="KUBES"]', calculateTotal);
    $(document).on('change', 'input[id*="PD_SIZE"], input[type="checkbox"][class="set-persistent"], input.volume-size', calculateTotal);
    $(document).on('change', 'input.is-public', calculateTotal);

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
                '<td class="text-center"><input type="checkbox" value="1" class="is-public" name="Ports[' + k + '][isPublic]"></td>' +
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
                '<td><small>GB</small></td>' +
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
}(_$));