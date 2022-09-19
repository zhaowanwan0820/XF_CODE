<?php
namespace core\service\duotou;

use core\service\deal\P2pDepositoryService;

class DtP2pDealBaseService extends P2pDepositoryService
{
    const FREEZETYPE_TYPE_DTBID = '01'; // 冻结类型 -- 预约投资
}
