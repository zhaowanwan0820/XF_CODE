<?php
namespace NCFGroup\Protos\Open;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * open:更新开放平台的统计信息Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author YanJun
 */
class RequestUpdateStatsInfo extends ProtoBufferBase
{
    /**
     * 分站ID
     *
     * @var int
     * @required
     */
    private $siteId;

    /**
     * 注册用户数
     *
     * @var int
     * @optional
     */
    private $regUser = 0;

    /**
     * 投资用户数
     *
     * @var int
     * @optional
     */
    private $invesUser = 0;

    /**
     * 注册资金总额
     *
     * @var float
     * @optional
     */
    private $invesTotal = 0;

    /**
     * 更新统计信息时间
     *
     * @var int
     * @required
     */
    private $statsTime;

    /**
     * 当日注册当日身份验证人数
     *
     * @var int
     * @optional
     */
    private $idcardPassedUser = 0;

    /**
     * 当日注册当日绑定银行卡人数
     *
     * @var int
     * @optional
     */
    private $bankPassedUser = 0;

    /**
     * 当日注册当日投资人数
     *
     * @var int
     * @optional
     */
    private $regInvUser = 0;

    /**
     * 当日注册当日投资金额
     *
     * @var float
     * @optional
     */
    private $regInvMoney = 0;

    /**
     * 当日投资总金额年化
     *
     * @var float
     * @optional
     */
    private $invAnnualMoney = 0;

    /**
     * 当日新增投资用户
     *
     * @var int
     * @optional
     */
    private $invNewUser = 0;

    /**
     * 当日新增投资用户投资金额
     *
     * @var float
     * @optional
     */
    private $invNewUserMoney = 0;

    /**
     * web当日注册用户数
     *
     * @var int
     * @optional
     */
    private $webRegUser = 0;

    /**
     * web当日注册当日身份验证人数
     *
     * @var int
     * @optional
     */
    private $webIdcardPassedUser = 0;

    /**
     * web当日注册当日绑定银行卡人数
     *
     * @var int
     * @optional
     */
    private $webBankPassedUser = 0;

    /**
     * web当日注册当日投资人数
     *
     * @var int
     * @optional
     */
    private $webRegInvUser = 0;

    /**
     * web当日注册当日投资金额
     *
     * @var float
     * @optional
     */
    private $webRegInvMoney = 0;

    /**
     * web当日投资总人数
     *
     * @var int
     * @optional
     */
    private $webInvUser = 0;

    /**
     * web当日投资总金额
     *
     * @var float
     * @optional
     */
    private $webInvMoney = 0;

    /**
     * web当日投资总金额年化
     *
     * @var float
     * @optional
     */
    private $webInvAnnualMoney = 0;

    /**
     * app当日注册用户数
     *
     * @var int
     * @optional
     */
    private $appRegUser = 0;

    /**
     * app当日注册当日身份验证人数
     *
     * @var int
     * @optional
     */
    private $appIdcardPassedUser = 0;

    /**
     * app当日注册当日绑定银行卡人数
     *
     * @var int
     * @optional
     */
    private $appBankPassedUser = 0;

    /**
     * app当日注册当日投资人数
     *
     * @var int
     * @optional
     */
    private $appRegInvUser = 0;

    /**
     * app当日注册当日投资金额
     *
     * @var float
     * @optional
     */
    private $appRegInvMoney = 0;

    /**
     * app当日投资总人数
     *
     * @var int
     * @optional
     */
    private $appInvUser = 0;

    /**
     * app当日投资总金额
     *
     * @var float
     * @optional
     */
    private $appInvMoney = 0;

    /**
     * app当日投资总金额年化
     *
     * @var float
     * @optional
     */
    private $appInvAnnualMoney = 0;

    /**
     * wap当日注册用户数
     *
     * @var int
     * @optional
     */
    private $wapRegUser = 0;

    /**
     * wap当日注册当日身份验证人数
     *
     * @var int
     * @optional
     */
    private $wapIdcardPassedUser = 0;

    /**
     * wap当日注册当日绑定银行卡人数
     *
     * @var int
     * @optional
     */
    private $wapBankPassedUser = 0;

