(function ($) {
    var settings = {};

    var methods = {
        init: function (options) {
            settings = $.extend({
                'data': {},
                'emptyLabelText': "<i>(noname)</i>",
                'multiselect': true,
                'name': '',
                'selectedItems': [],
                'ignoreItems': [],
                'showSelected': true,
                'height': 'auto',
                'onlyType': false,
                'rootLabelText': "The root of the site",
                'disableRoot': false,
                'disableSections': false,
            }, options);
            return this.each(make);
        },
        showOnlyType: function (typeId) {
            settings.onlyType = parseInt(typeId);
            methods.render.apply(this, arguments);
        },
        render: function () {
            $(this).html(renderTreeRecursive(settings.data));
            if (settings.showSelected) {
                $(this)
                    .find('input:checked')
                    .each(function () {
                        $(this).parents('ul.ctreeview-child').each(function () {
                            $(this).show();
                            $(this).parent().find('> span.glyphicon-chevron-right').hide();
                            $(this).parent().find('> span.glyphicon-chevron-down').show();
                        });
                    });
            }
        },
    };

    $.fn.categoryTreeView = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        }
        $.error('Method ' + method + ' not exist!');
    };

    function renderTreeRecursive(dataList, parentId) {
        parentId = parentId || 0;
        var items = '';
        $.each(dataList, function (key, value) {
            if (
                (settings.onlyType === false || settings.onlyType === value.type)
                && $.inArray(value.id, settings.ignoreItems) === -1
            ) {
                var childs = renderTreeRecursive(value.childs, value.id);
                var hasNoChilds = childs.length === 0;
                var isChecked = $.inArray(value.id, settings.selectedItems) > -1;

                if (hasNoChilds && settings.disableSections && value.is_section === 1) {
                    return;
                }

                items += "<li data-id=\"" + value.id + "\" data-type=\"" + value.type + "\">\n";
                items += "<span data-enable=\"" + hasNoChilds + "\" class=\"ctreeview-nochevron\" style=\"display: " + (hasNoChilds ? 'inline' : 'none') + ";\"></span>";
                items += "<span data-enable=\"" + !hasNoChilds + "\" class=\"glyphicon glyphicon-chevron-right\" style=\"display: " + (hasNoChilds ? 'none' : 'inline') + ";\"></span>";
                items += "<span data-enable=\"" + !hasNoChilds + "\" class=\"glyphicon glyphicon-chevron-down\"></span>"; // it's hidden by default in css
                items += "<label class=\"ctreeview-item-label\">";
                items += "<input type=\"" + (settings.multiselect ? "checkbox" : "radio") + "\" name=\"" + settings.name + "\" value=\"" + value.id + "\"" + (isChecked ? ' checked' : '') + (settings.disableSections && value.is_section === 1 ? " class=\"disabled\" disabled" : "") + ">";
                items += (value.name.length ? value.name : settings.emptyLabelText) + "</label>\n";
                items += childs;
                items += "</li>\n";
            }
        });

        if (parentId === 0) {
            var rootDisabled = settings.disableRoot;
            if (!rootDisabled && settings.onlyType !== false) {
                $.each(dataList, function (key, value) {
                    if (settings.onlyType === value.type && $.inArray(value.id, settings.ignoreItems) === -1) {
                        if (value.is_section === 1) {
                            rootDisabled = true;
                            return true;
                        }
                    }
                });
            }

            var content = "";
            content += "<div class=\"ctreeview\" style=\"max-height: " + settings.height + ";\">\n";
            content += "<ul>\n";
            content += "<li data-id=\"0\">\n";
            content += "<label class=\"ctreeview-item-label\">";
            content += "<input type=\"" + (settings.multiselect ? "checkbox" : "radio") + "\" name=\"" + settings.name + "\" value=\"0\"" + (!rootDisabled && $.inArray(0, settings.selectedItems) > -1 ? ' checked' : '') + (rootDisabled ? " class=\"disabled\" disabled" : "") + ">";
            content += settings.rootLabelText;
            content += "</label>\n";
            content += "</li>\n";
            content += "<li>\n";
            content += "<ul>\n" + items + "</ul>\n";
            content += "</li>\n";
            content += "</ul>\n";
            content += "</div>\n";
            return content;
        }
        return items.length === 0 ? '' : "<ul class=\"ctreeview-child\">\n" + items + "</ul>\n";
    }

    var make = function () {
        $(this).addClass('panel panel-default panel-body');

        $(this).on('click', 'span.glyphicon-chevron-right', function (e) {
            $(this).hide();
            $(this).parent().find('> span.glyphicon-chevron-down').show();
            $(this).parent().find('> ul.ctreeview-child').slideDown();
        });

        $(this).on('click', 'span.glyphicon-chevron-down', function (e) {
            $(this).hide();
            $(this).parent().find('> span.glyphicon-chevron-down').show();
            $(this).parent().find('ul.ctreeview-child').slideUp(function () {
                $(this).parent().find('span.glyphicon-chevron-down').each(function () {
                    if ($(this).attr('data-enable') === 'true') {
                        $(this).hide();
                    }
                });
                $(this).parent().find('span.glyphicon-chevron-right').each(function () {
                    if ($(this).attr('data-enable') === 'true') {
                        $(this).show();
                    }
                });
            });
        });

        methods.render.apply(this, arguments);
    };
})(jQuery);
