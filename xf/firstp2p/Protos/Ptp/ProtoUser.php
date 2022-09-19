<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 用户Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ProtoUser extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 用户名
     *
     * @var string
     * @optional
     */
    private $userName = '';

    /**
     * 用户名
     *
     * @var string
     * @optional
     */
    private $realName = '';

    /**
     * 性别
     *
     * @var int
     * @optional
     */
    private $sex = -1;

    /**
     * money
     *
     * @var string
     * @optional
     */
    private $money = '';

    /**
     * lock_money
     *
     * @var string
     * @optional
     */
    private $lock_money = '';

    /**
     * 身份证号
     *
     * @var string
     * @optional
     */
    private $idno = '';

    /**
     * 证件类型
     *
     * @var int
     * @optional
     */
    private $idtype = 1;

    /**
     * 身份验证
     *
     * @var int
     * @optional
     */
    private $idcardPassed = '';

    /**
     * photo验证
     *
     * @var string
     * @optional
     */
    private $photoPassed = '';

    /**
     * 手机号码
     *
     * @var string
     * @optional
     */
    private $mobile = '';

    /**
     * 用户邮箱
     *
     * @var string
     * @optional
     */
    private $email = '';

    /**
     * 银行卡号
     *
     * @var string
     * @optional
     */
    private $bankNo = '';

    /**
     * 银行名称
     *
     * @var string
     * @optional
     */
    private $bank = '';

    /**
     * bank_icon
     *
     * @var string
     * @optional
     */
    private $bankIcon = '';

    /**
     * bank_zone
     *
     * @var string
     * @optional
     */
    private $bankZone = '';

    /**
     * 银行ID
     *
     * @var int
     * @optional
     */
    private $bankId = 0;

    /**
     * 开户用户名
     *
     * @var string
     * @optional
     */
    private $bankUserName = '';

    /**
     * remain
     *
     * @var string
     * @optional
     */
    private $remain = '';

    /**
     * frozen
     *
     * @var string
     * @optional
     */
    private $frozen = '';

    /**
     * earningAll
     *
     * @var string
     * @optional
     */
    private $earningAll = '';

    /**
     * income
     *
     * @var string
     * @optional
     */
    private $income = '';

    /**
     * corpus
     *
     * @var string
     * @optional
     */
    private $corpus = '';

    /**
     * total
     *
     * @var string
     * @optional
     */
    private $total = '';

    /**
     * totalExt
     *
     * @var string
     * @optional
     */
    private $totalExt = '';

    /**
     * bonus
     *
     * @var string
     * @optional
     */
    private $bonus = '';

    /**
     * 分组ID
     *
     * @var int
     * @optional
     */
    private $groupId = 0;

    /**
     * 注册时间
     *
     * @var int
     * @optional
     */
    private $registerTime = 0;

    /**
     * 邀请人ID
     *
     * @var int
     * @optional
     */
    private $referUserId = 0;

    /**
     * 注册邀请码
     *
     * @var string
     * @optional
     */
    private $inviteCode = '';

    /**
     * 用户所属网站
     *
     * @var string
     * @optional
     */
    private $siteName = '';

    /**
     * 邀请人所属网站
     *
     * @var string
     * @optional
     */
    private $inviteSiteName = '';

    /**
     * 是否为o2o用户
     *
     * @var int
     * @optional
     */
    private $isO2oUser = 0;

    /**
     * 页码
     *
     * @var int
     * @optional
     */
    private $pageNum = 1;

    /**
     * 每页数量
     *
     * @var int
     * @optional
     */
    private $pageSize = 30;

    /**
     * 返利类型
     *
     * @var string
     * @optional
     */
    private $type = '';

    /**
     * 更新时间
     *
     * @var int
     * @optional
     */
    private $updateTime = 0;

    /**
     * 是否有效
     *
     * @var int
     * @optional
     */
    private $isEffect = 1;

    /**
     * 身份类型列表
     *
     * @var array
     * @optional
     */
    private $idTypeList = NULL;

    /**
     * 用户密码
     *
     * @var string
     * @optional
     */
    private $userPwd = '';

    /**
     * 用户类型
     *
     * @var int
     * @optional
     */
    private $userTypes = 2;

    /**
     * 大金所待收本金
     *
     * @var string
     * @optional
     */
    private $djsNorepayPrincipal = '';

    /**
     * 大金所待收收益
     *
     * @var string
     * @optional
     */
    private $djsNorepayEarnings = '';

    /**
     * 大金所累计收益
     *
     * @var string
     * @optional
     */
    private $djsTotalEarnings = '';

    /**
     * 被邀请人ID
     *
     * @var string
     * @optional
     */
    private $inviteeId = 0;

    /**
     * 是否是东方联合用户
     *
     * @var int
     * @optional
     */
    private $isDFLH = 0;

    /**
     * 包含存管的在投本金
     *
     * @var string
     * @optional
     */
    private $corpusTotal = 0;

    /**
     * 包含存管的待收收益
     *
     * @var string
     * @optional
     */
    private $incomeTotal = 0;

    /**
     * 包含存管的累计收益
     *
     * @var string
     * @optional
     */
    private $earningAllTotal = 0;

    /**
     * 存管在投本金
     *
     * @var string
     * @optional
     */
    private $cgNorepayPrincipal = 0;

    /**
     * 网信账户免密授权状态
     *
     * @var int
     * @optional
     */
    private $isWxFreePayment = 0;

    /**
     * 验卡状态
     *
     * @var int
     * @optional
     */
    private $cardVerify = 0;

    /**
     * 未货币格式化的user表money字段
     *
     * @var float
     * @optional
     */
    private $unFormatted = 0;

    /**
     * 银行简码
     *
     * @var string
     * @optional
     */
    private $bankCode = '';

    /**
     * 用户年龄
     *
     * @var int
     * @optional
     */
    private $userAge = '';

    /**
     * 数据是否脱敏
     *
     * @var int
     * @optional
     */
    private $isTm = 1;

    /**
     * 所有用户id
     *
     * @var array
     * @optional
     */
    private $allUserId = NULL;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return ProtoUser
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return ProtoUser
     */
    public function setUserName($userName = '')
    {
        $this->userName = $userName;

        return $this;
    }
    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->realName;
    }

    /**
     * @param string $realName
     * @return ProtoUser
     */
    public function setRealName($realName = '')
    {
        $this->realName = $realName;

        return $this;
    }
    /**
     * @return int
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @param int $sex
     * @return ProtoUser
     */
    public function setSex($sex = -1)
    {
        $this->sex = $sex;

        return $this;
    }
    /**
     * @return string
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param string $money
     * @return ProtoUser
     */
    public function setMoney($money = '')
    {
        $this->money = $money;

        return $this;
    }
    /**
     * @return string
     */
    public function getLock_money()
    {
        return $this->lock_money;
    }

    /**
     * @param string $lock_money
     * @return ProtoUser
     */
    public function setLock_money($lock_money = '')
    {
        $this->lock_money = $lock_money;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdno()
    {
        return $this->idno;
    }

    /**
     * @param string $idno
     * @return ProtoUser
     */
    public function setIdno($idno = '')
    {
        $this->idno = $idno;

        return $this;
    }
    /**
     * @return int
     */
    public function getIdtype()
    {
        return $this->idtype;
    }

    /**
     * @param int $idtype
     * @return ProtoUser
     */
    public function setIdtype($idtype = 1)
    {
        $this->idtype = $idtype;

        return $this;
    }
    /**
     * @return int
     */
    public function getIdcardPassed()
    {
        return $this->idcardPassed;
    }

    /**
     * @param int $idcardPassed
     * @return ProtoUser
     */
    public function setIdcardPassed($idcardPassed = '')
    {
        $this->idcardPassed = $idcardPassed;

        return $this;
    }
    /**
     * @return string
     */
    public function getPhotoPassed()
    {
        return $this->photoPassed;
    }

    /**
     * @param string $photoPassed
     * @return ProtoUser
     */
    public function setPhotoPassed($photoPassed = '')
    {
        $this->photoPassed = $photoPassed;

        return $this;
    }
    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return ProtoUser
     */
    public function setMobile($mobile = '')
    {
        $this->mobile = $mobile;

        return $this;
    }
    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return ProtoUser
     */
    public function setEmail($email = '')
    {
        $this->email = $email;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankNo()
    {
        return $this->bankNo;
    }

    /**
     * @param string $bankNo
     * @return ProtoUser
     */
    public function setBankNo($bankNo = '')
    {
        $this->bankNo = $bankNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * @param string $bank
     * @return ProtoUser
     */
    public function setBank($bank = '')
    {
        $this->bank = $bank;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankIcon()
    {
        return $this->bankIcon;
    }

    /**
     * @param string $bankIcon
     * @return ProtoUser
     */
    public function setBankIcon($bankIcon = '')
    {
        $this->bankIcon = $bankIcon;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankZone()
    {
        return $this->bankZone;
    }

    /**
     * @param string $bankZone
     * @return ProtoUser
     */
    public function setBankZone($bankZone = '')
    {
        $this->bankZone = $bankZone;

        return $this;
    }
    /**
     * @return int
     */
    public function getBankId()
    {
        return $this->bankId;
    }

    /**
     * @param int $bankId
     * @return ProtoUser
     */
    public function setBankId($bankId = 0)
    {
        $this->bankId = $bankId;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankUserName()
    {
        return $this->bankUserName;
    }

    /**
     * @param string $bankUserName
     * @return ProtoUser
     */
    public function setBankUserName($bankUserName = '')
    {
        $this->bankUserName = $bankUserName;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemain()
    {
        return $this->remain;
    }

    /**
     * @param string $remain
     * @return ProtoUser
     */
    public function setRemain($remain = '')
    {
        $this->remain = $remain;

        return $this;
    }
    /**
     * @return string
     */
    public function getFrozen()
    {
        return $this->frozen;
    }

    /**
     * @param string $frozen
     * @return ProtoUser
     */
    public function setFrozen($frozen = '')
    {
        $this->frozen = $frozen;

        return $this;
    }
    /**
     * @return string
     */
    public function getEarningAll()
    {
        return $this->earningAll;
    }

    /**
     * @param string $earningAll
     * @return ProtoUser
     */
    public function setEarningAll($earningAll = '')
    {
        $this->earningAll = $earningAll;

        return $this;
    }
    /**
     * @return string
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @param string $income
     * @return ProtoUser
     */
    public function setIncome($income = '')
    {
        $this->income = $income;

        return $this;
    }
    /**
     * @return string
     */
    public function getCorpus()
    {
        return $this->corpus;
    }

    /**
     * @param string $corpus
     * @return ProtoUser
     */
    public function setCorpus($corpus = '')
    {
        $this->corpus = $corpus;

        return $this;
    }
    /**
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param string $total
     * @return ProtoUser
     */
    public function setTotal($total = '')
    {
        $this->total = $total;

        return $this;
    }
    /**
     * @return string
     */
    public function getTotalExt()
    {
        return $this->totalExt;
    }

    /**
     * @param string $totalExt
     * @return ProtoUser
     */
    public function setTotalExt($totalExt = '')
    {
        $this->totalExt = $totalExt;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * @param string $bonus
     * @return ProtoUser
     */
    public function setBonus($bonus = '')
    {
        $this->bonus = $bonus;

        return $this;
    }
    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param int $groupId
     * @return ProtoUser
     */
    public function setGroupId($groupId = 0)
    {
        $this->groupId = $groupId;

        return $this;
    }
    /**
     * @return int
     */
    public function getRegisterTime()
    {
        return $this->registerTime;
    }

    /**
     * @param int $registerTime
     * @return ProtoUser
     */
    public function setRegisterTime($registerTime = 0)
    {
        $this->registerTime = $registerTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getReferUserId()
    {
        return $this->referUserId;
    }

    /**
     * @param int $referUserId
     * @return ProtoUser
     */
    public function setReferUserId($referUserId = 0)
    {
        $this->referUserId = $referUserId;

        return $this;
    }
    /**
     * @return string
     */
    public function getInviteCode()
    {
        return $this->inviteCode;
    }

    /**
     * @param string $inviteCode
     * @return ProtoUser
     */
    public function setInviteCode($inviteCode = '')
    {
        $this->inviteCode = $inviteCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getSiteName()
    {
        return $this->siteName;
    }

    /**
     * @param string $siteName
     * @return ProtoUser
     */
    public function setSiteName($siteName = '')
    {
        $this->siteName = $siteName;

        return $this;
    }
    /**
     * @return string
     */
    public function getInviteSiteName()
    {
        return $this->inviteSiteName;
    }

    /**
     * @param string $inviteSiteName
     * @return ProtoUser
     */
    public function setInviteSiteName($inviteSiteName = '')
    {
        $this->inviteSiteName = $inviteSiteName;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsO2oUser()
    {
        return $this->isO2oUser;
    }

    /**
     * @param int $isO2oUser
     * @return ProtoUser
     */
    public function setIsO2oUser($isO2oUser = 0)
    {
        $this->isO2oUser = $isO2oUser;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageNum()
    {
        return $this->pageNum;
    }

    /**
     * @param int $pageNum
     * @return ProtoUser
     */
    public function setPageNum($pageNum = 1)
    {
        $this->pageNum = $pageNum;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return ProtoUser
     */
    public function setPageSize($pageSize = 30)
    {
        $this->pageSize = $pageSize;

        return $this;
    }
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ProtoUser
     */
    public function setType($type = '')
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * @param int $updateTime
     * @return ProtoUser
     */
    public function setUpdateTime($updateTime = 0)
    {
        $this->updateTime = $updateTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsEffect()
    {
        return $this->isEffect;
    }

    /**
     * @param int $isEffect
     * @return ProtoUser
     */
    public function setIsEffect($isEffect = 1)
    {
        $this->isEffect = $isEffect;

        return $this;
    }
    /**
     * @return array
     */
    public function getIdTypeList()
    {
        return $this->idTypeList;
    }

    /**
     * @param array $idTypeList
     * @return ProtoUser
     */
    public function setIdTypeList(array $idTypeList = NULL)
    {
        $this->idTypeList = $idTypeList;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserPwd()
    {
        return $this->userPwd;
    }

    /**
     * @param string $userPwd
     * @return ProtoUser
     */
    public function setUserPwd($userPwd = '')
    {
        $this->userPwd = $userPwd;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserTypes()
    {
        return $this->userTypes;
    }

    /**
     * @param int $userTypes
     * @return ProtoUser
     */
    public function setUserTypes($userTypes = 2)
    {
        $this->userTypes = $userTypes;

        return $this;
    }
    /**
     * @return string
     */
    public function getDjsNorepayPrincipal()
    {
        return $this->djsNorepayPrincipal;
    }

    /**
     * @param string $djsNorepayPrincipal
     * @return ProtoUser
     */
    public function setDjsNorepayPrincipal($djsNorepayPrincipal = '')
    {
        $this->djsNorepayPrincipal = $djsNorepayPrincipal;

        return $this;
    }
    /**
     * @return string
     */
    public function getDjsNorepayEarnings()
    {
        return $this->djsNorepayEarnings;
    }

    /**
     * @param string $djsNorepayEarnings
     * @return ProtoUser
     */
    public function setDjsNorepayEarnings($djsNorepayEarnings = '')
    {
        $this->djsNorepayEarnings = $djsNorepayEarnings;

        return $this;
    }
    /**
     * @return string
     */
    public function getDjsTotalEarnings()
    {
        return $this->djsTotalEarnings;
    }

    /**
     * @param string $djsTotalEarnings
     * @return ProtoUser
     */
    public function setDjsTotalEarnings($djsTotalEarnings = '')
    {
        $this->djsTotalEarnings = $djsTotalEarnings;

        return $this;
    }
    /**
     * @return string
     */
    public function getInviteeId()
    {
        return $this->inviteeId;
    }

    /**
     * @param string $inviteeId
     * @return ProtoUser
     */
    public function setInviteeId($inviteeId = 0)
    {
        $this->inviteeId = $inviteeId;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsDFLH()
    {
        return $this->isDFLH;
    }

    /**
     * @param int $isDFLH
     * @return ProtoUser
     */
    public function setIsDFLH($isDFLH = 0)
    {
        $this->isDFLH = $isDFLH;

        return $this;
    }
    /**
     * @return string
     */
    public function getCorpusTotal()
    {
        return $this->corpusTotal;
    }

    /**
     * @param string $corpusTotal
     * @return ProtoUser
     */
    public function setCorpusTotal($corpusTotal = 0)
    {
        $this->corpusTotal = $corpusTotal;

        return $this;
    }
    /**
     * @return string
     */
    public function getIncomeTotal()
    {
        return $this->incomeTotal;
    }

    /**
     * @param string $incomeTotal
     * @return ProtoUser
     */
    public function setIncomeTotal($incomeTotal = 0)
    {
        $this->incomeTotal = $incomeTotal;

        return $this;
    }
    /**
     * @return string
     */
    public function getEarningAllTotal()
    {
        return $this->earningAllTotal;
    }

    /**
     * @param string $earningAllTotal
     * @return ProtoUser
     */
    public function setEarningAllTotal($earningAllTotal = 0)
    {
        $this->earningAllTotal = $earningAllTotal;

        return $this;
    }
    /**
     * @return string
     */
    public function getCgNorepayPrincipal()
    {
        return $this->cgNorepayPrincipal;
    }

    /**
     * @param string $cgNorepayPrincipal
     * @return ProtoUser
     */
    public function setCgNorepayPrincipal($cgNorepayPrincipal = 0)
    {
        $this->cgNorepayPrincipal = $cgNorepayPrincipal;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsWxFreePayment()
    {
        return $this->isWxFreePayment;
    }

    /**
     * @param int $isWxFreePayment
     * @return ProtoUser
     */
    public function setIsWxFreePayment($isWxFreePayment = 0)
    {
        $this->isWxFreePayment = $isWxFreePayment;

        return $this;
    }
    /**
     * @return int
     */
    public function getCardVerify()
    {
        return $this->cardVerify;
    }

    /**
     * @param int $cardVerify
     * @return ProtoUser
     */
    public function setCardVerify($cardVerify = 0)
    {
        $this->cardVerify = $cardVerify;

        return $this;
    }
    /**
     * @return float
     */
    public function getUnFormatted()
    {
        return $this->unFormatted;
    }

    /**
     * @param float $unFormatted
     * @return ProtoUser
     */
    public function setUnFormatted($unFormatted = 0)
    {
        $this->unFormatted = $unFormatted;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankCode()
    {
        return $this->bankCode;
    }

    /**
     * @param string $bankCode
     * @return ProtoUser
     */
    public function setBankCode($bankCode = '')
    {
        $this->bankCode = $bankCode;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserAge()
    {
        return $this->userAge;
    }

    /**
     * @param int $userAge
     * @return ProtoUser
     */
    public function setUserAge($userAge = '')
    {
        $this->userAge = $userAge;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsTm()
    {
        return $this->isTm;
    }

    /**
     * @param int $isTm
     * @return ProtoUser
     */
    public function setIsTm($isTm = 1)
    {
        $this->isTm = $isTm;

        return $this;
    }
    /**
     * @return array
     */
    public function getAllUserId()
    {
        return $this->allUserId;
    }

    /**
     * @param array $allUserId
     * @return ProtoUser
     */
    public function setAllUserId(array $allUserId = NULL)
    {
        $this->allUserId = $allUserId;

        return $this;
    }

}
