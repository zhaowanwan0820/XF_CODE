<?php

/**
 * This is the model class for table "dw_borrow_tender".
 *
 * The followings are the available columns in table 'dw_borrow_tender':
 * @property string $id
 * @property integer $site_id
 * @property integer $user_id
 * @property integer $status
 * @property integer $debt_status
 * @property integer $debt_type
 * @property integer $type
 * @property integer $borrow_id
 * @property string $money
 * @property string $account
 * @property string $repayment_account
 * @property string $interest
 * @property string $repayment_yesaccount
 * @property string $wait_account
 * @property string $wait_interest
 * @property string $repayment_yesinterest
 * @property string $agreement_path
 * @property string $money_detail
 * @property string $addtime
 * @property string $addip
 */
class BorrowTender extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $realname;//用户真实姓名
    public $username;//用户名
    public $borrowname;//项目名称
    public $phone;//电话号码
    // public $tender_status;//投资状态
    
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return BorrowTender the static model class
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
		return 'dw_borrow_tender';
	}


     protected $_debtType=array(
                                1=>'爱担保',
                                5=>'爱融租',
                                7=>'爱保理',
                                9=>'爱收藏',
								2=>'爱担保债权转让',
                                6=>'爱融租债权转让',
                                8=>'爱保理债权转让',
                                10=>'爱收藏债权转让',
                                11=>'省心计划--影视',
                                12=>'省心计划--影视债权转让',
                                17=>'省心计划--小贷',
                                18=>'省心计划--小贷债权转让',
                                27=>'省心计划--典当',
                                28=>'省心计划--典当债权转让',
								29=>'零钱计划',
								31=>'省心优选',
								32=>'省心优选债权转让',
								33=>'智选集合',
								34=>'智选集合债券转让',
								35=>'智选计划',
					 );
    
     public function getDebtType(){
         return $this->_debtType;
     }
     public function StrDebtType(){
		 $debt_type = '';
		 //小贷
		 if(in_array($this->debt_type, array(17, 15, 13))){
			 $debt_type = 17;
		 } elseif (in_array($this->debt_type, array(14, 16, 18))){//小贷债权转让
			 $debt_type = 18;
		 } elseif (in_array($this->debt_type, array(23, 25, 27))){//典当
			 $debt_type = 27;
		 } elseif (in_array($this->debt_type, array(24, 26, 28))){//典当债权转让
			 $debt_type = 28;
		 } elseif (in_array($this->debt_type, array(11, 19, 21))){//影视
			 $debt_type = 11;
		 } elseif (in_array($this->debt_type, array(12, 20, 22))){//影视债权转让
			 $debt_type = 12;
		 } else{
			 $debt_type = $this->debt_type;
		 }
         return $this->_debtType[$debt_type];
     }
     public function getStrDebtType($key){
         return (array_key_exists($key, $this->_debtType))?$this->_debtType[$key]:"选择类型";
     }

     # 获取投资者利率
     public function getWiseTenderApr(){

     	if($this->status == 2){
            $exitInfo = ItzInvestExit::model()->findByAttributes(["tender_id"=>$this->id,'status'=>2]);
            $days 	  = BorrowService::getInstance()->getDays($this->addtime,$exitInfo?$exitInfo->endtime:0);
            $apr      = BorrowService::getInstance()->getTenderApr($days);
        }else{
            $days     = BorrowService::getInstance()->getDays($this->addtime);
            $apr      = BorrowService::getInstance()->getTenderApr($days);
        }
        return $apr;
     }
  
    /**
     * 状态
     */
     protected $_status=array(0=>'存续期', 1=>'存续期',2=>"已结息",15=>"已转让");
    
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus(){
         if(isset($this->_status[$this->status]))
            return $this->_status[$this->status];
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
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
			array('agreement_path', 'required'),
			array('site_id, user_id, status, debt_status, debt_type, type, borrow_id, addtime', 'numerical', 'integerOnly'=>true),
			array('money, account, repayment_account, interest, repayment_yesaccount, wait_account, wait_interest, repayment_yesinterest, coupon_value', 'length', 'max'=>11),
			array('agreement_path', 'length', 'max'=>300),
			array('addip', 'length', 'max'=>50),
			array('request_no', 'length', 'max'=>100),
			array('invest_device', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('pre_time,phone,borrowname,request_no,realname,username,id, invest_device,site_id, user_id, status, debt_status, debt_type, type, borrow_id, money, account, repayment_account, interest, repayment_yesaccount, wait_account, wait_interest, repayment_yesinterest, agreement_path, money_detail, addtime, addip', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		  "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
		  "borrowInfo" =>  array(self::BELONGS_TO, 'Borrow', 'borrow_id'),
		  "borrowCollectionInfo" =>  array(self::BELONGS_TO, 'BorrowCollection', 'borrow_id')
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => '订单ID',
			'site_id' => 'Site',
			'user_id' => '用户ID',
			'status' => '投资状态',
			'debt_status' => '债权状态',
			'debt_type' => '投资类型',
			'type' => '类型',
			'borrow_id' => '项目ID',
			'borrowname' => '项目名称',
			'money' => 'Money',
			'account' => '投资金额',
			'repayment_account' => '总额',
			'interest' => 'Interest',
			'repayment_yesaccount' => '已还总额',
			'wait_account' => '待还总额',
			'wait_interest' => '待还利息',
			'repayment_yesinterest' => '已还利息',
			'agreement_path' => '合同',
			'money_detail' => '真实姓名',
			'addtime' => '投资时间',
			'addip' => '投资IP',
			'realname' => '真实姓名',
            'username' => '用户名',
            'phone' => '电话号码',
            'request_no' => '订单号',
            'invest_device' => '投资设备',
            // 'tender_status' => '投资状态',
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
		//兼容历史省心数据
		if($this->debt_type != ''){
			$debt_type = '';
			//小贷
			if($this->debt_type == 17){
				$debt_type = array(17, 15, 13);
			} elseif ($this->debt_type == 18){//小贷债权转让
				$debt_type = array(14, 16, 18);
			} elseif ($this->debt_type == 27){//典当
				$debt_type = array(23, 25, 27);
			} elseif ($this->debt_type == 28){//典当债权转让
				$debt_type = array(24, 26, 28);
			} elseif ($this->debt_type == 11){//影视
				$debt_type = array(11, 19, 21);
			} elseif ($this->debt_type == 12){//影视债权转让
				$debt_type = array(12, 20, 22);
			} else{
				$debt_type = $this->debt_type;
			}
		}
		$criteria->compare('id',$this->id);
        $criteria->compare('pre_time',$this->pre_time);
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('debt_status',$this->debt_status);
		$criteria->compare('debt_type', $debt_type);
		$criteria->compare('type',$this->type);
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('money',$this->money);
		$criteria->compare('account',$this->account);
		$criteria->compare('coupon_value',$this->coupon_value);
		$criteria->compare('coupon_type',$this->coupon_type);
		$criteria->compare('repayment_account',$this->repayment_account);
		$criteria->compare('interest',$this->interest);
		$criteria->compare('repayment_yesaccount',$this->repayment_yesaccount);
		$criteria->compare('wait_account',$this->wait_account);
		$criteria->compare('wait_interest',$this->wait_interest);
		$criteria->compare('repayment_yesinterest',$this->repayment_yesinterest);
		$criteria->compare('agreement_path',$this->agreement_path);
		$criteria->compare('money_detail',$this->money_detail);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip);
		$criteria->compare('request_no',$this->request_no);
		$criteria->compare('invest_device',$this->invest_device);
        $criteria->order = 'id desc';
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
