<?php

/**
 * This is the model class for table "dw_account_cash".
 *
 * The followings are the available columns in table 'dw_account_cash':
 * @property string $id
 * @property integer $user_id
 * @property integer $status
 * @property integer $cash_type
 * @property string $account
 * @property string $bank
 * @property string $branch
 * @property integer $province
 * @property integer $city
 * @property integer $area
 * @property string $total
 * @property string $credited
 * @property string $fee
 * @property string $use_withdraw_free_detail
 * @property string $remark
 * @property integer $verify_userid
 * @property integer $verify_time
 * @property string $verify_remark
 * @property string $transfer_remark
 * @property string $transfer_num
 * @property integer $transfer_userid
 * @property integer $transfer_time
 * @property string $addtime
 * @property string $addip
 * @property string $trade_no
 */
class AccountCash extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $realname;//用户真实姓名
    public $username;//用户名
    public $timeStart;
    public $timeEnd;
    public $pageSize=200;
	public $phone;//手机号
	public $verify_time_start;//审核时间起
    public $verify_time_end;//审核时间止
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AccountCash the static model class
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
		return 'dw_account_cash';
	}
    
    /**
     * 状态
     */
     /*protected $_status=array('待审核-提现待审核','审核通过待转账-提现处理中','审核拒绝-提现失败','转账成功--提现成功','转账失败--提现失败','银行退票--提现未成功','已取消',100=>'银行处理中');*/
     protected $_status=array(0=>'发起提现',1=>'审核通过待转账',2=>'审核拒绝',3=>'提现成功',4=>'提现失败',5=>'银行退票',6=>'已取消',100=>'银行处理中');
     
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus(){
     	 $IsTwoDays = (time() < ($this->addtime + 172800)) ? 1 : 0;			//发起提现两天的时间点
         if(in_array($this->status, [1,2,3,4,5,6,100])){
         	return $this->_status[$this->status];
         } else if($this->status==0 && !$IsTwoDays){
         	return '提现未成功';
         } else if($this->status==0 && $IsTwoDays){
         	return $this->_status[$this->status];
         }else {
         	return '未知';
         }
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
     } 
     public function getNumberFormatTotal(){
		 return number_format($this->total,2);
	 }
	public function getNumberFormatCredited(){
		return number_format($this->credited,2);
	 }
	public function getNumberFormatFee(){
		return number_format($this->fee,2);
	 }
    /**
     * 状态
     */
     /* protected $_cashType=array(1=>'直接提现',2=>"手工"); */
     protected $_cashType=array(1=>'线上',2=>"手工");
    
     public function getCashType(){
         return $this->_cashType;
     }
     public function StrCashType(){
         return $this->_cashType[$this->cash_type];
     }
     public function getStrCashType($key){
         return (array_key_exists($key, $this->_cashType))?$this->_cashType[$key]:"";
     }   
     
     
    /**
     * 银行分类
     */
     public function getBankType(){
         $usersrcs = Yii::app()->params['bank'];
         return $usersrcs;
     }
     public function StrBankType(){
         $usersrcs = $this->getBankType();
         if(isset($usersrcs[$this->bank]))
            return $usersrcs[$this->bank];
     }
     public function getStrBankType($key){
         $res  = $this->getBankType();
         return (array_key_exists($key, $res?$res[$key]:""));
     }
     
     public function getTradeNo(){
     	return $this->trade_no = $this->trade_no ? $this->trade_no : '--';
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
			//array('user_id, status, cash_type, province, city, area, verify_userid, verify_time, transfer_userid, transfer_time, addtime', 'numerical', 'integerOnly'=>true),
			array('account', 'length', 'max'=>50),
			array('bank', 'length', 'max'=>302),
			array('branch, trade_no, transfer_num', 'length', 'max'=>100),
			array('total, credited, fee', 'length', 'max'=>11),
			array('remark, verify_remark, transfer_remark', 'length', 'max'=>250),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('phone,trade_no,remark,union_bank_id,realname,username,id, user_id, status, cash_type, account, bank, branch, province, city, area, total, credited, fee, use_withdraw_free_detail, verify_userid, verify_time, verify_remark, transfer_remark, transfer_num, transfer_userid, transfer_time, addtime, addip', 'safe', 'on'=>'search'),
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
            "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id','select'=>'user_id,username,realname,phone,card_id'),
        );
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => '订单ID',
			'user_id' => '用户ID',
			'status' => '提现状态',
			'cash_type' => '提现途径',
			'account' => '账号',
			'bank' => '所属银行',
			'phone'=>'手机号',
			'branch' => '支行',
			'province' => '省',
			'city' => '市',
			'area' => '区',
			'total' => '提现总额(元)',
			'credited' => '转账总额(元)',
			'fee' => '手续费(元)',
			'use_withdraw_free_detail' => 'Use Withdraw Free Detail',
			'remark' => '提现设备',
			'verify_userid' => '审核人',
			'verify_time' => '审核时间',
			'verify_remark' => '审核备注',
			'transfer_remark' => '转账结果',
			'transfer_num' => '流水号',
			'transfer_userid' => '转账用户名',
			'transfer_time' => '转账时间',
			'addtime' => '申请时间',
			'addip' => 'IP',
			'realname' => '用户实名',
            'username' => '用户名',
			'trade_no' => '订单流水号',
            'union_bank_id'=>'联行号',
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

		$criteria->compare('union_bank_id',$this->union_bank_id);
		$criteria->compare('id',$this->id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('cash_type',$this->cash_type);
		$criteria->compare('account',$this->account);
		$criteria->compare('bank',$this->bank);
		$criteria->compare('branch',$this->branch);
		$criteria->compare('trade_no',$this->trade_no);
		$criteria->compare('province',$this->province);
		$criteria->compare('city',$this->city);
		$criteria->compare('area',$this->area);
		$criteria->compare('total',$this->total);
		$criteria->compare('credited',$this->credited);
		$criteria->compare('fee',$this->fee);
		$criteria->compare('use_withdraw_free_detail',$this->use_withdraw_free_detail);
		$criteria->compare('remark',$this->remark);
		$criteria->compare('verify_userid',$this->verify_userid);
		$criteria->compare('verify_time',$this->verify_time);
		$criteria->compare('verify_remark',$this->verify_remark);
		$criteria->compare('transfer_remark',$this->transfer_remark);
		$criteria->compare('transfer_num',$this->transfer_num);
		$criteria->compare('transfer_userid',$this->transfer_userid);
		$criteria->compare('transfer_time',$this->transfer_time);
		// if(!empty($this->addtime))
             // $criteria->addBetweenCondition('addtime',$this->timeStart,$this->timeEnd);
        // else
            // $criteria->compare('addtime',$this->addtime);
        //申请时间
        if(!empty($this->timeStart) && !empty($this->timeEnd)){
            $criteria->addBetweenCondition('addtime',$this->timeStart,$this->timeEnd);
        }elseif(empty($this->timeStart)){
            $criteria->compare('addtime','<='.$this->timeEnd);
        }elseif(empty($this->timeEnd)){
            $criteria->compare('addtime','>='.$this->timeStart);
        }else{
            $criteria->compare('addtime',$this->addtime);
        }
        //审核时间
        if(!empty($this->verify_time_start) && !empty($this->verify_time_end)){
            $criteria->addBetweenCondition('verify_time',$this->verify_time_start,$this->verify_time_end);
        }elseif(empty($this->verify_time_start)){
            $criteria->compare('verify_time','<='.$this->verify_time_end);
        }elseif(empty($this->verify_time_end)){
            $criteria->compare('verify_time','>='.$this->verify_time_start);
        }else{
            $criteria->compare('verify_time',$this->verify_time);
        }
        
		$criteria->compare('addip',$this->addip);

		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'addtime DESC', 
            ),
            'pagination'=>array(
                'pageSize'=>!empty($this->pageSize)?$this->pageSize:200,
            ),
			'criteria'=>$criteria,
		));
	}
}
