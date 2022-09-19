<?php

/**
 * This is the model class for table "dw_message".
 *
 * The followings are the available columns in table 'dw_message':
 * @property string $id
 * @property integer $sent_user
 * @property integer $receive_user
 * @property string $name
 * @property integer $status
 * @property string $type
 * @property string $sented
 * @property integer $deltype
 * @property string $content
 * @property string $addtime
 * @property string $addip
 */
class Message extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Message the static model class
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
		return 'dw_message';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sent_user, receive_user, status, deltype', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>255),
			array('type, addtime, addip', 'length', 'max'=>50),
			array('sented', 'length', 'max'=>2),
			array('content', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sent_user, receive_user, name, status, type, sented, deltype, content, addtime, addip', 'safe', 'on'=>'search'),
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
			'sented' => 'Sented',
			'deltype' => 'Deltype',
			'content' => 'Content',
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
		$criteria->compare('type',$this->type,true);
		$criteria->compare('sented',$this->sented,true);
		$criteria->compare('deltype',$this->deltype);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
