define(['backbone', 'application/utils'], function(Backbone, Utils) {
    'use strict';

    var Pod = {};

    Pod.Model = Backbone.Model.extend({
        urlRoot: rootURL + '?request=pods',
        idAttribute: 'name',

        defaults: function () {
            return {};
        },

        parse: function (response) {
            return Utils.parseResponse(response);
        },

        getStatusText: function () {
            if (this.get('status') == 'stopped') {
                return 'Start';
            } else if (this.get('status') == 'unpaid') {
                return 'Pay and Start';
            } else {
                return 'Stop';
            }
        },

        getStatusClass: function () {
            if (this.get('status') == 'stopped') {
                return 'pod-start';
            } else if (this.get('status') == 'unpaid') {
                return 'pod-pay';
            } else {
                return 'pod-stop';
            }
        },

        getButtonClass: function () {
            if (this.get('status') == 'stopped') {
                return 'success';
            } else if (this.get('status') == 'unpaid') {
                return 'success';
            } else {
                return 'danger';
            }
        },

        getIconClass: function () {
            if (this.get('status') == 'stopped') {
                return 'play';
            } else if (this.get('status') == 'unpaid') {
                return 'play';
            } else {
                return 'stop';
            }
        },

        getPublicIp: function () {
            if (this.get('public_ip')) {
                return this.get('public_ip');
            }

            if (this.get('public_aws')) {
                return this.get('public_aws');
            }

            if (this.get('domain')) {
                return this.get('domain');
            }

            return 'none';
        },

        getPodIp: function () {
            return this.get('podIP') ? this.get('podIP') : 'none';
        },

        getStatus: function () {
            return Utils.ucFirst(this.get('status'));
        },

        getKube: function () {
            return Utils.getKube(userPackage.id, this.get('kube_type'));
        },

        getKubes: function () {
            return _.reduce(this.get('containers'), function (s, v) {
                return s + v.kubes;
            }, 0);
        },

        getVolume: function (name) {
            return _.first(_.filter(this.get('volumes'), function (e) {
                return e.name == name;
            }));
        },

        getContainer: function (name) {
            return _.first(_.filter(this.get('containers'), function (e) {
                return e.name == name;
            }));
        },

        getPersistentSize: function () {
            return _.reduce(this.get('volumes'), function (s, v) {
                if (typeof v.persistentDisk == 'undefined') {
                    return 0;
                }
                return s + v.persistentDisk.pdSize || 0;
            }, 0);
        },

        getTotalPrice: function (full) {
            var kube = this.getKube(),
                total = 0;

            total += this.getKubes() * kube.price;
            total += (this.get('public_ip') ? 1 : 0) * userPackage.price_ip;
            total += this.getPersistentSize() * userPackage.price_pstorage;

            if (full) {
                return userPackage.prefix + total.toFixed(2) + ' ' + userPackage.suffix + ' / ' + userPackage.period;
            } else {
                return parseFloat(total.toFixed(2));
            }
        },

        getKubePrice: function (full) {
            var kube = this.getKube();

            if (full) {
                return userPackage.prefix + kube.price + ' ' + userPackage.suffix + ' / ' + userPackage.period;
            } else {
                return kube.price;
            }
        },

        getPostDescription: function () {
            return Utils.processBBCode(this.get('postDescription'));
        },

        getName: function () {
            return encodeURIComponent(this.get('name'));
        }
    });

    Pod.Collection = Backbone.Collection.extend({
        url:  rootURL + '?request=pods',
        model: Pod.Model,

        parse: function (response) {
            return Utils.parseResponse(response);
        }
    });

    Pod.ImageModel = Backbone.Model.extend({
        idAttribute: 'name',
        urlRoot: rootURL + '?request=pods/image',

        defaults: function () {
            return {};
        },

        parse: function (response) {
            return Utils.parseResponse(response);
        }
    });

    Pod.ImageCollection = Backbone.Collection.extend({
        url: rootURL + '?request=pods/search',
        model: Pod.ImageModel,
        page: 1,

        parse: function (response) {
            var self = this;
            _.each(response.data, function(v, k) {
                if(k != 'images') {
                    self[k] = v;
                }
            });

            return response.data.images;
        }
    });

    Pod.PDModel = Backbone.Model.extend({

    });

    Pod.PDCollection = Backbone.Collection.extend({
        url:  rootURL + '?request=persistent_drives',
        model: Pod.PDModel,

        parse: function (response) {
            return Utils.parseResponse(response);
        }
    });

    return Pod;
});
