<?php echo $this->fetch('web/views/fenzhan/header.html'); ?>
<?php if (( app_conf ( 'TEMPLATE_ID' ) == 1 ) && ( app_conf ( 'HOME_THEME_TURN_ON' ) == 1 )): ?>
<style>
body{background:url(<?php echo $this->asset->makeUrl('v1/images/index/year2015_bg.jpg');?>) no-repeat center 80px #d91a1f;}
.m-head .fix_width span a{background:url(<?php echo $this->asset->makeUrl('v1/images/index/year2015_logo.jpg');?>) no-repeat 0 0; margin-top:0; height:59px; width:212px;}
.m-head .fix_width menu{margin-left:20px;}
.shadow,.product_con{-webkit-box-shadow:none; box-shadow:none;}
.partner_title{color:#fff;}
.partner_title i.icon_partner{background:url(<?php echo $this->asset->makeUrl('v1/images/index/year2015_icon.png');?>) no-repeat;}
.m-foot{background-image:url(<?php echo $this->asset->makeUrl('v1/images/index/year2015_foot.jpg');?>);}
.g-copyright p{color:#f1d1d0;}
</style>
<?php endif; ?>

<section class="clearfix mt-30">
    <div class="box">
        <div id="focus" class="shadow" onselectstart="return false;">
            <div class="slide">
                <?php 
$k = array (
  'name' => 'get_adv',
  'x' => '首页广告位1',
);
echo $k['name']($k['x']);
?>


                <div class="leftBt"></div>
                <div class="rightBt"></div>
            </div>
            <?php 
$k = array (
  'name' => 'get_adv',
  'x' => '首页投资提示',
);
echo $k['name']($k['x']);
?>
        </div>
        <div class="p5"></div>
        <div class="shadow mb10">
            <div class="earnings_box">
                <ul>
                    <li class="eb_rate">
                        <h3>年化收益率</h3>
                        <div>
                        <?php 
$k = array (
  'name' => 'get_num_pic',
  'x' => $this->_var['deals_income_view']['income_rate_min'],
  'y' => '1',
);
echo $k['name']($k['x'],$k['y']);
?><i class="ico_percent" alt="%"></i><i class="ico_rung" alt="-"></i>
                        <?php 
$k = array (
  'name' => 'get_num_pic',
  'x' => $this->_var['deals_income_view']['income_rate_max'],
  'y' => '1',
);
echo $k['name']($k['x'],$k['y']);
?><i class="ico_percent" alt="%"></i>
                        </div>
                    </li>
                    <li class="eb_already">
                        <h3>已为投资人带来收益</h3>
                        <div>
                        <?php 
$k = array (
  'name' => 'get_num_pic',
  'x' => $this->_var['deals_income_view']['income_sum'],
  'y' => '1',
);
echo $k['name']($k['x'],$k['y']);
?><span>元</span>
                        </div>
                    </li>
                    <li class="eb_soon">
                        <h3>即将带来收益</h3>
                        <div>
                        <?php 
$k = array (
  'name' => 'get_num_pic',
  'x' => $this->_var['deals_income_view']['income_plan_sum'],
  'y' => '1',
);
echo $k['name']($k['x'],$k['y']);
?><span>元</span>
                        </div>
                    </li>
                </ul>
            </div>
            <?php 
$k = array (
  'name' => 'get_adv',
  'x' => '首页投资说明',
);
echo $k['name']($k['x']);
?>
        </div>

        <div class="product_con mb20">

        <div class="tab" id="index_list_tab">
            <!--ul class="tabhd clearfix indexlist-tab-num<?php echo $this->_var['count']; ?>">
                    <?php $_from = $this->_var['deal_type']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'type');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['type']):
?>
                    <li class=" j_index_tab" data-id="<?php echo $this->_var['key']; ?>"><a href="javascript:void(0)" title="<?php echo $this->_var['type']['name']; ?>"><?php echo $this->_var['type']['name']; ?>(<?php echo $this->_var['type']['count']; ?>)</a></li>
                    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                    <li class="active j_index_tab"><a href="javascript:void(0)">VIP(8)</a></li>
            </ul-->
        <div class="tabbd">

        <div class="tabContent">
            <div class="product_hd">
                <ul>
                    <li class=" w212 tc pr20">投资项目</li>
                    <li class=" w190 tc">年化收益率</li>
                    <li class=" w80 tc">期限</li>
                    <li class=" w133 tc">收益方式</li>
                    <li class=" w220 tc" >投资进度</li>
                    <li class=" w110 linehg_set">状态</li>
                </ul>
            </div>
                <div class="product_bd2 pb20">
                <adv adv_id="全部tab广告描述语">
          <?php if (isset ( $this->_var['deal_list']['brief'] ) && $this->_var['deal_list']['brief'] && ( app_conf ( 'TEMPLATE_ID' ) == 1 || app_conf ( 'TEMPLATE_ID' ) == 7 || app_conf ( 'TEMPLATE_ID' ) == 6 )): ?><div class="w980 tab_tip_index color-yellow1 f14"><?php echo $this->_var['deal_list']['brief']; ?></div><?php endif; ?>
                <?php 
$k = array (
  'name' => 'get_adv',
  'x' => '首页列表预约广告位',
);
echo $k['name']($k['x']);
?>
                <table>
                    <colgroup>
                        <col width="63">
                        <col width="170">
                        <col width="195">
                        <col width="90">
                        <col width="140">
                        <col width="210">
                        <col width="135">
                    </colgroup>

                    <tbody class="j_index_tbody">
                    <?php $_from = $this->_var['deals_list']['list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'deal');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['deal']):
?>
                    <tr>
                    <td>
                        <?php if ($this->_var['deal']['type_id'] == 11): ?>
                        <div class="tc"><i class="icon_car"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 12): ?>
                        <div class="tc"><i class="icon_room"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 13): ?>
                        <div class="tc"><i class="icon_enterprise"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 14): ?>
                        <div class="tc"><i class="icon_personal"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 15): ?>
                        <div class="tc"><i class="icon_assets"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 16): ?>
                        <div class="tc"><i class="icon_melting"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 17): ?>
                        <div class="tc"><i class="icon_ysd"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 18): ?>
                        <div class="tc"><i class="icon_ddd"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 19 || $this->_var['deal']['type_id'] == 29): ?>
                        <div class="tc"><i class="icon_xs"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 21): ?>
                        <div class="tc"><i class="icon_ZSZ"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 22): ?>
                        <div class="tc"><i class="icon_YLD"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 23): ?>
                        <div class="tc"><i class="icon_YSD"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 24): ?>
                        <div class="tc"><i class="icon_KZD"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 25): ?>
                        <div class="tc"><i class="icon_GYB"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 26): ?>
                        <div class="tc"><i class="icon_CYD"></i></div>
                        <?php endif; ?>
                        <?php if ($this->_var['deal']['type_id'] == 27): ?>
                        <div class="tc"><i class="icon_LGL"></i></div>
                        <?php endif; ?>
                    </td>
                    <th>
                        <div class="pro_name" style="padding-left:0px;">
                            <p>
                                <?php if ($this->_var['deal']['bid_flag'] == 1): ?>
                                <a title="<?php echo $this->_var['deal']['old_name']; ?>" alt="<?php echo $this->_var['deal']['old_name']; ?>"  href="<?php echo $this->_var['deal']['url']; ?>" target="_blank" ><?php echo $this->_var['deal']['name']; ?></a>
                                <?php else: ?>
                                <i title="<?php echo $this->_var['deal']['old_name']; ?>" alt="<?php echo $this->_var['deal']['old_name']; ?>" class='notoutitle'><?php echo $this->_var['deal']['name']; ?></i>
                                <?php endif; ?>
                            </p>
                            <div class="pro_links">
                                总额：<?php echo $this->_var['deal']['borrow_amount_format_detail']; ?>万
                                <?php if ($this->_var['deal']['warrant'] == 1): ?>
                                <i class="badge_02" title="担保本金"></i>
                                <?php elseif ($this->_var['deal']['warrant'] == 2): ?>
                                <i class="badge" title="担保本息"></i>
                                <?php elseif ($this->_var['deal']['warrant'] == 4): ?>
                                <i class="badge" title="第三方资产收购"></i>
                                <?php endif; ?>
                                <?php if ($this->_var['deal']['agency_id'] > 0): ?>
                                <i class="badge_01" title="<?php echo $this->_var['deal']['agency_info']['short_name']; ?>"></i></i>
                                <?php endif; ?>
                                <?php if (( app_conf ( 'TEMPLATE_ID' ) == 1 || app_conf ( 'TEMPLATE_ID' ) == 7 || app_conf ( 'TEMPLATE_ID' ) == 6 || app_conf ( 'TEMPLATE_ID' ) == 11 ) && ( $this->_var['deal']['type_id'] == 11 || $this->_var['deal']['type_id'] == 12 )): ?><i class="badge_03" title="中国信贷荣誉出品"></i><?php endif; ?>
                            </div>
                        </div>
                    </th>

                    <th>
                        <?php if ($this->_var['deal']['income_ext_rate'] == 0): ?>
                        <p class="btm f14 tc"><?php echo $this->_var['deal']['rate_show']; ?><em>%</em>
                            <?php if ($this->_var['deal']['deal_type'] == 1): ?>起<?php endif; ?>
                        </p>
                        <?php else: ?>
                        <p class="btm f14 tc"><?php echo $this->_var['deal']['income_base_rate']; ?><em>%</em>+<?php echo $this->_var['deal']['income_ext_rate']; ?><em>%</em> <?php 
