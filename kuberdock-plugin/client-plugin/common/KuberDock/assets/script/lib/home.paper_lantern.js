document.addEventListener("DOMContentLoaded", function() {
    require(["frameworksBuild"], function() {
        require(["jquery"], function($) {
            $.each(PAGE.appGroups, function(index, group){
                if (group.group=="kuberdock_apps") {
                    var items = group.items;
                    var my_apps;
                    var more_apps;

                    $.each(items, function(num, item){
                        if (typeof item!='undefined' && item.itemdesc=='My Apps') {
                            my_apps = item;
                            items.splice(num, 1);
                        }
                        if (typeof item!='undefined' && item.itemdesc=='More Apps') {
                            more_apps = item;
                            items.splice(num, 1);
                        }
                    });

                    items.sort(function(a, b){
                        var an = a.itemdesc;
                        var bn = b.itemdesc;
                        if (an && bn) {
                            return an.toUpperCase().localeCompare(bn.toUpperCase());
                        }
                        return 0;
                    })

                    items.push(my_apps);
                    items.push(more_apps);
                }
            });
        });
    });
});