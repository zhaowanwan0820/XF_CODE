<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 修改券商信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author libing
 */
class RequestUpdateTrader extends AbstractRequestBase
{
    /**
     * 券商id
     *
     * @var string
     * @required
     */
    private $traderId;

    /**
     * 开户文案
     *
     * @var string
     * @required
     */
    private $accountText;

    /**
     * 开户文案
     *
     * @var string
     * @required
     */
    private $accountFeature;

    /**
     * 开户url
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
     * 开户列表显示顺序
     *
     * @var int
     * @required
     */
    private $accountLevel;

    /**
     * 交易列表显示顺序
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
     * 开户类型
     *
     * @var int
     * @required
     */
    private $accountType;

    /**
     * 键值对
     *
     * @var string
     * @required
     */
    private $remark;

    /**
     * 图标
     *
     * @var string
     * @required
     */
    private $icon;

    /**
     * 服务电话
     *
     * @var string
     * @required
     */
    private $servicePhone;

    /**
     * @return string
     */
    public function getTraderId()
    {
        return $this->traderId;
    }

    /**
     * @param string $traderId
     * @return RequestUpdateTrader
     */
    public function setTraderId($traderId)
    {
        \Assert\Assertion::string($traderId);

        $this->traderId = $traderId;

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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
     */
    public function setIsTrade($isTrade)
    {
        \Assert\Assertion::integer($isTrade);

        $this->isTrade = $isTrade;

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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
     */
    public function setActUrl($actUrl)
    {
        \Assert\Assertion::string($actUrl);

        $this->actUrl = $actUrl;

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
     * @return RequestUpdateTrader
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
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return RequestUpdateTrader
     */
    public function setRemark($remark)
    {
        \Assert\Assertion::string($remark);

        $this->remark = $remark;

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
     * @return RequestUpdateTrader
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
     * @return RequestUpdateTrader
     */
    public function setServicePhone($servicePhone)
    {
        \Assert\Assertion::string($servicePhone);

        $this->servicePhone = $servicePhone;

        return $this;
    }

}