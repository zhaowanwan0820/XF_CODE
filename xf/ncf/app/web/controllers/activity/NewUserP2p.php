<?php
namespace web\controllers\activity;

/**
 * 新手推荐专区主页
 * @author: gengkuan@ucfgroup.com
 */
use libs\web\Url;
use libs\utils\Aes;
use web\controllers\BaseAction;
use core\service\deal\DealService;
use core\enum\DealEnum;

class NewUserP2p extends BaseAction

{

    public function invoke()
    {

        $siteId = $this->getSiteId();
        $option = array();
        $option['deal_type'] = DealEnum::DEAL_TYPE_ALL_P2P; //标的类型(p2p)
        $deal_service = new DealService();
        $option['isHitSupervision'] = true;
        $deals = $deal_service -> getDealsList(null,1,3,false,$siteId,$option);
        $newUserDealsList = $deals['list']['list'];
        $dealsList = array();
        foreach ($newUserDealsList as $key => $value){
            $dealsList[$key]['id'] = $value['id'];
            $dealsList[$key]['name'] = $value['name'];
            $dealsList[$key]['repayTime'] = $value['repay_time'];
            $dealsList[$key]['loanType'] = $value['loantype'];
            $dealsList[$key]['rate'] = number_format($value['rate'],2);
            $dealsList[$key]['url'] = Url::gene("d", "", Aes::encryptForDeal($value['id']), true);
            $dealsList[$key]['deal_type'] = $value['deal_type'];
        }
        $this->tpl->assign('newUserDealsList', $dealsList); //可投资列表
        $this->template = 'web/views/activity/new_user_page_p2p.html';

    }

}