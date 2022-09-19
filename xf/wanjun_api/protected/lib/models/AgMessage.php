<?php

/**
 * This is the model class for table "ag_message".
 *
 * The followings are the available columns in table 'ag_message':
 * @property string $id
 * @property integer $sent_user
 * @property integer $receive_user
 * @property string $name
 * @property integer $status
 * @property integer $type
 * @property string $content
 * @property integer $platform_id
 * @property integer $addtime
 * @property string $addip
 */
class AgMessage extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgMessage the static model class
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
		return Yii::app()->agdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_message';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sent_user, receive_user, status, type, platform_id, addtime', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>255),
			array('addip', 'length', 'max'=>31),
			array('content', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sent_user, receive_user, name, status, type, content, platform_id, addtime, addip', 'safe', 'on'=>'search'),
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
			'sent_user' => 'Sent User',
			'receive_user' => 'Receive User',
			'name' => 'Name',
			'status' => 'Status',
			'type' => 'Type',
			'content' => 'Content',
			'platform_id' => 'Platform',
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
		$criteria->compare('sent_user',$this->sent_user);
		$criteria->compare('receive_user',$this->receive_user);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('type',$this->type);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}