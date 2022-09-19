<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 捐赠记录列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan@
 */
class RequestGongyiList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 用户ID
     *
     * @var int
     * @optional
     */
    private $userId = 0;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGongyiList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGongyiList
     */
    public function setUserId($userId = 0)
    {
        $this->userId = $userId;

        return $this;
    }

}