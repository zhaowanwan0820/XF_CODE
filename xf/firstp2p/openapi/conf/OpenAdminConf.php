<?php

namespace openapi\conf;

class OpenAdminConf {

    static $adminProxyConfig = [
        'user_ncfwxcarry' => [ // 对应 openapi 中 controller_action, 都需要小写
            'admin_location'     => 'ncfwx', // admin系统名称
            'admin_url'          => 'http://%s/m.php?m=UserCarry&a=index&%s', // admin系统对应的url
            'admin_after_invoke' => 'afterUserCarray', //admin系统回调
        ],
        'user_ncfphcarry' => [ // 对应 openapi 中 controller_action, 都需要小写
            'admin_location'     => 'ncfph', // admin系统名称
            'admin_url'          => 'http://%s/m.php?m=SupervisionWithdraw&a=index&%s', // admin系统对应的url
            'admin_after_invoke' => 'afterUserCarray', //admin系统回调
        ],
        'user_viprebatelog' => [
            'admin_location'     => 'ncfwx',
            'admin_url'          => 'http://%s/m.php?m=VipUser&a=rebateLog&%s',
        ],
        'user_viplevellog' => [
            'admin_location'     => 'ncfwx',
            'admin_url'          => 'http://%s/m.php?m=VipUser&a=levelLog&%s',
        ],
        'user_accountdetail' => [
            'admin_location' => 'ncfwx',
            'admin_url'      => 'http://%s/m.php?m=User&a=account_detail&%s',
            'out_fields'    => array('id','log_info','log_time','money','note','lock_money','remaining_total_money','remaining_money','deal_type','user_id','user_name','mobile'),
        ],
        'user_accountdetailsupervision' => [
            'admin_location' => 'ncfwx',
            'admin_url'      => 'http://%s/m.php?m=User&a=account_detail_supervision&%s',
            'out_fields'    => array('id','log_info','log_time','money','note','lock_money','remaining_total_money','remaining_money','deal_type','user_id','user_name','mobile'),
        ],
        'user_accountdetailgold' => [
            'admin_location' => 'ncfwx',
            'admin_url'      => 'http://%s/m.php?m=User&a=account_detail_gold&%s',
            'out_fields'    => array('id','logInfo','logTime','gold','note','lockMoney','remainingTotalMoney','remainingMoney','userId'),
        ],
        'user_bonus' => [
           'admin_location' => 'bonus',
           'admin_url' => 'http://%s/log/list?%s',
           'out_fields' => array(),
        ],
        'user_discountlist' => [
           'admin_location' => 'o2o',
           'admin_url' => 'http://%s/discount/list?%s',
           'out_fields' => array(),
        ],
        'user_discountgiven' => [
           'admin_location' => 'o2o',
           'admin_url' => 'http://%s/discount/givenLogList?%s',
           'out_fields' => array(),
        ],
        'user_changebankcard' => [ // 对应 openapi 中 controller_action, 都需要小写
            'admin_location'     => 'ncfwx', // admin系统名称
            'admin_url'          => 'http://%s/m.php?m=User&a=AuditBankInfo&%s', // admin系统对应的url
            'admin_after_invoke' => 'afterChangeBankcard', //admin系统回调
        ],
        'user_changebankcarddetail' => [ // 对应 openapi 中 controller_action, 都需要小写
            'admin_location'     => 'ncfwx', // admin系统名称
            'admin_url'          => 'http://%s/m.php?m=User&a=getBankInfo&%s', // admin系统对应的url
            'admin_after_invoke' => 'afterChangeBankcardDetail', //admin系统回调
        ],
        'user_couponlog' => [
            'admin_location' => 'ncfwx',
            'admin_url'      => 'http://%s/m.php?m=CouponLog&a=index&%s',
            'out_fields'    => array('l_create_time','l_user_id','l_user_name','lu_real_name','lu_mobile','lu_create_time','l_money','l_money_yearly','l_deal_type_text','l_source_type','l_site_name','l_deal_id','d_name','d_repay_time','d_loantype_name','d_deal_status','refer_real_name','short_alias','referer_rebate_ratio_factor','rebate_amount','rebate_ratio_amount','referer_rebate_amount','referer_rebate_ratio_amount'),
        ],
        'user_coupongoldclist' => [
            'admin_location' => 'ncfwx',
            'admin_url'      => 'http://%s/m.php?m=CouponLog&a=goldclist&%s',
            'out_fields'    => array('create_time','consume_user_id','consume_user_name','consume_real_name','deal_load_money','refer_real_name','short_alias','rebate_ratio_amount','referer_rebate_ratio','referer_rebate_ratio_amount'),
        ],
        'user_couponduotoulist' => [
            'admin_location' => 'ncfwx',
            'admin_url'      => 'http://%s/m.php?m=CouponLog&a=duotoulist&%s',
            'out_fields'    => array('create_time','consume_user_id','consume_user_name','consume_real_name','deal_load_money','refer_real_name','short_alias','rebate_ratio_amount','referer_rebate_ratio','referer_rebate_ratio_amount'),
        ],
        'user_couponncfphlist' => [
            'admin_location' => 'ncfwx',
            'admin_url'      => 'http://%s/m.php?m=CouponLog&a=ncfphlist&%s',
            'out_fields'    => array('deal_load_id','create_time','consume_user_id','consume_user_name','consume_real_name','deal_id','deal_load_money','refer_real_name','agency_user_name','short_alias','rebate_ratio_amount','referer_rebate_ratio','referer_rebate_ratio_amount','agency_rebate_ratio','agency_rebate_ratio_amount','pay_status','pay_time'),
        ],
        'user_couponlist' => [
            'admin_location' => 'o2o',
            'admin_url' => 'http://%s/coupon/list?%s',
            'out_fields' => array(),
        ],
        'user_gamelog' => [
            'admin_location' => 'o2o',
            'admin_url' => 'http://%s/game/game-log?%s',
            'out_fields' => array(),
        ],
    ];

}
