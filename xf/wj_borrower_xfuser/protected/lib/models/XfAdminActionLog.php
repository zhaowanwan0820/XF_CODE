<?php

/**
 * This is the model class for table "xf_admin_action_log".
 *
 * The followings are the available columns in table 'xf_admin_action_log':
 * @property string $id
 * @property string $business_type
 * @property integer $action_uid
 * @property string $related_id
 * @property string $editDetail
 * @property string $remark
 * @property string $addtime
 */
class XfAdminActionLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return XfAdminActionLog the static model class
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
		return Yii::app()->fdb2;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'xf_admin_action_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('action_uid', 'numerical', 'integerOnly'=>true),
			array('business_type', 'length', 'max'=>30),
			array('related_id, addtime', 'length', 'max'=>10),
			array('editDetail, remark', 'length', 'max'=>500),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, business_type, action_uid, related_id, editDetail, remark, addtime', 'safe', 'on'=>'search'),
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
			'business_type' => 'Business Type',
			'action_uid' => 'Action Uid',
			'related_id' => 'Related',
			'editDetail' => 'Edit Detail',
			'remark' => 'Remark',
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('business_type',$this->business_type,true);
		$criteria->compare('action_uid',$this->action_uid);
		$criteria->compare('related_id',$this->related_id,true);
		$criteria->compare('editDetail',$this->editDetail,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('addtime',$this->addtime,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}