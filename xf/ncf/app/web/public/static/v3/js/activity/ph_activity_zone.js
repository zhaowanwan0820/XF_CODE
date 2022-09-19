$(function(){
  var href_status = GetUrlParam('status');
  var currentPage = GetUrlParam('page');
  var totalPage = $('#JS_totalPage').val()
  var count = $('#JS_count').val()
  statusFlag();
  $('.JS_state_all').click(function(){
    location.href = "/activity/ActivityZone"
  })
  $('.JS_state_1').click(function(){
    location.href = '/activity/ActivityZone?status=0'
  })
  $('.JS_state_0').click(function(){
    location.href = '/activity/ActivityZone?status=1'
  })

  //分页
  if(totalPage <= 1){
    $("#pagination").hide()
  }else{
    Firstp2p.paginate($("#pagination"), {
        pages: totalPage == 0 ? 1 : totalPage,
        currentPage: currentPage == 0 ? 1 : currentPage,
        onPageClick: function(pageNumber, $obj) {
            location.href = '/activity/ActivityZone?status=' + href_status + '&page=' + pageNumber
        }
    });
  }

// 导航按钮状态
  function statusFlag() {
    if(href_status == '0') {
      $('.JS_state_all, .JS_state_0').removeClass('active')
      $('.JS_state_1').addClass('active')
    }else if(href_status == '1') {
      $('.JS_state_all, .JS_state_1').removeClass('active')
      $('.JS_state_0').addClass('active')
    }else if(href_status == ''){
      $('.JS_state_1, .JS_state_0').removeClass('active')
      $('.JS_state_all').addClass('active')
    }
  }

//paraName 等找参数的名称
　　function GetUrlParam(paraName) {
　　　　var url = document.location.toString();
　　　　var arrObj = url.split("?");
　　　　if (arrObj.length > 1) {
　　　　　　var arrPara = arrObj[1].split("&");
　　　　　　var arr;
　　　　　　for (var i = 0; i < arrPara.length; i++) {
　　　　　　　　arr = arrPara[i].split("=");
　　　　　　　　if (arr != null && arr[0] == paraName) {
　　　　　　　　　　return arr[1];
                alert(arr[1]) 　　　　　　　　}
　　　　　　}
　　　　　　return "";
　　　　}
　　　　else {
　　　　　　return "";
　　　　}
　　}

})