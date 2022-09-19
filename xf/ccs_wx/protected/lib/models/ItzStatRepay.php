<?php

/**
 * This is the model class for table "itz_stat_repay".
 *
 * The followings are the available columns in table 'itz_stat_repay':
 * @property string $id
 * @property string $borrow_id
 * @property string $value_time
 * @property string $repay_time
 * @property string $repay_money
 * @property string $capital
 * @property string $interest
 * @property string $addtime
 */
class ItzStatRepay extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $borrow_name;
    public $company; //企业名称
    public $account;
    public $guarantor_name;//合作金融机构、
    public $is_maturity; //是否到期
    
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzStatRepay the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
    
    //到期时间
     protected $_is_maturity=array('否','是');
    
     public function getIsMaturity(){
         return $this->_is_maturity;
     }
    
    //还款状态
     protected $_status=array('未知','已还','未还');
    
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus(){
         if(isset($this->_status[$this->repay_status]))
            return $this->_status[$this->repay_status];
         else 
            return '未知';
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
     }
     
     //获取企业名称
     public function getCompany($borrow_id){
         $borrowInfo = Borrow::model()->findByPk($borrow_id);
         $companyInfo = User::model()->findByPk($borrowInfo->user_id);
         return $companyInfo->username;
     }
     
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_stat_repay';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('borrow_id, value_time, repay_time, repay_money, capital, interest, addtime', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('is_maturity,guarantor_name,repay_status,account,borrow_name,company,id, borrow_id, value_time, repay_time, repay_money, capital, interest, addtime', 'safe', 'on'=>'search'),
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
		  "BorrowInfo"   =>  array(self::BELONGS_TO, 'Borrow', 'borrow_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'borrow_id' => '项目ID',
			'value_time' => '每月起息时间',
			'repay_time' => '还款时间',
			'repay_money' => '还款总额',
			'borrow_name' => '项目名称',
			'company' => '企业名称',
			'account' => '借款金额',
			'capital' => '本金',
			'interest' => '利息',
			'addtime' => '统计时间',
			'repay_status'=>'还款状态',
			'is_maturity'=>'是否到期',
			'guarantor_name'=>'合作金融机构',
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
        $criteria->compare('repay_status',$this->repay_status);
		$criteria->compare('borrow_id',$this->borrow_id);
        if(!empty($this->value_time))
             $criteria->addBetweenCondition('value_time',strtotime($this->value_time),(strtotime($this->value_time)+86399));
        else
            $criteria->compare('value_time',$this->value_time);
        
		if(!empty($this->repay_time))
             $criteria->addBetweenCondition('repay_time',strtotime($this->repay_time),(strtotime($this->repay_time)+86399));
        else
            $criteria->compare('repay_time',$this->repay_time);
		$criteria->compare('repay_money',$this->repay_money);
		$criteria->compare('capital',$this->capital);
		$criteria->compare('interest',$this->interest);
		$criteria->compare('addtime',$this->addtime);

		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'repay_time DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}