<?php

/**
 * This is the model class for table "crm_call_record".
 *
 * The followings are the available columns in table 'crm_call_record':
 * @property integer $id
 * @property integer $task_id
 * @property integer $user_id
 * @property integer $admin_id
 * @property integer $ag_phone
 * @property string $call_id
 * @property string $user_phone
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $status
 * @property string $remark
 * @property integer $call_time
 * @property integer $talk_time
 * @property integer $ring_secs
 * @property string $result
 * @property integer $addtime
 * @property integer $updatetime
 * @property integer $type
 * @property integer $contact_way
 * @property integer $systype
 */
class CrmCallRecord extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CrmCallRecord the static model class
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
		return Yii::app()->crmdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'crm_call_record';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('task_id, user_id, admin_id, ag_phone, start_time, end_time, status, call_time, talk_time, ring_secs, addtime, updatetime, type, systype,quaere_type', 'numerical', 'integerOnly'=>true),
			array('call_id', 'length', 'max'=>30),
			array('user_phone', 'length', 'max'=>11),
			array('remark', 'length', 'max'=>200),
			array('result', 'length', 'max'=>100),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, task_id, user_id, admin_id, ag_phone, call_id, user_phone, start_time, end_time, status, remark, call_time, talk_time, ring_secs, result, addtime, updatetime, type, contact_way, systype,quaere_type', 'safe', 'on'=>'search'),
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
			'task_id' => 'Task',
			'user_id' => 'User',
			'admin_id' => 'Admin',
			'ag_phone' => 'Ag Phone',
			'call_id' => 'Call',
			'user_phone' => 'User Phone',
			'start_time' => 'Start Time',
			'end_time' => 'End Time',
			'status' => 'Status',
			'remark' => 'Remark',
			'call_time' => 'Call Time',
			'talk_time' => 'Talk Time',
			'ring_secs' => 'Ring Secs',
			'result' => 'Result',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
			'type' => 'Type',
			'contact_way' => 'Contact Way',
			'systype' => 'Systype',
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
		$criteria->compare('task_id',$this->task_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('ag_phone',$this->ag_phone);
		$criteria->compare('call_id',$this->call_id,true);
		$criteria->compare('user_phone',$this->user_phone,true);
		$criteria->compare('start_time',$this->start_time);
		$criteria->compare('end_time',$this->end_time);
		$criteria->compare('status',$this->status);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('call_time',$this->call_time);
		$criteria->compare('talk_time',$this->talk_time);
		$criteria->compare('ring_secs',$this->ring_secs);
		$criteria->compare('result',$this->result,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);
		$criteria->compare('type',$this->type);
		$criteria->compare('contact_way',$this->contact_way);
		$criteria->compare('systype',$this->systype);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}