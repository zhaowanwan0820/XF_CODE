<?php
return [
    //先锋数据中心介接入平台
    "platform_type" => array(
        1,//尊享
        2,//普惠
        3,//金融工场
        4,//智多新
        5,//交易所
    ),
    //线下产品平台
    "offline_products" => array(
        3,//金融工场
        4,//智多新
        5,//交易所
    ),
    //先锋数据中心接入商城
    "xf_shop" => array(
        1,//有解
    ),
    //先锋数据中心接入商城
    "xf_contract_type" => array(
        1,//服务协议
        2,//商城积分兑换服务协议
    ),
    'loantype' => [
        1 => '按季等额还款',
        2 => '按月等额还款',
        3 => '一次性还本付息',
        4 => '按月付息一次还本',
        5 => '按天一次性还款',
        6 => '按季付息到期还本',
        9 => '半年付息到期还本',
    ],
    // 测试用手机号码
    "xf_test_number" => array(
        18910660866,13439807685,15810571697,13716970622,18600273938
    ),
    //债转市场专区列表
    'area_list' => [
        1 => '汇源专区',
    ],
    'purchase_status' => [
        0 =>'待签约',
        1 =>'待付款',
        2 =>'已付款待债转',
        3 =>'已债转待生成合同',
        4 =>'交易完成',
        5 =>'已失效',
    ],
    'province_name' => [
        11 => "北京",
        12 => "天津",
        13 => "河北",
        14 => "山西",
        15 => "内蒙古",
        21 => "辽宁",
        22 => "吉林",
        23 => "黑龙江",
        31 => "上海",
        32 => "江苏",
        33 => "浙江",
        34 => "安徽",
        35 => "福建",
        36 => "江西",
        37 => "山东",
        41 => "河南",
        42 => "湖北",
        43 => "湖南",
        44 => "广东",
        45 => "广西",
        46 => "海南",
        50 => "重庆",
        51 => "四川",
        52 => "贵州",
        53 => "云南",
        54 => "西藏",
        61 => "陕西",
        62 => "甘肃",
        63 => "青海",
        64 => "宁夏",
        65 => "新疆",
        71 => "台湾",
        81 => "香港",
        82 => "澳门",
        91 => "国外",
        99 => "其他"
    ],
    'white_list' =>[
        12130848,4909,1941,9540
    ],
];
