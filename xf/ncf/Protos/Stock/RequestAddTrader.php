<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 添加券商
 *
 * 由代码生成器生成, 不可人为修改
 * @author libing
 */
class RequestAddTrader extends AbstractRequestBase
{
    /**
     * 券商名称
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 名称缩写
     *
     * @var string
     * @required
     */
    private $briefName;

    /**
     * 券商来源
     *
     * @var string
     * @required
     */
    private $source;

    /**
     * 图标url
     *
     * @var string
     * @required
     */
    private $icon;

    /**
     * 券商服务电话
     *
     * @var string
     * @required
     */
    private $servicePhone;

    /**
     * 券商开户文案
     *
     * @var string
     * @required
     */
    private $accountText;

    /**
     * 券商开户宣传点
     *
     * @var string
     * @required
     */
    private $accountFeature;

    /**
     * 券商开户url
     *
     * @var string
     * @required
     */
    private $accountUrl;

    /**
     * 交易url
     *
     * @var string
     * @required
     */
    private $tradeUrl;

    /**
     * 持仓url
     *
     * @var string
     * @required
     */
    private $positionUrl;

    /**
     * 开户列表显示优先级
     *
     * @var int
     * @required
     */
    private $accountLevel;

    /**
     * 交易列表显示优先级
     *
     * @var int
     * @required
     */
    private $tradeLevel;

    /**
     * 是否提供开户
     *
     * @var int
     * @required
     */
    private $isAccount;

    /**
     * 是否提供交易
     *
     * @var int
     * @required
     */
    private $isTrade;

    /**
     * 开户类型
     *
     * @var int
     * @required
     */
    private $accountType;

    /**
     * 推广文案
     *
     * @var string
     * @required
     */
    private $actText;

    /**
     * 推广url
     *
     * @var string
     * @required
     */
    private $actUrl;

    /**
     * 键值对
     *
     * @var string
     * @required
     */
    private $remark;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return RequestAddTrader
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

        return $this;
    }
    /**
     * @return string
     */
    public function getBriefName()
    {
        return $this->briefName;
    }

    /**
     * @param string $briefName
     * @return RequestAddTrader
     */
    public function setBriefName($briefName)
    {
        \Assert\Assertion::string($briefName);

        $this->briefName = $briefName;

        return $this;
    }
    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @return RequestAddTrader
     */
    public function setSource($source)
    {
        \Assert\Assertion::string($source);

        $this->source = $source;

        return $this;
    }
    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return RequestAddTrader
     */
    public function setIcon($icon)
    {
        \Assert\Assertion::string($icon);

        $this->icon = $icon;

        return $this;
    }
    /**
     * @return string
     */
    public function getServicePhone()
    {
        return $this->servicePhone;
    }

    /**
     * @param string $servicePhone
     * @return RequestAddTrader
     */
    public function setServicePhone($servicePhone)
    {
        \Assert\Assertion::string($servicePhone);

        $this->servicePhone = $servicePhone;

        return $this;
    }
    /**
     * @return string
     */
    public function getAccountText()
    {
        return $this->accountText;
    }

    /**
     * @param string $accountText
     * @return RequestAddTrader
     */
    public function setAccountText($accountText)
    {
        \Assert\Assertion::string($accountText);

        $this->accountText = $accountText;

        return $this;
    }
    /**
     * @return string
     */
    public function getAccountFeature()
    {
        return $this->accountFeature;
    }

    /**
     * @param string $accountFeature
     * @return RequestAddTrader
     */
    public function setAccountFeature($accountFeature)
    {
        \Assert\Assertion::string($accountFeature);

        $this->accountFeature = $accountFeature;

        return $this;
    }
    /**
     * @return string
     */
    public function getAccountUrl()
    {
        return $this->accountUrl;
    }

    /**
     * @param string $accountUrl
     * @return RequestAddTrader
     */
    public function setAccountUrl($accountUrl)
    {
        \Assert\Assertion::string($accountUrl);

        $this->accountUrl = $accountUrl;

        return $this;
    }
    /**
     * @return string
     */
    public function getTradeUrl()
    {
        return $this->tradeUrl;
    }

    /**
     * @param string $tradeUrl
     * @return RequestAddTrader
     */
    public function setTradeUrl($tradeUrl)
    {
        \Assert\Assertion::string($tradeUrl);

        $this->tradeUrl = $tradeUrl;

        return $this;
    }
    /**
     * @return string
     */
    public function getPositionUrl()
    {
        return $this->positionUrl;
    }

    /**
     * @param string $positionUrl
     * @return RequestAddTrader
     */
    public function setPositionUrl($positionUrl)
    {
        \Assert\Assertion::string($positionUrl);

        $this->positionUrl = $positionUrl;

        return $this;
    }
    /**
     * @return int
     */
    public function getAccountLevel()
    {
        return $this->accountLevel;
    }

    /**
     * @param int $accountLevel
     * @return RequestAddTrader
     */
    public function setAccountLevel($accountLevel)
    {
        \Assert\Assertion::integer($accountLevel);

        $this->accountLevel = $accountLevel;

        return $this;
    }
    /**
     * @return int
     */
    public function getTradeLevel()
    {
        return $this->tradeLevel;
    }

    /**
     * @param int $tradeLevel
     * @return RequestAddTrader
     */
    public function setTradeLevel($tradeLevel)
    {
        \Assert\Assertion::integer($tradeLevel);

        $this->tradeLevel = $tradeLevel;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsAccount()
    {
        return $this->isAccount;
    }

    /**
     * @param int $isAccount
     * @return RequestAddTrader
     */
    public function setIsAccount($isAccount)
    {
        \Assert\Assertion::integer($isAccount);

        $this->isAccount = $isAccount;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsTrade()
    {
        return $this->isTrade;
    }

    /**
     * @param int $isTrade
     * @return RequestAddTrader
     */
    public function setIsTrade($isTrade)
    {
        \Assert\Assertion::integer($isTrade);

        $this->isTrade = $isTrade;

        return $this;
    }
    /**
     * @return int
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * @param int $accountType
     * @return RequestAddTrader
     */
    public function setAccountType($accountType)
    {
        \Assert\Assertion::integer($accountType);

        $this->accountType = $accountType;

        return $this;
    }
    /**
     * @return string
     */
    public function getActText()
    {
        return $this->actText;
    }

    /**
     * @param string $actText
     * @return RequestAddTrader
     */
    public function setActText($actText)
    {
        \Assert\Assertion::string($actText);

        $this->actText = $actText;

        return $this;
    }
    /**
     * @return string
     */
    public function getActUrl()
    {
        return $this->actUrl;
    }

    /**
     * @param string $actUrl
     * @return RequestAddTrader
     */
    public function setActUrl($actUrl)
    {
        \Assert\Assertion::string($actUrl);

        $this->actUrl = $actUrl;

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
     * @return RequestAddTrader
     */
    public function setRemark($remark)
    {
        \Assert\Assertion::string($remark);

        $this->remark = $remark;

        return $this;
    }

}