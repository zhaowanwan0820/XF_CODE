<?php
namespace core\service\deal;

abstract class State
{
    protected $deal;
    protected $deal_model;

    public abstract function work($sm);

    public function setDeal($deal) {
        $this->deal = $deal;
    }

    public function setDealModel($deal_model) {
        return $this->deal_model = $deal_model;
    }
}

?>
