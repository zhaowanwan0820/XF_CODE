<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * medal基本信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class ResponseMedalInfo extends AbstractRequestBase
{
    /**
     * 勋章ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 勋章名
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 是否是限量勋章
     *
     * @var int
     * @required
     */
    private $isLimited;

    /**
     * 大的点亮图片的URL
     *
     * @var string
     * @required
     */
    private $bigLightenedImg;

    /**
     * 中等的点亮图片的URL
     *
     * @var string
     * @required
     */
    private $mediumLightenedImg;

    /**
     * 小的点亮图片的URL
     *
     * @var string
     * @required
     */
    private $smallLightenedImg;

    /**
     * 大的未点亮图片的URL
     *
     * @var string
     * @required
     */
    private $bigUnlightenedImg;

    /**
     * 中等的未点亮图片的URL
     *
     * @var string
     * @required
     */
    private $mediumUnlightenedImg;

    /**
     * 小的未点亮图片的URL
     *
     * @var string
     * @required
     */
    private $smallUnlightenedImg;

    /**
     * 勋章的描述
     *
     * @var string
     * @required
     */
    private $description;

    /**
     * 勋章的详情
     *
     * @var string
     * @required
     */
    private $details;

    /**
     * 起始时间
     *
     * @var int
     * @required
     */
    private $startTime;

    /**
     * 勋章有效期类型,1.没有结束时期，2.有明确的结束时间(endTime生效)
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 是否有奖励,1表示没有奖励，2表示有奖励
     *
     * @var int
     * @required
     */
    private $hasAwards;

    /**
     * 1表示所有条件必须完成，2表示只要完成条件之一
     *
     * @var int
     * @required
     */
    private $optional;

    /**
     * 勋章的进度
     *
     * @var array
     * @optional
     */
    private $progresses = NULL;

    /**
     * 勋章数量
     *
     * @var int
     * @optional
     */
    private $num = 0;

    /**
     * 结束时间
     *
     * @var int
     * @optional
     */
    private $endTime = 0;

    /**
     * 用户是否拥有该勋章
     *
     * @var bool
     * @optional
     */
    private $isOwned = false;

    /**
     * 是不是过往的勋章
     *
     * @var bool
     * @optional
     */
    private $isHistory = false;

    /**
     * 是否已经领奖
     *
     * @var bool
     * @optional
     */
    private $isAwarded = false;

    /**
     * 可领取奖品数量
     *
     * @var int
     * @optional
     */
    private $prizeNum = 0;

    /**
     * 奖品列表，一段json，奖品的id
     *
     * @var string
     * @optional
     */
    private $prizeList = '';

    /**
     * 奖品领取的结束时间戳
     *
     * @var int
     * @optional
     */
    private $prizeDeadline = 0;

    /**
     * 是否新手专属
     *
     * @var bool
     * @optional
     */
    private $isForBeginner = false;

    /**
     * 勋章消息的显示信息
     *
     * @var string
     * @optional
     */
    private $remark = '';

    /**
     * 勋章获取时间
     *
     * @var int
     * @optional
     */
    private $grantTime = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ResponseMedalInfo
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ResponseMedalInfo
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsLimited()
    {
        return $this->isLimited;
    }

    /**
     * @param int $isLimited
     * @return ResponseMedalInfo
     */
    public function setIsLimited($isLimited)
    {
        \Assert\Assertion::integer($isLimited);

        $this->isLimited = $isLimited;

        return $this;
    }
    /**
     * @return string
     */
    public function getBigLightenedImg()
    {
        return $this->bigLightenedImg;
    }

    /**
     * @param string $bigLightenedImg
     * @return ResponseMedalInfo
     */
    public function setBigLightenedImg($bigLightenedImg)
    {
        \Assert\Assertion::string($bigLightenedImg);

        $this->bigLightenedImg = $bigLightenedImg;

        return $this;
    }
    /**
     * @return string
     */
    public function getMediumLightenedImg()
    {
        return $this->mediumLightenedImg;
    }

    /**
     * @param string $mediumLightenedImg
     * @return ResponseMedalInfo
     */
    public function setMediumLightenedImg($mediumLightenedImg)
    {
        \Assert\Assertion::string($mediumLightenedImg);

        $this->mediumLightenedImg = $mediumLightenedImg;

        return $this;
    }
    /**
     * @return string
     */
    public function getSmallLightenedImg()
    {
        return $this->smallLightenedImg;
    }

    /**
     * @param string $smallLightenedImg
     * @return ResponseMedalInfo
     */
    public function setSmallLightenedImg($smallLightenedImg)
    {
        \Assert\Assertion::string($smallLightenedImg);

        $this->smallLightenedImg = $smallLightenedImg;

        return $this;
    }
    /**
     * @return string
     */
    public function getBigUnlightenedImg()
    {
        return $this->bigUnlightenedImg;
    }

    /**
     * @param string $bigUnlightenedImg
     * @return ResponseMedalInfo
     */
    public function setBigUnlightenedImg($bigUnlightenedImg)
    {
        \Assert\Assertion::string($bigUnlightenedImg);

        $this->bigUnlightenedImg = $bigUnlightenedImg;

        return $this;
    }
    /**
     * @return string
     */
    public function getMediumUnlightenedImg()
    {
        return $this->mediumUnlightenedImg;
    }

    /**
     * @param string $mediumUnlightenedImg
     * @return ResponseMedalInfo
     */
    public function setMediumUnlightenedImg($mediumUnlightenedImg)
    {
        \Assert\Assertion::string($mediumUnlightenedImg);

        $this->mediumUnlightenedImg = $mediumUnlightenedImg;

        return $this;
    }
    /**
     * @return string
     */
    public function getSmallUnlightenedImg()
    {
        return $this->smallUnlightenedImg;
    }

    /**
     * @param string $smallUnlightenedImg
     * @return ResponseMedalInfo
     */
    public function setSmallUnlightenedImg($smallUnlightenedImg)
    {
        \Assert\Assertion::string($smallUnlightenedImg);

        $this->smallUnlightenedImg = $smallUnlightenedImg;

        return $this;
    }
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return ResponseMedalInfo
     */
    public function setDescription($description)
    {
        \Assert\Assertion::string($description);

        $this->description = $description;

        return $this;
    }
    /**
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param string $details
     * @return ResponseMedalInfo
     */
    public function setDetails($details)
    {
        \Assert\Assertion::string($details);

        $this->details = $details;

        return $this;
    }
    /**
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param int $startTime
     * @return ResponseMedalInfo
     */
    public function setStartTime($startTime)
    {
        \Assert\Assertion::integer($startTime);

        $this->startTime = $startTime;

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
     * @return ResponseMedalInfo
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getHasAwards()
    {
        return $this->hasAwards;
    }

    /**
     * @param int $hasAwards
     * @return ResponseMedalInfo
     */
    public function setHasAwards($hasAwards)
    {
        \Assert\Assertion::integer($hasAwards);

        $this->hasAwards = $hasAwards;

        return $this;
    }
    /**
     * @return int
     */
    public function getOptional()
    {
        return $this->optional;
    }

    /**
     * @param int $optional
     * @return ResponseMedalInfo
     */
    public function setOptional($optional)
    {
        \Assert\Assertion::integer($optional);

        $this->optional = $optional;

        return $this;
    }
    /**
     * @return array
     */
    public function getProgresses()
    {
        return $this->progresses;
    }

    /**
     * @param array $progresses
     * @return ResponseMedalInfo
     */
    public function setProgresses(array $progresses = NULL)
    {
        $this->progresses = $progresses;

        return $this;
    }
    /**
     * @return int
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * @param int $num
     * @return ResponseMedalInfo
     */
    public function setNum($num = 0)
    {
        $this->num = $num;

        return $this;
    }
    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     * @return ResponseMedalInfo
     */
    public function setEndTime($endTime = 0)
    {
        $this->endTime = $endTime;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsOwned()
    {
        return $this->isOwned;
    }

    /**
     * @param bool $isOwned
     * @return ResponseMedalInfo
     */
    public function setIsOwned($isOwned = false)
    {
        $this->isOwned = $isOwned;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsHistory()
    {
        return $this->isHistory;
    }

    /**
     * @param bool $isHistory
     * @return ResponseMedalInfo
     */
    public function setIsHistory($isHistory = false)
    {
        $this->isHistory = $isHistory;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsAwarded()
    {
        return $this->isAwarded;
    }

    /**
     * @param bool $isAwarded
     * @return ResponseMedalInfo
     */
    public function setIsAwarded($isAwarded = false)
    {
        $this->isAwarded = $isAwarded;

        return $this;
    }
    /**
     * @return int
     */
    public function getPrizeNum()
    {
        return $this->prizeNum;
    }

    /**
     * @param int $prizeNum
     * @return ResponseMedalInfo
     */
    public function setPrizeNum($prizeNum = 0)
    {
        $this->prizeNum = $prizeNum;

        return $this;
    }
    /**
     * @return string
     */
    public function getPrizeList()
    {
        return $this->prizeList;
    }

    /**
     * @param string $prizeList
     * @return ResponseMedalInfo
     */
    public function setPrizeList($prizeList = '')
    {
        $this->prizeList = $prizeList;

        return $this;
    }
    /**
     * @return int
     */
    public function getPrizeDeadline()
    {
        return $this->prizeDeadline;
    }

    /**
     * @param int $prizeDeadline
     * @return ResponseMedalInfo
     */
    public function setPrizeDeadline($prizeDeadline = 0)
    {
        $this->prizeDeadline = $prizeDeadline;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsForBeginner()
    {
        return $this->isForBeginner;
    }

    /**
     * @param bool $isForBeginner
     * @return ResponseMedalInfo
     */
    public function setIsForBeginner($isForBeginner = false)
    {
        $this->isForBeginner = $isForBeginner;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return ResponseMedalInfo
     */
    public function setRemark($remark = '')
    {
        $this->remark = $remark;

        return $this;
    }
    /**
     * @return int
     */
    public function getGrantTime()
    {
        return $this->grantTime;
    }

    /**
     * @param int $grantTime
     * @return ResponseMedalInfo
     */
    public function setGrantTime($grantTime = 0)
    {
        $this->grantTime = $grantTime;

        return $this;
    }

}