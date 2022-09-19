<?php

/**
 * This is the model class for table "ag_wx_assignee_info".
 *
 * The followings are the available columns in table 'ag_wx_assignee_info':
 * @property string $id
 * @property string $user_id
 * @property string $transferability_limit
 * @property string $transferred_amount
 * @property string $remaining_transferable_amount
 * @property string $agreement_url
 * @property integer $status
 * @property string $transfer_order
 * @property string $add_user_id
 * @property string $add_ip
 * @property string $add_time
 * @property string $update_user_id
 * @property string $update_ip
 * @property string $update_time
 */
class AgWxAssigneeInfo extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgWxAssigneeInfo the static model class
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
		return 'ag_wx_assignee_info';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status', 'numerical', 'integerOnly'=>true),
			array('user_id, transferability_limit, transferred_amount, remaining_transferable_amount, transfer_order, add_user_id, add_time, update_user_id, update_time', 'length', 'max'=>10),
			array('agreement_url, add_ip, update_ip', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, transferability_limit, transferred_amount, remaining_transferable_amount, agreement_url, status, transfer_order, add_user_id, add_ip, add_time, update_user_id, update_ip, update_time', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'transferability_limit' => 'Transferability Limit',
			'transferred_amount' => 'Transferred Amount',
			'remaining_transferable_amount' => 'Remaining Transferable Amount',
			'agreement_url' => 'Agreement Url',
			'status' => 'Status',
			'transfer_order' => 'Transfer Order',
			'add_user_id' => 'Add User',
			'add_ip' => 'Add Ip',
			'add_time' => 'Add Time',
			'update_user_id' => 'Update User',
			'update_ip' => 'Update Ip',
			'update_time' => 'Update Time',
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
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('transferability_limit',$this->transferability_limit,true);
		$criteria->compare('transferred_amount',$this->transferred_amount,true);
		$criteria->compare('remaining_transferable_amount',$this->remaining_transferable_amount,true);
		$criteria->compare('agreement_url',$this->agreement_url,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('transfer_order',$this->transfer_order,true);
		$criteria->compare('add_user_id',$this->add_user_id,true);
		$criteria->compare('add_ip',$this->add_ip,true);
		$criteria->compare('add_time',$this->add_time,true);
		$criteria->compare('update_user_id',$this->update_user_id,true);
		$criteria->compare('update_ip',$this->update_ip,true);
		$criteria->compare('update_time',$this->update_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}