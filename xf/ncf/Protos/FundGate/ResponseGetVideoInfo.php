<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 获得对应视频信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author LiBing <libing10@ucfgroup.com>
 */
class ResponseGetVideoInfo extends ResponseBase
{
    /**
     * 视频要修改的信息
     *
     * @var array
     * @required
     */
    private $videoInfo;

    /**
     * @return array
     */
    public function getVideoInfo()
    {
        return $this->videoInfo;
    }

    /**
     * @param array $videoInfo
     * @return ResponseGetVideoInfo
     */
    public function setVideoInfo(array $videoInfo)
    {
        $this->videoInfo = $videoInfo;

        return $this;
    }

}