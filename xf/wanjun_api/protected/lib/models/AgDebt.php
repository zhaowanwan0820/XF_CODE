<?php

/**
 * This is the model class for table "ag_debt".
 *
 * The followings are the available columns in table 'ag_debt':
 * @property string $id
 * @property integer $tender_id
 * @property integer $user_id
 * @property integer $status
 * @property string $amount
 * @property string $sold_amount
 * @property string $discount
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $success_time
 * @property string $serial_number
 * @property integer $debt_src
 */
class AgDebt extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgDebt the static model class
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
		return Yii::app()->agdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_debt';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('tender_id, user_id, status, start_time, end_time, success_time, debt_src', 'numerical', 'integerOnly'=>true),
			array('amount, sold_amount, discount', 'length', 'max'=>11),
			array('serial_number', 'length', 'max'=>127),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, purchase_order_id,tender_id, project_type_id, platform_id, project_id, user_id, status, amount, sold_amount, discount, start_time, end_time, success_time, serial_number,apr, debt_src,addtime,addip', 'safe', 'on'=>'search'),
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
			'tender_id' => 'Tender',
			'user_id' => 'User',
			'status' => 'Status',
			'amount' => 'Amount',
			'sold_amount' => 'Sold Amount',
			'discount' => 'Discount',
			'start_time' => 'Start Time',
			'end_time' => 'End Time',
			'success_time' => 'Success Time',
			'serial_number' => 'Serial Number',
			'debt_src' => 'Debt Src',
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('tender_id',$this->tender_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('amount',$this->amount,true);
		$criteria->compare('sold_amount',$this->sold_amount,true);
		$criteria->compare('discount',$this->discount,true);
		$criteria->compare('start_time',$this->start_time);
		$criteria->compare('end_time',$this->end_time);
		$criteria->compare('success_time',$this->success_time);
		$criteria->compare('serial_number',$this->serial_number,true);
		$criteria->compare('debt_src',$this->debt_src);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}