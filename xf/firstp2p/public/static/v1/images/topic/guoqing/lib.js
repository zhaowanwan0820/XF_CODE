//globalObj 为全局公用变量 主要存放公用的前后台交互的变量
if(typeof(globalObj) == "undefined"){
     globalObj = {};
}

      

/*
创建全局命名空间变量 X，主要包括了一些表单处理的方法

 */

var X = {
    ajax: function(obj) {
        var online = obj.online || true,
            data = obj.data || "测试ajax离线";
        if ( !! online) {
            $.ajax({
                url: obj.url,
                dataType: obj.dataType || "json",
                type: obj.type || 'post',
                data: data ,
                success: function(data) { 
                    !! obj.fn && obj.fn(data);
                },
                error: function(e) { 
                    !! obj.errorFn && obj.errorFn();
                    return;
                }
            });
        } else { 
            !! obj.testFn && obj.testFn(data);
        }
    },
    ajaxPost : function($obj, options){
          var   $t = $obj,
               settings = {
                   url : $t.data("url"),
                   data : {
                         cid: $t.data("cid"),
                         proid: $t.data("proid")
                    },
                   type : "post",
                   sFn : function(json , $t){
                        if(json.status == 1){
                               X.locationReload(null , 1500);
                         }
                          newAlert({
                           msg: json.msg,
                               fn: function() {

                                 }
                        });
                   }
            };
          $.extend(settings , options);
          X.ajax({
              url: settings.url,
              data: settings.data,
              type : settings.type,
              fn: function(json) {
                  settings.sFn(json , $t);
              }

          });
          
    },
    ajaxDel : function($obj , options){
        var settings = {
             url : $obj.attr("action"),
             data : $obj.serialize(),
             type : "post",
             msg : "确定要删除吗？删除以后不能恢复呢",
             callback : function(data){
                  alert(data.msg);
                  if(data.data && data.data.url){
                        X.locationReload(data.data.url);
                  }
                  X.locationReload(null ,500);
                  
             }
        };
        $.extend(settings , options);
        newComfirm({
            msg: settings.msg,
            fn: function(data) {
                if (data.btn == "yes") {
                    var $f = $obj;
                    X.ajax({
                        url : settings.url,
                        type : settings.type,
                        data : settings.data,
                        fn : function(data){
                               !!settings.callback && settings.callback(data);
                        }
                    });


                }
            }
        });
    },
    locationReload : function(url,time){
        var time = time || 1500;
        if(!url){
              setTimeout("location.reload()",time);
        }else{
            setTimeout(function(){
               location.replace(url);
            },time);
        }
        
    },
    formPost : function($form , option){
        var settings = {
            prettySelect : false,
            ajaxFormValidationMethod : "post",
            ajaxFormValidation : true,
            modForm : null,
            onFailure :null,
            cFn : function(status, form, json, options){
                  if ( !! status) {
                      if ( !! json.status) {
                          if ( !! json.data && !! json.data.url) {
                              X.locationReload(json.data.url, 2000);
                              newAlert({
                                  msg: json.msg,
                                  fn: function() {
                                      location.href = json.data.url;
                                  }
                              });
                          } else {
                              X.locationReload(null, 2000);
                              newAlert({
                                  msg: json.msg,
                                  fn: function() {
                                      location.reload();
                                  }
                              });

                          }
                      } else {
                          alert(json.msg);
                      }
                  }
            }
        };
        $.extend(settings , option);
        $form.valid({
            onFailure : function(form){
                !!settings.onFailure && settings.onFailure(form);
            },
            prettySelect : settings.prettySelect,
            ajaxFormValidation: settings.ajaxFormValidation,
            ajaxFormValidationMethod: settings.ajaxFormValidationMethod,
            modForm : function(form, json, options){
                !!settings.modForm && settings.modForm(form, json, options);

            },
            onAjaxFormComplete: function(status, form, json, options) {
                  settings.cFn(status, form, json, options);
            }
        });

    },
    accMul : function (arg1,arg2){
        var m=0,s1=arg1.toString(),s2=arg2.toString();
        try{m+=s1.split(".")[1].length}catch(e){};
        try{m+=s2.split(".")[1].length}catch(e){};
        return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m);
     },
     accDiv : function (arg1,arg2){
        var t1=0,t2=0,r1,r2;
        try{t1=arg1.toString().split(".")[1].length}catch(e){};
        try{t2=arg2.toString().split(".")[1].length}catch(e){};
        with(Math){
        r1=Number(arg1.toString().replace(".",""));
        r2=Number(arg2.toString().replace(".",""));
        return (r1/r2)*pow(10,t2-t1);
        }
    },
    accAdd : function (arg1,arg2){ 
        var r1,r2,m; 
        try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0} ;
        try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0} ;
        m=Math.pow(10,Math.max(r1,r2)) ;
        return (arg1*m+arg2*m)/m ;
    },
    accSub : function (arg1,arg2){
         var r1,r2,m,n;
         try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0};
         try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0};
         m=Math.pow(10,Math.max(r1,r2));
         n=(r1>=r2)?r1:r2;
         return ((arg1*m-arg2*m)/m).toFixed(n);
     },
    textLimit: function($o, config) {
        $o.on('input propertychange', function(evt) {
               var $o = $(this); 
               var _maxlen = $o.attr("maxlength") || (config.maxlength || 300);
               var content_len = !! $o[0] ? $o.val().length : 0;
               var $next = $o.parent().find(config.nexttarget);
               var str = "";
               if (content_len <= _maxlen) {            
                   if (config.nexttarget) {
                       $next.html(content_len + "/" + _maxlen);
                   } 
               } else {
                   str = $o.val();
                   $o.val(str.replace(new RegExp("(.{"+ _maxlen +"}).+"), "$1"));
                   $next.html(_maxlen + "/" + _maxlen);
               }
        });
       $o.trigger("input").trigger("propertychange");
    }

};