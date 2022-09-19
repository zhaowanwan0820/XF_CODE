<?php
class NewUserTrade extends ItzApi{
    private $_typeTitles = array(
        'recharge' => '充值',
        'cash' => '提现',
        'invest' => '出借',
    	'interest'=>'还息', //回息 pc改版 改版前回息和加息是两个业务类别，改版后加息并入到回息中
    	'capital_expire'=>'还本', // pc改版
    	'capital_debt'=>'债权转让', // pc改版
		'debt_exchange_finish'=>'兑换第三方积分',
        'other' => '其他',
		'dispose_repay'=>'回款',
    );

    private $_rechargeTypeTitiles = array(
        '0' => '线下',
        '1' => '在线',
        '3' => '在线',
        '4' => '在线',
        '5' => '在线',
        '10' => '代销虚拟'
    );

    private $_typeNames = array(
        //充值
        'recharge' => array(
            'recharge'                          =>'充值成功',
        	'expergold_recharge'                =>'体验金充值成功',
        ),
        //提现
        'cash' => array(
            'cash_success'                      =>'提现成功'
        ),
        //投资
        'invest' => array(
            'debt_success'                      =>'认购【金融产品名称】项目',
            'invest_success'                    =>'投资【金融产品名称】项目',
            'purchase_success'                  =>'申购零活计划',
            'continue_frost'                    =>'【金融产品名称】续投冻结',
        ),
    	//回息
    	'interest'=> array(
            'interest_company_ontime'           =>'【金融产品名称】项目付息',
            'interest_company_advance_extra'    =>'【金融产品名称】项目提前还款补偿利息',
            'interest_guarantor_advance_extra'  =>'【金融产品名称】项目提前还款补偿利息',
            'interest_company_advance'          =>'【金融产品名称】项目提前付息',
            'interest_guarantor_advance'        =>'【金融产品名称】项目提前付息',
            'interest_security_ontime'          =>'【金融产品名称】项目风险保障金代偿利息',
            'interest_guarantor_ontime'         =>'【金融产品名称】项目保障机构代偿利息',
            'interest_guarantor_overtime'       =>'项目逾期违约金',//'【金融产品名称】项目逾期利息',
            'interest_guarantor_overtime_extra' =>'项目逾期违约金',//'【金融产品名称】项目逾期罚息',
            'interest_company_overtime'         =>'项目逾期违约金',//'【金融产品名称】项目逾期利息',
            'interest_company_overtime_extra'   =>'项目逾期违约金',//'【金融产品名称】项目逾期罚息',
            'interest_buyback'                  =>'【金融产品名称】项目债权回购支付利息',
            //老的type值，方向在新定义的log_type中为0，所以不显示，故在此作兼容
            'interest_buyback_frost'            =>'【金融产品名称】项目债权回购支付利息',
            'current_interest'                  =>'零活计划付息',
            'interest_returnback'               =>'【金融产品名称】补偿利息',      
            'exit_invest_interest'              =>'【金融产品名称】退出回息',
			'plan_split_interest'               =>'【金融产品名称】拆分收益结算',

		),
		//临时兼容app
		'interest_reward'=>array(
			'interest_reward'					=>'【金融产品名称】项目加息',
		),
        //回本
    	'capital_expire'=> array(
            'capital_company_ontime'            =>'【金融产品名称】项目还本',
            'capital_company_advance'           =>'【金融产品名称】项目提前还本',
            'capital_company_overtime'          =>'【金融产品名称】项目企业逾期还本',
            'capital_guarantor_overtime'        =>'【金融产品名称】项目保障机构代偿本金',
            'capital_guarantor_advance'         =>'【金融产品名称】项目保障机构提前代偿',
            'capital_security_ontime'           =>'【金融产品名称】项目风险保障金代偿本金',

            //老的type值，方向在新定义的log_type中为0，所以不显示，故在此作兼容
            'redeem_success'                    =>'赎回零活计划', 
            'capital_returnback'                =>'【金融产品名称】退还本金',
            'exit_invest_finish'                =>'【金融产品名称】退出回本',
            "continue_cancel"                   =>'【金融产品名称】退出回本',
			'wait_continue'                     =>'【金融产品名称】待续投资金到账',

		),
		//债权
    	'capital_debt'=> array(
    		'capital_buyback'                   =>'【金融产品名称】项目债权回购支付本金',
    		'debt_finish'                       =>'【金融产品名称】项目债权转让回收本金',
    		'debt_exchange_finish'				=>'【金融产品名称】',
    		//老的type值，方向在新定义的log_type中为0，所以不显示，故在此作兼容
    		'capital_buyback_frost'             =>'【金融产品名称】项目债权回购支付本金'
    	),
		//兑换第三方积分
		'debt_exchange_finish' => array(
			'debt_exchange_finish'				=>'【金融产品名称】',
		),
		//展期特殊回息
		'dispose_repay'=>array(
			'dispose_interest'					=>'【金融产品名称】项目回款',
		),
        //
        'other' => array(
            //'realname'                          =>'实名认证',
            'hongbao_success'                   =>'红包被领取',
            'hongbao_recharge'                  =>'领取红包',
            'reversal_success'                  =>'异常资金处理', 
        	'activity_recharge'                 =>'平台现金奖励',
        	'expergold_expire'                  =>'体验金收回成功',
            'reward_expire'                     =>'平台现金回收',
            'reserve_reward'                    =>'预约奖励',
            'novice_reward'                     =>'新手奖励',
            'plat_reward'                       =>'平台奖励',
			'dispose_interest'					=>'【金融产品名称】项目回款',
			'interest_reward'					=>'【金融产品名称】项目加息',
		),

    );
    
