<?php

/**
 * 智多星导入数据类
 * Class HandleZDXData.
 */
class HandleZDXData extends BaseHandleOfflineData
{
    public $platform_id = 4;
    //需要必填的字段
    public $needCheckField = [
        'order_sn', 'old_user_id'
    ];
    private $contract_list_key = 'zdx:contract:user:tender:';


    public function handle($import)
    {
        try {
            $this->checkParam($import)->handleUserInfo()->handleDealInfo()->handelTenderInfo()->handelContractInfo()->handleCollection();
        } catch (Exception $e) {
            throw  $e;
        }

        return $this;
    }

    //处理用户信息
    public function handleUserInfo()
    {
        $user_info_key = $this->import['old_user_id'];
        if (!empty(self::$userInfoArray) && in_array($user_info_key, array_keys(self::$userInfoArray))) {
            $this->userInfo = self::$userInfoArray[$user_info_key];
        } else {
            $userInfo = Firstp2pUser::model()->findByAttributes(['id' => $this->import['old_user_id']]);
            if (!$userInfo) {
                throw new Exception('获取用户信息失败 import_content 表 id:'.$this->import['id'].' error: 用户id不存在:'.$this->import['old_user_id']);
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
                $userPlatform->real_name = $userInfo->real_name;
                $userPlatform->phone = $userInfo->mobile;
                $userPlatform->id_type = $userInfo->id_type;
                $userPlatform->idno = $userInfo->idno;
                $userPlatform->old_user_id = $this->import['old_user_id'];
                if (false === $userPlatform->save()) {
                    throw new Exception('创建平台用户时失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($userPlatform->getErrors(), true));
                }
            }
            $this->userPlatformInfo = $userPlatform;
            $this->userInfo = $userInfo;
            self::$userInfoArray[$user_info_key] = $userInfo;
            self::$userPlatformInfoArray[$user_info_key] = $userPlatform;
        }

        return $this;
    }

    public function handleDealInfo()
    {
        $dealInfo = OfflineDeal::model()->findByAttributes(['name' =>'智多新','platform_id'=>$this->platform_id]);
        if ($dealInfo) {
            $this->dealInfo = $dealInfo;
        } else {
            $dealProjectInfo = new OfflineDealProject();
            $dealProjectInfo->name = '智多新'; //项目名称
            $dealProjectInfo->product_class = '智多新';
            $dealProjectInfo->product_name = '智多新'; //产品名称
            $dealProjectInfo->intro = $this->import['p_desc']; //
            $dealProjectInfo->borrow_amount = $this->import['raise_money']; //借款金额
            $dealProjectInfo->loantype = 3; //还款方式
            $dealProjectInfo->repay_time = $this->import['p_limit_num']; //借款期限
            $dealProjectInfo->rate = $this->import['rate']; //年化利率
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
            $dealInfo->name = '智多新'; //借款标题
            $dealInfo->rate = $this->import['rate']; //年化利率
            $dealInfo->loantype = 3; //还款方式
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
            if (false === $dealInfo->save()) {
                throw new Exception('创建子项目失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($dealInfo->getErrors(), true));
            }
        }
        $this->dealInfo = $dealInfo;

        return $this;
    }


    //处理投资记录信息
    public function handelTenderInfo()
    {
        $res = OfflineDealLoad::model()->findByAttributes(['user_id' => $this->userInfo->id, 'order_sn' => $this->import['order_sn'], 'platform_id' => $this->platform_id]);
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
            $dealLoadInfo->order_sn = $this->import['order_sn']; //投资
            $dealLoadInfo->platform_id = $this->platform_id; //平台id
            $dealLoadInfo->status = 1;

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
        $contract = OfflineContractTask::model()->findByAttributes(['borrow_id' => $this->dealInfo->id, 'tender_id' => $this->dealLoadInfo->id, 'user_id' => $this->userInfo->id]);
        if ($contract) {
            throw new Exception('创建合同失败 import_content 表 id:'.$this->import['id'].' error:合同已经存在 tender_id:'.$this->dealLoadInfo->id);
        }
        $contract = new OfflineContractTask();
        $contract->borrow_id = $this->dealInfo->id;
        $contract->type = 1;
        $contract->contract_type = 1;
        $contract->tender_id = $this->dealLoadInfo->id;
        $contract->user_id = $this->userInfo->id;
        $contract->investtime = $this->import['rg_time'];
        $contract->addtime = time();
        $contract->platform_id = $this->platform_id; //平台id;

        if (false === $contract->save()) {
            throw new Exception('创建合同失败 import_content 表 id:'.$this->import['id'].' error:'.print_r($contract->getErrors(), true));
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
        $dealLoanRepay->time = strtotime('2019-07-04') - 3600 * 8;;
        $dealLoanRepay->real_time = 0;
        $dealLoanRepay->status = 0;
        $dealLoanRepay->type = 1;
        $dealLoanRepay->is_zdx = 1;
        $dealLoanRepay->platform_id = $this->platform_id; //平台id;
        if (false == $dealLoanRepay->save()) {
            throw new Exception('创建新的本金还款计划失败 '.print_r($dealLoanRepay->getAttributes(), true));
        }
    }

    /**
     * 保存用户账户金额
     * @throws Exception
     */
    private function saveUserAccount()
    {
        if (!$this->import) {
            throw new Exception('待导入用户平台账户信息数据为空');
        }
        if (!$this->userPlatformInfo) {
            throw new Exception('用户平台信息不存在 '.print_r($this->import, true));
        }

        if (!$this->userInfo) {
            throw new Exception('用户信息不存在 '.print_r($this->import, true));
        }

        $this->userPlatformInfo->wait_join_money = bcadd($this->userPlatformInfo->wait_join_money, $this->import['wait_amount'], 2);
        if (false === $this->userPlatformInfo->save()) {
            throw new Exception('保存用户平台表 用户待加入金额失败 wait_join_money: '.$this->import['wait_capital'].' error:'.print_r($this->import, true));
        }
        $change_money = bcadd($this->userInfo->ph_money, $this->import['wait_amount'], 2);
        $res = Firstp2pUser::model()->updateByPk($this->userInfo->id,['ph_money' => $change_money]);

        if (false === $res) {
            throw new Exception('保存用户表 用户待加入金额失败 wait_join_money: '.$this->import['wait_capital'].' error:'.print_r($this->userInfo->getErrors(), true));
        }
        $accountLog = new XfUserAccountLog();
        $accountLog->platform_id = $this->platform_id;
        $accountLog->user_id = $this->userInfo->id;
        $accountLog->addtime = time();
        $accountLog->type = 'zdx_wait_join_money';
        $accountLog->direction = 1;
        $accountLog->money = $this->import['wait_amount'];
        $accountLog->changed_money = $change_money;
        if (false === $accountLog->save()) {
            throw new Exception('保存用户待加入金额流水记录失败 wait_join_money: '.$this->import['wait_capital'].' error:'.print_r($this->import, true));
        }
    }

    /**
     * 处理用户账户数据导入.
     *
     * @param $params
     *
     * @return array|void
     *
     * @throws Exception
     */
    public function handleUserAccount($params)
    {
        $this->needCheckField = ['old_user_id'];
        try {
            $this->checkParam($params)->handleUserInfo()->saveUserAccount();
        } catch (Exception $e) {
            throw $e;
        }
    }


    protected function getTableName($contractNum)
    {
        // 简单hash crc32 后对64取余
        $crc = intval(abs(crc32($contractNum)));
        $tableSurfix = $crc % 64;
        $tableName = sprintf('firstp2p_contract_files_with_num_%s', $tableSurfix);

        return $tableName;
    }
    //会员编号
    // no :user_id;
    //type 用户类型：0 个人会员 1:企业会员
    function numTo32($no, $type=0){
        $no+=34000000;
        $char_array=array("2", "3", "4", "5", "6", "7", "8", "9",
            "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M",
            "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $rtn = "";
        while($no >= 32) {
            $rtn = $char_array[fmod($no, 32)].$rtn;
            $no = floor($no/32);
        }

        $prefix = '00';
        if($type == 1){
            $prefix = '66';
        }
        return $prefix.$char_array[$no].$rtn;
    }

    //人民币小写转大写
    function get_amount($number = 0)
    {
        $int_unit = '元';
        $is_round = true;
        $is_extra_zero = false;

        // 将数字切分成两段
        $parts = explode('.', $number, 2);
        $int = isset($parts[0]) ? strval($parts[0]) : '0';
        $dec = isset($parts[1]) ? strval($parts[1]) : '';

        // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $dec_len = strlen($dec);
        if (isset($parts[1]) && $dec_len > 2) {
            $dec = $is_round ? substr(
                strrchr(strval(round(floatval("0." . $dec), 2)), '.'), 1) : substr(
                $parts[1], 0, 2);
        }

        // 当number为0.001时，小数点后的金额为0元
        if ((empty($int) && empty($dec)) || $number == 0) {
            return '零元整';
        }

        // 定义
        $chs = array(
            '0',
            '壹',
            '贰',
            '叁',
            '肆',
            '伍',
            '陆',
            '柒',
            '捌',
            '玖'
        );
        $uni = array(
            '',
            '拾',
            '佰',
            '仟'
        );
        $dec_uni = array(
            '角',
            '分'
        );
        $exp = array(
            '',
            '万'
        );
        $res = '';

        // 整数部分从右向左找
        for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k ++) {
            $str = '';
            // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
            for ($j = 0; $j < 4 && $i >= 0; $j ++, $i --) {
                $u = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
                $str = $chs[$int{$i}] . $u . $str;
            }
            $str = rtrim($str, '0'); // 去掉末尾的0
            $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
            if (! isset($exp[$k])) {
                $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
            }
            $u2 = $str != '' ? $exp[$k] : '';
            $res = $str . $u2 . $res;
        }

        // 如果小数部分处理完之后是00，需要处理下
        $dec = rtrim($dec, '0');

        $res .= empty($int) ? '' : $int_unit;

        // 小数部分从左向右找
        if (! empty($dec)) {

            // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
            if ($is_extra_zero) {
                if (substr($int, - 1) === '0') {
                    $res .= '零';
                }
            }

            for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i ++) {
                $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                $res .= $chs[$dec{$i}] . $u;
            }
            $tag = $number < 0.1 ? '' : '零'; // 兼容0.03的情况
            $res = rtrim($res, '0'); // 去掉末尾的0
            $res = preg_replace("/0+/", $tag, $res); // 替换多个连续的0
        } else {
            $res .= '整';
        }
        return $res;
    }

    /**
     * 查看合同列表
     * @return array
     * @throws Exception
     */
    public function getContractList()
    {
        $list = [];
        $params = $this->getContractNeedParams;

        $dealLoadInfo = Yii::app()->offlinedb->createCommand('select debt_type,deal_id,dt_project_id from offline_deal_load where id =:deal_load_id and user_id =:user_id ')->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id']])->queryRow();
        if (empty($dealLoadInfo)) {
            throw  new Exception('出借记录不存在');
        }
        $dealLoadInfo['dt_project_id'] = 1003  ;1004;
        //变量  投资金额，投资时间
        $dealLoadInfo['zdx_deal_load_id'];//多投库  duotou_deal_loan 主键
        //变量

       // 1003   rateYear => 5.00000  $project['rateDay'] = $rateDay = bcdiv($adjustInfo['rate_after'],360,5);

        $notice['loan_money'] = '';//大标原始出借金额
        $notice['uppercase_loan_money}'] = '';//人民币大写

        $notice['fee_rate'] = '1.000';
        $notice['fee_days'] = '10';

        $notice['sign_time'] = $dealLoadInfo['create_time'];//签名时间

        //甲方  用户信息
        $notice['loan_real_name'] = '';
        $notice['loan_user_number'] = '';//会员编号 见上面加密方式
        $notice['loan_user_idno'] = '';//证件号


        //1003 或 1004
        $dtDealId = intval(substr($number, 0, 8));
        $type = intval(substr($number, 8, 2));
        $contractType = intval(substr($number, 10, 2));
        $userId = intval(substr($number, 12, 10));
        $dtLoanId = intval(substr($number, 22, 10));//对应 多投库 duotou_deal_loan 的 id
        $notice['number'] = '';


        $contract['title'] = '顾问协议';
        $contract['path'] = 'aaaaaaaaaaaa';
        $list[] = $contract;

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
