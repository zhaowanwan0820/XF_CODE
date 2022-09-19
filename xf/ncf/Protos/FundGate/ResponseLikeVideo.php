<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 视频点赞人数
 *
 * 由代码生成器生成, 不可人为修改
 * @author LiBing <libing10@ucfgroup.com>
 */
class ResponseLikeVideo extends ResponseBase
{
    /**
     * 视频ID
     *
     * @var int
     * @required
     */
    private $videoId;

    /**
     * 点赞人数
     *
     * @var string
     * @required
     */
    private $likeAmount;

    /**
     * @return int
     */
    public function getVideoId()
    {
        return $this->videoId;
    }

    /**
     * @param int $videoId
     * @return ResponseLikeVideo
     */
    public function setVideoId($videoId)
    {
        \Assert\Assertion::integer($videoId);

        $this->videoId = $videoId;

        return $this;
    }
    /**
     * @return string
     */
    public function getLikeAmount()
    {
        return $this->likeAmount;
    }

    /**
     * @param string $likeAmount
     * @return ResponseLikeVideo
     */
    public function setLikeAmount($likeAmount)
    {
        \Assert\Assertion::string($likeAmount);

        $this->likeAmount = $likeAmount;

        return $this;
    }

}