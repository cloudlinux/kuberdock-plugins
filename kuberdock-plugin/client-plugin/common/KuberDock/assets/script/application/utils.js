define(['app', 'bbcode', 'bootstrap'], function(App) {
    'use strict';

    var Utils = {
        getKube: function (packageId, kubeId) {
            var p = this.getPackage(packageId);

            return _.first(_.filter(p['kubes'], function (e) {
                return e.id == kubeId;
            }));
        },

        getKubes: function (sorted) {
            sorted = sorted || 'id';
            var kubes = [];
            var data = _.isEmpty(userPackage) ? packages : [userPackage];

            _.each(data, function(row) {
                _.each(row.kubes, function(kube) {
                    kubes.push({
                        'id' : kube.id,
                        'name' : kube.name,
                        'available' : kube.available,
                        'package_id' : row.id,
                        'package_name' : row.name
                    });
                });
            });

            if (sorted) {
                kubes = _.sortBy(kubes, function (kube) {
                    return kube[sorted];
                });
            }

            return kubes;
        },

        getPackage: function(packageId) {
            if (!_.isEmpty(userPackage)) {
                return userPackage;
            }

            return _.first(_.filter(packages, function (e) {
                return e.id == packageId;
            }));
        },

        parseResponse: function (response) {
            if (response.data) {
                return response.data;
            } else if (response.message) {
                return response.message;
            } else {
                return response;
            }
        },

        ucFirst: function (str) {
            var f = str.charAt(0).toUpperCase();
            return f + str.substr(1);
        },

        processBBCode: function (text) {
            var result = XBBCODE.process({
                text: text,
                removeMisalignedTags: false,
                addInLineBreaks: false
            });

            return result.html;
        },

        escapeHtml: function (str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        modal: function (attributes, options) {
            var window = $('.confirm-modal'),
                header = window.find('.modal-header'),
                footer = window.find('.modal-footer').empty(),
                closeButton = {
                    class: 'btn btn-default',
                    'data-dismiss': 'modal',
                    text: 'Cancel'
                };

            header.html(attributes.text);

            _.each(attributes.buttons, function (e) {
                var attr = _.pick(e, 'class', 'data-dismiss', 'type');
                var button = $('<button>', e.type == 'close' ? closeButton : attr).text(e.text);

                if (e.event) {
                    button.bind('click', function () {
                        $.when(e.event.call()).done(function() {
                            window.modal('toggle');
                        });
                    });
                }
                footer.append(button);
            });

            window.modal(options);
        },

        localStorage: function () {
            return window.localStorage;
        },

        getToken2: function () {
            var storage = this.localStorage(),
                authTime = storage.getItem('authTime'),
                authToken = storage.getItem('authToken'),
                tokenLife = 3600,
                ts = Math.floor(Date.now() / 1000),
                d = $.Deferred();

            if (!authToken || (authTime && Math.abs(authTime - ts) > tokenLife)) {
                Backbone.ajax({
                    url: rootURL + '?request=token2',
                    dataType: 'json'
                }).done(function (response) {
                    storage.setItem('authTime', ts);
                    storage.setItem('authToken', response.data.token2);
                    d.resolve(response.data.token2);
                });
            } else {
                d.resolve(authToken);
            }

            return d.promise();
        }
    };

    return Utils;
});
