function init_dealform()
{
    //绑定副标题20个字数的限制
    $("input[name='sub_name']").bind("keyup change",function(){
        if($(this).val().length>20)
        {
            $(this).val($(this).val().substr(0,20));
        }
    });
}

// 计算分期收费用
function calc_fenqi_fee(idstr, is_update_child) {
    var clss = idstr + "_arr";
    var fee = parseFloat($("#"+idstr).text());
    var len = $("."+clss).length - 1;
    var fee_avg = Math.floor(parseFloat(fee/len) * 100)/100;
    var remain = fee;
    if(len > 1) {
        if (is_update_child) {
            $("."+clss+":eq(0)").val(0);
        }
        for(i=1;i<len;i++){
            remain-=fee_avg;
            if (is_update_child) {
                $("."+clss+":eq("+i+")").val(fee_avg);
            }
        }
        if (is_update_child) {
            $("."+clss+":last").val(remain.toFixed(2));
        }
        var strNameArr = idstr.split("_");
        input_change($("#total_"+idstr) , $("."+clss+":last") , "."+clss, strNameArr[0]);
    }
}

//利滚利标input
function change_lgl_input(){
    var type_tag = $("#type_id").find("option:selected").attr("type_tag");
    var lgl_type_tag = $("#lgl_type_tag").val();
    var bxt_type_tag = $("#bxt_type_tag").val();
    var dtb_type_tag = $("#dtb_type_tag").val();
    var xffq_type_tag = $("#xffq_type_tag").val();
    if(type_tag == lgl_type_tag){
        $('#redemption_period').addClass('require').parent().parent().show();

        $('#lock_period').addClass('require').parent().parent().show();
    }else{
        $('#redemption_period').removeClass('require').parent().parent().hide();
        $('#iframepage_extra,#iframepage_rebate,#iframepage_special').show();

        $('#lock_period').removeClass('require').parent().parent().hide();
        $('#iframepage_extra,#iframepage_rebate,#iframepage_special').show();
    }
    if(type_tag == xffq_type_tag){
        $('#xffq_first_repay_date_tr').show();
        $("#first_repay_day_box").show();
    }else{
        $('#xffq_first_repay_date_tr').hide();
        var deal_status = $("input[name='deal_status']:checked").val();
        var loantype = $("#repay_mode").val();
        if ((loantype == 4 || loantype == 6) && deal_status == 4) {
            $("#first_repay_day_box").show();
        } else {
            $("#first_repay_day_box").hide();
        }
    }
    if(type_tag == bxt_type_tag){
        $('#bianxiantong').show();
        $('#bianxiantong input').addClass('require');
    }else{
        $('#bianxiantong input').removeClass('require');
        $('#bianxiantong').hide();
    }

    if(type_tag == dtb_type_tag){
        $('#management_agency_tr').show();
        $('#management_fee_rate_tr').show();
        $('#management_fee_rate_type_tr').show();
        $('#management_fee_rate_type').addClass('require');
    }else{
        $('#management_agency_tr').hide();
        $('#management_fee_rate_tr').hide();
        $('#management_fee_rate_type_tr').hide();
        $('#management_fee_rate_type').removeClass('require')
    }
}
jQuery(function(){
    //绑定会员ID检测
    $("input[name='user_id']").bind("blur",function(){
        if(isNaN($(this).val())){
            alert("必须为数字");
            return false;
        }
        if($(this).val().length>0)
        {
            $.ajax({
                url:ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=load_user&id="+$(this).val(),
                dataType:"json",
                success:function(result){
                    if(result.status ==1)
                    {
                        if(result.user.services_fee)
                            $("input[name='services_fee']").val(parseFloat(result.user.services_fee));
                        else
                            $("input[name='services_fee']").val("5");
                    }
                    else{
                        alert("会员不存在");
                    }
                }
            });
        }
    });

    $("input[name='deal_status']").live("click",function(){
        $("#start_time_box #start_time").removeClass("require");
        $("#bad_time_box #bad_time").removeClass("require");
        $("#repay_start_time_box #repay_start_time").removeClass("require");
        var loantype = $("#repay_mode").val();
        switch($(this).val()){
            case "1":
                $("#start_time_box").show();
                $("#start_time_box #start_time").addClass("require");
                $("#bad_time_box").hide();
                $("#bad_info_box").hide();
                $("#start_loan_time_box").hide();
                $("#first_repay_day_box").hide();
                $("#repay_start_time_box").hide();
                break;
            case "3":
                $("#start_time_box").hide();
                $("#bad_time_box").show();
                $("#bad_time_box #bad_time").addClass("require");
                $("#bad_info_box").show();
                $("#repay_start_time_box").hide();
                $("#first_repay_day_box").hide();
                $("#start_loan_time_box").hide();
                break;
            case "4":
                $("#start_time_box").hide();
                $("#bad_time_box").hide();
                $("#bad_info_box").hide();
                $("#repay_start_time_box").show();
                if (loantype == 4 || loantype == 6) {
                    $("#first_repay_day_box").show();
                }
                $("#bad_time_box #repay_start_time").addClass("require");
                $("#start_loan_time_box").hide();
                break;
            case "8":
                $("#first_repay_day_box").show();
                break;
            default :
                $("#start_time_box").hide();
                $("#bad_time_box").hide();
                $("#bad_info_box").hide();
                $("#repay_start_time_box").hide();
                $("#first_repay_day_box").hide();
                $("#start_loan_time_box").show();
                break;
        }
    });

    $("#daren").bind("click",function(){

        if($(this).attr('checked') == true){
            $('.loan_limit').show();

        }else{
            $('.loan_limit').hide();
            $('#min_loan_total_count').val('0');
            $('#min_loan_total_amount').val('0.00');
        }
    });

    change_lgl_input();

});


