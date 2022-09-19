<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class LhFundState extends AbstractEnum
{
    //宜投的基金状态码集合（参考宜投字段说明，将对应状态码由16进制转换为10进制之后的，fund_ext表里存放的就是10进制状态码）
    const STATUS_NORMAL = '0'; // 正常: 可以申购、可以赎回 -> ONLINE  契约没有
    const STATUS_PUBLISH = '1'; // 发行: 可以认购、 ... 、不可赎回 -> SUBSCRIBE 认购=募集期
    const STATUS_CONFIRM_SUCC = '2'; // 发行成功: 不可认购、不可申购、不可赎回 -> OFFLINE  瞬间状态
    const STATUS_CONFIRM_FAIL = '3'; // 发行失败: 不可认购、不可申购、不可赎回 -> OFFLINE  募集失败
    const STATUS_STOP_TRADE = '4'; // 停止交易: 不可认购、不可申购、不可赎回 -> OFFLINE    停止交易
    const STATUS_STOP_APPLY = '5'; // 停止申购: ... 、不可申购、可以赎回 -> REDEEM    契约没有
    const STATUS_STOP_REDEEM = '6'; // 停止赎回: ... 、可以申购、不可赎回 -> APPLY  申购=停止赎回期
    const STATUS_REG_RIGHT = '7'; // 权益登记: ...
    const STATUS_SEND_BONUS = '8'; // 红利发放: ...
    const STATUS_CLOSE = '9'; // 基金封闭: 不可认购、不可申购、不可赎回 -> CLOSE    封闭期
    const STATUS_SHUTDOWN = '10'; // 基金终止: 不可认购、不可申购、不可赎回 -> OFFLINE  结束
    const STATUS_BOOK_BEGON = '11'; // 预约开始: 不可认购、不可申购、不可赎回 -> BOOKING
    const STATUS_BOOK_END = '12'; // 预约结束: 不可认购、不可申购、不可赎回 -> BOOKING

    //投资前的状态显示
    public static $statusBeforeShowMap = [
        self::STATUS_PUBLISH => '认购',
        self::STATUS_CLOSE => '封闭期',
        self::STATUS_STOP_REDEEM => '申购',
        self::STATUS_SHUTDOWN => '结束',
        self::STATUS_CONFIRM_FAIL => '募集失败',
        self::STATUS_STOP_TRADE => '停止交易',
    ];

    //已投后的状态显示
    public static $statusAfterShowMap = [
        self::STATUS_PUBLISH => '募集期',
        self::STATUS_CLOSE => '封闭期',
        self::STATUS_STOP_REDEEM => '申购开放',//'停止赎回期',
        self::STATUS_SHUTDOWN => '已收回',//'结束',
        self::STATUS_CONFIRM_FAIL => '募集失败',
        self::STATUS_STOP_TRADE => '停止交易',
    ];
}
