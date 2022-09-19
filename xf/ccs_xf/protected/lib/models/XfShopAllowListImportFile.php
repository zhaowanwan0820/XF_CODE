<?php

/**
 * This is the model class for table "xf_shop_allow_list_import_file".
 *
 * The followings are the available columns in table 'xf_shop_user_allow_list_import_file':
 * @property string $id
 * @property string $file_name
 * @property string $file_path
 * @property integer $appid
 * @property string $total_num
 * @property string $success_num
 * @property string $fail_num
 * @property integer $action_admin_id
 * @property string $action_user_name
 * @property integer $status
 * @property integer $type
 * @property integer $deal_type
 * @property string $addtime
 * @property integer $auth_admin_id
 * @property string $auth_user_name
 * @property string $auth_time
 * @property string $update_time
 * @property string $remark
 * @property string $area_id
 */
class XfShopAllowListImportFile extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return XfShopAllowListImportFile the static model class
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
        return 'xf_shop_allow_list_import_file';
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
            array('appid, action_admin_id, status, auth_admin_id,type,deal_type', 'numerical', 'integerOnly'=>true),
            array('file_name, file_path, remark', 'length', 'max'=>255),
            array('total_num, success_num, fail_num, addtime, auth_time, update_time', 'length', 'max'=>11),
            array('action_user_name, auth_user_name', 'length', 'max'=>50),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id,area_id, type,deal_type,file_name, file_path, appid, total_num, success_num, fail_num, action_admin_id, action_user_name, status, addtime, auth_admin_id, auth_user_name, auth_time, update_time, remark', 'safe', 'on'=>'search'),
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
            'appid' => 'Appid',
            'total_num' => 'Total Num',
            'success_num' => 'Success Num',
            'fail_num' => 'Fail Num',
            'action_admin_id' => 'Action Admin',
            'action_user_name' => 'Action User Name',
            'status' => 'Status',
            'type' => 'Type',
            'deal_type' => 'Deal Type',
            'addtime' => 'Addtime',
            'auth_admin_id' => 'Auth Admin',
            'auth_user_name' => 'Auth User Name',
            'auth_time' => 'Auth Time',
            'update_time' => 'Update Time',
            'remark' => 'Remark',
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
        $criteria->compare('appid', $this->appid);
        $criteria->compare('total_num', $this->total_num, true);
        $criteria->compare('success_num', $this->success_num, true);
        $criteria->compare('fail_num', $this->fail_num, true);
        $criteria->compare('action_admin_id', $this->action_admin_id);
        $criteria->compare('action_user_name', $this->action_user_name, true);
        $criteria->compare('status', $this->status);
        $criteria->compare('type', $this->type);
        $criteria->compare('deal_type', $this->deal_type);
        $criteria->compare('addtime', $this->addtime, true);
        $criteria->compare('auth_admin_id', $this->auth_admin_id);
        $criteria->compare('auth_user_name', $this->auth_user_name, true);
        $criteria->compare('auth_time', $this->auth_time, true);
        $criteria->compare('update_time', $this->update_time, true);
        $criteria->compare('remark', $this->remark, true);

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

    public static $deal_type_cn = [
        0=>'',
        1=>'尊享',
        2=>'普惠',
        3=>'工厂微金',
        4=>'智多新',
        5=>'交易所',
    ];

    public function getImportList($params)
    {
        if (empty($params['type'])) {
            return false;
        }
        $where = ' type =  '.$params['type'];
        if (!empty($params['appid'])) {
            $where .= " and f.appid =".$params['appid'];
        }
        if (isset($params['status'])) {
            $where .= " and f.status =".$params['status'];
        }

        $_file=[];
        $countFile = self::model()->countBySql('select count(1) from '.$this->tableName().' as f where '.$where);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select f.*,p.name from ".$this->tableName()." as f left join xf_debt_exchange_platform as p on f.appid=p.id  where {$where} order by f.id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($_file as &$item) {
                $item['addtime']=date('Y-m-d H:i:s', $item['addtime']);
                $item['auth_time']=$item['auth_time']?date('Y-m-d H:i:s', $item['auth_time']):'';
                $item['status_cn'] = self::$status_cn[$item['status']];
                $item['deal_type_cn'] = self::$deal_type_cn[$item['deal_type']];
                $item['area_name'] = $item['area_id']>0?XfDebtExchangeSpecialArea::model()->findByPk($item['area_id'])->name:'--';
            }
        }
        return ['countNum' => $countFile, 'list' => $_file];
    }


    public static function authImportFile($params)
    {
        if (empty($params['id'])) {
            throw new Exception('审核商城白名单导入文件 id 为空 error:'.print_r($params, true));
        }
        if (empty($params['user_id'])) {
            throw new Exception('审核商城白名单导入文件 审核人为空 error:'.print_r($params, true));
        }
        if (!in_array($params['status'], [1, 2, 3])) {
            throw new Exception('审核商城白名单导入文件 审核状态不合法 error:'.print_r($params, true));
        }

        try {
            Yii::app()->fdb->beginTransaction();

            $importFile = self::model()->findBySql("select * from xf_shop_allow_list_import_file where id = {$params['id']} for update ");
            if (empty($importFile)) {
                throw new Exception('审核商城白名单导入文件 不存在 error:'.print_r($params, true));
            }

            if ((3 == $params['status'] && $importFile->status > 0) || (3 == $importFile->status && in_array($params['status'], [1, 2])) || (2 == $importFile->status && in_array($params['status'], [1, 2, 3])) || (1 == $importFile->status && in_array($params['status'], [1, 2, 3]))) {
                throw new Exception('操作失败:当前状态为['.self::$status_cn[$importFile->status ].']');
            }

            if (!in_array($params['type'], [1,2])) {
                throw new Exception('操作失败:业务类型不存在');
            }

            $M = $params['type']==1?'XfDebtExchangeUserAllowList':'XfDebtExchangeDealAllowList';

            //3 撤销  1 审核通过
            if (in_array($params['status'], [3])) {
                $res = $M::model()->updateAll(['update_time' => time(), 'status' => 3], "upload_id = {$params['id']}");
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

                $res = $M::model()->updateAll(['update_time' => time(), 'status' => 1], "upload_id = {$params['id']}");
                if ($res ===false) {
                    throw new Exception('审核白名单 失败 请重试');
                }
                //处理项目白名单
                if ($params['type']==2) {
                    $sql = "select deal_id,count(deal_id) as total_num from xf_debt_exchange_deal_allow_list where appid = {$importFile->appid} and status = 1 and `type` = {$importFile->deal_type} and area_id = {$importFile->area_id} group by deal_id having count(deal_id) > 1";
                    $re_deal_id_list = Yii::app()->fdb->createCommand($sql)->queryAll();
                    //存在重复的项目
                    if ($re_deal_id_list) {
                        $now = time();
                        //改成失效
                        foreach ($re_deal_id_list as $item) {
                            $l = $item['total_num'] - 1;
                            $sql = "update xf_debt_exchange_deal_allow_list set status = 4,update_at = {$now} where appid = {$importFile->appid} and status = 1 and `type` = {$importFile->deal_type} and area_id = {$importFile->area_id} and deal_id = {$item['deal_id']}  limit {$l} ";
                            $re = Yii::app()->fdb->createCommand($sql)->execute();
                            if ($re == false) {
                                throw new Exception('审核项目白名单 失败 请重试');
                            }
                        }
                    }
                }
            }
            $importFile->status = $params['status'];
            if (isset($params['remark'])) {
                $importFile->remark = $params['remark'];
            }
            if (false == $importFile->save()) {
                throw new Exception('审核商城白名单导入文件 失败 请重试');
            }

            Yii::app()->fdb->commit();
        } catch (Exception $e) {
            Yii::app()->fdb->rollback();
            throw $e;
        }
    }
}
