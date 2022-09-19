<?php

class ExchangeController extends CController
{
    public $AuditLog;
    //提示信息
    private static $codeToInfo = [
        0 => 'success',
        1000 => '网络错误，请稍后重试',
        1001 => '服务端资源不可用',
        1002 => '参数错误，请核对后重试',
        1003 => '鉴权失败，请核对后重试',
        2000 => '用户不存在，请核对后重试',
        2001 => '数据异常，请稍后重试',
        2002 => '凭证失效，请重新获取',
        2003 => '用户信息缺失，请重新登录',
        2004 => '手机号码格式错误，请重新输入',
        2005 => '请输入短信验证码',
        2006 => '短信验证码不正确，请重新输入',
        2007 => '手机号码已变更，请重新获取验证码',
        2008 => '单笔债权剩余金额不得小于5元',
        2104 => '短信验证码过期，请重新获取',
        2043 => '短信验证码不正确，请重新输入',
        2045 => '错误超过三次，请重新获取短信验证码',
        2011 => '该手机号码尚未认证',
        2012 => '兑换金额不满足起兑金额',
        2013 => '审核授权未通过，请耐心等候',
        2014 => '您暂未开通互金平台存管银行，请在互金平台开通存管账户后完成授权',
        2015 => '兑换金额与用户余额不一致',
        2025 => '选择债权条数过多，请取消不必要的债权',
        2026 => '选择债权组合异常，请尝试选择其他债权',
        2027 => '单笔债权兑换金额不得小于5元',
        2016 => '数据处理中，请稍后重试',
        2017 => '指定债权兑换缺少debtType参数',
        2018 => '改商城在数据中心尚未注册',
        2019 => '认购人已失效',
        2020 => '无可兑换债权',
        2021 => '保存债权记录失败',
        2022 => '禁止登陆',
        2023 => '请选择您需要兑换的债权',
        2024 => '请选择您需要兑换的债权类型',
        2030 => '兑换数据为空',
        2031 => '债权受让人获取失败,请联系客服',
        2032 => '兑换积分流水号重复',
        2056 => '参数有误',
        2057 => '债权需全部兑换',
        3001 => '保存协议数据失败',
        5000 => '网络异常，请稍后重试',

        6001 => '悠融债权占比低于50%，禁止兑换',
        6002 => '待还本金低于兑换金额，请重新操作',
        6003 => '下车债权总金额异常，请重新操作',
        6004 => '出借记录信息有误，请重新操作',
        6005 => '您的可兑换本金为0',
        6006 => '受让人指定失败，请稍后再试',
        6007 => '兑换失败，请联系客服',


    ];

    private static $error_info = '';

    /**
     * 响应结果.
     * @param array  $data
     * @param int    $code
     * @param string $info
     */
    private function echoJsonExit($data = [], $code = 0, $info = '')
    {
        Yii::log(' echoJsonExit data:'.print_r($data, true).' code:'.$code .' info:'.$info, 'info', __FUNCTION__);

        header('Content-type:application/json; charset=utf-8');
        $res['data'] = $data;
        $res['code'] = intval($code);
        $res['info'] = $info;
        echo exit(json_encode($res));
    }

    /**
     * 获取秘钥
     * @param $appid
     * @return array|false|mixed|string
     */
    private static function getSecretByAppId($appid)
    {
        $res = XfDebtExchangePlatform::model()->findByPk($appid);
        if (0 == $res->status) {
            $secret = '';
            self::$error_info='平台授权已经失效';
        } else {
            $secret = $res->secret;
        }
        return $secret;
    }

    /**
     * 校验请求合法性.
     * @return bool
     */
    private static function checkLegal()
    {
        Yii::log(' api checkLegal data:'.print_r($_REQUEST, true), 'info', __FUNCTION__);
        //return true;
        $data = $_REQUEST;

        if (!isset($data['appid']) || !isset($data['signature']) || !isset($data['timestamp'])) {
            return false;
        }

        if (isset($data['area_code']) && empty($data['area_code'])) {
            unset($data['area_code']);
        }

        unset($data['signature']);
        ksort($data);
        if ($data['timestamp']+300 <time()) {
            Yii::log(' api timestamp out time', 'error', __FUNCTION__);
            return  false;
        }
        $secret_key = self::getSecretByAppId($data['appid']);
        
        $str = md5(implode('', $data).$secret_key);
        Yii::log(' api checkLegal signature:'.$str, 'info', __FUNCTION__);

        if ($str === $_REQUEST['signature']) {
            return true;
        }

        return false;
    }



