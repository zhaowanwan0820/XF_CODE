<?php

/**
 * This is the model class for table "offline_deal_loan_repay".
 *
 * The followings are the available columns in table 'offline_deal_loan_repay':
 * @property string $id
 * @property string $deal_id
 * @property string $deal_repay_id
 * @property string $deal_loan_id
 * @property string $loan_user_id
 * @property string $borrow_user_id
 * @property string $money
 * @property integer $type
 * @property integer $time
 * @property integer $real_time
 * @property integer $status
 * @property integer $deal_type
 * @property integer $create_time
 * @property integer $update_time
 * @property string $repaid_amount
 * @property integer $last_part_repay_time
 * @property integer $is_zdx
 * @property integer $old_repay_log_id
 * @property integer $old_repay_num
 * @property integer $platform_id
 */
class OfflineDealLoanRepay extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineDealLoanRepay the static model class
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
		return Yii::app()->offlinedb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'offline_deal_loan_repay';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, time, real_time, status, deal_type, create_time, update_time, last_part_repay_time, is_zdx, old_repay_log_id, old_repay_num, platform_id', 'numerical', 'integerOnly'=>true),
			array('deal_id, deal_repay_id, deal_loan_id, loan_user_id, borrow_user_id', 'length', 'max'=>11),
			array('money', 'length', 'max'=>20),
			array('repaid_amount', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, deal_id, deal_repay_id, deal_loan_id, loan_user_id, borrow_user_id, money, type, time, real_time, status, deal_type, create_time, update_time, repaid_amount, last_part_repay_time, is_zdx, old_repay_log_id, old_repay_num, platform_id', 'safe', 'on'=>'search'),
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
			'deal_id' => 'Deal',
			'deal_repay_id' => 'Deal Repay',
			'deal_loan_id' => 'Deal Loan',
			'loan_user_id' => 'Loan User',
			'borrow_user_id' => 'Borrow User',
			'money' => 'Money',
			'type' => 'Type',
			'time' => 'Time',
			'real_time' => 'Real Time',
			'status' => 'Status',
			'deal_type' => 'Deal Type',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'repaid_amount' => 'Repaid Amount',
			'last_part_repay_time' => 'Last Part Repay Time',
			'is_zdx' => 'Is Zdx',
			'old_repay_log_id' => 'Old Repay Log',
			'old_repay_num' => 'Old Repay Num',
			'platform_id' => 'Platform',
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
		$criteria->compare('deal_id',$this->deal_id,true);
		$criteria->compare('deal_repay_id',$this->deal_repay_id,true);
		$criteria->compare('deal_loan_id',$this->deal_loan_id,true);
		$criteria->compare('loan_user_id',$this->loan_user_id,true);
		$criteria->compare('borrow_user_id',$this->borrow_user_id,true);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('time',$this->time);
		$criteria->compare('real_time',$this->real_time);
		$criteria->compare('status',$this->status);
		$criteria->compare('deal_type',$this->deal_type);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('repaid_amount',$this->repaid_amount,true);
		$criteria->compare('last_part_repay_time',$this->last_part_repay_time);
		$criteria->compare('is_zdx',$this->is_zdx);
		$criteria->compare('old_repay_log_id',$this->old_repay_log_id);
		$criteria->compare('old_repay_num',$this->old_repay_num);
		$criteria->compare('platform_id',$this->platform_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}