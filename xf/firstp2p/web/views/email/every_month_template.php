<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>网信 - 网络贷款平台 - 网信</title>
<link rel="icon" href="http://fp9.ncfstatic.com/skins/default/images/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="http://fp9.ncfstatic.com/skins/default/images/favicon.ico" type="image/x-icon" />
<meta name="keywords" content="网信，p2p，网络借贷，网络贷款，借贷 ，网络平台，民间借贷，小额贷款，无抵押贷款，信用贷款，投资理财，借钱，企业贷款，小企业贷款，个人经营贷款，融资" />
<meta name="description" content="网信 — 安全，专业的网络借款、理财平台。提供便捷、安全、低门槛的个人信用贷款和个人理财服务。" />


<script src="<?php echo $asset->makeUrl("/v1/js/common/html5shiv.js?v={$version}",1);?>"></script>
<link href="<?php echo  $asset->makeUrl('/v1/css/ie6-fix.css?v='.$version,1); ?>" rel="stylesheet" type="text/css">
<link href="<?php echo $asset->makeUrl('/v1/css/base.css?v='.$version,1);?>" rel="stylesheet" type="text/css">
<link href="<?php echo $asset->makeUrl('/v1/css/common.css?v='.$version,1); ?>" rel="stylesheet" type="text/css">
<link href="<?php echo $asset->makeUrl('/v1/css/email.css?v='.$version,1); ?>" rel="stylesheet" type="text/css">

<script type="text/javascript" src="<?php echo $asset->makeUrl('/v1/js/index/DD_belatedPNG.js?v='.$version,1); ?>"></script>
<script type="text/javascript" src="<?php echo $asset->makeUrl('/v1/js/index/DD_belated_ie6_fix.js?v='.$version,1); ?>"></script>

</head>

<body>
<table align="center" width="800">
<tr>
    <td>
<div class="email_main" style="width:800px; border:1px solid #dedede; margin:0px auto; background:#fafafa; font-family:Microsoft YaHei">
    <div class="e_title" style="height:89px; text-align:center; border-bottom:3px solid #ff804d;">
        <img src="<?php echo $asset->makeUrl('/v1/images/email/title_bg_wx.png',1); ?>">
    </div>
    <table align="center" width="742"style="width:742px; margin:0px auto; ">
<tr>
    <td>
    <div class="email_box" style="width:742px; margin:0px auto; padding-top:30px;">
        <div class="e_users" style="border-radius:5px; position:relative; overflow:hidden;border:1px solid #dedede;font-size:24px; color:#848484;
