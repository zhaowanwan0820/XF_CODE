$(function() {
   applyInlineLabels();
});

var applyInlineLabels = function() {
  var psuedoPlaceholder = function(input) {
    if (input.attr('type') === 'password') {
      var clear_input = "<input type=\"text\" class=\"password-clear\" value=\"" + (input.data('placeholder')) + "\" />";
      input.hide();
      input.before(clear_input);
      input.prev('.password-clear').focus(function() {
        $(this).hide();
        input.show();
        return input.focus();
      });
      return input.blur(function() {
        if (input.val() === '') {
          input.hide();
          return input.prev('.password-clear').show();
        }
      });
    } else {
      return input.focus(function() {
        input.removeClass('placeholder');
        if (input.val() === input.data('placeholder')) {
          return input.val('');
        }
      }).blur(function() {
        input.addClass('placeholder');
        if (input.val() === '') {
          return input.val(input.data('placeholder'));
        }
      }).blur();
    }
  };
  return $('input[type=text], input[type=password]').each(function() {
    var label, label_text;
    label = $(this).prev('label');
    label_text = label.text();
    if (('placeholder' in document.createElement('input'))) {
      $(this).attr('placeholder', label_text);
      return label.remove();
    } else {
      $(this).data('placeholder', label_text);
      psuedoPlaceholder($(this));
      return label.remove();
    }
  });
};