<?php
namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class RelatedEnum extends AbstractEnum {

    const STATUS_USELESS    = 2; //停用
    const STATUS_USEFUL     = 1; //启用

    const CHANNEL_NCFWX     = 1; //适用网信
    const CHANNEL_NCFPH     = 2; //适用普惠

    const USER_TYPE_COMPANY  = 0; //关联企业
    const USER_TYPE_USER     = 1; //关联个人

    const RELATED_TYPE_OTHER  = 3; //关联形式-其他
    const RELATED_MODE_OTHER  = 3; //关联关系-其他

    // 加密字段
    static $DBDES_FIELDS = array(
        'serialno',
        'license',
        'related_name',
        'enname',
        'address',
        'related_user',
        'remark',
        'idno',
        'name',
    );

    //适用渠道
    static $CHANNELS = array(
        self::CHANNEL_NCFWX => '网信',
        self::CHANNEL_NCFPH => '普惠',
    );

    //所属公司
    static $STATUS = array(
        self::STATUS_USELESS => '停用',
        self::STATUS_USEFUL => '启用',
    );

    //所属公司
    static $RELATED_COMPANYS = array(
        '1' => '北京经讯时代科技有限公司',
        '2' => '北京网信金服信息科技有限公司',
        '3' => '北京网信金服信息科技有限公司北京第一分公司',
        '4' => '北京网信金服信息科技有限公司北京第二分公司',
        '5' => '北京网信金服信息科技有限公司南京分公司',
        '6' => '北京网信云服信息科技有限公司',
        '7' => '北京网信云服信息科技有限公司北京第一分公司',
        '8' => '深圳一房羽融金融信息科技服务有限公司',
        '9' => '上海网信普惠商务咨询有限公司',
        '10' => '新族（北京）科技有限公司',
        '11' => '北京盈华财富投资管理股份有限公司',
        '12' => '深圳盈信基金销售有限公司',
        '13' => '上海岑慕商务信息咨询有限公司',
        '14' => '天津圆融资产管理有限公司',
        '15' => '北京东方联合投资管理有限公司',
    );

    //职务
    static $POSITIONS = array(
        '1' => '实际控制人',
        '2' => '股东',
        '3' => '董事长',
        '4' => '执行董事',
        '5' => '董事',
        '6' => '法定代表人',
        '7' => '监事',
        '8' => '经理（工商）',
        '9' => 'CEO',
        '10' => 'COO',
        '11' => 'CTO',
        '12' => 'CFO',
        '13' => 'CCO',
        '14' => 'CRO',
    );

    //关联形式
    static $RELATED_TYPES = array(
        '1' => '直接持股',
        '2' => '间接持股',
        '3' => '其他',
    );

    //关联关系
    static $RELATED_MODES = array(
        '1' => '关联控制',
        '2' => '关联重大影响',
        '3' => '其他',
    );
}
