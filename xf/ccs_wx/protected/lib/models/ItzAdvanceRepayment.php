<?php

/**
 * This is the model class for table "itz_advance_repayment".
 *
 * The followings are the available columns in table 'itz_advance_repayment':
 * @property integer $id
 * @property integer $borrow_id
 * @property integer $status
 * @property string $interest_extra
 * @property string $capital
 * @property string $interest
 * @property string $interest_cancel
 * @property integer $settlement_status
 * @property integer $op_user_id
 * @property integer $process_time
 * @property integer $addtime
 * @property string $addip
 */
class ItzAdvanceRepayment extends DwActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzAdvanceRepayment the static model class
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
		return Yii::app()->dwdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_advance_repayment';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('borrow_id, capital, addtime', 'required'),
			array('borrow_id, status, settlement_status, op_user_id, process_time, addtime', 'numerical', 'integerOnly'=>true),
			array('interest_extra, capital', 'length', 'max'=>255),
			array('interest, interest_cancel', 'length', 'max'=>512),
			array('addip', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, borrow_id, status, interest_extra, capital, interest, interest_cancel, settlement_status, op_user_id, process_time, addtime, addip', 'safe', 'on'=>'search'),
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
			'borrow_id' => 'Borrow',
			'status' => 'Status',
			'interest_extra' => 'Interest Extra',
			'capital' => 'Capital',
			'interest' => 'Interest',
			'interest_cancel' => 'Interest Cancel',
			'settlement_status' => 'Settlement Status',
			'op_user_id' => 'Op User',
			'process_time' => 'Process Time',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
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
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('interest_extra',$this->interest_extra,true);
		$criteria->compare('capital',$this->capital,true);
		$criteria->compare('interest',$this->interest,true);
		$criteria->compare('interest_cancel',$this->interest_cancel,true);
		$criteria->compare('settlement_status',$this->settlement_status);
		$criteria->compare('op_user_id',$this->op_user_id);
		$criteria->compare('process_time',$this->process_time);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}