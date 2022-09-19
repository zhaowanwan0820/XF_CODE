<?php

/**
 * This is the model class for table "offline_import_file".
 *
 * The followings are the available columns in table 'offline_import_file':
 * @property string $id
 * @property string $file_name
 * @property string $file_path
 * @property integer $platform_id
 * @property string $total_num
 * @property string $success_num
 * @property string $fail_num
 * @property string $total_amount
 * @property string $success_capital_amount
 * @property string $fail_capital_amount
 * @property string $success_interest_amount
 * @property string $fail_interest_amount
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
 * @property string $handle_success_capital_amount
 * @property string $handle_fail_capital_amount
 * @property string $handle_success_interest_amount
 * @property string $handle_fail_interest_amount
 */
class OfflineImportFile extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineImportFile the static model class
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
		return 'offline_import_file';
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
			array('total_amount, success_capital_amount, fail_capital_amount, success_interest_amount, fail_interest_amount, handle_success_capital_amount, handle_fail_capital_amount, handle_success_interest_amount, handle_fail_interest_amount', 'length', 'max'=>20),
			array('action_user_name, auth_user_name', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, file_name, file_path, platform_id, total_num, success_num, fail_num, total_amount, success_capital_amount, fail_capital_amount, success_interest_amount, fail_interest_amount, action_admin_id, auth_admin_id, auth_time, auth_status, addtime, deal_status, update_time, remark, action_user_name, auth_user_name, handle_success_num, handle_fail_num, handle_success_capital_amount, handle_fail_capital_amount, handle_success_interest_amount, handle_fail_interest_amount', 'safe', 'on'=>'search'),
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
			'success_capital_amount' => 'Success Capital Amount',
			'fail_capital_amount' => 'Fail Capital Amount',
			'success_interest_amount' => 'Success Interest Amount',
			'fail_interest_amount' => 'Fail Interest Amount',
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
			'handle_success_capital_amount' => 'Handle Success Capital Amount',
			'handle_fail_capital_amount' => 'Handle Fail Capital Amount',
			'handle_success_interest_amount' => 'Handle Success Interest Amount',
			'handle_fail_interest_amount' => 'Handle Fail Interest Amount',
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
		$criteria->compare('success_capital_amount',$this->success_capital_amount,true);
		$criteria->compare('fail_capital_amount',$this->fail_capital_amount,true);
		$criteria->compare('success_interest_amount',$this->success_interest_amount,true);
		$criteria->compare('fail_interest_amount',$this->fail_interest_amount,true);
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
		$criteria->compare('handle_success_capital_amount',$this->handle_success_capital_amount,true);
		$criteria->compare('handle_fail_capital_amount',$this->handle_fail_capital_amount,true);
		$criteria->compare('handle_success_interest_amount',$this->handle_success_interest_amount,true);
		$criteria->compare('handle_fail_interest_amount',$this->handle_fail_interest_amount,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}