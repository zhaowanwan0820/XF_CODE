<?php

/**
 * This is the model class for table "firstp2p_user_company".
 *
 * The followings are the available columns in table 'firstp2p_user_company':
 * @property integer $id
 * @property integer $user_id
 * @property string $name
 * @property string $address
 * @property string $domicile
 * @property string $legal_person
 * @property string $tel
 * @property string $license
 * @property string $description
 * @property integer $is_effect
 * @property integer $is_delete
 * @property integer $create_time
 * @property integer $update_time
 * @property string $project_area
 * @property string $project_condition
 * @property string $top_credit
 * @property string $is_important_enterprise
 * @property string $mangage_condition
 * @property string $complain_condition
 * @property string $trustworthiness
 * @property string $repayment_source
 * @property string $policy
 * @property string $marketplace
 * @property string $licence_image
 * @property string $organization_iamge
 * @property string $taxation_image
 * @property string $bank_iamge
 * @property integer $is_html
 */
class Firstp2pUserCompany extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Firstp2pUserCompany the static model class
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
		return 'firstp2p_user_company';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('description, project_condition, mangage_condition', 'required'),
			array('user_id, is_effect, is_delete, create_time, update_time, is_html', 'numerical', 'integerOnly'=>true),
			array('name, legal_person, tel, license', 'length', 'max'=>100),
			array('address, domicile', 'length', 'max'=>255),
			array('project_area, is_important_enterprise, complain_condition', 'length', 'max'=>30),
			array('top_credit', 'length', 'max'=>20),
			array('licence_image, organization_iamge, taxation_image, bank_iamge', 'length', 'max'=>11),
			array('trustworthiness, repayment_source, policy, marketplace', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, name, address, domicile, legal_person, tel, license, description, is_effect, is_delete, create_time, update_time, project_area, project_condition, top_credit, is_important_enterprise, mangage_condition, complain_condition, trustworthiness, repayment_source, policy, marketplace, licence_image, organization_iamge, taxation_image, bank_iamge, is_html', 'safe', 'on'=>'search'),
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
			'name' => 'Name',
			'address' => 'Address',
			'domicile' => 'Domicile',
			'legal_person' => 'Legal Person',
			'tel' => 'Tel',
			'license' => 'License',
			'description' => 'Description',
			'is_effect' => 'Is Effect',
			'is_delete' => 'Is Delete',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'project_area' => 'Project Area',
			'project_condition' => 'Project Condition',
			'top_credit' => 'Top Credit',
			'is_important_enterprise' => 'Is Important Enterprise',
			'mangage_condition' => 'Mangage Condition',
			'complain_condition' => 'Complain Condition',
			'trustworthiness' => 'Trustworthiness',
			'repayment_source' => 'Repayment Source',
			'policy' => 'Policy',
			'marketplace' => 'Marketplace',
			'licence_image' => 'Licence Image',
			'organization_iamge' => 'Organization Iamge',
			'taxation_image' => 'Taxation Image',
			'bank_iamge' => 'Bank Iamge',
			'is_html' => 'Is Html',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('domicile',$this->domicile,true);
		$criteria->compare('legal_person',$this->legal_person,true);
		$criteria->compare('tel',$this->tel,true);
		$criteria->compare('license',$this->license,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('is_effect',$this->is_effect);
		$criteria->compare('is_delete',$this->is_delete);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('project_area',$this->project_area,true);
		$criteria->compare('project_condition',$this->project_condition,true);
		$criteria->compare('top_credit',$this->top_credit,true);
		$criteria->compare('is_important_enterprise',$this->is_important_enterprise,true);
		$criteria->compare('mangage_condition',$this->mangage_condition,true);
		$criteria->compare('complain_condition',$this->complain_condition,true);
		$criteria->compare('trustworthiness',$this->trustworthiness,true);
		$criteria->compare('repayment_source',$this->repayment_source,true);
		$criteria->compare('policy',$this->policy,true);
		$criteria->compare('marketplace',$this->marketplace,true);
		$criteria->compare('licence_image',$this->licence_image,true);
		$criteria->compare('organization_iamge',$this->organization_iamge,true);
		$criteria->compare('taxation_image',$this->taxation_image,true);
		$criteria->compare('bank_iamge',$this->bank_iamge,true);
		$criteria->compare('is_html',$this->is_html);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}