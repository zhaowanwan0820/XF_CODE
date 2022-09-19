/* 使用rem布局设置初始字体大小，按照苹果6的标准进行计算 */
var winW = document.documentElement.clientWidth
document.documentElement.style.fontSize = winW / 375 * 100 + "px"
/* 设置APP返回按钮显示问题 */
$(function(){
  $(window).resize(function(){
    var winW = document.documentElement.clientWidth
    document.documentElement.style.fontSize = winW / 375 * 100 + "px"
  })
  var path = location.pathname
	if(path == "/house/ProductIntroduction") {
			window.location.href = 'firstp2p://api?method=updatebacktype&param=2';
	} else if(path == "/house/Result") {
			window.location.href = 'firstp2p://api?method=updatebacktype&param=3';
	} else if(path == '/creditloan/loan_index') {
      window.location.href = 'firstp2p://api?method=updatebacktype&param=1';
  }else{
			window.location.href = 'firstp2p://api?method=updatebacktype&param=0';
  }
})

/* 底部提示语 */
function _html(ele,ContentH,Gap,TextShow){
  var url = location.origin + "/help/faq_list/?cid=226"
  var html = '<div class="bottom_tips_container">'
  + '<div class="common_problem">'
  + '<a href='+url+'>'
  + '常见问题'
  +'</a>'
  + '</div>'
  + '<div class="bottom_tips">'
  + '<span class="line"></span>'
  + '<span>本服务由深圳一房和信资产管理有限公司提供</span>'
  + '<span class="line"></span>'
  + '</div>'
  + '</div>'
  var Ele = document.createElement("div")
  Ele.innerHTML = html
  ele.appendChild(Ele)
  bottomTips(ContentH,Gap,TextShow)
}
function bottomTips(ContentH,Gap,TextShow){
  //获取winH,fixedH
  var winH = $(window).height(),
      fixedH = $(".bottom_tips_container").height(),
      bottom_tips_container = $(".bottom_tips_container")
  var common_problem = $(".common_problem a")
  if( parseFloat(winH) > (parseFloat(fixedH) + parseFloat(ContentH))){
    !Gap ?
    bottom_tips_container.addClass("bottom_tips_container1")
    :
    bottom_tips_container.addClass("bottom_tips_container1").css("bottom",Gap)
    TextShow == "block" ?
    common_problem.css("display","block")
    :
    common_problem.css("display","none")
  }else{
    TextShow == "block" ?
    common_problem.css("display","block")
    :
    common_problem.css("display","none")
  }
}
/* --end-- */
/* scheme源生调起手机相册+拍照功能,自定义方法传参：ele,photoFn,albumFn,popup_show,photo_show */

function _scheme_photo(ele,_data_integrity,photo_show){
  var html = '<div class="photo_click_popup">'
        + '<div class="photo_fixed">'
        + '<a href="firstp2p://api?type=photo&source=camera&callback=photoFn" class="photo"><span>拍照</span></a>'
        + '<a href="firstp2p://api?type=photo&source=library&callback=albumFn" class="album"><span>从相册中选择</span></a>'
        + '<span class="photo_cancel">取消</span>'
        + '</div>'
        + '</div>'
  var Ele = document.createElement("div")
  Ele.innerHTML = html
  ele.appendChild(Ele)
  var photo_click_popup = $(".photo_click_popup");
  /* 点击取消，拍照弹框隐藏 */
  $(".photo_cancel").click(function(){
    photo_click_popup.css("display","none")
  })
  /* 判断用户是否传入photo_show：只显示拍照或者只显示从相册中选择或者拍照和从相册中选择均显示 */
  if(photo_show == "undefined"){
    $(".photo").show()
    $(".album").show()
  }else if(photo_show == 1){
    $(".photo").show()
    $(".album").hide()
  }else if(photo_show == 2){
    $(".photo").hide()
    $(".album").show()
  }
}
 /* 调起拍照功能回调方法 */
 function photoFn(data){
  $(".photo_click_popup").css("display","none")
  /* 返回数据添至mying下的img里面并判断数据完整性 */
  $imgEl.html('<img src="'+data.url+'">')
  if($imgEl.selector==".myimg_file1"){
    myimg_file1 = data.url
    first_img_id = data.image_id
    P2PWAP.cache.set("_myimg_file1_src_",myimg_file1,60000)
    P2PWAP.cache.set("_myimg_file1_id_",first_img_id,60000)
  }else{
    myimg_file2 = data.url
    second_img_id = data.image_id
    P2PWAP.cache.set("_myimg_file2_src_",myimg_file2,60000)
    P2PWAP.cache.set("_myimg_file2_id_",second_img_id,60000)
  }
  _data_integrity()
}
/*调起手机相册回调函数*/
function albumFn(data){
  $(".photo_click_popup").css("display","none")
  /* 返回数据添至mying下的img里面并判断数据完整性 */
  $imgEl.html('<img src="'+data.url+'">')
  if($imgEl.selector==".myimg_file1"){
    myimg_file1 = data.url
    first_img_id = data.image_id
    P2PWAP.cache.set("_myimg_file1_src_",myimg_file1,60000)
    P2PWAP.cache.set("_myimg_file1_id_",first_img_id,60000)
  }else{
    myimg_file2 = data.url
    second_img_id = data.image_id
    P2PWAP.cache.set("_myimg_file2_src_",myimg_file2,60000)
    P2PWAP.cache.set("_myimg_file2_id_",second_img_id,60000)
  }
  _data_integrity()
}
/* --end-- */

/* loadMore 公用方法封装,参数ele字符串追加父元素,status用户（借款或房产列表数据状态）*/
function _load_more(ele,status){
  var html
  if(status == 1){
    html = '<div class="ui_loadmore"><a href="javascript:void(0)">点击加载更多</a></div>'
  }else if(status == 2){
    html = '<div class="ui_loadmore"><div class="ui_loading"><div class="bar1"></div><div class="bar2"></div><div class="bar3"></div><div class="bar4"></div><div class="bar5"></div><div class="bar6"></div><div class="bar7"></div><div class="bar8"></div><div class="bar9"></div><div class="bar10"></div><div class="bar11"></div><div class="bar12"></div></div>&nbsp;&nbsp;正在加载</div>'
  }else if(status == 3){
    html = '<div class="ui_loadmore">没有更多了</div>'
  }
  var Ele = document.createElement("div")
  Ele.innerHTML = html
  ele.appendChild(Ele)
}
