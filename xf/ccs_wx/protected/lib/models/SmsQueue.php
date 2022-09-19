<?php

/**
 * This is the model class for table "itz_sms_queue".
 *
 * The followings are the available columns in table 'itz_sms_queue':
 * @property string $id
 * @property string $type
 * @property integer $user_id
 * @property integer $status
 * @property integer $send_status
 * @property string $mobile
 * @property string $content
 * @property integer $gateway
 * @property string $ret
 * @property string $addtime
 * @property string $addip
 * @property string $remark
 */
class SmsQueue extends ItzActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return SmsQueue the static model class
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
		return 'itz_sms_queue';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('mobile, content', 'required'),
			array('user_id, status, send_status, gateway', 'numerical', 'integerOnly'=>true),
			array('type', 'length', 'max'=>50),
			array('content, ret, remark', 'length', 'max'=>500),
			array('addtime, addip', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, user_id, status, send_status, mobile, content, gateway, ret, addtime, addip, remark', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'status' => 'Status',
			'send_status' => 'Send Status',
			'mobile' => 'Mobile',
			'content' => 'Content',
			'gateway' => 'Gateway',
			'ret' => 'Ret',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
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
		$criteria->compare('type',$this->type,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('send_status',$this->send_status);
		$criteria->compare('mobile',$this->mobile,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('gateway',$this->gateway);
		$criteria->compare('ret',$this->ret,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('remark',$this->remark,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}