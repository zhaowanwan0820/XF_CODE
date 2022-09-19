<?php

/**
 * This is the model class for table "xf_user_shopping_import_file".
 *
 * The followings are the available columns in table 'xf_user_shopping_import_file':
 * @property string $id
 * @property string $file_name
 * @property string $file_path
 * @property integer $platform_id
 * @property string $total_num
 * @property string $total_amount
 * @property string $total_integral
 * @property integer $action_admin_id
 * @property string $action_user_name
 * @property integer $status
 * @property string $addtime
 * @property integer $auth_admin_id
 * @property string $auth_user_name
 * @property string $auth_time
 * @property string $update_time
 */
class XfUserShoppingImportFile extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return XfUserShoppingImportFile the static model class
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
        return 'xf_user_shopping_import_file';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('file_name, file_path', 'required'),
            array('platform_id, action_admin_id, status, auth_admin_id', 'numerical', 'integerOnly'=>true),
            array('file_name, file_path', 'length', 'max'=>255),
            array('total_num, addtime, auth_time, update_time', 'length', 'max'=>11),
            array('total_amount, total_integral', 'length', 'max'=>10),
            array('action_user_name, auth_user_name', 'length', 'max'=>50),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, file_name, file_path, platform_id, total_num, total_amount, total_integral, action_admin_id, action_user_name, status, addtime, auth_admin_id, auth_user_name, auth_time, update_time', 'safe', 'on'=>'search'),
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
            'file_name' => 'File Name',
            'file_path' => 'File Path',
            'platform_id' => 'Platform',
            'total_num' => 'Total Num',
            'total_amount' => 'Total Amount',
            'total_integral' => 'Total Integral',
            'action_admin_id' => 'Action Admin',
            'action_user_name' => 'Action User Name',
            'status' => 'Status',
            'addtime' => 'Addtime',
            'auth_admin_id' => 'Auth Admin',
            'auth_user_name' => 'Auth User Name',
            'auth_time' => 'Auth Time',
            'update_time' => 'Update Time',
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

        $criteria->compare('id', $this->id, true);
        $criteria->compare('file_name', $this->file_name, true);
        $criteria->compare('file_path', $this->file_path, true);
        $criteria->compare('platform_id', $this->platform_id);
        $criteria->compare('total_num', $this->total_num, true);
        $criteria->compare('total_amount', $this->total_amount, true);
        $criteria->compare('total_integral', $this->total_integral, true);
        $criteria->compare('action_admin_id', $this->action_admin_id);
        $criteria->compare('action_user_name', $this->action_user_name, true);
        $criteria->compare('status', $this->status);
        $criteria->compare('addtime', $this->addtime, true);
        $criteria->compare('auth_admin_id', $this->auth_admin_id);
        $criteria->compare('auth_user_name', $this->auth_user_name, true);
        $criteria->compare('auth_time', $this->auth_time, true);
        $criteria->compare('update_time', $this->update_time, true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
    public static $status_cn = [
        0=>'待审核',
        1=>'审核通过',
        2=>'已拒绝',
        3=>'已撤回',
    ];

    public function getImportList($params)
    {
        $where =[];
        if (!empty($params['appid'])) {
            $where[] = " f.platform_id =".$params['appid'];
        }
        if (isset($params['status']) && $params['status'] >= 0) {
            $where[]= "f.status =".intval($params['status']);
        }
        if ($params['total_num'] > 0) {
            $where[]= "f.total_num =".intval($params['total_num']);
        }
        if (!empty($params['action_user_name'])) {
            $where[]= "f.action_user_name ='".$params['action_user_name']."'";
        }
        if (!empty($params['auth_user_name'])) {
            $where[]= "f.auth_user_name ='".$params['auth_user_name']."'";
        }
        if (!empty($params['action_start'])) {
            $where[]= "f.addtime >=".strtotime($params['action_start']);
        }
        if (!empty($params['action_end'])) {
            $where[]= "f.addtime <=".(strtotime($params['action_end'])+86400);
        }
        if (!empty($params['auth_start'])) {
            $where[]= "f.auth_time >=".strtotime($params['auth_start']);
        }
        if (!empty($params['auth_end'])) {
            $where[]= "f.auth_time <=".(strtotime($params['auth_end'])+86400);
        }
        $condition = '';
        if (!empty($where)) {
            $condition = 'where ' . implode(' and ', $where);
        }

        $_file=[];
        $sql = 'select count(1) from '.$this->tableName().' as f  '.$condition;
        
        $countFile = self::model()->countBySql($sql);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select f.*,p.name from ".$this->tableName()." as f left join xf_debt_exchange_platform as p on f.platform_id=p.id  {$condition} order by f.id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($_file as &$item) {
                $item['addtime']=date('Y-m-d H:i:s', $item['addtime']);
                $item['auth_time']=$item['auth_time']?date('Y-m-d H:i:s', $item['auth_time']):'';
                $item['status_cn'] = self::$status_cn[$item['status']];
            }
        }
        return ['countNum' => $countFile, 'list' => $_file];
    }

    public static function authImportFile($params)
    {
        if (empty($params['id'])) {
            throw new Exception('审核文件 id 为空 error:'.print_r($params, true));
        }
        if (empty($params['user_id'])) {
            throw new Exception('审核文件 审核人为空 error:'.print_r($params, true));
        }
        if (!in_array($params['status'], [1, 2, 3])) {
            throw new Exception('审核文件 审核状态不合法 error:'.print_r($params, true));
        }

        try {
            Yii::app()->fdb->beginTransaction();

            $importFile = self::model()->findBySql("select * from xf_user_shopping_import_file where id = {$params['id']} for update ");
            if (empty($importFile)) {
                throw new Exception('审核导入文件 不存在 error:'.print_r($params, true));
            }

            if ((3 == $params['status'] && $importFile->status > 0) || (3 == $importFile->status && in_array($params['status'], [1, 2])) || (2 == $importFile->status && in_array($params['status'], [1, 2, 3])) || (1 == $importFile->status && in_array($params['status'], [1, 2, 3]))) {
                throw new Exception('操作失败:当前状态为['.self::$status_cn[$importFile->status ].']');
            }
            //3 撤销
            if (in_array($params['status'], [3])) {
                $res = XfUserShoppingInfo::model()->updateAll(['update_time' => time(), 'status' => 3], "upload_id = {$params['id']}");
                if (false === $res) {
                    throw new Exception('取消出借记录明细 失败 请重试');
                }
            }
            //审核人
            if (in_array($params['status'], [1])) {
                $userInfo = Yii::app()->user->getState("_user");
                $username = $userInfo['username'];
                $importFile->auth_user_name = $username;
                $importFile->auth_admin_id = $params['user_id'];
                $importFile->auth_time = time();
               
                $res = XfUserShoppingInfo::model()->updateAll(['update_time' => time(), 'status' => 1], "upload_id = {$params['id']}");
                if ($res ===false) {
                    throw new Exception('审核白名单 失败 请重试');
                }
            }
            $importFile->status = $params['status'];
            if (isset($params['remark'])) {
                $importFile->remark = $params['remark'];
            }
            if (false == $importFile->save()) {
                throw new Exception('审核导入文件 失败 请重试');
            }

            Yii::app()->fdb->commit();
        } catch (Exception $e) {
            Yii::app()->fdb->rollback();
            throw $e;
        }
    }
}
