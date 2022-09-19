<?php

// 转化时间戳
$formatTime = function ($time) {
    if (strpos($time, '/') || strpos($time, '-')) {
        return strtotime($time);
    } elseif (is_numeric($time)) {
        $utime = PHPExcel_Shared_Date::ExcelToPHP($time);
        return strtotime("-8 hour", $utime);
    } else {
        return false;
    }
};

$formatNum = function ($str) {
    return empty($str) ? 0 : $str;
};

$original = function ($str) {
    return $str;
};

$special = function ($str) use ($formatTime) {
    return empty($str) ? 0 : $formatTime($str);
};

$user_id      = '/^[0-9]+$/';                                   // user_id
$user_name    = '/^.{1,30}$/u';                                 // 出借人姓名|借款方名称
$user_type    = '/^[1-2]{1}$/';                                 // 出借人类型|借款方类型
$idno_type    = '/^[1-5]{1}$/';                                 // 证件类型
$indo         = '/^[a-zA-Z0-9]{1,50}$/u';                       // 证件号|借款方证件号|营业执照号
$p_name       = '/^.{1,30}$/';                                  // 项目名称
$p_type       = '/^[1-3]{1}$/';                                 // 项目类型
$p_limit_type = '/^[0-9]{1,2}$/';                               // 项目期限类型
$p_limit_date = '/^[0-9]{1,5}$/';                               // 项目期限
$rate         = '/^[0-9]{1,3}(.[0-9]{1,5})?$/';                 // 年化利率
$loantype     = '/^[0-9]{1,2}$/';                                 // 还款方式
$raise_money  = '/^[0-9]{1,18}(.[0-9]{1,2})?$/';                // 募集资金|出借人认购金额
$contract_num = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9-]{1,50}$/u';     // 合同编号|开户行
$wait_capital = '/^[0-9]{1,18}(.[0-9]{1,2})?$/';                // 待还本金
$object_sn    = '/^[0-9]{1,6}$/u';                              // 标的号
$order_sn     = '/^[0-9]{1,10}$/u';                             // 投资订单号
$phone        = '/^.*$/';//'/^1[0-9]{10}$/u';                              // 手机号
$bank_number  = '/^[0-9]{20}$/u';                               // 银行卡号
$wait_date    = '/^[0-9]{3}$/u';                                // 待还期数
$repay_status = '/^[0-1]{1}$/';                                 // 是否已还
$all          = '/^.*$/';                                       // 合同地址


