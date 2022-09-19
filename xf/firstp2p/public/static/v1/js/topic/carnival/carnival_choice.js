seajs.use(['jquery', 'validator', 'formsubmit', 'dialog', Firstp2p.staticUrl + '/v1/js/topic/carnival/address'], function($, undefined, formsubmit, dialog) {
    // Firstp2p.preLoad(Firstp2p.staticUrl + '/v1/css/carnival_choice.css');
    $(function() {
        var is_commit = $('#is_commit').val();
        // 地区联动


        // 模块显示与隐藏
        function partToggle(type) {
            if (type == 'virtual') {
                $('#form_virtual').show();
                $('#form_practical').hide();
            } else {
                $('#form_virtual').hide();
                $('#form_practical').show();
            }
        }
        // 初始显示
        if(is_commit != 0){
            var initType = $('.award_type input').eq(0).prop('checked',true).attr('kind');
            partToggle(initType);
        }  

        // radio绑定事件
        $('.award_type input').click(function() {
            var type = $(this).attr('kind');
            partToggle(type);
        })

        // dialog
        function dialogSet(el, t, cont) {
            dialog({
                content: cont,
                okValue: '确定',
                cancelValue: '取消',
                ok: function() {
                    t.btnReset(el);
                    window.location.reload();
                },
                cancel: false
            }).show();
        }
        // 表单提交
        formsubmit('#form_virtual', {
            btnReset: function(el) {
                return this.submitBtn.removeAttr('disabled', 'disabled').css({
                    "background": "#ffb904",
                    "color": "#fff"
                }).val('确定');
            },
            ajaxFn: function(el) {
                var that = this;
                var user_id = $('#user_id').val();
                var type = $('.award_type input[name=award_type]:checked').val();
                $.ajax({
                    type: 'POST',
                    url: el.attr('action'),
                    beforeSend: function() {
                        that.btnDisable(el);
                    },
                    data: 'award_type=' + type + '&user_id=' + user_id,
                    success: function(data) {
                        var cont = '';
                        data = $.parseJSON(data);
                        if (data.state == '0') {
                            cont = data.msg;
                        } else {
                            cont = data.succ + '<br/><br/>' + data.msg;
                        }
                        dialogSet(el, that, cont);
                    },
                    error: function() {
                        var cont = '提交失败，请重新提交！';
                        dialogSet(el, that, cont);
                    }
                });
            }
        })

        formsubmit('#form_practical', {
            btnReset: function(el) {
                return this.submitBtn.removeAttr('disabled', 'disabled').css({
                    "background": "#ffb904",
                    "color": "#fff"
                }).val('确定');
            },
            ajaxFn: function(el) {
                var that = this;
                var user_id = $('#user_id').val();
                var type = $('.award_type input[name=award_type]:checked').val();
                var province = $("select[name='region_lv2'] option[value='" + $("select[name='region_lv2']").val() + "']").html();
                var city = $("select[name='region_lv3'] option[value='" + $("select[name='region_lv3']").val() + "']").html();
                var country = $("select[name='region_lv4'] option[value='" + $("select[name='region_lv4']").val() + "']").html();
                $.ajax({
                    type: 'POST',
                    url: el.attr('action'),
                    beforeSend: function() {
                        that.btnDisable(el);
                    },
                    data: $(el).serialize() + '&award_type=' + type + '&user_id=' + user_id + '&province=' + province + '&user_city=' + city + '&country=' + country,
                    success: function(data) {
                        var cont = '';
                        data = $.parseJSON(data);
                        if (data.state == '0') {
                            cont = data.msg;
                        } else {
                            cont = data.succ + '<br/><br/>' + data.msg;
                        }
                        dialogSet(el, that, cont);
                    },
                    error: function() {
                        var cont = '提交失败，请重新提交！';
                        dialogSet(el, that, cont);
                    }
                });
            }
        })

    })
})