    /**
     * 校验接口token.
     * @param $token
     * @return bool
     */
    private static function checkApiToken()
    {
        $token = $_REQUEST['token'];
        if (empty($token)) {
            return  false;
        }

        return RedisService::getInstance()->get($token);
    }

    /**
     * 校验用户交易密码
     * @param $user_id
     * @param $password
     * @return array
     */
    private static function checkUserPassword($user_id, $password)
    {
        $password_key = 'user_exchange_password:'.$user_id;
        //交易密码校验
        $returnData = [
            'data' => [],
            'code' => 100,
            'info' => 'error',
        ];

        $userInfo = User::model()->findByPk($user_id);
        if (empty($userInfo)) {
            $returnData['info'] = '用户不存在';
            return $returnData;
        }
        $expire = strtotime('+1 year');
        $res = RedisService::getInstance()->get($password_key);
        if ($res >= 3) {
            $returnData['info'] = '密码错误次数超过限制，请前往先锋数据中心重新设置。';
            return $returnData;
        }
        $str_len = strlen($userInfo->transaction_password);
        if (24 == $str_len) {
            if ($userInfo->transaction_password != GibberishAESUtil::enc($password, Yii::app()->c->idno_key)) {
                $returnData['info'] = $res<2 ?'密码错误,剩余'.(2 - $res).'次':'密码错误次数超过限制，请前往先锋数据中心重新设置。';
                RedisService::getInstance()->set($password_key, $res + 1, $expire);
                return $returnData;
            }
        } elseif (32 == $str_len) {
            if ($userInfo->transaction_password != md5($password)) {
                $returnData['info'] = $res<2 ?'密码错误,剩余'.(2 - $res).'次':'密码错误次数超过限制，请前往先锋数据中心重新设置。';
                RedisService::getInstance()->set($password_key, $res + 1, $expire);
                return $returnData;
            }
        } else {
            $returnData['info'] = '密码错误-3';
            return $returnData;
        }
        RedisService::getInstance()->del($password_key);
        $returnData['code'] = 0;
        $returnData['info'] = 'success';
        return $returnData;
    }

    /**
     * 验证用户是否已经同意积分兑换服务协议
     * @param $params
     * @return bool
     */
    private static function checkPlatformAuth($params)
    {
        if (empty($params['openid']) || empty($params['appid'])) {
            return false;
        }
        $authUser = XfDebtExchangeAuthUser::model()->findByAttributes(['openid' => $params['openid'], 'appid' => $params['appid']]);
        return  $authUser && $authUser->agreement_status==1?true:false;
    }

    /**
     * 获取平台名称
     */
    public function actionPlatformName()
    {
        $returnData = [];
        $appid = $_GET['appid'];

        if (empty($appid)) {
            $this->echoJsonExit($returnData, 1, self::$codeToInfo[1002]); //参数不全
        }

        $platform_key = 'debt_exchange_platform_'.$appid;
        $res = RedisService::getInstance()->get($platform_key);
        if ($res) {
            $returnData['platform'] = $res;
            $this->echoJsonExit($returnData);
        }

        $platform = XfDebtExchangePlatform::model()->findByPk($appid);

        if (empty($platform)) {
            $this->echoJsonExit($returnData, 100, '平台未授权'); //
        }
        if (0 == $platform->status) {
            $this->echoJsonExit($returnData, 100, '平台授权已经失效'); //
        }

        $returnData['platform'] = $platform->name;
        RedisService::getInstance()->set($platform_key, $platform->name, 300);
        $this->echoJsonExit($returnData);
    }

