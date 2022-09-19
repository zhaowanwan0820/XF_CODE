<?php

/**
 * This is the model class for table "dw_customer_system".
 *
 * The followings are the available columns in table 'dw_customer_system':
 * @property string $id
 * @property integer $user_id
 * @property integer $invite_userid
 * @property integer $type_id
 * @property string $username
 * @property string $realname
 * @property string $birthday
 * @property string $sex
 * @property string $phone
 * @property string $email
 * @property integer $real_status
 * @property integer $email_status
 * @property integer $phone_status
 * @property string $user_src
 * @property integer $isinvested
 * @property integer $isvalidpromotion
 * @property string $total
 * @property string $use_money
 * @property string $use_virtual_money
 * @property string $invested_money
 * @property string $recharge_amount
 * @property integer $totals
 * @property string $callback_remark
 * @property integer $callback_time
 * @property integer $s_user_id
 * @property string $s_realname
 * @property integer $expiration_time
 */
class CustomerSystem extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CustomerSystem the static model class
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
		return 'dw_customer_system';
	}

    /**
     * 性别类型
     */
     protected $_sexType=array(1=>'男',2=>'女');
    
     public function getSexType(){
         return $this->_sexType;
     }
     public function StrSexType(){
         if($this->sex)
            return $this->_sexType[$this->sex];
     }
     public function getStrSexType($key){
         return (array_key_exists($key, $this->_sexType))?$this->_sexType[$key]:"选择类型";
     }
     
      /**
     * 用户来源
     */
     public function getUsersrcType(){
         $usersrcs = Yii::app()->params['user_src'];
         $itzChannelList = ItzChannel::model()->findAll();
         foreach ($itzChannelList as $key => $value) {
             $usersrcs[$value->id] = $value->name;
         }
         return $usersrcs;
     }
     public function StrUsersrcType(){
         $usersrcs = $this->getUsersrcType();
         if(isset($usersrcs[$this->user_src]))
            return $usersrcs[$this->user_src];
     }
     public function getStrUsersrcType($key){
         $res  = $this->getUsersrcType();
         return (array_key_exists($key, $res?$res[$key]:""));
     }
     
     
    /**
     * 实名认证状态
     */
     protected $_real_status=array("未处理","审核通过","等待审核","审核不通过");
    
     public function getRealStatus(){
         return $this->_real_status;
     }
     public function StrRealStatus(){
         if(isset($this->real_status))
            return $this->_real_status[$this->real_status];
     }
     public function getStrRealStatus($key){
         return (array_key_exists($key, $this->_real_status))?$this->_real_status[$key]:"选择类型";
     }
     
     
    /**
     * 邮箱状态
     */
     protected $_email_status=array("未认证","已认证");
    
     public function getEmailStatus(){
         return $this->_email_status;
     }
     public function StrEmailStatus(){
         if(isset($this->email_status) && isset($this->_email_status[$this->email_status]))
            return $this->_email_status[$this->email_status];
     }
     public function getStrEmailStatus($key){
         return (array_key_exists($key, $this->_email_status))?$this->_email_status[$key]:"选择类型";
     }
     
    /**
     * 手机认证状态
     */
     protected $_phone_status=array("未认证","已认证");
    
     public function getPhoneStatus(){
         return $this->_phone_status;
     }
     public function StrPhoneStatus(){
         if(isset($this->phone_status) && isset($this->_phone_status[$this->phone_status]))
            return $this->_phone_status[$this->phone_status];
     }
     public function getStrPhoneStatus($key){
         return (array_key_exists($key, $this->_phone_status))?$this->_phone_status[$key]:"选择类型";
     }
     
     /**
     * 生日筛选
     */
     protected $_birthday=array(1=>"全部用户",2=>"近期生日用户");
    
     public function getBirthday(){
         return $this->_birthday;
     }
     public function StrBirthday(){
         if(isset($this->birthday) && isset($this->_birthday[$this->birthday]))
            return $this->_birthday[$this->birthday];
     }
     public function getStrBirthday($key){
         return (array_key_exists($key, $this->_birthday))?$this->_birthday[$key]:"选择类型";
     }
    /**
     * 投资状态
     */
     protected $_isinvested=array("未知",'有效',"无效");
    
     public function getIsinvested(){
         return $this->_isinvested;
     }
     public function StrIsinvested(){
         if(isset($this->isinvested))
            return $this->_isinvested[$this->isinvested];
     }
     public function getStrIsinvested($key){
         return (array_key_exists($key, $this->_isinvested))?$this->_isinvested[$key]:"选择类型";
     }
     
    /**
     * 推广状态
     */
     protected $_isvalidpromotion=array("未知","有效","无效");
    
     public function getIsvalidpromotion(){
         return $this->_isvalidpromotion;
     }
     public function StrIsvalidpromotion(){
         if(isset($this->isvalidpromotion))
            return $this->_isvalidpromotion[$this->isvalidpromotion];
     }
     public function getStrIsvalidpromotion($key){
         return (array_key_exists($key, $this->_isvalidpromotion))?$this->_isvalidpromotion[$key]:"选择类型";
     }
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id,user_id,collection, invite_userid, username, realname, birthday,addtime,sex, phone, email, real_status, email_status, phone_status, user_src, isinvested, isvalidpromotion, total, use_money, use_virtual_money, invested_money, recharge_amount, totals, callback_remark, callback_time, s_user_id, s_realname, expiration_time', 'safe'),
            array('id,user_id,collection, invite_userid, username, realname, birthday,addtime,sex, phone, email, real_status, email_status, phone_status, user_src, isinvested, isvalidpromotion, total, use_money, use_virtual_money, invested_money, recharge_amount, totals, callback_remark, callback_time, s_user_id, s_realname, expiration_time', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
		      "inviteInfo"   =>  array(self::BELONGS_TO, 'User', 'invite_userid'),
		      "userInfo"   =>  array(self::BELONGS_TO, 'ItzUser', 's_user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => '用户ID',
			'invite_userid' => '推荐人',
			'username' => '用户名',
			'realname' => '真实姓名',
			'birthday' => '生日筛选',
			'addtime' => '注册时间',
			'sex' => '性别',
			'phone' => '手机',
			'email' => '邮箱',
			'real_status' => '实名认证',
			'email_status' => '邮箱认证',
			'phone_status' => '手机认证',
			'user_src' => '用户来源',
			'isinvested' => '投资状态',
			'isvalidpromotion' => '推广状态',
			'total' => '账户余额',
			'use_money' => '可用金额',
			'use_virtual_money' => '可用优惠券总额',
			'invested_money' => '累计投资',
			'recharge_amount' => '累计充值',
			'totals' => '投资总笔数',
			'callback_remark' => '回呼信息',
			'callback_time' => '回呼时间',
			's_user_id' => '最后回呼客服',
			's_realname' => '回呼客服用户名',
			'expiration_time' => '项目到期最近时间',
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
        $order = 'user_id DESC';
        if(!empty($this->username)){
            $criteria->select = "* ,case when username like '%".$this->username."%' then (length(username)-length('".$this->username."')) end as rn ";
            $order      = ' rn ';
        }
		$criteria->compare('id',$this->id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('invite_userid',$this->invite_userid);
		$criteria->compare('username',$this->username,TRUE);
		$criteria->compare('realname',$this->realname);
        if(!empty($this->birthday) && ($this->birthday)==2){
            $criteria->condition="DATE_FORMAT(from_unixtime(birthday),'%m-%d') >= DATE_FORMAT(now(),'%m-%d')  
         and DATE_FORMAT(from_unixtime(birthday),'%m-%d') <= DATE_FORMAT(date_add(now(),  interval 7 day),'%m-%d')";
        }else{
            $this->birthday = NULL;
            $criteria->compare('birthday',$this->birthday);
        }
        if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('addtime',$this->addtime);
        
		$criteria->compare('sex',$this->sex);
		$criteria->compare('phone',$this->phone,TRUE);
		$criteria->compare('email',$this->email,TRUE);
		$criteria->compare('real_status',$this->real_status);
		$criteria->compare('email_status',$this->email_status);
		$criteria->compare('phone_status',$this->phone_status);
		$criteria->compare('user_src',$this->user_src);
		$criteria->compare('isinvested',$this->isinvested);
		$criteria->compare('isvalidpromotion',$this->isvalidpromotion);
		$criteria->compare('total',$this->total);
		$criteria->compare('use_money',$this->use_money);
		$criteria->compare('use_virtual_money',$this->use_virtual_money);
		$criteria->compare('invested_money',$this->invested_money);
		$criteria->compare('recharge_amount',$this->recharge_amount);
		$criteria->compare('totals',$this->totals);
		$criteria->compare('callback_remark',$this->callback_remark);
		$criteria->compare('callback_time',$this->callback_time);
		$criteria->compare('s_user_id',$this->s_user_id);
		$criteria->compare('s_realname',$this->s_realname);
         if(!empty($this->expiration_time))
             $criteria->addBetweenCondition('expiration_time',strtotime($this->expiration_time),(strtotime($this->expiration_time)+86400));
        else
            $criteria->compare('expiration_time',$this->expiration_time);

		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>$order, 
            ),
			'criteria'=>$criteria,
		));
	}

}