<?php

/**
 * This is the model class for table "itz_send_iphone_message".
 *
 * The followings are the available columns in table 'itz_send_iphone_message':
 * @property string $id
 * @property integer $message_id
 * @property string $DeviceToken
 * @property string $message
 * @property string $url
 * @property integer $borrow_id
 * @property integer $borrow_type
 * @property integer $addtime
 * @property integer $sendtime
 * @property integer $status
 */
class ItzSendIphoneMessage extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzSendIphoneMessage the static model class
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
		return 'itz_send_iphone_message';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('message_id,DeviceToken,message', 'required'),
			array('message_id,user_id, borrow_type, addtime, sendtime, status', 'numerical', 'integerOnly'=>true),
			array('DeviceToken', 'length', 'max'=>255),
			array('url', 'length', 'max'=>200),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id,message_id, DeviceToken, message, url, borrow_id, borrow_type, addtime, sendtime, status', 'safe', 'on'=>'search'),
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
			'message_id' => 'Message',
			'DeviceToken' => 'Device Token',
			'message' => 'Message',
			'url' => 'Url',
			'borrow_id' => 'Borrow',
			'borrow_type' => 'Borrow Type',
			'addtime' => 'Addtime',
			'sendtime' => 'Sendtime',
			'status' => 'Status',
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
		$criteria->compare('message_id',$this->message_id);
        $criteria->compare('user_id',$this->user_id);
		$criteria->compare('DeviceToken',$this->DeviceToken,true);
		$criteria->compare('message',$this->message,true);
		$criteria->compare('url',$this->url,true);
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('borrow_type',$this->borrow_type);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('sendtime',$this->sendtime);
		$criteria->compare('status',$this->status);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}