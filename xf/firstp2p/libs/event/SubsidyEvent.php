<?php
namespace libs\event;
use core\service\DealLoadService;
use core\dao\DealLoadModel;

class SubsidyEvent implements AsyncEvent
{
    public $dealId = null;

    public function __construct($dealId)
    {
        $this->dealId = $dealId;
    }

    public function execute()
    {
        $successFul = true;
        $dealLoanList = DealLoadModel::instance()->getDealLoanList($this->dealId);
        $dealLoadService = new DealLoadService();
        foreach($dealLoanList as $dealLoan) {
            if (!$dealLoadService->dealSubsidyToUser($dealLoan->id)) {
                $successFul = false;
            }
        }

        return $successFul;
    }
}
