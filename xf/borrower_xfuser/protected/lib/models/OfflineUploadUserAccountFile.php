<?php

/**
 * This is the model class for table "offline_upload_user_account_file".
 *
 * The followings are the available columns in table 'offline_upload_user_account_file':
 * @property string $id
 * @property string $file_name
 * @property string $file_path
 * @property integer $platform_id
 * @property string $total_num
 * @property string $success_num
 * @property string $fail_num
 * @property string $total_amount
 * @property string $success_wait_amount
 * @property string $fail_wait_amount
 * @property integer $action_admin_id
 * @property integer $auth_admin_id
 * @property string $auth_time
 * @property integer $auth_status
 * @property string $addtime
 * @property integer $deal_status
 * @property string $update_time
 * @property string $remark
 * @property string $action_user_name
 * @property string $auth_user_name
 * @property string $handle_success_num
 * @property string $handle_fail_num
 * @property string $handle_success_wait_amount
 * @property string $handle_fail_wait_amount
 */
class OfflineUploadUserAccountFile extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineUploadUserAccountFile the static model class
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
		return 'offline_upload_user_account_file';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('file_name, file_path', 'required'),
			array('platform_id, action_admin_id, auth_admin_id, auth_status, deal_status', 'numerical', 'integerOnly'=>true),
			array('file_name, file_path, remark', 'length', 'max'=>255),
			array('total_num, success_num, fail_num, auth_time, addtime, update_time, handle_success_num, handle_fail_num', 'length', 'max'=>11),
			array('total_amount, success_wait_amount, fail_wait_amount, handle_success_wait_amount, handle_fail_wait_amount', 'length', 'max'=>20),
			array('action_user_name, auth_user_name', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, file_name, file_path, platform_id, total_num, success_num, fail_num, total_amount, success_wait_amount, fail_wait_amount, action_admin_id, auth_admin_id, auth_time, auth_status, addtime, deal_status, update_time, remark, action_user_name, auth_user_name, handle_success_num, handle_fail_num, handle_success_wait_amount, handle_fail_wait_amount', 'safe', 'on'=>'search'),
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
			'file_name' => 'File Name',
			'file_path' => 'File Path',
			'platform_id' => 'Platform',
			'total_num' => 'Total Num',
			'success_num' => 'Success Num',
			'fail_num' => 'Fail Num',
			'total_amount' => 'Total Amount',
			'success_wait_amount' => 'Success Wait Amount',
			'fail_wait_amount' => 'Fail Wait Amount',
			'action_admin_id' => 'Action Admin',
			'auth_admin_id' => 'Auth Admin',
			'auth_time' => 'Auth Time',
			'auth_status' => 'Auth Status',
			'addtime' => 'Addtime',
			'deal_status' => 'Deal Status',
			'update_time' => 'Update Time',
			'remark' => 'Remark',
			'action_user_name' => 'Action User Name',
			'auth_user_name' => 'Auth User Name',
			'handle_success_num' => 'Handle Success Num',
			'handle_fail_num' => 'Handle Fail Num',
			'handle_success_wait_amount' => 'Handle Success Wait Amount',
			'handle_fail_wait_amount' => 'Handle Fail Wait Amount',
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
		$criteria->compare('file_name',$this->file_name,true);
		$criteria->compare('file_path',$this->file_path,true);
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('total_num',$this->total_num,true);
		$criteria->compare('success_num',$this->success_num,true);
		$criteria->compare('fail_num',$this->fail_num,true);
		$criteria->compare('total_amount',$this->total_amount,true);
		$criteria->compare('success_wait_amount',$this->success_wait_amount,true);
		$criteria->compare('fail_wait_amount',$this->fail_wait_amount,true);
		$criteria->compare('action_admin_id',$this->action_admin_id);
		$criteria->compare('auth_admin_id',$this->auth_admin_id);
		$criteria->compare('auth_time',$this->auth_time,true);
		$criteria->compare('auth_status',$this->auth_status);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('deal_status',$this->deal_status);
		$criteria->compare('update_time',$this->update_time,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('action_user_name',$this->action_user_name,true);
		$criteria->compare('auth_user_name',$this->auth_user_name,true);
		$criteria->compare('handle_success_num',$this->handle_success_num,true);
		$criteria->compare('handle_fail_num',$this->handle_fail_num,true);
		$criteria->compare('handle_success_wait_amount',$this->handle_success_wait_amount,true);
		$criteria->compare('handle_fail_wait_amount',$this->handle_fail_wait_amount,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}