<?php

/**
 * This is the model class for table "itz_mobile_bankcard".
 *
 * The followings are the available columns in table 'itz_mobile_bankcard':
 * @property integer $id
 * @property string $card_number
 * @property integer $bank_id
 * @property string $union_bank_id
 * @property string $branch_name
 * @property string $agreement_number
 * @property string $phone
 * @property integer $user_id
 * @property integer $status
 * @property integer $is_default
 * @property string $addtime
 * @property string $addip
 */
class ItzMobileBankcard extends DwActiveRecord
{
    public $dbname = 'dwdb';
	public $username;
	public $bank_name;
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzMobileBankcard the static model class
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
		return 'itz_mobile_bankcard';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array(' branch_name,union_bank_id', 'required'),
			array('bank_id, user_id, status, is_default', 'numerical', 'integerOnly'=>true),
			array('card_number', 'length', 'max'=>55),
			array('union_bank_id', 'length', 'max'=>20),
			array('branch_name', 'length', 'max'=>100),
			array('agreement_number, phone, addtime, addip', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('username,id,bank_name, card_number, bank_id, union_bank_id, branch_name, agreement_number, phone, user_id, status, is_default, addtime, addip', 'safe', 'on'=>'search'),
		);
	}
	
	
	protected function bankName(){
		$res = ItzBank::model()->findAll();
		$banks = array();
		foreach ($res as $key => $value) {
			$banks[$value->bank_id] = $value->bank_name;
		}
		return $banks;
	}
	public function getBankName(){
         return $this->bankName();
     }
     public function StrBankName(){
         if(isset($this->bank_name) && isset($this->bankName[$this->bank_name]))
            return $this->bankName[$this->bank_name];
     }
     public function getStrBankName($key){
         return (array_key_exists($key, $this->bankName))?$this->bankName[$key]:"选择类型";
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
			"bankNameInfo"   =>  array(self::BELONGS_TO, 'ItzBank', 'bank_id'),
		);
	}
	
	protected $_statusType=array('未绑定','已绑定','已解绑');
    
     public function getIsinvestedType(){
         return $this->_statusType;
     }
     public function StrIsinvestedType(){
         return $this->_statusType[$this->status];
     }
     public function getStrIsinvestedType($key){
         return (array_key_exists($key, $this->_statusType))?$this->_statusType[$key]:"";
     }
	/*
    public function relations()
	{
		return array(
		      "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'username' => '用户名',
			'phone' => '手机号',
			'card_number' => '银行卡号',
			'bank_id' => '所属银行',
			'branch_name' => '分支行信息',
			'agreement_number' => '绑定协议号',
			'user_id' => 'User',
			'status' => '绑定状态',
			'is_default' => 'Is Default',
			'addtime' => '提交时间',
			'addip' => 'Addip',
			'bank_name' => '所属银行',
			'union_bank_id' => '联行号',
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
		$criteria->compare('username',$this->username);
		$criteria->compare('card_number',$this->card_number);
		$criteria->compare('bank_id',$this->bank_id);
		$criteria->compare('union_bank_id',$this->union_bank_id);
		$criteria->compare('branch_name',$this->branch_name);
		$criteria->compare('agreement_number',$this->agreement_number);
		$criteria->compare('phone',$this->phone);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('is_default',$this->is_default);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip);
		$criteria->compare('bank_name',$this->bank_name);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	public function bankFind(){
        $this->bank_name = isset($this->bankNameInfo->bank_name)?$this->bankNameInfo->bank_name:"";
    }
	public function nameFind(){
        $this->username = isset($this->userInfo->username)?$this->userInfo->username:"";
    }
}