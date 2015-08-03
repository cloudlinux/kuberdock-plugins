var resDelete = function(obj) {
    var owner = obj.getAttribute('owner');

    window.location.href = window.location.protocol + '//' +  window.location.host +  window.location.pathname
        + '?a=delete&o=' + owner;

    return false;
};