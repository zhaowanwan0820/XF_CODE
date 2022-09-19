<?php echo $this->fetch('web/views/v2/header.html'); ?>
    <?php if ($this->_var['NEW_YEAR_SKIN_2016'] == "1"): ?>
    <style>
    body{background:url(<?php echo $this->asset->makeUrl('v2/images/index/mainbg.png');?>) repeat;}
    .p_index{background:url(<?php echo $this->asset->makeUrl('v2/images/index/chunjie_bg.png');?>) no-repeat center 492px;}
    .p_index .announcement{padding-bottom:20px; border-bottom: none;background:url(<?php echo $this->asset->makeUrl('v2/images/index/wen.png');?>) repeat-x center bottom #fff;}
    .p_index .ui_product_tab.mt20{margin-top: 0}
    </style>
    <?php else: ?>
    <?php endif; ?>
    <?php echo $this->asset->renderJsV2("index_v2"); ?>
    <link href="<?php echo $this->asset->makeUrl('v2/css/weebox.css');?>" type="text/css" rel="stylesheet">
    <div class="p_index">
    	<section>

       <?php 
$k = array (
  'name' => 'get_adv',
  'x' => '首页banner_2015',
);
echo $k['name']($k['x']);
?>
    	<div class="banner_slide">
        	<ul class="banner_view">
            </ul>
            <div class="slide_pager">
            	<div class="slide_pager_l"></div>
                <div class="slide_pager_r">
                	<ul>
                    </ul>
                </div>
            </div>
        </div>
        </section>
        <section class="announcement f16">
            <span class="pr40">目前为止我们为投资者带来收益 <em class="color_red"><span class="f18"><?php echo $this->_var['deals_income_view']['income_sum']; ?></span>元</em></span>即将带来收益 <em class="color_red"><span class="f18"><?php echo $this->_var['deals_income_view']['income_plan_sum']; ?></span>元</em>
        </section>
        <?php if ($this->_var['duotou']): ?>
        <section class="main">
            <div class="ui_product_tab box mb30 mt20">
                <div class="product_type">
                    <div class="fl title"><i class="icon_line mr15"></i>智多鑫</div>
                    <div class="mr14"><a class="new_more" href="/finplan/lists" target="_blank"></a></div>
                </div>
                <div class="tabbd">
                    <div class="con">
                        <div class="conbd">

                            <div class="p2p_product p5">
                                <div class="clearfix bg_whtie">
                                    <div class="con_l">
                                        <h3 class="f16">
                                        <i class="icon_WTDX" style="display:none;"></i>
                                            <a href="/finplan/<?php echo $this->_var['duotou']['id']; ?>" title="<?php echo $this->_var['duotou']['name']; ?>" alt="<?php echo $this->_var['duotou']['name']; ?>" class="deal_tag_name"><?php 
$k = array (
  'name' => 'msubstr',
  'v' => $this->_var['duotou']['name'],
  'f' => '0',
  'l' => '20',
);
echo $k['name']($k['v'],$k['f'],$k['l']);
?></a>
                                        <?php if (isset ( $this->_var['duotou']['tagBeforeName'] ) && $this->_var['duotou']['tagBeforeName'] != ''): ?>
                                        <i class="deal_tips bg_blue" title="<?php echo $this->_var['duotou']['tagBeforeDesc']; ?>"><?php echo $this->_var['duotou']['tagBeforeName']; ?></i>
                                        <?php endif; ?>

                                        <?php if (isset ( $this->_var['duotou']['tagAfterName'] ) && $this->_var['duotou']['tagAfterName'] != ''): ?>
                                        <i class="deal_tips bg_blue" title="<?php echo $this->_var['duotou']['tagAfterDesc']; ?>"><?php echo $this->_var['duotou']['tagAfterName']; ?></i>
                                        <?php endif; ?>
                                        </h3>
                                        <div class="fl w360">
                                            <p><span>年化收益率：</span>
                                                <span class="f20"><i><?php 
$k = array (
  'name' => 'number_format',
  'v' => $this->_var['duotou']['projectInfo']['rateYear'],
  'f' => '2',
);
echo $k['name']($k['v'],$k['f']);
?></i>&nbsp;%</span>
                                            <p>
                                                <span>起投金额：</span> <?php echo $this->_var['duotou']['projectInfo']['minLoanMoney']; ?>元
                                            </p>
                                        </div>
                                        <div class="fl w265 progress_rate" total="1000" has="111.0000">
                                        <p><span>计息方式：</span>
                                            <em>按日计息</em></p>
                                        <p><span>收益方式：</span>一次性还本，按月付息</p>
                                        </div>
                                        <div class="fl w265 progress_rate" >
                                            <p><span>管理费：</span> <?php if ($this->_var['duotou']['projectInfo']['feeDays'] > 0 && $this->_var['duotou']['projectInfo']['feeRate'] > 0): ?>年化<?php 
$k = array (
  'name' => 'number_format',
  'v' => $this->_var['duotou']['projectInfo']['feeRate'],
  'f' => '2',
);
echo $k['name']($k['v'],$k['f']);
?>% （持有满<?php echo $this->_var['duotou']['projectInfo']['feeDays']; ?>天免费）<?php else: ?>免费<?php endif; ?> </p>
                                        </div>
                                    </div>
                                    <div class="product_btn">
                                        <?php if ($this->_var['duotou']['isShow'] == 1 && $this->_var['duotou']['isEffect'] == 1): ?>
                                            <a href="/finplan/<?php echo $this->_var['duotou']['id']; ?>" class="btn_touzi">投资</a>
                                        <?php else: ?>
                                            <span class="btn_manbiao">投资</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- //循环 -->
                        </div>
                    </div>
                </div>
            </div>
            <link rel="stylesheet" type="text/css" href="<?php echo $this->asset->makeUrl('v2/js/widget/paginate/paginate.v1.css');?>">
        </section>

        <?php endif; ?>

        <section class="w1100">
            <div class="ui_product_tab box mt20 mb30" id="tabs">
                <div class="product_type">
                    <div class="fl title"><i class="icon_line mr15"></i>网贷理财</div>

                    <div class="mr14"><a class="new_more" href="/deals" target="_blank"></a></div>

                    <!--div class="fg_line"></div>
                    <ul class="nav indexlist-tab-num<?php echo $this->_var['count']; ?>">
                        <?php $_from = $this->_var['deal_type']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'type');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['type']):
