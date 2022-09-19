(function($){
     var today = new Date();
     var expireDay = new Date();
     var msPerWeek = 24 * 60 * 60 * 1000 * 7;

     expireDay.setTime(today.getTime() + msPerWeek);

     function setCookie(Key, Value) {
         document.cookie = Key + "=" + Value + ";expires=" + expireDay.toGMTString();
     }

     function getCookie(Key) {
         var search = Key + "=";
         begin = document.cookie.indexOf(search);
         if (begin != -1) {
             begin += search.length;
             end = document.cookie.indexOf(";", begin);
             if (end == -1) end = document.cookie.length;
             return document.cookie.substring(begin, end);
         }
     }


     $(function() {
         var remeber = getCookie('PHPREMEMBER');
         var username = getCookie('username');
         var usertype = getCookie('PHPUSERTYPE');
        
         if (remeber == 'true') {
             $('#user').val(username);
             $('input[name="remember_name"][type="checkbox"]').attr('checked', true);
         } 
         
         $("#loginForm").submit(function(){
               var $user = $("#user"),
               $pass = $("#input-password"),
               $code = $("#input-captcha"),
               $error = $('#error-row');
               var username1 = $.trim($user.val());
               var pass = $pass.val();
               //var code = $.trim($code.val());
               if (!username1 || username1 == '用户名') {
                   $user.focus();
                   $error.html('请输入用户名');
                   return false;
               }
               if (!pass) {
                   $pass.focus();
                   $error.html('请输入密码');
                   return false;
               }
               if($code.length >0 && !$.trim($code.val())){
                   $code.focus();
                   $error.html('请输入验证码');
                   return false;
               }
               

         });


     });
})(jQuery);