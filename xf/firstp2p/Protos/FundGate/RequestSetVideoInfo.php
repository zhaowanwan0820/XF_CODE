<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 更新视频信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author LiBing <libing10@ucfgroup.com>
 */
class RequestSetVideoInfo extends AbstractRequestBase
{
    /**
     * 视频ID
     *
     * @var int
     * @required
     */
    private $videoId;

    /**
     * 视频名
     *
     * @var string
     * @required
     */
    private $videoName;

    /**
     * 视频链接
     *
     * @var string
     * @required
     */
    private $videoUrl;

    /**
     * 视频图片链接
     *
     * @var string
     * @required
     */
    private $videoImage;

    /**
     * 视频频道名
     *
     * @var string
     * @required
     */
    private $videoChannelName;

    /**
     * 视频发布时间
     *
     * @var string
     * @required
     */
    private $videoDatetime;

    /**
     * 作者ID
     *
     * @var int
     * @required
     */
    private $authorId;

    /**
     * @return int
     */
    public function getVideoId()
    {
        return $this->videoId;
    }

    /**
     * @param int $videoId
     * @return RequestSetVideoInfo
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
    public function getVideoName()
    {
        return $this->videoName;
    }

    /**
     * @param string $videoName
     * @return RequestSetVideoInfo
     */
    public function setVideoName($videoName)
    {
        \Assert\Assertion::string($videoName);

        $this->videoName = $videoName;

        return $this;
    }
    /**
     * @return string
     */
    public function getVideoUrl()
    {
        return $this->videoUrl;
    }

    /**
     * @param string $videoUrl
     * @return RequestSetVideoInfo
     */
    public function setVideoUrl($videoUrl)
    {
        \Assert\Assertion::string($videoUrl);

        $this->videoUrl = $videoUrl;

        return $this;
    }
    /**
     * @return string
     */
    public function getVideoImage()
    {
        return $this->videoImage;
    }

    /**
     * @param string $videoImage
     * @return RequestSetVideoInfo
     */
    public function setVideoImage($videoImage)
    {
        \Assert\Assertion::string($videoImage);

        $this->videoImage = $videoImage;

        return $this;
    }
    /**
     * @return string
     */
    public function getVideoChannelName()
    {
        return $this->videoChannelName;
    }

    /**
     * @param string $videoChannelName
     * @return RequestSetVideoInfo
     */
    public function setVideoChannelName($videoChannelName)
    {
        \Assert\Assertion::string($videoChannelName);

        $this->videoChannelName = $videoChannelName;

        return $this;
    }
    /**
     * @return string
     */
    public function getVideoDatetime()
    {
        return $this->videoDatetime;
    }

    /**
     * @param string $videoDatetime
     * @return RequestSetVideoInfo
     */
    public function setVideoDatetime($videoDatetime)
    {
        \Assert\Assertion::string($videoDatetime);

        $this->videoDatetime = $videoDatetime;

        return $this;
    }
    /**
     * @return int
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * @param int $authorId
     * @return RequestSetVideoInfo
     */
    public function setAuthorId($authorId)
    {
        \Assert\Assertion::integer($authorId);

        $this->authorId = $authorId;

        return $this;
    }

}