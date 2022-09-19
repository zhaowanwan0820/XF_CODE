;(function($){
     $(function() {
        P2PWAP.ui.ModalHeight_ = window.innerHeight;
        P2PWAP.ui.ModalViewEls_ = [];
        P2PWAP.ui.addModalView = function(instance) {
            if (!window.navigator || !/iphone.*OS 7.*/i.test(window.navigator.userAgent)) return;
            window.scrollTo(0, 0);
            if (P2PWAP.ui.ModalViewEls_.length == 0) {
                document.body.style.height = P2PWAP.ui.ModalHeight_ + "px";
                document.body.style.overflow = "hidden";
            }
            P2PWAP.ui.ModalViewEls_.push(instance);
            instance.style.height = P2PWAP.ui.ModalHeight_ + "px";
        };
        P2PWAP.ui.removeModalView = function(instance) {
            if (!window.navigator || !/iphone.*OS 7.*/i.test(window.navigator.userAgent)) return;
            var index = P2PWAP.ui.ModalViewEls_.indexOf(instance);
            if (index >= 0) {
                P2PWAP.ui.ModalViewEls_.splice(index, 1);
            }
            if (P2PWAP.ui.ModalViewEls_.length == 0) {
                document.body.style.height = "auto";
                document.body.style.overflow = "auto";
            }
        };
        P2PWAP.ui.showNoticeDialog = function(title,content,type) {
            var div = document.createElement('div');
            var html = '';
            html += '<div class="ui_max_width">';
            html += '    <div class="ui_dialog hd_dialog">';
            html += '        <div class="dialog_con">';
            html += '            <div class="fz_protocol">';
            html += '            <div class="fz_title">' + title + '';
            html += '            </div>';
            html += '                <div class="fz_transferPt_text_sc" style="display:none;">';
            html += '                   <div class="respectUser">尊敬的用户：</div>';
            html += '                   <div class="detail">由于公司战略调整，网站升级，您需要阅读本说明的全部内容，并授权同意更新后的网站注册协议，本次更新不会影响您此前在平台投融资的所有项目及操作的各类事项，您与相关各方在平台上达成的协议全部继续有效。</div>';
            html += '                </div>';
            html += '                </div>';
            html += '            <div class="dialog_text">';
            html += '                <div class="iscroll_inner">';
            html += '                <div class="ggw_text">';
            html +=                      content;
            html += '                    <p class="zw_p">&nbsp;</p>';
            html += '                    <p class="zw_big">&nbsp;</p>';
            html += '                    <p class="zw_sam">&nbsp;</p>';
            html += '                    <p class="zw_sam">&nbsp;</p>';
            html += '                    <p class="zw_sam">&nbsp;</p>';
            html += '                </div>';
            html += '                </div>';
            html += '            </div>';
            html += '            <div class="zw_height" style="height:117px;">';
            html += '            </div>';
            html += '          <div class="dialog_bottom">';
            html += '            <div class="regis_dialog_btn j_know">我知道了</div>';
            html += '            <div><button class="transf_dialog_btn" type="submit">同意</button></div>';
            html += '            <div class="kfTel"><a href="javascript:void(0);">不同意，请联系客服</a></div>';
            html += '            <div class="j_registerPt"><a href="javascript:void(0);">《注册协议》</a></div>';
            html += '           </div>';
            html += '        </div>';
            html += '    </div>';
            html += '</div>';
            div.innerHTML = html;
            div.className = 'transfer_ui_mask' + type;
            $('#d_form').append(div);
            function dialogTopFix(){
                var $dialog = $(div).find('.ui_dialog');
            }
            $(div).find('.dialog_btn').click(function(event) {
                    P2PWAP.ui.removeModalView(div);
                    $(div).remove();
                });
            dialogTopFix();
            $(window).resize(function(){
                dialogTopFix();
            });
            P2PWAP.ui.addModalView(div);
                var myScroll_id = new IScroll($(div).find('.dialog_text')[0],{
                    scrollbars: true,
                    interactiveScrollbars: true,
                    shrinkScrollbars: 'scale',
                    fadeScrollbars: true
                });
        };
        P2PWAP.ui.showNoticeDialog('网站用户共享授权声明',$("#transferAgreement").val(),1);
        $("body").on("touchmove" , ".dialog_bottom" , function(event){
           event.preventDefault();
        });

        $('body').on('touchstart', '.j_registerPt a',function () {
            P2PWAP.ui.showNoticeDialog('注册协议',$("#registerAgreement").val(),2);
        });
        $('body').on('touchstart', '.j_know',function (event) {
            $('.transfer_ui_mask2').remove();
            event.preventDefault();
        });

        var wapsn_h = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
        $("body .transfer_ui_mask1 .fz_transferPt_text_sc").css("height",wapsn_h-294 + "px");
    });
})(Zepto);