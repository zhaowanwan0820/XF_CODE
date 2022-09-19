<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * ProjectDeal
 *
 * 由代码生成器生成, 不可人为修改
 * @author liuzhenpeng
 */
class ProtoProjectDeal extends ProtoBufferBase
{
    /**
     * projectID
     *
     * @var int
     * @optional
     */
    private $id = 0;

    /**
     * userId
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 放款审批单编号
     *
     * @var string
     * @optional
     */
    private $approveNumber = '';

    /**
     * 标的名称
     *
     * @var string
     * @optional
     */
    private $name = '';

    /**
     * 借款总金额
     *
     * @var float
     * @optional
     */
    private $borrowAmount = 0;

    /**
     * 还款方式
     *
     * @var int
     * @optional
     */
    private $loanType = 0;

    /**
     * 借款期限
     *
     * @var int
     * @optional
     */
    private $repayReriod = 0;

    /**
     * 借款综合成本(年化)
     *
     * @var float
     * @optional
     */
    private $rate = 0;

    /**
     * 项目授信额度
     *
     * @var float
     * @optional
     */
    private $credit = 0;

    /**
     * 项目简介（word附件地址）
     *
     * @var string
     * @optional
     */
    private $projectInfoUrl = '';

    /**
     * 标的类型
     *
     * @var int
     * @optional
     */
    private $dealType = 0;

    /**
     * 项目锁定期
     *
     * @var int
     * @optional
     */
    private $lockPeriod = 0;

    /**
     * 项目赎回期
     *
     * @var int
     * @optional
     */
    private $redemptionPeriod = 0;

    /**
     * 咨询机构名称
     *
     * @var int
     * @optional
     */
    private $advisoryId = 0;

    /**
     * 担保机构名称
     *
     * @var int
     * @optional
     */
    private $agencyId = 0;

    /**
     * 产品类别(借款用途)
     *
     * @var int
     * @optional
     */
    private $typeId = 0;

    /**
     * 年化借款平台手续费
     *
     * @var float
     * @optional
     */
    private $manageFeeRate = 0;

    /**
     * 年化借款咨询费
     *
     * @var float
     * @optional
     */
    private $consultFeeRate = 0;

    /**
     * 提前还款违约金系数
     *
     * @var float
     * @optional
     */
    private $prepayRate = 0;

    /**
     * 提前还款罚息天数
     *
     * @var int
     * @optional
     */
    private $prepayPenaltyDays = 0;

    /**
     * 提前还款限制(提前还款锁定天数)
     *
     * @var int
     * @optional
     */
    private $prepayDaysLimit = 0;

    /**
     * 逾期还款罚息系数
     *
     * @var float
     * @optional
     */
    private $overdueRate = 0;

    /**
     * 代偿时间(天)
     *
     * @var int
     * @optional
     */
    private $overdueDay = 0;

    /**
     * 合同类型
     *
     * @var string
     * @optional
     */
    private $contractTplType = '';

    /**
     * 基础合同编号
     *
     * @var string
     * @optional
     */
    private $leasingContractNum = '';

    /**
     * 基础合同项下承租人名称
     *
     * @var string
     * @optional
     */
    private $lesseeRealName = '';

    /**
     * 基础合同交易金额(元)
     *
     * @var float
     * @optional
     */
    private $leasingMoney = 0;

    /**
     * 委托贷款委托合同编号
     *
     * @var string
     * @optional
     */
    private $entrustedLoanEntrustedContractNum = '';

    /**
     * 委托贷款借款合同编号
     *
     * @var string
     * @optional
     */
    private $entrustedLoanBorrowContractNum = '';

    /**
     * 基础合同借款到期日
     *
     * @var int
     * @optional
     */
    private $baseContractRepayTime = 0;

    /**
     * 借款担保费
     *
     * @var float
     * @optional
     */
    private $guaranteeFeeRate = 0;

    /**
     * 打包费率
     *
     * @var float
     * @optional
     */
    private $packingRate = 0;

    /**
     * 借款期限类型
     *
     * @var int
     * @optional
     */
    private $repayPeriodType = 1;

    /**
     * 年化支付费率
     *
     * @var float
     * @optional
     */
    private $annualPaymentRate = 1;

    /**
     * 上线网站编号
     *
     * @var int
     * @optional
     */
    private $lineSiteId = 0;

    /**
     * 上线网站名称
     *
     * @var string
     * @optional
     */
    private $lineSiteName = '';

    /**
     * 中途逾期强还天数
     *
     * @var int
     * @optional
     */
    private $overdueBreakDays = 0;

    /**
     * 年化借款平台手续费类型
     *
     * @var int
     * @optional
     */
    private $loanFeeRateType = 1;

    /**
     * 年化借款咨询费类型
     *
     * @var int
     * @optional
     */
    private $consultFeeRateType = 1;

    /**
     * 年化借款担保费类型
     *
     * @var int
     * @optional
     */
    private $guaranteeFeeRateType = 1;

    /**
     * 年化支付服务费
     *
     * @var int
     * @optional
     */
    private $payFeeRateType = 1;

    /**
     * 基础合同名称
     *
     * @var string
     * @optional
     */
    private $leasingContractTitle = '';

    /**
     * 转让资产类别
     *
     * @var int
     * @optional
     */
    private $contractTransferType = 1;