//利滚利 自动回填 借款期限天数
/*var change_lgl_day = function (){
    var date_now = new Date();
    var date_end = new Date($('#end_date').val().replace(/-/g,'/'));
    var day = (date_end.getTime() - date_now.getTime())/1000/86400;
    $('#repay_period2').val(day > 0 ? Math.ceil(day) : 0);
    //changeRate('income_fee_rate');
}*/

//利滚利 自动回填 终止日期
/*function change_lgl_time(){
    var date = new Date((new Date().getTime()+$('#repay_period2').val()*86400*1000));
    var year = date.getFullYear();
    var month = date.getMonth()+1;
    var day = date.getDate();
    month = month < 10 ? '0'+ month : month;
    day = day < 10 ? '0'+ day : day;
    $('#end_date').val(year+"-"+month+"-"+day);
    //changeRate('income_fee_rate');
}*/

//借款综合成本（年化）
function get_complex_rate(){
    var number_scale_length = 5;
    var repay_mode = $('#repay_mode').val();
    var rate = parseFloat($('#annualized_rate').val());
    var loan_fee_rate = parseFloat($("input[name='loan_fee_rate']").val());
    var consult_fee_rate = parseFloat($("input[name='consult_fee_rate']").val());
    var guarantee_fee_rate = parseFloat($("input[name='guarantee_fee_rate']").val());
    var pay_fee_rate = parseFloat($("input[name='pay_fee_rate']").val());

    if(repay_mode == 5){
        var repay_time = $('#repay_period2').val();
    }else if(repay_mode == 4){
        var repay_time = $('#erpay_period3').val();
    }else{
        var repay_time = $("#repay_period").val();
    }

    if(repay_time > 0){
        var yearly_rate = rate+parseFloat(loan_fee_rate)+parseFloat(consult_fee_rate)+parseFloat(guarantee_fee_rate)+parseFloat(pay_fee_rate);
    }else{
        var yearly_rate = rate;
    }

    yearly_rate = isNaN(yearly_rate) ? 0 : yearly_rate;
    $('#yearly_rate').html(yearly_rate.toFixed(number_scale_length));
}

function change_year_to_period(){
    get_period_rate('loan_fee_rate');
    get_period_rate('consult_fee_rate');
    get_period_rate('guarantee_fee_rate');
    get_period_rate('advisor_fee_rate');
    get_period_rate('pay_fee_rate');
    get_period_rate('management_fee_rate');
    get_period_rate('canal_fee_rate');

}

