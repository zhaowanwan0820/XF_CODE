<?php

/**
 * This is the model class for table "offline_deal_load".
 *
 * The followings are the available columns in table 'offline_deal_load':
 * @property integer $id
 * @property integer $deal_id
 * @property integer $user_id
 * @property string $user_name
 * @property string $user_deal_name
 * @property double $money
 * @property string $wait_capital
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $is_repay
 * @property integer $from_deal_id
 * @property integer $deal_parent_id
 * @property integer $site_id
 * @property integer $source_type
 * @property string $ip
 * @property string $short_alias
 * @property integer $deal_type
 * @property integer $debt_status
 * @property integer $debt_type
 * @property integer $is_debt_confirm
 * @property integer $debt_confirm_time
 * @property integer $black_status
 * @property integer $update_black_time
 * @property string $join_reason
 * @property integer $repay_way
 * @property integer $repay_plan_id
 * @property integer $confirm_repay_time
 * @property string $repay_capital_init
 * @property integer $debt_batch_number
 * @property integer $clear_status
 * @property string $clear_amount
 * @property integer $status
 * @property string $wait_interest
 * @property string $yes_interest
 * @property string $order_sn
 * @property integer $platform_id
 * @property string $receivable_interest
 */
class OfflineDealLoad extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineDealLoad the static model class
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
		return 'offline_deal_load';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('deal_id, user_id, create_time, update_time, is_repay, from_deal_id, deal_parent_id, site_id, source_type, deal_type, debt_status, debt_type, is_debt_confirm, debt_confirm_time, black_status, update_black_time, repay_way, repay_plan_id, confirm_repay_time, debt_batch_number, clear_status, status, platform_id', 'numerical', 'integerOnly'=>true),
			array('money', 'numerical'),
			array('user_name, user_deal_name, ip', 'length', 'max'=>50),
			array('wait_capital, short_alias, repay_capital_init, clear_amount, wait_interest, yes_interest, order_sn', 'length', 'max'=>20),
			array('join_reason', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('receivable_interest, id, deal_id, user_id, user_name, user_deal_name, money, wait_capital, create_time, update_time, is_repay, from_deal_id, deal_parent_id, site_id, source_type, ip, short_alias, deal_type, debt_status, debt_type, is_debt_confirm, debt_confirm_time, black_status, update_black_time, join_reason, repay_way, repay_plan_id, confirm_repay_time, repay_capital_init, debt_batch_number, clear_status, clear_amount, status, wait_interest, yes_interest, order_sn, platform_id', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'user_name' => 'User Name',
			'user_deal_name' => 'User Deal Name',
			'money' => 'Money',
			'wait_capital' => 'Wait Capital',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'is_repay' => 'Is Repay',
			'from_deal_id' => 'From Deal',
			'deal_parent_id' => 'Deal Parent',
			'site_id' => 'Site',
			'source_type' => 'Source Type',
			'ip' => 'Ip',
			'short_alias' => 'Short Alias',
			'deal_type' => 'Deal Type',
			'debt_status' => 'Debt Status',
			'debt_type' => 'Debt Type',
			'is_debt_confirm' => 'Is Debt Confirm',
			'debt_confirm_time' => 'Debt Confirm Time',
			'black_status' => 'Black Status',
			'update_black_time' => 'Update Black Time',
			'join_reason' => 'Join Reason',
			'repay_way' => 'Repay Way',
			'repay_plan_id' => 'Repay Plan',
			'confirm_repay_time' => 'Confirm Repay Time',
			'repay_capital_init' => 'Repay Capital Init',
			'debt_batch_number' => 'Debt Batch Number',
			'clear_status' => 'Clear Status',
			'clear_amount' => 'Clear Amount',
			'status' => 'Status',
			'wait_interest' => 'Wait Interest',
			'yes_interest' => 'Yes Interest',
			'order_sn' => 'Order Sn',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('deal_id',$this->deal_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('user_name',$this->user_name,true);
		$criteria->compare('user_deal_name',$this->user_deal_name,true);
		$criteria->compare('money',$this->money);
		$criteria->compare('wait_capital',$this->wait_capital,true);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('is_repay',$this->is_repay);
		$criteria->compare('from_deal_id',$this->from_deal_id);
		$criteria->compare('deal_parent_id',$this->deal_parent_id);
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('source_type',$this->source_type);
		$criteria->compare('ip',$this->ip,true);
		$criteria->compare('short_alias',$this->short_alias,true);
		$criteria->compare('deal_type',$this->deal_type);
		$criteria->compare('debt_status',$this->debt_status);
		$criteria->compare('debt_type',$this->debt_type);
		$criteria->compare('is_debt_confirm',$this->is_debt_confirm);
		$criteria->compare('debt_confirm_time',$this->debt_confirm_time);
		$criteria->compare('black_status',$this->black_status);
		$criteria->compare('update_black_time',$this->update_black_time);
		$criteria->compare('join_reason',$this->join_reason,true);
		$criteria->compare('repay_way',$this->repay_way);
		$criteria->compare('repay_plan_id',$this->repay_plan_id);
		$criteria->compare('confirm_repay_time',$this->confirm_repay_time);
		$criteria->compare('repay_capital_init',$this->repay_capital_init,true);
		$criteria->compare('debt_batch_number',$this->debt_batch_number);
		$criteria->compare('clear_status',$this->clear_status);
		$criteria->compare('clear_amount',$this->clear_amount,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('wait_interest',$this->wait_interest,true);
		$criteria->compare('yes_interest',$this->yes_interest,true);
		$criteria->compare('order_sn',$this->order_sn,true);
		$criteria->compare('platform_id',$this->platform_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}