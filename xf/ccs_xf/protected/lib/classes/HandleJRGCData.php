<?php

/**
 * 金融工厂数据导入
 * Class HandleJRGCData.
 */
class HandleJRGCData extends BaseHandleOfflineData
{
    //需要必填的字段
    public $needCheckField = [
        'order_sn', 'object_sn', 'mobile_phone', 'old_user_id', 'user_name',
    ];
    public $platform_id = 3;
    public $productType2Name = [
        1 => '产融通',
        2 => '消费贷',
        3 => '利随享',
    ];

    public $contractTyp2Name = [
        1 => '出借合同',
        2 => '担保合同',
        3 => '出借咨询与服务协议',
    ];

    //导入的临时还款计划数据
    private $uploadRepayLog;

    private $contract_list_key = 'jrgc:contract:user:tender:';

    public function handle($import)
    {
        try {
            $this->checkParam($import)->handleUserInfo()->handleUserBankInfo()->handleBorrowerInfo()->handleAgencyInfo()->handleDealInfo()->handelTenderInfo()->handelContractInfo();
        }catch (Exception $e){
            throw  $e;
        }
        return $this;
    }

    //处理用户信息
    public function handleUserInfo()
    {
        $_id_no = GibberishAESUtil::enc(trim($this->import['idno']), Yii::app()->c->idno_key);
        $_mobile = GibberishAESUtil::enc(trim($this->import['mobile_phone']), Yii::app()->c->idno_key);
        $user_info_key = $_mobile;
        if (!empty(self::$userInfoArray) && in_array($user_info_key, array_keys(self::$userInfoArray))) {
            $this->userInfo = self::$userInfoArray[$user_info_key];
        } else {
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

            if ($userInfo->idno !== $_id_no) {
                throw new Exception('该手机号:'.$this->import['mobile_phone'].' 证件号不一致 已经被用户id:'.$userInfo->id.' 证件号为:['.GibberishAESUtil::dec($userInfo->idno , Yii::app()->c->idno_key).']使用');
            }

            if ($userInfo->real_name !== $this->import['user_name']) {
                throw new Exception('该手机号:'.$this->import['mobile_phone'].' 姓名不一致 已经被用户id:'.$userInfo->id.' 姓名为:['.$userInfo->real_name.']使用');
            }

            $this->userInfo = $userInfo;
            self::$userInfoArray[$user_info_key] = $userInfo;
        }

        return $this;
    }

