<?php

/**
 * This is the model class for table "xf_user_account_log".
 *
 * The followings are the available columns in table 'xf_user_account_log':
 * @property string $id
 * @property string $user_id
 * @property string $type
 * @property integer $direction
 * @property string $money
 * @property string $changed_money
 * @property string $remark
 * @property string $addtime
 * @property integer $platform_id
 */
class XfUserAccountLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return XfUserAccountLog the static model class
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
		return Yii::app()->fdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'xf_user_account_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('direction, platform_id', 'numerical', 'integerOnly'=>true),
			array('user_id, changed_money', 'length', 'max'=>11),
			array('type', 'length', 'max'=>50),
			array('money', 'length', 'max'=>20),
			array('remark', 'length', 'max'=>255),
			array('addtime', 'length', 'max'=>10),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, type, direction, money, changed_money, remark, addtime, platform_id', 'safe', 'on'=>'search'),
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
			'type' => 'Type',
			'direction' => 'Direction',
			'money' => 'Money',
			'changed_money' => 'Changed Money',
			'remark' => 'Remark',
			'addtime' => 'Addtime',
			'platform_id' => 'Platform',
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
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('direction',$this->direction);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('changed_money',$this->changed_money,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('platform_id',$this->platform_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}