<?php
class ShopController extends CController
{
    public $AuditLog;

    public function echoJsonExit($data = array(), $code = 0, $info = "")
    {
        header("Content-type:application/json; charset=utf-8");
        $res['data'] = $data;
        $res['code'] = intval($code);
        $res['info'] = $info;
        echo exit(json_encode($res));
    }

    //提示信息
    private static $codeToInfo = [
        0    => '请求成功',
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
        2008 => '单笔债权剩余金额不得小于100元',
        2104 => '短信验证码过期，请重新获取',
        2043 => '短信验证码不正确，请重新输入',
        2045 => '错误超过三次，请重新获取短信验证码',
        2011 => '该手机号码尚未认证',
        2012 => '兑换金额不满足起兑金额',
        2013 => '审核授权未通过，请耐心等候',
        2014 => '您暂未开通互金平台存管银行，请在互金平台开通存管账户后完成授权',
        2015 => '兑换金额与用户余额不一致',
        2016 => '数据处理中，请稍后重试',
        2017 => '指定债权兑换缺少debtType参数',
        2020 => '无可兑换债权',
        2021 => '保存债权记录失败',
        2022 => '禁止登陆',
        2030 => '兑换数据为空',
        2031 => '债权受让人获取失败,请联系客服',
        3001 => '保存协议数据失败',
    ];

    /**
     * 获取秘钥
     * @param $appKey
     * @return array|false|mixed|string
     */
    private static function getSecretByAppKey($appKey)
    {
        return ConfUtil::get($appKey . '-secret');
    }

    /**
     * 校验请求合法性
     * @return bool
     */
    private function CheckLegal()
    {
        //return true;//todo 临时不校验 更新秘钥
        $data = $_REQUEST;
        if (!isset($data['WxAppKey']) || !isset($data['signature'])) {
            return false;
        }
        unset($data['signature']);
        ksort($data);
        $secret_key = self::getSecretByAppKey($data['WxAppKey']);
        $str        = md5(implode('', $data) . $secret_key);
        if ($str === $_REQUEST['signature']) {
            return true;
        }
        return false;
    }

    /**********************公共方法*****结束******************************/

    /**
     * 对外调用公共接口
     */
    public function actionService()
    {
        Yii::log('openApi service params ' . print_r($_REQUEST, true), 'info', __CLASS__ . '.' . __FUNCTION__);

        $returnData = [];
        if (!$this->checkLegal()) {
            $this->echoJsonExit($returnData, 1003, self::$codeToInfo[1003]);
        };
        $data = $_REQUEST;
        if (!empty($_REQUEST['uid'])) {
            $user_info = User::model()->findByPk(intval($_REQUEST['uid']));
            if (!$user_info) {
                $this->echoJsonExit($returnData, 2000, self::$codeToInfo[2000].$_REQUEST['uid']);
            }
            $_SESSION['uid'] = $_REQUEST['uid'];
        }

        switch (trim($data['serviceName'])) {
            case 'USER_INFO_BY_HASH_ID':
                $this->getUserInfoByHashId(); //用户信息
                break;
            case 'USER_INFO_BY_PHONE':
                $this->getUserInfoByPhone(); //用户信息
                break;
            case 'USER_DEBT_AMOUNT':
                $this->getUserAccount(); //债权总额
                break;
            case 'USER_DEBT_LIST':
                $this->getUserDebtList(); //可兑换债权的列表
                break;
            case 'USER_DEBT_COMMIT':
                $this->userDebtCommit(); //兑换提交
                break;
            case 'USER_ORDER_DEBT_INFO':
                $this->getOrdersStatus(); //兑换结果
                break;
            case 'ORDERS_DEBT_STATUS':
                $this->getOrdersStatus(); //批量获取兑换结果
                break;
            case 'CHECK_ORDERS_ACCOUNT':
                $this->checkOrdersAccount(); //批量校验订单金额
                break;
            case 'USER_DEC':
                $this->userDec($data['respData']);//用户信息解密
                break;
            case 'USER_ENC':
                $this->userEnc($data['respData']);//用户信息解密
                break;
            case 'DEBT_SUBTOTAL':
                $this->getStatisticsData();//数据统计
                break;
            case 'USER_FACE_AUTH_STATUS':
                $this->getUserFaceAuthInfo(); //人脸认证授权状态
                break;
            case 'SAVE_USER_PHOTO_AUTH':
                $this->savePhotoAuthInfo();
                break;
            case 'USER_SPECIAL_DEBT_ACCOUNT':
                $this->getUserSpecialDebtAccount(); //下车 可兑换债权的金额
                break;
            case 'USER_SPECIAL_DEBT_COMMIT':
                $this->userSpecialDebtCommit(); //下车 兑换提交
                break;
            case 'USER_DEBT_ROLLBACK':
                $this->userDebtRollback();//债权回退CHECK_USER_LOGIN_BY_PHONE
                break;
            case 'CHECK_USER_LOGIN_BY_PHONE':
                $this->checkUserLogin();//
                break;
            case 'USER_DEBT_CONFIRM':
                $this->confirmUserDebt();
                break;
            case 'USER_AGREEMENT_INFO':
                $this->getUserAgreementInfo();
                break;
            default:
                echo '服务端资源不可用';
                //记录日志 通知失败
                Yii::log('openApi service all undefined data:' . print_r($returnData, true), "error", __FUNCTION__);
                break;

        }
    }

