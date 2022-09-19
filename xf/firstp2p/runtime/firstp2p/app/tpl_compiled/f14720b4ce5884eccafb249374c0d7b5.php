<?php if ($this->_var['user_info']): ?>
<ul class="fr nav">
    <li id="liIner46782_account_Li">
        <div class="ztx_liIner46782_box act">
            <div class="inner">
                <a href="/account" class="ztx_liIner46782_NavA">您好，<?php if (! isset ( $this->_var['isEnterprise'] ) || ! $this->_var['isEnterprise']): ?><?php if (empty ( $this->_var['user_info']['real_name'] )): ?><?php echo $this->_var['user_info']['user_name']; ?><?php else: ?><?php echo $this->_var['user_info']['real_name']; ?><?php endif; ?><?php else: ?><?php echo $this->_var['enterpriseInfo']['company_name']; ?><?php endif; ?></a>
            </div>
            <div class="cont ztx_liIner46782_act">
            	<div class="dataPanel">
                    <div class="ye46782"><label>可用余额：</label><span>{{money}}元</span></div>
                    <div class="hb"><label>含红包：</label><span>{{bonus}}元</span></div>
                    <div class="bntBox">
                        <a href="/account/charge" class="cz">充值
                        </a><a href="/account/carry" class="tx">提现</a>
                    </div>
                    <ul class="ztx_liIner46782_ul">
                        <li>待收本金：{{principal}}元</li>
                        <li>待收收益：{{interest}}元</li>
                        <li>待获返利：{{coupon}}元</li>
                    </ul>
                    <div class="accountABox">
                        <a href="/account" class="accountA">进入我的账户</a>
                    </div>
                </div>
                <div class="errorPanel"></div>
            </div>
        </div>
        <script type="text/javascript">
            (function(){
                var accountLi=$('#liIner46782_account_Li');
                var dataPanel=accountLi.find('.dataPanel:first');//展现数据面板
				var errorPanel=accountLi.find('.errorPanel:first');//错误数据面板
                /**
                 *  面板展示控制，hide表示展现数据，隐藏loading效果；load表示loading效果修改成提示重新加载效果;login表示登陆超时，提示请重新登录
                 */
                function drawPanel(callType,data){
                    var box=dataPanel;
                    var setters={
                        'data':function(){//展现数据
                            var html=box.html();
                            var regObj=null;
                            $.each(data,function(key,val){
                                regObj=new RegExp('{{'+key+'}}','g');
                                html=html.replace(regObj,val);
                            });
                            box.html(html);
							box.show();errorPanel.hide();
                        },
                        'reload':function(){//重新加载
                            errorPanel.html('<p>加载失败，请<a href="javascript:;">重试</a></p>').removeClass('loading');
                            errorPanel.find('a').on('click',function(){
                                getDataAjax();
                            });
                        },
                        'login':function(){//重新登录
							var tarHref=encodeURIComponent(location.href);
                            errorPanel.html('<p>登录超时，请重新<a href="/user/login">登录</a></p>').removeClass('loading');
							if(!/^\s*\/\s*$/.test(location.pathname) || !/^\s*$/.test(location.search)){
								errorPanel.find('a').attr('href',"/user/login?backurl="+tarHref);
							}
                        }
                    }
                    var args = Array.prototype.slice.apply(arguments, [1]);
                    setters[callType].apply(this,args);
                }

                /**
                 * 异步读取个人中心数据函数
                 */
                function getDataAjax(){
                    errorPanel.html('').addClass('loading');
                    $.ajax({
                        type: "get",
                        timeout : 15000,
                        url:'/index/account',
                        dataType: "json",
                        success: function(returnVal) {
                            var data=returnVal.data;
                            if(returnVal.status!=0){
                                drawPanel('data',data);
                            }else if(returnVal.status==0){
                                if(returnVal.info=="登录信息过期，请重新登录"){
                                    drawPanel('login');
                                }else{
                                    drawPanel('reload');
                                }
                            }
                        },
                        error:function(){
                            drawPanel('reload');
                        }
                    });
                }
                accountLi.one("mouseenter",function(){
                    getDataAjax();
                });
            })();
        </script>
	</li>
    <li><a href="<?php
echo parse_url_tag("u:shop|user/loginout|"."".""); 
?>" class="ztx_liIner46782_NavA">退出</a></li>
    <li>
        <div class="ztx_liIner46782_box msg <?php if (! isset ( $this->_var['msg_count'] ) || $this->_var['msg_count'] <= 0): ?>disabled<?php endif; ?>">
            <div class="inner">
                <a href="/message" class="ztx_liIner46782_NavA">消息</a>
                <?php if (isset ( $this->_var['msg_count'] ) && $this->_var['msg_count'] > 0): ?>
    <span class="message_num"><span class="m_lbg"></span><span class="m_rbg"><?php echo $this->_var['msg_count']; ?></span></span>
                <?php endif; ?>
            </div>
            <?php if (isset ( $this->_var['msg_count'] ) && $this->_var['msg_count'] > 0): ?>
            <div class="cont ztx_liIner46782_msg">
            	<?php $_from = $this->_var['msg_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'msg');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['msg']):
?>
                <a href="/message/deal/<?php echo $this->_var['msg']['group_key']; ?>"><?php echo $this->_var['msg']['total']; ?>&nbsp;条&nbsp;<?php if (isset ( $this->_var['msg_title'][$this->_var['msg']['is_notice']] )): ?>
                    <?php echo $this->_var['msg_title'][$this->_var['msg']['is_notice']]; ?>
                    <?php else: ?>
                    <?php echo $this->_var['LANG']['SYSTEM_PM']; ?>
                    <?php endif; ?></a>
  				<?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
            </div>
            <?php endif; ?>
        </div>
    </li>
    <li style="padding-left:0;"><a class="border_l ztx_liIner46782_NavA pl20" target="_blank" href="<?php
echo parse_url_tag("u:index|guide|"."".""); 
?>">新手指南</a></li>
    <li><a class="ztx_liIner46782_NavA" target="_blank" href="http://app.firstp2p.com/">手机客户端</a></li>
</ul>
<?php else: ?>
<ul class="fr nav">
    <li>您好，请<a href="<?php
echo parse_url_tag("u:shop|user/login|"."".""); 
?>">登录</a></li>
    <li><a class="color_green" href="<?php
echo parse_url_tag("u:shop|user/register|"."".""); 
?>">免费注册</a></li>
    <?php if ($this->_var['help_title'] == '新手指南'): ?>
    <li><a class="border_l pl20" target="_blank" href="<?php
echo parse_url_tag("u:index|guide|"."".""); 
?>"><?php echo $this->_var['help_title']; ?></a></li>
    <?php else: ?>
    <li><a href="<?php
echo parse_url_tag("u:index|helpcenter|"."".""); 
?>">新手指南</a></li>
    <?php endif; ?>
 <li><a class="ztx_liIner46782_NavA" target="_blank" href="http://app.firstp2p.com/">手机客户端</a></li>
</ul>
<?php endif; ?>