    /**
     * 转让资产类别
     *
     * @var string
     * @optional
     */
    private $loanApplicationType = '';

    /**
     * 投资人收益率
     *
     * @var int
     * @optional
     */
    private $rateYields = 0;

    /**
     * 放款方式
     *
     * @var int
     * @optional
     */
    private $loanMoneyType = 0;

    /**
     * 开户人姓名
     *
     * @var string
     * @optional
     */
    private $cardName = '';

    /**
     * 银行名称
     *
     * @var string
     * @optional
     */
    private $bankCard = '';

    /**
     * 开户行
     *
     * @var string
     * @optional
     */
    private $bankZone = '';

    /**
     * 银行id
     *
     * @var int
     * @optional
     */
    private $bankId = 0;

    /**
     * 合同是否被委托签署
     *
     * @var int
     * @optional
     */
    private $entrustSign = 0;

    /**
     * 用户类型
     *
     * @var int
     * @optional
     */
    private $userTypes = 2;

    /**
     * 固定还款日
     *
     * @var int
     * @optional
     */
    private $fixedReplay = 0;

    /**
     * 代偿机构
     *
     * @var int
     * @optional
     */
    private $advanceAgencyId = 1;

    /**
     * 项目担保方合同是否被委托签署
     *
     * @var int
     * @optional
     */
    private $entrustAgencySign = 0;

    /**
     * 项目资产管理方合同是否被委托签署
     *
     * @var int
     * @optional
     */
    private $entrustAdvisorySign = 0;

    /**
     * 担保范围
     *
     * @var int
     * @optional
     */
    private $warrant = 2;

    /**
     * 产品类别
     *
     * @var string
     * @optional
     */
    private $productClass = '';

    /**
     * 产品名称
     *
     * @var string
     * @optional
     */
    private $productName = '';

    /**
     * 是否添加项目
     *
     * @var int
     * @optional
     */
    private $isAddProject = 0;

    /**
     * 项目id
     *
     * @var int
     * @optional
     */
    private $projectId = 0;

    /**
     * 是否非信贷上标
     *
     * @var int
     * @optional
     */
    private $isCredit = 0;

    /**
     * 自定义属性(描述)
     *
     * @var string
     * @optional
     */
    private $dealTagDesc = '';

    /**
     * 最低投资金额
     *
     * @var int
     * @optional
     */
    private $minLoanMoney = 0;

    /**
     * 最高投资金额
     *
     * @var int
     * @optional
     */
    private $maxLoanMoney = 0;

    /**
     * 自定义属性
     *
     * @var string
     * @optional
     */
    private $dealTagName = '';

    /**
     * 附件合同模板
     *
     * @var string
     * @optional
     */
    private $attachInfo = '';

    /**
     * 业务线
     *
     * @var string
     * @optional
     */
    private $businessLines = '';

    /**
     * 是否有效
     *
     * @var int
     * @optional
     */
    private $isEffect = 0;

    /**
     * 项目名称
     *
     * @var string
     * @optional
     */
    private $projectName = '';

    /**
     * 项目借款总金额
     *
     * @var float
     * @optional
     */
    private $projectBorrowAmout = 0;

    /**
     * 委托机构
     *
     * @var int
     * @optional
     */
    private $entrustAgencyId = 0;

    /**
     * 委托投资说明
     *
     * @var string
     * @optional
     */
    private $entrustInvestmentDesc = '';

    /**
     * 固定起息日
     *
     * @var int
     * @optional
     */
    private $fixedValueDate = 0;

    /**
     * 放款账号类型(1:对公 0:对私)
     *
     * @var int
     * @optional
     */
    private $cardType = 0;

    /**
     * 风险承受能力
     *
     * @var int
     * @optional
     */
    private $riskBearing = '0';

    /**
     * 产品结构1级
     *
     * @var string
     * @optional
     */
    private $productMix1 = '';

    /**
     * 产品结构2级
     *
     * @var string
     * @optional
     */
    private $productMix2 = '';

    /**
     * 产品结构3级
     *
     * @var string
     * @optional
     */
    private $productMix3 = '';

    /**
     * 手续费分期信息
     *
     * @var string
     * @optional
     */
    private $loanFeeExt = '';

    /**
     * 代充值机构id
     *
     * @var int
     * @optional
     */
    private $generationRechargeId = 0;

    /**
     * 咨询机构报警级别
     *
     * @var int
     * @optional
     */
    private $advisoryWarningLevel = 0;

    /**
     * 产品报警级别
     *
     * @var int
     * @optional
     */
    private $productWarningLevel = 0;

    /**
     * 咨询机构已经使用的钱数
     *
     * @var float
     * @optional
     */
    private $advisoryWarningUseMoney = 0;

    /**
     * 相应产品已经使用的钱数
     *
     * @var float
     * @optional
     */
    private $productWarningUseMoney = 0;

    /**
     * 咨询机构名称
     *
     * @var string
     * @optional
     */
    private $advisoryName = '';

    /**
     * 基础资产描述
     *
     * @var string
     * @optional
     */
    private $assetsDesc = '';

    /**
     * 交易所ID
     *
     * @var int
     * @optional
     */
    private $jysId = 0;

    /**
     * 交易所备案产品号
     *
     * @var string
     * @optional
     */
    private $jysRecordNumber = '';

    /**
     * 放款类型
     *
     * @var int
     * @optional
     */
    private $extLoanType = 0;

