<?php

/**
 * This is the model class for table "switch_real_auth".
 *
 * The followings are the available columns in table 'switch_real_auth':
 * @property string $record_id
 * @property string $realname
 * @property string $card_id
 * @property string $card_type
 * @property integer $user_id
 * @property integer $status
 * @property integer $addtime
 * @property integer $updatetime
 */
class SwitchRealAuth extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return SwitchRealAuth the static model class
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
		return Yii::app()->dwdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'switch_real_auth';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, status, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('realname', 'length', 'max'=>20),
			array('card_id', 'length', 'max'=>50),
			array('card_type', 'length', 'max'=>10),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('record_id, realname, card_id, card_type, user_id, status, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'record_id' => 'Record',
			'realname' => 'Realname',
			'card_id' => 'Card',
			'card_type' => 'Card Type',
			'user_id' => 'User',
			'status' => 'Status',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
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

		$criteria->compare('record_id',$this->record_id,true);
		$criteria->compare('realname',$this->realname,true);
		$criteria->compare('card_id',$this->card_id,true);
		$criteria->compare('card_type',$this->card_type,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}