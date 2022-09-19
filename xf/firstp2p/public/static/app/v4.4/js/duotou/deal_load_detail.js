$(function() {
    //给成交记录及协议增加地址  
    var origin_url = window.location.protocol + "//" +window.location.hostname;
    var contract_web_url = encodeURIComponent(origin_url + '/duotou/loanTansContract?number='+ contract_number +'&ctype=1&is_allow_access=1&token='+ $('#token').val()),
    contract_url = "firstp2p://api?type=webview&gobackrefresh=false&url=" + contract_web_url;
    $(".j_contract_btn").attr('href', contract_url);
    var cjjl_web_url = encodeURIComponent(origin_url +'/duotou/InvestList?deal_loan_id='+ deal_loan_id +'&project_id='+ deal_loan_projectId +'&is_allow_access=1&token='+ $('#token').val()),
    cjjl_url = "firstp2p://api?type=webview&gobackrefresh=false&url=" + cjjl_web_url;
    $(".j_cjjl_btn").attr('href', cjjl_url);

    $(".JS_submit_but").bind("click", function() {
        var $t = $(this);
        var dataObj = {
            "token":$('#token').val(),
            "deal_loan_id":deal_loan_id,
            "is_allow_access":1
        };
        var shuhui_url = '&is_allow_access=' + dataObj.is_allow_access + '&deal_loan_id=' + dataObj.deal_loan_id;
  
        // 申请赎回
        $.ajax({
            url: '/duotou/ApplyTrans',
            type: 'get',
            data: {
                'deal_loan_id': dataObj.deal_loan_id,
                'is_allow_access':dataObj.is_allow_access,
                'token':dataObj.token
            },
            dataType: 'json',
            success: function(result) {
                // console.log(result);
                if(result.errno == 0){
                    $("#j_dealloaddetail_pop").show();
                    $("#j_confirm_shui").show();
                    $('.j_bjin').html(result.data.money);
                    $('.j_fwf').html(result.data.manageFee);
                    // $('.j_wsy').html(result.data.norepayInterest);
                    $('.j_dzr').html(result.data.minTransferDays + '-' + result.data.maxTransferDays);
                    $("#JS-confirm").attr('href', 'invest://api?type=redeem' + shuhui_url);
                    $("#JS-confirm,#JS-cancel").click(function(){
                        $("#j_dealloaddetail_pop").hide();
                        $("#j_confirm_shui").hide();
                    });
                } else {
                    $("#j_dealloaddetail_pop").show();
                    $("#j_shui_err").show();
                    $('.j_err_txt').html(result.error);
                    $("#JS-know").click(function(){
                        $("#j_dealloaddetail_pop").hide();
                        $("#j_shui_err").hide();
                    });

                }
                
            },
            error: function() {
                // alert("0");
            }
        })

    });
});
