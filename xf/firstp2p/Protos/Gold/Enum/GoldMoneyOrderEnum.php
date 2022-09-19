<?php
namespace NCFGroup\Protos\Gold\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class GoldMoneyOrderEnum extends AbstractEnum {

    //黄金相关 1-100
    const BIZ_SUBTYPE_GOLD_WITHDRAW = 1; //黄金变现
    const BIZ_SUBTYPE_GOLD_WITHDRAW_FEE = 2; //黄金变现手续费
    const BIZ_SUBTYPE_GOLD_COUPON_INTEREST = 3; //黄金结息
    const BUYGOLDLOCK = 4; //投资买金冻结
    const BUYGOLDFEELOCK = 5;//投资人买金手续费冻结
    const BUYGOLDRELEASELOCK =  6;//投资买金冻结释放
    const BUYGOLDFEERELEASELOCK =  7;//投资买金手续费冻结释放
    const BUYGOLDCURRENT = 8;//购买优金宝
    const BUYGOLDCURRENTFEE = 9;//购买优金宝手续费
    const SELLGOLDCURRENT = 10;//销售优金宝
    const SELLGOLDCURRENTFEE = 11;//销售优金宝手续费
    const BUYGOLDDISCOUNT = 12;//购买优金宝黄金券
    const BUYGOLDDISCOUNTFEE = 13;//购买优金宝手续费黄金券
    const SELLGOLDDISCOUNT = 14;//销售优金宝黄金券
    const SELLGOLDDISCOUNTFEE = 15;//销售优金宝手续费黄金券
    const BIZ_SUBTYPE_GOLD_LOAN_DEDUCT_LOCK = 16;// 定期投资放款买金解冻
    const BIZ_SUBTYPE_GOLD_LOAN_BORROW = 17;// 定期投资放款货款划转
    const BIZ_SUBTYPE_GOLD_LOAN_BORROW_PAY_FEE = 18;// 定期放款支出给支付机构服务费
    const BIZ_SUBTYPE_GOLD_LOAN_BORROW_FEE = 19;// 定期放款支出给平台手续费
    const BIZ_SUBTYPE_GOLD_LOAN_PAY_FEE = 20;// 定期放款增加支付服务费
    const BIZ_SUBTYPE_GOLD_LOAN_FEE = 21;// 定期放款增加平台服务费
    const BIZ_SUBTYPE_GOLD_LOAN_DEDUCT_LOCK_FEE = 22; //定期投资放款买金手续费解冻
    const BIZ_SUBTYPE_GOLD_LOAN_BORROW_BUY_FEE = 23; //定期投资放款购买人手续费划转
    const DELIVERGOLDFEELOCK= 24;//提金手续费冻结
    const DELIVERGOLDFEERELEASELOCK= 25;//提金手续费冻结释放
    const BIZ_SUBTYPE_GOLD_DELIVER_FEE = 26;// 提金手续费
    const BIZ_SUBTYPE_GOLD_TECH_FEE = 27;// 黄金技术服务续费（优长金技术服务费）
    const BIZ_SUBTYPE_GOLD_BORROW_TECH_FEE = 28;// 黄金借款人技术服务费（优长金借款人技术服务费）
    const BIZ_SUBTYPE_GOLD_CHARGE_FEE = 29;//黄金收费（优金宝导流服务费和技术服务费）



    // 所有业务子类型, 此map必须定义，否则黄金订单对账服务验证失败
    public static $subtypeDesc = [
        self::BIZ_SUBTYPE_GOLD_WITHDRAW => '变现',
        self::BIZ_SUBTYPE_GOLD_WITHDRAW_FEE => '变现手续费',
        self::BIZ_SUBTYPE_GOLD_COUPON_INTEREST => '结息',
        self::BUYGOLDLOCK => '买金冻结',
        self::BUYGOLDFEELOCK => '买金手续费冻结',
        self::BUYGOLDRELEASELOCK => '买金冻结释放',
        self::BUYGOLDFEERELEASELOCK => '买金手续费冻结释放',
        self::BUYGOLDCURRENT => '购买优金宝',
        self::BUYGOLDCURRENTFEE => '购买优金宝手续费',
        self::SELLGOLDCURRENT => '售出优金宝',
        self::SELLGOLDCURRENTFEE => '售出优金宝手续费',
        self::BUYGOLDDISCOUNT => '购买优金宝黄金券',
        self::BUYGOLDDISCOUNTFEE => '购买优金宝手续费黄金券',
        self::SELLGOLDDISCOUNT => '售出优金宝黄金券',
        self::SELLGOLDDISCOUNTFEE => '售出优金宝手续费黄金券',
        self::BIZ_SUBTYPE_GOLD_LOAN_DEDUCT_LOCK =>  '定期投资放款买金解冻',
        self::BIZ_SUBTYPE_GOLD_LOAN_BORROW => '定期投资放款货款划转',
        self::BIZ_SUBTYPE_GOLD_LOAN_BORROW_PAY_FEE => '定期放款支出给支付机构服务费',
        self::BIZ_SUBTYPE_GOLD_LOAN_BORROW_FEE => '定期放款支出给平台手续费',
        self::BIZ_SUBTYPE_GOLD_LOAN_PAY_FEE => '定期放款增加支付服务费',
        self::BIZ_SUBTYPE_GOLD_LOAN_FEE => '定期放款增加平台服务费',
        self::BIZ_SUBTYPE_GOLD_LOAN_DEDUCT_LOCK_FEE => '定期买金手续费解冻',
        self::BIZ_SUBTYPE_GOLD_LOAN_BORROW_BUY_FEE => '定期买金手续费划转',
        self::DELIVERGOLDFEELOCK=>'提金手续费冻结',
        self::DELIVERGOLDFEERELEASELOCK=>'提金手续费冻结释放',
        self::BIZ_SUBTYPE_GOLD_DELIVER_FEE => '提金手续费',
        self::BIZ_SUBTYPE_GOLD_TECH_FEE => '黄金技术服务续费',
        self::BIZ_SUBTYPE_GOLD_BORROW_TECH_FEE => '黄金借款人技术服务费',
        self::BIZ_SUBTYPE_GOLD_CHARGE_FEE => '黄金收费',
    ];
}
