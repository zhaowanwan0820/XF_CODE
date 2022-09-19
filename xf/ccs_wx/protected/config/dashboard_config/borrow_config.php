<?php
//相关管理相关的选项
return array(
    //项目类型>项目期限>年化收益关系
    'duration' => array(
        2 => array(
            3 => 11,
            6 => 12,
            9 => 13,
            12 => 14,
        ),
        6 => array(
            1 => 7,
            2 => 7,
            6 =>array(10,11),
        ),
        5 => array(
            12 => 12.5,
            24 => 14,
        ),
        7 => array(
            6 => 10,
        ),
        /*8 => array(
            3 => 8,
            6 => 10,
            12 => 12,
        ),*/
        200 =>array(
            3 =>8,
        ),
        201 =>array(
            6 =>10,
        ),
        202 =>array(
            12 =>12,
        ),
        100 =>array(
            3 =>8,
        ),
        101 =>array(
            6 =>10,
        ),
        102 =>array(
            12 =>12,
        ),
        302 =>array(
            12 =>12,
        ),
    ),
    //项目类型
    'type' => array(
        2 => '爱担保',
        6 => '爱保理',
        5 => '爱融租',
        7 => '爱收藏',
        200 => '省心计划（小贷类）A套餐',
        201 => '省心计划（小贷类）B套餐',
        202 => '省心计划（小贷类）C套餐',
        100 => '省心计划（典当类）A套餐',
        101 => '省心计划（典当类）B套餐',
        102 => '省心计划（典当类）C套餐',
        302 => '省心计划（影视类）C套餐'
    ),
    //借款模式
    'borrow_mode' => array( '转让模式','担保模式','借款模式' ),
    //项目类型与借款模式联动
    'type_borrow_mode' => array(
        2 => array(1),
        5 => array(0),
        6 => array(0),
        7 => array(1),
        100 => array(1),
        101 => array(1),
        102 => array(1),
        200 => array(1, 0),
        201 => array(1, 0),
        202 => array(1, 0),
        302 => array(1, 2)
    ),
    //融租类型
    'rzt_status' => array('回租', '直租'),
    //保理类型
    'factoring_type' => array(
        1 => '明保理',
        2 => '暗保理',
        3 => '反向保理'
     ),
     //计息方式
    'delay_value_days' => array(
        0 => '当日计息',
        1 => '次日计息'
     ),
     //还款方式
    'style' => array(
        0 => '按月付息，到期还本',
        1 => '到期还本付息',
        3 => '按季付息，到期还本',
        5 => '等额本息,按月付款'
     ),
     //是否续借企业
    'is_renew' => array(
        0 => '否',
        1 => '是'
     ),
     //法定节假日
     /*元旦：2016.01.01、2017.01.01、2018.01.01
            春节：2016.02.08、2017.01.28、2018.02.16
            清明：2016.04.04、2017.04.04、2018.04.04
            五一：2016.05.01、2017.05.01、2018.05.01
            端午：2016.06.09、2017.05.30、2018.06.18
            中秋：2016.09.15、2017.10.04、2018.09.24
            国庆：2016.10.01、2017.10.01、2018.10.01*/
      'holidays' => array(
            '1451577600',//2016.01.01
            '1483200000',//2017.01.01
            '1514736000',//2018.01.01
            '1454860800',//2016.02.08
            '1485532800',//2017.01.28
            '1518710400',//2018.02.16
            '1459699200',//2016.04.04
            '1491235200',//2017.04.04
            '1522771200',//2018.04.04
            '1462032000',//2016.05.01
            '1493568000',//2017.05.01
            '1525104000',//2018.05.01
            '1465401600',//2016.06.09
            '1496073600',//2017.05.30
            '1529251200',//2018.06.18
            '1473868800',//2016.09.15
            '1507046400',//2017.10.04
            '1537718400',//2018.09.24
            '1475251200',//2016.10.01
            '1506787200',//2017.10.01
            '1538323200',//2018.10.01
     ),
    'status' => array(
        0 => '待预告',
        1 => '开放投资中',
        3 => '投资已满项目存续期',
        4 => '项目结束已还款还息',
        5 => '担保公司代偿中',
        6 => '担保公司已代偿',
        7 => '已提前还款',
        8 => '资管公司回购',
        9 => '担保公司提前代偿',
        11 => '交易取消',
        100 => '预发布',
        101 => '预告中'
    ),
     //办事处
    'project_city' => array(
        2 => '北京',
        3 => '河南',
        4 => '重庆',
        5 => '山东',
        6 => '上海',
        7 => '内蒙古',
        8 => '江西'
     ),
     //项目来源
    'project_source' => array(
        1 => '合作机构推荐',
        2 => '项目人员开发'
     ),
    //年化收益
    'apr' => array(
        '7.00' => '7%',
        '7.50' => '7.5%',
        '8.00' => '8%',
        '8.50' => '8.5%',
        '9.00' => '9%',
        '9.50' => '9.5%',
        '10.00' => '10%',
        '10.50' => '10.5%',
        '11.00' => '11%',
        '11.50' => '11.5%',
        '12.00' => '12%',
        '12.50' => '12.5%',
        '13.00' => '13%',
        '13.50' => '13.5%',
        '14.00' => '14%',
        '14.50' => '14.5%'
     ),
    //加息收益
    'increase_apr' => array(
        0 => '0%'
     ),
     //内部人员审核状态
    'internal_audit_status' => array(
        0 => '未审核',
        1 => '通过',
        2 => '不通过'
     ),
     //机构审核状态
    'guarantor_status' => array(
        0 => '未审核',
        1 => '通过',
        2 => '不通过'
     ),
     //拆分状态
    'financing_status' => array(
        0 => '未拆分',
        1 => '已拆完',
        2 => '拆分中'
     ),
    //项目投资权
    'priority_type' => array(
        0 => '常规',
        1 => '站岗资金投资特权',
        2 => '只有新手能投资'
     ),
    //投资此项目是否送券
    'return_coupon' => array(
        1 => '是',
        2 => '否'
     ),
    //投资上限
    'most_account' => array(
        0 => '无限制',
        100000 => '10万'
     ),
     //是否有抵押物
    'exist_collateral' => array(
        0 => '无',
        1 => '有'
     ),
     //合同隐藏
    'is_hide' => array(
        1 => '是',
        2 => '否'
     ),
         //还款主体
    'repayment_subject' => array(
        1 => '企业还款',
        2 => '机构代偿'
     ),
     //债务人证件类型
    'borrower_card_type' => array('其他', '身份证', '军官证', '台胞证', '护照', '营业执照'),
    //线上募资天数
    'valid_time' => 60,
    //最低投资金额
    'lowest_account' => 100,
    //投资递增金额
    'invest_step' => 100,
    //最低融资额
    'lowest_financing_amount' => 500000,
    //需要加密解密的字段
    'keep_enc' => array(
        'mobile',
        'idno',
    ),
    //需要脱敏并保留最后一位的字段
    'keep_one' => array(
        'realname',
        'real_name',
        'user_name',
        'name',
        'acntToName',
        'friends_username',
        'contactperson',
        'business_entity',
        'company_phone',
        'user_realname',
        'mate_name',
        'linkman1',
        'linkman2',
        'linkman3',
        'guar_business_entity',
        'customstatus',
    ),
    //需要脱敏并保留后四位的字段
    'keep_four' => array(
        'card_id',
        'bank_id',
        'phone',
        'tel',
        'account',//账号，提现账号
        'acntToNo',
        'bankCode',
        'card_number',
        'borrower',
        'borrower_desc',
        'original_creditor',
        'original_creditor_id',
        'bankcardid',
        'business_license',
//        'mobile',
        'user_phone',
        'user_tel',
        'mate_phone',
        'mate_tel',
        'phone1',
        'tel1',
        'tel2',
        'phone2',
        'tel3',
        'phone3',
        'user_phone',
        'card_number',
		'password',
		'paypassword',
    ),
    'sql_query_db' => array(
      'fdb',//尊享
      'phdb',//普惠
      'contractdb',//合同
      'yjdb',//金融工场
    ),
);
?>





















