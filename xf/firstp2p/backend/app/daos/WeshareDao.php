<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Library\Logger;
use NCFGroup\Ptp\models\Firstp2pWeshareInfoDisclosure;

/**
 * WeshareDao
 * 掌众数据库相关操作
 *
 * @package default
 */
class WeshareDao
{
    /**
     * getWeshareInfoDisclosureInfo
     * 获取掌众信息披露信息
     *
     * @param $productType,$investTerm,$investUnit
     * @static
     * @access public
     * @return $info
     */
    public static function getWeshareInfoDisclosureInfo($productType,$investTerm,$investUnit)
    {
        $productType = intval($productType);
        $investTerm = intval($investTerm);
        $investUnit = intval($investUnit);
        $conditons = 'productType = :productType: and investTerm = :investTerm: and investUnit = :investUnit:';
        $parameters = [
                'productType' => $productType,
                'investTerm' => $investTerm,
                'investUnit' => $investUnit,
        ];
        $info = Firstp2pWeshareInfoDisclosure::findFirst([
                $conditons,
                'bind' => $parameters,
        ]);
        return $info;
    }
}
