<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * medal基本信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class ProtoMedalInfo extends ProtoBufferBase
{
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
     * 显示起始时间
     *
     * @var int
     * @required
     */
    private $showStartTime;

    /**
     * 勋章有效期类型,1.没有结束时期，2.有明确的结束时间(endTime生效)
     *
     * @var int
     * @required
     */
    private $showTimeType;

    /**
     * 是否有奖励
     *
     * @var int
     * @required
     */
    private $hasAwards;

    /**
     * 多个规则1表示规则间and，2表示or
     *
     * @var int
     * @required
     */
    private $optional;

    /**
     * 勋章ID
     *
     * @var int
     * @optional
     */
    private $id = 0;

    /**
     * 有效状态
     *
     * @var int
     * @optional
     */
    private $isEffective = 1;

    /**
     * 奖品列表
     *
     * @var string
     * @optional
     */
    private $prizeList = '';

    /**
     * 奖品有效期（天）
     *
     * @var float
     * @optional
     */
    private $prizeTime = 0;

    /**
     * 可领取的数量
     *
     * @var int
     * @optional
     */
    private $prizeNum = 0;

    /**
     * 勋章剩余的数量
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
     * 勋章的数量
     *
     * @var int
     * @optional
     */
    private $totalNum = 0;

    /**
     * 显示结束时间
     *
     * @var int
     * @optional
     */
    private $showEndTime = 0;

    /**
     * 是否新手专属
     *
     * @var int
     * @optional
     */
    private $isForBeginner = 0;

    /**
     * 邀请人奖励
     *
     * @var string
     * @optional
     */
    private $inviterPrizeList = '';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
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
    public function getShowStartTime()
    {
        return $this->showStartTime;
    }

    /**
     * @param int $showStartTime
     * @return ProtoMedalInfo
     */
    public function setShowStartTime($showStartTime)
    {
        \Assert\Assertion::integer($showStartTime);

        $this->showStartTime = $showStartTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getShowTimeType()
    {
        return $this->showTimeType;
    }

    /**
     * @param int $showTimeType
     * @return ProtoMedalInfo
     */
    public function setShowTimeType($showTimeType)
    {
        \Assert\Assertion::integer($showTimeType);

        $this->showTimeType = $showTimeType;

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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
     */
    public function setOptional($optional)
    {
        \Assert\Assertion::integer($optional);

        $this->optional = $optional;

        return $this;
    }
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoMedalInfo
     */
    public function setId($id = 0)
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsEffective()
    {
        return $this->isEffective;
    }

    /**
     * @param int $isEffective
     * @return ProtoMedalInfo
     */
    public function setIsEffective($isEffective = 1)
    {
        $this->isEffective = $isEffective;

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
     * @return ProtoMedalInfo
     */
    public function setPrizeList($prizeList = '')
    {
        $this->prizeList = $prizeList;

        return $this;
    }
    /**
     * @return float
     */
    public function getPrizeTime()
    {
        return $this->prizeTime;
    }

    /**
     * @param float $prizeTime
     * @return ProtoMedalInfo
     */
    public function setPrizeTime($prizeTime = 0)
    {
        $this->prizeTime = $prizeTime;

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
     * @return ProtoMedalInfo
     */
    public function setPrizeNum($prizeNum = 0)
    {
        $this->prizeNum = $prizeNum;

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
     * @return ProtoMedalInfo
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
     * @return ProtoMedalInfo
     */
    public function setEndTime($endTime = 0)
    {
        $this->endTime = $endTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalNum()
    {
        return $this->totalNum;
    }

    /**
     * @param int $totalNum
     * @return ProtoMedalInfo
     */
    public function setTotalNum($totalNum = 0)
    {
        $this->totalNum = $totalNum;

        return $this;
    }
    /**
     * @return int
     */
    public function getShowEndTime()
    {
        return $this->showEndTime;
    }

    /**
     * @param int $showEndTime
     * @return ProtoMedalInfo
     */
    public function setShowEndTime($showEndTime = 0)
    {
        $this->showEndTime = $showEndTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsForBeginner()
    {
        return $this->isForBeginner;
    }

    /**
     * @param int $isForBeginner
     * @return ProtoMedalInfo
     */
    public function setIsForBeginner($isForBeginner = 0)
    {
        $this->isForBeginner = $isForBeginner;

        return $this;
    }
    /**
     * @return string
     */
    public function getInviterPrizeList()
    {
        return $this->inviterPrizeList;
    }

    /**
     * @param string $inviterPrizeList
     * @return ProtoMedalInfo
     */
    public function setInviterPrizeList($inviterPrizeList = '')
    {
        $this->inviterPrizeList = $inviterPrizeList;

        return $this;
    }

}