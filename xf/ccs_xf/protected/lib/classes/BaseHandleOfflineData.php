<?php

/**
 * 处理线下数据导入类
 * Class BaseHandleOfflineData.
 */
class BaseHandleOfflineData
{
    protected $contract_oss_path = [
        'group1' => '阿里云地址1',
        'group2' => '阿里云地址2',
    ];

    protected $getContractNeedParams = [];

    public $platform_id = 0;

    public $authStatus2Name = [
        0 => '待审核',
        1 => '审核已通过',
        2 => '审核未通过',
        3 => '已撤回',
        4 => '执行完成',
    ];

    public $stats2Name = [
        0 => '未定义',
        1 => '导入成功',
        2 => '导入失败',
        3 => '已取消',
        4 => '入库成功',
        5 => '入库失败',
    ];

    public $dealStatus2name = [
        0 => '待处理',
        1 => '已处理',
    ];

    public static $userInfoArray = [];
    public static $dealInfoArray = [];
    public static $borrowerInfoArray = [];
    public static $agencyInfoArray = [];
    public static $userPlatformInfoArray = [];
    //需要校验的参数
    public static $baseNeedCheckField = [
    ];
    //需要校验的字段
    public $needCheckField = [];
    //导入的每条统计记录维度的数据
    protected $import = [];
    //导入的用户信息
    protected $userInfo;
    //导入的用户平台信息
    protected $userPlatformInfo;
    //导入的项目信息
    protected $dealInfo;
    //导入的借款人信息
    protected $borrowerInfo;
    //担保方信息
    protected $agencyInfo;
    //投资记录信息
    protected $dealLoadInfo;

    public function __construct($config = [])
    {
        if (isset($config['checkField'])) {
            $this->needCheckField = $config['checkField'];
        }
    }

    public $productType2Name = [];

    public function handle($params)
    {
        return $this;
    }

    //验证输入字段
    public function checkParam($import)
    {
        foreach (array_merge($this->needCheckField, self::$baseNeedCheckField) as $filed) {
            if (!isset($import[$filed]) || empty($import[$filed])) {
                throw new Exception('校验数据时存在非法字段 key:'.$filed.' value:'.$import[$filed] ?: 'null');
            }
        }
        $this->import = $import;

        return $this;
    }

    //处理用户信息
    public function handleUserInfo()
    {
        return $this;
    }

