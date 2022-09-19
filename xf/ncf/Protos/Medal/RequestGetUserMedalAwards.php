<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户获取勋章下的奖品
 *
 * 由代码生成器生成, 不可人为修改
 * @author dengyi <dengyi@ucfgroup.com>
 */
class RequestGetUserMedalAwards extends AbstractRequestBase
{
    /**
     * 用户的ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 勋章的id
     *
     * @var int
     * @required
     */
    private $medalId;

    /**
     * 奖品的id
     *
     * @var array
     * @required
     */
    private $awards;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetUserMedalAwards
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getMedalId()
    {
        return $this->medalId;
    }

    /**
     * @param int $medalId
     * @return RequestGetUserMedalAwards
     */
    public function setMedalId($medalId)
    {
        \Assert\Assertion::integer($medalId);

        $this->medalId = $medalId;

        return $this;
    }
    /**
     * @return array
     */
    public function getAwards()
    {
        return $this->awards;
    }

    /**
     * @param array $awards
     * @return RequestGetUserMedalAwards
     */
    public function setAwards(array $awards)
    {
        $this->awards = $awards;

        return $this;
    }

}