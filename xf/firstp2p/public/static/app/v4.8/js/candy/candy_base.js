/**
 * ztx create 2018/07/18
 */
//通过iframe触发scheme
function triggerScheme(scheme){
    var newIframe=$('<iframe src="'+scheme+'" style="display: none;"></iframe>').appendTo('body');
    newIframe.remove();
}