<?php
/**
 * 系统常用字典定义
 * @author zhang ruoshi
 */
return array(
    'DICT_RELATIONSHIPS'=>array(//人与人之间的关系
        '1'=>'家人',
        '2'=>'同事',
        '3'=>'朋友',
    ),

    'SEASON_EQUAL_REPAY' => 1,
    'MONTH_EQUAL_REPAY' => 2,
    'ONCE_ALL_REPAY' => 3,
    'MONTH_INTEREST_REPAY' => 4,

    'LOAN_TYPE'=>array(//还款方式
        '1'=>'按季等额本息还款',
        '2'=>'按月等额本息还款',
        '3'=>'到期支付本金收益',
        '4'=>'按月支付收益到期还本',
        '5'=>'到期支付本金收益',
        '6'=>'按季支付收益到期还本',
        '7'=>'公益资助',
        '8'=>'等额本息固定日还款',
        '9'=>'按月等额本金',
        '10'=>'按季等额本金',
    ),

    'LOAN_TYPE_CN'=>array(//还款方式
        '1'=>'按季等额本息还款',
        '2'=>'按月等额本息还款',
        '3'=>'到期支付本金利息',
        '4'=>'按月支付利息到期还本',
        '5'=>'到期支付本金利息',
        '6'=>'按季支付利息到期还本',
        '7'=>'公益资助',
        '8'=>'等额本息固定日还款',
        '9'=>'按月等额本金',
        '10'=>'按季等额本金',
    ),


    'LOAN_TYPE_ENUM'=>array(
        'BY_SEASON'=>1,
        'BY_MONTH'=>2,
        'BY_ONCE_TIME'=>3,
        'BY_MONTH_INTEREST_REPAY'=>4,
        'BY_DAY'=>5,
        'BY_SEASON_INTEREST_REPAY'=>6,
        'BY_CROWDFUNDING'=>7,
        'BY_FIXED_DATE'=>8,
        'BY_MONTH_MATCH' => 9,
        'BY_SEASON_MATCH' => 10,
    ),

    'BORROW_FEE_TYPE'  =>  array( //借款项目的费用收取方式
        1   =>  "前收",
        2   =>  "后收",
        3   =>  "分期收",
        4   =>  "代销分期",
        5   =>  "固定比例前收",
        6   =>  "固定比例后收",
        7   =>  "固定比例分期收",
    ),

    'BORROW_FEE_TYPE_CN'  =>  array( //借款项目的费用收取方式
        1   =>  "借款发放时收取",
        2   =>  "还款时收取",
        3   =>  "分期收取",
        4   =>  "代销分期",
        5   =>  "借款发放时收取（固定比例）",
        6   =>  "还款时收取（固定比例）",
        7   =>  "分期收取（固定比例）",
    ),

    'LOAN_MONEY_TYPE'   =>  array(
        1   =>  "实际放款",
        2   =>  "非实际放款",
        3   =>  "受托支付",
    ),

    'LOAN_MONEY_TYPE_CN'   =>  array(
        1   =>  "实际放款",
        3   =>  "受托支付",
    ),


    'TWELVE_PERIOD' => 12,
    'NINE_PERIOD' => 9,
    'SIX_PERIOD' => 6,
    'THREE_PERIOD' => 3,

    'REPAY_TIME'=>array(//还款期限
        '12'=>'12个月',
        '9'=>'9个月',
        '6'=>'6个月',
        '3'=>'3个月',
    ),

    'DEAL_GUARANTOR_STATUS'=>array(//贷款担保人状态 deal_guarantor表status列
        '0'=>'未注册',
        '1'=>'已注册',
        '2'=>'同意担保',
        '3'=>'拒绝担保',
    ),

    /*'UPDATE_DEAL_LANG' => array(//用于前台 贷款修改 由..改为..的字段名显示
        //'name' => '贷款名称',
        //'sub_name' => '简短名称',
        'type_id' => '借款名称',    //借款用途
        //'cate_id' => '贷款分类',
        'agency_id' => '担保机构',
        'manager' => '客户经理',
        'manager_mobile' => '客户经理手机号码',
        'description' => '贷款描述',
        'borrow_amount' => '借款金额',
        //'min_loan_money' => '最低投标金额',
        'repay_time' => '借款期限',
        'rate' => '年利率',
        'enddate' => '筹标期限',
        'services_fee' => '成交服务费',
        'loantype' => '还款方式',
        //'warrant' => '担保范围',
        'loan_fee_rate' => '借款手续费',
        'guarantee_fee_rate' => '借款担保费',
        //'manage_fee_rate' => '平台管理费'
    ),*/

    //修改借款信息之后，不需要用户确认 edit by wenyanlei 20131112
    'UPDATE_DEAL_LANG' => array(),

    'DEAL_WARRANT' => array(//担保范围
        '0' => '无',
        '1' => '本金',
        '2' => '本金及利息'
    ),
    /* AJAX调用年利率*/
    'REPAY_MODE' => array(
        '1' => 'SEASON_EQUAL_MONTH_RATE',    //按季等额回款
        '2' => 'MONTH_EQUAL_MONTH_RATE',    //按月等额回款
        '3' => 'ONCE_BACK_MONTH_RATE',        //到期支付本金收益
        '5' => 'DAY_ONCE_BACK_MONTH_RATE', // 按天一次还本付息
    ),

    'INTEREST_REPAY_MODE' => array(
        '1' => 'SEASON_EQUAL_BACK_ANNUALIZED_RATE',  // 按季度的年化收益率
        '2' => 'MONTH_EQUAL_BACK_ANNUALIZED_RATE',   // 按月的年化收益率
        '3' => 'ONCE_BACK_ANNUALIZED_RATE',  // 一次性付款的年化收益率
        '5' => 'DAY_ONCE_BACK_ANNUALIZED_RATE'  // 按天一次性付款的年化收益率
    ),

    'REPAY_MONEY_MODE' => array(
        '1' => 'SEASON_EQUAL_BACK',
        '2' => 'MONTH_EQUAL_BACK',
        '3' => 'ONCE_BACK_PERIOD'
    ),
    'GET_REPAY' => array(
        '0' => 'SEASON_EQUAL_REPAY',
        '1' => 'MONTH_EQUAL_REPAY',
        '2' => 'ONCE_ALL_REPAY'
    ),
    'REPAY_PERIOD' => array(
        '3' => 'threeperiod',
        '6' => 'sixperiod',
        '9' => 'nineperiod',
        '12' => 'twelveperiod'
    ),
    'USER_SEX' => array(
        '-1' => '',
        '0' => '女士',
        '1' => '先生'
    ),
    'MSG_NOTICE_TITLE' => array(//站内消息 统一标题
        '1' => '系统通知',
        '2' => '材料通过',
        '3' => '材料驳回',
        '4' => '信用额度更新',
        '5' => '提现申请',
        '6' => '提现成功',
        '7' => '提现失败',
        '8' => '还款成功',
        '9' => '回款成功',
        '10' => '借款流标',
        '11' => '投标流标',
        '12' => '三日内还款',
        '13' => '标被留言',
        '14' => '标留言被回复',
        '15' => '借款投标过半',
        '16' => '借款满标',
        '17' => '还款失败',
        '18' => '投标完成',
        '19' => '投标放款',
        '37' => '智多新',
        '55' => '债权转让',
    ),

    'MONEY_APPLY_TYPE' => array(//账户余额申请类型
        '1'=>'申请',
        '2'=>'批准',
        '3'=>'拒绝',
    ),
    'CONTRACT_TYPE' => array(
        '1'=>'借款合同',
        '2'=>'委托担保合同',
        '3'=>'保证反担保合同',
        '4'=>'保证合同'
    ),

    'HANDLING_CHARGE' => '100',  // 充值手续费最高上限(圆)

    'WHITE_LIST' => array(
        'Xfjr' => '先锋支付',
    ),

    'CONTRACT_TPL_TYPE' => array(//合同模板系列类型
        'DF' => '标准合同',
        'HY' => '汇赢1号合同',//合同模板中的后缀名
        'CS' => '测试项目合同',
        'BJ' => '白金1号合同',
        'CD' => '车贷1号合同',
    ),

    // 汇赢担保公司id号
    'HY_DBGS' => '1',
    'HY_EMAIL' => 'guohaixia@ucfgroup.com', // 合同发送的邮件地址
    'HY_MOBILE' => '13910605329', // 发送的手机号

    //pdf \doc\docx\xls\xlsx\ppt\pptx\jpg\jpeg\mp4\rar\zip
    'P2B_ATTACHMENT_PATH' => './attachment/p2b/',
    'DEAL_FILE_PATH' => 'attachment/deal/',
    'PRESET_PATH' => 'attachment/preset/',
    'APP_PATH' => 'attachment/app/',
    'CONTRACT_PDF_PATH'    => APP_ROOT_PATH."assets/contract/",    //合同pdf地址

    'DEAL_FILE_TYPE' => array(
        "jpg","gif","png","jpeg","pdf","doc","docx","xls","xlsx","ppt","pptx","mp4","rar","zip"
    ),
    'MAX_UPLOAD' => "32 MB",

// 旧标单最后id号,大于此id号的标单使用pmt算法
    'OLD_DEAL_ID' => 254,

    //旧的按照365天计算的标
    'OLD_DEAL_DAY_ID' => 585,

   // 陈仲华确认所有按天一次性收益率设置为10%
    'DAY_ONCE_RATE' => '10',

    //短信通道类型
    'SMS_CHANNEL_TYPE' => array(
        'DOMESTIC'      => 0, //国内
        'INTERNATIONAL' => 1, //国际
    ),

    //允许提前执行还款的天数
    'DAY_OF_AHEAD_REPAY' => 10,

    //后台每次批量签署合同最大数量
    'CONT_SIGN_NUM' => 20,

    //投资人群，二进制复合状态
    'DEAL_CROWD' => array(
        '0' => '全部用户', // 000
        '1' => '新手专享', // 001
        '2' => '专享标',   // 010
        '4' => '手机专享', // 100
        '8' => '手机新手专享', // 1000
        '16' => '指定用户专享', // 10000
        '32' => '老用户专享',
        '33' => 'VIP用户专享',
      //  '34' => '批量导入可投用户',
    ),

    //投资限定条件2（0：全部,1:限个人用户，2限企业用户）
    'BID_RESTRICT' => array(
        '0' => '无限定', // 0
        '1' => '个人用户专享', // 1
        '2' => '企业用户专享',   // 2
    ),

    // 身份类型
    'ID_TYPE' => array(
        '1' => '内地居民身份证',
        '4' => '港澳居民来往内地通行证',
        '6' => '台湾居民往来大陆通行证',
        '2' => '护照',
        '3' => '军官证',
        '99' => '其他',
    ),

    //model变化通知人(如不想填写手机号，请留空)
    'MODEL_DIFF' => array(
        'liuzhenpeng@ucfgroup.com' => '13520286311',
        'wangyiming@ucfgroup.com' => '13510716568',
        'wangchuanlu@ucfgroup.com' => '18618135092',
        'daiyuxing@ucfgroup.com' => '15313660021',
        'jinhaidong@ucfgroup.com' => '18601165130',
        'wangjiantong@ucfgroup.com' => '15069181222',
    ),

    // 手机国际区号. is_show控制前台下拉框是否显示 1:显示; 0:不显示
    'MOBILE_CODE' => array(
            'cn' => array(
                    'country' => 'cn',
                    'code' => '86',
                    'name' => '中国',
                    'regex' => '^1[3456789]\d{9}$',
                    'is_show' => '1',
            ),
            'hk' => array(
                    'country' => 'hk',
                    'code' => '852',
                    'name' => '中国香港',
                    'regex' => '^[456789]\d{7}$',
                    'is_show' => '1',
            ),
            'mo' => array(
                    'country' => 'mo',
                    'code' => '853',
                    'name' => '中国澳门',
                    'regex' => '^[68]\d{7}$',
                    'is_show' => '1',
            ),
            'tw' => array(
                    'country' => 'tw',
                    'code' => '886',
                    'name' => '中国台湾',
                    'regex' => '^09\d{8}$',
                    'is_show' => '1',
            ),
            'us' => array(
                    'country' => 'us',
                    'code' => '1',
                    'name' => '美国',
                    'regex' => '^\d{10}$',
                    'is_show' => '1',
            ),
            'ca' => array(
                    'country' => 'ca',
                    'code' => '1',
                    'name' => '加拿大',
                    'regex' => '^\d{10}$',
                    'is_show' => '1',
            ),
            'uk' => array(
                    'country' => 'uk',
                    'code' => '44',
                    'name' => '英国',
                    'regex' => '^7\d{9}$',
                    'is_show' => '1',
            ),
    ),

    // 企业证件类别
    'CREDENTIALS_TYPE' => array(
        1 => '营业执照',
        //2 => '组织机构代码证',
        3 => '三证合一营业执照',
        //0 => '其他企业证件',
    ),

    // 机构列表
    'ORGANIZE_TYPE' => array(
        1 => '担保机构',
        2 => '咨询机构',
        3 => '平台机构',
        4 => '支付机构',
        5 => '管理机构',
        6 => '代垫机构',
        7 => '受托机构',
        8 => '代充值机构',
        9 => '交易所',
        10 => '渠道机构',
    ),

    // 机构列表
    'ORGANIZE_TYPE_CN' => array(
        1 => '担保机构',
        2 => '咨询机构',
        4 => '支付机构',
        5 => '管理机构',
        6 => '代垫机构',
        8 => '代充值机构',
    ),

    // 用户账户类型
    'ENTERPRISE_PURPOSE' => [
        0 => [
            'bizId' => 0,
            'bizName' => '借贷混合用户', // 个人用户专用
            'supervisionBizType' => '06', // 06-借贷混合户
        ],
        1 => [
            'bizId' => 1,
            'bizName' => '投资户', // 充值、提现、投资、回款
            'supervisionBizType' => '01', // 01-投资户
        ],
        2 => [
            'bizId' => 2,
            'bizName' => '融资户', // 充值、提现、借款、还款
            'supervisionBizType' => '02', // 02-借款户
        ],
        3 => [
            'bizId' => 3,
            'bizName' => '咨询户', // 充值、提现、收费
            'supervisionBizType' => '04', // 04-咨询户
            'organize_type' => 2, // 对应ORGANIZE_TYPE的机构列表
        ],
        4 => [
            'bizId' => 4,
            'bizName' => '担保户', // 充值、提现、代偿、收费、追偿（代偿还款）
            'supervisionBizType' => '03', // 03-担保户
            'organize_type' => 1, // 对应ORGANIZE_TYPE的机构列表
        ],
        5 => [
            'bizId' => 5,
            'bizName' => '渠道户', // 充值、提现、收费
            'supervisionBizType' => '',
        ],
        6 => [
            'bizId' => 6,
            'bizName' => '渠道虚拟户', // 充值、提现、收费
            'supervisionBizType' => '',
        ],
        7 => [
            'bizId' => 7,
            'bizName' => '资产收购户', // 充值、提现、代偿、收费、追偿（还代偿款）
            'supervisionBizType' => '03', // 03-担保户
        ],
        8 => [
            'bizId' => 8,
            'bizName' => '代垫户', // 充值、提现、代偿、收费、追偿（还代偿款）
            'supervisionBizType' => '03', // 03-担保户
        ],
        9 => [
            'bizId' => 9,
            'bizName' => '受托资产管理户', // 充值、提现、转账、结算，不需要开存管账户
            'supervisionBizType' => '',
        ],
        10 => [
            'bizId' => 10,
            'bizName' => '交易中心（所）', // 充值、提现、转账、结算，不需要开存管账户
            'supervisionBizType' => '',
        ],
        11 => [
            'bizId' => 11,
            'bizName' => '平台户', // 充值、提现、代偿、追偿（还代偿款）、收费、返利
            'supervisionBizType' => '05', // 05-p2p平台户
        ],
        12 => [
            'bizId' => 12,
            'bizName' => '保证金户', // 充值、提现、代偿、收费、追偿（还代偿款）
            'supervisionBizType' => '',
        ],
        13 => [
            'bizId' => 13,
            'bizName' => '支付户', // 充值、提现、收费，不需要开存管账户
            'supervisionBizType' => '',
        ],
        14 => [
            'bizId' => 14,
            'bizName' => '投资券户', // 充值、提现、返利
            'supervisionBizType' => '12', // 12-第三方营销用户
        ],
        15 => [
            'bizId' => 15,
            'bizName' => '红包户', // 充值、提现、返利
            'supervisionBizType' => '12', // 12-第三方营销用户
        ],
        16 => [
            'bizId' => 16,
            'bizName' => '代充值户', // 充值、提现、代偿、收费、追偿（还代偿款）
            'supervisionBizType' => '03', // 03-担保户
        ],
        17 => [
            'bizId' => 17,
            'bizName' => '放贷户', // 充值、提现、投资、回款
            'supervisionBizType' => '',
        ],
        18 => [
            'bizId' => 18,
            'bizName' => '垫资户', // 垫资
            'supervisionBizType' => '13', // 13-垫资户
        ],
        19 => [
            'bizId' => 19,
            'bizName' => '管理户', // 充值、提现、收费
            'supervisionBizType' => '10', // 10-平台收费户
        ],
        20 => [
            'bizId' => 20,
            'bizName' => '商户账户', // 黄金账户
            'supervisionBizType' => '',
        ],
        21 => [
            'bizId' => 21,
            'bizName' => '营销补贴户', // 充值、提现、返利
            'supervisionBizType' => '12', // 12-第三方营销用户
        ],
    ],

    // 用户账户类型
    'ENTERPRISE_PURPOSE_CN' => [
        1 => [
            'bizId' => 1,
            'bizName' => '网贷P2P账户', // 充值、提现、投资、回款
            'supervisionBizType' => '01', // 01-投资户
        ],
        2 => [
            'bizId' => 2,
            'bizName' => '网贷借款户', // 充值、提现、借款、还款
            'supervisionBizType' => '02', // 02-借款户
        ],
        3 => [
            'bizId' => 3,
            'bizName' => '咨询户', // 充值、提现、收费
            'supervisionBizType' => '04', // 04-咨询户
            'organize_type' => 2, // 对应ORGANIZE_TYPE的机构列表
        ],
        4 => [
            'bizId' => 4,
            'bizName' => '担保户', // 充值、提现、代偿、收费、追偿（代偿还款）
            'supervisionBizType' => '03', // 03-担保户
            'organize_type' => 1, // 对应ORGANIZE_TYPE的机构列表
        ],
        8 => [
            'bizId' => 8,
            'bizName' => '代垫户', // 充值、提现、代偿、收费、追偿（还代偿款）
            'supervisionBizType' => '03', // 03-担保户
        ],

        13 => [
            'bizId' => 13,
            'bizName' => '支付户', // 充值、提现、收费，不需要开存管账户
            'supervisionBizType' => '',
        ],
        14 => [
            'bizId' => 14,
            'bizName' => '优惠券户', // 充值、提现、返利
            'supervisionBizType' => '12', // 12-第三方营销用户
        ],
        15 => [
            'bizId' => 15,
            'bizName' => '红包户', // 充值、提现、返利
            'supervisionBizType' => '12', // 12-第三方营销用户
        ],
        16 => [
            'bizId' => 16,
            'bizName' => '代充值户', // 充值、提现、代偿、收费、追偿（还代偿款）
            'supervisionBizType' => '03', // 03-担保户
        ],
    ],

    'CREDITLOAN_BANKLIST' => array(
        array('id'=>1,'name'=>'海口联合农商银行','is_effect'=>1),
        array('id'=>2,'name'=>'渤海银行','is_effect'=>0),
        array('id'=>3,'name'=>'江苏银行','is_effect'=>0),
        array('id'=>4,'name'=>'华夏银行','is_effect'=>0),
        array('id'=>5,'name'=>'天津银行','is_effect'=>0),
    ),

    'DEAL_TYPE_ID_CN' => array(
        array('id'=>4,'name'=>'个人消费'),
        array('id'=>16,'name'=>'产融贷'),
        array('id'=>29,'name'=>'消费贷'),
        array('id'=>30,'name'=>'消费分期'),
        array('id'=>34,'name'=>'闪电消费'),
        array('id'=>35,'name'=>'消费贷闪信贷'),
        array('id'=>38,'name'=>'消费贷功夫贷'),
        array('id'=>39,'name'=>'车贷车通贷'),
        array('id'=>40,'name'=>'消费贷优易借'),
        array('id'=>41,'name'=>'闪电消费（线上）'),
        array('id'=>42,'name'=>'供应链店商贷'),
    ),

    //借款客群
    'LOAN_USER_CUSTOMER_TYPE' => array(
        1 => '普通消费者',
        2 => '小微企业',
        3 => '个体工商户',
        4 => '自就业者',
    ),

    //功夫贷银行代码映射表
    'GFD_BANKLIST' => array(
        array('id'=>1,'name'=>'招商银行','is_effect'=>1),
        array('id'=>2,'name'=>'广发银行','is_effect'=>1),
        array('id'=>3,'name'=>'光大银行','is_effect'=>1),
        array('id'=>5,'name'=>'建设银行','is_effect'=>1),
        array('id'=>6,'name'=>'民生银行','is_effect'=>1),
        array('id'=>7,'name'=>'农业银行','is_effect'=>1),
        array('id'=>8,'name'=>'浦发银行','is_effect'=>1),
        array('id'=>9,'name'=>'兴业银行','is_effect'=>1),
        array('id'=>10,'name'=>'中国银行','is_effect'=>1),
        array('id'=>11,'name'=>'中信银行','is_effect'=>1),
        array('id'=>12,'name'=>'工商银行','is_effect'=>1),
        array('id'=>13,'name'=>'交通银行','is_effect'=>1),
        array('id'=>14,'name'=>'平安银行','is_effect'=>1),
    ),
);
?>
