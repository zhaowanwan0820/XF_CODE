<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取用户股票映射
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestUserStockMapList extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @optional
     */
    private $userId = -1;

    /**
     * 股票用户ID
     *
     * @var string
     * @optional
     */
    private $stockUserId = '';

    /**
     * 身份证号
     *
     * @var string
     * @optional
     */
    private $cardId = '';

    /**
     * 手机号
     *
     * @var string
     * @optional
     */
    private $mobile = '';

    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestUserStockMapList
     */
    public function setUserId($userId = -1)
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getStockUserId()
    {
        return $this->stockUserId;
    }

    /**
     * @param string $stockUserId
     * @return RequestUserStockMapList
     */
    public function setStockUserId($stockUserId = '')
    {
        $this->stockUserId = $stockUserId;

        return $this;
    }
    /**
     * @return string
     */
    public function getCardId()
    {
        return $this->cardId;
    }

    /**
     * @param string $cardId
     * @return RequestUserStockMapList
     */
    public function setCardId($cardId = '')
    {
        $this->cardId = $cardId;

        return $this;
    }
    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return RequestUserStockMapList
     */
    public function setMobile($mobile = '')
    {
        $this->mobile = $mobile;

        return $this;
    }
    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestUserStockMapList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }

}