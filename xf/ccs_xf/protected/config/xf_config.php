<?php
return [
    'loantype' => [
        1 => '按季等额还款',
        2 => '按月等额还款',
        3 => '一次性还本付息',
        4 => '按月付息一次还本',
        5 => '按天一次性还款',
        6 => '按季付息到期还本',
        9 => '半年付息到期还本',
    ],
    'contact_name' => ['未拨打人数','可联人数','空号','停机','关机','无法接通','占线','挂断','无人接听','暂停服务'],
    'sex_name' => ['女性','男性'],
    'age_group' => [
        '20岁以下' => ['min'=>0, 'max'=>20],
        '21-25' => ['min'=>21, 'max'=>25],
        '26-30' => ['min'=>26, 'max'=>30],
        '31-35' => ['min'=>31, 'max'=>35],
        '36-40' => ['min'=>36, 'max'=>40],
        '41-45' => ['min'=>41, 'max'=>45],
        '46-50' => ['min'=>46, 'max'=>50],
        '51-55' => ['min'=>51, 'max'=>55],
        '56-60' => ['min'=>56, 'max'=>60],
        '60岁以上' => ['min'=>61, 'max'=>200]
    ],
    'question_type' => [
        1 => '结清类',
        2 => '还款纠纷类',
        3 => '借核实类',
        4 => '还款渠道身份类',
        5 => '负面影响类',
        6 => '拒绝还款类',
        7 => '减免类',
        8 => '死亡类',
        9 => '其他',
        0 => '未归类',
    ],
    'wait_capital_group' => [
        '1万以下' => ['min'=>0, 'max'=>10000],
        '1-2万' => ['min'=>10000, 'max'=>20000],
        '2-3万' => ['min'=>20000, 'max'=>30000],
        '3-4万' => ['min'=>30000, 'max'=>40000],
        '4-5万' => ['min'=>40000, 'max'=>50000],
        '5-6万' => ['min'=>50000, 'max'=>60000],
        '6-7万' => ['min'=>60000, 'max'=>70000],
        '7-8万' => ['min'=>70000, 'max'=>80000],
        '8-9万' => ['min'=>80000, 'max'=>90000],
        '10万以上' => ['min'=>90000, 'max'=>10000000]
    ],
    'area_list' => [
        [
            'id'=>1,
            'name'=>'汇源专区',
        ],
       
    ],
	
         'status_cn' => [
                0=>'待审核',
                1=>'审核通过',
                2=>'已拒绝',
                3=>'已撤回',
            ],

      'deal_type_cn' => [
        0=>'',
        1=>'尊享',
        2=>'普惠',
        3=>'工厂微金',
        4=>'智多新',
        5=>'交易所',
    ],
    'assignee_tel' => [
        12143682=>4008607960,//巴恩琦
        12143680=>4008955780,//莲裴茹
        12143679=>4000320686,//中创
    ],
    'debt_src' => [
        1 => '权益兑换',
        2 => '债转交易',
        3 => '债权划扣',
        4 => '一键下车',
        5 => '一键下车退回',
        6 => '权益兑换退回',
        7 => '债权置换',
    ],
    'login_data_src' =>[
        '前端获取','短信导入'
    ]
];
