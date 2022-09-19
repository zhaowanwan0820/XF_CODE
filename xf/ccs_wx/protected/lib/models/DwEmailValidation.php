<?php

/**
 * This is the model class for table "dw_email_validation".
 *
 * The followings are the available columns in table 'dw_email_validation':
 * @property string $id
 * @property string $email
 * @property string $user_id
 * @property integer $status
 * @property integer $num
 * @property string $code
 * @property string $lasttime
 * @property string $lastip
 */
class DwEmailValidation extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DwEmailValidation the static model class
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
		return 'dw_email_validation';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('code', 'required'),
			array('status, num', 'numerical', 'integerOnly'=>true),
			array('email, lasttime', 'length', 'max'=>50),
			array('user_id', 'length', 'max'=>10),
			array('code', 'length', 'max'=>32),
			array('lastip', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, email, user_id, status, num, code, lasttime, lastip', 'safe', 'on'=>'search'),
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
			'email' => 'Email',
			'user_id' => 'User',
			'status' => 'Status',
			'num' => 'Num',
			'code' => 'Code',
			'lasttime' => 'Lasttime',
			'lastip' => 'Lastip',
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
		$criteria->compare('email',$this->email,true);
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('num',$this->num);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('lasttime',$this->lasttime,true);
		$criteria->compare('lastip',$this->lastip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}