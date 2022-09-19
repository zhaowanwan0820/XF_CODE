<?php

namespace api\conf;

class ConstDefine {
    const SIGNATURE_KEY = 'signature';
    const XFZF_BANK_ID = 'CMBCHINA';
    const XFZF_BUSINESS_TYPE = 16;

    const RESULT_SUCCESS = '00';
    const RESULT_FAILURE = '01';

    //const XFZF_SEC_KEY = '/trBxSVzokD9vJSY/Fcj6w';
    //const XFZF_AES_KEY = '/trBxSVzokD9vJSY/Fcj6w';
    //const XFZF_PAY_CALLBACK = "http://api.firstp2p.com/account/payNotify";
    //const XFZF_PAY_CREATE = "http://10.20.15.170/ucfpay/p2pOperate/p2pCreateOrder";
    //const XFZF_PAY_CHECK = "http://10.20.15.170/ucfpay/p2pOperate/p2pQueryRechargeResult";

    const APP_SEC_KEY = '/trBxSVzokD9vJSY/Fcj6w'; // app与后台签名key,与支付段的key区别开来
    const APP_SEC_KEY_2 = '59cc22287dd6658e8c5963f83992e752';

    /*
     * app版本号与view目录对应关系
     * @added by longbo
     */
    public static $version_dir = array(
                            100 => '_v10',
                            200 => array('_v10',
                                        'account/DealLoadDetail' => 'deal_load_detail_v2',
                                        'deals/Detail' => 'detail_v2',
                                        'account/CouponPage' => 'coupon_page_v2',
                                    ),
                            300 => array('_v10',
                                        'deal/Confirm' => 'confirm_v3',
                                        'account/CouponPage' => 'coupon_page_v3',
                                    ),

                            320 => '_v32',
                            331 => '_v33',
                            340 => '_v34',
                            400 => '_v40',
                            410 => '_v41',
                            440 => '_v44',
                            450 => '_v45',
                            460 => '_v46',
                            470 => '_v47',
                            471 => '_v471',
                            472 => '_v472',
                            473 => '_v473',
                            475 => '_v475',
                            480 => '_v48',
                            483 => '_v483'
                        );
}
