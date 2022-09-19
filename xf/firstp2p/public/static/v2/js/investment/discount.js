;
(function($) {
    //合同展开收起
    $(function() {
        (function() {
            // 投资确认页关闭提示组件
            $(".wrap").addClass("p_dealbid");
            $(".p_dealbid").tooltip({
                disabled: true
            });
            /************* 投资券 *************/
            // ajax请求
            function reqData(data) {
                var pageText = '';
                $.ajax({
                    url: '/deal/discountPickList',
                    type: 'GET',
                    data: data,
                    dataType: 'json',
                    beforeSend: function() {},
                    success: function(result) {
                        // console.log(JSON.stringify(result));
                        $("#tzq_choose").show();
                        cerHtml(result);
                        if (result.pagecount <= 0) {
                            $("#pagination").hide();
                            return;
                        } else {
                            $("#pagination").show();
                            Firstp2p.paginate($("#pagination"), {
                                pages: result.pagecount,
                                currentPage: result.page,
                                onPageClick: function(pageNumber, $obj) {
                                    cerData["page"] = pageNumber;
                                    reqData(cerData);
                                }
                            });
                            pageText = '<li style="line-height:25px;">' + result.count + ' 条记录 ' + result.page + '/' + result.pagecount + ' 页</li>';
                            $("#pagination").find("ul").prepend(pageText);
                        }
                    },
                    error: function() {}
                })
            }

            // 数据拼接
            var _radioselectdata_ = null;
            function _updateSelectedText_(data) {
                var spanTxt = '';
                if (_radioselectdata_ == null) return;
                spanTxt = '<i></i>可获返利 <span class="color-yellow1">' + _radioselectdata_['fxMoney'] + '元</span> ' + _radioselectdata_['discountTypeDesp'] + '，金额满 <span class="color-yellow1">' + _radioselectdata_['maxMoney'] + '元</span> 可用 <a href="javascript:void(0)" class="blue pl15" id="cancel_choose">取消选择</a>';
                $("#tzq_tit").html(spanTxt);
                $("#tzq_tit").on("click", "#cancel_choose", function() {
                    $(".zjq_radio").removeAttr("checked");
                    $("#tzq_tit").html('<i></i>您有 <span class="color-yellow1">' + data.count + '</span> 张投资券可用');
                    _radioselectdata_ = null;
                    $("#discountId").val("");
                    $("#discountGroupId").val("");
                    $("#discountSign").val("");
                    $("#discountGoodsPrice").val("");
                });
            }
            function cerHtml(data) {
                var html = template('cer_data', data);
                $('#tzq_choose').html(html);
                _updateSelectedText_(data);
                $("#discount_" + $("#discountSign").val()).attr("checked", true);
                $(".zjq_radio").bind("change",function() {
                    var $p = $(this).closest("tr");
                    var obj = $(this).data("discount");
                    _radioselectdata_ = {};
                    _radioselectdata_['fxMoney'] = $p.find(".fx_money").text();
                    _radioselectdata_['maxMoney'] = $p.find(".max_money").text();
                    _radioselectdata_['discountTypeDesp'] = obj.discountTypeDesp;
                    _updateSelectedText_(data);
                    $("#discountId").val(obj.discountId);
                    $("#discountGroupId").val(obj.discountGroupId);
                    $("#discountSign").val(obj.discountSign);
                    $("#discountGoodsPrice").val(obj.discountGoodsPrice);
                    $("#discountGoodsType").val(obj.discountGoodsType);
                });
            }
            // 初始化数据
            var cerData = {
                dealId: $("#deal_id").val(),
                money: $("#J_BIDMONEY").val(),
                page: 1
            }
            reqData(cerData);
        })();
    })
})(jQuery);