    /***************************债权兑换相关*******开始**********************************/

    /**
     * 用户可兑换的债权总金额
     */
    private function getUserAccount()
    {
        $returnData = [];
        $user_id    = $_SESSION['uid'];
        //未登录
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        $aboutDebt = new AboutUserDebt($user_id);
        $aboutDebt->is_from_debt_confirm = true;

        //指定债权
        if (isset($_REQUEST['zx_borrow_ids']) && !empty($_REQUEST['zx_borrow_ids']) && (!isset($_REQUEST['ph_borrow_ids']) || empty($_REQUEST['ph_borrow_ids']))) {
            $aboutDebt->zx_borrow_ids = $_REQUEST['zx_borrow_ids'];
            $result2['total_account'] = 0;
            $result1 = $aboutDebt->getUserSumAccountAndTotalTender($_REQUEST);
        } elseif (isset($_REQUEST['ph_borrow_ids']) && !empty($_REQUEST['ph_borrow_ids']) && (!isset($_REQUEST['zx_borrow_ids']) || empty($_REQUEST['zx_borrow_ids']))) {
            $aboutDebt->ph_borrow_ids = $_REQUEST['ph_borrow_ids'];
            $result1['total_account'] = 0;
            $result2 = $aboutDebt->getUserSumAccountAndTotalTenderPH($_REQUEST);
        } elseif (isset($_REQUEST['zx_borrow_ids']) && !empty($_REQUEST['zx_borrow_ids']) && isset($_REQUEST['ph_borrow_ids']) && !empty($_REQUEST['ph_borrow_ids'])) {
            $aboutDebt->zx_borrow_ids = $_REQUEST['zx_borrow_ids'];
            $aboutDebt->ph_borrow_ids = $_REQUEST['ph_borrow_ids'];
            $result1 = $aboutDebt->getUserSumAccountAndTotalTender($_REQUEST);
            $result2 = $aboutDebt->getUserSumAccountAndTotalTenderPH($_REQUEST);
        } else {
            $result1 = $aboutDebt->getUserSumAccountAndTotalTender($_REQUEST);
            $result2 = $aboutDebt->getUserSumAccountAndTotalTenderPH($_REQUEST);
        }


        $returnData['amount'] = bcadd($result1['total_account'], $result2['total_account'], 2);
        $debt_type            = 0;
        if ($result1['total_account'] > 0) {
            $debt_type += 1;
        }
        if ($result2['total_account'] > 0) {
            $debt_type += 2;
        }
        $returnData['debt_type'] = $debt_type;
        $this->echoJsonExit($returnData, 0, self::$codeToInfo[0]);
    }

