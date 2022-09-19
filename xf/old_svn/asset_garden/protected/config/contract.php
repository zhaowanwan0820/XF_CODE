<?php
$debug = false;//开发模式
return [
    'idno_key' => ConfUtil::get('decrypt_secret_key'),//证件解密秘钥
    //'fdd_url' => $debug ? 'http://39.97.238.243:5002/' : 'http://39.105.50.238:5002/', //正式环境
    'fdd_url' => ConfUtil::get('FDD_URL'), //正式环境
    //企业签章
    'company_id' => $debug ? '1F894463B107A3C0F7684C3ED7178D15' : 'CF3BFCAC02E496AEEC076230828FF7BB',//正式环境
    //法人签章
    'ceo_id' => $debug ? '927FD674CDE754790F58CC7BE0986E3F' : '00DB686E25185D8986700DAD3B720F80',//正式环境
    'project_style' => ["按日计息 按月付息 到期还本","按日计息 到期还本息", "按日计息 月底付息 到期还本息", "按日计息 按季度付息 到期还本", "等额本金 安月付款", "等额本息 按月付款"],
    //权益兑换合同信息
    '1' => [
        'title' => '债权转让与受让协议',
        'template_id' => $debug ? '43d51cbe5d1311eab0920c4de9a14598' : '496209ca6f4311eab66100163e16ead3',//正式环境
        'params' => [
        ],
        'sign' => [
        ],
    ],
    //债转合同信息
    '2' => [
        'title' => '债权转让与受让协议',
        'template_id' => $debug ? '3bc8b3285d1311eaba8d0c4de9a14598' : 'caf321808f6011ea868e00163e16ead3',//正式环境
        'params' => [
        ],
        'sign' => [
        ],
    ],
    // 兑付协议
    '3' => [
        'title' => '兑付协议',
        'template_id' => $debug ? '1f8324e65d1311eaa6310c4de9a14598' : '0c979eae8f6111eaa93b00163e16ead3',//正式环境
        'params' => [
        ],
        'sign' => [
        ],
    ],
    // 以房抵债协议
    '4' => [
        'title' => '以房抵债协议',
        'template_id' => $debug ? '32a897ae5d1311eaacdc0c4de9a14598' : 'f0cd3fc68f6011eab41d00163e16ead3',//正式环境
        'params' => [
        ],
        'sign' => [
        ],
    ],
    // 有解商城确认协议
    '5' => [
        'title' => '债权兑换积分服务协议',
        'yj_fdd_id' => $debug ? 'BB02A1FEB3F8D9B6D4404DFF2445E113' : 'BB02A1FEB3F8D9B6D4404DFF2445E113',//正式环境
        'template_id' => $debug ? '29f133b45d1311ea9e200c4de9a14598' : '3b2e3d0e8f6111ea880200163e16ead3',//正式环境
        'params' => [
        ],
        'sign' => [
        ],
    ],
    'agency_name' => [
        '杭州大树网络技术有限公司','北京掌众金融信息服务有限公司'
    ]
];