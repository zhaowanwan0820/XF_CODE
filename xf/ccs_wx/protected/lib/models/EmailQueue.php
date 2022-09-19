<?php

/**
 * This is the model class for table "itz_email_queue".
 *
 * The followings are the available columns in table 'itz_email_queue':
 * @property string $id
 * @property string $type
 * @property integer $user_id
 * @property integer $status
 * @property integer $send_status
 * @property string $title
 * @property string $email
 * @property string $content
 * @property string $attachment
 * @property string $send_address
 * @property string $send_return
 * @property string $addtime
 * @property string $addip
 */
class EmailQueue extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return EmailQueue the static model class
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
		return 'itz_email_queue';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, status, send_status', 'numerical', 'integerOnly'=>true),
			array('type, addtime, addip', 'length', 'max'=>50),
			array('title', 'length', 'max'=>250),
			array('email, send_address, send_return', 'length', 'max'=>200),
			array('attachment', 'length', 'max'=>1024),
			array('content', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, user_id, status, send_status, title, email, content, attachment, send_address, send_return, addtime, addip', 'safe', 'on'=>'search'),
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
			'title' => 'Title',
			'email' => 'Email',
			'content' => 'Content',
			'attachment' => 'Attachment',
			'send_address' => 'Send Address',
			'send_return' => 'Send Return',
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
		$criteria->compare('type',$this->type,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('send_status',$this->send_status);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('attachment',$this->attachment,true);
		$criteria->compare('send_address',$this->send_address,true);
		$criteria->compare('send_return',$this->send_return,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}