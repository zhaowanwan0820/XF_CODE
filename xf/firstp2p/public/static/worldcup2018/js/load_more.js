
/* loadMore 公用方法封装,参数ele字符串追加父元素,status用户（列表数据状态）*/
function _load_more(ele,status,loadMoreText){
  var html
  if(status == 1){
    html = '<div class="ui_loadmore"><a href="javascript:void(0)">点击加载更多</a></div>'
  }else if(status == 2){
    html = '<div class="ui_loadmore"><div class="ui_loading"><div class="bar1"></div><div class="bar2"></div><div class="bar3"></div><div class="bar4"></div><div class="bar5"></div><div class="bar6"></div><div class="bar7"></div><div class="bar8"></div><div class="bar9"></div><div class="bar10"></div><div class="bar11"></div><div class="bar12"></div></div>&nbsp;&nbsp;正在加载</div>'
  }else if(status == 3){
    if(loadMoreText){
      html = '<div class="ui_loadmore">'+loadMoreText+'</div>'
    }else{
      html = '<div class="ui_loadmore">没有更多了</div>'
    }
    
  }
  var Ele = document.createElement("div")
  Ele.innerHTML = html
  ele.appendChild(Ele)
}