    /**
     * 用户可兑换积分的债权列表
     */
    private function getUserDebtList()
    {
        $returnData = ['list' => [], 'total' => 0];
        $user_id    = $_SESSION['uid'];
        //未登录
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        $aboutDebt = new AboutUserDebt($user_id);

        //指定债权
        if (isset($_REQUEST['zx_borrow_ids']) && !empty($_REQUEST['zx_borrow_ids']) && (!isset($_REQUEST['ph_borrow_ids']) || empty($_REQUEST['ph_borrow_ids']))) {
            $aboutDebt->zx_borrow_ids = $_REQUEST['zx_borrow_ids'];
            $aboutDebt->ph_borrow_ids = '-1';

        } elseif (isset($_REQUEST['ph_borrow_ids']) && !empty($_REQUEST['ph_borrow_ids']) && (!isset($_REQUEST['zx_borrow_ids']) || empty($_REQUEST['zx_borrow_ids']))) {
            $aboutDebt->ph_borrow_ids = $_REQUEST['ph_borrow_ids'];
            $aboutDebt->zx_borrow_ids = '-1';

        } elseif (isset($_REQUEST['zx_borrow_ids']) && !empty($_REQUEST['zx_borrow_ids']) && isset($_REQUEST['ph_borrow_ids']) && !empty($_REQUEST['ph_borrow_ids'])) {
            $aboutDebt->zx_borrow_ids = $_REQUEST['zx_borrow_ids'];
            $aboutDebt->ph_borrow_ids = $_REQUEST['ph_borrow_ids'];
        }


        if (isset($_REQUEST['debtType']) && $_REQUEST['debtType'] == 2) {
            $result = $aboutDebt->getUserDebtListPH($_REQUEST);
        } else {
            $result = $aboutDebt->getUserDebtList($_REQUEST);
        }
        $returnData['list']     = $result['data']['list'];
        $returnData['total']    = $result['data']['total'];
        $this->echoJsonExit($returnData, 0, self::$codeToInfo[0]);
    }

    /**
     * 债权兑换积分提交接口
     */
    private function userDebtCommit()
    {
        Yii::log('shop commit order params : ' . print_r($_REQUEST, true), 'info', __FUNCTION__);

        $returnData = ['redirectUrl' => ''];
        //未登录
        $user_id = $_SESSION['uid'];
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        $aboutDebt = new AboutUserDebt($user_id);
        //指定债权
        if (isset($_REQUEST['zx_borrow_ids']) && !empty($_REQUEST['zx_borrow_ids'])) {
            $aboutDebt->zx_borrow_ids = $_REQUEST['zx_borrow_ids'];
        }
        if (isset($_REQUEST['ph_borrow_ids']) && !empty($_REQUEST['ph_borrow_ids'])) {
            $aboutDebt->ph_borrow_ids = $_REQUEST['ph_borrow_ids'];
        }

        $result   = $aboutDebt->debtOrderCommit($_REQUEST);
        $this->echoJsonExit($result['data'], $result['code'], self::$codeToInfo[$result['code']]);
    }

    /**
     * 获取订单状态
     */
    private function getOrdersStatus()
    {
        $returnData = [];
        $orders     = json_decode($_REQUEST['orderNumber'], true);
        if (empty($orders)) {
            $this->echoJsonExit($returnData, 1002, self::$codeToInfo[1002]);
        }
        if (count($orders) == 1) {
            $orders = [current($orders)];
        }
        $aboutDebt   = new AboutUserDebt();
        $orderStatus = $aboutDebt->getOrderStatus($orders);
        Yii::log(' order info ' . print_r($orderStatus, true), 'info', 'openApi.debtExchange.getOrdersStatus');
        $this->echoJsonExit($orderStatus['data'], $orderStatus['code'], $orderStatus['info']);
    }

