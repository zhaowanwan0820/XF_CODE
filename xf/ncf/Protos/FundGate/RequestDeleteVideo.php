<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 删除视频
 *
 * 由代码生成器生成, 不可人为修改
 * @author LiBing <libing10@ucfgroup.com>
 */
class RequestDeleteVideo extends AbstractRequestBase
{
    /**
     * 视频ID
     *
     * @var int
     * @required
     */
    private $videoId;

    /**
     * @return int
     */
    public function getVideoId()
    {
        return $this->videoId;
    }

    /**
     * @param int $videoId
     * @return RequestDeleteVideo
     */
    public function setVideoId($videoId)
    {
        \Assert\Assertion::integer($videoId);

        $this->videoId = $videoId;

        return $this;
    }

}