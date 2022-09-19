<?php

/**
 * This is the model class for table "dw_yima_user_info".
 *
 * The followings are the available columns in table 'dw_yima_user_info':
 * @property integer $user_id
 * @property string $ym_uid
 * @property string $ym_hid
 * @property string $ym_k_param
 * @property integer $status
 * @property integer $addtime
 * @property string $addip
 */
class YimaUserInfo extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return YimaUserInfo the static model class
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
		return 'dw_yima_user_info';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, ym_uid, ym_hid, ym_k_param, addtime, addip', 'required'),
			array('user_id, status, addtime', 'numerical', 'integerOnly'=>true),
			array('ym_uid, ym_hid', 'length', 'max'=>20),
			array('ym_k_param', 'length', 'max'=>40),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('user_id, ym_uid, ym_hid, ym_k_param, status, addtime, addip', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'ym_uid' => 'Ym Uid',
			'ym_hid' => 'Ym Hid',
			'ym_k_param' => 'Ym K Param',
			'status' => 'Status',
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

		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('ym_uid',$this->ym_uid,true);
		$criteria->compare('ym_hid',$this->ym_hid,true);
		$criteria->compare('ym_k_param',$this->ym_k_param,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}