jQuery(function () {
    jQuery(document).on('click', '.btn_delete', function(e) {
        e.preventDefault();
        var self = jQuery(this);
        jQuery.ajax({
            url: self.attr('href'),
            type: 'POST',
            data: {
                'id': self.data('id'),
                'name': self.data('name')
            },
            dataType: 'json'
        }).done(function(data) {
            document.location.href = data.redirect;
        });
    });
});
