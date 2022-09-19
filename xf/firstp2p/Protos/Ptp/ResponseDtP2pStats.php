<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 多投查询p2p统计信息service
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangchuanlu
 */
class ResponseDtP2pStats extends ResponseBase
{
    /**
     * 赎回本金
     *
     * @var string
     * @optional
     */
    private $repayPrincipal = '0';

    /**
     * 待赎回本金
     *
     * @var string
     * @optional
     */
    private $norepayPrincipal = '0';

    /**
     * 已付利息
     *
     * @var string
     * @optional
     */
    private $repayInterest = '0';

    /**
     * 待付利息
     *
     * @var string
     * @optional
     */
    private $norepayInterest = '0';

    /**
     * 投资标已收收益
     *
     * @var string
     * @optional
     */
    private $repayEarnings = '0';

    /**
     * 投资标待收收益
     *
     * @var string
     * @optional
     */
    private $norepayEarnings = '0';

    /**
     * 投资标今日还款本金
     *
     * @var string
     * @optional
     */
    private $dayRepayPrincipal = '0';

    /**
     * @return string
     */
    public function getRepayPrincipal()
    {
        return $this->repayPrincipal;
    }

    /**
     * @param string $repayPrincipal
     * @return ResponseDtP2pStats
     */
    public function setRepayPrincipal($repayPrincipal = '0')
    {
        $this->repayPrincipal = $repayPrincipal;

        return $this;
    }
    /**
     * @return string
     */
    public function getNorepayPrincipal()
    {
        return $this->norepayPrincipal;
    }

    /**
     * @param string $norepayPrincipal
     * @return ResponseDtP2pStats
     */
    public function setNorepayPrincipal($norepayPrincipal = '0')
    {
        $this->norepayPrincipal = $norepayPrincipal;

        return $this;
    }
    /**
     * @return string
     */
    public function getRepayInterest()
    {
        return $this->repayInterest;
    }

    /**
     * @param string $repayInterest
     * @return ResponseDtP2pStats
     */
    public function setRepayInterest($repayInterest = '0')
    {
        $this->repayInterest = $repayInterest;

        return $this;
    }
    /**
     * @return string
     */
    public function getNorepayInterest()
    {
        return $this->norepayInterest;
    }

    /**
     * @param string $norepayInterest
     * @return ResponseDtP2pStats
     */
    public function setNorepayInterest($norepayInterest = '0')
    {
        $this->norepayInterest = $norepayInterest;

        return $this;
    }
    /**
     * @return string
     */
    public function getRepayEarnings()
    {
        return $this->repayEarnings;
    }

    /**
     * @param string $repayEarnings
     * @return ResponseDtP2pStats
     */
    public function setRepayEarnings($repayEarnings = '0')
    {
        $this->repayEarnings = $repayEarnings;

        return $this;
    }
    /**
     * @return string
     */
    public function getNorepayEarnings()
    {
        return $this->norepayEarnings;
    }

    /**
     * @param string $norepayEarnings
     * @return ResponseDtP2pStats
     */
    public function setNorepayEarnings($norepayEarnings = '0')
    {
        $this->norepayEarnings = $norepayEarnings;

        return $this;
    }
    /**
     * @return string
     */
    public function getDayRepayPrincipal()
    {
        return $this->dayRepayPrincipal;
    }

    /**
     * @param string $dayRepayPrincipal
     * @return ResponseDtP2pStats
     */
    public function setDayRepayPrincipal($dayRepayPrincipal = '0')
    {
        $this->dayRepayPrincipal = $dayRepayPrincipal;

        return $this;
    }

}