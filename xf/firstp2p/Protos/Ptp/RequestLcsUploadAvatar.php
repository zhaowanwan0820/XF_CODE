<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 理财师用户上传头像
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class RequestLcsUploadAvatar extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 头像二进制数据
     *
     * @var 
     * @required
     */
    private $img;

    /**
     * 头像名称
     *
     * @var string
     * @required
     */
    private $imgName;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestLcsUploadAvatar
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return 
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @param  $img
     * @return RequestLcsUploadAvatar
     */
    public function setImg($img)
    {
        $this->img = $img;

        return $this;
    }
    /**
     * @return string
     */
    public function getImgName()
    {
        return $this->imgName;
    }

    /**
     * @param string $imgName
     * @return RequestLcsUploadAvatar
     */
    public function setImgName($imgName)
    {
        \Assert\Assertion::string($imgName);

        $this->imgName = $imgName;

        return $this;
    }

}