<?php

/**
 * This is the model class for table "dw_loan_application".
 *
 * The followings are the available columns in table 'dw_loan_application':
 * @property integer $id
 * @property integer $status
 * @property string $company
 * @property string $registration_mark
 * @property string $legal_person
 * @property string $card_id
 * @property string $phone
 * @property string $city
 * @property string $amount
 * @property integer $cycle
 * @property string $description
 * @property string $repayment_source
 * @property integer $dateline
 * @property string $ip
 * @property string $remark
 */
class LoanApplication extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return LoanApplication the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
    /**
     * 状态
     */
     protected $_status=array('未处理','处理中','通过','拒绝');
    
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus(){
         if(isset($this->_status[$this->status]))
            return $this->_status[$this->status];
         else 
            return '未知';
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
     } 
     //融资周期
     protected $_cycle = array('1-3'=>'1-3月','3-6'=>'3-6月','6-9'=>'6-9月',);
    
     public function getCycle(){
         return $this->_cycle;
     }
     public function StrCycle(){
         if(isset($this->_cycle[$this->cycle]))
            return $this->_cycle[$this->cycle];
         else 
            return '未知';
     }
     public function getStrCycle($key){
         return (array_key_exists($key, $this->_cycle))?$this->_cycle[$key]:"";
     } 
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dw_loan_application';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('company, phone, description, repayment_source', 'required'),
			array('status,dateline', 'numerical', 'integerOnly'=>true),
			array('company', 'length', 'max'=>150),
			array('registration_mark, legal_person, card_id, ip', 'length', 'max'=>50),
			array('phone', 'length', 'max'=>15),
			array('city, amount', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
		    array('distribution,id, name,status, company, registration_mark, legal_person, card_id, phone, city, amount, cycle, description, repayment_source, dateline, ip, remark', 'safe'),
        	array('distribution,id, name,status, company, registration_mark, legal_person, card_id, phone, city, amount, cycle, description, repayment_source, dateline, ip, remark', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'status' => '处理状态',
			'company' => '企业名称',
			'registration_mark' => '注册号',
			'legal_person' => '法定代表人/负责人',
			'card_id' => '法人身份证号',
			'phone' => '手机号码',
			'city' => '所在城市',
			'amount' => '融资金额',
			'cycle' => '融资周期',
			'description' => '融资用途',
			'repayment_source' => '抵押物估值',
			'dateline' => '申请时间',
			'ip' => 'Ip',
			'remark' => '处理备注',
			'name'   =>'借款人姓名',
			'distribution'=>'分配给'
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
		$criteria->compare('status',$this->status);
		$criteria->compare('company',$this->company);
		$criteria->compare('registration_mark',$this->registration_mark);
		$criteria->compare('legal_person',$this->legal_person);
		$criteria->compare('card_id',$this->card_id);
		$criteria->compare('phone',$this->phone);
		$criteria->compare('city',$this->city);
        $criteria->compare('name',$this->name);
		$criteria->compare('amount',$this->amount);
		$criteria->compare('cycle',$this->cycle);
		$criteria->compare('description',$this->description);
		$criteria->compare('repayment_source',$this->repayment_source);
         if(!empty($this->dateline))
             $criteria->addBetweenCondition('dateline',strtotime($this->dateline),(strtotime($this->dateline)+86400));
        else
            $criteria->compare('dateline',$this->dateline);
		$criteria->compare('ip',$this->ip);
		$criteria->compare('remark',$this->remark);
        $criteria->addCondition("status!=-1");
		return new CActiveDataProvider($this, array(
		   'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}