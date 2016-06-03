$(function() {
    var kd_wrapper = $('#kuberdock_apps .cellbox-body');

    var my_apps = kd_wrapper.find('.item').filter(function() {
        return $(this).find('a.link').text() === 'My Apps';
    }).remove();

    var more_apps = kd_wrapper.find('.item').filter(function() {
        return $(this).find('a.link').text() === 'More Apps';
    }).remove();

    kd_wrapper.find('.item').sort(function(a, b){
        var an = $(a).find('a.link').text();
        var bn = $(b).find('a.link').text();
        if (an && bn) {
            return an.toUpperCase().localeCompare(bn.toUpperCase());
        }
        return 0;
    }).appendTo(kd_wrapper);

    kd_wrapper.append(my_apps).append(more_apps);
});