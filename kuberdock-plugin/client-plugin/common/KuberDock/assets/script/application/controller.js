define([
    'app', 'application/pods/views',
    'application/pods/model',
    'application/predefined/views',
    'application/predefined/model'
], function(App, Views, Pod, PredefinedViews, Predefined) {
    'use strict';

    return Backbone.Marionette.Object.extend({
        initialize: function() {
            this.layout = new Views.layout();
            App.contents.show(this.layout);
        },

        podList: function() {
            var view;

            if (this.podCollection) {
                view = new Views.itemListView({
                    collection: this.podCollection
                });
                this.layout.showChildView('content', view);
            } else {
                var self = this;
                this.podCollection = new Pod.Collection;
                view = new Views.itemListView({
                    collection: this.podCollection
                });

                $.when(this.podCollection.fetch()).done(function() {
                    self.layout.showChildView('content', view);
                });
            }
        },

        podDetails: function (name, description) {
            var view,
                self = this;

            if (_.isEmpty(userPackage)) {
                $.ajax({
                    async: false,
                    url: rootURL + '?request=user/package',
                    dataType: 'json'
                }).done(function (response) {
                    userPackage = response.data;
                });
            }

            if (this.podCollection && this.podCollection.get(name)) {
                view = new Views.Details({
                    model: this.podCollection.get(name),
                    description: description
                });
                this.layout.showChildView('content', view);
            } else {
                this.podCollection = new Pod.Collection;

                $.when(this.podCollection.fetch()).done(function() {
                    view = new Views.Details({
                        model: self.podCollection.get(name),
                        description: description
                    });
                    self.layout.showChildView('content', view);
                });
            }
        },

        podSearch: function() {
            var view = new Views.itemSearchView({
                collection: new Pod.ImageCollection
            });
            this.layout.showChildView('content', view);
            var templateCollection = new Predefined.TemplateCollection;
            var templateView = new Views.TemplatesListView({
                collection: templateCollection
            });
            templateView.render();
            templateCollection.fetch();
        },

        podCreate: function(name1, name2) {
            var self = this;
            var model = new Pod.ImageModel({
                name: name1 + ((name2) ? '/' + name2 : '')  // TODO: fix it
            });

            var view = new Views.createView({
                model: model
            });

            $.when(model.fetch({silent: true})).done(function() {
                self.layout.showChildView('content', view);
            });
        },

        podUpgrade: function (name) {
            var view;

            if (this.podCollection && this.podCollection.get(name)) {
                view = new Views.Upgrade({
                    model: this.podCollection.get(name)
                });
                this.layout.showChildView('content', view);
            } else {
                this.podCollection = new Pod.Collection;
                var self = this;

                $.when(this.podCollection.fetch()).done(function() {
                    view = new Views.Upgrade({
                        model: self.podCollection.get(name)
                    });
                    self.layout.showChildView('content', view);
                });
            }
        },

        processPredefined: function(id) {
            var self = this,
                view;

            this.predefinedCollection = new Predefined.Collection(null, {
                template_id: id
            });
            this.predefinedCollection.fetch({async:false});

            if (this.predefinedCollection.length == 0) {
                App.navigate('predefined/new/' + id, {trigger: true});
            } else if (this.predefinedCollection.length == 1) {
                App.navigate('pod/' + encodeURIComponent(this.predefinedCollection.at(0).get('name')), {trigger: true});
            } else {
                view = new Views.itemListView({
                    collection: this.predefinedCollection
                });
                this.layout.showChildView('content', view);
            }
        },

        predefinedCreate: function (id) {
            var self = this;

            this.templateModel = new Predefined.TemplateModel({
                id: id
            });
            var view = new PredefinedViews.planSelect({
                model: this.templateModel
            });

            $.when(this.templateModel.fetch({silent: true})).done(function () {
                self.layout.showChildView('content', view);
            });
        },

        predefinedSetup: function (id, plan) {
            var self = this;
            var model = new Predefined.TemplateVariablesModel({
                id: id,
                plan: plan
            });
            var view = new PredefinedViews.Setup({
                model: model
            });

            if (!this.templateModel) {
                var templateModel = new Predefined.TemplateModel({
                    id: id
                });
                $.when(templateModel.fetch({silent: true}), model.fetch({silent: true})).done(function () {
                    view.templateModel = templateModel;
                    self.layout.showChildView('content', view);
                });
            } else {
                view.templateModel = this.templateModel;
                $.when(model.fetch({silent: true})).done(function () {
                    self.layout.showChildView('content', view);
                });
            }
        },

        predefinedChangePlan: function (name) {
            if (this.podCollection && this.podCollection.get(name) && this.templateModel && this.isVolumeResizableModel) {
                this.predefinedChangePlanSetView(name);
            } else {
                this.podCollection = new Pod.Collection;
                var self = this;

                $.when(this.podCollection.fetch()).done(function () {
                    var pod = self.podCollection.get(name);
                    self.templateModel = new Predefined.TemplateModel({
                        id: pod.get('template_id')
                    });
                    self.isVolumeResizableModel = new Predefined.IsVolumeResizableModel();

                    $.when(
                        self.templateModel.fetch({silent: true}),
                        self.isVolumeResizableModel.fetch({silent:true})
                    ).done(function () {
                        self.predefinedChangePlanSetView(name);
                    });
                });
            }
        },

        predefinedChangePlanSetView: function (name) {
            var view,
                pod = this.podCollection.get(name);

            pod.set('templateModel', this.templateModel);
            pod.set('isVolumeResizableModel', this.isVolumeResizableModel);
            view = new Views.ChangePlan({
                model: pod
            });
            this.layout.showChildView('content', view);
        }
    });
});