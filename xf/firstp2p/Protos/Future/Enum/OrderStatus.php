<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class OrderStatus extends AbstractEnum
{
    const INIT = 0; // 初始
    const ACPT = 1; // 已授理
    const SUCC = 2; // 成功, 操盘中
    const TFAIL = 3; // 技术失败
    const BFAIL = 4; // 业务失败
    const CLOSE = 5; // 终止
}