?>
                        <li class=" j_index_tab" data-id="<?php echo $this->_var['key']; ?>"><a href="javascript:void(0)" title="<?php echo $this->_var['type']['name']; ?>"><?php echo $this->_var['type']['name']; ?></a></li>
                        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                    </ul-->
                </div>
                <div class="tabbd">

                    <div class="con">
                        <div class="conbd">
                        <?php $_from = $this->_var['deals_list']['list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'deal');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['deal']):
?>


                            <div class="p2p_product p5">
                                <div class="clearfix bg_whtie">
                                    <div class="con_l">
                                        <h3 class="f16">
                                            <!--icon-->
                                            <?php if ($this->_var['deal']['type_id'] == 11): ?>
                                            <i class="icon_CD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 12): ?>
                                            <i class="icon_FD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 13): ?>
                                            <i class="icon_QYD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 14): ?>
                                            <i class="icon_GRD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 15): ?>
                                            <i class="icon_ZRD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 16): ?>
                                            <i class="icon_CRD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 17): ?>
                                            <i class="icon_YSD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 18): ?>
                                            <i class="icon_DDD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 19): ?>
                                            <i class="icon_XSD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 21): ?>
                                            <i class="icon_ZSZD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 22): ?>
                                            <i class="icon_YLD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 23): ?>
                                            <i class="icon_YRD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 24): ?>
                                            <i class="icon_KZD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 25): ?>
                                            <i class="icon_GYD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 26): ?>
                                            <i class="icon_CYD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 27): ?>
                                            <i class="icon_TZD"></i>
                                            <?php endif; ?>
                                            <?php if ($this->_var['deal']['type_id'] == 29): ?>
                                            <i class="icon_XFDD"></i>
                                            <?php endif; ?>
                                            <!--/icon-->
                                            <?php if ($this->_var['deal']['bid_flag'] == 1): ?>
                                            <a title="<?php echo $this->_var['deal']['old_name']; ?>" alt="<?php echo $this->_var['deal']['old_name']; ?>"  href="<?php echo $this->_var['deal']['url']; ?>" target="_blank" ><?php echo $this->_var['deal']['name']; ?></a>
                                            <?php else: ?>
                                            <span title="<?php echo $this->_var['deal']['old_name']; ?>" alt="<?php echo $this->_var['deal']['old_name']; ?>" class="deal_tag_name"><?php echo $this->_var['deal']['name']; ?></span>
                                            <?php endif; ?>
                                            <?php if (isset ( $this->_var['deal']['deal_tag_name'] ) && $this->_var['deal']['deal_tag_name'] != ''): ?>
                                            <i class="deal_tips bg_blue" title="<?php echo $this->_var['deal']['deal_tag_desc']; ?>"><?php echo $this->_var['deal']['deal_tag_name']; ?></i>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="fl w360">
                                            <p><span>年化收益率：
                                                <i class="f20">
                                                <?php if ($this->_var['deal']['income_ext_rate'] == 0): ?>
                                                    <?php echo $this->_var['deal']['rate_show']; ?>
                                                <?php else: ?>
                                                    <?php echo $this->_var['deal']['income_base_rate']; ?>
                                                <?php endif; ?></i><ins class="f20">%</ins>
                                                <?php if ($this->_var['deal']['type_id'] == 27): ?>起<?php endif; ?>
                                                <?php if ($this->_var['deal']['income_ext_rate'] != 0): ?>
                                                +<i><?php echo $this->_var['deal']['income_ext_rate']; ?></i>&nbsp<ins>%</ins>
                                                <?php endif; ?>
                                            </span></p>
                                            <p>
                                                <span>总额：</span>
                                                <?php echo $this->_var['deal']['borrow_amount_format_detail']; ?>万
                                            </p>

                                        </div>
                                        <div class="fl w265">
                                            <p><span>投资期限：</span>
                                            <?php if ($this->_var['deal']['loantype'] == 5): ?>
                                                <em><i class="f18"><?php if ($this->_var['deal']['deal_type'] == 1): ?><?php 
$k = array (
  'name' => 'plus',
  'x' => $this->_var['deal']['lock_period'],
  'y' => $this->_var['deal']['redemption_period'],
);
echo $k['name']($k['x'],$k['y']);
?>~<?php endif; ?><?php echo $this->_var['deal']['repay_time']; ?></i>天</em>
                                            <?php else: ?>
                                            <em><i class="f18"><?php echo $this->_var['deal']['repay_time']; ?></i>个月</em>
                                            <?php endif; ?>
                                            </p>
                                            <p>
                                                <span>收益方式：</span>
                                                <?php if ($this->_var['deal']['deal_type'] == 1): ?>
                                                提前<?php echo $this->_var['deal']['redemption_period']; ?>天申请赎回
                                                <?php else: ?>
                                                <?php echo $this->_var['deal']['loantype_name']; ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="fl w265 progress_rate" total="<?php echo $this->_var['deal']['borrow_amount']; ?>" has="<?php echo $this->_var['deal']['load_money']; ?>">
                                            <p>
                                                <span>投资进度：</span>
                                                <span class="progress">
                                                    <i class="ico_bace"></i>
                                                    <i class="ico_yitou">进度条</i>
                                                </span><ins class="f12 pl5"></ins>
                                            </p>
                                            <p><span>剩余可投：</span>&nbsp;<?php echo $this->_var['deal']['need_money_detail']; ?>元</p>
                                        </div>
                                    </div>
                                    <div class="product_btn">

                                        <?php if ($this->_var['deal']['deal_type'] == 0 || $this->_var['deal']['deal_type'] == 3): ?>
                                        <?php if ($this->_var['deal']['is_crowdfunding'] == 0): ?>
                                        <?php if ($this->_var['deal']['is_update'] == 1): ?>
                                        <a href="#" class="btn_touzi">查看</a>
                                        <?php elseif ($this->_var['deal']['deal_status'] == 4): ?>
                                        <span class="btn_manbiao">还款中</span>
                                        <?php elseif ($this->_var['deal']['deal_status'] == 0): ?>
                                        <a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">查看</a>
                                        <?php elseif ($this->_var['deal']['deal_status'] == 2): ?>
                                        <span class="btn_manbiao">满标</span>
                                        <?php elseif ($this->_var['deal']['deal_status'] == 5): ?>
                                        <span class="btn_manbiao">已还清</span>
                                        <?php else: ?>
                                        <?php if ($this->_var['deal']['type_id'] != 25): ?>
                                            <a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">投资</a>
                                        <?php else: ?>
                                            <a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">捐赠</a>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <?php elseif ($this->_var['deal']['is_crowdfunding'] == 1): ?>
                                        <?php if ($this->_var['deal']['is_update'] == 1): ?>
                                        <a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">查看</a>
                                        <?php elseif ($this->_var['deal']['deal_status'] == 4): ?>
                                        <span class="btn_manbiao">已成功</span>
                                        <?php elseif ($this->_var['deal']['deal_status'] == 0): ?>
                                        <a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">查看</a>
                                        <?php elseif ($this->_var['deal']['deal_status'] == 2): ?>
                                        <span class="btn_manbiao">已成功</span>
                                        <?php elseif ($this->_var['deal']['deal_status'] == 5): ?>
                                        <span class="btn_manbiao">已成功</span>
                                        <?php else: ?>
                                        <a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">捐赠</a>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <?php if ($this->_var['deal']['deal_status'] == 0): ?>
                                        <a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">查看</a>
                                        <?php endif; ?>
                                        <?php if ($this->_var['deal']['deal_status'] == 1): ?>
                                        <?php if ($this->_var['deal']['deal_compound_status'] == 1): ?>
                                        <a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">已投</a>
                                        <?php else: ?>
                                        <a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">投资</a>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($this->_var['deal']['deal_status'] == 2): ?>
                                        <span class="btn_manbiao">满标</span>
                                        <?php endif; ?>
                                        <?php if ($this->_var['deal']['deal_status'] == 4): ?>
                                        <?php if ($this->_var['deal']['deal_compound_status'] == 2): ?>
                                        <!--<a href="<?php echo $this->_var['deal']['url']; ?>" class="btn_touzi">待赎回</a>-->
                                        <span class="btn_manbiao">待赎回</span>
                                        <?php endif; ?>
                                        <?php if ($this->_var['deal']['deal_compound_status'] == 3): ?>
                                        <span class="btn_manbiao">还款中</span>
                                        <?php endif; ?>
                                        <?php if ($this->_var['deal']['deal_compound_status'] == 4): ?>
                                        <span class="btn_manbiao">已还清</span>
                                        <?php endif; ?>
                                        <?php if ($this->_var['deal']['deal_compound_status'] == 0): ?>
                                        <span class="btn_manbiao">还款中</span>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($this->_var['deal']['deal_status'] == 5): ?>
                                        <span class="btn_manbiao">已还清</span>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                        </div>
                        <div class="deal_more"><a href="/deals<?php if ($this->_var['key1']): ?>?cate=<?php echo $this->_var['key1']; ?><?php endif; ?>">点击查看更多</a></div>
                    </div>
                </div>
            </div>

            <!-- 投资理财 -->
            <?php if (( $this->_var['show_btx_list'] == 0 ) || ( ( $this->_var['bxt_list']['0']['deal_status'] != 1 ) && ( $this->_var['bxt_list']['1']['deal_status'] != 1 ) && ( $this->_var['bxt_list']['2']['deal_status'] != 1 ) )): ?>

            <div class="ui_product_tab box mt20 mb30" id="tzlc" style="display:none">
            <?php else: ?>
            <div class="ui_product_tab box mt20 mb30" id="tzlc">
            <?php endif; ?>
                <div class="product_type">
                    <div class="fl title"><i class="icon_line mr15"></i>专享理财</div>
                    <div class="mr14"><a class="new_more" href="/touzi" target="_blank"></a></div>
                </div>
                <div class="tabbd">
                    <div class="con">
                        <div class="conbd">
                        <!-- 循环 -->
                            <?php $_from = $this->_var['bxt_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'bxt');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['bxt']):
?>
                            <div class="p2p_product p5" >
                                <div class="clearfix bg_whtie">
                                    <div class="con_l">
                                        <h3 class="f16">
                                        <i class="icon_WTDX" style="display:none;"></i>
                                            <?php if ($this->_var['bxt']['bid_flag'] == 1): ?>
                                                <a title="<?php echo $this->_var['bxt']['old_name']; ?>" alt="<?php echo $this->_var['bxt']['old_name']; ?>"  href="<?php echo $this->_var['bxt']['url']; ?>" target="_blank" ><?php echo $this->_var['bxt']['name']; ?></a>
                                            <?php else: ?>
                                                <span title="<?php echo $this->_var['bxt']['old_name']; ?>" alt="<?php echo $this->_var['bxt']['old_name']; ?>" class="deal_tag_name"><?php echo $this->_var['bxt']['name']; ?></span>
                                            <?php endif; ?>
                                            <?php if (isset ( $this->_var['bxt']['deal_tag_name'] ) && $this->_var['bxt']['deal_tag_name'] != ''): ?>
                                                <i class="deal_tips bg_blue" title="<?php echo $this->_var['bxt']['deal_tag_desc']; ?>"><?php echo $this->_var['bxt']['deal_tag_name']; ?></i>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="fl w360">
                                            <p><span>年化收益率：</span>
                                            <?php if ($this->_var['bxt']['income_base_rate'] == $this->_var['bxt']['max_rate']): ?>
                                                <span class="f20"><i><?php echo $this->_var['bxt']['max_rate']; ?></i>&nbsp;%</span>
                                            <?php else: ?>
                                                <span class="f20"><i><?php echo $this->_var['bxt']['income_base_rate']; ?></i>&nbsp;%～<i><?php echo $this->_var['bxt']['max_rate']; ?></i>&nbsp;%</span>
                                            <?php endif; ?>
                                            </p>
                                            <p>
                                                <span>总额：</span> <?php echo $this->_var['bxt']['borrow_amount_format_detail']; ?>万
                                            </p>
                                        </div>
                                        <div class="fl w265">
                                            <p><span>投资期限：</span>
                                                <?php if ($this->_var['bxt']['loantype'] == 5): ?>
                                                    <em><i class="f18"><?php if ($this->_var['bxt']['deal_type'] == 1): ?><?php 
$k = array (
  'name' => 'plus',
  'x' => $this->_var['bxt']['lock_period'],
  'y' => $this->_var['bxt']['redemption_period'],
);
echo $k['name']($k['x'],$k['y']);
?>~<?php endif; ?><?php echo $this->_var['bxt']['repay_time']; ?></i>天</em>
                                                <?php else: ?>
                                                    <em><i class="f18"><?php echo $this->_var['bxt']['repay_time']; ?></i>个月</em>
                                                <?php endif; ?>
                                            </p>
                                            <p>
                                                <span>收益方式：</span> <?php echo $this->_var['bxt']['loantype_name']; ?> </p>
                                        </div>
                                        <div class="fl w265 progress_rate" total="<?php echo $this->_var['bxt']['borrow_amount']; ?>" has="<?php echo $this->_var['bxt']['load_money']; ?>">
                                            <p>
                                                <span>投资进度：</span>
                                                <span class="progress">
                                                <i class="ico_bace"></i>
                                                <i class="ico_yitou" style="width: 50%;">进度条</i>
                                            </span>
                                                <ins class="f12 pl5"></ins>
                                            </p>
                                            <p><span>剩余可投：</span>&nbsp;<?php echo $this->_var['bxt']['need_money_detail']; ?>元</p>
                                        </div>
                                    </div>
                                    <div class="product_btn">
                                        <?php if ($this->_var['bxt']['deal_type'] == 0 || $this->_var['bxt']['deal_type'] == 3): ?>
                                            <?php if ($this->_var['bxt']['is_crowdfunding'] == 0): ?>
                                                <?php if ($this->_var['bxt']['is_update'] == 1): ?>
                                                    <a href="#" class="btn_touzi">查看</a>
                                                <?php elseif ($this->_var['bxt']['deal_status'] == 4): ?>
                                                    <span class="btn_manbiao">还款中</span>
                                                <?php elseif ($this->_var['bxt']['deal_status'] == 0): ?>
                                                    <a href="<?php echo $this->_var['bxt']['url']; ?>" class="btn_touzi">查看</a>
                                                <?php elseif ($this->_var['bxt']['deal_status'] == 2): ?>
                                                    <span class="btn_manbiao">满标</span>
                                                <?php elseif ($this->_var['bxt']['deal_status'] == 5): ?>
                                                    <span class="btn_manbiao">已还清</span>
                                                <?php else: ?>
                                                    <a href="<?php echo $this->_var['bxt']['url']; ?>" class="btn_touzi">投资</a>
                                                <?php endif; ?>
                                            <?php elseif ($this->_var['bxt']['is_crowdfunding'] == 1): ?>
                                                <?php if ($this->_var['bxt']['is_update'] == 1): ?>
                                                    <a href="<?php echo $this->_var['bxt']['url']; ?>" class="btn_touzi">查看</a>
                                                <?php elseif ($this->_var['bxt']['deal_status'] == 4): ?>
                                                    <span class="btn_manbiao">已成功</span>
                                                <?php elseif ($this->_var['bxt']['deal_status'] == 0): ?>
                                                    <a href="<?php echo $this->_var['bxt']['url']; ?>" class="btn_touzi">查看</a>
                                                <?php elseif ($this->_var['bxt']['deal_status'] == 2): ?>
                                                    <span class="btn_manbiao">已成功</span>
                                                <?php elseif ($this->_var['bxt']['deal_status'] == 5): ?>
                                                    <span class="btn_manbiao">已成功</span>
                                                <?php else: ?>
                                                    <a href="<?php echo $this->_var['bxt']['url']; ?>" class="btn_touzi">筹款</a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                            <!-- //循环 -->
                        </div>
                        <div class="deal_more"><a href="/touzi/">点击查看更多</a></div>
                    </div>
                </div>
            </div>
            <?php if (app_conf ( 'TEMPLATE_ID' ) == '1' && app_conf ( 'SHOW_FUNDING' ) == '1' && ! $this->_var['isEnterprise']): ?>
            <div class="ui_product_tab box mt20 mb30 ui_fund">
                <div class="product_type">
                  <div class="fl title"><i class="icon_line mr15"></i>基金理财</div>
                  <div class="mr14"><a class="new_more" href="/jijin" target="_blank"></a></div>
                </div>
                <div class="con">
                    <?php $_from = $this->_var['fund_list']['list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'fund');if (count($_from)):
    foreach ($_from AS $this->_var['fund']):
?>
                    <div class="p2p_product p5">
                        <div class="clearfix bg_whtie">
                            <div class="con_l">
                                <h3 class="f16">
                                    <i class="icon_JJD"></i>
                                    <?php if ($this->_var['fund']['status'] == 1): ?>
                                        <a title="<?php echo $this->_var['fund']['name']; ?>" alt="<?php echo $this->_var['fund']['name']; ?>"  href="/jijin/detail?id=<?php echo $this->_var['fund']['id']; ?>" target="_blank" ><?php echo $this->_var['fund']['name']; ?></a>
                                    <?php else: ?>
                                    <span title="<?php echo $this->_var['fund']['name']; ?>" alt="<?php echo $this->_var['fund']['name']; ?>"  href="/jijin/detail?id=<?php echo $this->_var['fund']['id']; ?>" target="_blank" class="deal_tag_name" ><?php echo $this->_var['fund']['name']; ?></span>
                                    <?php endif; ?>
                                </h3>
                                <div class="fl w340">
                                    <p><span>年化收益率：</span>
                                        <?php if ($this->_var['fund']['income_min'] != $this->_var['fund']['income_max']): ?>
                                        <span class="f20"><i><?php echo $this->_var['fund']['income_min']; ?></i>&nbsp;%～<i><?php echo $this->_var['fund']['income_max']; ?></i>&nbsp;%</span>
                                        <?php else: ?>
                                        <span class="f20"><i><?php echo $this->_var['fund']['income_min']; ?></i>&nbsp;%</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="fl w175">
                                    <p><span>期限：</span><em><i class="f16"><?php echo $this->_var['fund']['repay_time']; ?></i></em></p>
                                </div>
                                <div class="fl w200">
                                    <p><span>起投金额：</span><?php echo $this->_var['fund']['loan_money_min']; ?></p>
                                </div>
                                <div class="fl w173">
                                    <p><span>预约人数：</span><?php echo $this->_var['fund']['subscribe_count']; ?>人</p>
                                </div>
                            </div>
                            <?php if ($this->_var['fund']['status'] == 1): ?>
                                <div class="product_btn"><a href="/jijin/detail?id=<?php echo $this->_var['fund']['id']; ?>" class="btn_yuyue" target="_blank">预约中</a></div>
                            <?php else: ?>
                                <div class="product_btn"><span class="btn_manbiao">已结束</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                    <div class="deal_more"><a href="/jijin/">点击查看更多</a></div>
              </div>
            </div>
            <?php endif; ?>

            <div class="news clearfix mb30" id="newsPart">
                <div class="news_list mt_news" id="mtbd">
                    <h2><a class="new_more" href="###" ></a><i class="icon_line mr15"></i>媒体报道</h2>
                   <div class="con">
                       <div class="no_data">加载中，请稍候...</div>
                    </div>
                </div>
                <div class="news_list pt_news" id="ptgg">
                    <h2><a class="new_more" href="###"></a><i class="icon_line mr15"></i>平台公告</h2>
                    <div class="con">
                           <div class="no_data">加载中，请稍候...</div>
                    </div>
                </div>
                <div class="news_list hk_news" id="hkgg">
                    <h2><a class="new_more" href="/news/hklist" target="_blank"></a><i class="icon_line mr15"></i>还款公告</h2>
                    <div class="con">
                        <div class="no_data">加载中，请稍候...</div>
                    </div>
                </div>
            </div>


            <div class="box mb30 partner clearfix" id="scroll">
                <div class="title">
                  <h2><i class="icon_line mr15"></i>合作伙伴</h2>
                </div>
              <div class="clearfix">
                  <div class="left_but scroll_up"></div>
                  <div class="scrollDiv">
                    <ul>
                        <li>
                        <?php $_from = $this->_var['links']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'link');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['link']):
?>
                        <?php if ($this->_var['key'] % 3 == 0 && $this->_var['key'] != 0): ?>
                        </li>
                        <li>
                        <?php endif; ?>
                        <a index="<?php echo $this->_var['key']; ?>" href="<?php echo $this->_var['link']['url']; ?>" target="_blank" class="logo_h"><span style="background:url(<?php echo $this->_var['link']['img_gray']; ?>) no-repeat center;"></span></a>
                        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                        </li>
                    </ul>
                   </div>
                <div class="right_but scroll_down"></div>
            </div>
            </div>
        </section>
        <section class="index_step tc <?php if ($this->_var['user_info']): ?>none<?php endif; ?>">
            <div class="step_img"><img src="<?php echo $this->asset->makeUrl('v2/images/index/step.png');?>"></div>
            <a href="/user/register" class="reg_btn">立即注册</a>
        </section>
    </div>
<?php echo $this->fetch('web/views/v2/footer.html'); ?>
