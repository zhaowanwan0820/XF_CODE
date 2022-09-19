<?php

/**
 * This is the model class for table "dw_user_stat".
 *
 * The followings are the available columns in table 'dw_user_stat':
 * @property string $timekey
 * @property string $type
 * @property string $register
 * @property string $invite
 * @property string $from_baidu
 * @property string $from_other
 * @property string $addtime
 */
class UserStat extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserStat the static model class
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
		return 'dw_user_stat';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('timekey, type', 'required'),
			array('timekey, register, invite, from_baidu, from_other, addtime', 'length', 'max'=>10),
			array('type', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('timekey, type, register, invite, from_baidu, from_other, addtime', 'safe', 'on'=>'search'),
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
			'timekey' => 'Timekey',
			'type' => 'Type',
			'register' => 'Register',
			'invite' => 'Invite',
			'from_baidu' => 'From Baidu',
			'from_other' => 'From Other',
			'addtime' => 'Addtime',
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

		$criteria->compare('timekey',$this->timekey,true);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('register',$this->register,true);
		$criteria->compare('invite',$this->invite,true);
		$criteria->compare('from_baidu',$this->from_baidu,true);
		$criteria->compare('from_other',$this->from_other,true);
		$criteria->compare('addtime',$this->addtime,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}