<?php

/**
 * This is the model class for table "ag_user_platform".
 *
 * The followings are the available columns in table 'ag_user_platform':
 * @property string $platform_user_id
 * @property integer $user_id
 * @property integer $platform_id
 * @property integer $authorization_status
 * @property integer $authorization_time
 * @property integer $agree_status
 * @property integer $agree_time
 * @property string $real_name
 * @property string $id_no
 * @property string $bank_card
 */
class AgPlatformUser extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgUser the static model class
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
		return 'ag_user_platform';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('user_id, platform_id, platform_user_id, authorization_status, authorization_time, agree_status, agree_time', 'numerical', 'integerOnly'=>true),
            array('real_name', 'length', 'max'=>20),
            array('id_no', 'length', 'max'=>30),
            array('bank_card', 'length', 'max'=>63),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, user_id, platform_id,platform_user_id, authorization_status, authorization_time, agree_status, agree_time, real_name, id_no, bank_card', 'safe', 'on'=>'search'),
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
            'platform_id' => 'Platform',
            'platform_user_id' => 'Platform User',
            'authorization_status' => 'Authorization Status',
            'authorization_time' => 'Authorization Time',
            'agree_status' => 'Agree Status',
            'agree_time' => 'Agree Time',
            'real_name' => 'Real Name',
            'id_no' => 'Id No',
            'bank_card' => 'Bank Card',
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
        $criteria->compare('user_id',$this->user_id);
        $criteria->compare('platform_id',$this->platform_id);
        $criteria->compare('platform_user_id',$this->platform_user_id);
        $criteria->compare('authorization_status',$this->authorization_status);
        $criteria->compare('authorization_time',$this->authorization_time);
        $criteria->compare('agree_status',$this->agree_status);
        $criteria->compare('agree_time',$this->agree_time);
        $criteria->compare('real_name',$this->real_name,true);
        $criteria->compare('id_no',$this->id_no,true);
        $criteria->compare('bank_card',$this->bank_card,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
	}
}