    //处理借款人信息
    public function handleBorrowerInfo()
    {
        $_id_no = GibberishAESUtil::enc(trim($this->import['b_idno']), Yii::app()->c->idno_key);

        $borrower_info_key = $this->import['borrower_name'].'-'.$_id_no;
        if (!empty(self::$borrowerInfoArray) && in_array($borrower_info_key, array_keys(self::$borrowerInfoArray))) {
            $this->borrowerInfo = self::$borrowerInfoArray[$borrower_info_key];
        } else {

            if($this->import['b_type'] == 1){

                $borrowerInfo = Firstp2pUser::model()->findByAttributes(['user_type' => 1, 'idno' => $_id_no, 'real_name' => $this->import['borrower_name']]);
                if (!$borrowerInfo) {
                    $must = ['user_name', 'user_pwd', 'passport_id', 'create_time', 'update_time', 'login_ip', 'group_id', 'is_effect', 'is_delete', 'passport_no', 'military_id', 'h_idno', 't_idno', 'm_idno', 'other_idno', 'idcardpassed', 'idcardpassed_time', 'real_name', 'mobile', 'mobilepassed', 'score', 'money', 'lock_money', 'code', 'login_time', 'focus_count', 'focused_count', 'n_province_id', 'n_city_id', 'province_id', 'city_id', 'graduation', 'graduatedyear', 'university', 'edu_validcode', 'marriage', 'haschild', 'hashouse', 'houseloan', 'hascar', 'carloan', 'car_brand', 'car_year', 'car_number', 'address', 'phone', 'point', 'creditpassed', 'creditpassed_time', 'workpassed', 'workpassed_time', 'incomepassed', 'incomepassed_time', 'housepassed', 'housepassed_time', 'carpassed', 'carpassed_time', 'marrypassed', 'marrypassed_time', 'edupassed', 'edupassed_time', 'skillpassed', 'skillpassed_time', 'videopassed', 'videopassed_time', 'mobiletruepassed', 'mobiletruepassed_time', 'residencepassed', 'residencepassed_time'];
                    $borrowerInfo = new Firstp2pUser();
                    foreach ($must as $item) {
                        $borrowerInfo->$item = 0;
                    }
                    $borrowerInfo->user_name = 'B'.substr(time(),2,8).rand(10000, 99999);
                    $borrowerInfo->real_name = $this->import['borrower_name'];
                    $borrowerInfo->mobile = GibberishAESUtil::enc(trim($this->import['b_mobile_phone']), Yii::app()->c->idno_key);
                    $borrowerInfo->idno = $_id_no;
                    $borrowerInfo->id_type = $this->import['b_idno_type'];
                    $borrowerInfo->is_effect = 1;
                    $borrowerInfo->user_type = 1;
                    $borrowerInfo->create_time = time();
                    $borrowerInfo->update_time = time();
                    if (false === $borrowerInfo->save()) {
                        throw new Exception('创建借款人时失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($borrowerInfo->getErrors(), true));
                    }

                }
            }else{
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
            }

            $this->borrowerInfo = $borrowerInfo;
            self::$borrowerInfoArray[$borrower_info_key] = $borrowerInfo;
        }

        return $this;
    }

    //处理担保机构信息
    public function handleAgencyInfo()
    {
        $agency_info_key = $this->import['guarantee_name'].'-'.$this->import['g_license'];
        if (!empty(self::$agencyInfoArray) && in_array($agency_info_key, array_keys(self::$agencyInfoArray))) {
            $this->agencyInfo = self::$agencyInfoArray[$agency_info_key];
        } else {
            //这里先放弃使用 id_type
            $agencyInfo = OfflineDealAgency::model()->findByAttributes(['name' => $this->import['guarantee_name']]);
            if (!$agencyInfo) {
                $agencyInfo = new OfflineDealAgency();
                $agencyInfo->name = $this->import['guarantee_name'];
                $agencyInfo->bankzone = $this->import['g_bankzone']; //开户行
                $agencyInfo->bankcard = $this->import['g_bank_number']; //银行卡
                $agencyInfo->license = $this->import['g_license']; //执照
                $agencyInfo->address = $this->import['g_address']; //地址
                $agencyInfo->realname = $this->import['g_legal_person']; //法人
                $agencyInfo->company_brief = $this->import['g_desc']; //公司简介
                if (false === $agencyInfo->save()) {
                    throw new Exception('创建担保方失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($agencyInfo->getErrors(), true));
                }
            }
            $this->agencyInfo = $agencyInfo;
            self::$agencyInfoArray[$agency_info_key] = $agencyInfo;
        }

        return $this;
    }

    //处理项目信息
    public function handleDealInfo()
    {
        return $this;
    }

    //处理投资记录信息
    public function handelTenderInfo()
    {
        return $this;
    }

    /**
     * 生成还款计划.
     *
     * @param $params
     *
     * @return $this
     */
    public function handelCollectionInfo($params)
    {
        return $this;
    }

    //处理用户银行卡信息
    public function handleUserBankInfo()
    {
        if (!empty($this->import['bank_number'])) {
            $_card = GibberishAESUtil::enc(trim($this->import['bank_number']), Yii::app()->c->idno_key);
            $res = Firstp2pUserBankcard::model()->findByAttributes(['user_id' => $this->userInfo->id, 'verify_status' => 1]);

            $bankInfo = new Firstp2pUserBankcard();
            $bankInfo->user_id = $this->userInfo->id;
            $bankInfo->card_name = $this->import['user_name'];
            $bankInfo->bank_id = $this->import['bank_id'];
            $bankInfo->bankcard = $_card;
            $bankInfo->bankzone = $this->import['bankzone'];
            $bankInfo->verify_status = $res ? 0 : 1; //有 【有效的银行卡】 就 新增一个无效的。
            $bankInfo->create_time = time();
            $bankInfo->region_lv1 = '0';
            $bankInfo->region_lv2 = '0';
            $bankInfo->region_lv3 = '0';
            $bankInfo->region_lv4 = '0';
            if (false === $bankInfo->save()) {
                throw new Exception('创建用户银行卡失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($bankInfo->getErrors(), true));
            }
        }

        return $this;
    }

    //处理合同信息
    public function handelContractInfo()
    {
        if (!empty($this->import['contract_number'])) {
            $contract = new OfflineContractTask();
            $contract->borrow_id = $this->dealInfo->id;
            $contract->type = 1;
            $contract->tender_id = $this->dealLoadInfo->id;
            $contract->user_id = $this->userInfo->id;
            $contract->version = $this->import['contract_number'];
            $contract->investtime = $this->import['rg_time'];
            $contract->addtime = time();
            if (false === $contract->save()) {
                throw new Exception('创建合同失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($contract->getErrors(), true));
            }
        }

        return $this;
    }

    /**
     * 审核出借记录导入文件.
     *
     * @param $params
     *
     * @throws Exception
     */
    public function handelImportFileAuth($params)
    {
        if (empty($params['id'])) {
            throw new Exception('审核出借记录导入文件 id 为空 error:'.print_r($params, true));
        }
        if (empty($params['user_id'])) {
            throw new Exception('审核出借记录导入文件 审核人为空 error:'.print_r($params, true));
        }
        if (!in_array($params['auth_status'], [1, 2, 3])) {
            throw new Exception('审核出借记录导入文件 审核状态不合法 error:'.print_r($params, true));
        }
        $importFile = OfflineImportFile::model()->findByAttributes(['id' => $params['id'], 'platform_id' => $this->platform_id]);
        if (empty($importFile)) {
            throw new Exception('审核出借记录导入文件 不存在 error:'.print_r($params, true));
        }
        if ((3 == $params['auth_status'] && $importFile->auth_status > 0) || (3 == $importFile->auth_status && in_array($params['auth_status'], [1, 2])) || (2 == $importFile->auth_status && in_array($params['auth_status'], [1, 2, 3])) || (1 == $importFile->auth_status && in_array($params['auth_status'], [1, 2, 3]))) {
            throw new Exception('操作失败:当前状态为['.(1 == $importFile->deal_status ? $this->authStatus2Name[4] : $this->authStatus2Name[$importFile->auth_status]).']');
        }
        if (in_array($params['auth_status'], [2, 3])) {
            $res = OfflineImportContent::model()->updateAll(['update_time' => time(), 'status' => 3], "file_id = {$params['id']}");
            if (false === $res) {
                throw new Exception('取消出借记录明细 失败 请重试');
            }
        }
        //审核人
        if (in_array($params['auth_status'], [1, 2])) {
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $importFile->auth_user_name = $username;
            $importFile->auth_admin_id = $params['user_id'];
        }
        $importFile->auth_status = $params['auth_status'];
        $importFile->auth_time = time();
        $importFile->auth_admin_id = $params['user_id'];
        if (isset($params['remark'])) {
            $importFile->remark = $params['remark'];
        }
        if (false == $importFile->save()) {
            throw new Exception('审核出借记录导入文件 失败 请重试');
        }
    }

    /**
     * 审核还款计划文件.
     *
     * @param $params
     *
     * @throws Exception
     */
    public function handelUploadRepayFileAuth($params)
    {
        if (empty($params['id'])) {
            throw new Exception('审核还款计划导入文件 id 为空 error:'.print_r($params, true));
        }
        if (empty($params['user_id'])) {
            throw new Exception('审核还款计划导入文件 审核人为空 error:'.print_r($params, true));
        }
        if (!in_array($params['auth_status'], [1, 2, 3])) {
            throw new Exception('审核还款计划导入文件 审核状态不合法 error:'.print_r($params, true));
        }
        $uploadRepayFile = OfflineUploadRepayFile::model()->findByAttributes(['id' => $params['id'], 'platform_id' => $this->platform_id]);
        if (empty($uploadRepayFile)) {
            throw new Exception('审核还款计划导入文件 不存在 error:'.print_r($params, true));
        }

        if ((3 == $params['auth_status'] && $uploadRepayFile->auth_status > 0) || (3 == $uploadRepayFile->auth_status && in_array($params['auth_status'], [1, 2])) || (2 == $uploadRepayFile->auth_status && in_array($params['auth_status'], [1, 2, 3])) || (1 == $uploadRepayFile->auth_status && in_array($params['auth_status'], [1, 2, 3]))) {
            throw new Exception('操作失败:当前状态为['.(1 == $uploadRepayFile->deal_status ? $this->authStatus2Name[4] : $this->authStatus2Name[$uploadRepayFile->auth_status]).']');
        }

        if (in_array($params['auth_status'], [2, 3])) {
            $res = OfflineUploadRepayLog::model()->updateAll(['update_time' => time(), 'status' => 3], "file_id = {$params['id']}");
            if (false === $res) {
                throw new Exception('取消还款计划明细 失败 请重试');
            }
        }

        //审核人
        if (in_array($params['auth_status'], [1, 2])) {
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $uploadRepayFile->auth_user_name = $username;
            $uploadRepayFile->auth_admin_id = $params['user_id'];
        }

        $uploadRepayFile->auth_status = $params['auth_status'];
        $uploadRepayFile->auth_time = time();
        $uploadRepayFile->auth_admin_id = $params['user_id'];
        if (isset($params['remark'])) {
            $uploadRepayFile->remark = $params['remark'];
        }
        if (false == $uploadRepayFile->save()) {
            throw new Exception('审核还款计划导入文件 失败 请重试');
        }
    }

    /**
     * 审核用户账户信息文件.
     *
     * @param $params
     *
     * @throws Exception
     */
    public function handelUploadUserAccountFileAuth($params)
    {
        if (empty($params['id'])) {
            throw new Exception('审核用户账户数据导入文件 id 为空 error:'.print_r($params, true));
        }
        if (empty($params['user_id'])) {
            throw new Exception('审核用户账户数据导入文件 审核人为空 error:'.print_r($params, true));
        }
        if (!in_array($params['auth_status'], [1, 2, 3])) {
            throw new Exception('审核用户账户数据导入文件 审核状态不合法 error:'.print_r($params, true));
        }
        $uploadUserAccountFile = OfflineUploadUserAccountFile::model()->findByAttributes(['id' => $params['id'], 'platform_id' => $this->platform_id]);
        if (empty($uploadUserAccountFile)) {
            throw new Exception('审核用户账户数据导入文件 不存在 error:'.print_r($params, true));
        }

        if ((3 == $params['auth_status'] && $uploadUserAccountFile->auth_status > 0) || (3 == $uploadUserAccountFile->auth_status && in_array($params['auth_status'], [1, 2])) || (2 == $uploadUserAccountFile->auth_status && in_array($params['auth_status'], [1, 2, 3])) || (1 == $uploadUserAccountFile->auth_status && in_array($params['auth_status'], [1, 2, 3]))) {
            throw new Exception('操作失败:当前状态为['.(1 == $uploadUserAccountFile->deal_status ? $this->authStatus2Name[4] : $this->authStatus2Name[$uploadUserAccountFile->auth_status]).']');
        }

        if (in_array($params['auth_status'], [2, 3])) {
            $res = OfflineUploadUserAccountLog::model()->updateAll(['update_time' => time(), 'status' => 3], "file_id = {$params['id']}");
            if (false === $res) {
                throw new Exception('取消还款计划明细 失败 请重试');
            }
        }

        //审核人
        if (in_array($params['auth_status'], [1, 2])) {
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $uploadUserAccountFile->auth_user_name = $username;
            $uploadUserAccountFile->auth_admin_id = $params['user_id'];
        }

        $uploadUserAccountFile->auth_status = $params['auth_status'];
        $uploadUserAccountFile->auth_time = time();
        $uploadUserAccountFile->auth_admin_id = $params['user_id'];
        if (isset($params['remark'])) {
            $uploadUserAccountFile->remark = $params['remark'];
        }
        if (false == $uploadUserAccountFile->save()) {
            throw new Exception('审核用户账户数据导入文件 失败 请重试');
        }
    }

    /**
     * 获取出借记录导入文件列表.
     *
     * @param $params
     *
     * @return array
     */
    public function getImportFileList($params)
    {
        $fileList = [];
        $countFile = OfflineImportFile::model()->countBySql('select count(1) from offline_import_file where platform_id = '.$this->platform_id);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select * from offline_import_file  where platform_id = {$this->platform_id} order by id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = OfflineImportFile::model()->findAllBySql($sql);
            foreach ($_file as $item) {
                $list['id'] = $item->id;
                $list['success_num'] = $item->success_num;
                $list['total_num'] = $item->total_num;
                $list['platform_id'] = $item->platform_id;
                $list['fail_num'] = $item->fail_num;
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

        return ['countNum' => $countFile, 'list' => $fileList];
    }

    /**
     * 获取账户信息导入文件列表.
     *
     * @param $params
     *
     * @return array
     */
    public function getUserAccountFileList($params)
    {
        $fileList = [];
        $countFile = OfflineUploadUserAccountFile::model()->countBySql('select count(1) from offline_upload_user_account_file where platform_id = '.$this->platform_id);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select * from offline_upload_user_account_file  where platform_id = {$this->platform_id} order by id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = OfflineUploadUserAccountFile::model()->findAllBySql($sql);
            foreach ($_file as $item) {
                $list['id'] = $item->id;
                $list['success_num'] = $item->success_num;
                $list['total_num'] = $item->total_num;
                $list['platform_id'] = $item->platform_id;
                $list['fail_num'] = $item->fail_num;
                $list['total_amount'] = number_format($item->total_amount, 2);
                $list['success_wait_amount'] = number_format($item->success_wait_amount, 2);
                $list['fail_wait_amount'] = number_format($item->fail_wait_amount, 2);
                //处理成功加入金额
                $list['handle_success_wait_amount'] = number_format($item->handle_success_wait_amount, 2);
                //处理失败加入金额
                $list['handle_fail_wait_amount'] = number_format($item->handle_fail_wait_amount, 2);
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

        return ['countNum' => $countFile, 'list' => $fileList];
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
        return [];
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
        return [];
    }

    /**
     * 用户侧
     * 获取合同列表.
     *
     * @return array
     */
    public function getContractList()
    {
        return [];
    }

    /**
     * 校验合同参数.
     *
     * @param $params
     *
     * @return $this
     *
     * @throws Exception
     */
    public function checkContractNeedParams($params)
    {
        if (empty($params['deal_load_id'])) {
            throw new Exception('出借记录为空');
        }
        if (empty($params['user_id'])) {
            throw new Exception('出借人为空');
        }

        $this->getContractNeedParams = $params;

        return $this;
    }

    /**
     * 合同详情.
     *
     * @return array
     */
    public function getContractInfo()
    {
        return [];
    }

    /**
     * 处理用户数据的导入.
     *
     * @param $params
     *
     * @return array
     */
    public function handleUserAccount($params)
    {
        return [];
    }
}