    /**
     * 是否启用浮动起投金额 0--否 1--是
     *
     * @var int
     * @optional
     */
    private $isFloatMinLoan = 0;

    /**
     * 平台费折扣率
     *
     * @var float
     * @optional
     */
    private $discountRate = 100;

    /**
     * 渠道机构id
     *
     * @var int
     * @optional
     */
    private $canalAgencyId = 0;

    /**
     * 渠道服务费率
     *
     * @var string
     * @optional
     */
    private $canalFeeRate = 0;

    /**
     * 渠道服务费收取方式
     *
     * @var int
     * @optional
     */
    private $canalFeeRateType = 0;

    /**
     * 分期咨询费率
     *
     * @var float
     * @optional
     */
    private $consultFeePeriodRate = 0;
     
     /**
     * 客群
     *
     * @var int
     * @optional
     */
    private $loanUserCustomerType = 0;

    /**
     * 产品大类ID
     *
     * @var int
     * @optional
     */
    private $productClassType = 0;

    /**
     * 结算方式
     * @var int
     */
    private $clearingType = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoProjectDeal
     */
    public function setId($id = 0)
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return ProtoProjectDeal
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
    public function getApproveNumber()
    {
        return $this->approveNumber;
    }

    /**
     * @param string $approveNumber
     * @return ProtoProjectDeal
     */
    public function setApproveNumber($approveNumber = '')
    {
        $this->approveNumber = $approveNumber;

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
     * @return ProtoProjectDeal
     */
    public function setName($name = '')
    {
        $this->name = $name;

        return $this;
    }
    /**
     * @return float
     */
    public function getBorrowAmount()
    {
        return $this->borrowAmount;
    }

    /**
     * @param float $borrowAmount
     * @return ProtoProjectDeal
     */
    public function setBorrowAmount($borrowAmount = 0)
    {
        $this->borrowAmount = $borrowAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getLoanType()
    {
        return $this->loanType;
    }

    /**
     * @param int $loanType
     * @return ProtoProjectDeal
     */
    public function setLoanType($loanType = 0)
    {
        $this->loanType = $loanType;

        return $this;
    }
    /**
     * @return int
     */
    public function getRepayReriod()
    {
        return $this->repayReriod;
    }

    /**
     * @param int $repayReriod
     * @return ProtoProjectDeal
     */
    public function setRepayReriod($repayReriod = 0)
    {
        $this->repayReriod = $repayReriod;

        return $this;
    }
    /**
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param float $rate
     * @return ProtoProjectDeal
     */
    public function setRate($rate = 0)
    {
        $this->rate = $rate;

        return $this;
    }
    /**
     * @return float
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @param float $credit
     * @return ProtoProjectDeal
     */
    public function setCredit($credit = 0)
    {
        $this->credit = $credit;

        return $this;
    }
    /**
     * @return string
     */
    public function getProjectInfoUrl()
    {
        return $this->projectInfoUrl;
    }

    /**
     * @param string $projectInfoUrl
     * @return ProtoProjectDeal
     */
    public function setProjectInfoUrl($projectInfoUrl = '')
    {
        $this->projectInfoUrl = $projectInfoUrl;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealType()
    {
        return $this->dealType;
    }

    /**
     * @param int $dealType
     * @return ProtoProjectDeal
     */
    public function setDealType($dealType = 0)
    {
        $this->dealType = $dealType;

        return $this;
    }
    /**
     * @return int
     */
    public function getLockPeriod()
    {
        return $this->lockPeriod;
    }

    /**
     * @param int $lockPeriod
     * @return ProtoProjectDeal
     */
    public function setLockPeriod($lockPeriod = 0)
    {
        $this->lockPeriod = $lockPeriod;

        return $this;
    }
    /**
     * @return int
     */
    public function getRedemptionPeriod()
    {
        return $this->redemptionPeriod;
    }

    /**
     * @param int $redemptionPeriod
     * @return ProtoProjectDeal
     */
    public function setRedemptionPeriod($redemptionPeriod = 0)
    {
        $this->redemptionPeriod = $redemptionPeriod;

        return $this;
    }
    /**
     * @return int
     */
    public function getAdvisoryId()
    {
        return $this->advisoryId;
    }

    /**
     * @param int $advisoryId
     * @return ProtoProjectDeal
     */
    public function setAdvisoryId($advisoryId = 0)
    {
        $this->advisoryId = $advisoryId;

        return $this;
    }
    /**
     * @return int
     */
    public function getAgencyId()
    {
        return $this->agencyId;
    }

    /**
     * @param int $agencyId
     * @return ProtoProjectDeal
     */
    public function setAgencyId($agencyId = 0)
    {
        $this->agencyId = $agencyId;

        return $this;
    }
    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param int $typeId
     * @return ProtoProjectDeal
     */
    public function setTypeId($typeId = 0)
    {
        $this->typeId = $typeId;

        return $this;
    }
    /**
     * @return float
     */
    public function getManageFeeRate()
    {
        return $this->manageFeeRate;
    }

    /**
     * @param float $manageFeeRate
     * @return ProtoProjectDeal
     */
    public function setManageFeeRate($manageFeeRate = 0)
    {
        $this->manageFeeRate = $manageFeeRate;

        return $this;
    }
    /**
     * @return float
     */
    public function getConsultFeeRate()
    {
        return $this->consultFeeRate;
    }

    /**
     * @param float $consultFeeRate
     * @return ProtoProjectDeal
     */
    public function setConsultFeeRate($consultFeeRate = 0)
    {
        $this->consultFeeRate = $consultFeeRate;

        return $this;
    }
    /**
     * @return float
     */
    public function getPrepayRate()
    {
        return $this->prepayRate;
    }

    /**
     * @param float $prepayRate
     * @return ProtoProjectDeal
     */
    public function setPrepayRate($prepayRate = 0)
    {
        $this->prepayRate = $prepayRate;

        return $this;
    }
    /**
     * @return int
     */
    public function getPrepayPenaltyDays()
    {
        return $this->prepayPenaltyDays;
    }

    /**
     * @param int $prepayPenaltyDays
     * @return ProtoProjectDeal
     */
    public function setPrepayPenaltyDays($prepayPenaltyDays = 0)
    {
        $this->prepayPenaltyDays = $prepayPenaltyDays;

        return $this;
    }
    /**
     * @return int
     */
    public function getPrepayDaysLimit()
    {
        return $this->prepayDaysLimit;
    }

    /**
     * @param int $prepayDaysLimit
     * @return ProtoProjectDeal
     */
    public function setPrepayDaysLimit($prepayDaysLimit = 0)
    {
        $this->prepayDaysLimit = $prepayDaysLimit;

        return $this;
    }
    /**
     * @return float
     */
    public function getOverdueRate()
    {
        return $this->overdueRate;
    }

    /**
     * @param float $overdueRate
     * @return ProtoProjectDeal
     */
    public function setOverdueRate($overdueRate = 0)
    {
        $this->overdueRate = $overdueRate;

        return $this;
    }
    /**
     * @return int
     */
    public function getOverdueDay()
    {
        return $this->overdueDay;
    }

    /**
     * @param int $overdueDay
     * @return ProtoProjectDeal
     */
    public function setOverdueDay($overdueDay = 0)
    {
        $this->overdueDay = $overdueDay;

        return $this;
    }
    /**
     * @return string
     */
    public function getContractTplType()
    {
        return $this->contractTplType;
    }

    /**
     * @param string $contractTplType
     * @return ProtoProjectDeal
     */
    public function setContractTplType($contractTplType = '')
    {
        $this->contractTplType = $contractTplType;

        return $this;
    }
    /**
     * @return string
     */
    public function getLeasingContractNum()
    {
        return $this->leasingContractNum;
    }

    /**
     * @param string $leasingContractNum
     * @return ProtoProjectDeal
     */
    public function setLeasingContractNum($leasingContractNum = '')
    {
        $this->leasingContractNum = $leasingContractNum;

        return $this;
    }
    /**
     * @return string
     */
    public function getLesseeRealName()
    {
        return $this->lesseeRealName;
    }

    /**
     * @param string $lesseeRealName
     * @return ProtoProjectDeal
     */
    public function setLesseeRealName($lesseeRealName = '')
    {
        $this->lesseeRealName = $lesseeRealName;

        return $this;
    }
    /**
     * @return float
     */
    public function getLeasingMoney()
    {
        return $this->leasingMoney;
    }

    /**
     * @param float $leasingMoney
     * @return ProtoProjectDeal
     */
    public function setLeasingMoney($leasingMoney = 0)
    {
        $this->leasingMoney = $leasingMoney;

        return $this;
    }
    /**
     * @return string
     */
    public function getEntrustedLoanEntrustedContractNum()
    {
        return $this->entrustedLoanEntrustedContractNum;
    }

    /**
     * @param string $entrustedLoanEntrustedContractNum
     * @return ProtoProjectDeal
     */
    public function setEntrustedLoanEntrustedContractNum($entrustedLoanEntrustedContractNum = '')
    {
        $this->entrustedLoanEntrustedContractNum = $entrustedLoanEntrustedContractNum;

        return $this;
    }
    /**
     * @return string
     */
    public function getEntrustedLoanBorrowContractNum()
    {
        return $this->entrustedLoanBorrowContractNum;
    }

    /**
     * @param string $entrustedLoanBorrowContractNum
     * @return ProtoProjectDeal
     */
    public function setEntrustedLoanBorrowContractNum($entrustedLoanBorrowContractNum = '')
    {
        $this->entrustedLoanBorrowContractNum = $entrustedLoanBorrowContractNum;

        return $this;
    }
    /**
     * @return int
     */
    public function getBaseContractRepayTime()
    {
        return $this->baseContractRepayTime;
    }

    /**
     * @param int $baseContractRepayTime
     * @return ProtoProjectDeal
     */
    public function setBaseContractRepayTime($baseContractRepayTime = 0)
    {
        $this->baseContractRepayTime = $baseContractRepayTime;

        return $this;
    }
    /**
     * @return float
     */
    public function getGuaranteeFeeRate()
    {
        return $this->guaranteeFeeRate;
    }

    /**
     * @param float $guaranteeFeeRate
     * @return ProtoProjectDeal
     */
    public function setGuaranteeFeeRate($guaranteeFeeRate = 0)
    {
        $this->guaranteeFeeRate = $guaranteeFeeRate;

        return $this;
    }
    /**
     * @return float
     */
    public function getPackingRate()
    {
        return $this->packingRate;
    }

    /**
     * @param float $packingRate
     * @return ProtoProjectDeal
     */
    public function setPackingRate($packingRate = 0)
    {
        $this->packingRate = $packingRate;

        return $this;
    }
    /**
     * @return int
     */
    public function getRepayPeriodType()
    {
        return $this->repayPeriodType;
    }

    /**
     * @param int $repayPeriodType
     * @return ProtoProjectDeal
     */
    public function setRepayPeriodType($repayPeriodType = 1)
    {
        $this->repayPeriodType = $repayPeriodType;

        return $this;
    }
    /**
     * @return float
     */
    public function getAnnualPaymentRate()
    {
        return $this->annualPaymentRate;
    }

    /**
     * @param float $annualPaymentRate
     * @return ProtoProjectDeal
     */
    public function setAnnualPaymentRate($annualPaymentRate = 1)
    {
        $this->annualPaymentRate = $annualPaymentRate;

        return $this;
    }
    /**
     * @return int
     */
    public function getLineSiteId()
    {
        return $this->lineSiteId;
    }

    /**
     * @param int $lineSiteId
     * @return ProtoProjectDeal
     */
    public function setLineSiteId($lineSiteId = 0)
    {
        $this->lineSiteId = $lineSiteId;

        return $this;
    }
    /**
     * @return string
     */
    public function getLineSiteName()
    {
        return $this->lineSiteName;
    }

    /**
     * @param string $lineSiteName
     * @return ProtoProjectDeal
     */
    public function setLineSiteName($lineSiteName = '')
    {
        $this->lineSiteName = $lineSiteName;

        return $this;
    }
    /**
     * @return int
     */
    public function getOverdueBreakDays()
    {
        return $this->overdueBreakDays;
    }

    /**
     * @param int $overdueBreakDays
     * @return ProtoProjectDeal
     */
    public function setOverdueBreakDays($overdueBreakDays = 0)
    {
        $this->overdueBreakDays = $overdueBreakDays;

        return $this;
    }
    /**
     * @return int
     */
    public function getLoanFeeRateType()
    {
        return $this->loanFeeRateType;
    }

    /**
     * @param int $loanFeeRateType
     * @return ProtoProjectDeal
     */
    public function setLoanFeeRateType($loanFeeRateType = 1)
    {
        $this->loanFeeRateType = $loanFeeRateType;

        return $this;
    }
    /**
     * @return int
     */
    public function getConsultFeeRateType()
    {
        return $this->consultFeeRateType;
    }

    /**
     * @param int $consultFeeRateType
     * @return ProtoProjectDeal
     */
    public function setConsultFeeRateType($consultFeeRateType = 1)
    {
        $this->consultFeeRateType = $consultFeeRateType;

        return $this;
    }
    /**
     * @return int
     */
    public function getGuaranteeFeeRateType()
    {
        return $this->guaranteeFeeRateType;
    }

    /**
     * @param int $guaranteeFeeRateType
     * @return ProtoProjectDeal
     */
    public function setGuaranteeFeeRateType($guaranteeFeeRateType = 1)
    {
        $this->guaranteeFeeRateType = $guaranteeFeeRateType;

        return $this;
    }
    /**
     * @return int
     */
    public function getPayFeeRateType()
    {
        return $this->payFeeRateType;
    }

    /**
     * @param int $payFeeRateType
     * @return ProtoProjectDeal
     */
    public function setPayFeeRateType($payFeeRateType = 1)
    {
        $this->payFeeRateType = $payFeeRateType;

        return $this;
    }
    /**
     * @return string
     */
    public function getLeasingContractTitle()
    {
        return $this->leasingContractTitle;
    }

    /**
     * @param string $leasingContractTitle
     * @return ProtoProjectDeal
     */
    public function setLeasingContractTitle($leasingContractTitle = '')
    {
        $this->leasingContractTitle = $leasingContractTitle;

        return $this;
    }
    /**
     * @return int
     */
    public function getContractTransferType()
    {
        return $this->contractTransferType;
    }

    /**
     * @param int $contractTransferType
     * @return ProtoProjectDeal
     */
    public function setContractTransferType($contractTransferType = 1)
    {
        $this->contractTransferType = $contractTransferType;

        return $this;
    }
    /**
     * @return string
     */
    public function getLoanApplicationType()
    {
        return $this->loanApplicationType;
    }

    /**
     * @param string $loanApplicationType
     * @return ProtoProjectDeal
     */
    public function setLoanApplicationType($loanApplicationType = '')
    {
        $this->loanApplicationType = $loanApplicationType;

        return $this;
    }
    /**
     * @return int
     */
    public function getRateYields()
    {
        return $this->rateYields;
    }

    /**
     * @param int $rateYields
     * @return ProtoProjectDeal
     */
    public function setRateYields($rateYields = 0)
    {
        $this->rateYields = $rateYields;

        return $this;
    }
    /**
     * @return int
     */
    public function getLoanMoneyType()
    {
        return $this->loanMoneyType;
    }

    /**
     * @param int $loanMoneyType
     * @return ProtoProjectDeal
     */
    public function setLoanMoneyType($loanMoneyType = 0)
    {
        $this->loanMoneyType = $loanMoneyType;

        return $this;
    }
    /**
     * @return string
     */
    public function getCardName()
    {
        return $this->cardName;
    }

    /**
     * @param string $cardName
     * @return ProtoProjectDeal
     */
    public function setCardName($cardName = '')
    {
        $this->cardName = $cardName;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankCard()
    {
        return $this->bankCard;
    }

    /**
     * @param string $bankCard
     * @return ProtoProjectDeal
     */
    public function setBankCard($bankCard = '')
    {
        $this->bankCard = $bankCard;

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
     * @return ProtoProjectDeal
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
     * @return ProtoProjectDeal
     */
    public function setBankId($bankId = 0)
    {
        $this->bankId = $bankId;

        return $this;
    }
    /**
     * @return int
     */
    public function getEntrustSign()
    {
        return $this->entrustSign;
    }

    /**
     * @param int $entrustSign
     * @return ProtoProjectDeal
     */
    public function setEntrustSign($entrustSign = 0)
    {
        $this->entrustSign = $entrustSign;

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
     * @return ProtoProjectDeal
     */
    public function setUserTypes($userTypes = 2)
    {
        $this->userTypes = $userTypes;

        return $this;
    }
    /**
     * @return int
     */
    public function getFixedReplay()
    {
        return $this->fixedReplay;
    }

    /**
     * @param int $fixedReplay
     * @return ProtoProjectDeal
     */
    public function setFixedReplay($fixedReplay = 0)
    {
        $this->fixedReplay = $fixedReplay;

        return $this;
    }
    /**
     * @return int
     */
    public function getAdvanceAgencyId()
    {
        return $this->advanceAgencyId;
    }

    /**
     * @param int $advanceAgencyId
     * @return ProtoProjectDeal
     */
    public function setAdvanceAgencyId($advanceAgencyId = 1)
    {
        $this->advanceAgencyId = $advanceAgencyId;

        return $this;
    }
    /**
     * @return int
     */
    public function getEntrustAgencySign()
    {
        return $this->entrustAgencySign;
    }

    /**
     * @param int $entrustAgencySign
     * @return ProtoProjectDeal
     */
    public function setEntrustAgencySign($entrustAgencySign = 0)
    {
        $this->entrustAgencySign = $entrustAgencySign;

        return $this;
    }
    /**
     * @return int
     */
    public function getEntrustAdvisorySign()
    {
        return $this->entrustAdvisorySign;
    }

    /**
     * @param int $entrustAdvisorySign
     * @return ProtoProjectDeal
     */
    public function setEntrustAdvisorySign($entrustAdvisorySign = 0)
    {
        $this->entrustAdvisorySign = $entrustAdvisorySign;

        return $this;
    }
    /**
     * @return int
     */
    public function getWarrant()
    {
        return $this->warrant;
    }

    /**
     * @param int $warrant
     * @return ProtoProjectDeal
     */
    public function setWarrant($warrant = 2)
    {
        $this->warrant = $warrant;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductClass()
    {
        return $this->productClass;
    }

    /**
     * @param string $productClass
     * @return ProtoProjectDeal
     */
    public function setProductClass($productClass = '')
    {
        $this->productClass = $productClass;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * @param string $productName
     * @return ProtoProjectDeal
     */
    public function setProductName($productName = '')
    {
        $this->productName = $productName;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsAddProject()
    {
        return $this->isAddProject;
    }

    /**
     * @param int $isAddProject
     * @return ProtoProjectDeal
     */
    public function setIsAddProject($isAddProject = 0)
    {
        $this->isAddProject = $isAddProject;

        return $this;
    }
    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     * @return ProtoProjectDeal
     */
    public function setProjectId($projectId = 0)
    {
        $this->projectId = $projectId;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsCredit()
    {
        return $this->isCredit;
    }

    /**
     * @param int $isCredit
     * @return ProtoProjectDeal
     */
    public function setIsCredit($isCredit = 0)
    {
        $this->isCredit = $isCredit;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealTagDesc()
    {
        return $this->dealTagDesc;
    }

    /**
     * @param string $dealTagDesc
     * @return ProtoProjectDeal
     */
    public function setDealTagDesc($dealTagDesc = '')
    {
        $this->dealTagDesc = $dealTagDesc;

        return $this;
    }
    /**
     * @return int
     */
    public function getMinLoanMoney()
    {
        return $this->minLoanMoney;
    }

    /**
     * @param int $minLoanMoney
     * @return ProtoProjectDeal
     */
    public function setMinLoanMoney($minLoanMoney = 0)
    {
        $this->minLoanMoney = $minLoanMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getMaxLoanMoney()
    {
        return $this->maxLoanMoney;
    }

    /**
     * @param int $maxLoanMoney
     * @return ProtoProjectDeal
     */
    public function setMaxLoanMoney($maxLoanMoney = 0)
    {
        $this->maxLoanMoney = $maxLoanMoney;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealTagName()
    {
        return $this->dealTagName;
    }

    /**
     * @param string $dealTagName
     * @return ProtoProjectDeal
     */
    public function setDealTagName($dealTagName = '')
    {
        $this->dealTagName = $dealTagName;

        return $this;
    }
    /**
     * @return string
     */
    public function getAttachInfo()
    {
        return $this->attachInfo;
    }

    /**
     * @param string $attachInfo
     * @return ProtoProjectDeal
     */
    public function setAttachInfo($attachInfo = '')
    {
        $this->attachInfo = $attachInfo;

        return $this;
    }
    /**
     * @return string
     */
    public function getBusinessLines()
    {
        return $this->businessLines;
    }

    /**
     * @param string $businessLines
     * @return ProtoProjectDeal
     */
    public function setBusinessLines($businessLines = '')
    {
        $this->businessLines = $businessLines;

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
     * @return ProtoProjectDeal
     */
    public function setIsEffect($isEffect = 0)
    {
        $this->isEffect = $isEffect;

        return $this;
    }
    /**
     * @return string
     */
    public function getProjectName()
    {
        return $this->projectName;
    }

    /**
     * @param string $projectName
     * @return ProtoProjectDeal
     */
    public function setProjectName($projectName = '')
    {
        $this->projectName = $projectName;

        return $this;
    }
    /**
     * @return float
     */
    public function getProjectBorrowAmout()
    {
        return $this->projectBorrowAmout;
    }

    /**
     * @param float $projectBorrowAmout
     * @return ProtoProjectDeal
     */
    public function setProjectBorrowAmout($projectBorrowAmout = 0)
    {
        $this->projectBorrowAmout = $projectBorrowAmout;

        return $this;
    }
    /**
     * @return int
     */
    public function getEntrustAgencyId()
    {
        return $this->entrustAgencyId;
    }

    /**
     * @param int $entrustAgencyId
     * @return ProtoProjectDeal
     */
    public function setEntrustAgencyId($entrustAgencyId = 0)
    {
        $this->entrustAgencyId = $entrustAgencyId;

        return $this;
    }
    /**
     * @return string
     */
    public function getEntrustInvestmentDesc()
    {
        return $this->entrustInvestmentDesc;
    }

    /**
     * @param string $entrustInvestmentDesc
     * @return ProtoProjectDeal
     */
    public function setEntrustInvestmentDesc($entrustInvestmentDesc = '')
    {
        $this->entrustInvestmentDesc = $entrustInvestmentDesc;

        return $this;
    }
    /**
     * @return int
     */
    public function getFixedValueDate()
    {
        return $this->fixedValueDate;
    }

    /**
     * @param int $fixedValueDate
     * @return ProtoProjectDeal
     */
    public function setFixedValueDate($fixedValueDate = 0)
    {
        $this->fixedValueDate = $fixedValueDate;

        return $this;
    }
    /**
     * @return int
     */
    public function getCardType()
    {
        return $this->cardType;
    }

    /**
     * @param int $cardType
     * @return ProtoProjectDeal
     */
    public function setCardType($cardType = 0)
    {
        $this->cardType = $cardType;

        return $this;
    }
    /**
     * @return int
     */
    public function getRiskBearing()
    {
        return $this->riskBearing;
    }

    /**
     * @param int $riskBearing
     * @return ProtoProjectDeal
     */
    public function setRiskBearing($riskBearing = '0')
    {
        $this->riskBearing = $riskBearing;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductMix1()
    {
        return $this->productMix1;
    }

    /**
     * @param string $productMix1
     * @return ProtoProjectDeal
     */
    public function setProductMix1($productMix1 = '')
    {
        $this->productMix1 = $productMix1;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductMix2()
    {
        return $this->productMix2;
    }

    /**
     * @param string $productMix2
     * @return ProtoProjectDeal
     */
    public function setProductMix2($productMix2 = '')
    {
        $this->productMix2 = $productMix2;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductMix3()
    {
        return $this->productMix3;
    }

    /**
     * @param string $productMix3
     * @return ProtoProjectDeal
     */
    public function setProductMix3($productMix3 = '')
    {
        $this->productMix3 = $productMix3;

        return $this;
    }
    /**
     * @return string
     */
    public function getLoanFeeExt()
    {
        return $this->loanFeeExt;
    }

    /**
     * @param string $loanFeeExt
     * @return ProtoProjectDeal
     */
    public function setLoanFeeExt($loanFeeExt = '')
    {
        $this->loanFeeExt = $loanFeeExt;

        return $this;
    }
    /**
     * @return int
     */
    public function getGenerationRechargeId()
    {
        return $this->generationRechargeId;
    }

    /**
     * @param int $generationRechargeId
     * @return ProtoProjectDeal
     */
    public function setGenerationRechargeId($generationRechargeId = 0)
    {
        $this->generationRechargeId = $generationRechargeId;

        return $this;
    }
    /**
     * @return int
     */
    public function getAdvisoryWarningLevel()
    {
        return $this->advisoryWarningLevel;
    }

    /**
     * @param int $advisoryWarningLevel
     * @return ProtoProjectDeal
     */
    public function setAdvisoryWarningLevel($advisoryWarningLevel = 0)
    {
        $this->advisoryWarningLevel = $advisoryWarningLevel;

        return $this;
    }
    /**
     * @return int
     */
    public function getProductWarningLevel()
    {
        return $this->productWarningLevel;
    }

    /**
     * @param int $productWarningLevel
     * @return ProtoProjectDeal
     */
    public function setProductWarningLevel($productWarningLevel = 0)
    {
        $this->productWarningLevel = $productWarningLevel;

        return $this;
    }
    /**
     * @return float
     */
    public function getAdvisoryWarningUseMoney()
    {
        return $this->advisoryWarningUseMoney;
    }

    /**
     * @param float $advisoryWarningUseMoney
     * @return ProtoProjectDeal
     */
    public function setAdvisoryWarningUseMoney($advisoryWarningUseMoney = 0)
    {
        $this->advisoryWarningUseMoney = $advisoryWarningUseMoney;

        return $this;
    }
    /**
     * @return float
     */
    public function getProductWarningUseMoney()
    {
        return $this->productWarningUseMoney;
    }

    /**
     * @param float $productWarningUseMoney
     * @return ProtoProjectDeal
     */
    public function setProductWarningUseMoney($productWarningUseMoney = 0)
    {
        $this->productWarningUseMoney = $productWarningUseMoney;

        return $this;
    }
    /**
     * @return string
     */
    public function getAdvisoryName()
    {
        return $this->advisoryName;
    }

    /**
     * @param string $advisoryName
     * @return ProtoProjectDeal
     */
    public function setAdvisoryName($advisoryName = '')
    {
        $this->advisoryName = $advisoryName;

        return $this;
    }
    /**
     * @return string
     */
    public function getAssetsDesc()
    {
        return $this->assetsDesc;
    }

    /**
     * @param string $assetsDesc
     * @return ProtoProjectDeal
     */
    public function setAssetsDesc($assetsDesc = '')
    {
        $this->assetsDesc = $assetsDesc;

        return $this;
    }
    /**
     * @return int
     */
    public function getJysId()
    {
        return $this->jysId;
    }

    /**
     * @param int $jysId
     * @return ProtoProjectDeal
     */
    public function setJysId($jysId = 0)
    {
        $this->jysId = $jysId;

        return $this;
    }
    /**
     * @return string
     */
    public function getJysRecordNumber()
    {
        return $this->jysRecordNumber;
    }

    /**
     * @param string $jysRecordNumber
     * @return ProtoProjectDeal
     */
    public function setJysRecordNumber($jysRecordNumber = '')
    {
        $this->jysRecordNumber = $jysRecordNumber;

        return $this;
    }
    /**
     * @return int
     */
    public function getExtLoanType()
    {
        return $this->extLoanType;
    }

    /**
     * @param int $extLoanType
     * @return ProtoProjectDeal
     */
    public function setExtLoanType($extLoanType = 0)
    {
        $this->extLoanType = $extLoanType;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsFloatMinLoan()
    {
        return $this->isFloatMinLoan;
    }

    /**
     * @param int $isFloatMinLoan
     * @return ProtoProjectDeal
     */
    public function setIsFloatMinLoan($isFloatMinLoan = 0)
    {
        $this->isFloatMinLoan = $isFloatMinLoan;

        return $this;
    }
    /**
     * @return float
     */
    public function getDiscountRate()
    {
        return $this->discountRate;
    }

    /**
     * @param float $discountRate
     * @return ProtoProjectDeal
     */
    public function setDiscountRate($discountRate = 100)
    {
        $this->discountRate = $discountRate;

        return $this;
    }
    /**
     * @return int
     */
    public function getCanalAgencyId()
    {
        return $this->canalAgencyId;
    }

    /**
     * @param int $canalAgencyId
     * @return ProtoProjectDeal
     */
    public function setCanalAgencyId($canalAgencyId = 0)
    {
        $this->canalAgencyId = $canalAgencyId;

        return $this;
    }
    /**
     * @return string
     */
    public function getCanalFeeRate()
    {
        return $this->canalFeeRate;
    }

    /**
     * @param string $canalFeeRate
     * @return ProtoProjectDeal
     */
    public function setCanalFeeRate($canalFeeRate = 0)
    {
        $this->canalFeeRate = $canalFeeRate;

        return $this;
    }
    /**
     * @return int
     */
    public function getCanalFeeRateType()
    {
        return $this->canalFeeRateType;
    }

    /**
     * @param int $canalFeeRateType
     * @return ProtoProjectDeal
     */
    public function setCanalFeeRateType($canalFeeRateType = 0)
    {
        $this->canalFeeRateType = $canalFeeRateType;

        return $this;
    }

    /**
     * @return float
     */
    public function getConsultFeePeriodRate()
    {
        return $this->consultFeePeriodRate;
    }

    /**
     * @param float $consultFeePeriodRate
     * @return ProtoProjectDeal
     */
    public function setConsultFeePeriodRate($consultFeePeriodRate = 0)
    {
        $this->consultFeePeriodRate = $consultFeePeriodRate;
        return $this;
    }
 
    /**
     * @return int
    */
    public function getLoanUserCustomerType()
    {
        return $this->loanUserCustomerType;
    }

    /**
     * @param int $loanUserCustomerType
     * @return ProtoProjectDeal
     */
    public function setLoanUserCustomerType($loanUserCustomerType = 0)
    {
        $this->loanUserCustomerType = $loanUserCustomerType;

        return $this;
    }
    /**
     * @return int
     */
    public function getProductClassType()
    {
        return $this->productClassType;
    }

    /**
     * @param int $productClassType
     * @return ProtoProjectDeal
     */
    public function setProductClassType($productClassType = 0)
    {
        $this->productClassType = $productClassType;
        return $this;
    }

    /**
     * @return int
     */
    public function getClearingType(){
        return $this->clearingType;
    }

    /**
     * @param int $clearingType
     * @return ProtoProjectDeal
     */
    public function setClearingType($clearingType=0) {
        $this->clearingType = $clearingType;
        return $this;
    }

}