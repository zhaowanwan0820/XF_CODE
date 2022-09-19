<?php

/**
 * This is the model class for table "dw_sms_report_log".
 *
 * The followings are the available columns in table 'dw_sms_report_log':
 * @property string $submitdate
 * @property string $seqid
 * @property string $servicecodeadd
 * @property string $mobile
 * @property string $status
 * @property string $errorcode
 * @property string $memo
 * @property string $receivedate
 * @property string $dateline
 */
class SmsReportLog extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $selstatus;//状态
    public $operator;//运营商
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return SmsReportLog the static model class
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
		return 'dw_sms_report_log';
	}

     /**
     * 状态
     */
     
     protected $_guarantorStatus=array('成功',"失败");
    
     public function getGuarantorStatus(){
         return $this->_guarantorStatus;
     }
     public function StrGuarantorStatus(){
         return $this->_guarantorStatus[$this->status];
     }
     public function getStrGuarantorStatus($key){
         return (array_key_exists($key, $this->_guarantorStatus))?$this->_guarantorStatus[$key]:"";
     } 
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('servicecodeadd, mobile, status, errorcode, memo', 'required'),
			array('submitdate, seqid, receivedate, dateline', 'length', 'max'=>10),
			array('servicecodeadd', 'length', 'max'=>50),
			array('mobile', 'length', 'max'=>15),
			array('status', 'length', 'max'=>5),
			array('errorcode, memo', 'length', 'max'=>500),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('operator,selstatus,submitdate, seqid, servicecodeadd, mobile, status, errorcode, memo, receivedate, dateline', 'safe', 'on'=>'search'),
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
			'submitdate' => '发送时间',
			'seqid' => '消息流水号',
			'servicecodeadd' => '下行服务号码',
			'mobile' => '手机号码',
			'status' => '状态',
			'errorcode' => '错误编码',
			'memo' => '备注',
			'receivedate' => '接收时间',
			'dateline' => '所需时长',
			'selstatus'=>'状态报告值',
			'operator'=>'运营商',
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

		$criteria->compare('seqid',$this->seqid);
		$criteria->compare('servicecodeadd',$this->servicecodeadd);
		$criteria->compare('mobile',$this->mobile);
		$criteria->compare('status',$this->status);
		$criteria->compare('errorcode',$this->errorcode);
		$criteria->compare('memo',$this->memo);
        if(!empty($this->receivedate))
           $criteria->addBetweenCondition('receivedate',strtotime($this->receivedate),(strtotime($this->receivedate)+86400));
        else
            $criteria->compare('receivedate',$this->receivedate);
        
        if(!empty($this->submitdate))
             $criteria->addBetweenCondition('submitdate',strtotime($this->submitdate),(strtotime($this->submitdate)+86400));
        else
            $criteria->compare('submitdate',$this->submitdate);
        
		$criteria->compare('dateline',$this->dateline,true);

		return new CActiveDataProvider($this, array(
		      'sort'=>array(
                'defaultOrder'=>'seqid DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}