    /**
     * 授权提交接口.
     */
    public function actionCheckAuth()
    {
        $params = $_REQUEST;
        $phone = trim($params['phone']);

        if (!FunctionUtil::IsMobile($phone)) {
            $this->echoJsonExit([], 2004, self::$codeToInfo[2004]);
        }
        if (!isset($params['verification_code']) || empty($params['verification_code'])) {
            $this->echoJsonExit([], 2005, self::$codeToInfo[2005]);
        }

        if (empty($params['appid'])) {
            $this->echoJsonExit([], 100, 'appid 参数缺失');
        }

        $verify_result = SmsIdentityUtils::ValidateCode($phone, $params['verification_code'], 'xf_auth_login');
        if ($verify_result['code']) {
            $this->echoJsonExit([], $verify_result['code'], Yii::app()->c->error_code_info[$verify_result['code']]);
        }

        //检查用户是否存在
        $_phone = GibberishAESUtil::enc(trim($_REQUEST['phone']), Yii::app()->c->contract['idno_key']);
        $user_info = User::model()->findByAttributes(['mobile' => $_phone, 'is_effect' => 1]);
        if (!$user_info) {
            $this->echoJsonExit([], 2000, self::$codeToInfo[2000]);
        }

        //设置获取登录授权code
        $codeInfo = AuthCodeUtil::makeCode($user_info->id, 'AL');
        if (!$codeInfo) {
            $this->echoJsonExit([], 1000, self::$codeToInfo[1000]);
        }

        $authUser = XfDebtExchangeAuthUser::model()->findByAttributes(['openid' => $user_info->id, 'appid' => $params['appid']]);
        if (empty($authUser)) {
            $authUser = new XfDebtExchangeAuthUser();
            $authUser->appid = $params['appid'];
            $authUser->openid = $user_info->id;
            $authUser->auth_status = 1;
            $authUser->auth_at = time();
            $authUser->created_at = time();
            $authUser->save();
            Yii::log('user auth login platform:'.$_REQUEST['appid'].'   '.print_r($_REQUEST, true), 'info', __CLASS__.'.'.__FUNCTION__);
        }
        $userContract = XfUserContract::model()->findByAttributes(['user_id'=>$user_info->id]);
        if (empty($userContract)) {
            //同意协议
            $userContract = new XfUserContract();
            $userContract->type =1;
            $userContract->addtime =time();
            $userContract->user_id =$user_info->id;
            $userContract->addip =FunctionUtil::ip_address();
            $userContract->data_json ='';
            $userContract->fdd_download ='';
            $userContract->oss_download ='';
            $userContract->status =0;
            $userContract->save();
        }

        $this->echoJsonExit(['code'=>$codeInfo], 0, 'success');
    }

