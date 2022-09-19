<?php

/**
 * This is the model class for table "dw_guarantor".
 *
 * The followings are the available columns in table 'dw_guarantor':
 * @property integer $gid
 * @property integer $linkage_id
 * @property string $user_id
 * @property string $abbr
 * @property integer $status
 * @property string $name
 * @property string $desc
 * @property integer $weight
 * @property string $addtime
 * @property string $updatetime
 * @property string $addip
 */
class Guarantor extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Guarantor the static model class
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
		return 'dw_guarantor';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('linkage_id', 'required'),
			array('linkage_id, status, weight', 'numerical', 'integerOnly'=>true),
			array('user_id', 'length', 'max'=>11),
			array('abbr', 'length', 'max'=>8),
			array('name', 'length', 'max'=>255),
			array('addtime, updatetime, addip', 'length', 'max'=>50),
			array('desc', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('gid, linkage_id, user_id, abbr, status, name, desc, weight, addtime, updatetime, addip', 'safe', 'on'=>'search'),
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
			'gid' => 'Gid',
			'linkage_id' => 'Linkage',
			'user_id' => 'User',
			'abbr' => 'Abbr',
			'status' => 'Status',
			'name' => 'Name',
			'desc' => 'Desc',
			'weight' => 'Weight',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
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

		$criteria->compare('gid',$this->gid);
		$criteria->compare('linkage_id',$this->linkage_id);
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('abbr',$this->abbr,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('desc',$this->desc,true);
		$criteria->compare('weight',$this->weight);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('updatetime',$this->updatetime,true);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}