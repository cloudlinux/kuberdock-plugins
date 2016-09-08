define(['app', 'application/utils',
    'application/pods/model',
    'tpl!application/pods/view/layout_pod.tpl',
    'tpl!application/pods/view/pod_list.tpl',
    'tpl!application/pods/view/pod_list_item.tpl',
    'tpl!application/pods/view/search/pod_search.tpl',
    'tpl!application/pods/view/search/pod_search_item.tpl',
    'tpl!application/pods/view/create/pod_create.tpl',
    'tpl!application/pods/view/create/pod_description.tpl',
    'tpl!application/pods/view/create/pod_port_section.tpl',
    'tpl!application/pods/view/create/pod_env_section.tpl',
    'tpl!application/pods/view/create/pod_volume_section.tpl',
    'tpl!application/pods/view/pod_details.tpl',
    'tpl!application/pods/view/predefined_details.tpl',
    'tpl!application/pods/view/pod_upgrade.tpl',
    'tpl!application/pods/view/search/templates_list.tpl',
    'tpl!application/pods/view/search/templates_list_item.tpl',
    'slider', 'carousel'
], function(App, Utils, Pod, layoutPodTpl, podListTpl, podListItemTpl, podSearchTpl, podSearchItemTpl,
            podCreateTpl, podDescriptionTpl, podPortSectionTpl, podEnvSectionTpl, podVolumeSectionTpl,
            podDetailsTpl, predefinedDetailsTpl, podUpgradeTpl, templatesListTpl, templatesListItemTpl) {
    'use strict';

    var PodView = {};

    // Pods list
    PodView.layout = Backbone.Marionette.LayoutView.extend({
        template: layoutPodTpl,

        regions: {
            content: '#main_content'
        },

        ui: {
            addNewButton: '.add-new-app'
        },

        events: {
            'click @ui.addNewButton': 'searchPod'
        },

        searchPod: function(e) {
            e.preventDefault();
            App.navigate('pod/image/search', {trigger: true});
        }
    });

    PodView.itemList = Backbone.Marionette.ItemView.extend({
        template: podListItemTpl,
        tagName: 'tr',

        initialize: function() {
            this.listenTo(this.model, 'change:command', this.processCommand);
        },

        ui: {
            startButton: '.pod-start',
            payAndStartButton: '.pod-pay',
            stopButton: '.pod-stop',
            editButton: '.pod-edit',
            deleteButton: '.pod-delete',
            podDetails: '.pod-details',
            dropdown: '.kd-dropdown'
        },

        events: {
            'click @ui.startButton': 'startPod',
            'click @ui.payAndStartButton': 'startPod',
            'click @ui.stopButton': 'stopPod',
            'click @ui.editButton': 'editPod',
            'click @ui.deleteButton': 'deletePod',
            'click @ui.podDetails': 'podDetails',
            'click @ui.dropdown': 'dropdown'
        },

        templateHelpers: function() {
            return {
                model: this.model
            };
        },

        startPod: function (e) {
            e.preventDefault();
            this.model.save({command: 'start'});
        },

        stopPod: function (e) {
            e.preventDefault();
            var self = this;

            Utils.modal({
                'text': 'Do you want to stop application?',
                buttons: [
                    {
                        type: 'close'
                    },
                    {
                        class: 'btn btn-primary btn-action',
                        text: 'Stop',
                        event: function() {
                            self.model.save({command: 'stop'});
                        }
                    }
                ]
            });
        },

        editPod: function (e) {
            e.preventDefault();
            this.model.save({command: 'edit'});
        },

        deletePod: function (e) {
            e.preventDefault();
            var self = this;

            Utils.modal({
                'text': 'Do you want to delete application?<br><br>' +
                '<samp>Note: that after you delete application all persistent drives and public IP`s will not be deleted and we`ll' +
                ' charge you for that. Please, go to KuberDock admin panel and delete everything you no longer need.</samp>',
                buttons: [
                    {
                        type: 'close'
                    },
                    {
                        class: 'btn btn-primary btn-action',
                        text: 'Delete',
                        event: function() {
                            self.model.destroy({wait: true}).done(function () {
                                App.navigate('/');
                            });
                        }
                    }
                ]
            });
        },

        podDetails: function (e) {
            e.preventDefault();
            App.navigate('pod/' + encodeURIComponent(this.model.escape('name')), {trigger: true});
        },

        processCommand: function (model, value) {
            var statuses = {
                stop: 'stopped',
                start: 'pending'
            };

            if(statuses[value]) {
                this.model.set('status', statuses[value]);
                this.render();
            }
        },

        // Plesk issue: dropdown become hidden when fired toggle event
        dropdown: function (e) {
            $(e.target).parents('div.dropdown').toggleClass('open');
        }
    });

    PodView.itemListView = Backbone.Marionette.CompositeView.extend({
        template: podListTpl,
        childView: PodView.itemList,
        //emptyView : '',
        childViewContainer  : 'tbody'
    });

    // Images list
    PodView.itemSearch = Backbone.Marionette.ItemView.extend({
        template: podSearchItemTpl,
        tagName: 'tr',

        ui: {
            installButton: '.install-app',
            detailsLink: '.image-more-details'
        },

        events: {
            'click @ui.installButton': 'installApp',
            'click @ui.detailsLink': 'showDetails'
        },

        templateHelpers: function () {
            return {
                index: this.model.collection.indexOf(this.model) + 1,
                stars: this.model.get('star_count') > 10 ? 10 : this.model.get('star_count')
            };
        },

        installApp: function (e) {
            e.preventDefault();
            App.navigate('pod/create/' + this.model.get('name'));
        },

        showDetails: function (e) {
            e.preventDefault();
            $(e.target).parents('tr').find('.info').toggleClass('hidden');
        }
    });

    PodView.itemSearchView = Backbone.Marionette.CompositeView.extend({
        template: podSearchTpl,
        childView: PodView.itemSearch,
        //emptyView : '',
        childViewContainer  : 'tbody',
        image: '',
        page: 1,

        ui: {
            searchButton: 'button.image-search',
            imageInput: 'input#image',
            loadMore: 'div.load-more'
        },

        events: {
            'click @ui.searchButton': 'searchPod',
            'keyup @ui.imageInput': 'pressEnter',
            'click @ui.loadMore': 'loadMore'

        },

        initialize: function () {
            var self = this;
            this.collection.on('add', function (e) {
                self.ui.loadMore.removeClass('hidden');
            });
        },

        searchPod: function(e) {
            e.preventDefault();

            this.page = 1;
            this.image = this.ui.imageInput.val();
            if(!this.image.length) return;

            this.collection.fetch({
                url: this.getUrl()
            });
        },

        pressEnter: function (e) {
            e.preventDefault();

            if(e.which != 13) {
                return false;
            }

            this.searchPod(e);
        },

        loadMore: function (e) {
            e.preventDefault();

            this.page += 1;
            this.collection.fetch({
                url: this.getUrl(),
                remove: false,
                sort: false,
                data: {
                    page: this.page
                }
            });
        },

        getUrl: function () {
            return this.collection.url + '/' + this.image;
        }
    });

    // Pod create
    PodView.createView = Backbone.Marionette.ItemView.extend({
        template: podCreateTpl,

        ui: {
            packageInput: '#package_id',
            kubeSelect: 'select#kube_id',
            kubeCountInput: '#kube_count',
            kubeSlider: '.kube-slider',
            kubeSliderValue: '.kube-slider-value',
            addPortButton: '#add_port',
            addEnvButton: '#add_env',
            addVolumeButton: '#add_volume',
            deletePortButton: '.delete-port',
            deleteEnvButton: '.delete-env',
            deleteVolumeButton: '.delete-volume',
            portTable: '#port_table',
            envTable: '#env_table',
            volumeTable: '#volume_table',
            startButton: '.start-button',
            productDescription: '.product-description',
            priceBlock: '#priceBlock',
            portSection: '.port-section',
            publicCheckbox: 'input.is-public',
            pdCheckbox: 'input.set-persistent',
            setPDCheckbox: 'input.set-persistent',
            pdVolumeSize: 'input.volume-size',
            pdVolumeName: 'input.volume-name',
            createForm: 'form.pod-install',
            tooltip: "[data-toggle='tooltip']"
        },

        events: {
            'change @ui.kubeSlider': 'calculate',
            'change @ui.publicCheckbox': 'calculate',
            'change @ui.kubeSelect': 'calculate',
            'change @ui.pdCheckbox': 'calculate',
            'change @ui.setPDCheckbox': 'allowPdFields',
            'change @ui.pdVolumeSize': 'calculate',
            'click @ui.pdVolumeSize': 'calculate',
            'click @ui.setPDCheckbox': 'calculate',
            'click @ui.pdVolumeName': 'renderDrives',
            'click @ui.addPortButton': 'addPortSection',
            'click @ui.deletePortButton': 'deleteSection',
            'click @ui.addEnvButton': 'addEnvSection',
            'click @ui.deleteEnvButton': 'deleteSection',
            'click @ui.addVolumeButton': 'addVolumeSection',
            'click @ui.deleteVolumeButton': 'deleteSection',
            'click @ui.startButton': 'create'
        },

        initialize: function () {
        },

        serializeData: function() {
            var self = this;

            this.model.set('kubes', Utils.getKubes());

            return {
                model: this.model,
                renderPort: function (port, i) {
                    return self.getPortSection(port, i);
                },
                renderEnv: function (env, i) {
                    return self.getEnvSection(env, i);
                },
                renderVolume: function (volume, i) {
                    return self.getVolumeSection(volume, i);
                }
            };
        },

        onShow: function() {
            this.ui.tooltip.tooltip();
            this.ui.kubeSlider.noUiSlider({
                start: [ 1 ],
                range: {
                    min: [ 1 ],
                    max: [ maxKubes ]
                },
                format: wNumb({
                    decimals: 0,
                    thousand: '.'
                })
            });

            this.ui.kubeSlider.Link().to(this.ui.kubeSliderValue);
            this.ui.kubeSlider.Link().to(this.ui.kubeCountInput);

            this.calculate();
        },

        calculate: function() {
            var kubeId = this.ui.kubeSelect.val(),
                packageId = this.ui.kubeSelect.find(':selected').data('pid'),
                kubeCount = parseInt(this.ui.kubeCountInput.val()),
                pdTotal = this.getTotalPDSize(),
                kube = Utils.getKube(packageId, kubeId),
                product = Utils.getPackage(packageId),
                publicIp = this.getPublicIP();

            var additionalPrice = product.price_ip * publicIp + product.price_pstorage * pdTotal;
            var price = wNumb({
                decimals: 2,
                prefix: product.prefix,
                postfix: product.suffix  + ' / ' + product.period
            }).to(kube.price * kubeCount + additionalPrice);

            this.ui.productDescription.html(podDescriptionTpl({
                cpu: this.getFormatted(kube.cpu * kubeCount, kube.cpu_units),
                memory: this.getFormatted(kube.memory * kubeCount, kube.memory_units, 0),
                traffic: this.getFormatted(kube.included_traffic * kubeCount, kube.disk_space_units),
                hdd: this.getFormatted(kube.disk_space * kubeCount, kube.disk_space_units),
                ip: publicIp,
                pd: pdTotal ? this.getFormatted(pdTotal, kube.disk_space_units, 0) : 0
            }));
            this.ui.packageInput.val(packageId);
            this.ui.priceBlock.html(price);

            if(product['count_type'] == 'fixed') {
                this.ui.startButton.text('Pay and Start your app');
            } else {
                this.ui.startButton.text('Start your app');
            }
        },

        getTotalPDSize: function() {
            var pdTotal = 0;

            _.each(this.ui.pdCheckbox, function(e) {
                if($(e).is(':checked')) {
                    pdTotal += parseInt($(e).parents('tr').find('.volume-size').val()) || 0;
                }
            });

            return pdTotal;
        },

        getPublicIP: function() {
            var publicIp = 0;

            this.ui.publicCheckbox.each(function() {
                if($(this).prop('checked')) {
                    publicIp += 1;
                }
            });

            return publicIp;
        },

        getFormatted: function(value, unit, decimals) {
            decimals = typeof decimals === 'undefined' ? 2 : decimals;

            return wNumb({
                decimals: decimals,
                prefix: '',
                postfix: ' ' + unit
            }).to(value);
        },

        getPortSection: function (data, i) {
            i = typeof data.i == 'undefined' ? i : data.i;
            return podPortSectionTpl({port: data, i: i});
        },

        addPortSection: function (e) {
            var i = this.ui.portTable.find('tr').length - 1;
            this.ui.portTable.append(this.getPortSection({i: i}));
            this.bindUIElements();
        },

        deleteSection: function (e) {
            $(e.target).parents('tr').remove();
        },

        getEnvSection: function (data, i) {
            i = typeof data.i == 'undefined' ? i : data.i;
            return podEnvSectionTpl({env: data, i: i});
        },

        addEnvSection: function (e) {
            var i = this.ui.envTable.find('tr').length - 1;
            this.ui.envTable.append(this.getEnvSection({i: i}));
            this.bindUIElements();
        },

        getVolumeSection: function (data, i) {
            i = typeof data.i == 'undefined' ? i : data.i;
            return podVolumeSectionTpl({volume: data, i: i});
        },

        addVolumeSection: function (e) {
            var i = this.ui.volumeTable.find('tr').length - 1;
            this.ui.volumeTable.append(this.getVolumeSection({i: i}));
            this.bindUIElements();
        },

        allowPdFields: function (e) {
            $(e.target).parents('tr').find('input.volume-name, input.volume-size')
                .prop('disabled', !$(e.target).is(':checked'));
        },

        renderDrives: function (e) {
            e.preventDefault();

            var self = this,
                view,
                pdList = $(e.target).parents('tr').find('.pd-list');

            if (pdList.is(':hidden')) {
                pdList.show();
            } else {
                pdList.hide();
            }

            if (this.PDCollection) {
                view = new PodView.PDView({
                    el: pdList,
                    collection: this.PDCollection
                });
                view.render();
            } else {
                this.PDCollection = new Pod.PDCollection;
                view = new PodView.PDView({
                    el: pdList,
                    collection: this.PDCollection
                });

                $.when(this.PDCollection.fetch()).done(function () {
                    view.render();
                });
            }
        },

        create: function (e) {
            e.preventDefault();
            var self = this;

            Backbone.ajax({
                url: rootURL + '?request=pods',
                method: 'POST',
                data: this.ui.createForm.serialize(),
                dataType: 'json'
            }).done(function (response) {
                App.Controller.pod = new Pod.Model();
                App.Controller.pod.set(response.data);
                if (App.Controller.podCollection) {
                    App.Controller.podCollection.add(App.Controller.pod);
                }
                App.navigate('pod/' + encodeURIComponent(App.Controller.pod.get('name')), {trigger: true});
            });
        }
    });

    PodView.Details = Backbone.Marionette.ItemView.extend({
        description: 0,

        getTemplate: function () {
            if (this.model && this.model.get('template_id')) {
                return predefinedDetailsTpl;
            } else {
                return podDetailsTpl;
            }
        },

        initialize: function(attributes) {
            _.extend(this, attributes);

            this.listenTo(this.model, 'change:command', this.processCommand);
            this.listenTo(this.model, 'sync', this.render);
        },

        ui: {
            startButton: '.pod-start',
            payAndStartButton: '.pod-pay',
            stopButton: '.pod-stop',
            editButton: '.pod-edit',
            deleteButton: '.pod-delete',
            upgradeButton: '.pod-upgrade',
            restartButton: '.pod-restart',
            addMoreAppsButton: '.pod-search',
            backButton: '.back',
            dropdown: '.kd-dropdown'
        },

        events: {
            'click @ui.startButton': 'startPod',
            'click @ui.payAndStartButton': 'startPod',
            'click @ui.stopButton': 'stopPod',
            'click @ui.editButton': 'editPod',
            'click @ui.deleteButton': 'deletePod',
            'click @ui.backButton': 'back',
            'click @ui.upgradeButton': 'upgradePod',
            'click @ui.restartButton': 'restartPod',
            'click @ui.addMoreAppsButton': 'searchImages',
            'click @ui.dropdown': 'dropdown'
        },

        templateHelpers: function () {
            var self = this;

            return {
                model: this.model,
                kube: this.model.getKube(),
                kubes: this.model.getKubes(),
                escape: Utils.escapeHtml,
                showDescription: function () {
                    return self.description;
                }
            };
        },

        startPod: function (e) {
            e.preventDefault();
            this.model.save({command: 'start'});
        },

        stopPod: function (e) {
            e.preventDefault();
            var self = this;

            Utils.modal({
                'text': 'Do you want to stop application?',
                buttons: [
                    {
                        type: 'close'
                    },
                    {
                        class: 'btn btn-primary btn-action',
                        text: 'Stop',
                        event: function() {
                            self.model.save({command: 'stop'});
                        }
                    }
                ]
            });
        },

        editPod: function (e) {
            e.preventDefault();
            this.model.save({command: 'edit'});
        },

        restartPod: function (e) {
            e.preventDefault();
            var self = this;

            Utils.modal({
                'text': 'Do you want to restart application?<br><br>' +
                '<samp>You can wipe out all the data and redeploy the application or you can just ' +
                    'restart and save data in Persistent storages of your application.</samp>',
                buttons: [
                    {
                        class: 'btn btn-primary btn-danger',
                        text: 'Wipe Out',
                        event: function() {
                            self.model.save({command: 'redeploy', commandOptions: {wipeOut: true}});
                        }
                    },
                    {
                        class: 'btn btn-primary btn-action',
                        text: 'Just Restart',
                        event: function() {
                            self.model.save({command: 'redeploy'});
                        }
                    }
                ]
            });
        },

        upgradePod: function (e) {
            e.preventDefault();
            App.navigate('pod/upgrade/' + this.model.get('name'), {trigger: true});
        },

        deletePod: function (e) {
            e.preventDefault();
            var self = this;

            Utils.modal({
                'text': 'Do you want to delete application?<br><br>' +
                '<samp>Note: that after you delete application all persistent drives and public IP`s will not be deleted and we`ll' +
                ' charge you for that. Please, go to KuberDock admin panel and delete everything you no longer need.</samp>',
                buttons: [
                    {
                        type: 'close'
                    },
                    {
                        class: 'btn btn-primary btn-action',
                        text: 'Delete',
                        event: function() {
                            self.model.destroy({wait: true}).done(function () {
                                App.navigate('/');
                            });
                        }
                    }
                ]
            });
        },

        processCommand: function (model, value) {
            var statuses = {
                stop: 'stopped',
                start: 'pending'
            };

            if(statuses[value]) {
                this.model.set('status', statuses[value]);
                this.render();
            }
        },

        back: function (e) {
            e.preventDefault();
            App.navigate('/');
        },

        searchImages: function (e) {
            e.preventDefault();
            App.navigate('pod/image/search', {trigger: true});
        },

        // Plesk issue: dropdown become hidden when fired toggle event
        dropdown: function (e) {
            $(e.target).parents('div.dropdown').toggleClass('open');
        }
    });

    PodView.Upgrade = Backbone.Marionette.ItemView.extend({
        template: podUpgradeTpl,

        initialize: function() {
        },

        ui: {
            upgradeButton: '.pod-upgrade',
            slider: '.slider',
            containerKubes: 'input[name$="_kubes"]',
            resourcesSection: '.resources',
            form: 'form.upgrade-form',
            backButton: '.back'
        },

        events: {
            'click @ui.upgradeButton': 'upgradePod',
            'click @ui.backButton': 'back'
        },

        templateHelpers: function () {
            return {
                model: this.model
            };
        },

        setResources: function () {
            var template = _.template('<span>CPU: <%- cpu %></span>' +
                '<span>HDD: <%- hdd %></span>' +
                '<span>RAM: <%- memory %></span>'),
                kube = this.model.getKube(),
                price = this.model.getTotalPrice();

            var kubes = _.reduce(this.ui.containerKubes, function(s, e) {
                return s + parseInt($(e).val());
            }, 0);

            this.ui.resourcesSection.html(template({
                cpu: (kube.cpu * kubes).toFixed(2) + ' ' + kube.cpu_units,
                hdd: kube.disk_space * kubes  + ' ' + kube.disk_space_units,
                memory: kube.memory * kubes  + ' ' + kube.memory_units
            }));

            $('.new-price').text(wNumb({
                decimals: 2,
                prefix: userPackage.prefix,
                postfix: userPackage.suffix + ' / ' + userPackage.period
            }).to(price + (kubes - this.model.getKubes()) * kube.price));
        },

        onRender: function () {
            var self = this;
            _.each(this.ui.slider, function(slider) {
                var kubes = parseInt($(slider).parents('th').find(self.ui.containerKubes).val());

                $(slider).noUiSlider({
                    animate: true,
                    start: kubes,
                    step: 1,
                    range: {
                        min: 1,
                        max: maxKubes
                    },
                    format: wNumb({
                        decimals: 0
                    })
                });
                $(slider).val(kubes);
                $(slider).Link().to($(slider).parents('th').find(self.ui.containerKubes));
                $(slider).Link().to($(slider).parents('th').find('div.slider-value'));
                $(slider).on('change', function() {
                    self.setResources();
                });
            });

            this.setResources();
        },

        upgradePod: function (e) {
            var formData = this.ui.form.serializeArray(),
                containers = this.model.get('containers'),
                self = this;

            _.each(containers, function (c, i) {
                _.each(formData, function (e) {
                    if (e.name.indexOf(c.name) >= 0) {
                        containers[i].kubes = parseInt(e.value);
                    }
                });
            });

            this.model.save({command: 'upgrade', containers: containers}).done(function () {
                App.navigate('pod/' + encodeURIComponent(self.model.get('name')));
            });
        },

        back: function (e) {
            e.preventDefault();
            App.navigate('pod/' + encodeURIComponent(this.model.get('name')));
        }
    });

    PodView.PDItemView = Backbone.Marionette.ItemView.extend({
        template: _.template('<span><%- name %> (<%- size %>)</span>'),

        ui: {
            drive: 'span'
        },

        triggers: {
            'click @ui.drive': 'select:drive'
        }
    });

    PodView.PDView = Backbone.Marionette.CollectionView.extend({
        childView: PodView.PDItemView,
        //emptyView : '',
        childViewContainer  : 'div',

        childEvents: {
            'select:drive' : 'selectDrive'
        },

        selectDrive: function (e) {
            e.$el.parents('tr').find('input.volume-name').val(e.model.get('name'));
            e.$el.parents('tr').find('input.volume-size').val(e.model.get('size')).trigger('change');
            this.$el.empty().hide();
        },

        onBeforeRender: function () {
            this.$el.empty();
        }
    });

    PodView.TemplatesItemListView = Backbone.Marionette.ItemView.extend({
        template: templatesListItemTpl,
        tagName: 'div',
        className: 'text-center',

        templateHelpers: function () {
            return {
                model: this.model
            };
        }
    });

    PodView.TemplatesListView = Backbone.Marionette.CompositeView.extend({
        el: '#templates',
        template: templatesListTpl,
        childView: PodView.TemplatesItemListView,
        emptyView : '',

        childViewContainer: '#owl_carousel',

        initialize: function () {
            this.listenTo(this.collection, 'sync', this.init);
        },
        
        ui: {
            carousel: '#owl_carousel'
        },
        
        init: function () {
            this.ui.carousel.owlCarousel({items: 5});
        }
    });

    return PodView;
});
