define(['app', 'application/utils', 'application/predefined/model', 'application/pods/model',
    'tpl!application/predefined/view/choose_plan.tpl',
    'tpl!application/predefined/view/plan_description.tpl',
    'tpl!application/predefined/view/setup.tpl',
    'tpl!application/predefined/view/fields/autogen.tpl',
    'tpl!application/predefined/view/fields/input.tpl',
    'tpl!application/predefined/view/fields/kube_count.tpl',
    'tpl!application/predefined/view/fields/kube_type.tpl',
    'tpl!application/predefined/view/fields/select.tpl',
    'tpl!application/predefined/view/fields/user_domain_list.tpl'
], function(App, Utils, Predefined, Pod, paChoosePlanTpl, paPlanDescriptionTpl, paSetupTpl, autogenFieldTpl, inputFieldTpl,
            kubeCountFieldTpl, kubeTypeFieldTpl, selectFieldTpl, userDomainsFieldTpl) {
    'use strict';

    var Template = {};

    Template.planSelect = Backbone.Marionette.ItemView.extend({
        template: paChoosePlanTpl,

        initialize: function () {
        },

        ui: {
            showDetails: '.show-details',
            selectPlan: '.select-plan'
        },

        events: {
            'click @ui.showDetails': 'showDetails',
            'click @ui.selectPlan': 'selectPlan'
        },

        templateHelpers: function () {
            return {
                model: this.model,
                planDescription: this.planDescription,
            };
        },

        planDescription: function (planKey) {
            return paPlanDescriptionTpl({
                kube: this.model.getKube(planKey),
                kubes: this.model.getKubes(planKey),
                publicIP: this.model.getPublicIP(planKey),
                hasBaseDomain: this.model.hasBaseDomain(planKey),
                pdSize: this.model.getPersistentSize(planKey)
            });
        },

        showDetails: function (e) {
            e.preventDefault();
            $(e.target).parent().find('.product-description').toggleClass('hidden');
        },

        selectPlan: function (e) {
            e.preventDefault();
            App.navigate('predefined/new/' + this.model.get('id') +'/' + $(e.target).data('plan'), {trigger: true});
        }
    });

    Template.Setup = Backbone.Marionette.ItemView.extend({
        template: paSetupTpl,

        initialize: function () {
        },

        ui: {
            startButton: '.start-button',
            selectPlan: '.select-plan',
            appForm: 'form.app-install'
        },

        events: {
            'click @ui.startButton': 'startApp',
            'click @ui.selectPlan': 'selectPlan'
        },

        templateHelpers: function () {
            return {
                templateModel: this.templateModel,
                kube: this.templateModel.getKube(this.model.get('plan')),
                model: this.model,
                planPackage: this.templateModel.getPackage(),
                renderField: this.renderField
            };
        },

        startApp: function (e) {
            e.preventDefault();

            var formData = this.ui.appForm.serializeArray(),
                self = this,
                model = new Predefined.Model();

            _.map(formData, function (v, k) {
                model.set(v.name, v.value);
            });
            model.save({
                id: this.model.get('id')
            }).done(function (response) {
                App.Controller.pod = new Pod.Model();
                App.Controller.pod.set(response.data);
                if (App.Controller.podCollection) {
                    App.Controller.podCollection.add(App.Controller.pod);
                }
                App.navigate('pod/' + encodeURIComponent(model.get('name')) + '/1', {trigger: true});
            });
        },

        selectPlan: function (e) {
            e.preventDefault();
            App.navigate('predefined/' + this.model.get('id'), {trigger: true});
        },

        renderField: function (variable) {
            var data = this.model.get(variable);
            var templates = {
                autogen: autogenFieldTpl,
                input: inputFieldTpl,
                kube_count: kubeCountFieldTpl,
                kube_type: kubeTypeFieldTpl,
                select: selectFieldTpl,
                user_domain_list: userDomainsFieldTpl
            };

            if (!_.isObject(data)) return;

            return templates[data.type](_.extend({variable: variable}, data));
        },

        onRender: function () {
            if (this.templateModel.getPackage().count_type == 'fixed') {
                this.$el.find(this.ui.startButton).text('Pay and Start your App');
            }
        }
    });

    return Template;
});