(function($) {
	var winW = $(window).width(),
    winH = $(window).height(),
    $loading = $('.loading'),
    $arrow = $('#arrow'),
    $videoBox = $('.p_our .video'),
    videoW = winW * 0.9,
    videoH = winW * 0.9 * 0.78;
  var videoHtml = '<iframe frameborder="0" width="' + videoW  + '" height="' + videoH + '" src="https://v.qq.com/iframe/player.html?vid=h0530eybcz0&tiny=0&auto=0" allowfullscreen></iframe>';
  $videoBox.width(videoW).height(videoH);
  // 预加载
  function loadSource(hash, callback) {
    var totalLen = imgHash.length,
      doneLen = 0,
      per = 0,
      $loadingPer = $('.loading .per');

    for (var i = 0; i < imgHash.length; i++) {
      (function() {
        var img = new Image();
        img.src = imgPrex + imgHash[i];
        img.onload = function() {
          doneLen++;
          per = parseInt(doneLen / totalLen * 100);
          $loadingPer.html(per + '%');
          if (doneLen >= totalLen) callback();
        }
        img.onerror = function() {
          doneLen++;
          per = parseInt(doneLen / totalLen * 100);
          $loadingPer.html(per + '%');
          if (doneLen >= totalLen) callback();
        }
      })();
    }
  }

  loadSource(imgHash, function(){
  	$loading.addClass('loading_hide');
    if(winW / winH >= 0.68) {
      $('body').addClass('b_hack')
    } else {
      $('body').removeClass('b_hack')
    }
  	run();
  });

  function run() {
    window.mySwiper = null;
    $('.swiper-wrapper').height(winH);
    setTimeout(function() {
    	$loading.remove();
      mySwiper = new Swiper('.swiper-container', {
        slideActiveClass: 'active',
        nextButton: '#arrow',
        direction: 'vertical',

        // autoplay: 1000,
        onInit: function(swiper) {
          init();
        },
        onTouchEnd: function(swiper) {
        },
        onSlideChangeStart: function(swiper) {
          if(swiper.activeIndex != 5) {
            $('.p_our .video').html('');
          } else {
            $('.p_our .video').html(videoHtml);
          }
        },
        onSlideChangeEnd: function(swiper) {
          if(swiper.activeIndex == 9) {
            $arrow.hide();
          } else {
            $arrow.show();
          }
        }
      });
    }, 1000);

    // init
    function init() {
    }
  }
})(Zepto);