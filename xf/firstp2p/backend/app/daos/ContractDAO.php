<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Ptp\models\Firstp2pDealQueue;
use NCFGroup\Ptp\models\Firstp2pContract;

class ContractDAO
{
    /**
     * 获得合同列表
     * @param Pageable $pageable
     * @param array $conditon
     * @return array
     */
    public static function getContractList(Pageable $pageable, $conditon) {
        $list = Firstp2pContract::findByPageable($pageable, $conditon);
        return $list->toArray();
    }


}

