<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\ProtoWebUnionUser;
use NCFGroup\Protos\Ptp\ProtoWebUnionDeal;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RequestUser;
use libs\utils\Logger;
use core\dao\DealLoanTypeModel;
use core\service\DealService;
use core\service\DealLoanTypeService;
use core\service\DealLoadService;
use core\dao\DealExtModel;

class PtpWebUnionDealService extends ServiceBase
{
    private $_LogTags = 'p2peye';
    private $_TypeList   = array('个人贷'=>3, '车贷'=>1, '房贷'=>2);
    private $_DealStatusList = array(4=>2, 5=>3, 3=>5, 1=>1);
    private $_LoanTypeList   = array('1,2,8'=>1, '4,6'=>2, '3,5'=>3, '9,10'=>4);

    /**
     * @获取标的详情
     * @param ProtoWebUnionUser $request
     * @return ResponseBase $response
     */
    public function getDealInfo(ProtoWebUnionDeal $request)
    {
        $response = new ResponseBase();

        $dealId = $request->getDealId();
        $dealInfo = (new DealService())->getDeal($dealId);
        if(!$dealInfo){
            $response->ret = false;
            return $response;
        }

        $dealTypeName = (new DealLoanTypeService())->getDealLoanType($dealInfo->_row['type_id']);
        $response->ret = $dealInfo->_row;
        $response->ret['type_id']     = in_array($dealTypeName, array_keys($this->_TypeList)) ? $this->_TypeList[$dealTypeName] : 8;
        $response->ret['dealStatus'] = in_array($response->ret['deal_status'], array_keys($this->_DealStatusList)) ? $this->_DealStatusList[$response->ret['deal_status']] : 1;
        $loantype = 1;
        foreach($this->_LoanTypeList as $key => $val){
            $arrItem = explode(',', $key);
            if(in_array($response->ret['loantype'], $arrItem)){
                $loantype = $val;
                break;
            }
        }

        $response->ret['pay_way'] = $loantype;
        $dealExtInfo = DealExtModel::instance()->getInfoByDeal($dealId, false);
        $response->ret['deal_ext_info'] = $dealExtInfo->_row;

        $catInfo = $response->ret['cate_info'];
        $response->ret['cate_info'] = $catInfo->_row;

        $typeInfo = $response->ret['type_info'];
        $response->ret['type_info'] = $typeInfo->_row;

        $agencyInfo = $response->ret['agency_info'];
        $response->ret['agency_info'] = $agencyInfo->_row;

        $advisoryInfo = $response->ret['advisory_info'];
        $response->ret['advisory_info'] = $advisoryInfo->_row;

         return $response;
    }

    /**
     * @标的状态转换
     * @param ProtoWebUnionDeal $request
     * @return int
     */
    public function getDealStatus(ProtoWebUnionDeal $request)
    {
        $status = $request->getDealStatus();
        $response = new ResponseBase();
        $response->ret = in_array($status, array_keys($this->_DealStatusList)) ? $this->_DealStatusList[$status] : 1;
        return $response;
    }

    /**
     * @获取绑定用户交易数据
     * @param ProtoWebUnionUser $request
     * @return array
     */
    public function getDealLoad(ProtoWebUnionUser $request)
    {
        $response = new ResponseBase();

        $db = getDI()->get('firstp2p');
        $eTime = $request->getSendTime();
        $sTime = $eTime-86400;
        $userIds = $request->getUserIds();
        $runSql = "SELECT A.id,A.name,A.borrow_amount,A.rate,A.repay_time,A.loantype,A.start_time,A.type_id,A.deal_status,A.deal_crowd,B.id AS order_id,B.money,B.user_id,B.create_time FROM firstp2p_deal AS A Inner Join firstp2p_deal_load AS B On A.id=B.deal_id WHERE B.create_time>=".$sTime." And B.create_time<=".$eTime." and B.user_id In(".$userIds.") And deal_status=4";
        $ret = $db->fetchAll($runSql);
        if(empty($ret)){
            $response->ret = false;
            return $response;
        }

        $dealTypeObj = new DealLoanTypeService();
        foreach($ret as $key=>$val){
            $dealTypeName = $dealTypeObj->getDealLoanType($val['type_id']);
            $ret[$key]['type_id']    = in_array($dealTypeName, array_keys($this->_TypeList)) ? $this->_TypeList[$dealTypeName] : 8;
            $ret[$key]['dealStatus'] = in_array($val['deal_status'], array_keys($this->_DealStatusList)) ? $this->_DealStatusList[$val['deal_status']] : 0;
        }

        $response->ret = $ret;
        return $response;
    }

    /**
     * @获取投资详情
     * @param ProtoWebUnionDeal $request
     * @return ResponseBase $response
     */
    public function getLoadInfo(ProtoWebUnionDeal $request)
    {
        $response = new ResponseBase();

        $loadId = $request->getLoadId();
        $res = (new DealLoadService())->findLoadInfo($loadId);
        $res = $res->_row;
        if(empty($res)){
            $response->ret = false;
        }
        $response->ret = $res;
        return $response;
    }
}