$k = array (
  'name' => 'get_rate_tips',
);
echo $k['name']();
?></p>
                        <?php endif; ?>
                        <p class="tc">

                            <?php if (isset ( $this->_var['deal']['deal_tag_name'] ) && $this->_var['deal']['deal_tag_name'] != ''): ?>
                            <span class="icon_new j_tips" title="<?php echo $this->_var['deal']['deal_tag_desc']; ?>"><?php echo $this->_var['deal']['deal_tag_name']; ?></span>
                            <?php endif; ?>

                        </p></th>

                    <td>
                        <?php if ($this->_var['deal']['loantype'] == 5): ?>
                        <P class="btm f14 tc">
                            <?php if ($this->_var['deal']['deal_type'] == 1): ?>
                            <?php 
$k = array (
  'name' => 'plus',
  'x' => $this->_var['deal']['lock_period'],
  'y' => $this->_var['deal']['redemption_period'],
);
echo $k['name']($k['x'],$k['y']);
?>~<?php endif; ?><?php echo $this->_var['deal']['repay_time']; ?>天</P>
                        <?php else: ?>
                        <P class="btm f14 tc"><?php echo $this->_var['deal']['repay_time']; ?>个月</P>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($this->_var['deal']['deal_type'] == 1): ?>
                        <p class="date tc">提前<?php echo $this->_var['deal']['redemption_period']; ?>天申请赎回</p>
                        <?php else: ?>
                        <p class="date tc"><?php echo $this->_var['deal']['loantype_name']; ?></p>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div  class="pl20" >
                            <?php if ($this->_var['deal']['is_update'] == 1): ?>
                            <p>等待确认</p>
                            <?php if (isset ( $this->_var['deal']['start_loan_time_format'] ) && $this->_var['deal']['start_loan_time_format']): ?>
                            <p>开始时间：<?php echo $this->_var['deal']['start_loan_time_format']; ?></p>
                            <?php endif; ?>
                            <?php elseif ($this->_var['deal']['deal_status'] == 4): ?>
                            <p>投资成功</p>
                            <p>成功时间：<?php echo $this->_var['deal']['full_scale_time']; ?></p>
                            <?php elseif ($this->_var['deal']['deal_status'] == 0): ?>
                            等待确认
                            <?php if (isset ( $this->_var['deal']['start_loan_time_format'] ) && $this->_var['deal']['start_loan_time_format']): ?>
                            <p>开始时间：<?php echo $this->_var['deal']['start_loan_time_format']; ?></p>
                            <?php endif; ?>
                            <?php elseif ($this->_var['deal']['deal_status'] == 2): ?>
                            <p>可投金额：<em class="color-yellow1"><?php echo $this->_var['deal']['need_money_detail']; ?>元</em></p>
                            <p>成功时间：<?php echo $this->_var['deal']['full_scale_time']; ?></p>
                            <?php elseif ($this->_var['deal']['deal_status'] == 5): ?>
                            <p>投资成功</p>
                            <p>成功时间：<?php echo $this->_var['deal']['full_scale_time']; ?></p>
                            <?php else: ?>
                            <p>可投金额：<em class="color-yellow1"><?php echo $this->_var['deal']['need_money_detail']; ?>元</em></p>
                            <p>剩余时间：<?php echo $this->_var['deal']['remain_time_format']; ?></p>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>



                        <?php if ($this->_var['deal']['deal_type'] == 0 || $this->_var['deal']['deal_type'] == 3): ?>
                        <?php if ($this->_var['deal']['is_crowdfunding'] == 0): ?>
                        <?php if ($this->_var['deal']['is_update'] == 1): ?>
                        <div class="table_cell"><a href="<?php echo $this->_var['deal']['url']; ?>" class="view">查看</a></div>
                        <?php elseif ($this->_var['deal']['deal_status'] == 4): ?>
                        <?php if ($this->_var['deal']['bid_flag'] == 1): ?>
                        <div class="complete">
                            <i class=" icon_yitou"></i>
                        </div>
                        <div class="table_cell"><em class="view_02">还款中</em></div>
                        <?php else: ?>
                        <div class="table_cell"><em class="view_02">还款中</em></div>
                        <?php endif; ?>
                        <?php elseif ($this->_var['deal']['deal_status'] == 0): ?>
                        <div class="table_cell"><a href="<?php echo $this->_var['deal']['url']; ?>" class="view">查看</a></div>
                        <?php elseif ($this->_var['deal']['deal_status'] == 2): ?>
                        <?php if ($this->_var['deal']['bid_flag'] == 1): ?>
                        <div class="complete">
                            <i class=" icon_yitou"></i>
                        </div>
                        <div class="table_cell"><em class="view_02">满标</em></div>
                        <?php else: ?>
                        <div class="table_cell"><em class="view_02">满标</em></div>
                        <?php endif; ?>
                        <?php elseif ($this->_var['deal']['deal_status'] == 5): ?>
                        <div class="complete">
                            <i class=" icon_complete"></i>
                        </div>
                        <div class="table_cell"><em class="view_02">已还清</em></div>
                        <?php else: ?>
                        <div class="table_cell"><a href="<?php echo $this->_var['deal']['url']; ?>" class="view_01">投资</a></div>
                        <?php endif; ?>
                        <?php elseif ($this->_var['deal']['is_crowdfunding'] == 1): ?>
                        <?php if ($this->_var['deal']['is_update'] == 1): ?>
                        <div class="table_cell"><a href="<?php echo $this->_var['deal']['url']; ?>" class="view">查看</a></div>
                        <?php elseif ($this->_var['deal']['deal_status'] == 4): ?>
                        <?php if ($this->_var['deal']['bid_flag'] == 1): ?>
                        <div class="complete">
                            <i class=" icon_yitou"></i>
                        </div>
                        <div class="table_cell"><em class="view_02">已成功</em></div>
                        <?php else: ?>
                        <div class="table_cell"><em class="view_02">已成功</em></div>
                        <?php endif; ?>
                        <?php elseif ($this->_var['deal']['deal_status'] == 0): ?>
                        <div class="table_cell"><a href="<?php echo $this->_var['deal']['url']; ?>" class="view">查看</a></div>
                        <?php elseif ($this->_var['deal']['deal_status'] == 2): ?>
                        <?php if ($this->_var['deal']['bid_flag'] == 1): ?>
                        <div class="complete">
                            <i class=" icon_yitou"></i>
                        </div>
                        <div class="table_cell"><em class="view_02">已成功</em></div>
                        <?php else: ?>
                        <div class="table_cell"><em class="view_02">已成功</em></div>
                        <?php endif; ?>
                        <?php elseif ($this->_var['deal']['deal_status'] == 5): ?>
                        <div class="complete">
                            <i class=" icon_complete"></i>
                        </div>
                        <div class="table_cell"><em class="view_02">已成功</em></div>
                        <?php else: ?>
                        <div class="table_cell"><a href="<?php echo $this->_var['deal']['url']; ?>" class="view_01">筹款</a></div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <?php else: ?>
                    <?php if ($this->_var['deal']['deal_status'] == 0): ?>
                    <div class="table_cell"><a href="<?php echo $this->_var['deal']['url']; ?>" class="view">查看</a></div>
                    <?php endif; ?>
                    <?php if ($this->_var['deal']['deal_status'] == 1): ?>
                    <?php if ($this->_var['deal']['deal_compound_status'] == 1): ?>
                    <div class="complete">
                        <i class=" icon_yitou"></i>
                    </div>
                    <div class="table_cell"><a href="<?php echo $this->_var['deal']['url']; ?>" class="view">已投</a></div>
                    <?php else: ?>
                    <div class="table_cell"><a href="<?php echo $this->_var['deal']['url']; ?>" class="view_01">投资</a></div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($this->_var['deal']['deal_status'] == 2): ?>
                    <?php if ($this->_var['deal']['bid_flag'] == 1): ?>
                    <div class="complete">
                        <i class=" icon_yitou"></i>
                    </div>
                    <div class="table_cell"><em class="view_02">满标</em></div>
                    <?php else: ?>
                    <div class="table_cell"><em class="view_02">满标</em></div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($this->_var['deal']['deal_status'] == 4): ?>
                    <?php if ($this->_var['deal']['deal_compound_status'] == 2): ?>
                    <div class="complete">
                        <i class=" icon_yitou"></i>
                    </div>
                    <div class="table_cell"><em class="view_02">待赎回</em></div>
                    <?php endif; ?>
                    <?php if ($this->_var['deal']['deal_compound_status'] == 3): ?>
                    <div class="complete">
                        <i class=" icon_yitou"></i>
                    </div>
                    <div class="table_cell"><em class="view_02">还款中</em></div>
                    <?php endif; ?>
                    <?php if ($this->_var['deal']['deal_compound_status'] == 4): ?>
                    <div class="complete">
                        <i class=" icon_yitou"></i>
                    </div>
                    <div class="table_cell"><em class="view_02">已还清</em></div>
                    <?php endif; ?>
                    <?php if ($this->_var['deal']['deal_compound_status'] == 0): ?>
                    <div class="table_cell"><em class="view_02">还款中</em></div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($this->_var['deal']['deal_status'] == 5): ?>
                    <div class="complete">
                        <i class=" icon_complete"></i>
                    </div>
                    <div class="table_cell"><em class="view_02">已还清</em></div>
                    <?php endif; ?>
                    <?php endif; ?>






                    </tr>
                    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>

                    </tbody>
                </table>
                </div>
                <div class="tc f14 pb20"><a href="/deals<?php if ($this->_var['key1']): ?>?cate=<?php echo $this->_var['key1']; ?><?php endif; ?>" class="but-gray but-blue w106 pt5 pb5">查看更多</a></div>
        </div>
                </div>
                </div>
          </div>
          <?php if (app_conf ( 'TEMPLATE_ID' ) == '1' && app_conf ( 'SHOW_FUNDING' ) == '1'): ?>
          <div class="product_con mb20">
        <div class="tab">
            <ul class="tabhd clearfix indexlist-tab-num">
                    <li class="j_index_tab active"><a href="javascript:void(0)" title="全部基金">全部基金(<?php echo $this->_var['fund_list']['count']; ?>)</a></li>
            </ul>
            <div class="tabbd">
                <div class="tabContent">
                    <div class="product_hd">
                        <ul>
                            <li class=" w250 tc pr20">基金名称</li>
                            <li class=" w160 tc">年化收益率</li>
                            <li class=" w120 tc">期限</li>
                            <li class=" w150 tc">起投金额</li>
                            <li class=" w133 tc">预约人数</li>
                            <li class=" w145 linehg_set">状态</li>
                        </ul>
                    </div>
                    <div class="product_bd2 pb20">
                <table>
                    <colgroup>
                        <col width="63">
                        <col width="170">
                        <col width="165">
                        <col width="90">
                        <col width="140">
                        <col width="100">
                        <col width="135">
                    </colgroup>

                    <tbody class="j_index_tbody">
                    <?php $_from = $this->_var['fund_list']['list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'fund');if (count($_from)):
    foreach ($_from AS $this->_var['fund']):
?>
                        <tr>
                            <td>
                            <div class="tc"><i class="icon_ZSZ icon_jijin"></i></div>
                            </td>
                            <th>
                                <div class="pro_name">
                                    <p>
                                    <?php if ($this->_var['fund']['status'] == 1): ?>
                                        <a title="<?php echo $this->_var['fund']['name']; ?>" alt="<?php echo $this->_var['fund']['name']; ?>"  href="/jijin/detail?id=<?php echo $this->_var['fund']['id']; ?>" target="_blank" ><?php echo $this->_var['fund']['name']; ?></a>
                                    <?php else: ?>
                                        <i title="<?php echo $this->_var['fund']['name']; ?>" alt="<?php echo $this->_var['fund']['name']; ?>" class='notoutitle'><?php echo $this->_var['fund']['name']; ?></i>
                                    <?php endif; ?>
                                    </p>
                            </th>
                            <th>
                                <div class="tips_1">
                                    <p class="btm f14 tc"><?php echo $this->_var['fund']['income_min']; ?><em>%</em>~<?php echo $this->_var['fund']['income_max']; ?><em>%</em></p>
                                </div>
                            </th>
                             <th>
                               <p class="btm f14 tc w80"><?php echo $this->_var['fund']['repay_time']; ?></p>
                            </th>
                            <td><p class="color-yellow1 tc w150"><?php echo $this->_var['fund']['loan_money_min']; ?>元</p></td>
                            <td><p class="color-yellow1 tc w100"><?php echo $this->_var['fund']['subscribe_count']; ?>人</p></td>
                            <td>
                                <div  class="pl20" >
                                <?php if ($this->_var['fund']['status'] == 1): ?>
                                    <div class="table_cell"><a href="/jijin/detail?id=<?php echo $this->_var['fund']['id']; ?>" class="view" target="_blank">预约中</a></div>
                                <?php else: ?>
                                    <div class="table_cell"><em class="view_02">已结束</em></div>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                    </tbody>
                </table>
                </div>
                <div class="tc f14 pb20 none"><a href="/jijin/" class="but-gray but-blue w106 pt5 pb5">查看更多</a></div>
                </div>
            </div>
        </div>
        </div>
        <?php endif; ?>
        <div class="partner">
            <div class="partner_title"><i class="icon_partner mr10"></i>合作伙伴</div>
            <div class="partner_con shadow clearfix" id="scroll">
                <div class="left_but scroll_up"></div>
                    <div class="scrollDiv">
                <ul>
                <?php $_from = $this->_var['links']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'link');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['link']):
?>
                    <li title="<?php echo $this->_var['link']['name']; ?>"><a href="<?php echo $this->_var['link']['url']; ?>" target="_blank" class="logo_h"><img data-src="<?php echo $this->_var['link']['img_gray']; ?>" width="109" height="50"></a><a href="<?php echo $this->_var['link']['url']; ?>" target="_blank" class="logo_c"><img data-src="<?php echo $this->_var['link']['img']; ?>" width="109" height="50"></a></li>
                <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                </ul>
              </div>
                <div class="right_but scroll_down"></div>
            </div>
        </div>
      </div>
</section>
<?php echo $this->fetch('web/views/fenzhan/footer.html'); ?>
