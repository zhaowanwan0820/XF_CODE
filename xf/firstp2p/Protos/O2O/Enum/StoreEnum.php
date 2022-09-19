<?php

namespace NCFGroup\Protos\O2O\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class StoreEnum extends AbstractEnum {

    public static $storeFormConf = array(
        //storeId门店编号
        '3' => array(
            'storeName' => 'AA租车',
            'form' => array(
                'phone' => array('name' => 'phone', 'displayName' => '手机号码', 'require' => true, 'type' => 'string'),
                //'idno' => array('name' => 'idno', 'displayName' => '身份证号', 'require' => true, 'type' => 'string'),
                //'userName' => array('name' => 'userName', 'displayName' => '用户名', 'require' => true, 'type' => 'string'),
                //'email' => array('name' => 'email', 'displayName' => '邮箱', 'require' => true, 'type' => 'string'),
            ),
            'pushConf' => array(
                //是否需要调用推送券接口
                'needCoupon' => 1,
                'couponProvider' => 'aazc'
            ),
            'msgConf' => array(
                //是否发送消息的配置,
                'needMsg' => 0,
                'tplId' => '1239',//短信后台配置的模板name:TPL_O2O_EXCHANGE_COUPON
                'storeTel' => '',
            )
        ),
        '8188' => array(
            'storeName' => 'AA租车账户',
            'titleName' => '用于接收AA租车优惠券的账户信息',
            'form' => array(
                'phone' => array('name' => 'phone', 'displayName' => '手机号码', 'require' => true, 'type' => 'string'),
            ),
            'pushConf' => array(
                //是否需要调用推送券接口
                'needCoupon' => 1,
                'couponProvider' => 'aazc'
            ),
            'msgConf' => array(
                //是否发送消息的配置,
                'needMsg' => 0,
                'tplId' => '1239',//短信后台配置的模板name:TPL_O2O_EXCHANGE_COUPON
                'storeTel' => '',
            )
        ),
        '1514506' => array(
            'storeName' => '麦趣鸡盒',
            'form' => array(
                'phone' => array('name' => 'phone', 'displayName' => '手机号码', 'require' => true, 'type' => 'string'),
            ),
            'msgConf' => array(
                //是否发送消息的配置,
                'needMsg' => 1,
                'tplId' => '1239',//短信后台配置的模板name:TPL_O2O_EXCHANGE_COUPON
                'storeTel' => '',
            )
        ),
        //易赏配置-接收话费
        '28939' => array(
            'storeName' => '要充值的',
            'titleName' => '提交话费充值的手机号码',
            'form' => array(
                'phone' => array('name' => 'phone', 'displayName' => '手机号码', 'require' => true, 'type' => 'string'),
            ),
            'msgConf' => array(
                //是否发送消息的配置,
                'needMsg' => 0,
                'tplId' => '1239',//短信后台配置的模板name:TPL_O2O_EXCHANGE_COUPON
                'storeTel' => '',
            ),
            'pushConf' => array(
                //是否需要调用推送券接口
                'needCoupon' => 1,
                'couponProvider' => 'yishang'
            ),
        ),
        //易赏配置-接收流量包
        '3709046' => array(
            'storeName' => '要充值的',
            'titleName' => '提交流量充值的手机号码',
            'form' => array(
                'phone' => array('name' => 'phone', 'displayName' => '手机号码', 'require' => true, 'type' => 'string'),
            ),
            'pushConf' => array(
                //是否需要调用推送券接口
                'needCoupon' => 1,
                'couponProvider' => 'traffic'
            ),
        ),
        //u宝配置-接收话费
        '3107600' => array(
            'storeName' => '要充值的',
            'titleName' => '提交话费充值的手机号码',
            'form' => array(
                'phone' => array('name' => 'phone', 'displayName' => '手机号码', 'require' => true, 'type' => 'string'),
            ),
            'pushConf' => array(
                //是否需要调用推送券接口
                'needCoupon' => 1,
                'couponProvider' => 'ubao'
            ),
        ),
    );

}
