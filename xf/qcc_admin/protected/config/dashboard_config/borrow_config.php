<?php
//相关管理相关的选项
return array(
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
      'offlinedb',//offline
    ),
);
?>






















