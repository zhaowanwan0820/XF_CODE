<?php

/**
 * This is the model class for table "ag_wx_partial_repay_detail".
 *
 * The followings are the available columns in table 'ag_wx_partial_repay_detail':
 * @property integer $id
 * @property integer $partial_repay_id
 * @property string $name
 * @property integer $end_time
 * @property integer $tender_id
 * @property integer $user_id
 * @property string $repay_money
 * @property integer $status
 * @property string $remark
 * @property integer $addtime
 */
class PHAgWxPartialRepayDetail extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgWxPartialRepayDetail the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return CDbConnection database connection
	 */
	public function getDbConnection()
	{
		return Yii::app()->phdb;
	}
	
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_wx_partial_repay_detail';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name', 'required'),
			array('partial_repay_id, end_time, deal_loan_id, user_id, status, addtime', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>80),
			array('repay_money', 'length', 'max'=>10),
			array('remark', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, repay_yestime, repay_status, deal_id,partial_repay_id, name, end_time, deal_loan_id, user_id, repay_money, status, remark, addtime', 'safe', 'on'=>'search'),
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
			'partial_repay_id' => 'Partial Repay',
			'name' => 'Name',
			'end_time' => 'End Time',
			'deal_loan_id' => 'Tender',
			'user_id' => 'User',
			'repay_money' => 'Repay Money',
			'status' => 'Status',
			'remark' => 'Remark',
			'addtime' => 'Addtime',
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
		$criteria->compare('partial_repay_id',$this->partial_repay_id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('end_time',$this->end_time);
		$criteria->compare('deal_loan_id',$this->deal_loan_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('repay_money',$this->repay_money,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('addtime',$this->addtime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}