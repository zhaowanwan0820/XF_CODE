const url_='https://www.huanhuanyiwu.com/admin/';
var U = {
    /**
     * promise AJAX
     * obj {} 
     * obj.site url地址 
     * obj.data 参数对象
     * obj.type 传输方式 GET POST
     * */
    https(obj){
        return new Promise((resolve, reject)=>{
            $.ajax({
                type: obj.type,
                url: url_ + obj.site,
                data: obj.data,
                success: function (res) {
                    console.log(res)
                    let data = U.parseJSON(res);
                    if(data.code == 1 && data.msg == 'OK'){
                        console.log(data)
                        resolve(data)
                    }else{
                        reject(data) 
                    }
                },
                error: function (err) {
                    reject(err)
                }
            });
        })
    },
    /**
     * 截取参数
     * name:要获取的单数名称
     * */
    GetQueryString(name) {
        // console.log(name);
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]);
        return null;
    },
    /**
     *  JSON格式转化
     * 【_Obj：是JSON格式的字符串或对象或集合】
     */
    parseJSON (_Obj) {
        var result = _Obj;
        if (!$.isArray(_Obj) && !$.isPlainObject(_Obj)) {
            result = $.parseJSON(_Obj);
        }else {
            return '';
        }
        return result;
    },
    
}