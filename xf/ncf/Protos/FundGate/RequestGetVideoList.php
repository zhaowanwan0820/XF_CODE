<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 获取全部视频列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author LiBing <libing10@ucfgroup.com>
 */
class RequestGetVideoList extends AbstractRequestBase
{
    /**
     * 分类页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 频道名称
     *
     * @var string
     * @optional
     */
    private $channelName = '\'\'';

    /**
     * 视频Id
     *
     * @var int
     * @optional
     */
    private $videoId = -1;

    /**
     * 视频名
     *
     * @var string
     * @optional
     */
    private $videoName = '\'\'';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetVideoList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getChannelName()
    {
        return $this->channelName;
    }

    /**
     * @param string $channelName
     * @return RequestGetVideoList
     */
    public function setChannelName($channelName = '\'\'')
    {
        $this->channelName = $channelName;

        return $this;
    }
    /**
     * @return int
     */
    public function getVideoId()
    {
        return $this->videoId;
    }

    /**
     * @param int $videoId
     * @return RequestGetVideoList
     */
    public function setVideoId($videoId = -1)
    {
        $this->videoId = $videoId;

        return $this;
    }
    /**
     * @return string
     */
    public function getVideoName()
    {
        return $this->videoName;
    }

    /**
     * @param string $videoName
     * @return RequestGetVideoList
     */
    public function setVideoName($videoName = '\'\'')
    {
        $this->videoName = $videoName;

        return $this;
    }

}