-moz-box-shadow:0px 2px 0px #dadada; -webkit-box-shadow:0px 2px 0px #dadada; box-shadow:0px 2px 0px #dadada; margin-bottom:40px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-family:Microsoft YaHei">
            <colgroup>
                <col width="380">
                <col>
            </colgroup>
            <thead style="background:#61b2e6; line-height:66px; font-size:28px;">
                <tr style="color:#848484;">
                    <td style="color:#fff;" height="50"><span style="padding-left:15px; font-size:28px;"><i class="icon_e_user" >
                        <img style="vertical-align:middle;" src="<?php echo $asset->makeUrl('/v1/images/email/icon_02.png',1);?>">
                        </i><span style="padding-left:10px;">
                        <?php echo htmlspecialchars($user_info['real_name']);?></span>  </span></td>
                    <td style="color:#fff;"><div  style="padding-right:15px; font-size:24px; text-align:right"><?php echo date('Y.m.d',$prev_month_start+86400);?>~<?php echo date('Y.m.d',$prev_month_end); ?></div></td>
                </tr>
            </thead>
            <tbody>
                <tr style="color:#848484;">
                    <td>
                       <table align="center" width="215" style="margin:0px auto;">
                           <tr style="color:#848484;">
                               <td style="padding:20px 0px;">
                        <?php
                            $arr = explode('@',$user_info['email']);
                            if(!empty($arr[1]) && $arr[1] == 'hotmail.com') {
                        ?>

                                <table border="0" cellspacing="0" cellpadding="0"  height="215" align="center">
                            <tr>
                                <td rowspan="3" style="width:20px; height:215px;font-size:0px; line-height:0px; padding-top:0px;"><img src="<?php echo $asset->makeUrl('/v1/images/email/icon_left.png',1); ?>" width="20" height="215"></td>
                                <td style="width:175px; height:72px; font-size:0px; line-height:0px;"><img src="<?php echo  $asset->makeUrl('/v1/images/email/icon_top.png',1); ?>" ></td>
                                <td rowspan="3" style="width:33px; height:215px; overflow:hiddenfont-size:0px; line-height:0px;"><img src="<?php echo $asset->makeUrl('/v1/images/email/icon_right.png',1);?>"></td>
                            </tr>
                             <tr>
                                <td style=" height:72px; overflow:hidden; background:#fff; text-align:center">
                                    <div style="relative; z-index:10px;padding-top:65px;padding-right:15px; ">累计收益</div>
                                    <span style="position:relative; z-index:10px; font-size:20px;padding-right:15px; "><?php echo format_price($user_statics['earning_all']); ?></span>
                                </td>
                                </tr>
                             <tr>
                                <td style=" height:71px;font-size:0px; line-height:0px;"><img src="<?php echo $asset->makeUrl('/v1/images/email/icon_bottom.png',1);?>" ></td>
                                </tr>
                        </table>


    <?php
}else {
?>
<div style="background:url(<?php echo $asset->makeUrl('/v1/images/email/icon_01.png',1);?>) no-repeat;width:230px; height:215px;display:block;color:#ff804d;text-align:center;position:relative;font-size: 24px;">
                                        <div style="position:relative; z-index:10px;padding-top:65px;padding-right:15px; ">累计收益</div>
                                        <span style="position:relative; z-index:10px; font-size:20px;padding-right:15px; "><?php echo format_price($earning_all); ?></span>
                                    </div>
<?php
}
?>





                               </td>
                           </tr>
                       </table>

                    </td>
                    <td style="font-size: 18px;font-family:Microsoft YaHei">
                        <div style="color: #25aae2;padding-bottom:8px;"><?php echo $prev_month;?>月已获收益：<span style="font-size:30px;"><?php echo format_price($last['income']+$last['referrals']);?></span></div>
                        <div style="padding-bottom:8px;"><?php echo $prev_month;?>月投资收益：<?php echo format_price($last['income']); ?></div>
                        <!--<div style="padding-bottom:8px;"><?php echo $prev_month;?>月邀请返利收益：<?php echo format_price($last['referrals']);?></div>-->
                        <div style="padding-bottom:8px;"><?php echo $prev_month;?>月投资：<?php echo  format_price($loan_sum); ?></div>
                    </td>
                </tr>
                <tr style="background:#e7f5ff; color:#848484">
                    <td colspan="2"><div style="padding:15px; padding-left:40px;font-size:24px;font-family:Microsoft YaHei"><span>我的资产总额：<?php echo format_price($moneyInfo['allMoney']);?></span></div></td>
                </tr>
                <tr style="background:#e7f5ff; color:#848484">
                    <td style="padding:0 0 10px 40px;font-family:Microsoft YaHei"><span style="font-size:18px;"><i class="icon_e_keyong" style="vertical-align:middle;">
                        <img style="vertical-align:middle;" src="<?php echo $asset->makeUrl('/v1/images/email/icon_03.png',1);?>"></i><span style="padding-left: 10px;  height:40px; vertical-align:middle">可用余额：<?php echo format_price($moneyInfo['usableMoney']);?></span></span></td>
                    <td style="padding:0 0 10px 0px;font-family:Microsoft YaHei"><span style="font-size:18px;"><i class="icon_e_benjin" style="vertical-align:middle;"><img style="vertical-align:middle;" src="<?php echo $asset->makeUrl('/v1/images/email/icon_06.png',1);?>"></i><span style="padding-left: 10px; height:40px; vertical-align:middle">待收本金：<?php echo format_price($moneyInfo['principalMoney'])?></span></span></td>
                </tr>
                <tr style="background:#e7f5ff; color:#848484">
                    <td  style="padding:0 0 10px 40px;font-family:Microsoft YaHei" ><div  style=" padding-bottom:20px;font-family:Microsoft YaHei"><span style="font-size:18px;font-family:Microsoft YaHei"><i class="icon_e_dongjie" style="vertical-align:middle;"><img style="vertical-align:middle;" src="<?php echo $asset->makeUrl('/v1/images/email/icon_04.png',1);?>"></i><span style="padding-left: 10px; height:40px; vertical-align:middle">冻结金额：<?php echo format_price($moneyInfo['freezeMoney']);?></span></span></div></td>
                    <td style="padding:0 0 10px 0px;font-family:Microsoft YaHei"><div  style=" padding-bottom:20px;"><span style="font-size:18px;"><i class="icon_e_daishou" style="vertical-align:middle;"><img style="vertical-align:middle;" src="<?php echo $asset->makeUrl('/v1/images/email/icon_05.png',1);?>"></i><span style="padding-left: 10px; height:40px; vertical-align:middle">待收收益：<?php echo format_price($moneyInfo['interestMoney']);?></span></span></div></td>
                </tr>
            </tbody>
        </table>
        </div>
        <div class="e_detail" style="padding:0px 0px; color:#848484">
            <div style="font-size:22px; padding:30px 0px 10px; color:#848484;font-weight:normal"><?php echo $prev_month;?>月收支明细 </div>
            <table width="100%" style="border:1px solid #dedede; font-size:14px;" border="0" cellspacing="0" cellpadding="0">
                <colgroup>
                    <col width="108">
                    <col width="80">
                    <col width="110">
                    <col width="130">
                    <col width="255">
                </colgroup>
                <thead>
                    <tr style="background:#dedede; line-height:30px; height:30px ;color:#848484">
                        <td><div style="text-align:left;padding-left:20px;font-size:14px;">时间</div></td>
                        <td><div style="text-align: left;font-size:14px;">类型</div></td>
                        <td><div style="text-align: right;font-size:14px;">收入(元)</div></td>
                        <td><div style="text-align: center;font-size:14px;">支出(元)</div></td>
                        <td><div style="text-align: center;font-size:14px;">备注</div></td>
                    </tr>
                </thead>
                <tbody>
                <?php
                if(count($detail)) {
                    foreach ($detail as $key => $val) {
                        $str = ($key%2) > 0 ? '<tr style="background:#f2f2f2;color:#848484">' : '<tr style="color:#848484">';
                        $str .=  '<td style="height:48px;font-size:14px;"><span style="padding-left:20px;font-family:Microsoft YaHei">'.date('Y.m.d',$val['log_time']+28800).'</span></td>
                                            <td style="font-size:14px;">'.$val['log_info'].'</td>';
                        if($val['is_earning'] > 0) {    // 收入
                            $str .= '<td><div style="text-align: right;font-size:14px; color: #589500; font-family:Microsoft YaHei">'.format_price($val['money'],false).'</div></td><td></td>';
                        }else {     //  支出
                            $str .= '<td></td><td><div style="text-align:center;font-size:14px; color:red; font-family:Microsoft YaHei">'.format_price($val['lock_money'],false).'</div></td>';
                        }

                        $str .= "<td><div style=\"text-align: center;font-size:14px;padding:5px 0px;font-family:Microsoft YaHei\">".$val['note']."</div></td>
                                        </tr>";
                        echo $str;
                    }
                }else {
                    echo '<tr style="color:#848484"><td style="height:48px;text-align:center;font-size:14px;" colspan="6"><span style="font-family:Microsoft
                                          YaHei;font-size:14px;">暂无收支明细</span></td></tr>';
                }
                ?>
                </tbody>
            </table>
            <div class="e_more" style="font-size:14px; text-align:right; padding:5px 0px 10px;">
                <a href="<?php echo $main_site?>/account/money" target="_blank" style="text-decoration:none; color:#0088cc; outline:none;">查看更多</a>
            </div>
            <div style="font-size:22px; padding:0px 0px 10px; color:#848484;font-weight:normal"><?php echo $cur_month;?>月回款计划</div>
            <table width="100%" style="border:1px solid #dedede; font-size:14px;" border="0" cellspacing="0" cellpadding="0">
                <colgroup>
                    <col width="158">
                    <col width="130">
                    <col width="100">
                    <col width="190">
                </colgroup>
                <thead>
                    <tr style="background:#dedede; line-height:30px;height:30px color:#848484">
                        <td><div style="text-align:left;padding-left:20px; font-size:14px;">时间</div></td>
                        <td><div style="text-align:left;font-size:14px;">类型</div></td>
                        <td><div style="text-align:right;font-size:14px;">金额(元)</div></td>
                        <td><div style="text-align:center;font-size:14px;">备注</div></td>
                    </tr>
                </thead>
                <tbody>
                <?php
                if(count($repay_list)) {
                    foreach($repay_list as $key=> $val) {
                        $str = '';
                        $str = ($key%2) > 0 ? '<tr style="background:#f2f2f2;font-family:Microsoft YaHei;font-size:14px;color:#848484">' : '<tr class="'.$key.'" style="color:#848484">';
                        $str .='<td style="height:48px;font-family:Microsoft YaHei;text-align:left;font-size:14px;"><span style="padding-left:20px;">'.date('Y.m.d',strtotime($val['time'])).'</span></td>
                            <td style="font-family:Microsoft YaHei;font-size:14px;">'.$val['money_type'].'</td>
                            <td><div style="text-align:right;font-family:Microsoft YaHei;font-size:14px;">'.format_price($val['money'],false).'</div></td>
                            <td><div style="text-align:center;font-family:Microsoft YaHei;font-size:14px;padding:5px 0px">'.$val['deal_name'].'</div></td>
                        </tr>';
                        echo $str;
                    }
                }else {
                    echo '<tr style="color:#848484"><td style="height:48px;text-align:center;font-size:14px;" colspan="4"><span style="font-family:Microsoft
                                          YaHei;font-size:14px;">暂无回款计划</span></td></tr>';
                }?>


                </tbody>
            </table>
            <div class="e_more" style="font-size:14px; text-align:right; padding:5px 0px 10px;">
                <a href="<?php echo $main_site;?>/account/loan" target="_blank" style="text-decoration:none; color:#0088cc; outline:none;">查看更多</a>
                <img style="width:0px;height:0px;" src="<?php echo $main_site;?>/email/show">
            </div>
        </div>
    </div>
    </td>
    </tr>
    </table>
    <table align="center" width="540" style="width:540px; margin:0px auto; ">
<tr>
    <td>
    <div class="email_footer" style="width:540px; margin:0px auto; font-size:16px; color:#7f7f7f;">
        如您对上述内容有疑问，请致电平台客户服务热线400-890-9888，
        或登录您的<a href="<?php echo $main_site;?>/" style="text-decoration:none; color:#0088cc; outline:none;">网信</a>平台账户进行查询。
        <table width="540">
          <tr>
              <td align="center">
                  <a href="<?php echo $main_site;?>/" title="网信" style="width:150px; height:40px; display:block; border:0px; margin:30px auto 50px;">
                    <img src="<?php echo $asset->makeUrl('/v1/images/email/logo_wx.png?v=20150624',1); ?>" style="border:none 0px;"></a>
              </td>
          </tr>
        </table>

    </div>
    </td>
    </tr>
    </table>
</div>
</td>
</tr>
</table>
</body>
</html>