    public function getTypeTitles(){
        return $this->_typeTitles;
    }
    public function getTypeNames(){
        return $this->_typeNames;
    }
    public function getRechargeTypeTitiles(){
        return $this->_rechargeTypeTitiles;
    }
    
    //获取accountlog types
    public function logTypes($type){
        switch ($type) {
            case 'recharge':    $logtypes = $this->typeNames['recharge']; break;
            case 'cash':        $logtypes = $this->typeNames['cash']; break;
            case 'invest':      $logtypes = $this->typeNames['invest']; break;
            case 'interest':    $logtypes = $this->typeNames['interest']; break;
            case 'interest_reward':    $logtypes = $this->typeNames['interest_reward']; break;
            case 'capital_expire':    $logtypes = $this->typeNames['capital_expire']; break;
            case 'capital_debt':      $logtypes = $this->typeNames['capital_debt']; break;
            case 'other':       $logtypes = $this->typeNames['other']; break;
            default:
                $logtypes = array();
                foreach($this->typeNames as $typeNames){
                    $logtypes += $typeNames;
                }
                break;
        }
        return $logtypes;
    }

    public function run($userid,$page=1,$type='all',$start_time=0,$end_time=0){
        if(empty($userid)){
            $this->code = '1003';
            return $this;
        }

        $logs = $this->queryLogsFromCache($userid, $page, $type, $start_time, $end_time);
        $resultLogs = array();
        foreach($logs['logs'] as $log){
            $resultLogs[] = array(
                'id'=>$log->id,
                'user_id'=>$log->user_id,
                'logTypeTitle'=>$log->logTypeTitle,
                'logTypeName'=>$log->logTypeName,
                'addtime'=>$log->addtime,
                'money'=>$log->money,
                'direction'=>$log->direction,
                'total'=>$log->total,
                'log_type'=>$log->log_type,
                'transid'=>$log->transid,
                'related_id'=>$log->related_id,
                'borrow_id'=>$log->borrow_id,
                'borrow_type'=>$log->borrow_type,
                'related_type' => $log->related_type // BUG ID ： 100 导出表格 回收本金字段出现repeat 现象
            );
        }
        $this->data = array('pager'=>$logs['pager'],'sumMoney'=>$logs['sumMoney'],'logs'=>$resultLogs);
        return $this;
    }
    
