;$(function() {
    (function() {
        var contractData=null;//用于缓存合同数据创建
        // 出借确认页关闭提示组件
        $(".wrap").addClass("p_dealbid");
        $(".p_dealbid").tooltip({
            disabled: true
        });
        /************* 出借券 *************/
        // ajax请求
        var listData = null,tipMsg="";
        function reqAvaliableCount(data) {
            var pageText = null;
            $.ajax({
                url: "/deal/DiscountAvaliableCount",
                type: "GET",
                data: data,
                dataType: "json",
                beforeSend: function() {},
                success: function(result) {
                    var tzq_tit=$('#tzq_tit');
                    $("#discountAvaliableCount").val(result);
                    tzq_tit.html('<i></i>您有<span>' + result + '</span>张优惠券可用');
                },
                error: function() {}
            })
        };
        function reqData(data,type) {
            var pageText = null;
            $.ajax({
                url: "/deal/discountPickList?type="+type,
                type: "GET",
                data: data,
                dataType: "json",
                beforeSend: function() {},
                success: function(result) {
                    result.type = type;
                    listData = result;
                    // console.log(JSON.stringify(result));
                    $("#tzq_choose").show();
                    cerHtml(result,tipMsg);
                    if (result.pagecount <= 1) {
                        $("#pagination").hide();
                        return;
                    } else {
                        $("#pagination").show();
                        Firstp2p.paginate($("#pagination"), {
                            pages: result.pagecount,
                            currentPage: result.page,
                            onPageClick: function(pageNumber, $obj) {
                                cerData["page"] = pageNumber;
                                reqData(cerData,type);
                            }
                        });
                        pageText = '<li style="line-height:25px;">' + result.count + ' 条记录 ' + result.page + '/' + result.pagecount + ' 页</li>';
                        $("#pagination").find("ul").prepend(pageText);
                    }
                },
                error: function() {}
            })
        };

        // 数据拼接
        var _radioselectdata_ = null;
        function _updateSelectedText_(data,tipMsg) {
            var spanTxt = "";
            if (_radioselectdata_ == null){
                $(".zjq_radio").removeAttr("checked");
                // $("#tzq_tit").html('<i></i>您有 <span class="color-yellow1">' + data.count + '</span> 张出借券可用');
            }else{
                 var type_text = data.type == 1 ? "返现券":"加息券";
                //正则匹配金额或加息数,进行高亮
                // var reg = /(\d*(\.)?\d*( ?)[元|%])/;
                // if(tipMsg){
                //     tipMsg = tipMsg.replace(reg, function(a, b){
                //         return '<span class="color-yellow1">'+b+'</span>';
                //     });
                // }
                spanTxt ='<i></i>已选择<span class="color-yellow1">1</span>张'+  type_text +'，' + tipMsg +'<a href="javascript:void(0)" class="blue pl15" id="cancel_choose">取消选择</a>';
                $("#tzq_tit").html(spanTxt);
                $("#tzq_tit").on("click", "#cancel_choose", function() {
                    $(".zjq_radio").removeAttr("checked");
                    var avaliableCount = $("#discountAvaliableCount").val();
                    // $("#tzq_tit").html('<i></i>您有 <span class="color-yellow1">' + avaliableCount + '</span> 张出借券可用');
                    $("#tzq_tit").html('<i></i>您有<span>' + avaliableCount + '</span>张优惠券可用');
                    _radioselectdata_ = null;
                    $("#discountId").val("");
                    $("#discountType").val("");
                    $("#discountGroupId").val("");
                    $("#discountSign").val("");
                    $("#discountGoodsPrice").val("");
                });
            }
        };
        //用户输入/修改出借金额,触发该方法
        function getAjaxDetail(){
            var $_thisRadio = $("#tzq_cont input[name='fav']:checked");
            var obj = $_thisRadio.data("discount");
            var price = $_thisRadio.data("price");
            if(!!obj){
                var $p = $(this).closest("tr");
                var money = $("#J_BIDMONEY").val();
                $.ajax({
                    url: "/deal/discountExpectedEarningInfo",
                    type: "GET",
                    data: {"dealId":$("#deal_id").val(),"money":money,"discountId":obj.discountId},
                    dataType: "json",
                    beforeSend: function() {},
                    success: function(ajaxData) {
                        tipMsg = ajaxData.discountDetail;
                        _radioselectdata_ = {};
                        _radioselectdata_["fxMoney"] = $p.find(".fx_money").text();
                        _radioselectdata_["maxMoney"] = $p.find(".max_money").text();
                        _radioselectdata_["discountTypeDesp"] = obj.discountTypeDesp;
                        _updateSelectedText_(listData,tipMsg);
                        $("#discountId").val(obj.discountId);
                        $("#discountType").val(obj.discountType);
                        $("#discountGroupId").val(obj.discountGroupId);
                        $("#discountSign").val(obj.discountSign);
                        $("#discountGoodsPrice").val(ajaxData.discountGoodPrice);
                        $("#discountGoodsType").val(obj.discountGoodsType);
                    },
                    error: function() {}
                });
            }
        };
        function cerHtml(listData,tipMsg) {
            var html = template("cer_data", listData);
            $("#tzq_cont").html(html);
            _updateSelectedText_(listData,tipMsg);
            $("#discount_" + $("#discountSign").val()).attr("checked", true);
            $(".zjq_radio").bind("change",function() {
                var $p = $(this).closest("tr");
                var obj = $(this).data("discount");
                var price = $(this).data("price");
                var _val = $("#J_BIDMONEY").val();
                if(!isNaN( _val) &&  _val>= 0){
                    getAjaxDetail(listData);
                }else{
                    tipMsg = obj.discountDetail;
                    _radioselectdata_ = {};
                    _radioselectdata_["fxMoney"] = $p.find(".fx_money").text();
                    _radioselectdata_["maxMoney"] = $p.find(".max_money").text();
                    _radioselectdata_["discountTypeDesp"] = obj.discountTypeDesp;
                    _updateSelectedText_(listData,tipMsg);
                    $("#discountId").val(obj.discountId);
                    $("#discountType").val(obj.discountType);
                    $("#discountGroupId").val(obj.discountGroupId);
                    $("#discountSign").val(obj.discountSign);
                    $("#discountDetail").val(obj.discountDetail);
                    $("#discountGoodsPrice").val(price);
                    $("#discountGoodsType").val(obj.discountGoodsType);
                }
            });
        };
        // 初始化数据
        var cerData = {
            dealId: $("#deal_id").val(),
            money: $("#J_BIDMONEY").val(),
            page: 1
        };
        reqData(cerData,1);
        reqAvaliableCount({'dealId': $("#deal_id").val()});
        //点击返现券/加息券
        $("#tzq_seclect span").on("click",function(){
            var _index = $(this).index(),type = _index+1;
            $(this).addClass("active").siblings("span").removeClass("active");
            cerData.page = 1;
            reqData(cerData,type);
            _radioselectdata_ = null;
        });
        //延迟触发方法
        var throttle =  function(idle, action){
          var last;
          return function(){
            var ctx = this, args = arguments;
            clearTimeout(last);
            last = setTimeout(function(){
                action.apply(ctx, args);
            }, idle)
          }
        };
        //计算预期收益
        var earning = function(){
            var contract_link ='<a href="javascript:;" class="protocolA"></a>';
            var borrow_money =  $("#J_BIDMONEY").val();
            if(!isNaN(borrow_money) && borrow_money>= 0){
                $("#borrow_amount").text(borrow_money+"元");
                $.getJSON("/deal/async",
                    {
                        deal_id: cerData.dealId,
                        principal: borrow_money
                    },
                    function(data) {
                        $("#earning_money").text(data.money+"元");
                        $("#earning_rate").text(data.rate+"%");
                        $("#loan_money_repay").text(data.money_repay+"元");
                        if(data.tips){
                            $("#bid_tips").html(data.tips).css({
                                'padding-top':'5px'
                            });
                        }
                        contractData=data;
                        if(contractData){
                            $('#J_bid_submit').val('同意合同和协议并出借').data('init-val','同意合同和协议并出借');
                            $('.j_protocolWrap').show();
                            for(i = 0; i < contractData.contract.length;i++){
                                $(contract_link).appendTo('.j_contract_link');
                                $('.protocolA').eq(i).html('《' + contractData.contract[i].title + '》');

                                $('.protocolA').each(function() {
                                    var idx = $(this).index();
                                    // alert(idx);
                                    $(this).click(function(){
                                        $('#articleBox').html(contractData?contractData.contract[idx].content:'');
                                        $('#contractPopMask,#contractPop').show();
                                    });
                                });
                                $('#contractPopMask,#contractPop .closeA').on('click',function () {
                                    $('#contractPopMask,#contractPop').hide();
                                });
                            }
                        }
                        
                });
                getAjaxDetail();
            }else{
                $("#earning_money").text("元");
            }
        };
        earning();
        $("#J_BIDMONEY").bind("input propertychange",throttle(500,earning));
        // $('#contractPopMask,#contractPop .closeA').on('click',function () {
        //     $('#contractPopMask,#contractPop').hide();
        // })
        // $('.protocolA').on('click',function () {
        //     var contractName=$(this).data('contractName');
        //     $('#articleBox').html(contractData?contractData[contractName]:'');
        //     $('#contractPopMask,#contractPop').show();
        // })
    })();
});
