<?php

/**
 * This is the model class for table "log".
 *
 * The followings are the available columns in table 'log':
 * @property integer $id
 * @property string $time
 * @property string $user_id
 * @property string $object_type
 * @property string $object_id
 * @property string $ip
 * @property string $operation
 * @property string $content
 */
class Log extends DwActiveRecord
{
    public $dbname = 'dwdb';
    /**
     * Returns the static model of the specified AR class.
     * @return Log the static model class
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
        return 'itz_log';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('time, user_id, object_type, object_id, ip, operation, content', 'required'),
            array('user_id', 'length', 'max'=>32),
            array('object_type, object_id', 'length', 'max'=>64),
            array('ip, operation', 'length', 'max'=>255),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, time, user_id, object_type, object_id, ip, operation, content', 'safe', 'on'=>'search'),
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
            'r_user'=>array(self::BELONGS_TO, 'ItzUser', 'id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'time' => '操作时间',
            'user_id' => '操作人',
            'object_type' => 'Object Type',
            'object_id' => 'Object',
            'ip' => 'IP',
            'operation' => '动作',
            'content' => '操作内容',
        );
    }

    public function defaultScope(){
        return array_merge(parent::defaultScope(), array(
                'order' =>  'id desc',
            ));
    }

    public function getUser(){
        return $this->user_id;

        $special_users = array(
            -1  =>  'console',
        );
        if(array_key_exists($this->user_id, $special_users))
            return $special_users[$this->user_id];
        return 'UNKOWN';
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $pagination = array(
            'pageSize'  =>  10,
        );
        $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id);
        $criteria->compare('time',$this->time);
        $criteria->compare('user_id',$this->user_id);
        $criteria->compare('object_type',$this->object_type);
        $criteria->compare('object_id',$this->object_id);
        $criteria->compare('ip',$this->ip);
        $criteria->compare('operation',$this->operation,true);
        $criteria->compare('content',$this->content,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
            'pagination'    =>  $pagination,
        ));
    }
}
