$(document).ready(function(){
    init_word_box();
    $("#info").ajaxStart(function(){
         $(this).html(LANG['AJAX_RUNNING']);
         $(this).show();
    });
    $("#info").ajaxStop(function(){

        $("#info").oneTime(2000, function() {
            $(this).fadeOut(2,function(){
                $("#info").html("");
            });
        });
    });
    $("form").bind("submit",function(){
        var btn=$('form input[type="submit"]');
        var $input = $('#hkop');
        $(btn).css({"color":"gray","background":"#ccc"}).attr("disabled","disabled");
        var doms = $(".require");
        var check_ok = true;
        $.each(doms,function(i, dom){
            //if($.trim($(dom).val())==''||$(dom).val()=='0')
            if($.trim($(dom).val())=='')
            {
                    var title = $(dom).parent().parent().find(".item_title").html();
                    if(!title)
                    {
                        title = '';
                    }
                    if(title.substr(title.length-1,title.length)==':')
                    {
                        title = title.substr(0,title.length-1);
                    }
                    if($(dom).val()=='')
                    TIP = LANG['PLEASE_FILL'];
                    if($(dom).val()=='0')
                    TIP = LANG['PLEASE_SELECT'];
                    alert(TIP+title);
                    $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
                    $(dom).focus();
                    check_ok = false;
                    return false;
            }
        });
        if(!check_ok)
        return false;
        if($("form").attr("name")=="search"){
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            return true;
            }else{
                if (confirm("确定此操作吗？")) {
                    //$input.val(btn.val());
                    $(this).append($input);
                     return true;
                } else {
                    $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
                    return false;
                }
            }
    });

});
//排序
function sortBy(field,sortType,module_name,action_name)
{
    location.href = CURRENT_URL+"&_sort="+sortType+"&_order="+field+"&";
}
//添加跳转
function add()
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=add";
}
//编辑跳转
function edit(id, role)
{
    var url = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=edit&id="+id;
    if (role) {
        url = url + '&role=' + role;
    }
    location.href = url;
}
//置为无效
function disable(id)
{
    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=disable&id="+id,
        data: "ajax=1",
        dataType: "json",
        success: function(obj){
            if(obj.data=='1') {
                alert("操作成功");
                location.reload();
            } else {
                alert("操作失败,请稍后重试!");
            }
        }
    });
}
//添加跳转
function add_goods()
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=shop_add";
}
//编辑跳转
function edit_goods(id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=shop_edit&id="+id;
}

//添加跳转
function add_deal_youhui()
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=youhui_add";
}
//编辑跳转
function edit_deal_youhui(id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=youhui_edit&id="+id;
}

//全选
function CheckAll(tableID)
{
    $("#"+tableID).find(".key").attr("checked",$("#check").attr("checked"));
}

function toogle_status(id,domobj,field)
{
    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=toogle_status&field="+field+"&id="+id,
        data: "ajax=1",
        dataType: "json",
        success: function(obj){

            if(obj.data=='1')
            {
                $(domobj).html(LANG['YES']);
            }
            else if(obj.data=='0')
            {
                $(domobj).html(LANG['NO']);
            }
            else if(obj.data=='')
            {

            }
            $("#info").html(obj.info);
        }
    });
}

//改变状态
function set_effect(id,domobj)
{
    if (!confirm('确认要重置用户状态吗?'))
    {
        return false;
    }
    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=set_effect&id="+id,
        data: "ajax=1",
        dataType: "json",
        success: function(obj){

            if(obj.data=='1')
            {
                $(domobj).html(LANG['IS_EFFECT_1']);
            }
            else if(obj.data=='0')
            {
                $(domobj).html(LANG['IS_EFFECT_0']);
            }
            else if(obj.data=='')
            {

            }
            $("#info").html(obj.info);
        }
    });
}

//改变状态
function set_coupon_disable(id,domobj)
{
    if (!confirm('确认要重置优惠码状态吗?'))
    {
        return false;
    }
    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"=User&"+VAR_ACTION+"=set_coupon_disable&id="+id,
        data: "ajax=1",
        dataType: "json",
        success: function(obj){
            if(obj.status == 1){
                if(obj.data=='1')
                {
                    $(domobj).html(LANG['IS_EFFECT_0']);
                }
                else if(obj.data=='0')
                {
                    $(domobj).html(LANG['IS_EFFECT_1']);
                }
            }
            else
            {
                alert(obj.info);
            }
        }
    });
}

function set_sort(id,sort,domobj)
{
    $(domobj).html("<input type='text' value='"+sort+"' id='set_sort' class='require'  />");
    $("#set_sort").select();
    $("#set_sort").focus();
    $("#set_sort").bind("blur",function(){
        var newsort = $(this).val();
        $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=set_sort&id="+id+"&sort="+newsort,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                if(obj.status)
                {
                    $(domobj).html(newsort);
                }
                else
                {
                    $(domobj).html(sort);
                }
                $("#info").html(obj.info);

            }
    });
});
}

