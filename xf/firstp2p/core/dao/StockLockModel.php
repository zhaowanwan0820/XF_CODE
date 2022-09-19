<?php
/**
 * StockLockModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 */

namespace core\dao;

use libs\utils\Logger;

class StockLockModel extends BaseModel
{

    // 冻结
    const STATUS_LOCK = 1;

    // 解冻
    const STATUS_UNLOCK = 2;

    // 扣减冻结
    const STATUS_PAYED = 3;
}
