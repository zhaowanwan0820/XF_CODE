<?php
namespace core\service\deal;

use core\dao\deal\DealModel;
use core\service\BaseService;


class DealStateService extends BaseService
{
    public static function work(DealModel $deal){
        $state = ucfirst(DealModel::$DEAL_STATUS[$deal->deal_status]) . 'State';
        return  (new $state())->work($deal);
    }
}