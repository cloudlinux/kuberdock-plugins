$(function() {
    if(location.hash) {
        $('a[href="' + location.hash + '"]').tab('show');
    }
});

var resDelete = function(obj) {
    var owner = obj.getAttribute('owner');

    window.location.href = window.location.protocol + '//' +  window.location.host +  window.location.pathname
        + '?a=deleteReseller&o=' + owner;

    return false;
};