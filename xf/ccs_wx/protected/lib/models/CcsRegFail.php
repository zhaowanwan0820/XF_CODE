<?php

/**
 * This is the model class for table "ccs_reg_fail".
 *
 * The followings are the available columns in table 'ccs_reg_fail':
 * @property string $id
 * @property string $user_phone
 * @property string $action_result
 * @property integer $action_time
 * @property string $action_client_ip
 * @property integer $action_status
 * @property integer $call_status
 * @property integer $admin_id
 * @property integer $addtime
 * @property integer $updatetime
 */
class CcsRegFail extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CcsRegFail the static model class
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
		return Yii::app()->ccsdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ccs_reg_fail';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('action_time, action_status, call_status, admin_id, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('user_phone', 'length', 'max'=>15),
			array('action_result', 'length', 'max'=>200),
			array('action_client_ip', 'length', 'max'=>100),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_phone, action_result, action_time, action_client_ip, action_status, call_status, admin_id, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'user_phone' => 'User Phone',
			'action_result' => 'Action Result',
			'action_time' => 'Action Time',
			'action_client_ip' => 'Action Client Ip',
			'action_status' => 'Action Status',
			'call_status' => 'Call Status',
			'admin_id' => 'Admin',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
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
		$criteria->compare('user_phone',$this->user_phone,true);
		$criteria->compare('action_result',$this->action_result,true);
		$criteria->compare('action_time',$this->action_time);
		$criteria->compare('action_client_ip',$this->action_client_ip,true);
		$criteria->compare('action_status',$this->action_status);
		$criteria->compare('call_status',$this->call_status);
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}