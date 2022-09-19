<?php

/**
 * This is the model class for table "itz_trigger_template".
 *
 * The followings are the available columns in table 'itz_trigger_template':
 * @property integer $id
 * @property integer $pointid
 * @property string $name
 * @property integer $type
 * @property string $title
 * @property string $header
 * @property string $content
 * @property string $footer
 * @property string $owner
 * @property string $params
 * @property integer $createtime
 * @property integer $lasttime
 * @property string $desc
 */
class TriggerTemplate extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return TriggerTemplate the static model class
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
		return 'itz_trigger_template';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('pointid, type', 'required'),
			array('pointid, type, createtime, lasttime', 'numerical', 'integerOnly'=>true),
			array('name, owner', 'length', 'max'=>30),
			array('title', 'length', 'max'=>100),
			array('desc', 'length', 'max'=>200),
            array('params', 'length', 'max'=>255),
			array('header, content, footer', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, pointid, name, type, title, header, content, footer, owner, createtime, lasttime, desc, params', 'safe', 'on'=>'search'),
		);
	}

	public function fetchTemplateAndChannelByCode($code)
	{
		$pointid = TriggerPoint::model()->find('code = :code', [':code' => $code])->id;
		if (!$pointid) {
			return false;
		}
		$sql = "SELECT `id`, `type`, `title`, `content`, `params` FROM {$this->tableName()} ";
		$sql .= " WHERE `pointid` = {$pointid}";
		$templates = $this->dbConnection->createCommand($sql)->queryAll();
		if (!$templates) {
			return false;
		}

		$result = [];
		foreach ($templates as $template) {
			if ($template['type'] == 0) {
				$result['sms']['content'] = $template['content'];
				$params = unserialize($template['params']);
				$list = Yii::app()->c->gatewayList;
				$result['sms']['main_channel'] = $list[$params['main']];
				$result['sms']['vice_channel'] = $list[$params['vice']];
			} elseif ($template['type'] == 1 || $template['type'] == 2) {
				$result['msg']['title'] = $template['title'];
				$result['msg']['content'] = $template['content'];
			} else {
				$result['mail']['title'] = $template['title'];
				$result['mail']['content'] = $template['content'];
			}
		}
		return $result;
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
			'pointid' => 'Pointid',
			'name' => 'Name',
			'type' => 'Type',
			'title' => 'Title',
			'header' => 'Header',
			'content' => 'Content',
			'footer' => 'Footer',
			'owner' => 'Owner',
			'createtime' => 'Createtime',
			'lasttime' => 'Lasttime',
			'desc' => 'Desc',
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
		$criteria->compare('pointid',$this->pointid);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('header',$this->header,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('footer',$this->footer,true);
		$criteria->compare('owner',$this->owner,true);
		$criteria->compare('createtime',$this->createtime);
		$criteria->compare('lasttime',$this->lasttime);
		$criteria->compare('desc',$this->desc,true);
		$criteria->compare('params',$this->params,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}