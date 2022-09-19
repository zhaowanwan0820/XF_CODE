<?php

/**
 * This is the model class for table "itz_expresspayment".
 *
 * The followings are the available columns in table 'itz_expresspayment':
 * @property string $id
 * @property string $payment_id
 * @property integer $rechargelevel
 * @property integer $bindlevel
 * @property integer $state
 * @property integer $addtime
 * @property string $addip
 * @property integer $adduser
 * @property integer $modifytime
 * @property integer $modifyuser
 * @property string $modifyip
 */
class Expresspayment extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $payment_name;
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Expresspayment the static model class
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
		return 'itz_expresspayment';
	}
    //状态
     protected $_status=array('不可用','可用');
    
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus(){
         if(isset($this->_status[$this->state]))
            return $this->_status[$this->state];
         else 
            return '未知';
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
     } 
     
     //充值优先级开关
     protected $_rswitch=array('关','开');
    
     public function getRswitch(){
         return $this->_rswitch;
     }
     public function StrRswitch(){
         if(isset($this->_rswitch[$this->recharge_switch]))
            return $this->_rswitch[$this->recharge_switch];
         else 
            return '未知';
     }
     public function getStrRswitch($key){
         return (array_key_exists($key, $this->_rswitch))?$this->_rswitch[$key]:"";
     } 
     
     //绑卡优先级开关
     protected $_bswitch=array('关','开');
    
     public function getBswitch(){
         return $this->_bswitch;
     }
     public function StrBswitch(){
         if(isset($this->_bswitch[$this->bind_switch]))
            return $this->_bswitch[$this->bind_switch];
         else 
            return '未知';
     }
     public function getStrBswitch($key){
         return (array_key_exists($key, $this->_bswitch))?$this->_bswitch[$key]:"";
     } 
     
     
     
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('rechargelevel, bindlevel, state, addtime, adduser, modifytime, modifyuser', 'numerical', 'integerOnly'=>true,'message'=>Yii::t('luben','{attribute}只能填写数字')),
			array('payment_id', 'length', 'max'=>10),
			array('addip, modifyip', 'length', 'max'=>20),
			array('payment_id', 'unique', 'message'=>'该记录已存在'),
			//array('rechargelevel,bindlevel', 'unique', 'message'=>'改等级优先级已存在'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('recharge_switch,bind_switch,payment_name,id, payment_id, rechargelevel, bindlevel, state, addtime, addip, adduser, modifytime, modifyuser, modifyip', 'safe'),
			array('recharge_switch,bind_switch,payment_name,id, payment_id, rechargelevel, bindlevel, state, addtime, addip, adduser, modifytime, modifyuser, modifyip', 'safe', 'on'=>'search'),
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
		      "paymentInfo"   =>  array(self::BELONGS_TO, 'Payment', 'payment_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'payment_id' => '通道ID',
			'rechargelevel' => '充值优先级',
			'recharge_switch'=>'充值开关',
			'bind_switch'=>'绑卡开关',
			'bindlevel' => '绑卡优先级',
			'state' => '状态',
			'addtime' => '添加时间',
			'addip' => '添加IP',
			'adduser' => '添加操作人ID',
			'modifytime' => '修改时间',
			'modifyuser' => '修改操作人ID',
			'modifyip' => '修改IP',
			'payment_name'=>'通道名'
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
		$criteria->compare('payment_id',$this->payment_id);
        $criteria->compare('recharge_switch',$this->recharge_switch);
        $criteria->compare('bind_switch',$this->bind_switch);
		$criteria->compare('rechargelevel',$this->rechargelevel);
		$criteria->compare('bindlevel',$this->bindlevel);
		$criteria->compare('state',$this->state);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip);
		$criteria->compare('adduser',$this->adduser);
		$criteria->compare('modifytime',$this->modifytime);
		$criteria->compare('modifyuser',$this->modifyuser);
		$criteria->compare('modifyip',$this->modifyip);
        $criteria->addNotInCondition('state', array(2));
		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}