    /**
     * wap当日注册当日投资人数
     *
     * @var int
     * @optional
     */
    private $wapRegInvUser = 0;

    /**
     * wap当日注册当日投资金额
     *
     * @var float
     * @optional
     */
    private $wapRegInvMoney = 0;

    /**
     * wap当日投资总人数
     *
     * @var int
     * @optional
     */
    private $wapInvUser = 0;

    /**
     * wap当日投资总金额
     *
     * @var float
     * @optional
     */
    private $wapInvMoney = 0;

    /**
     * wap当日投资总金额年化
     *
     * @var float
     * @optional
     */
    private $wapInvAnnualMoney = 0;

    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestUpdateStatsInfo
     */
    public function setSiteId($siteId)
    {
        \Assert\Assertion::integer($siteId);

        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return int
     */
    public function getRegUser()
    {
        return $this->regUser;
    }

    /**
     * @param int $regUser
     * @return RequestUpdateStatsInfo
     */
    public function setRegUser($regUser = 0)
    {
        $this->regUser = $regUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getInvesUser()
    {
        return $this->invesUser;
    }

    /**
     * @param int $invesUser
     * @return RequestUpdateStatsInfo
     */
    public function setInvesUser($invesUser = 0)
    {
        $this->invesUser = $invesUser;

        return $this;
    }
    /**
     * @return float
     */
    public function getInvesTotal()
    {
        return $this->invesTotal;
    }

    /**
     * @param float $invesTotal
     * @return RequestUpdateStatsInfo
     */
    public function setInvesTotal($invesTotal = 0)
    {
        $this->invesTotal = $invesTotal;

        return $this;
    }
    /**
     * @return int
     */
    public function getStatsTime()
    {
        return $this->statsTime;
    }

    /**
     * @param int $statsTime
     * @return RequestUpdateStatsInfo
     */
    public function setStatsTime($statsTime)
    {
        \Assert\Assertion::integer($statsTime);

        $this->statsTime = $statsTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getIdcardPassedUser()
    {
        return $this->idcardPassedUser;
    }

    /**
     * @param int $idcardPassedUser
     * @return RequestUpdateStatsInfo
     */
    public function setIdcardPassedUser($idcardPassedUser = 0)
    {
        $this->idcardPassedUser = $idcardPassedUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getBankPassedUser()
    {
        return $this->bankPassedUser;
    }

    /**
     * @param int $bankPassedUser
     * @return RequestUpdateStatsInfo
     */
    public function setBankPassedUser($bankPassedUser = 0)
    {
        $this->bankPassedUser = $bankPassedUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getRegInvUser()
    {
        return $this->regInvUser;
    }

    /**
     * @param int $regInvUser
     * @return RequestUpdateStatsInfo
     */
    public function setRegInvUser($regInvUser = 0)
    {
        $this->regInvUser = $regInvUser;

        return $this;
    }
    /**
     * @return float
     */
    public function getRegInvMoney()
    {
        return $this->regInvMoney;
    }

    /**
     * @param float $regInvMoney
     * @return RequestUpdateStatsInfo
     */
    public function setRegInvMoney($regInvMoney = 0)
    {
        $this->regInvMoney = $regInvMoney;

        return $this;
    }
    /**
     * @return float
     */
    public function getInvAnnualMoney()
    {
        return $this->invAnnualMoney;
    }

    /**
     * @param float $invAnnualMoney
     * @return RequestUpdateStatsInfo
     */
    public function setInvAnnualMoney($invAnnualMoney = 0)
    {
        $this->invAnnualMoney = $invAnnualMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getInvNewUser()
    {
        return $this->invNewUser;
    }

    /**
     * @param int $invNewUser
     * @return RequestUpdateStatsInfo
     */
    public function setInvNewUser($invNewUser = 0)
    {
        $this->invNewUser = $invNewUser;

        return $this;
    }
    /**
     * @return float
     */
    public function getInvNewUserMoney()
    {
        return $this->invNewUserMoney;
    }

    /**
     * @param float $invNewUserMoney
     * @return RequestUpdateStatsInfo
     */
    public function setInvNewUserMoney($invNewUserMoney = 0)
    {
        $this->invNewUserMoney = $invNewUserMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getWebRegUser()
    {
        return $this->webRegUser;
    }

    /**
     * @param int $webRegUser
     * @return RequestUpdateStatsInfo
     */
    public function setWebRegUser($webRegUser = 0)
    {
        $this->webRegUser = $webRegUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getWebIdcardPassedUser()
    {
        return $this->webIdcardPassedUser;
    }

    /**
     * @param int $webIdcardPassedUser
     * @return RequestUpdateStatsInfo
     */
    public function setWebIdcardPassedUser($webIdcardPassedUser = 0)
    {
        $this->webIdcardPassedUser = $webIdcardPassedUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getWebBankPassedUser()
    {
        return $this->webBankPassedUser;
    }

    /**
     * @param int $webBankPassedUser
     * @return RequestUpdateStatsInfo
     */
    public function setWebBankPassedUser($webBankPassedUser = 0)
    {
        $this->webBankPassedUser = $webBankPassedUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getWebRegInvUser()
    {
        return $this->webRegInvUser;
    }

    /**
     * @param int $webRegInvUser
     * @return RequestUpdateStatsInfo
     */
    public function setWebRegInvUser($webRegInvUser = 0)
    {
        $this->webRegInvUser = $webRegInvUser;

        return $this;
    }
    /**
     * @return float
     */
    public function getWebRegInvMoney()
    {
        return $this->webRegInvMoney;
    }

    /**
     * @param float $webRegInvMoney
     * @return RequestUpdateStatsInfo
     */
    public function setWebRegInvMoney($webRegInvMoney = 0)
    {
        $this->webRegInvMoney = $webRegInvMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getWebInvUser()
    {
        return $this->webInvUser;
    }

    /**
     * @param int $webInvUser
     * @return RequestUpdateStatsInfo
     */
    public function setWebInvUser($webInvUser = 0)
    {
        $this->webInvUser = $webInvUser;

        return $this;
    }
    /**
     * @return float
     */
    public function getWebInvMoney()
    {
        return $this->webInvMoney;
    }

    /**
     * @param float $webInvMoney
     * @return RequestUpdateStatsInfo
     */
    public function setWebInvMoney($webInvMoney = 0)
    {
        $this->webInvMoney = $webInvMoney;

        return $this;
    }
    /**
     * @return float
     */
    public function getWebInvAnnualMoney()
    {
        return $this->webInvAnnualMoney;
    }

    /**
     * @param float $webInvAnnualMoney
     * @return RequestUpdateStatsInfo
     */
    public function setWebInvAnnualMoney($webInvAnnualMoney = 0)
    {
        $this->webInvAnnualMoney = $webInvAnnualMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getAppRegUser()
    {
        return $this->appRegUser;
    }

    /**
     * @param int $appRegUser
     * @return RequestUpdateStatsInfo
     */
    public function setAppRegUser($appRegUser = 0)
    {
        $this->appRegUser = $appRegUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getAppIdcardPassedUser()
    {
        return $this->appIdcardPassedUser;
    }

    /**
     * @param int $appIdcardPassedUser
     * @return RequestUpdateStatsInfo
     */
    public function setAppIdcardPassedUser($appIdcardPassedUser = 0)
    {
        $this->appIdcardPassedUser = $appIdcardPassedUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getAppBankPassedUser()
    {
        return $this->appBankPassedUser;
    }

    /**
     * @param int $appBankPassedUser
     * @return RequestUpdateStatsInfo
     */
    public function setAppBankPassedUser($appBankPassedUser = 0)
    {
        $this->appBankPassedUser = $appBankPassedUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getAppRegInvUser()
    {
        return $this->appRegInvUser;
    }

    /**
     * @param int $appRegInvUser
     * @return RequestUpdateStatsInfo
     */
    public function setAppRegInvUser($appRegInvUser = 0)
    {
        $this->appRegInvUser = $appRegInvUser;

        return $this;
    }
    /**
     * @return float
     */
    public function getAppRegInvMoney()
    {
        return $this->appRegInvMoney;
    }

    /**
     * @param float $appRegInvMoney
     * @return RequestUpdateStatsInfo
     */
    public function setAppRegInvMoney($appRegInvMoney = 0)
    {
        $this->appRegInvMoney = $appRegInvMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getAppInvUser()
    {
        return $this->appInvUser;
    }

    /**
     * @param int $appInvUser
     * @return RequestUpdateStatsInfo
     */
    public function setAppInvUser($appInvUser = 0)
    {
        $this->appInvUser = $appInvUser;

        return $this;
    }
    /**
     * @return float
     */
    public function getAppInvMoney()
    {
        return $this->appInvMoney;
    }

    /**
     * @param float $appInvMoney
     * @return RequestUpdateStatsInfo
     */
    public function setAppInvMoney($appInvMoney = 0)
    {
        $this->appInvMoney = $appInvMoney;

        return $this;
    }
    /**
     * @return float
     */
    public function getAppInvAnnualMoney()
    {
        return $this->appInvAnnualMoney;
    }

    /**
     * @param float $appInvAnnualMoney
     * @return RequestUpdateStatsInfo
     */
    public function setAppInvAnnualMoney($appInvAnnualMoney = 0)
    {
        $this->appInvAnnualMoney = $appInvAnnualMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getWapRegUser()
    {
        return $this->wapRegUser;
    }

    /**
     * @param int $wapRegUser
     * @return RequestUpdateStatsInfo
     */
    public function setWapRegUser($wapRegUser = 0)
    {
        $this->wapRegUser = $wapRegUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getWapIdcardPassedUser()
    {
        return $this->wapIdcardPassedUser;
    }

    /**
     * @param int $wapIdcardPassedUser
     * @return RequestUpdateStatsInfo
     */
    public function setWapIdcardPassedUser($wapIdcardPassedUser = 0)
    {
        $this->wapIdcardPassedUser = $wapIdcardPassedUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getWapBankPassedUser()
    {
        return $this->wapBankPassedUser;
    }

    /**
     * @param int $wapBankPassedUser
     * @return RequestUpdateStatsInfo
     */
    public function setWapBankPassedUser($wapBankPassedUser = 0)
    {
        $this->wapBankPassedUser = $wapBankPassedUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getWapRegInvUser()
    {
        return $this->wapRegInvUser;
    }

    /**
     * @param int $wapRegInvUser
     * @return RequestUpdateStatsInfo
     */
    public function setWapRegInvUser($wapRegInvUser = 0)
    {
        $this->wapRegInvUser = $wapRegInvUser;

        return $this;
    }
    /**
     * @return float
     */
    public function getWapRegInvMoney()
    {
        return $this->wapRegInvMoney;
    }

    /**
     * @param float $wapRegInvMoney
     * @return RequestUpdateStatsInfo
     */
    public function setWapRegInvMoney($wapRegInvMoney = 0)
    {
        $this->wapRegInvMoney = $wapRegInvMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getWapInvUser()
    {
        return $this->wapInvUser;
    }

    /**
     * @param int $wapInvUser
     * @return RequestUpdateStatsInfo
     */
    public function setWapInvUser($wapInvUser = 0)
    {
        $this->wapInvUser = $wapInvUser;

        return $this;
    }
    /**
     * @return float
     */
    public function getWapInvMoney()
    {
        return $this->wapInvMoney;
    }

    /**
     * @param float $wapInvMoney
     * @return RequestUpdateStatsInfo
     */
    public function setWapInvMoney($wapInvMoney = 0)
    {
        $this->wapInvMoney = $wapInvMoney;

        return $this;
    }
    /**
     * @return float
     */
    public function getWapInvAnnualMoney()
    {
        return $this->wapInvAnnualMoney;
    }

    /**
     * @param float $wapInvAnnualMoney
     * @return RequestUpdateStatsInfo
     */
    public function setWapInvAnnualMoney($wapInvAnnualMoney = 0)
    {
        $this->wapInvAnnualMoney = $wapInvAnnualMoney;

        return $this;
    }

}