    //处理项目信息
    public function handleDealInfo()
    {
        $deal_info_key = $this->import['object_sn'];
        if (!empty(self::$dealInfoArray) && in_array($deal_info_key, array_keys(self::$dealInfoArray))) {
            $this->dealInfo = self::$dealInfoArray[$deal_info_key];
        } else {
            $dealInfo = OfflineDeal::model()->findByAttributes(['object_sn' => $this->import['object_sn'],'platform_id'=>$this->platform_id]);

            if ($dealInfo) {
                $this->dealInfo = $dealInfo;
            } else {
                $dealProjectInfo = new OfflineDealProject();
                $dealProjectInfo->name = $this->import['p_name']; //项目名称
                $dealProjectInfo->product_class = $this->productType2Name[$this->import['p_type']];
                $dealProjectInfo->product_name = $this->import['p_name']; //产品名称
                $dealProjectInfo->platform_id = $this->import['platform_id']; //平台id
                $dealProjectInfo->intro = $this->import['p_desc']; //年化利率
                $dealProjectInfo->borrow_amount = $this->import['raise_money']; //借款金额
                $dealProjectInfo->user_id = $this->borrowerInfo->id; //借款企业id
                $dealProjectInfo->loantype = $this->import['loantype']; //还款方式
                $dealProjectInfo->repay_time = $this->import['p_limit_num']; //借款期限
                $dealProjectInfo->rate = $this->import['rate']; //年化利率
                $dealProjectInfo->business_status = 5; //还款中
                $dealProjectInfo->card_type = $this->import['b_type'] - 1; //放款账号对公对私类型(0:对私  1:对公)
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
        $res = OfflineDealLoad::model()->findByAttributes(['user_id' => $this->userInfo->id, 'order_sn' => $this->import['order_sn'],'platform_id'=>$this->platform_id]);
        if (!$res) {
            $dealLoadInfo = new OfflineDealLoad();
            $dealLoadInfo->deal_id = $this->dealInfo->id;
            $dealLoadInfo->user_id = $this->userInfo->id;
            $dealLoadInfo->user_name = $this->userInfo->user_name;
            $dealLoadInfo->user_deal_name = ''; //todo 投资列表展示的名称
            $dealLoadInfo->money = $this->import['rg_amount'];
            $dealLoadInfo->wait_capital = $this->import['wait_capital'];
            $dealLoadInfo->wait_interest = $this->import['wait_interest'];
            $dealLoadInfo->repay_capital_init = $this->import['rg_amount'];
            $dealLoadInfo->create_time = $this->import['rg_time']; //投资时间
            $dealLoadInfo->order_sn = $this->import['order_sn']; //投资时间
            $dealLoadInfo->platform_id = $this->platform_id; //平台id
            $dealLoadInfo->status = 1;//还款中
            if (false === $dealLoadInfo->save()) {
                throw new Exception('创建投资记录失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($dealLoadInfo->getErrors(), true));
            }
            $this->dealLoadInfo = $dealLoadInfo;
        } else {
            throw new Exception('投资记录重复 import_content 表 id:'.$this->import['id']);
        }

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

    /**
     * 校验数据.
     *
     * @param $uploadRepayLog
     *
     * @return HandleJRGCData
     *
     * @throws Exception
     */
    private function checkUploadRepayLogParams($uploadRepayLog)
    {
        $this->uploadRepayLog = $uploadRepayLog;

        if (empty($this->uploadRepayLog)) {
            throw new Exception('上传还款记录对象不存在');
        }
        if (empty($this->uploadRepayLog['repay_log_id'])) {
            throw new Exception('原还款计划id不存在 id:'.$this->uploadRepayLog['id']);
        }
        if (empty($this->uploadRepayLog['order_sn'])) {
            throw new Exception('原投资订单号不存在 id:'.$this->uploadRepayLog['id']);
        }
        if (empty($this->uploadRepayLog['object_sn'])) {
            throw new Exception('原标的号不存在 id:'.$this->uploadRepayLog['id']);
        }

        return $this;
    }

    /**
     * 校验并生成新的还款计划.
     *
     * @throws Exception
     */
    private function makeRepayInfo()
    {
        $res = OfflineDealLoanRepay::model()->findByAttributes(['old_repay_log_id' => $this->uploadRepayLog['repay_log_id'],'platform_id'=>$this->platform_id]);
        if (!empty($res)) {
            throw new Exception('该笔还款计划已经存在: old_repay_log_id:'.$this->uploadRepayLog['repay_log_id']);
        }

        $tenderInfo = OfflineDealLoad::model()->findByAttributes(['order_sn' => $this->uploadRepayLog['order_sn'],'platform_id'=>$this->platform_id]);
        if (empty($tenderInfo)) {
            throw new Exception('出借记录表未查询到改笔出借: order_sn:'.$this->uploadRepayLog['order_sn']);
        }

        $dealInfo = OfflineDeal::model()->findByPk($tenderInfo->deal_id);
        if (empty($dealInfo)) {
            throw new Exception('子项目表未查询到改笔借款:id '.$tenderInfo->deal_id.', order_sn:'.$this->uploadRepayLog['order_sn']);
        }
        //本机和利息
        if ($this->uploadRepayLog['capital'] > 0) {
            $dealLoanRepay = new OfflineDealLoanRepay();
            $dealLoanRepay->deal_loan_id = $tenderInfo->id;
            $dealLoanRepay->loan_user_id = $tenderInfo->user_id;
            $dealLoanRepay->deal_id = $tenderInfo->deal_id;
            $dealLoanRepay->borrow_user_id = $dealInfo->user_id; //借款人id
            $dealLoanRepay->deal_repay_id = 0;
            $dealLoanRepay->money = $this->uploadRepayLog['capital'];
            $dealLoanRepay->time = $this->uploadRepayLog['time'] - 3600 * 8;
            $dealLoanRepay->real_time = $this->uploadRepayLog['real_time'];
            $dealLoanRepay->status = $this->uploadRepayLog['repay_status'];
            $dealLoanRepay->type = 1;
            $dealLoanRepay->old_repay_log_id = $this->uploadRepayLog['repay_log_id'];
            $dealLoanRepay->platform_id = $this->platform_id; //平台id;

            if (false == $dealLoanRepay->save()) {
                throw new Exception('创建新的本金还款计划失败 '.print_r($dealLoanRepay->getAttributes(), true));
            }
        }

        if ($this->uploadRepayLog['interest'] > 0) {
            $dealLoanRepay = new OfflineDealLoanRepay();
            $dealLoanRepay->deal_loan_id = $tenderInfo->id;
            $dealLoanRepay->loan_user_id = $tenderInfo->user_id;
            $dealLoanRepay->deal_id = $tenderInfo->deal_id;
            $dealLoanRepay->borrow_user_id = $dealInfo->user_id; //借款人id
            $dealLoanRepay->deal_repay_id = 0;
            $dealLoanRepay->money = $this->uploadRepayLog['interest'];
            $dealLoanRepay->time = $this->uploadRepayLog['time'] - 3600 * 8;
            $dealLoanRepay->real_time = $this->uploadRepayLog['real_time'];
            $dealLoanRepay->status = $this->uploadRepayLog['repay_status'];
            $dealLoanRepay->type = 2;
            $dealLoanRepay->old_repay_log_id = $this->uploadRepayLog['repay_log_id'];
            $dealLoanRepay->platform_id = $this->platform_id; //平台id;
            if (false == $dealLoanRepay->save()) {
                throw new Exception('创建新的利息还款计划失败 '.print_r($dealLoanRepay->getAttributes(), true));
            }
        }
    }

    /**
     * 处理还款计划导入数据.
     *
     * @param $uploadRepayLog
     *
     * @return HandleJRGCData|void
     *
     * @throws Exception
     */
    public function handelCollectionInfo($uploadRepayLog)
    {
        try {
            $this->checkUploadRepayLogParams($uploadRepayLog)->makeRepayInfo();
        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * 还款计划导入文件列表.
     *
     * @param $params
     *
     * @return array
     */
    public function getUploadRepayFileList($params)
    {
        $where = ['platform_id = '.$this->platform_id];
        if (!empty($params['start'])) {
            $where[] = 'addtime >= '.strtotime($params['start']);
        }
        if (!empty($params['end'])) {
            $where[] = 'addtime <= '.(strtotime($params['end']) + 86400);
        }
        if (isset($params['auth_status']) && in_array($params['auth_status'], [0, 1, 2, 3])) {
            $where[] = 'auth_status = '.intval($params['auth_status']);
        }
        $condition = implode(' and ', $where);
        $fileList = [];
        $countFile = OfflineUploadRepayFile::model()->countBySql('select count(1) from offline_upload_repay_file where '.$condition);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            if (1 == $params['export']) {
                $offset = 0;
                $pageSize = 50000;
            }
            $sql = "select * from offline_upload_repay_file  where {$condition} order by id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = OfflineUploadRepayFile::model()->findAllBySql($sql);
            foreach ($_file as $item) {
                $list['id'] = $item->id;
                $list['success_num'] = $item->success_num;
                $list['total_num'] = $item->total_num;
                $list['platform_id'] = $item->platform_id;
                $list['fail_num'] = $item->fail_num;
                $list['total_num'] = $item->total_num;
                $list['total_amount'] = number_format($item->total_amount, 2);
                $list['success_capital_amount'] = number_format($item->success_capital_amount, 2);
                $list['success_interest_amount'] = number_format($item->success_interest_amount, 2);
                $list['fail_capital_amount'] = number_format($item->fail_capital_amount, 2);
                $list['fail_interest_amount'] = number_format($item->fail_interest_amount, 2);

                //处理成功本金
                $list['handle_success_capital_amount'] = number_format($item->handle_success_capital_amount, 2);
                //处理成功利息
                $list['handle_success_interest_amount'] = number_format($item->handle_success_interest_amount, 2);
                //处理失败本金
                $list['handle_fail_capital_amount'] = number_format($item->handle_fail_capital_amount, 2);
                //处理失败利息
                $list['handle_fail_interest_amount'] = number_format($item->handle_fail_interest_amount, 2);
                //处理成功条数
                $list['handle_success_num'] = $item->handle_success_num;
                //处理失败条数
                $list['handle_fail_num'] = $item->handle_fail_num;

                $list['action_admin_id'] = $item->action_admin_id;
                $list['addtime'] = date('Y-m-d H:i:s', $item->addtime);
                $list['action_user_name'] = $item->action_user_name;
                $list['auth_user_name'] = $item->auth_user_name;
                $list['auth_time'] = $item->auth_time ? date('Y-m-d H:i:s', $item->auth_time) : '';
                $list['auth_status'] = $item->auth_status;
                $list['status_cn'] = 1 == $item->deal_status ? $this->authStatus2Name[4] : $this->authStatus2Name[$item->auth_status];
                $fileList[] = $list;
            }
        }
        if (1 == $params['export']) {
            include APP_DIR.'/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR.'/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(15);
            $objPHPExcel->getActiveSheet()->setCellValue('A1', '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '录入总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '录入成功条数');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '录入失败条数');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '录入成功在途本金');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '录入成功在途利息');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '录入失败在途本金');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '录入失败在途利息');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '执行成功在途本金');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', '执行成功在途利息');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '执行失败在途本金');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', '执行失败在途利息');
            $objPHPExcel->getActiveSheet()->setCellValue('M1', '执行成功条数');
            $objPHPExcel->getActiveSheet()->setCellValue('N1', '执行失败条数');
            $objPHPExcel->getActiveSheet()->setCellValue('O1', '录入人');
            $objPHPExcel->getActiveSheet()->setCellValue('P1', '录入时间');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1', '审核人');
            $objPHPExcel->getActiveSheet()->setCellValue('R1', '审核时间');
            $objPHPExcel->getActiveSheet()->setCellValue('S1', '状态');
            if (!empty($fileList)) {
                foreach ($fileList as $key => $value) {
                    $key += 2;
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.($key), $value['id']);
                    $objPHPExcel->getActiveSheet()->setCellValue('B'.($key), $value['total_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('C'.($key), $value['success_num']);
                    $objPHPExcel->getActiveSheet()->setCellValue('D'.($key), $value['fail_num']);
                    $objPHPExcel->getActiveSheet()->setCellValue('E'.($key), $value['success_capital_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('F'.($key), $value['success_interest_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('G'.($key), $value['fail_capital_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('H'.($key), $value['fail_interest_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('I'.($key), $value['handle_success_capital_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('K'.($key), $value['handle_success_interest_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('K'.($key), $value['handle_fail_capital_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('L'.($key), $value['handle_fail_interest_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('M'.($key), $value['handle_success_num']);
                    $objPHPExcel->getActiveSheet()->setCellValue('N'.($key), $value['handle_fail_num']);
                    $objPHPExcel->getActiveSheet()->setCellValue('O'.($key), $value['action_user_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue('P'.($key), $value['addtime']);
                    $objPHPExcel->getActiveSheet()->setCellValue('Q'.($key), $value['auth_user_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue('R'.($key), $value['auth_time']);
                    $objPHPExcel->getActiveSheet()->setCellValue('S'.($key), $value['status_cn']);
                }
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '金融工厂还款记录导入记录'.date('Y年m月d日 H时i分s秒');
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control:must-revalidate, post-check=0, pre-check=0');
            header('Content-Type:application/force-download');
            header('Content-Type:application/vnd.ms-execl');
            header('Content-Type:application/octet-stream');
            header('Content-Type:application/download');
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header('Content-Transfer-Encoding:binary');
            $objWriter->save('php://output');
        }

        return ['countNum' => $countFile, 'list' => $fileList];
    }

    /**
     * 还款计划导入明细列表.
     *
     * @param $params
     *
     * @return array
     */
    public function getRepayLogList($params)
    {
        $where = ['platform_id = '.$this->platform_id];
        if (!empty($params['start'])) {
            $where[] = 'time >= '.strtotime($params['start']);
        }
        if (!empty($params['end'])) {
            $where[] = 'time <= '.(strtotime($params['end']) + 86400);
        }
        if (!empty($params['remark'])) {
            $where[] = "remark  like '%".trim($params['remark'])."%'";
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1, 2, 3, 4, 5])) {
            $where[] = 'status = '.intval($params['status']);
        }

        $condition = implode(' and ', $where);
        $fileList = [];
        $countFile = OfflineUploadRepayLog::model()->countBySql('select count(1) from offline_upload_repay_log where '.$condition);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            if (1 == $params['export']) {
                $offset = 0;
                $pageSize = 50000;
            }
            $sql = "select * from offline_upload_repay_log  where {$condition} order by id asc LIMIT {$offset} , {$pageSize} ";
            $_file = OfflineUploadRepayLog::model()->findAllBySql($sql);
            foreach ($_file as $key => $item) {
                $fileList[$key] = $item->getAttributes();
                $fileList[$key]['create_time'] = date('Y-m-d H:i:s', $item->create_time);
                $fileList[$key]['time'] = date('Y-m-d H:i:s', $item->time);
                $fileList[$key]['real_time'] = date('Y-m-d H:i:s', $item->real_time);
                $fileList[$key]['status_cn'] = $this->stats2Name[$item->status];
                $fileList[$key]['repay_status_cn'] = 0 == $item->repay_status ? '未还' : '已还';
                $fileList[$key]['deal_status_cn'] = $this->dealStatus2name[$item->deal_status];
            }
        }
        if (1 == $params['export']) {
            include APP_DIR.'/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR.'/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(10);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(10);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(30);
            $objPHPExcel->getActiveSheet()->setCellValue('A1', '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '原回款记录id');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '原出借记录订单号');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '原标的号');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '手机号码');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '证件号');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '本金');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '利息');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '总额');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', '还款状态');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '审核状态');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', '处理状态');
            $objPHPExcel->getActiveSheet()->setCellValue('M1', '备注');
            if (!empty($fileList)) {
                foreach ($fileList as $key => $value) {
                    $key += 2;
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.($key), $value['id']);
                    $objPHPExcel->getActiveSheet()->setCellValue('B'.($key), $value['repay_log_id']);
                    $objPHPExcel->getActiveSheet()->setCellValue('C'.($key), $value['order_sn']);
                    $objPHPExcel->getActiveSheet()->setCellValue('D'.($key), $value['object_sn']);
                    $objPHPExcel->getActiveSheet()->setCellValue('E'.($key), $value['']);
                    $objPHPExcel->getActiveSheet()->setCellValue('F'.($key), $value['']);
                    $objPHPExcel->getActiveSheet()->setCellValue('G'.($key), $value['capital']);
                    $objPHPExcel->getActiveSheet()->setCellValue('H'.($key), $value['interest']);
                    $objPHPExcel->getActiveSheet()->setCellValue('I'.($key), $value['total_money']);
                    $objPHPExcel->getActiveSheet()->setCellValue('J'.($key), $value['repay_status_cn']);
                    $objPHPExcel->getActiveSheet()->setCellValue('K'.($key), $value['status_cn']);
                    $objPHPExcel->getActiveSheet()->setCellValue('L'.($key), $value['deal_status_cn']);
                    $objPHPExcel->getActiveSheet()->setCellValue('M'.($key), $value['remark']);
                }
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '金融工厂录入还款计划明细'.date('Y年m月d日 H时i分s秒');
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control:must-revalidate, post-check=0, pre-check=0');
            header('Content-Type:application/force-download');
            header('Content-Type:application/vnd.ms-execl');
            header('Content-Type:application/octet-stream');
            header('Content-Type:application/download');
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header('Content-Transfer-Encoding:binary');
            $objWriter->save('php://output');
        }

        return ['countNum' => $countFile, 'list' => $fileList];
    }

    /**
     * 合同列表.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getContractList()
    {
        $list = [];
        $params = $this->getContractNeedParams;

        $dealLoadInfo = Yii::app()->offlinedb->createCommand('select debt_type,deal_id from offline_deal_load where id =:deal_load_id and platform_id =:platform_id')->bindValues([':deal_load_id' => $params['deal_load_id'], ':platform_id' => $this->platform_id])->queryRow();
        if (empty($dealLoadInfo)) {
            throw  new Exception('出借记录不存在');
        }

        if (1 == $dealLoadInfo['debt_type']) {
            $sql = 'select  contract_type,oss_download from offline_contract_task where tender_id =:deal_load_id and platform_id =:platform_id   and user_id =:user_id and `type` = 1 and status = 2';
            $contract_info = Yii::app()->offlinedb->createCommand($sql)->bindValues([':deal_load_id' => $params['deal_load_id'], ':platform_id' => $this->platform_id, ':user_id' => $params['user_id']])->queryAll();
            if (!empty($contract_info)) {
                foreach ($contract_info as $item) {
                    $contract['title'] = $this->contractTyp2Name[$item['contract_type']];
                    $contract['path'] = $item['oss_download'];
                    $list[] = $contract;
                }
                //看看有没有转让过的
                $sql = 'select d.id from firstp2p_deal_load as t left join firstp2p_debt as d on t.id = d.tender_id where t.id =:deal_load_id and d.status = 1 ';
                $debtInfo = Yii::app()->offlinedb->createCommand($sql)->bindValues([':deal_load_id' => $params['deal_load_id']])->queryAll();
                if (!empty($debtInfo)) {
                    $debtId = ArrayUntil::array_column($debtInfo, 'id');
                    //看有没有认购成功
                    $sql = 'select new_tender_id from firstp2p_debt_tender where debt_id in ('.implode($debtId).')';
                    $debtTenderInfo = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                    if (!empty($debtTenderInfo)) {
                        $newTenderId = ArrayUntil::array_column($debtTenderInfo, 'new_tender_id');
                        //看合同
                        $sql = 'select  contract_type,oss_download from offline_contract_task where borrow_id =:deal_id and status = 2 and tender_id in ('.implode(',', $newTenderId).')';
                        $debtContract = Yii::app()->offlinedb->createCommand($sql)->bindValues([':deal_id' => $dealLoadInfo['deal_id']])->queryAll();
                        if (!empty($debtContract)) {
                            foreach ($debtContract as $item) {
                                $contract['title'] = $this->contractTyp2Name[$item['contract_type']];
                                $contract['path'] = $item['oss_download'];
                                $list[] = $contract;
                            }
                        }
                    }
                }
            }
        } else {
            //债权的合同
            $sql = 'select  contract_type,oss_download from offline_contract_task where tender_id =:deal_load_id and platform_id =:platform_id  and user_id =:user_id and `type` = 0 and status = 2';
            $debtContract = Yii::app()->offlinedb->createCommand($sql)->bindValues([':deal_load_id' => $params['deal_load_id'], ':platform_id' => $this->platform_id, ':user_id' => $params['user_id']])->queryAll();
            if (!empty($debtContract)) {
                foreach ($debtContract as $item) {
                    $contract['title'] = $this->contractTyp2Name[$item['contract_type']];
                    $contract['path'] = $item['oss_download'];
                    $list[] = $contract;
                }
            }
        }

        Yii::app()->rcache->set($this->contract_list_key.$params['user_id'].':'.$params['deal_load_id'], json_encode($list), 3600); //缓存1小时

        return  $list;
    }

    /**
     * 查看指定合同.
     *
     * @throws Exception
     */
    public function getContractInfo()
    {
        $params = $this->getContractNeedParams;

        $contractList = Yii::app()->rcache->get($this->contract_list_key.$params['user_id'].':'.$params['deal_load_id']);
        if (empty($contractList)) {
            throw new Exception('超时~请返回合同列表重试');
        }
        if (!isset($params['order']) || empty($contractList[$params['order']])) {
            throw new Exception('合同序号未提供');
        }

        $contractList = json_decode($contractList, true);
        $info = parse_url($contractList[$params['order']]['path']);
        $path = $info['path'];
        $filename = basename($path);
        $fileBuffer = Yii::app()->oss->getObject(Yii::app()->oss->bucket, $path);
        if ($fileBuffer) {
            ob_end_clean();
            header('Content-Type: application/force-download');
            header('Content-Transfer-Encoding: binary');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename='.$filename);
            header('Content-Length: '.strlen($fileBuffer));
            echo $fileBuffer;
            flush();
            ob_flush();

            return;
        }
    }
}