    /**
     * 获取用户相关日志
     * @param type $userid 用户ID
     * @param type $page 页码
     * @param type $type 查询类型 all:全部 recharge:充值 
     * @param type $start_time 查询时间段
     * @param type $end_time 查询结束时间
     * @return type
     */
    public function queryLogs($userid,$page,$type,$start_time,$end_time){
        $dependencySql = 'SELECT MAX(id) FROM '.AccountLog::model()->tableName().' WHERE user_id = '.$userid;
        $this->cache(300,$dependencySql);
        $currentLogs = $this->logTypes($type);

        $criteria = new CDbCriteria;
        $criteria->compare('user_id',$userid);
        $criteria->addCondition(" ((direction = 0 and log_type = 'wait_continue') or direction in (1,2)) ");
        if(!empty($start_time)) $criteria->addCondition('addtime >='.$start_time);
        if(!empty($end_time)) $criteria->addCondition('addtime <='.$end_time);
        //$criteria->addInCondition('log_type', array_keys($currentLogs));
        
        /*
         * 当所有的log_type 都有值后，就是使用上一句替换下边的四句
         */
        $criteria2 = new CDbCriteria;
        $criteria2->addInCondition('log_type', array_keys($currentLogs));
        $criteria2->addInCondition('type', array_keys($currentLogs),'OR');
        $criteria->mergeWith($criteria2);
        
        //获取条数
        $itemCount = AccountLog::model()->count($criteria);
        $sumMoney = '';
        if(in_array($type, array('recharge','cash','invest','interest','capital_expire','capital_debt'))){
            $criteria3 = clone $criteria;
            $criteria3->select = 'sum(money) as money';
            $sumMoney = AccountLog::model()->find($criteria3)->money;
        }

        $pagination = new CPagination($itemCount);
        $pagination->currentPage = $page - 1;
        $pagination->pageSize = $this->pageSize;
        $pagination->applyLimit($criteria);

        $criteria->order = 'addtime DESC, id DESC';
        $accountLogs = AccountLog::model()->findAll($criteria);
        foreach($accountLogs as &$log){
            $pre = '';
            empty($log->log_type) && $log->log_type = $log->type;
            if($log->type == 'recharge') {
                $TransIds = explode("_", $log->transid);
                $related_id = isset($TransIds[1])?$TransIds[1]:0;
                $reInfo = AccountRecharge::model()->findByPk($related_id);                
                $pre = $this->rechargeTypeTitiles[$reInfo->type];

            }
            $logTypeName = $this->getLogTypeName($log);
            $log->logTypeTitle = $logTypeName['logTypeTitle'];
            $log->logTypeName = $pre.$logTypeName['logTypeName'];
        }
        
        $pager = array(
            'page'=>$pagination->currentPage + 1,
            'itemCount'=>$pagination->itemCount,
            'pageCount'=>$pagination->pageCount,
            'pageSize'=>$pagination->pageSize,
        );
        return array('pager'=>$pager,'sumMoney'=>$sumMoney,'logs'=>$accountLogs);
    }
    
    public function getLogTypeName($log){
        $logTypeName = array('logTypeTitle'=>'其他','logTypeName'=>'其他');
        foreach($this->typeNames as $typeTitle=>$typeNames){
            if(array_key_exists($log->log_type,$typeNames)){
                $logTypeName['logTypeTitle'] = $this->typeTitles[$typeTitle];
                $logTypeName['logTypeName'] = $typeNames[$log->log_type];
            }
        }
        if(!empty($log->borrow_type)){
            $borrowTypeName = Yii::app()->c->linkconfig['borrow_type_online_usertrade'][$log->borrow_type];
            if(!empty($borrowTypeName)) {
                $logTypeName = str_replace('【金融产品名称】',$borrowTypeName,$logTypeName);
            }
        }
        return $logTypeName;
    }
    
    
    public function detail($logid){
        if(empty($logid)){
            $this->code = '1003';
            return $this;
        }
        $this->data = $this->queryLogDetailFromCache($logid);
        return $this;
    }
    
