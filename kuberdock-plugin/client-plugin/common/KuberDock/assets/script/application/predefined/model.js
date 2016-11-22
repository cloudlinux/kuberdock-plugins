define(['backbone', 'application/utils', 'application/pods/model'], function (Backbone, Utils, Pod) {
    'use strict';

    var Predefined = {};

    Predefined.Model = Backbone.Model.extend({
        url: function () {
            return rootURL + '?request=predefined/' + this.get('id');
        },

        parse: function (response) {
            return Utils.parseResponse(response);
        }
    });

    Predefined.Collection = Backbone.Collection.extend({
        model: Pod.Model,
        template_id: null,

        url: function () {
            return rootURL + '?request=predefined/' + this.template_id;
        },

        initialize: function (models, data) {
            var self = this;
            _.each(_.keys(data), function (k) {
                self[k] = data[k];
            });
        },

        parse: function (response) {
            return Utils.parseResponse(response);
        }
    });

    Predefined.TemplateModel = Backbone.Model.extend({
        urlRoot: rootURL + '?request=templates',

        defaults: function () {
            return {};
        },

        parse: function (response) {
            return Utils.parseResponse(response);
        },

        setCurrentPlan: function (plan) {
            this.set('currentPlan', plan);
        },

        getKDSection: function () {
            if (this.has('template')) {
                return this.get('template').kuberdock || {};
            } else {
                return this.get('kuberdock') || {};
            }

        },

        getName: function () {
            return this.getKDSection().name;
        },

        getPreDescription: function () {
            return Utils.processBBCode(this.getKDSection().preDescription);
        },

        getPlan: function (key) {
            return this.get('plans')[key] || {};
        },

        getKubes: function (planKey) {
            var plan = this.getPlan(planKey),
                kubes = 0;

            _.each(plan.pods, function (p) {
                kubes += _.reduce(p.containers, function(s, c) {
                    return s += c.kubes;
                }, 0);
            });

            return kubes;
        },

        getPublicIP: function (planKey) {
            var plan = this.getPlan(planKey),
                containerPublic = false,
                planPublic = false;

            if(plan) {
                planPublic = plan.getPublicIP || false;
            }

            _.each(this.getContainers() , function (c) {
                _.each(c.ports, function (p) {
                    if (!p.isPublic) return;
                    containerPublic = p.isPublic;
                });
            });

            return containerPublic && !planPublic ? containerPublic : planPublic;
        },

        hasBaseDomain: function(planKey) {
            return this.getPlan(planKey).baseDomain !== undefined;
        },

        getPersistentSize: function (planKey) {
            var plan = this.has('currentPlan')
                ? this.get('currentPlan')
                : this.getPlan(planKey);

            var size = _.reduce(this.getVolumes(), function (s, v) {
                if (typeof v.persistentDisk == 'undefined') {
                    return 0;
                }

                return s + v.persistentDisk.pdSize || 0;
            }, 0);

            _.each(plan.pods, function(p) {
                size += _.reduce(p.persistentDisks, function (s, v) {
                    return s + v.pdSize;
                }, 0);
            });

            return size;
        },

        getKube: function (planKey) {
            var plan = this.getPlan(planKey);
            // TODO: fix it for few pods
            var kubeType = plan.pods[0].kubeType === undefined
                ? packageDefaults.kubeType : plan.pods[0].kubeType;

            return Utils.getKube(this.getPackageId(), kubeType);
        },

        getTotalPrice: function (planKey) {
            var p = this.getPackage(),
                kube = this.getKube(planKey),
                total = 0;

            total += this.getKubes(planKey) * kube.price;
            total += this.getPersistentSize(planKey) * p.price_pstorage;

            if (!this.hasBaseDomain(planKey)) {
                total += (this.getPublicIP(planKey) ? 1 : 0) * p.price_ip;
            }

            return total.toFixed(2);
        },

        getContainers: function () {
            return this.get('spec').template.spec.containers;
        },

        getVolumes: function () {
            return this.get('spec').template.spec.volumes;
        },

        getPackageId: function () {
            var packageId = this.getKDSection().packageID;
            return packageId === undefined ? packageDefaults.packageId : packageId;
        },

        getPackage: function () {
            return Utils.getPackage(this.getPackageId());
        },

        getIcon: function () {
            return this.getKDSection().icon ? this.getKDSection().icon : assetsURL + 'images/default_transparent.png';
        }
    });

    Predefined.IsVolumeResizableModel = Backbone.Model.extend({
        urlRoot: rootURL + '?request=is_volume_resizable',

        defaults: function () {
            return {};
        },

        parse: function (response) {
            return Utils.parseResponse(response);
        }
    });

    Predefined.TemplateVariablesModel = Backbone.Model.extend({
        urlRoot: rootURL + '?request=templates/setup',

        defaults: function () {
            return {};
        },

        parse: function (response) {
            return Utils.parseResponse(response);
        }
    });

    Predefined.TemplateCollection = Backbone.Collection.extend({
        url: rootURL + '?request=templates',
        model: Predefined.TemplateModel,

        parse: function (response) {
            return Utils.parseResponse(response);
        }
    });

    return Predefined;
});