<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 请求用户已购买基金
 *
 * 由代码生成器生成, 不可人为修改
 * @author chengQ <qicheng@ucfgroup.com>
 */
class RequestGetPurchaseOrders extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 用户Id
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 类型
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 版本标识
     *
     * @var int
     * @required
     */
    private $version;

    /**
     * app版本号
     *
     * @var int
     * @optional
     */
    private $appVersion = 0;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetPurchaseOrders
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestGetPurchaseOrders
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestGetPurchaseOrders
     */
    public function setType($type = 0)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     * @return RequestGetPurchaseOrders
     */
    public function setVersion($version)
    {
        \Assert\Assertion::integer($version);

        $this->version = $version;

        return $this;
    }
    /**
     * @return int
     */
    public function getAppVersion()
    {
        return $this->appVersion;
    }

    /**
     * @param int $appVersion
     * @return RequestGetPurchaseOrders
     */
    public function setAppVersion($appVersion = 0)
    {
        $this->appVersion = $appVersion;

        return $this;
    }

}