    public function queryLogDetail($logid){
        $this->cache(300);
        $logInfo = AccountLog::model()->findByPk($logid);
        if(empty($logInfo)){
            $this->code = '3002';
            return $this;
        }
        
        if(empty($logInfo->related_id)){
            $transid_array = explode('_', $logInfo->transid);
            $logInfo->related_type = $transid_array[0];
            $logInfo->related_id   = $transid_array[1];
        }
        
        empty($logInfo->log_type) && $logInfo->log_type = $logInfo->type;
        
        switch($logInfo->log_type){
            case 'recharge':
                /*$detail = current(BaseCrudService::getInstance()->get('AccountRecharge','',0,1,'',array('id'=>$logInfo->related_id),null,array('paymentInfo')));
                break;*/
            case 'expergold_recharge':
                //体验金充值
               	$detail = current(BaseCrudService::getInstance()->get('AccountRecharge','',0,1,'',array('id'=>$logInfo->related_id),null,array('paymentInfo')));
                break;
            case 'cash_success':
                $detail = current(BaseCrudService::getInstance()->get('AccountCash','',0,1,'',array('id'=>$logInfo->related_id)));
                break;
            case 'hongbao_success':
                $detail = current(BaseCrudService::getInstance()->get('HongbaoRecord','',0,1,'',array('id'=>$logInfo->related_id),null,array('hongbaoInfo','userInfo')));
                break;
            case 'hongbao_recharge':
                $detail = current(BaseCrudService::getInstance()->get('HongbaoRecord','',0,1,'',array('id'=>$logInfo->related_id),null,array('hongbaoInfo','hongbaoUserInfo')));
                break;
            case 'activity_recharge':
                $detail = $logInfo->attributes;     //活动充值奖励, 返回account_log数据, 前端显示的文案从remark字段取
                break;
            default :
                $borrow = BorrowService::getInstance()->getBorrowFromCache($logInfo->borrow_id);
                $detail['borrow'] = $borrow['borrow'];
                if($logInfo->related_type == 'collection'){
                    $bcInfo = BorrowCollection::model()->with('tenderInfo')->findByPk($logInfo->related_id);
                    if (in_array($bcInfo->tenderInfo->coupon_type, array(3,4))) {
                        $detail['reward_apr'] = $bcInfo->tenderInfo->coupon_value;
                    }
                    $detail['invest_time'] = $bcInfo->tenderInfo->addtime;
                }elseif($logInfo->related_type == 'debttender'){
                    $debttenderInfo = DebtTender::model()->findByAttributes(array('new_tender_id'=>$logInfo->related_id));
                    $detail['invest_time'] = $logInfo->addtime;
                    $detail['debt_id'] = $debttenderInfo->debt_id;
                }elseif($logInfo->related_type == 'debt'){
                    $debtInfo = Debt::model()->with('tenderInfo')->findByPk($logInfo->related_id);
                    $detail['invest_time'] = $debtInfo->tenderInfo->addtime;
                }else{
                    $detail['invest_time'] = $logInfo->addtime;
                }
                break;
        }
        return array('logInfo'=>$logInfo->attributes,'detail'=>$detail);
    }

    public function getUserCanViewEarlestTime($userid,$type){
        $currentLogs = $this->logTypes($type);

        $criteria = new CDbCriteria;
        $criteria->compare('user_id',$userid);
        $criteria->addInCondition('direction',array(1,2));

        $criteria2 = new CDbCriteria;
        $criteria2->addInCondition('log_type', array_keys($currentLogs));
        $criteria2->addInCondition('type', array_keys($currentLogs),'OR');
        $criteria->mergeWith($criteria2);

        $criteria->order = ' addtime asc';
        $addtime = AccountLog::model()->find($criteria)->addtime;

        return $addtime;
    }

}
