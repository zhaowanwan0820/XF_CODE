<?php

/**
 * This is the model class for table "itz_overdue_repayment".
 *
 * The followings are the available columns in table 'itz_overdue_repayment':
 * @property integer $id
 * @property integer $borrow_id
 * @property integer $status
 * @property string $interest
 * @property string $capital
 * @property string $risk_insurance
 * @property string $overdue_suspend_interest
 * @property string $overdue_suspend_capital
 * @property string $overdue_interest
 * @property string $overdue_interest_extra
 * @property integer $settlement_interest_status
 * @property integer $settlement_capital_status
 * @property integer $settlement_risk_interest_status
 * @property integer $settlement_risk_capital_status
 * @property integer $settlement_overdue_interest_status
 * @property integer $op_user_id
 * @property integer $process_time
 * @property integer $overdue_process_time
 * @property integer $addtime
 * @property string $addip
 */
class ItzOverdueRepayment extends DwActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzOverdueRepayment the static model class
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
		return 'itz_overdue_repayment';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('borrow_id, addtime', 'required'),
			array('borrow_id, status, settlement_interest_status, settlement_capital_status, settlement_risk_interest_status, settlement_risk_capital_status, settlement_overdue_interest_status, process_time, overdue_process_time, addtime', 'numerical', 'integerOnly'=>true),
			array('interest, capital, overdue_suspend_interest, overdue_suspend_capital, overdue_interest, overdue_interest_extra', 'length', 'max'=>127),
			array('op_user_id, risk_insurance', 'length', 'max'=>11),
			array('addip', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, borrow_id, status, interest, capital, risk_insurance, overdue_suspend_interest, overdue_suspend_capital, overdue_interest, overdue_interest_extra, settlement_interest_status, settlement_capital_status, settlement_risk_interest_status, settlement_risk_capital_status, settlement_overdue_interest_status, op_user_id, process_time, overdue_process_time, addtime, addip', 'safe', 'on'=>'search'),
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
			'interest' => 'Interest',
			'capital' => 'Capital',
			'risk_insurance' => 'Risk Insurance',
			'overdue_suspend_interest' => 'Overdue Suspend Interest',
			'overdue_suspend_capital' => 'Overdue Suspend Capital',
			'overdue_interest' => 'Overdue Interest',
			'overdue_interest_extra' => 'Overdue Interest Extra',
			'settlement_interest_status' => 'Settlement Interest Status',
			'settlement_capital_status' => 'Settlement Capital Status',
			'settlement_risk_interest_status' => 'Settlement Risk Interest Status',
			'settlement_risk_capital_status' => 'Settlement Risk Capital Status',
			'settlement_overdue_interest_status' => 'Settlement Overdue Interest Status',
			'op_user_id' => 'Op User',
			'process_time' => 'Process Time',
			'overdue_process_time' => 'Overdue Process Time',
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
		$criteria->compare('interest',$this->interest,true);
		$criteria->compare('capital',$this->capital,true);
		$criteria->compare('risk_insurance',$this->risk_insurance,true);
		$criteria->compare('overdue_suspend_interest',$this->overdue_suspend_interest,true);
		$criteria->compare('overdue_suspend_capital',$this->overdue_suspend_capital,true);
		$criteria->compare('overdue_interest',$this->overdue_interest,true);
		$criteria->compare('overdue_interest_extra',$this->overdue_interest_extra,true);
		$criteria->compare('settlement_interest_status',$this->settlement_interest_status);
		$criteria->compare('settlement_capital_status',$this->settlement_capital_status);
		$criteria->compare('settlement_risk_interest_status',$this->settlement_risk_interest_status);
		$criteria->compare('settlement_risk_capital_status',$this->settlement_risk_capital_status);
		$criteria->compare('settlement_overdue_interest_status',$this->settlement_overdue_interest_status);
		$criteria->compare('op_user_id',$this->op_user_id);
		$criteria->compare('process_time',$this->process_time);
		$criteria->compare('overdue_process_time',$this->overdue_process_time);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}