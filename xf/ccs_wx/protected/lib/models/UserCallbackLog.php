<?php

/**
 * This is the model class for table "dw_user_callback_log".
 *
 * The followings are the available columns in table 'dw_user_callback_log':
 * @property integer $id
 * @property integer $c_user_id
 * @property integer $s_user_id
 * @property string $remark
 * @property integer $addtime
 * @property string $ip
 */
class UserCallbackLog extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserCallbackLog the static model class
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
		return 'dw_user_callback_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('c_user_id', 'required'),
			array('c_user_id, s_user_id, addtime', 'numerical', 'integerOnly'=>true),
			array('ip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('phone_type,callback_type,consultation_type,subclass,connected_situation,conditions,id, c_user_id, s_user_id, remark, addtime, ip', 'safe'),
			array('phone_type,callback_type,consultation_type,subclass,connected_situation,conditions,id, c_user_id, s_user_id, remark, addtime, ip', 'safe', 'on'=>'search'),
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
			'c_user_id' => 'C User',
			's_user_id' => 'S User',
			'remark' => 'Remark',
			'addtime' => 'Addtime',
			'ip' => 'Ip',
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
		$criteria->compare('c_user_id',$this->c_user_id);
		$criteria->compare('s_user_id',$this->s_user_id);
		$criteria->compare('remark',$this->remark);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('ip',$this->ip);
        $criteria->compare('phone_type',$this->phone_type);
        $criteria->compare('callback_type',$this->callback_type);
        $criteria->compare('consultation_type',$this->consultation_type);
        $criteria->compare('subclass',$this->subclass);
        $criteria->compare('connected_situation',$this->connected_situation);
        $criteria->compare('conditions',$this->conditions);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}