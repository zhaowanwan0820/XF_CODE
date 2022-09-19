<?php
namespace core\service\deal;

use core\dao\DealModel;
use core\service\deal\FailState;

class StateManager
{
    protected $deal;
    protected $deal_model;
    protected $deal_params_conf_id; // 标的参数配置方案内容

    public function __construct() {
        $this->deal_model = new DealModel();
    }
    /**
     * setDeal
     *
     * @param array $deal
     * @access public
     * @return void
     */
    public function setDeal($deal) {
        $this->deal = $deal;
    }

    public function getDeal() {
        return $this->deal;
    }

    public function getDealModel() {
        return $this->deal_model;
    }

    public function setDealParamsConfId($deal_params_conf_id) {
        $this->deal_params_conf_id = intval($deal_params_conf_id);
    }

    public function getDealParamsConfId() {
        return $this->deal_params_conf_id;
    }

    public function work() {
        //  @todo 找到规律 然后用配置 直接得到类名 就不用写这些if else了
        if ($this->deal['deal_status'] == DealModel::$DEAL_STATUS['failed'] && $this->deal['is_doing'] == 1) {
            $state = new FailState();
        } elseif ($this->deal['deal_status'] == DealModel::$DEAL_STATUS['full']) {
            $state = new FullState();
        } elseif ($this->deal['deal_status'] == DealModel::$DEAL_STATUS['progressing']) {
            $state = new ProcessingState();
        } elseif ($this->deal['deal_status'] == DealModel::$DEAL_STATUS['waiting']) {
            $state = new WaitingState();
        }

        if (!empty($state)) {
            $rs = $state->work($this);
        } else {
            echo "state is empty \n";
            return false;
        }
        return $rs;
    }
}

?>
