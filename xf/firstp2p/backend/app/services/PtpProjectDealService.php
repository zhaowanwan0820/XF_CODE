<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\ProtoProjectDeal;
use NCFGroup\Protos\Ptp\ProtoOpenDelP2pDealProject;
use NCFGroup\Ptp\daos\ProjectDealDAO;
use core\service\ProductManagementService;
use core\service\PlatformManagementService;
use libs\utils\Alarm;
use libs\utils\Logger;
use core\dao\DealModel;
use NCFGroup\Protos\Life\RequestCommon as LifeRequestCommon;

/**
 * DealService
 * 标相关service
 * @uses ServiceBase
 * @package default
 */
class PtpProjectDealService extends ServiceBase {

    const DEAL_IS_EXIST =   -7;//标的已经存在
    const DEAL_IS_HANDLE =   -8;//业务处理中
    const DEAL_IS_FULL_MONEY =   -9;//用户在途借款本金不得大于20万元

    /**
     * 增加ProjectDeal记录
     * @param ProtoProjectDeal $request
     * @return ProtoProjectDeal
     */
    public function addProjectDeal(ProtoProjectDeal $request)
    {
        $response = new ResponseBase();
        $nameKey = md5('addProjectDeal-name-'.$request->getName());
        /*信贷上标检查审批单号是否存在*/
        if($request->getIsCredit() == 1){
            $uidRes = 0;
            //读取缓存上标成功的项目对应的approveNumber 和 uid
            try {
                $redis = \SiteApp::init()->dataCache->getRedisInstance();
                if (empty($redis)) {
                    throw new \Exception("resdis make failed");
                }
                $uidDealRes = $redis->get('addProjectDeal-SUCCESS-'.$request->getApproveNumber());
                $dealInfoRes = empty($uidDealRes) ? array() : explode(',',$uidDealRes);
                $uidRes = $dealInfoRes['0'];//uid
                $dealIdRes = $dealInfoRes['1'];//dealID
                if (empty($dealInfoRes)) {
                    //缓存上标成功的项目对应的approveNumber 和 uid,dealId=0
                    $uidRes = $redis->setex('addProjectDeal-SUCCESS-'.$request->getApproveNumber(),'86400',$request->getUserId().','.'0');
                    if (!$uidRes) {
                        Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'add project dealinfo to redis faild',$request->getApproveNumber())));
                        Alarm::push('SetProjectDealInfo', '一键上标成功记入redis失败',  'approve_number:' . $request->getApproveNumber());
                    }
                } elseif($uidRes == $request->getUserId() && $dealIdRes == 0) {
                    $response->resCode = self::DEAL_IS_HANDLE;//如果缓存的标dealId为0，则返回处理中
                    return $response;
                }


            } catch (\Exception $e) {
                Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'read project dealinfo to redis faild',$request->getApproveNumber().'|'.$e->getMessage())));
                Alarm::push('SetProjectDealInfo', '一键上标成功读取redis失败',  'approve_number:' . $request->getApproveNumber().$e->getMessage());
                //如果读取redis失败，则尝试从主库中读取
                $dealInfoRes = ProjectDealDAO::getDealByApproveNum($request->getApproveNumber(),false);
                if ($dealInfoRes) {
                    $time = time() - 24*3600;//当前时间往前推24小时
                    $uidRes = ($dealInfoRes->createTime > $time) ? $dealInfoRes->userId : 0;
                    $dealIdRes = $dealInfoRes->id;
                }
            }
            //如果没有缓存查从库
            if (empty($dealInfoRes)) {
                $dealInfoRes = ProjectDealDAO::getDealByApproveNum($request->getApproveNumber());
                //如果从库有对应的信息并且用户uid和approveNumber都相同则认为此标已上成功
                $uidRes = $dealInfoRes->userId;
                $dealIdRes = $dealInfoRes->id;
            }

            //缓存结果存在或者从库查询存在都认为已上标成功
            if ($uidRes == $request->getUserId()) {
                $response->resCode = self::DEAL_IS_EXIST;
                $response->dealId  = $dealIdRes;
                Logger::info(implode('|',array(__CLASS__,__FUNCTION__,'dealproject is exist',$request->getApproveNumber(),'dealid:'.$dealIdRes)));
                Alarm::push('SetProjectDealInfo', '项目标的已经存在',  'approve_number:' . $request->getApproveNumber());
                return $response;
            }

            if($dealInfoRes){
                $response->resCode = -1;
                return $response;
            }
            $nameCount = $redis->incr($nameKey);
            $redis->expire($nameKey, 300);//过期时间设置为5分钟
            if($nameCount > 1 || ProjectDealDAO::getByNameProject($request->getName())){
                $redis->del('addProjectDeal-SUCCESS-'.$request->getApproveNumber());//失败删除key
                $response->resCode = -2;
                return $response;
            }
        }


        //检查机构、产品限额
        //平台限额检查
        $checkAdvisory = $this->checkAdvisoryIsWarning($request->getAdvisoryId(),$request->getBorrowAmount());
        if ($checkAdvisory !== false) {
            if ($checkAdvisory['errno'] == 1) {
                $response->resCode = -3;
                $redis->del('addProjectDeal-SUCCESS-'.$request->getApproveNumber());//失败删除key
                Alarm::push('PlateFormWarning', '机构限额不足', 'advisoryid:'.$request->getAdvisoryId().'approvenum:'.$request->getApproveNumber().$checkAdvisory['errmsg']);
                return $response;
            }
            if ($checkAdvisory['errno'] == 2) {
                $response->resCode = -5;
                $redis->del('addProjectDeal-SUCCESS-'.$request->getApproveNumber());//失败删除key
                Alarm::push('PlateFormWarning', '不在机构有效期内', 'advisoryid:'.$request->getAdvisoryId().'approvenum:'.$request->getApproveNumber().$checkAdvisory['errmsg']);
                return $response;
            }
            if ($checkAdvisory['errno'] === 0) {
                $request->setAdvisoryName($checkAdvisory['advisory_name']);
                $request->setAdvisoryWarningLevel($checkAdvisory['level']);
                $request->setAdvisoryWarningUseMoney($checkAdvisory['use_money']);
            }
        }
        //产品限额检查
        $checkProduct = $this->checkProductIsWarning($request->getProductName(), $request->getBorrowAmount());
        if ($checkProduct !== false) {
            if ($checkProduct['errno'] == 1) {
                $response->resCode = -4;
                $redis->del('addProjectDeal-SUCCESS-'.$request->getApproveNumber());//失败删除key
                Alarm::push('PlateFormWarning', '产品限额不足', 'product:'.$request->getProductName().'approvenum:'.$request->getApproveNumber().$checkProduct['errmsg']);
                return $response;
            }
            if ($checkProduct['errno'] == 2) {
                $response->resCode = -6;
                $redis->del('addProjectDeal-SUCCESS-'.$request->getApproveNumber());//失败删除key
                Alarm::push('PlateFormWarning', '不在产品有效期内', 'product:'.$request->getProductName().'approvenum:'.$request->getApproveNumber().$checkProduct['errmsg']);
                return $response;
            }
            if ($checkProduct['errno'] === 0) {
                $request->setProductWarningLevel($checkProduct['level']);
                $request->setProductWarningUseMoney($checkProduct['use_money']);
            }
        }

        $ret = ProjectDealDAO::addProjectDealInfo($request);
        $response = new ResponseBase();
        if ($ret === false){
            $response->resCode = RPCErrorCode::FAILD;
            $redis->del('addProjectDeal-SUCCESS-'.$request->getApproveNumber());//失败删除key
            $redis->del($nameKey);//删除缓存的项目名称
        }else{
            $this->emailSmsWarning($request->getAdvisoryWarningLevel(), $request->getAdvisoryName(),$request->getAdvisoryId(),1);
            $this->emailSmsWarning($request->getProductWarningLevel(), $request->getProductName(),$checkProduct['product_id'],2);
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->dealId  = $ret['deal_id'];
            $response->projectId = $ret['project_id'];
            //缓存上标成功的项目对应的approveNumber 和 uid,dealId
            $uidRes = $redis->setex('addProjectDeal-SUCCESS-'.$request->getApproveNumber(),'86400',$request->getUserId().','.$ret['deal_id']);
            if (!$uidRes) {
                Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'add project dealinfo to redis faild',$request->getApproveNumber())));
                Alarm::push('SetProjectDealInfo', '一键上标成功记入redis失败',  'approve_number:' . $request->getApproveNumber());
            }
        }

        return $response;
    }

    /**
     * @删除从开放平台传过来的标的和项目
     * @param ProtoOpenDelP2pDealProject $request
     * @return ResponseBase
     */
    public function delDealProject(ProtoOpenDelP2pDealProject $request)
    {
        $dealId    = $request->getDealId();
        $projectId = $request->getProjectId();

        $response = new ResponseBase();

        $ret = ProjectDealDAO::delDealProject($dealId, $projectId);
        if(!$ret){
            $response->resCode = RPCErrorCode::FAILD;
        }else{
            $response->resCode = RPCErrorCode::SUCCESS;
        }

        return $response;
    }
    /**
     * 上标接口检查交易平台咨询机构金额限制
     * @param int $advisoryId:咨询机构ID,$borrowAmount：借款金额
     * @return bool
     */
    public function checkAdvisoryIsWarning($advisoryId,$borrowAmount) {
        $service = new PlatformManagementService();
        return $service->getPlatManagement($advisoryId,$borrowAmount);
    }

    /**
     * 上标接口检查交易平台产品金额限制
     * @param int $productName: 产品名称,$borrowAmount ： 借款金额
     * @return bool
     */
    public function checkProductIsWarning($productName,$borrowAmount) {
        $service = new ProductManagementService();
        return $service->getProductManagement($productName,$borrowAmount);
    }

    /**
     * 邮件或者短信告警0：不预警，1：后台红字提示，2：邮件通知 ，3：短信提示
     * @param int $level $name $id:机构或产品对应的id,$type 1:咨询机构   2：产品名称
     */
    public function emailSmsWarning($level,$name,$id,$type=1) {
        require_once(APP_ROOT_PATH . 'system/libs/msgcenter.php');
        $cache = \SiteApp::init()->cache;
        $expire_time = 24*3600;
        $msgcenter = new \Msgcenter();
        //邮件
        if ($level == 2) {
            $key = $type != 1 ? 'trad_product_waring_key_email_'.$id : 'trad_platform_waring_key_email_'.$id;
            $title ='平台用款限额预警';
            if ($cache->get($key) != 1) {
                $content = $name.'可用用款限额低于10%，请及时处理';
                $msgcenter->setMsg('1040406303@qq.com', '', $content, false,$title);
                $msgcenter->setMsg('guolei@ucfgroup.com', '', $content, false,$title);
                $msgcenter->setMsg('zangmeijie@ucfgroup.com', '', $content, false,$title);
                $msgcenter->setMsg('yuxiaolin@ucfgroup.com', '', $content, false,$title);
                $result = $msgcenter->save();
                $cache->set($key,1,$expire_time);
                if ($result) {
                    \libs\utils\Logger::info('success email'.'|'.$id.'|'.$result);
                }
            }
        }
        //短信
        if ($level ==3) {
            $key = $type != 1 ? 'trad_product_waring_key_sms_'.$id : 'trad_platform_waring_key_sms_'.$id;
            $content = array('0'=>$name.'可用用款限额低于5%，请及时处理');
            if ($cache->get($key) != 1) {
                \libs\sms\SmsServer::instance()->send('15232738474', 'TPL_SMS_ZZ_ALARM_NOTIFY', $content);
                \libs\sms\SmsServer::instance()->send('17326832726', 'TPL_SMS_ZZ_ALARM_NOTIFY', $content);
                \libs\sms\SmsServer::instance()->send('15166055799', 'TPL_SMS_ZZ_ALARM_NOTIFY', $content);
                \libs\sms\SmsServer::instance()->send('13520349919', 'TPL_SMS_ZZ_ALARM_NOTIFY', $content);
                $cache->set($key,1,$expire_time);
                if ($result) {
                    \libs\utils\Logger::info('success sms'.'|'.$id.'|'.$result);
                }
            }
        }
    }

    /**
     * 更新ProjectDeal记录
     * @param ProtoProjectDeal $request
     * @return ProtoProjectDeal
     */
    public function updateProjectDeal(LifeRequestCommon $request)
    {
        $approveNumber = $request->getVar('approveNumber');
        $list = $request->getVar('project_info');
        $response = new ResponseBase();
        $dealInfoRes = ProjectDealDAO::getDealByApproveNum($approveNumber);
        $deal_id= $dealInfoRes->id;
        $project_id = $dealInfoRes->projectId;
        //检查机构、产品限额
        if(!empty($list["borrow_amount"])){
             $checkAdvisory = $this->checkAdvisoryIsWarning(($dealInfoRes->advisoryId),$list['borrow_amount']);
          if ($checkAdvisory !== false) {
            if ($checkAdvisory['errno'] == 1) {
                Alarm::push('PlateFormWarning', '
                ', 'advisoryid:'.$dealInfoRes->advisoryId.'approvenum:'.$approveNumber.$checkAdvisory['errmsg']);
                return false ;
            }
            if ($checkAdvisory['errno'] == 2) {
                Alarm::push('PlateFormWarning', '不在机构有效期内', 'advisoryid:'.$dealInfoRes->advisoryId.'approvenum:'.$approveNumber.$checkAdvisory['errmsg']);
                return false;
            }
          }
            //产品限额检查
            $checkProduct = $this->checkProductIsWarning($dealInfoRes->productName,$list['borrow_amount']);
             if ($checkProduct !== false) {
                 if ($checkProduct['errno'] == 1) {
                     $response->resCode = -4;
                     Alarm::push('PlateFormWarning', '产品限额不足', 'product:'.$dealInfoRes->ProductName.'approvenum:'.$approveNumber.$checkProduct['errmsg']);
                     return $response;
                 }
                 if ($checkProduct['errno'] == 2) {
                     $response->resCode = -6;
                     Alarm::push('PlateFormWarning', '不在产品有效期内', 'product:'.$dealInfoRes->ProductName.'approvenum:'.$approveNumber.$checkProduct['errmsg']);
                     return $response;
                 }
             }
            if($dealInfoRes->dealType == 2){//交易所
                $min_loan_money  = bcmul(ceil(bcdiv($list["borrow_amount"], 200*1000,5)),1000,2);
                $list['min_loan_money'] = $min_loan_money;
                if($dealInfoRes->isFloatMinLoan == DealModel::DEAL_FLOAT_MIN_LOAN_MONEY_YES){
                    $jys_min_money = (new DealModel())->getJYSMinLoanMony($dealInfoRes->jysId);
                    $list['min_loan_money'] =$min_loan_money > $jys_min_money ? $min_loan_money :$jys_min_money;
                }
            }
        }
        if(isset($list['loan_fee_rate_type']) && !isset($list['repay_period'])){
                $list['repay_period'] =  $dealInfoRes->repayPeriod;
        }
        $ret = ProjectDealDAO::updateProjectDealInfo($list,$project_id,$deal_id,$dealInfoRes->dealType);
        if ($ret === false){
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }
        $response->data = $ret;
        return $response;
    }

}
