<?php

/**
 * This is the model class for table "dw_user_urgentcontact".
 *
 * The followings are the available columns in table 'dw_user_urgentcontact':
 * @property integer $id
 * @property integer $user_id
 * @property string $name
 * @property string $phone
 * @property integer $relationship
 * @property integer $addtime
 * @property integer $updatetime
 * @property string $updateip
 */
class UserUrgentcontact extends DwActiveRecord
{
     public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserUrgentcontact the static model class
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
		return 'dw_user_urgentcontact';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, name, phone, relationship, addtime, updatetime, updateip', 'required'),
			array('user_id, relationship, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>20),
			array('phone', 'length', 'max'=>11),
			array('updateip', 'length', 'max'=>30),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, name, phone, relationship, addtime, updatetime, updateip', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'name' => 'Name',
			'phone' => 'Phone',
			'relationship' => 'Relationship',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
			'updateip' => 'Updateip',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('relationship',$this->relationship);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);
		$criteria->compare('updateip',$this->updateip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}