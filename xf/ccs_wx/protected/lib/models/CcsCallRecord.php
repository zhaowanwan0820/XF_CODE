<?php

/**
 * This is the model class for table "ccs_call_record".
 *
 * The followings are the available columns in table 'ccs_call_record':
 * @property integer $id
 * @property integer $type
 * @property integer $category
 * @property integer $status
 * @property integer $app_id
 * @property integer $admin_id
 * @property string $admin_name
 * @property string $ag_phone
 * @property string $call_id
 * @property string $user_phone
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $call_status
 * @property string $remark
 * @property integer $call_time
 * @property integer $talk_time
 * @property integer $ring_secs
 * @property string $endresult
 * @property string $result
 * @property string $record_url
 * @property integer $order_status
 * @property integer $append
 * @property integer $addtime
 * @property integer $updatetime
 */
class CcsCallRecord extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CcsCallRecord the static model class
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
		return 'ccs_call_record';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, category, status, app_id, admin_id, start_time, end_time, call_status, call_time, talk_time, ring_secs, order_status, append, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('admin_name, endresult, result', 'length', 'max'=>50),
			array('ag_phone, user_phone', 'length', 'max'=>11),
			array('call_id', 'length', 'max'=>32),
			array('remark', 'length', 'max'=>150),
			array('record_url', 'length', 'max'=>200),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, category, status, app_id, admin_id, admin_name, ag_phone, call_id, user_phone, start_time, end_time, call_status, remark, call_time, talk_time, ring_secs, endresult, result, record_url, order_status, append, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'type' => 'Type',
			'category' => 'Category',
			'status' => 'Status',
			'app_id' => 'App',
			'admin_id' => 'Admin',
			'admin_name' => 'Admin Name',
			'ag_phone' => 'Ag Phone',
			'call_id' => 'Call',
			'user_phone' => 'User Phone',
			'start_time' => 'Start Time',
			'end_time' => 'End Time',
			'call_status' => 'Call Status',
			'remark' => 'Remark',
			'call_time' => 'Call Time',
			'talk_time' => 'Talk Time',
			'ring_secs' => 'Ring Secs',
			'endresult' => 'Endresult',
			'result' => 'Result',
			'record_url' => 'Record Url',
			'order_status' => 'Order Status',
			'append' => 'Append',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('type',$this->type);
		$criteria->compare('category',$this->category);
		$criteria->compare('status',$this->status);
		$criteria->compare('app_id',$this->app_id);
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('admin_name',$this->admin_name,true);
		$criteria->compare('ag_phone',$this->ag_phone,true);
		$criteria->compare('call_id',$this->call_id,true);
		$criteria->compare('user_phone',$this->user_phone,true);
		$criteria->compare('start_time',$this->start_time);
		$criteria->compare('end_time',$this->end_time);
		$criteria->compare('call_status',$this->call_status);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('call_time',$this->call_time);
		$criteria->compare('talk_time',$this->talk_time);
		$criteria->compare('ring_secs',$this->ring_secs);
		$criteria->compare('endresult',$this->endresult,true);
		$criteria->compare('result',$this->result,true);
		$criteria->compare('record_url',$this->record_url,true);
		$criteria->compare('order_status',$this->order_status);
		$criteria->compare('append',$this->append);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}