//普通删除
function del(id)
{
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert(LANG['DELETE_EMPTY_WARNING']);
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
    }
    if(confirm(LANG['CONFIRM_DELETE']))
    $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=delete&id="+id,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                $("#info").html(obj.info);
                if(obj.status==1)
                location.href=location.href;
            }
    });
}
//完全删除
function foreverdel(id, m)
{
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert(LANG['DELETE_EMPTY_WARNING']);
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
    }
    if (!m) {
        m = 'foreverdelete';
    }

    if(confirm(LANG['CONFIRM_DELETE']))
    $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=" + m + "&id="+id + "&t=" + Math.random(),
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                $("#info").html(obj.info);
                if(obj.status==1)
                location.href=location.href;
            }
    });
}
//恢复
function restore(id)
{
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert(LANG['RESTORE_EMPTY_WARNING']);
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
    }
    if(confirm(LANG['CONFIRM_RESTORE']))
    $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=restore&id="+id,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                $("#info").html(obj.info);
                if(obj.status==1)
                location.href = location.href;
            }
    });
}

//majax("audit", "id=1&status=1", function(){}, function(){})
function majax(action, param, ok_func, err_func) {
    if (!confirm("确定要进行此操作么?")) {
        return
    }

    $.ajax({
            url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=" + action + "&" + param,
            data: "ajax=1",
            dataType: "json",
            success: function(data){
                if (data.status == true) {
                    if (ok_func) {
                        ok_func(data);
                    } else {
                        alert("操作已成功");
                        location.href = location.href;
                    }
                } else if (data.status == false) {
                    if (err_func) {
                        err_func(data);
                    } else {
                        if (data.info) {
                            alert(data.info);
                        } else {
                            alert("操作失败, 请刷新页面重试");
                       }
                    }
                }
            }
    });
}

//节点全选
function check_node(obj)
{
    $(obj.parentNode.parentNode.parentNode).find(".node_item").attr("checked",$(obj).attr("checked"));
}
function check_is_all(obj)
{
    if($(obj.parentNode.parentNode.parentNode).find(".node_item:checked").length!=$(obj.parentNode.parentNode.parentNode).find(".node_item").length)
    {
        $(obj.parentNode.parentNode.parentNode).find(".check_all").attr("checked",false);
    }
    else
        $(obj.parentNode.parentNode.parentNode).find(".check_all").attr("checked",true);
}
function check_module(obj)
{
    if($(obj).attr("checked"))
    {
        $(obj).parent().parent().find(".check_all").attr("checked",true);
        $(obj).parent().parent().find(".node_item").attr("checked",true);
    }
    else
    {
        $(obj).parent().parent().find(".check_all").attr("checked",false);
        $(obj).parent().parent().find(".node_item").attr("checked",false);
    }
}


function export_csv()
{
    idBox = $(".key:checked");
    idArray = new Array();
    $.each( idBox, function(i, n){
        idArray.push($(n).val());
    });
    id = idArray.join(",");

    var inputs = $(".search_row").find("input");
    var selects = $(".search_row").find("select");
    var param = '';
    for(i=0;i<inputs.length;i++)
    {
        if(inputs[i].name!='m'&&inputs[i].name!='a')
        param += "&"+inputs[i].name+"="+$(inputs[i]).val();
    }
    for(i=0;i<selects.length;i++)
    {
        param += "&"+selects[i].name+"="+$(selects[i]).val();
    }
    var url= ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=export_csv&id="+id;
    location.href = url+param;
}

function init_word_box()
{
    $(".word-only").bind("keydown",function(e){
        if(e.keyCode<65||e.keyCode>90)
        {
            if(e.keyCode != 8)
            return false;
        }
    });
}

function reset_sending(field)
{
    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"=Index&"+VAR_ACTION+"=reset_sending&field="+field,
        data: "ajax=1",
        dataType: "json",
        success: function(obj){
            $("#info").html(obj.info);
        }
    });
}

function search_supplier()
{
    var key = $("input[name='supplier_key']").val();
    if($.trim(key)=='')
    {
        alert(INPUT_KEY_PLEASE);
    }
    else
    {
        $.ajax({
            url: ROOT+"?"+VAR_MODULE+"=SupplierLocation&"+VAR_ACTION+"=search_supplier",
            data: "ajax=1&key="+key,
            type: "POST",
            success: function(obj){
                $("#supplier_list").html(obj);
            }
        });
    }
}
userCard=(function(){
    return {
        load : function(e,id){


            }
          };
})();


(function($){
    $(function(){
        $(".j_img_upload").bind("change" , function(evt) {
            if(!evt.target.files){
                return false;
            }else{
                var files = evt.target.files;
            }
            if(typeof VAR_FILESIZE == 'undefined'){
                VAR_FILESIZE = 1.5;
            };
            if (parseInt(files[0].size) > parseInt(VAR_FILESIZE)*1024*1024 ) {
                alert(KE.lang['invalidFiles'] + ",不能超过"+  parseFloat(VAR_FILESIZE) +"MB,请重新上传");
                this.value = "";
            }
        });
    });
})(jQuery);
