<?php

/**
 * This is the model class for table "dw_account_log".
 *
 * The followings are the available columns in table 'dw_account_log':
 * @property string $id
 * @property string $transid
 * @property integer $user_id
 * @property string $type
 * @property integer $direction
 * @property string $total
 * @property string $money
 * @property string $virtual_money
 * @property string $use_money
 * @property string $no_use_money
 * @property string $collection
 * @property string $withdraw_free
 * @property string $use_virtual_money
 * @property string $invested_money
 * @property integer $to_user
 * @property string $remark
 * @property string $addtime
 * @property string $addip
 * @property string $recharge_amount
 */
class AccountLog extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $realname;//用户真实姓名
    public $username;//用户名
    public $phone;//电话号码
    public $logTypeTitle = "";
    public $logTypeName = "";
    public $headers = [
	        "x-itz-apptoken: r&ht0E@*aGeJkNg3d6X3gOM&WWEbGCgO",
	        "Content-Type: application/json; charset=utf-8",
	    ];
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AccountLog the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dw_account_log';
	}

    /**
     * 银行分类
     */
     public function getType(){
         $account_type = Yii::app()->params['account_type'];
         return $account_type;
     }
     public function StrType(){
		 $account_type = $this->getType();
         if(isset($account_type[$this->type]))
            return $account_type[$this->type];
     }
     public function getStrType($key){
         $res  = $this->getType();
         return (array_key_exists($key, $res?$res[$key]:""));
     }
	public function StrLogType(){
		//新log_type
		$account_log_type = Yii::app()->params['account_log_type'];
		//项目类型
		$borrow_type = Yii::app()->params['borrow_type_online_usertrade'];

		if(isset($account_log_type[$this->type]))
			return $borrow_type[$this->borrow_type].$account_log_type[$this->type];
	}
     public function getUrl($id){
         $logInfo = AccountLog::model()->findByPk($id);
         $transidArr = explode('_', $logInfo->transid);
         
         if($logInfo->type == 'recharge' && $transidArr[0] == 'recharge' )
         {//充值链接
                $aResult = CHtml::link($logInfo->transid,'/account/AccountRecharge/view?id='.$transidArr[1],array('target'=>'_blank'));
         }elseif(in_array($logInfo->type, array('cash_false','cash_frost','cash_refund','cash_success')) && $transidArr[0] == 'cash' )
         {//流水号加提现详情链接
                $aResult = CHtml::link($logInfo->transid,'/account/AccountCash/view?id='.$transidArr[1],array('target'=>'_blank'));
         }elseif(in_array($logInfo->type, array('award_invest','hongbao_frost','hongbao_cancel','hongbao_success','hongbao_recharge','award_invite','realname',)))
         {//无连接
                $aResult = $logInfo->transid;
         }else
         {//流水号加项目详情页链接
                if($transidArr[0] == 'tender' || $transidArr[0] == 'debttender'){//投资项目ID
                    $infos = BorrowTender::model()->findByPk($transidArr[1]);
                }elseif($transidArr[0] == 'collection'){
                    $infos = BorrowCollection::model()->findByPk($transidArr[1]);
                }elseif($transidArr[0] == 'debt'){
                    $infos = Debt::model()->findByPk($transidArr[1]);
                }elseif($transidArr[0] == 'pre'){
                    $infos = BorrowPre::model()->findByPk($transidArr[1]);
                }
                
                if($infos){
                    $aResult = CHtml::link($logInfo->transid,'/borrow/borrow/view?id='.$infos->borrow_id,array('target'=>'_blank'));
                }else{
                    $aResult = $logInfo->transid;
                }
         }
         return $aResult;
     }

    public function getUserassets($uid=''){
    	
    	//现有资产 = 待收本金+待收利息+待收加息+可用余额+冻结金额+冻结奖励金额
        $user_id = intval($uid);
    	$DwAccountModel = new Account();
        $criteria = new CDbCriteria; 
        $attributes = array(
          "user_id"    =>   $user_id,   
        );
        $result =$DwAccountModel->findByAttributes($attributes,$criteria);
        $dwAccountInfo = $result->getAttributes();

        //用户待收数据获取
        $user_unreceived_total = UserAccountService::getInstance()->getUserUnreceivedTotal($user_id);
        // 私募高端理财
        $result = $this->getAccount($user_id);
        $user_assets = $user_unreceived_total['data']['user_unreceived_capital'] +
            $user_unreceived_total['data']['user_unreceived_interest'] +
            $user_unreceived_total['data']['user_unreceived_rewardInterest'] +
            $dwAccountInfo['use_money'] +
            $dwAccountInfo['no_use_money'] +
            $result['data']['trust_principal'];

        return $user_assets;
    }


    public function getEncryptRealName($str='')
     {
        $strlen = mb_strlen($str, 'UTF-8');
        if ($strlen>0) {
            return $this->substr_replace_cn($str,'*',1,mb_strlen($str, 'UTF-8')-1);
        }
        return '-';
     }
     public function getEncryptStr($str='',$length=0)
    {
        $strlen = mb_strlen($str, 'UTF-8');
        if ($strlen>0) {
            return $this->substr_replace_cn($str,'*',0,$strlen-$length);
        }
        return '-';
    }
     public function substr_replace_cn($string, $repalce = '*',$start = 0,$len = 0) {
        $count = mb_strlen($string, 'UTF-8'); //此处传入编码，建议使用utf-8。此处编码要与下面mb_substr()所使用的一致
        if(!$count) { 
            return $string; 
        }
        if($len == 0){
            $end = $count;  //传入0则替换到最后
        }else{
            $end = $start + $len;       //传入指定长度则为开始长度+指定长度
        }
        $i = 0;
        $returnString = '';
        while ($i < $count) {        //循环该字符串
            $tmpString = mb_substr($string, $i, 1, 'UTF-8'); // 与mb_strlen编码一致
            if ($start <= $i && $i < $end) {
                $returnString .= $repalce;
            } else {
                $returnString .= $tmpString;
            }
            $i ++;
        }
        return $returnString;
    }

    /**
     * 获取账户详情
     * @param $user_id
     * @return array
     */
    public function getAccount($user_id)
    {
        $returnData = array('code' => 0, 'info' => '', 'data' => []);
        $url = Yii::app()->c->request_url . '/account/' . $user_id;
        $result = $this->request('get',$url);
        Yii::log($url . '接口返回值:' . $result, 'info', 'UserService.getAccount');
        $result = json_decode($result, true);
        if (!isset($result)) {
            // Java接口异常记录日志
            Yii::log($url . ':接口返回异常!', CLogger::LEVEL_ERROR, 'UserService.getAccount');
            $returnData['code'] = 100;
            $returnData['info'] = '网络异常,请稍后重试';
            return $returnData;
        }
        if ($result['code'] == 0) {
            $returnData['data'] = $result['data'];
            $returnData['info'] = '获取账户信息成功';
            return $returnData;
        } else {
            $returnData['code'] = $result['code'];
            $returnData['info'] = $result['info'];
            // Java接口异常记录日志
            Yii::log($url . ':接口返回异常:' . json_encode($returnData), CLogger::LEVEL_ERROR, 'AccountLog.getAccount');
            return $returnData;
        }
    }

    //请求
    public function request($methord = 'get', $url = '', $body = '')
    {
        // 1.初始化
        $curl = curl_init();
        // 2.设置属性
        curl_setopt($curl, CURLOPT_URL, $url);          // 需要获取的 URL 地址
        curl_setopt($curl, CURLOPT_HEADER, 0);          // 设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 要求结果为字符串且输出到屏幕上
        // Set headers
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers); // 设置 HTTP 头字段的数组
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        switch ($methord) {
            case 'get':
                break;
            case 'post':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'delete':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'patch':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'put':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            default:

        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($curl, CURLOPT_NOSIGNAL, true);
        // 3.执行并获取结果
        $res = curl_exec($curl);
        // 4.释放句柄
        curl_close($curl);
        Yii::log($methord . ':' . $url . ' body:' . $body . ' return:' . json_encode($res, JSON_UNESCAPED_UNICODE));
        return $res;
    }
     
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('transid', 'required'),
			array('user_id, direction, to_user, addtime', 'numerical', 'integerOnly'=>true),
			array('transid, type', 'length', 'max'=>100),
			array('total, money, virtual_money, use_money, no_use_money, collection, withdraw_free, use_virtual_money, invested_money, recharge_amount', 'length', 'max'=>11),
			array('remark', 'length', 'max'=>250),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('borrow_type,phone,realname,username,id, transid, user_id, type, direction, total, money, virtual_money, use_money, no_use_money, collection, withdraw_free, use_virtual_money, invested_money, to_user, remark, addtime, addip, recharge_amount', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
		      "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'transid' => '流水号',
			'user_id' => '用户ID',
			'type' => '类型',
			'phone' => '手机号',
			'direction' => 'Direction',
			'total' => '可用余额',
			'money' => '金额',
			'virtual_money' => '使用子账户金额',
			'use_money' => '可用金额',
			'no_use_money' => '不可用金额',
			'collection' => 'Collection',
			'withdraw_free' => '手续费',
			'use_virtual_money' => '可用金额',
			'invested_money' => '投资过的金额',
			'to_user' => '交易对方',
			'remark' => '备注',
			'addtime' => '发生时间',
			'addip' => 'IP',
			'recharge_amount' => '用户充值金额',
			'realname' => '真实姓名',
            'username' => '用户名',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('transid',$this->transid);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('borrow_type',$this->borrow_type,FALSE);
		$criteria->compare('type',$this->type,FALSE);
		$criteria->compare('direction',$this->direction);
		$criteria->compare('total',$this->total);
		$criteria->compare('money',$this->money);
		$criteria->compare('virtual_money',$this->virtual_money);
		$criteria->compare('use_money',$this->use_money);
		$criteria->compare('no_use_money',$this->no_use_money);
		$criteria->compare('collection',$this->collection);
		$criteria->compare('withdraw_free',$this->withdraw_free);
		$criteria->compare('use_virtual_money',$this->use_virtual_money);
		$criteria->compare('invested_money',$this->invested_money);
		$criteria->compare('to_user',$this->to_user);
		$criteria->compare('remark',$this->remark);
        if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('recharge_amount',$this->recharge_amount,true);

		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}