    /**
     * 获取用户信息.
     */
    public function actionUserInfo()
    {
        Yii::log(' action  userInfo params : '.print_r($_REQUEST, true), 'info', __FUNCTION__);

        $returnData = [];
        if (!self::checkLegal()) {
            $this->echoJsonExit($returnData, 1003, self::$error_info ? :self::$codeToInfo[1003]);
        }
        $code = $_REQUEST['code'];
        if (empty($code)) {
            $this->echoJsonExit($returnData, 1002, self::$codeToInfo[1002]);
        }

        if (empty($_REQUEST['appid'])) {
            $this->echoJsonExit($returnData, 1002, '参数appid缺失');
        }
        $user_id = AuthCodeUtil::getCodeInfo($code);
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2002, self::$codeToInfo[2002]);
        }
        //用户信息
        $userInfo = User::model()->findByPk($user_id);
        if (!XfDebtExchangeUserAllowList::checkUserAllowByOpenid($user_id, $_REQUEST['appid'])) {
            $this->echoJsonExit($returnData, 100, '您尚未获得资格');
        }
        $returnData['openid'] = $userInfo->id;
        $returnData['phone'] = false;
        $returnData['real_name'] = '';
        $returnData['fdd_real_status'] = $userInfo->fdd_real_status == 1 ? 1 : 0;
        if ($_REQUEST['appid']==4) {
            $returnData['real_name'] = $userInfo->real_name;
            $returnData['phone'] = GibberishAESUtil::dec($userInfo->mobile, Yii::app()->c->contract['idno_key']);
        }

        
        //$returnData['card_id'] = !empty($userInfo->idno) ? GibberishAESUtil::dec($userInfo->idno, Yii::app()->c->contract['idno_key']) : '';

        $this->echoJsonExit($returnData, 0, self::$codeToInfo[0]);
    }

    /**********************************以上是授权登录*********************************/

    public function actionUserStatus()
    {
        $returnData = [
            'auth_status' => 0,
            'agreement_status' => 0,
            'pay_password_status' => 0,
            'debt_balance' => 0,
            'debt_type'=>[],
            'token' => '',
        ];

        if (!self::checkLegal()) {
            $this->echoJsonExit($returnData, 1003, self::$error_info ? :self::$codeToInfo[1003]);
        }

        $params = $_POST;


        if (empty($params['openid'])) {
            $this->echoJsonExit($returnData, 100, '参数openid为空');
        }
        if (empty($params['appid'])) {
            $this->echoJsonExit($returnData, 100, '参数appid为空');
        }
        if (empty($params['goodsInfo'])) {
            $this->echoJsonExit($returnData, 100, '参数goodsInfo缺失');
        }
        if (empty($params['exchange_no'])) {
            $this->echoJsonExit($returnData, 100, '参数exchange_no缺失');
        }
        if (empty($params['goods_order_no'])) {
            $this->echoJsonExit($returnData, 100, '参数goods_order_no缺失');
        }
        $commonObj = new CommonUserDebt($params['appid'], $params['openid']);
        $is_all_exchange = $commonObj->checkUserIsNeedAllExchange();

        if (!$is_all_exchange && empty($params['amount'])) {
            $this->echoJsonExit($returnData, 100, '参数amount缺失');
        }

        //法大大实名认证校验
        $userInfo = User::model()->findByPk($params['openid']);
        if (!$userInfo || $userInfo->fdd_real_status != 1 ) {
            $this->echoJsonExit($returnData, 100, '请先去用户中心实名认证');
        }

        //全量下车用户
        if ($is_all_exchange) {
            if(!FunctionUtil::float_equal($is_all_exchange, $params['amount'], 2)){
                $this->echoJsonExit($returnData, 100, '兑换金额有误');
            }
            //充提差限制
            $user_sql = "SELECT * FROM xf_user_recharge_withdraw WHERE user_id = {$params['openid']}  ";
            $user_recharge_info = Yii::app()->db->createCommand($user_sql)->queryRow();
            if(!$user_recharge_info || FunctionUtil::float_bigger_equal(0, $user_recharge_info['ph_increase_reduce'], 2)){
                $this->echoJsonExit($returnData, 100, '用户充提差小于等于0，禁止兑换');
            }
        }

        if (empty($params['redirect_url'])) {
            $this->echoJsonExit($returnData, 100, '参数redirect_url缺失');
        }
        
        $authUser = XfDebtExchangeAuthUser::model()->findByAttributes(['openid' => $params['openid'], 'appid' => $params['appid']]);
        if (empty($authUser)) {
            $this->echoJsonExit($returnData, 100, '用户授权信息缺失，请重新授权');
        }

        if ($authUser->auth_status==0) {
            $this->echoJsonExit($returnData, 100, '用户授权信息失效，请重新授权');
        }

        $token = md5(json_encode($_POST));
        RedisService::getInstance()->set($token, json_encode($_POST), 6 * 3600);
        RedisService::getInstance()->set($params['exchange_no'], json_encode($_POST), 3 * 3600);
        $returnData['token'] = $token;

        $returnData['auth_status'] = $authUser->auth_status;
        $returnData['agreement_status'] = $authUser->agreement_status;
        $returnData['pay_password_status'] = User::model()->findByPk($params['openid'])->transaction_password ? 1 : 0;
        $aboutDebt = new AboutUserDebtV2($params['openid'], $params['appid'], $params['area_code']?:'');
        $result1 = $aboutDebt->getUserSumAccountAndTotalTender();
        $result2 = $aboutDebt->getUserSumAccountAndTotalTenderPH();

//            $offlineDebt3 = new AboutUserOfflineDebt($params['openid'],3,$params['appid'],$params['area_code']?:'');
//
//            $result3 = $offlineDebt3->getUserSumAccountAndTotalTender();

        $offlineDebt4 = new AboutUserOfflineDebt($params['openid'], 4, $params['appid'], $params['area_code']?:'');

        $result4 = $offlineDebt4->getUserSumAccountAndTotalTender();

        // $offlineDebt5 = new AboutUserOfflineDebt($params['openid'], 5, $params['appid'], $params['area_code']?:'');
        // $result5 = $offlineDebt5->getUserSumAccountAndTotalTender();
        $returnData['debt_balance'] = $result1['total_account']+$result2['total_account']+$result4['total_account'];

        $debt_type = [];
        if ($result2['total_account'] > 0) {
            $debt_type['2']='普惠';
        }
        if ($result1['total_account'] > 0) {
            $debt_type['1']='尊享';
        }
//            if($result3['total_account'] > 0){
//                $debt_type['3']='工厂微金';
//            }
        if ($result4['total_account'] > 0) {
            $debt_type['4']='智多新';
        }
        // if ($result5['total_account'] > 0) {
        //     $debt_type['5']='交易所';
        // }
        if (empty($debt_type)) {
            $debt_type['0']='无债权';
        }
        $returnData['ph_total_amount'] = 0;
        
        if ($is_all_exchange) {
            $aboutDebt->is_not_check_white_list = true;
            $offlineDebt4->is_not_check_white_list = true;
            $result2 = $aboutDebt->getUserSumAccountAndTotalTenderPH();
            $result4 = $offlineDebt4->getUserSumAccountAndTotalTender();

            $ph_total_amount = $result2['total_account']+$result4['total_account'];
          
            if ($ph_total_amount > 0) {
                $returnData['ph_total_amount'] = $ph_total_amount;
                $debt_type = [];
                $debt_type['99']='普惠(含智多新)';
            }
        }

        $returnData['debt_type'] = $debt_type;
        $this->echoJsonExit($returnData);
    }

    public function actionUserPasswordStatus()
    {
        Yii::log(' get user password status : '.print_r($_POST, true), 'info', __FUNCTION__);

        $params = $_POST;
        $returnData['pay_password_status'] = 0;
        if (!self::checkApiToken()) {
            $this->echoJsonExit($returnData, 100, 'token校验失败');
        }

        if (empty($params['openid'])) {
            $this->echoJsonExit($returnData, 100, '参数openid为空');
        }
        if (empty($params['appid'])) {
            $this->echoJsonExit($returnData, 100, '参数appid为空');
        }
        $returnData['pay_password_status'] = User::model()->findByPk($params['openid'])->transaction_password ? 1 : 0;
        $this->echoJsonExit($returnData);
    }
    /**
     * 同意协议.
     */
    public function actionDoAgreement()
    {
        $returnData = [];
        if (!self::checkApiToken()) {
            $this->echoJsonExit($returnData, 100, 'token校验失败');
        }
        $params = $_POST;
        if (empty($params['openid'])) {
            $this->echoJsonExit($returnData, 100, '参数openid为空');
        }
        if (empty($params['appid'])) {
            $this->echoJsonExit($returnData, 100, '参数appid为空');
        }
        $authUser = XfDebtExchangeAuthUser::model()->findByAttributes(['openid' => $params['openid'], 'appid' => $params['appid']]);
        if (empty($authUser)) {
            $this->echoJsonExit($returnData, 100, '尚未授权');
        }
        if (1 == $authUser->agreement_status) {
            $this->echoJsonExit($returnData, 100, '已经同意协议');
        }
        $authUser->agreement_status = 1;
        $authUser->agreement_at = time();
        $authUser->save();
        if (false == $authUser->save()) {
            $this->echoJsonExit($returnData, 100, '网络错误，请稍后重试');
        }
        $this->echoJsonExit($returnData, 0, '操作成功！');
    }


    /**
     * 用户可兑换积分的债权列表.
     */
    public function actionDebtList()
    {
        $returnData = ['list' => [], 'total' => 0];

        if (!$_tokenInfo = self::checkApiToken()) {
            $this->echoJsonExit($returnData, 100, 'token校验失败');
        }
        $tokenInfo = json_decode($_tokenInfo, true);
        $user_id = $tokenInfo['openid'];
        //未登录
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        $platform = XfDebtExchangePlatform::model()->findByPk($tokenInfo['appid']);

        if (empty($platform)) {
            $this->echoJsonExit($returnData, 100, '平台未授权'); //
        }
        if (0 == $platform->status) {
            $this->echoJsonExit($returnData, 100, '平台授权已经失效'); //
        }

        if (!in_array($_POST['debt_type'], [0,1,2,3,4,5,99])) {
            $this->echoJsonExit($returnData, 100, '请选择债权类型'); //
        }

        //法大大实名认证校验
        $userInfo = User::model()->findByPk($user_id);
        if (!$userInfo || $userInfo->fdd_real_status != 1 ) {
            $this->echoJsonExit($returnData, 100, '请先去用户中心实名认证');
        }


        if (1 == $_POST['debt_type']) {
            $aboutDebt = new AboutUserDebtV2($user_id, $tokenInfo['appid'], $tokenInfo['area_code']?:'');
            $result = $aboutDebt->getUserDebtList($_POST);
        } elseif (2 == $_POST['debt_type']) {
            $aboutDebt = new AboutUserDebtV2($user_id, $tokenInfo['appid'], $tokenInfo['area_code']?:'');
            $result = $aboutDebt->getUserDebtListPH($_POST);
        } elseif (in_array($_POST['debt_type'], [3,4,5])) {
            $aboutDebt= new AboutUserOfflineDebt($user_id, $_POST['debt_type'], $tokenInfo['appid'], $tokenInfo['area_code']?:'');
            $result = $aboutDebt->getUserOfflineDebtList($_POST);
        } elseif (99 == $_POST['debt_type']) {
            $_POST['limit'] = 10000;
            $aboutUserOfflineDebt= new AboutUserOfflineDebt($user_id, 4, $tokenInfo['appid'], '');
            $aboutUserOfflineDebt->is_not_check_white_list = true;
            $result1 = $aboutUserOfflineDebt->getUserOfflineDebtList($_POST);
            $aboutUserDebtV2 = new AboutUserDebtV2($user_id, $tokenInfo['appid'], '');
            $aboutUserDebtV2->is_not_check_white_list = true;
            $result2 = $aboutUserDebtV2->getUserDebtListPH($_POST);
        
            $result['data']['list']= array_merge($result1['data']['list'], $result2['data']['list']);
            $result['data']['total']=$result1['data']['total'] + $result2['data']['total'];
        } else {
            $result['data']['list']=[];
            $result['data']['total']=0;
        }



        $returnData['list'] = $result['data']['list'];
        $returnData['total'] = $result['data']['total'];
        $this->echoJsonExit($returnData, 0, self::$codeToInfo[0]);
    }

    /**
     * 债权兑换积分提交接口.
     */
    public function actionDebtCommit()
    {
        Yii::log(' commit order params : '.print_r($_POST, true), 'info', __FUNCTION__);
        $returnData = [];
        if (!$_tokenInfo = self::checkApiToken()) {
            $this->echoJsonExit($returnData, 100, 'token校验失败');
        }
        $tokenInfo = json_decode($_tokenInfo, true);
        //未登录
        $user_id = $tokenInfo['openid'];
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        $appid = $_POST['appid'];

        if (!$appid) {
            $this->echoJsonExit($returnData, 100, 'appid 参数为空');
        }

        $platform = XfDebtExchangePlatform::model()->findByPk($tokenInfo['appid']);

        if (empty($platform)) {
            $this->echoJsonExit($returnData, 100, '平台未授权'); //
        }
        if (0 == $platform->status) {
            $this->echoJsonExit($returnData, 100, '平台授权已经失效'); //
        }
      
        if (!XfDebtExchangeUserAllowList::checkUserAllowByOpenid($user_id, $appid)) {
            $this->echoJsonExit($returnData, 100, '您尚未获得该商城兑换积分资格');
        }
        $orderInfo = json_decode(RedisService::getInstance()->get($_POST['exchange_no']), true);
        $_POST['goodsInfo'] = $orderInfo['goodsInfo'];
        $_POST['goods_order_no'] = $orderInfo['goods_order_no'];

        if (!self::checkPlatformAuth(['appid'=>$appid,'openid'=>$user_id])) {
            $this->echoJsonExit($returnData, 100, '您尚未同意服务协议');
        }

        //法大大实名认证校验
        $userInfo = User::model()->findByPk($user_id);
        if (!$userInfo || $userInfo->fdd_real_status != 1 ) {
            $this->echoJsonExit($returnData, 100, '请先去用户中心实名认证');
        }

        $res = self::checkUserPassword($user_id, $_POST['password']);
        if ($res['code']) {
            $this->echoJsonExit($returnData, 100, $res['info']);
        }

        if (in_array($_POST['debt_type'], [1,2])) {
            $aboutDebt = new AboutUserDebtV2($user_id, $tokenInfo['appid'], $tokenInfo['area_code']?:'');
            $result = $aboutDebt->debtOrderCommit($_POST);
        } elseif (in_array($_POST['debt_type'], [3,4,5])) {
            $aboutDebt = new AboutUserOfflineDebt($user_id, $_POST['debt_type'], $tokenInfo['appid'], $tokenInfo['area_code']?:'');
            $result = $aboutDebt->debtOrderCommit($_POST);
        } elseif ($_POST['debt_type'] == 99) {
            $aboutDebt = new CommonUserDebt($tokenInfo['appid'], $user_id, '');
            $result = $aboutDebt->debtOrderCommit($_POST);
        }
        $this->echoJsonExit($result['contract_url'], $result['code'], $result['info']?:self::$codeToInfo[$result['code']]);
    }

    /**
     * 商城调用
     * 获取订单状态
     */
    public function actionResult()
    {
        Yii::log(' action  order result : '.print_r($_POST, true), 'info', __FUNCTION__);

        $returnData = [];
        if (!isset($_REQUEST['admin']) && !self::checkLegal()) {
            $this->echoJsonExit($returnData, 1003, self::$codeToInfo[1003]);
        }
        if (empty($_REQUEST['exchange_no'])) {
            $this->echoJsonExit($returnData, 1002, self::$codeToInfo[1002]);
        }
        $params = $_REQUEST;
        $aboutDebt = new AboutUserDebtV2($params['openid'], $params['appid']);
        $orderStatus = $aboutDebt->getOrderStatus($params);
        Yii::log(' order info '.print_r($orderStatus, true), 'info', 'openApi.debtExchange.getOrdersStatus');
        $this->echoJsonExit($orderStatus['data'], $orderStatus['code'], $orderStatus['info']);
    }

    /**
     * 校验手机号码是否在白名单
     */
    public function actionCheckPhone()
    {
        $returnData=['is_in_allow_list'=>false,'fdd_real_status' =>0];
        if (!self::checkLegal()) {
             $this->echoJsonExit($returnData, 1003, self::$codeToInfo[1003]);
        }
        if (empty($_REQUEST['phone']) || empty($_REQUEST['appid'])) {
            $this->echoJsonExit($returnData, 100, '参数错误，请核对');
        }
        $user_info = XfDebtExchangeUserAllowList::newCheckUserAllowByPhone($_REQUEST['phone'], $_REQUEST['appid']);
        if ($user_info) {
            $returnData['is_in_allow_list'] = true;
            $returnData['fdd_real_status'] = $user_info->fdd_real_status == 1 ? 1 : 0;
        }

        $this->echoJsonExit($returnData, 0, 'success');
    }


    public function actionNotify()
    {
        echo 'SUCCESS';
    }

    public function actionUserXiaCheDebtAmount()
    {
        $returnData = ['amount'=>0];
        if (!self::checkLegal()) {
            $this->echoJsonExit($returnData, 1003, self::$codeToInfo[1003]);
        }
        $user_id = $_GET['openid'];
        if (empty($user_id)) {
            $this->echoJsonExit($returnData, 100, '参数错误，请核对');
        }
        $user_info  = User::model()->findByAttributes(['id' => $user_id, 'is_effect' => 1]);
        if (empty($user_info)) {
            $this->echoJsonExit($returnData, 100, '用户不存在');
        }
        //法大大实名认证校验
        if ($user_info['fdd_real_status'] != 1 ) {
            $this->echoJsonExit($returnData, 100, '请先去用户中心实名认证');
        }

        if (!XfDebtExchangeUserAllowList::checkUserAllowByOpenid($user_id, $_GET['appid'])) {
            $this->echoJsonExit($returnData, 100, '您尚未获得资格');
        }
        $debtInfo = (new AboutUserDebtV2($user_id, $_GET['appid']))->getUserXcAmount($user_id);
        if ($debtInfo['code']) {
            $returnData['amount'] = 0;
        } else {
            $returnData['amount'] = $debtInfo['data']['wait_capital'];
        }
        Yii::log("getUserXcAmount user_id=[$user_id]: debtInfo:[".print_r($debtInfo, true)."]", 'info');


        $this->echoJsonExit($returnData, 0, 'success');
    }

    public function actionUserXiaCheDebtExchange()
    {
        $this->echoJsonExit([], 1000, '未接入合同自主签署，暂不能兑换');

        $returnData = [
            
        ];
        if (!self::checkLegal()) {
            $this->echoJsonExit($returnData, 1003, self::$codeToInfo[1003]);
        }
        $user_id = $_POST['openid'];

        if (empty($user_id)) {
            $this->echoJsonExit($returnData, 100, 'openid 不存在');
        }

        if (empty($_POST['appid'])) {
            $this->echoJsonExit($returnData, 100, 'appid 不存在');
        }

        if (empty($_POST['amount'])) {
            $this->echoJsonExit($returnData, 100, '兑换金额 不存在');
        }

        if (empty($_POST['exchange_no'])) {
            $this->echoJsonExit($returnData, 100, '债权兑换流水号 不存在');
        }

        $user_info  = User::model()->findByAttributes(['id' => $user_id, 'is_effect' => 1]);
        if (empty($user_info)) {
            $this->echoJsonExit($returnData, 100, '用户不存在');
        }

        //法大大实名认证校验
        if ($user_info['fdd_real_status'] != 1 ) {
            $this->echoJsonExit($returnData, 100, '请先去用户中心实名认证');
        }

        if (!XfDebtExchangeUserAllowList::checkUserAllowByOpenid($user_id, $_POST['appid'])) {
            $this->echoJsonExit($returnData, 100, '您尚未获得资格');
        }

        $debtInfo = (new AboutUserDebtV2($user_id, $_POST['appid']))->handelUserXcAmount($user_id, $_POST['amount'], $_POST['exchange_no']);


        $this->echoJsonExit($returnData, $debtInfo['code'], self::$codeToInfo[$debtInfo['code']]);
    }

    public function actionSpecialArea()
    {
        $returnData = [];
        if (!self::checkLegal()) {
            $this->echoJsonExit($returnData, 1003, self::$codeToInfo[1003]);
        }
        $params = $_GET;
        if (empty($params['openid'])) {
            $this->echoJsonExit($returnData, 100, '参数openid为空');
        }
        if (empty($params['appid'])) {
            $this->echoJsonExit($returnData, 100, '参数appid为空');
        }
        $returnData = (new CommonUserDebt($params['appid'], $params['openid']))->getUserSpecialAreaListFromCache();
        $this->echoJsonExit($returnData, 0, 'success');
    }

    public function actionCheckUserAllExchange()
    {
        $returnData = [];
        if (!self::checkLegal()) {
            $this->echoJsonExit($returnData, 1003, self::$codeToInfo[1003]);
        }
        $params = $_GET;
        if (empty($params['openid'])) {
            $this->echoJsonExit($returnData, 100, '参数openid为空');
        }
        if (empty($params['appid'])) {
            $this->echoJsonExit($returnData, 100, '参数appid为空');
        }
        $returnData['debt_balance'] = 0;
        $commonObj = new CommonUserDebt($params['appid'], $params['openid']);
        $amount = $commonObj->checkUserIsNeedAllExchange();
        $returnData['is_all_exchange'] = $amount > 0 ? true:false;
        if ($returnData['is_all_exchange']) {
            $returnData['debt_balance'] = $amount;
        }
        $this->echoJsonExit($returnData, 0, 'success');
    }

    public function actionQuery()
    {
        $params['openid'] = $_GET['openid'];
        $params['appid'] = $_GET['appid'];
        $params['area_code'] = $_GET['area_code'];
        $is_all_exchange = $_GET['is_all_exchange'];
       
        $aboutDebt = new AboutUserDebtV2($params['openid'], $params['appid'], $params['area_code']?:'');
        $result1 = $aboutDebt->getUserSumAccountAndTotalTender();
        $result2 = $aboutDebt->getUserSumAccountAndTotalTenderPH();

//            $offlineDebt3 = new AboutUserOfflineDebt($params['openid'],3,$params['appid'],$params['area_code']?:'');
//
//            $result3 = $offlineDebt3->getUserSumAccountAndTotalTender();

        $offlineDebt4 = new AboutUserOfflineDebt($params['openid'], 4, $params['appid'], $params['area_code']?:'');

        $result4 = $offlineDebt4->getUserSumAccountAndTotalTender();

        // $offlineDebt5 = new AboutUserOfflineDebt($params['openid'], 5, $params['appid'], $params['area_code']?:'');
        // $result5 = $offlineDebt5->getUserSumAccountAndTotalTender();
        $returnData['debt_balance'] = $result1['total_account']+$result2['total_account']+$result4['total_account'];

        $debt_type = [];
        if ($result2['total_account'] > 0) {
            $debt_type['2']='普惠';
        }
        if ($result1['total_account'] > 0) {
            $debt_type['1']='尊享';
        }
//            if($result3['total_account'] > 0){
//                $debt_type['3']='工厂微金';
//            }
        if ($result4['total_account'] > 0) {
            $debt_type['4']='智多新';
        }
        // if ($result5['total_account'] > 0) {
        //     $debt_type['5']='交易所';
        // }
        if (empty($debt_type)) {
            $debt_type['0']='无债权';
        }
        $returnData['ph_total_amount'] = 0;
        
        if ($is_all_exchange) {
            $aboutDebt->is_not_check_white_list = true;
            $offlineDebt4->is_not_check_white_list = true;
            $result2 = $aboutDebt->getUserSumAccountAndTotalTenderPH();
            $result4 = $offlineDebt4->getUserSumAccountAndTotalTender();

            $ph_total_amount = $result2['total_account']+$result4['total_account'];
          
            if ($ph_total_amount > 0) {
                $returnData['ph_total_amount'] = $ph_total_amount;
                $debt_type = [];
                $debt_type['99']='普惠(含智多新)';
            }
        }

        $returnData['debt_type'] = $debt_type;
        $this->echoJsonExit($returnData, 0, 'success');
    }
}