    /**
     * 对账用
     * 校验订单兑换金额
     */
    private function checkOrdersAccount()
    {
        $returnData       = [];
        $startTime        = intval($_REQUEST['startTime']);
        $endTime          = intval($_REQUEST['endTime']);
        $page             = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $limit            = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 100;
        $onlyGetTotalPage = isset($_REQUEST['onlyGetTotalPage']) && $_REQUEST['onlyGetTotalPage']==1 ? 1 : 0;
        Yii::log('check order params:' . print_r($_REQUEST, true), 'info', 'openApi.debtExchange.checkOrdersAccount');
        if (!$startTime || !$endTime) {
            $this->echoJsonExit($returnData, 1002, self::$codeToInfo[1002]);
        }
        $aboutDebt   = new AboutUserDebt();
        $orderStatus = $aboutDebt->checkOrdersAccount($startTime, $endTime, $page, $limit, $onlyGetTotalPage);
        Yii::log(' check order info ' . print_r($orderStatus, true), 'info', 'openApi.debtExchange.checkOrdersAccount');
        $this->echoJsonExit($orderStatus['data'], $orderStatus['code'], $orderStatus['info']);
    }

    /***************************债权兑换相关*******结束**********************************/

    /**
     * 根据hashid获取用户信息
     */
    private function getUserInfoByHashId()
    {
        $returnData = array();
        $user_info  = User::model()->findByPk(intval($_REQUEST['hashid']));
        Yii::log('shop getUserInfoByHashId user_info : ' . print_r($user_info, true), 'info', __FUNCTION__);
        if ($user_info) {
            $returnData['uid']      = $user_info->id;
            $returnData['head']     = ''; //头像
            $returnData['phone']    = GibberishAESUtil::dec($user_info->mobile, Yii::app()->c->contract['idno_key']);
            $returnData['username'] = $user_info->user_name;
            $returnData['idno']      = !empty($user_info->idno) ? GibberishAESUtil::dec($user_info->idno, Yii::app()->c->contract['idno_key']) : '';
            Yii::log('shop getUserInfoByPhone return data : ' . print_r($returnData, true), 'info', __FUNCTION__);
            $this->echoJsonExit($returnData, 0, self::$codeToInfo[0]);
        } else {
            $this->echoJsonExit($returnData, 2000, self::$codeToInfo[2000]);
        }
    }

    private function getUserInfoByPhone()
    {
        $returnData = array();
        $_phone     = GibberishAESUtil::enc(trim($_REQUEST['phone']), Yii::app()->c->contract['idno_key']);
        $user_info  = User::model()->findByAttributes(['mobile' => $_phone, 'is_effect' => 1]);
        if ($user_info) {
            $returnData['uid']       = $user_info->id;
            $returnData['head']      = ''; //头像
            $returnData['phone']     = trim($_REQUEST['phone']);
            $returnData['username']  = $user_info->user_name;
            $returnData['real_name'] = $user_info->real_name;
            $returnData['idno']      = !empty($user_info->idno) ? GibberishAESUtil::dec($user_info->idno, Yii::app()->c->contract['idno_key']) : '';
            Yii::log('shop getUserInfoByPhone return data : ' . print_r($returnData, true), 'info', __FUNCTION__);
            $this->echoJsonExit($returnData, 0, self::$codeToInfo[0]);
        } else {
            $this->echoJsonExit($returnData, 2000, self::$codeToInfo[2000]);
        }
    }

    /**
     * 获取用户人脸认证授权状态
     */
    private function getUserFaceAuthInfo()
    {
        $returnData['status'] = 0;
        $user_id              = $_SESSION['uid'];
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        //登录成功后校验是否授权
        $userAuth = new AboutUserAuth($user_id);
        $authInfo = $userAuth->getUserAuthInfo();
        if ($authInfo) {
            $returnData['status'] = 1;
        }
        $this->echoJsonExit($returnData, 0, self::$codeToInfo[0]);
    }

