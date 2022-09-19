<?php

/**
 * This is the model class for table "offline_upload_user_account_log".
 *
 * The followings are the available columns in table 'offline_upload_user_account_log':
 * @property string $id
 * @property integer $platform_id
 * @property string $file_id
 * @property string $old_user_id
 * @property string $wait_amount
 * @property integer $status
 * @property integer $deal_status
 * @property integer $create_time
 * @property string $update_time
 * @property string $remark
 */
class OfflineUploadUserAccountLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineUploadUserAccountLog the static model class
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
		return 'offline_upload_user_account_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('wait_amount', 'required'),
			array('platform_id, status, deal_status, create_time', 'numerical', 'integerOnly'=>true),
			array('file_id, old_user_id, update_time', 'length', 'max'=>11),
			array('wait_amount', 'length', 'max'=>20),
			array('remark', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, platform_id, file_id, old_user_id, wait_amount, status, deal_status, create_time, update_time, remark', 'safe', 'on'=>'search'),
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
			'platform_id' => 'Platform',
			'file_id' => 'File',
			'old_user_id' => 'Old User',
			'wait_amount' => 'Wait Amount',
			'status' => 'Status',
			'deal_status' => 'Deal Status',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'remark' => 'Remark',
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
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('file_id',$this->file_id,true);
		$criteria->compare('old_user_id',$this->old_user_id,true);
		$criteria->compare('wait_amount',$this->wait_amount,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('deal_status',$this->deal_status);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time,true);
		$criteria->compare('remark',$this->remark,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}