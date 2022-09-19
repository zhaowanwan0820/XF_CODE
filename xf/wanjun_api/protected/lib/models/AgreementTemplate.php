<?php

/**
 * This is the model class for table "firstp2p_agreement_template".
 *
 * The followings are the available columns in table 'itz_agreement_template':
 * @property integer $agree_id
 * @property string $name
 * @property string $template_name
 * @property string $template_content
 * @property string $add_time
 * @property string $update_time
 */
class AgreementTemplate extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgreementTemplate the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getDbConnection()
	{
		return Yii::app()->db;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'firstp2p_agreement_template';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('agree_id', 'required'),
			array('agree_id', 'numerical', 'integerOnly'=>true),
			array('name, template_name', 'length', 'max'=>100),
			array('add_time, update_time', 'length', 'max'=>255),
			array('template_content', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('agree_id, name, template_name, template_content, add_time, update_time', 'safe', 'on'=>'search'),
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


}