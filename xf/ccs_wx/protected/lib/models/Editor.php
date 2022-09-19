<?php

/**
 * This is the model class for table "dw_editor".
 *
 * The followings are the available columns in table 'dw_editor':
 * @property integer $editor_id
 * @property string $code
 * @property string $name
 * @property string $description
 * @property string $version
 * @property string $author
 * @property string $date
 * @property string $api
 */
class Editor extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Editor the static model class
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
		return 'dw_editor';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('code, name', 'length', 'max'=>50),
			array('description', 'length', 'max'=>255),
			array('version, author, date', 'length', 'max'=>20),
			array('api', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('editor_id, code, name, description, version, author, date, api', 'safe', 'on'=>'search'),
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
			'editor_id' => 'Editor',
			'code' => 'Code',
			'name' => 'Name',
			'description' => 'Description',
			'version' => 'Version',
			'author' => 'Author',
			'date' => 'Date',
			'api' => 'Api',
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

		$criteria->compare('editor_id',$this->editor_id);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('version',$this->version,true);
		$criteria->compare('author',$this->author,true);
		$criteria->compare('date',$this->date,true);
		$criteria->compare('api',$this->api,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}