    /**
     * 上传照片
     */
    private function savePhotoAuthInfo()
    {
        $data       = $_REQUEST;
        $user_id    = intval($_REQUEST['hashid']);
        $user_info  = User::model()->findByPk(intval($_REQUEST['hashid']));
        if (!$user_info) {
            $this->echoJsonExit([], 2000, self::$codeToInfo[2000]);
        }
        $aboutAuth  = new AboutUserAuth($user_id);
        $result     = $aboutAuth->savePhotoAuthInfo($data);
        $this->echoJsonExit($result['data'], $result['code'], $result['info']);
    }

    public function actionGetUserDebtAmount()
    {
        $user_id = $_SESSION['uid'] = $_REQUEST['user_id'];
        $this->getUserAccount();
    }

    public function actionGetUserDebtList()
    {
        $user_id = $_SESSION['uid'] = $_REQUEST['user_id'];
        $this->getUserDebtList();
    }

    public function actionGetPhone()
    {
        $returnData['phone'] = GibberishAESUtil::dec($_REQUEST['phone'], Yii::app()->c->contract['idno_key']);
        if ($user_info = User::model()->findByAttributes(['mobile' => $_REQUEST['phone'], 'is_effect' => 1])) {
            $bank_info = UserBankcard::model()->findBySql('select * from firstp2p_user_bankcard where user_id = :user_id order by id desc limit 1', ['user_id'=>$user_info['id']]);
            if ($bank_info) {
                $returnData['bankcard'] = GibberishAESUtil::dec($bank_info->bankcard, Yii::app()->c->contract['idno_key']);
                $returnData['card_name'] = $bank_info->card_name;
            }
            $returnData['idno'] = GibberishAESUtil::dec($user_info->idno, Yii::app()->c->contract['idno_key']);
        }

        $this->echoJsonExit($returnData);
    }

    public function actionCheck(){
        $data = Yii::app()->rcache->get('_firstp2p_deal_tag_42_44');

        $this->echoJsonExit(['d'=>json_decode($data)]);

    }



    /**
     * 用户信息解密
     * @param string $json_data
     */
    private function userDec($json_data='')
    {
        Yii::log("shop userDec params : $json_data");
        $data = json_decode($json_data, true);
        //参数校验
        $returnData = [];
        if (!is_array($data) || empty($data)) {
            Yii::log('shop userDec return : code=1000', 'error');
            $this->echoJsonExit($returnData, 1000, '参数异常');
        }

        //逐一解密
        foreach ($data as $k=>$v) {
            $returnData[$k] = GibberishAESUtil::dec($v, Yii::app()->c->contract['idno_key']);
        }

        Yii::log('shop userDec return : ' . print_r($returnData, true));
        $this->echoJsonExit($returnData, 0);
    }


    /**
     * 用户信息加密
     * @param string $json_data
     */
    private function userEnc($json_data='')
    {
        Yii::log("shop userEnc params : $json_data");
        $data = json_decode($json_data, true);
        //参数校验
        $returnData = [];
        if (!is_array($data) || empty($data)) {
            Yii::log('shop userEnc return : code=1000', 'error');
            $this->echoJsonExit($returnData, 1000, '参数异常');
        }

        //逐一加密
        foreach ($data as $k=>$v) {
            $returnData[$k] = GibberishAESUtil::enc($v, Yii::app()->c->contract['idno_key']);
        }

        Yii::log('shop userEnc return : ' . print_r($returnData, true));
        $this->echoJsonExit($returnData, 0);
    }

    /**
     * 数据统计相关
     */
    private function getStatisticsData()
    {
        $aboutDebt = new AboutUserDebt();
        $returnData = $aboutDebt->getStatisticsData();
        $this->echoJsonExit($returnData, 0);
    }

