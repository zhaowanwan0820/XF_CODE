<?php

/**
 * 交易所数据导入
 * Class HandleJRGCData.
 */
class HandleJYSData extends BaseHandleOfflineData
{
    //需要必填的字段
    public $platform_id = 5;
    public $needCheckField = [
        'idno', 'mobile_phone', 'user_name',
    ];
    public $productType2Name = [
        0 => '',
    ];

    public function handle($import)
    {
        try {
            $this->checkParam($import)->handleUserInfo()->handleUserBankInfo()->handleBorrowerInfo()->handleDealInfo()->handelTenderInfo()->handelContractInfo()->handleCollection();
        }catch (Exception $e){
            throw  $e;
        }
        return $this;
    }

    public function handleCollection()
    {
        $res = OfflineDealLoanRepay::model()->findByAttributes(['loan_user_id' => $this->dealLoadInfo->user_id, 'deal_loan_id' => $this->dealLoadInfo->id, 'platform_id' => $this->platform_id]);
        if (!empty($res)) {
            throw new Exception('该笔还款计划已经存在: tender_id:'.$this->dealInfo->id);
        }

        //本机和利息
        $dealLoanRepay = new OfflineDealLoanRepay();
        $dealLoanRepay->deal_loan_id = $this->dealLoadInfo->id;
        $dealLoanRepay->loan_user_id = $this->dealLoadInfo->user_id;
        $dealLoanRepay->deal_id = $this->dealLoadInfo->deal_id;
        $dealLoanRepay->borrow_user_id = $this->dealInfo->user_id; //借款人id
        $dealLoanRepay->deal_repay_id = 0; //项目的还款计划表
        $dealLoanRepay->money = $this->import['wait_capital'];
        $dealLoanRepay->time = $this->dealInfo->repayment_date - 3600 * 8;;
        $dealLoanRepay->real_time = 0;
        $dealLoanRepay->status = 0;
        $dealLoanRepay->type = 1;
        $dealLoanRepay->is_zdx = 0;
        $dealLoanRepay->platform_id = $this->platform_id; //平台id;
        if (false == $dealLoanRepay->save()) {
            throw new Exception('创建新的还款计划失败 '.print_r($dealLoanRepay->getAttributes(), true));
        }
    }
    //处理用户信息
    public function handleUserInfo()
    {
        $_id_no = GibberishAESUtil::enc(trim($this->import['idno']), Yii::app()->c->idno_key);
        $_mobile = GibberishAESUtil::enc(trim($this->import['mobile_phone']), Yii::app()->c->idno_key);
        $user_info_key = $_mobile;
        $params = ['is_effect' => 1, 'mobile' => $_mobile,'user_type'=>0];
        $userInfo = Firstp2pUser::model()->findByAttributes($params);

        if (!$userInfo) {
            $must = ['user_name', 'user_pwd', 'passport_id', 'create_time', 'update_time', 'login_ip', 'group_id', 'is_effect', 'is_delete', 'passport_no', 'military_id', 'h_idno', 't_idno', 'm_idno', 'other_idno', 'idcardpassed', 'idcardpassed_time', 'real_name', 'mobile', 'mobilepassed', 'score', 'money', 'lock_money', 'code', 'login_time', 'focus_count', 'focused_count', 'n_province_id', 'n_city_id', 'province_id', 'city_id', 'graduation', 'graduatedyear', 'university', 'edu_validcode', 'marriage', 'haschild', 'hashouse', 'houseloan', 'hascar', 'carloan', 'car_brand', 'car_year', 'car_number', 'address', 'phone', 'point', 'creditpassed', 'creditpassed_time', 'workpassed', 'workpassed_time', 'incomepassed', 'incomepassed_time', 'housepassed', 'housepassed_time', 'carpassed', 'carpassed_time', 'marrypassed', 'marrypassed_time', 'edupassed', 'edupassed_time', 'skillpassed', 'skillpassed_time', 'videopassed', 'videopassed_time', 'mobiletruepassed', 'mobiletruepassed_time', 'residencepassed', 'residencepassed_time'];
            $userInfo = new Firstp2pUser();
            foreach ($must as $item) {
                $userInfo->$item = 0;
            }
            $userInfo->user_name = 'C'.substr(time(),2,8).rand(10000, 99999);
            $userInfo->real_name = $this->import['user_name'];
            $userInfo->mobile = $_mobile;
            $userInfo->idno = $_id_no;
            $userInfo->sex = FunctionUtil::getSexByCardID($this->import['idno']);
            $birthDay = FunctionUtil::getBirthDay($this->import['idno']);
            $userInfo->byear = $birthDay['year'];
            $userInfo->bmonth = $birthDay['month'];
            $userInfo->bday = $birthDay['day'];
            $userInfo->id_type = $this->import['idno_type'];
            $userInfo->is_effect = 1;
            $userInfo->is_online = 1;
            $userInfo->user_type = 0;
            $userInfo->create_time = time();
            $userInfo->update_time = time();
            if (false === $userInfo->save()) {
                throw new Exception('创建新用户时失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($userInfo->getErrors(), true));
            }
        }else{
            if($userInfo->is_online == 0){
                $res = Firstp2pUser::model()->updateByPk($userInfo->id,['is_online' => 1]);
                if($res===false){
                    throw new Exception('更新用户is_online失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($userInfo->getErrors(), true));
                }
            }
        }
        //用户平台表关联一下
        $userPlatform = OfflineUserPlatform::model()->findByAttributes(['user_id' => $userInfo->id, 'platform_id' => $this->platform_id]);
        if (!$userPlatform) {
            $userPlatform = new OfflineUserPlatform();
            $userPlatform->platform_id = $this->platform_id;
            $userPlatform->user_id = $userInfo->id;
            $userPlatform->real_name = $this->import['user_name'];
            $userPlatform->phone = $_mobile;
            $userPlatform->id_type = $this->import['idno_type'];
            $userPlatform->idno = $_id_no;
            $userPlatform->old_user_id = $this->import['old_user_id'];
            if (false === $userPlatform->save()) {
                throw new Exception('创建平台用户时失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($userPlatform->getErrors(), true));
            }
        }
        if(empty($userInfo->idno) || empty($userInfo->real_name)){
            $userInfo->real_name = $this->import['user_name'];
            $userInfo->idno = $_id_no;
            $userInfo->id_type = $this->import['idno_type'];
            $userInfo->sex = FunctionUtil::getSexByCardID($this->import['idno']);
            $birthDay = FunctionUtil::getBirthDay($this->import['idno']);
            $userInfo->byear = $birthDay['year'];
            $userInfo->bmonth = $birthDay['month'];
            $userInfo->bday = $birthDay['day'];
            if (false === $userInfo->save()) {
                throw new Exception('更新用户信息失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($userInfo->getErrors(), true));
            }
        }
        if ($userInfo->idno !== $_id_no) {
            throw new Exception('该手机号:'.$this->import['mobile_phone'].' 证件号不一致 已经被用户id:'.$userInfo->id.' 证件号为:['.GibberishAESUtil::dec($userInfo->idno , Yii::app()->c->idno_key).']使用');
        }

        if ($userInfo->real_name !== $this->import['user_name']) {
            throw new Exception('该手机号:'.$this->import['mobile_phone'].' 姓名不一致 已经被用户id:'.$userInfo->id.' 姓名为:['.$userInfo->real_name.']使用');
        }

        $this->userInfo = $userInfo;
        self::$userInfoArray[$user_info_key] = $userInfo;

        return $this;
    }

    //处理发行人/融资方
    public function handleBorrowerInfo()
    {
        $_id_no = GibberishAESUtil::enc(trim($this->import['b_idno']), Yii::app()->c->idno_key);

        $borrower_info_key = $this->import['borrower_name'].'-'.$_id_no;
        if (!empty(self::$borrowerInfoArray) && in_array($borrower_info_key, array_keys(self::$borrowerInfoArray))) {
            $this->borrowerInfo = self::$borrowerInfoArray[$borrower_info_key];
        } else {
            $enterprise = Firstp2pEnterprise::model()->findByAttributes(['company_name' => $this->import['borrower_name'],'company_purpose'=>2]);
            if (!$enterprise) {
                $must = ['user_name', 'user_pwd', 'passport_id', 'create_time', 'update_time', 'login_ip', 'group_id', 'is_effect', 'is_delete', 'passport_no', 'military_id', 'h_idno', 't_idno', 'm_idno', 'other_idno', 'idcardpassed', 'idcardpassed_time', 'real_name', 'mobile', 'mobilepassed', 'score', 'money', 'lock_money', 'code', 'login_time', 'focus_count', 'focused_count', 'n_province_id', 'n_city_id', 'province_id', 'city_id', 'graduation', 'graduatedyear', 'university', 'edu_validcode', 'marriage', 'haschild', 'hashouse', 'houseloan', 'hascar', 'carloan', 'car_brand', 'car_year', 'car_number', 'address', 'phone', 'point', 'creditpassed', 'creditpassed_time', 'workpassed', 'workpassed_time', 'incomepassed', 'incomepassed_time', 'housepassed', 'housepassed_time', 'carpassed', 'carpassed_time', 'marrypassed', 'marrypassed_time', 'edupassed', 'edupassed_time', 'skillpassed', 'skillpassed_time', 'videopassed', 'videopassed_time', 'mobiletruepassed', 'mobiletruepassed_time', 'residencepassed', 'residencepassed_time'];
                $borrowerInfo = new Firstp2pUser();
                foreach ($must as $item) {
                    $borrowerInfo->$item = 0;
                }
                $borrowerInfo->user_name = 'B'.substr(time(),2,8).rand(10000, 99999);
                $borrowerInfo->real_name = $this->import['borrower_name'];
                $borrowerInfo->idno = $_id_no;
                $borrowerInfo->id_type = $this->import['b_idno_type'];
                $borrowerInfo->is_effect = 1;
                $borrowerInfo->user_type = 1;
                $borrowerInfo->create_time = time();
                $borrowerInfo->update_time = time();
                if (false === $borrowerInfo->save()) {
                    throw new Exception('创建借款人时失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($borrowerInfo->getErrors(), true));
                }

                $enterprise = new Firstp2pEnterprise();
                $enterprise->user_id = $borrowerInfo->id;
                $enterprise->company_purpose = 2; //企业会员账户用途(0:其他1:投资2:融资3:咨询4:担保5:渠道)
                $enterprise->credentials_type = 1; //企业证件类别(0:其他 1:营业执照 2:组织机构代码证 3:三证合一营业执照)
                $enterprise->credentials_no = $this->import['b_idno']; //营业执照
                $enterprise->company_name = $this->import['borrower_name']; //企业名称
                $enterprise->legalbody_name = $this->import['b_legal_person']; //法人
                $enterprise->registration_address = $this->import['b_address'];
                $enterprise->contract_address = $this->import['b_address'];

                if (false == $enterprise->save()) {
                    throw new Exception('创建借款人机构信息失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($enterprise->getErrors(), true));
                }
            }else {

                $borrowerInfo = Firstp2pUser::model()->findByAttributes(['id' => $enterprise->user_id]);
            }

            $this->borrowerInfo = $borrowerInfo;
            self::$borrowerInfoArray[$borrower_info_key] = $borrowerInfo;
        }

        return $this;
    }

    //处理项目信息
    public function handleDealInfo()
    {
        $deal_info_key = $this->import['p_name'];
        if (!empty(self::$dealInfoArray) && in_array($deal_info_key, array_keys(self::$dealInfoArray))) {
            $this->dealInfo = self::$dealInfoArray[$deal_info_key];
        } else {
            $dealInfo = OfflineDeal::model()->findByAttributes(['name' => $this->import['p_name'],'platform_id'=>$this->platform_id]);

            if ($dealInfo) {
                $this->dealInfo = $dealInfo;
            } else {
                $dealProjectInfo = new OfflineDealProject();
                $dealProjectInfo->name = $this->import['p_name']; //项目名称
                $dealProjectInfo->product_class = 0;
                $dealProjectInfo->product_name = $this->import['p_name']; //产品名称
                $dealProjectInfo->platform_id = $this->import['platform_id']; //平台id
                $dealProjectInfo->intro = $this->import['p_desc']; //描述
                $dealProjectInfo->borrow_amount = $this->import['raise_money']; //借款金额
                $dealProjectInfo->user_id = $this->borrowerInfo->id; //借款企业id
                $dealProjectInfo->loantype = $this->import['loantype']; //还款方式
                $dealProjectInfo->repay_time = $this->import['p_limit_num']; //借款期限
                $dealProjectInfo->limit_type = $this->import['p_limit_type']; //借款期限类型
                $dealProjectInfo->rate = $this->import['rate']; //年化利率
                $dealProjectInfo->max_rate = $this->import['max_rate']; //年化利率
                $dealProjectInfo->business_status = 5; //还款中
                $dealProjectInfo->card_type = 1; //放款账号对公对私类型(0:对私  1:对公)
                $dealProjectInfo->bankzone = $this->import['b_bankzone'];
                $dealProjectInfo->bankcard = $this->import['b_bank_number'];
                $dealProjectInfo->card_name = $this->import['borrower_name'];
                $dealProjectInfo->platform_id = $this->platform_id;
                if (false === $dealProjectInfo->save()) {
                    throw new Exception('创建父项目失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($dealProjectInfo->getErrors(), true));
                }

                $dealInfo = new OfflineDeal();
                $dealInfo->name = $this->import['p_name']; //借款标题
                $dealInfo->rate = $this->import['rate']; //年化利率
                $dealInfo->loantype = $this->import['loantype']; //还款方式
                $dealInfo->product_class_type = $this->import['p_type']; //产品分类
                $dealInfo->repay_start_time = $this->import['value_date']; //起息日
                $dealInfo->repayment_date = $this->import['repayment_time']; //到期日
                $dealInfo->next_repay_time = 0; //$this->import['']; //下次还款时间
                $dealInfo->borrow_amount = $this->import['raise_money']; //借款金额
                $dealInfo->repay_time = $this->import['p_limit_num']; //借款期限
                $dealInfo->platform_id = $this->platform_id; //平台id
                $dealInfo->description = $this->import['p_desc'];
                $dealInfo->is_effect = 1;
                $dealInfo->deal_status = 4;
                $dealInfo->is_has_loans = 1;
                $dealInfo->project_id = $dealProjectInfo->id;
                $dealInfo->object_sn = $this->import['object_sn'];
                $dealInfo->user_id = $this->borrowerInfo->id; //借款企业id
                $dealInfo->agency_id = $this->agencyInfo->id; //担保机构id
                if (false === $dealInfo->save()) {
                    throw new Exception('创建子项目失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($dealInfo->getErrors(), true));
                }
            }
            $this->dealInfo = $dealInfo;
            self::$dealInfoArray[$deal_info_key] = $dealInfo;
        }

        return $this;
    }

    //处理投资记录信息
    public function handelTenderInfo()
    {

        $dealLoadInfo = new OfflineDealLoad();
        $dealLoadInfo->deal_id = $this->dealInfo->id;
        $dealLoadInfo->user_id = $this->userInfo->id;
        $dealLoadInfo->user_name = $this->userInfo->user_name;
        $dealLoadInfo->user_deal_name = ''; //todo 投资列表展示的名称
        $dealLoadInfo->money = $this->import['rg_amount'];
        $dealLoadInfo->wait_capital = $this->import['wait_capital'];
        $dealLoadInfo->wait_interest = $this->import['wait_interest'];
        $dealLoadInfo->receivable_interest = $this->import['receivable_interest'];//应收利息
        $dealLoadInfo->repay_capital_init = $this->import['rg_amount'];
        $dealLoadInfo->create_time = $this->import['value_date']; //投资时间
        $dealLoadInfo->order_sn = 0; //
        $dealLoadInfo->platform_id = $this->platform_id; //平台id
        $dealLoadInfo->status = 3;//wait confirm
        if (false === $dealLoadInfo->save()) {
            throw new Exception('创建投资记录失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($dealLoadInfo->getErrors(), true));
        }
        $this->dealLoadInfo = $dealLoadInfo;

//        $res = OfflineDealLoad::model()->findByAttributes(['user_id' => $this->userInfo->id, 'deal_id' => $this->dealInfo->id ,'platform_id'=>$this->platform_id]);
//        if (!$res) {
//
//        } else {
//            throw new Exception('投资记录重复 import_content 表 id:'.$this->import['id']);
//        }

        return $this;
    }

    //处理合同信息
    public function handelContractInfo()
    {
        //借款合同
        if (!empty($this->import['download'])) {
            $contract = OfflineContractTask::model()->findByAttributes(['oss_download' => $this->import['download'],'platform_id'=>$this->platform_id]);
            if ($contract) {
                throw new Exception('创建借款合同失败 import_content 表 id:'.$this->import['id'].' 该合同地址已被出借记录:'.$contract->tender_id.' 使用');
            }
            $contract = new OfflineContractTask();
            $contract->borrow_id = $this->dealInfo->id;
            $contract->type = 1;
            $contract->status = 2;
            $contract->contract_type = 1;
            $contract->contract_no = $this->import['contract_number'];
            $contract->tender_id = $this->dealLoadInfo->id;
            $contract->user_id = $this->userInfo->id;
            $contract->oss_download = $this->import['download'];
            $contract->investtime = $this->import['rg_time'];
            $contract->addtime = time();
            $contract->platform_id = $this->platform_id; //平台id;
            if (false === $contract->save()) {
                throw new Exception('创建借款合同失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($contract->getErrors(), true));
            }
        }

        //担保合同
        if (!empty($this->import['danbao_download'])) {
            $contract = OfflineContractTask::model()->findByAttributes(['oss_download' => $this->import['danbao_download'],'platform_id'=>$this->platform_id]);
            if ($contract) {
                throw new Exception('创建担保合同失败 import_content 表 id:'.$this->import['id'].' 该合同地址已被出借记录:'.$contract->tender_id.' 使用');
            }
            $contract = new OfflineContractTask();
            $contract->borrow_id = $this->dealInfo->id;
            $contract->type = 1;
            $contract->status = 2;
            $contract->contract_type = 2;
            $contract->contract_no = '';
            $contract->tender_id = $this->dealLoadInfo->id;
            $contract->user_id = $this->userInfo->id;
            $contract->oss_download = $this->import['danbao_download'];
            $contract->investtime = $this->import['rg_time'];
            $contract->addtime = time();
            $contract->platform_id = $this->platform_id; //平台id;
            if (false === $contract->save()) {
                throw new Exception('创建担保合同失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($contract->getErrors(), true));
            }
        }

        //咨询服务协议
        if (!empty($this->import['zixun_fuwu_download'])) {
            $contract = OfflineContractTask::model()->findByAttributes(['oss_download' => $this->import['zixun_fuwu_download'],'platform_id'=>$this->platform_id]);
            if ($contract) {
                throw new Exception('创建咨询服务协议失败 import_content 表 id:'.$this->import['id'].' 该合同地址已被出借记录:'.$contract->tender_id.' 使用');
            }
            $contract = new OfflineContractTask();
            $contract->borrow_id = $this->dealInfo->id;
            $contract->type = 1;
            $contract->status = 2;
            $contract->contract_type = 3;
            $contract->contract_no = '';
            $contract->tender_id = $this->dealLoadInfo->id;
            $contract->user_id = $this->userInfo->id;
            $contract->oss_download = $this->import['zixun_fuwu_download'];
            $contract->investtime = $this->import['rg_time'];
            $contract->addtime = time();
            $contract->platform_id = $this->platform_id; //平台id;
            if (false === $contract->save()) {
                throw new Exception('创建咨询服务协议失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($contract->getErrors(), true));
            }
        }

        return $this;
    }

}
