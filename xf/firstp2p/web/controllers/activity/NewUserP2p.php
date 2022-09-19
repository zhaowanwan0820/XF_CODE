<?php
namespace web\controllers\activity;
/**
 * 新手推荐专区主页
 * @author: wangxiangshuo@ucfgroup.com
 */
use libs\web\Url;
use libs\utils\Aes;
use web\controllers\BaseAction;
use libs\web\Form;
use core\dao\DealLoanTypeModel;
use core\dao\DealModel;
use core\service\PassportService;
use libs\utils\Logger;

class NewUserP2p extends BaseAction
{

    public function invoke()
    {

        $siteId = $this->getSiteId();
        $option = array();

        $option['deal_type'] = DealModel::DEAL_TYPE_ALL_P2P; //标的类型(p2p)

        if((int)app_conf('SUPERVISION_SWITCH') === 1){
            $option['isHitSupervision'] = true;
            $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, 1,3,false,$siteId,$option)), 30, false, false);
        }else{
            if($this->isSvOpen){
                $option['isHitSupervision'] = true;
                $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, 1,3,false,$siteId,$option)), 30, false, false);
            }else{
                $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, 1,3,false,$siteId,$option)), 30, false, false);
            }
        }

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

        if ($this->is_firstp2p) {

            $this->template = 'web/views/v3/activity/new_user_page_p2p.html';
        }

    }

}