    /**
     * 下车债权金额
     */
    private function getUserSpecialDebtAccount()
    {
        $returnData = ['account' => 0];
        $user_id    = $_SESSION['uid'];
        //未登录
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        $aboutDebt = new AboutUserDebt($user_id);
        $result = $aboutDebt->getUserSpecialSumAccountAndTotalTenderPH();
        $returnData['account']  = $result['total_account'];
        $this->echoJsonExit($returnData, 0, self::$codeToInfo[0]);
    }

    /**
     * 下车债权提交接口
     */
    private function userSpecialDebtCommit()
    {
        Yii::log('shop special commit order params : ' . print_r($_REQUEST, true), 'info', __FUNCTION__);

        $returnData = [];
        //未登录
        $user_id = $_SESSION['uid'];
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        $aboutDebt = new AboutUserDebt($user_id);

        $result   = $aboutDebt->specialDebtOrderCommit($_REQUEST);
        $this->echoJsonExit($result['data'], $result['code'], self::$codeToInfo[$result['code']]);
    }

    /**
     * 仅支持普惠
     * 债权回退
     */
    private function userDebtRollback()
    {
        Yii::log(' userDebt Rollback params : ' . print_r($_REQUEST, true) . ' user_id:' . $_SESSION['uid'], 'info', __FUNCTION__);
        if (empty($_REQUEST['order_id'])) {
            $this->echoJsonExit([], 100, '债权单号不能为空');
        }
        if ($_REQUEST['account'] <= 0 || empty($_REQUEST['account'])) {
            $this->echoJsonExit([], 100, '债权兑换金额格式错误');
        }
        if (empty($_SESSION['uid'])) {
            $this->echoJsonExit([], 100, 'hashid不能为空');
        }
        $aboutDebt = new AboutUserDebt($_SESSION['uid']);
        $res       = $aboutDebt->userDebtRollback($_REQUEST['order_id'], $_REQUEST['account']);
        Yii::log('huanhuan userDebt Rollback return data : ' . print_r($res, true), 'info', __FUNCTION__);

        $this->echoJsonExit($res['data'], $res['code'], $res['info']);
    }

    /**
     * 校验用户可否登陆
     */
    private function checkUserLogin(){
        $returnData = array();
        $_phone     = GibberishAESUtil::enc(trim($_REQUEST['phone']), Yii::app()->c->contract['idno_key']);
        $user_info  = User::model()->findByAttributes(['mobile' => $_phone]);
        if($user_info){
            $aboutUser  = new AboutUserAuth($user_info->id);
            $result = $aboutUser->checkUserLogin();
            $this->echoJsonExit($result['data'], $result['code'], self::$codeToInfo[$result['code']]);
        }
        return $this->echoJsonExit($returnData,0);
    }

    /**
     * 用户同意新的协议 生成电子合同
     */
    protected function confirmUserDebt(){
        $returnData = [];
        $user_id              = $_SESSION['uid'];
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        //保存债权数据到协议表
        $userAuth = new AboutUserAuth($user_id);
        $authInfo = $userAuth->confirmUserDebt($_REQUEST['debtInfo'],$_REQUEST['confirmType']);
        return $this->echoJsonExit($authInfo['data'],$authInfo['code'],self::$codeToInfo[$authInfo['code']]);
    }

    /**
     * 用户协议信息
     */
    private function getUserAgreementInfo(){
        $returnData = [];
        $user_id              = $_SESSION['uid'];
        if (!$user_id) {
            $this->echoJsonExit($returnData, 2003, self::$codeToInfo[2003]);
        }
        //保存债权数据到协议表
        $userAuth = new AboutUserAuth($user_id);
        $authInfo = $userAuth->getUserAgreementInfo();
        return $this->echoJsonExit($authInfo['data'],$authInfo['code'],self::$codeToInfo[$authInfo['code']]);
    }

}
