<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;
use NCFGroup\Common\Library\Date\XDateTime;

//use NCFGroup\Ptp\models\Firstp2pCfpCustomerMap;
use NCFGroup\Ptp\models\cfp\CfpCustomerMap;

/**
 * CustomerDAO
 * 理财是客户相关DAO
 *
 * @package default
 */
class CustomerDAO
{
    /**
     * addMemoForCustomer
     * 修改客户备注
     *
     * @param mixed $cfpId
     * @param mixed $customerId
     * @param mixed $memo
     * @static
     * @access public
     * @return void
     */
    public static function addMemoForCustomer($cfpId, $customerId, $memo)
    {
        $obj = CfpCustomerMap::findFirst(array(
            'cfpId = :cfpId: AND customerId = :customerId:',
            'bind' => array('cfpId' => $cfpId, 'customerId' => $customerId),
        ));
        if (empty($obj)) {
            $obj = new CfpCustomerMap();
            $obj->cfpId = $cfpId;
            $obj->customerId = $customerId;
            $obj->ctime = XDateTime::now()->toString();
        }
        $obj->memo = $memo;
        $obj->mtime = XDateTime::now()->toString();
        return $obj->save();
    }

    /**
     * getMemoByCfpIdAndCustomerId
     * 获取客户的备注信息
     *
     * @param mixed $cfpId
     * @param mixed $customerId
     * @static
     * @access public
     * @return void
     */
    public static function getMemoByCfpIdAndCustomerId($cfpId, $customerId)
    {
        $obj = CfpCustomerMap::findFirst(array(
            'cfpId = :cfpId: AND customerId = :customerId:',
            'bind' => array('cfpId' => $cfpId, 'customerId' => $customerId),
        ));
        if (empty($obj)) {
            return "";
        } else {
            return $obj->memo;
        }
    }
}
