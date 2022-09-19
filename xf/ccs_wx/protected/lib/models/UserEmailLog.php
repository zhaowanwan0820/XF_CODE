<?php

/**
 * This is the model class for table "dw_user_sendemail_log".
 *
 * The followings are the available columns in table 'dw_user_sendemail_log':
 * @property string $id
 * @property integer $status
 * @property string $title
 * @property string $type
 * @property string $email
 * @property integer $user_id
 * @property string $msg
 * @property string $addtime
 * @property string $addip
 */
class UserEmailLog extends DwActiveRecord
{
	public $dbname = 'dwdb';
	
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserEmailLog the static model class
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
		return 'dw_user_sendemail_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status, user_id', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>250),
			array('type, email, addtime, addip', 'length', 'max'=>50),
			array('msg', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, status, title, type, email, user_id, msg, addtime, addip', 'safe', 'on'=>'search'),
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
			'status' => 'Status',
			'title' => 'Title',
			'type' => 'Type',
			'email' => 'Email',
			'user_id' => 'User',
			'msg' => 'Msg',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
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
		$criteria->compare('status',$this->status);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('msg',$this->msg,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}