return [
    'borrow' => [
        '3' => [
            'checkField'    => [//脚本校验字段
                                'order_sn',
                                'object_sn',
                                'mobile_phone',
                                'old_user_id',
            ],
            'wait_capital'  => 28,
            'wait_interest' => 29,
            'order_sn'      => 21,
            'validate'      => [
                '0'  => $user_id,
                '1'  => $user_name,
                '2'  => $user_type,
                '3'  => $idno_type,
                '4'  => $indo,
                '10' => $object_sn,
                '11' => $p_name,
                '12' => $p_type,
                '13' => $p_limit_type,
                '14' => $p_limit_date,
                '15' => $rate,
                '16' => $loantype,
                '17' => $formatTime,
                '18' => $formatTime,
                '19' => $raise_money,
                '21' => $order_sn,
                '22' => $raise_money,
                '23' => $formatTime,
                '24' => $contract_num,
                '25' => $all,
                '26' => $all,
                '27' => $all,
                '28' => $wait_capital,
                '31' => $user_name,
                '32' => $user_type,
                '33' => $idno_type,
                '34' => $indo,
            ],
            'title'         => [
                '0'  => 'user_id*',
                '1'  => '出借人姓名*',
                '2'  => '出借人类型*（证件类型 1: 个人  2:机构）',
                '3'  => '证件类型*（证件类型 1: 身份证  2:护照  3:军官证  4:港澳台证件 5:营业执照）',
                '4'  => '证件号*',
                '5'  => '银行卡号 ',
                '6'  => '银行id（见银行信息索引）',
                '7'  => '开户分支行',
                '8'  => '持卡人姓名',
                '9'  => '手机号码',
                '10' => '标的号',
                '11' => '项目名称*',
                '12' => '项目类型*（见项目类型索引）',
                '13' => '项目期限类型（1:天 2:月）*',
                '14' => '借款期限*',
                '15' => '年化收益率*',
                '16' => '还款方式*（1:按季等额还款；2:按月等额还款；3:一次性还本付息 4:按月付息一次还本 5:按天一次性还款）',
                '17' => '计息时间*',
                '18' => '计划最大回款时间',
                '19' => '借款总额*',
                '20' => '项目简介',
                '21' => '订单号',
                '22' => '投资金额*',
                '23' => '投资时间*',
                '24' => '出借合同编号*',
                '25' => '出借合同地址*',
                '26' => '担保合同地址*',
                '27' => '出借咨询与服务协议地址*',
                '28' => '剩余待还本金',
                '29' => '剩余待还利息',
                '30' => '待还期数',
                '31' => '借款方名称',
                '32' => '借款方类型*（1:个人  2:机构）',
                '33' => '证件类型*（证件类型 1: 身份证  2:护照  3:军官证  4:港澳台证件 5:营业执照）',
                '34' => '借款方证件号*',
                '35' => '借款方地址',
                '36' => '借款方联系方式',
                '37' => '借款方简介',
                '38' => '借款方开户银行',
                '39' => '借款方银行卡号',
                '40' => '借款方法定代表人',
                '41' => '担保方名称',
                '42' => '担保方营业执照号',
                '43' => '担保方公司地址',
                '44' => '担保方联系方式',
                '45' => '担保方公司简介',
                '46' => '担保方开户银行',
                '47' => '担保方银行卡号',
                '48' => '法定代表人',
            ],
            'columns'       => [
                'old_user_id',
                'user_name',
                'user_type',
                'idno_type',
                'idno',
                'bank_number',
                'bank_id',
                'bankzone',
                'cardholder',
                'mobile_phone',
                'object_sn',
                'p_name',
                'p_type',
                'p_limit_type',
                'p_limit_num',
                'rate',
                'loantype',
                'value_date',
                'repayment_time',
                'raise_money',
                'p_desc',
                'order_sn',
                'rg_amount',
                'rg_time',
                'contract_number',
                'download',
                'danbao_download',
                'zixun_fuwu_download',
                'wait_capital',
                'wait_interest',
                'wait_date',
                'borrower_name',
                'b_type',
                'b_idno_type',
                'b_idno',
                'b_address',
                'b_mobile_phone',
                'b_desc',
                'b_bankzone',
                'b_bank_number',
                'b_legal_person',
                'guarantee_name',
                'g_license',
                'g_address',
                'g_mobile_phone',
                'g_desc',
                'g_bankzone',
                'g_bank_number',
                'g_legal_person',
                'remark',
                'addtime',
                'update_time',
                'file_id',
                'platform_id',
                'status'
            ],
        ],
        '4' => [
            'wait_capital' => '4',
            'order_sn' => '1',
            'validate' => [
                '0' => $user_id,
                '1' => $user_id,
                '2' => $raise_money,
                '3' => $formatTime,
                '4' => $raise_money,
            ],
            'title' => [
                '0' => 'user_id*',
                '1' => '出借记录id(deal_loan_id)*',
                '2' => '投资金额*',
                '3' => '投资时间*',
                '4' => '剩余待还本金*',
            ],
            'columns' => [
                'old_user_id',
                'order_sn',
                'rg_amount',
                'rg_time',
                'wait_capital',
                'remark',
                'addtime',
                'update_time',
                'file_id',
                'platform_id',
                'status'
            ],
        ],
        //交易所
        '5' => [
            'wait_capital'  => 19,
            'wait_interest' => 20,
            'validate'      => [
                '0'  => $user_name,
                '1'  => $user_type,
                '2'  => $idno_type,
                '3'  => $all,
                '7' => $phone,
                '8' => $all,
                '9' => $p_limit_type,
                '10' => $p_limit_date,
                '12' => $rate,
                '13' => $rate,
                '14' => $loantype,
                '15' => $formatTime,
                '16' => $formatTime,
                '17' => $raise_money,
                '19' => $raise_money,
                '21' => $all,
            ],
            'title'         => [
                '0'  => '投资人姓名*',
                '1'  => '投资人类型*（证件类型 1: 个人  2:机构）',
                '2'  => '证件类型*（证件类型 1: 身份证  2:护照  3:军官证  4:港澳台证件 5:营业执照）',
                '3'  => '证件号*',
                '4'  => '银行卡号',
                '5'  => '开户分支行 ',
                '6'  => '持卡人姓名',
                '7'  => '手机号码*',
                '8'  => '产品名称*',
                '9'  => '期限类型（1:天 2:月）*',
                '10' => '期限*',
                '11' => '募集金额',
                '12' => '最低年化收益率*',
                '13' => '最高年化收益率*',
                '14' => '还款方式*（1:按季等额还款；2:按月等额还款；3:一次性还本付息 4:按月付息一次还本 5:按天一次性还款 6:按季付息；9:半年付息）',
                '15' => '起息日*',
                '16' => '到期日*',
                '17' => '投资金额*',
                '18' => '应付利息',
                '19' => '剩余待还本金*',
                '20' => '剩余待还利息',
                '21' => '发行人/融资方简称*',
                '22' => '登记备案场所',
            ],
            'columns'       => [
                'user_name',
                'user_type',
                'idno_type',
                'idno',
                'bank_number',
                'bankzone',
                'cardholder',
                'mobile_phone',
                'p_name',
                'p_limit_type',
                'p_limit_num',
                'raise_money',//募集金额
                'rate',
                'max_rate',//最高利率 加字段
                'loantype',
                'value_date',
                'repayment_time',
                'rg_amount',
                'receivable_interest',// 应收利息 加子弹
                'wait_capital',
                'wait_interest',
                'borrower_name',
                'b_address',
                'remark',
                'addtime',
                'update_time',
                'file_id',
                'platform_id',
                'status'
            ],
        ],
    ],
    'repay'  => [
        '3' => [
            'capital'  => 3,
            'interest' => 4,
            'validate' => [
                '0' => $order_sn,
                '1' => $order_sn,
                '2' => $object_sn,
                '3' => $raise_money,
                '4' => $raise_money,
                '5' => $raise_money,
                '6' => $p_limit_date,
                '7' => $formatTime,
                '8' => $repay_status,
                '9' => $special
            ],
            'title'    => [
                '0' => '原还款计划id*',
                '1' => '出借订单号*',
                '2' => '标的号',
                '3' => '本金*',
                '4' => '利息*',
                '5' => '计划回款金额*',
                '6' => '还款期数*',
                '7' => '计划还款时间*',
                '8' => '是否已还 （1是 0否）*',
                '9' => '实际还款时间',
            ],
            'columns'  => [
                'repay_log_id',
                'order_sn',
                'object_sn',
                'capital',
                'interest',
                'total_money',
                'old_repay_num',
                'time',
                'repay_status',
                'real_time',
                'remark',
                'create_time',
                'update_time',
                'file_id',
                'platform_id',
                'status'
            ],
        ]
    ],
    'account' => [
        '4' => [
            'wait_amount' => 1,
            'validate' => [
                '0' => $user_id,
                '1' => $raise_money,
            ],
            'title' => [
                '用户ID*',
                '待加入金额*'
            ],
            'columns' => [
                'old_user_id',
                'wait_amount',
                'remark',
                'create_time',
                'update_time',
                'file_id',
                'platform_id',
                'status'
            ]
        ]

    ]
];
