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
        'template_id' => $debug ? '43d51cbe5d1311eab0920c4de9a14598' : 'c7a7099a2fb211eb90b300163e32cf71',//正式环境
        'params' => [
        ],
        'sign' => [
        ],
    ],
    //债转合同信息
    '2' => [
        'title' => '债权转让与受让协议',
        'template_id' => $debug ? '504a76ecd62b11eab93e00163e32cf71' : 'c76d8f58c71111eaac3000163e16ead3',//正式环境
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
    //下车专区积分兑换合同信息
    '6' => [
        'title' => '债权转让协议',
        'template_id' => $debug ? '4cc8e6da2fc611eb846d00163e32cf71' : '4cc8e6da2fc611eb846d00163e32cf71',//正式环境
        'params' => [
        ],
        'sign' => [
        ],
    ],
    //悠融标的出清证明
    '7' => [
        'title' => '悠融消费信贷结清证明',
        'template_id' => $debug ? '4cc8e6da2fc611eb846d00163e32cf71' : '4cc8e6da2fc611eb846d00163e32cf71',//正式环境
        'customer_id' => '',
        'params' => [
        ],
        'sign' => [
        ],
    ],
    //法大大合同升级后积分兑换合同模板 8-3张附件  9-13张附件
    '8' => [
        'title' => '债权转让协议',
        'template_id' => $debug ? '7ded1382a10111ecb1b200163e32cf71' : '636f5342a91e11ec937400163e32cf71',//正式环境
        'customer_id' => '',
        'params' => [
        ],
        'sign' => [
        ],
    ],
    '9' => [
        'title' => '债权转让协议',
        'template_id' => $debug ? 'ad0992c4a10311ecacd100163e32cf71' : '486d964ea91e11ec88f700163e32cf71',//正式环境
        'customer_id' => '',
        'params' => [
        ],
        'sign' => [
        ],
    ],
    'agency_name' => [
        '杭州大树网络技术有限公司','北京掌众金融信息服务有限公司'
    ],
    'purchase_template' => [
        '1_5' =>  $debug ?    '7737d08e16de11ec9dcc00163e32cf71' : 'edee6892154811ec9f5e00163e32cf71',
        '6_10' =>  $debug ?   '651c05a016de11ec90ba00163e32cf71' : '2e3c8794154911ecb01600163e32cf71',
        '11_15' =>  $debug ?  '58d70c4a16de11ecbf3b00163e32cf71' : '3cda3b3e154911ecba2b00163e32cf71',
        '16_20' =>  $debug ?  '4b6632ca16de11ecb09600163e32cf71' : '4bcef634154911ec8b2200163e32cf71',
        '21_30' =>  $debug ?  '3e35d0e216de11ec991000163e32cf71' : '64084ebc154911eca99600163e32cf71',
        '31_40' =>  $debug ?  '320e77f616de11ec8eeb00163e32cf71' : '721602f6154911ec9f8300163e32cf71',
        '41_50' =>  $debug ?  '24d3b89416de11ec987a00163e32cf71' : '7e931492154911ecb48d00163e32cf71',
        '51_60' =>  $debug ?  '185258be16de11ec819400163e32cf71' : '8a56189c154911ecb61300163e32cf71',
        '61_70' =>  $debug ?  '03f8f79c16de11ec933600163e32cf71' : '974b27c2154911ec8c2100163e32cf71',
        '71_170' =>  $debug ? 'e963ce8416dd11ec82c000163e32cf71' : 'aaba6318154911ecb6c600163e32cf71',
        '171_470' =>  $debug ? '7f9b118c743511ec88ad00163e32cf71' : '936d4af4744411ecadb600163e32cf71',
    ],
    'displace_uid' => $debug ?  '12143685' : '12143676',//万峻
];