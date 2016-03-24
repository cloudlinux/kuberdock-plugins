define(['app', 'bbcode', 'bootstrap'], function(App) {
    'use strict';

    var Utils = {
        getKube: function (packageId, kubeId) {
            var p = this.getPackage(packageId);

            return _.first(_.filter(p['kubes'], function (e) {
                return e.id == kubeId;
            }));
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
            return response.data ? response.data : response;
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
        }
    };

    return Utils;
});
