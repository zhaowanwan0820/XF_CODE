$(function() {
    // 拼接数据
    var cerHtml = function(data,scriptId,container) {
        var html = template(scriptId, data);
        container.html(html);
    };
    cerHtml(datajson,"contract_list",$('#contract_container'));
    
});