function get_period_rate($rate_name){
    var rate = $('#'+$rate_name).val();
    var repay_mode = $('#repay_mode').val();
    if(repay_mode == 5){
        var repay_time = $('#repay_period2').val();
    }else if(repay_mode == 4 || repay_mode == 3 || repay_mode == 2){
        var repay_time = $('#repay_period3').val();
    }else{
        var repay_time = $('#repay_period').val();
    }
    var url = "m.php?m=Ajax&a=convertToPeriodRate&repay_mode="+repay_mode+"&period="+repay_time+"&rate="+rate;
    jQuery.getJSON(url,function(json){
        var period_fee_rate  = Number(json).toFixed(5);
        $('#period_'+$rate_name).html(period_fee_rate);
    });
    get_complex_rate();

    if(repay_mode != 5) {
        var totalMoneyArr = $rate_name.split("_");
        var idFlag = totalMoneyArr[0] + "_" + totalMoneyArr[1];
        var loanMoney = parseFloat($("#apr").val()); // 借款金额
        var feeMoney = Math.floor( parseFloat((loanMoney * rate/100 * repay_time * 30) / 360) * 100) / 100;
        $("#"+idFlag).text(feeMoney);
        calc_fenqi_fee(idFlag, (document.readyState == 'complete'));
    }

    if (document.readyState == 'complete' && typeof(is_proxy_sale) == 'function' && is_proxy_sale()) {
        update_proxy_loan_info();
    }
}

var dealcrowd=function()
{
    $("#relation").hide();
    if($('#deal_crowd').val()=='16') // 指定用户可投
    {
        $("#specify_vip").hide();
        $("#specify_uid_dev").show();
        $("#specify_uid").focus();
        $("input#specify_uid").addClass('require');
        $('#user_group').hide();
        $('.loan_limit').hide();
        $('#daren').attr('checked', false);
        $('#min_loan_total_count').val('0');
        $('#min_loan_total_amount').val('0');
        $("#upload_csv_datas").hide();
    }
    else if($('#deal_crowd').val()=='1')//新手专享
    {
        /*
         $('#max_loan_money').removeAttr('readonly');
         $('#max_loan_money').removeAttr('disabled');
         */
        $("#specify_vip").hide();
        $("#specify_uid_dev").hide();
        $("input#specify_uid").removeClass('require');
        $('#user_group').hide();
        $('.loan_limit').hide();
        $('#daren').attr('checked', false);
        $('#min_loan_total_count').val('0');
        $('#min_loan_total_amount').val('0');
        $("#upload_csv_datas").hide();
    }
    else if ($('#deal_crowd').val() == '33')
    {
        //VIP用户专享
        $("#specify_uid_dev").hide();
        $('#user_group').hide();
        $("#specify_vip").show();
        $("#upload_csv_datas").hide();
    }else if($('#deal_crowd').val() == '34'){
        $("#specify_uid_dev").hide();
        $('#user_group').hide();
        $("#specify_vip").hide();
        $("input#specify_uid").removeClass('require')
        $("#upload_csv_datas").show();
    }
    else
    {
        $("#specify_vip").hide();
        $("#specify_uid_dev").hide();
        $("#upload_csv_datas").hide();
        /*
         $('#max_loan_money').val('0');
         $('#max_loan_money').attr({readonly:'readonly',disabled:'disabled'});
         */
        if($('#deal_crowd').val()=='2') //特定用户组
        {
            $("#relation").show();
            $('#user_group').show();
        }
        else
        {
            $('#user_group').hide();
        }
    }
};

function specify_blur() {
    var specify_uid = $("#specify_uid").val();
    if($('#deal_crowd').val()!='16') {
        return ;
    }
    if($.trim(specify_uid) == "") {
        alert('指定用户ID不能为空');
        $("#specify_uid").val("");
    }else if(isNaN(specify_uid)) {
        alert('指定用户ID必须为纯数字');
        $("#specify_uid").val("");
    }else{
        $.ajax({
            url:ROOT+"?"+VAR_MODULE+"=User&"+VAR_ACTION+"=getAjaxUser&id="+specify_uid,
            dataType:"json",
            success:function(result){
                if(result.status ==1)
                {
                    if(result.user.is_effect == 0 || result.user.is_delete == 1) {
                        alert("指定用户ID在系统中无效");
                        $("#specify_uid").val("");
                        $("#specify_user").text("");
                    }else {
                        $("#specify_user").text("  姓名:"+result.user.real_name+" 手机:"+result.user.mobile);
                    }
                }
                else{
                    alert("指定用户ID在系统中不存在");
                    $("#specify_uid").val("");
                    $("#specify_user").text("");
                }
            }
        });
    }
}

var checkLoanMoney=function()
{
    var min=parseFloat($('#min_loan_money').val());
    var max=parseFloat($('#max_loan_money').val());
    if(max > 0 && min >0)
    {
        if(max<min)
        {
            alert('最大金额不能小于最小金额');
            return false;
        }
    }
};
