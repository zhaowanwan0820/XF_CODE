<?php

/**
 * This is the model class for table "dw_account_recharge".
 *
 * The followings are the available columns in table 'dw_account_recharge':
 * @property string $id
 * @property string $trade_no
 * @property integer $user_id
 * @property integer $status
 * @property string $money
 * @property string $payment
 * @property string $return
 * @property string $type
 * @property string $remark
 * @property string $fee
 * @property integer $verify_userid
 * @property string $verify_time
 * @property string $verify_remark
 * @property string $addtime
 * @property string $addip
 */
class AccountRecharge extends DwActiveRecord
{
     public $dbname = 'dwdb';
     public $realname;//用户真实姓名
     public $username;//用户名
     public $phone;//电话号码
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AccountRecharge the static model class
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
		return 'dw_account_recharge';
	}
    
     //充值类型
     protected $_accountType=array('线下充值','PC网关充值',3=>'wap充值',4=>'IOS充值',5=>'android充值',6=>'PC快捷充值');
    
     public function getAccountType(){
         return $this->_accountType;
     }
     public function StrAccountType(){
         return $this->_accountType[$this->type];
     }
     public function getStrAccountType($key){
         return (array_key_exists($key, $this->_accountType))?$this->_accountType[$key]:"选择类型";
     }
     
     //充值状态
     // protected $_accountStatus=array(1=>'充值成功',2=>'充值失败',3=>'线下待审核',4=>'线上发起充值',5=>'线上充值处理中',6=>'充值未成功',);
     protected $_accountStatus=array(1=>'充值成功',2=>'充值失败',3=>'线下待审核',);
    
     public function getAccountStatus(){
         return $this->_accountStatus;
     }
     public function StrAccountStatus(){

     	$IsTwoDaysTime = (time() < ($this->addtime + 172800)) ? 1 : 0;			//两天之内的订单
     	if (in_array($this->status, [1,2,3])) {
     		return $this->_accountStatus[$this->status];
     	} elseif ($this->status==0 && $this->step==1 && $IsTwoDaysTime) {
     		return '线上发起充值';
     	} elseif ($this->status==0 && $this->step==2 && $IsTwoDaysTime) {
     		return '线上充值处理中';
     	}elseif ($this->status==0 && !$IsTwoDaysTime) {					//未成功的订单
     		return '充值未成功';
     	}
    }

    public function IsTwoDays(){
        $IsTwoDaysTime = (time() < ($this->addtime + 172800)) ? 1 : 0;          //两天之内的订单
        return $IsTwoDaysTime;
    }

     public function getStrAccountStatus($key){
         return (array_key_exists($key, $this->_accountStatus))?$this->_accountStatus[$key]:"选择状态";
     }
     
     //银行分类
     public function getRemark(){
         $usersrcs = Yii::app()->params['bank_remark'];
         return $usersrcs;
     }
     public function StrRemark(){
         $usersrcs = $this->getRemark();
         if(isset($usersrcs[$this->remark]))
            return $usersrcs[$this->remark];
     }
     public function getStrRemark($key){
         $res  = $this->getRemark();
         return (array_key_exists($key, $res[$key]?$res[$key]:""));
     }
     
     //充值银行
     public function getAccountBank(){
         $names2 = ItzBank::model()->findAll();
         foreach($names2 as $kk=>$vv){
            $banks[$vv->bank_id] = $vv->bank_name;
         }
         return $banks;
     }
     public function StrAccountBank(){
         $res = $this->getAccountBank();
         return isset($res[$this->bank_id])?$res[$this->bank_id]:"-";
     }
     public function getStrAccountBank($key){
         $banks  = $this->getAccountBank();
         return (array_key_exists($key, $banks[$key]?$banks[$key]:"-"));
     }
     
     //充值渠道
     public function getPaymentType(){
         return $this->getPayment();
     }
     public function StrPaymentType(){
         $res = $this->getPayment();
         return isset($res[$this->payment])?$res[$this->payment]:"未知";
     }
     public function getStrPaymentType($key){
         $res = $this->getPayment();
         return (array_key_exists($key, $res))?$res[$key]:"";
     }
     public function getPayment(){
         $paymentModel = new Payment;
         $aResult = $paymentModel->findAll();
         foreach ($aResult as $paymentModel) 
         {
             $objArray[] = $paymentModel->attributes;
         }
         foreach($objArray as $k=>$v){
             $arr[$v['id']] = $v['name'];
         }
         return $arr;
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
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
		    array('type,payment,money,remark','required','message'=>Yii::t('luben','{attribute}不能为空')),
			array('user_id, step,status, verify_userid, verify_time, addtime', 'numerical', 'integerOnly'=>true),
			array('trade_no', 'length', 'max'=>100),
			array('money, fee', 'length', 'max'=>11),
			array('payment', 'length', 'max'=>100),
			array('type', 'length', 'max'=>10),
			array('remark, verify_remark', 'length', 'max'=>250),
			array('addip', 'length', 'max'=>15),
			array('return', 'safe'),
			array('app_version,bank_id,card_number,phone,realname,username,id, trade_no, user_id, step,status, money, payment, return, type, remark, fee, verify_userid, verify_time, verify_remark, addtime, addip', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
          "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
          "vuserInfo"   =>  array(self::BELONGS_TO, 'ItzUser', 'verify_userid'),
          "paymentInfo" =>  array(self::BELONGS_TO, 'Payment', 'payment'),
          "bankInfo" =>  array(self::BELONGS_TO, 'ItzBank', 'bank_id')
        );
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => '订单ID',
			'trade_no' => '订单号',
			'user_id' => '用户ID',
			'status' => '充值状态(发起充值、充值处理中、未成功暂不支持筛选)',
			'step' => '充值操作节点',
			'phone' => '手机号',
			'money' => '充值金额(元)',
			'payment' => '充值渠道',
			'return' => 'RETURN',
			'type' => '充值类型',
			'remark' => '充值备注',
			'fee' => '费用',
			'verify_userid' => '审核人',
			'verify_time' => '审核时间',
			'verify_remark' => '审核备注',
			'addtime' => '充值时间',
			'addip' => '充值IP',
			'realname' => '用户实名',
            'username' => '用户名',
            'card_number'=>'银行卡号',
            'bank_id'=>'充值银行',
            'app_version'=>'客户端版本',
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
		$criteria->compare('trade_no',$this->trade_no);
        $criteria->compare('bank_id',$this->bank_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('step',$this->step);
		$criteria->compare('money',$this->money);
		$criteria->compare('payment',$this->payment);
		$criteria->compare('return',$this->return);
		$criteria->compare('type',$this->type);
		$criteria->compare('remark',$this->remark);
		$criteria->compare('fee',$this->fee);
		$criteria->compare('verify_userid',$this->verify_userid);
		$criteria->compare('verify_time',$this->verify_time);
		$criteria->compare('verify_remark',$this->verify_remark);
		if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('addtime',$this->addtime);
        
		$criteria->compare('addip',$this->addip);
		return new CActiveDataProvider($this, array(
		   'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}
