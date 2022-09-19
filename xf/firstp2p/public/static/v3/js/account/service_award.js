(function($) {
  $(function() {
    (function() {
      /* arttemplate模板把时间戳转换 */
      template.helper('dateFormat',function (str) {
          var html="";
          var arr=[];
          if (str){
              arr=str.split(/\s+/);
              html='<p>'+arr[0]+'</p>'+'<p>'+arr[1]+'</p>';
          }else{
              html='--';
          }
          return html;
      });
        var tabItem=$('#tabCont .item');
        var tabTitle=$('#tabTitle li');
        var tabsUl = $("#tabTitle");

        function setpro(input) {
          var errorTip=input.siblings('.errorTip');
          var isBigUser=$.trim(input.siblings('.is_big_user').val());
          var userVerify = false;
          var userVal = $.trim(input.val());
          if('1'==isBigUser){
            userVerify = (userVal == "") || /^1[3456789]\d{9}$/.test(userVal);
          }else{
            userVerify = (userVal == "") || /^[\u0391-\uFFE5]{2,12}$|^1[3456789]\d{9}$/.test(userVal);
          }

          input.val(userVal);
          if (!userVerify) {
            errorTip.show();
            return false;
          } else {
            errorTip.hide();
            return true;
          }
        };
        $("#tabCont .searchInput").on('blur',function() {
          setpro($(this));
        });
        
        // tab事件绑定
        tabsUl.on("click", "li", function() {
          var index=$(this).index();
          tabTitle.removeClass('active');
          $(this).addClass('active');
          tabItem.hide().eq(index).show();
        });

        // 点击查询按钮
        $("#tabCont .searchBtn").on("click", function() {
          var input=$(this).siblings('.searchInput');
          var curItem=$(this).parents('.item');
          var index = curItem.index();
          var type=tabTitle.eq(index).data('tabname');
          if(setpro(input)){
            input.data('content',$.trim(input.val()));
            reqData(curItem,type,1);
          }
        });

        // ajax请求
        function reqData(curItem,type,page) {
          var curInput=curItem.find('.searchInput');
          var content=curInput.data('content');
          var ajaxData={
            'type':type,
            'page':page,
            "content":content,
            "dataType":2
          };
          var _lock = curItem.data('lock');
          if(_lock == "1"){
            return;
          }
          curItem.data('lock','1');
            
          $.ajax({
            url: '/coupon/lists',
            type: 'GET',
            data: ajaxData,
            dataType: 'json',
            success: function(result) {
              investHtml(curItem,result,type);
              curItem.data('lock','0');
            },
            error: function() {
              curItem.data('lock','0');
            }
          });
        }

        // 数据拼接
        function investHtml(curItem,result,type) {
            var curTbody=curItem.find('tbody');
            var noRecord=curItem.find('.no_record');
            var curTable=curItem.find('table');
            var pagination=curItem.find('.pagination');
            var html = template('invest_data', result);

            curTbody.html(html);
            if(result.pagecount <= 0){
                noRecord.show();
                curTable.add(pagination).hide();
            } else {
                noRecord.hide();
                curTable.add(pagination).show();
                pageFn(curItem,result,type);
            }
        }

        function pageFn(curItem,result,type) {
            var indexPage=null;
            var lastPage=null;
            var pageText = '';
            var curPaginate=curItem.find('.pagination');

            curPaginate.show();
            Firstp2p.paginate(curPaginate, {
                pages: result.pagecount,
                currentPage: result.page,
                displayedPages:3,
                onPageClick: function(page, $obj) {
                    reqData(curItem,type,page);
                }
            });
            //分页结构二次加工
            if(result.page==1){
                indexPage=$('<li><span class="index" title="首页">首页</span></li>');
            }else{
                indexPage=$('<li><a href="#page=1" class="page-link index" title="首页">首页</a></li>');
            }
            if(result.page==result.pagecount){
                lastPage=$('<li><span class="last" title="尾页">尾页</span></li>');
            }else{
                lastPage=$('<li><a href="#page='+result.pagecount+'" class="page-link last" title="尾页">尾页</a></li>');
            }
            pageText = '<li><span class="total">共<i>'+result.pagecount+'</i>页</span></li>';
            curPaginate.find("ul").append(lastPage,pageText).prepend(indexPage);
            curPaginate.find('a.last,a.index').on('click',function (event) {
                event.preventDefault();
                var page=$(this).attr('href').match(/.*#page=(\d*)$/)[1];
                reqData(curItem,type,page);
            });
        }

      tabItem.each(function (index) {
        var type=tabTitle.eq(index).data('tabname');
        reqData($(this),type,1);
      });
    })();
  });
})(jQuery);
