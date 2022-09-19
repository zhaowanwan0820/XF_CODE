/*
placeholder 
mabaoyue 2013-09-02
*/

;
(function($) {
    $.fn.placeholder = function(options) {
        if ("placeholder" in document.createElement("input")) {
            return;
        };
        var settings = {
            color: "rgb(169,169,169)",
            name: "original-font-color"
        };

        return this.each(function() {
            var settings = $.extend({}, settings, options),
                color = settings.color,
                name = settings.name;
            var getContent = function(element) {
                return $(element).val();
            }

            var setContent = function(element, content) {
                $(element).val(content);
            }

            var getPlaceholder = function(element) {
                return $(element).attr("placeholder");
            }

            var isContentEmpty = function(element) {
                var content = getContent(element);
                return (content.length === 0) || content == getPlaceholder(element);
            }

            var setPlaceholderStyle = function(element) {
                $(element).data(name, $(element).css("color"));
                $(element).css("color", color);
            }

            var clearPlaceholderStyle = function(element) {
                $(element).css("color", $(element).data(name));
                $(element).removeData(name);
            }

            var showPlaceholder = function(element) {
                setContent(element, getPlaceholder(element));
                setPlaceholderStyle(element);
            }

            var hidePlaceholder = function(element) {
                if ($(element).data(name)) {
                    setContent(element, "");
                    clearPlaceholderStyle(element);
                }
            }

            // -- Event Handlers --
            var inputFocused = function() {
                if (isContentEmpty(this)) {
                    hidePlaceholder(this);
                }
            }

            var inputBlurred = function() {
                if (isContentEmpty(this)) {
                    showPlaceholder(this);
                }
            }

            var parentFormSubmitted = function() {
                if (isContentEmpty(this)) {
                    hidePlaceholder(this);
                }
            }
            var $t = $(this);
            // -- Bind event to components --

            if ($t.attr("placeholder")) {
                $t.focus(inputFocused);
                $t.blur(inputBlurred);
                $t.bind("parentformsubmitted", parentFormSubmitted);

                // triggers show place holder on page load
                $t.trigger("blur");
                // triggers form submitted event on parent form submit
                $t.parents("form").submit(function() {
                    $t.trigger("parentformsubmitted");
                });
            }
        });
    }
})(jQuery);