<?php

/**
 * This is the model class for table "itz_user_rank_privilege".
 *
 * The followings are the available columns in table 'itz_user_rank_privilege':
 * @property integer $id
 * @property integer $rank_id
 * @property integer $privilege_type_id
 * @property integer $privilege_class_id
 * @property string $name
 * @property string $rule
 * @property string $pic_url
 * @property string $remark
 * @property integer $addtime
 * @property integer $updatetime
 */
class ItzUserRankPrivilege extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzUserRankPrivilege the static model class
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
		return 'itz_user_rank_privilege';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('rule, pic_url, remark, updatetime', 'required'),
			array('rank_id, privilege_type_id, privilege_class_id, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>150),
			array('rule, pic_url, remark', 'length', 'max'=>200),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, rank_id, privilege_type_id, privilege_class_id, name, rule, pic_url, remark, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'rank_id' => 'Rank',
			'privilege_type_id' => 'Privilege Type',
			'privilege_class_id' => 'Privilege Class',
			'name' => 'Name',
			'rule' => 'Rule',
			'pic_url' => 'Pic Url',
			'remark' => 'Remark',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('rank_id',$this->rank_id);
		$criteria->compare('privilege_type_id',$this->privilege_type_id);
		$criteria->compare('privilege_class_id',$this->privilege_class_id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('rule',$this->rule,true);
		$criteria->compare('pic_url',$this->pic_url,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}