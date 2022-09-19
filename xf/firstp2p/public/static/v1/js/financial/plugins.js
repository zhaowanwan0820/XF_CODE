(function($) {

  $(function() {
    $('a[rel=modal]').click(function(e) {
      e.preventDefault();
      var modalId = $(this).attr('data-modal');
      $('#' + modalId).revealModal();
    });
  })

  $.fn.revealModal = function() {
    var $modal = $(this);
    $modal.css({
      left: ($(window).width() - $modal.width()) / 2,
      top: ($(window).height() - $modal.height()) / 2 + $(window).scrollTop()
    });
    $modal.show();
    $modal.find('a[rel=cancel]').click(function(e) {
      $(this).hideModal(e);
    });

    $modal.find('a[rel=modal-step-next]').click(function(e) {
      $(this).hideModal(e);
      var modalId = $(this).attr('data-modal');
      $('#' + modalId).revealModal();
    });

    var $mask = $('<div class="modal-mask"></div>').css({
      width: $(window).width(),
      height: $(document).height()
    });
    $mask.insertBefore($modal);

    $(window).resize(function() {
      $modal.css({
        left: ($(window).width() - $modal.width()) / 2,
        top: ($(window).height() - $modal.height()) / 2 + $(window).scrollTop()
      });
      $mask.css({
        width: $(window).width(),
        height: $(document).height()
      });
    });
  }

  $.fn.hideModal = function(e) {
    e.preventDefault();
    $('.modal').hide();
    $('.modal-mask').hide();
  }
})(jQuery);