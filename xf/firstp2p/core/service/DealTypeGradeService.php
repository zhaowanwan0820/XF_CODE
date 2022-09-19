<?php
/**
* DealTypeGradeService.php.
*
* @date 2017-02-16
*
* @author wangzhen <wangzhen3@ucfgroup.com>
*/

namespace core\service;

use core\dao\DealTypeGradeModel;
use libs\utils\Logger;

/**
 * 产品类型service.
 */
class DealTypeGradeService extends BaseService
{
    const ENABLED = 1; //启用
    const DISABLED = 0; //停用
    const PRODUCT_HASH = 'PRODUCT_CLASS_LEVEL';

    public function __construct()
    {
        $this->dealTypeGradModel = new DealTypeGradeModel();
    }

    /**
     * 获取分类list.
     */
    public function getDealTypeGradeList()
    {
        return $this->dealTypeGradModel->getDealTypeList();
    }

    /**
     * 通过id获取分类.
     *
     * @param $id
     *
     * @return array
     */
    public function getbyId($id)
    {
        return $this->dealTypeGradModel->getbyId($id);
    }

    /**
     * 通过父id获取分类.
     *
     * @param $id
     *
     * @return array
     */
    public function getbyParentId($parentId)
    {
        return $this->dealTypeGradModel->getbyParentId($parentId);
    }

    /**
     * 通过name获取分类，不包含主键id等于$id的值
     *
     * @param $id
     *
     * @return array
     */
    public function getbyNameExceptId($name, $id = 0, $parent_id = 0)
    {
        return $this->dealTypeGradModel->getbyNameExceptId($name, $id, $parent_id);
    }

    /**
     * 保存分类.
     *
     * @param $data
     *
     * @return bool|resource
     */
    public function save($data)
    {
        $GLOBALS['db']->startTrans();
        try {
            $result = $this->dealTypeGradModel->save($data);
            if (self::DISABLED == $data['status'] && !empty($data['id'])) {
                $this->triggerStatus($data['id']);
            } else {
                $this->updateUsed();
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, __LINE__, '数据库写入失败', 'data :'.json_encode($data))));
            $GLOBALS['db']->rollback();

            return false;
        }

        return true;
    }

    /**
     * 删除分类.
     *
     * @param $id
     */
    public function del($id)
    {
        return $this->dealTypeGradModel->del($id);
    }

    /**
     * 获取分类上级关系.
     *
     * @param $id
     *
     * @return string
     */
    public function getGradePath($id)
    {
        $node = $this->getbyId($id);
        if (0 != $node['parent_id']) {
            return $this->getGradePath($node['parent_id']).'>>'.$node['name'];
        }

        return $node['name'];
    }

    /**
     * 根据姓名查找分类.
     *
     * @param $name
     *
     * @return \libs\db\Model
     */
    public function findByName($name)
    {
        return $this->dealTypeGradModel->findByName($name);
    }

    /**
     *触发启用停用状态联动.
     *
     * @param $id
     */
    public function triggerStatus($id)
    {
        $this->upChangeStatusDisabled($id);
        $this->downChangeStatusDisabled($id);
    }

    /**
     * 通过父id获取启用的节点.
     *
     * @param $parentId
     *
     * @return int
     */
    public function getEnableCountByParentIdExceptId($parentId, $id = 0)
    {
        return $this->dealTypeGradModel->countViaSlave(" parent_id ={$parentId} and id != {$id} and status = ".self::ENABLED);
    }

    /**
     *往上联动.
     *
     * @param $id
     *
     * @return bool
     */
    private function upChangeStatusDisabled($id)
    {
        if (0 != $id) {
            $node = $this->getbyId($id);
            $count = $this->dealTypeGradModel->countViaSlave(" parent_id ={$node['parent_id']} and id != {$id} and status = ".self::ENABLED);
            if (0 == $count) {
                $this->dealTypeGradModel->updateBy(array('status' => self::DISABLED), " id = {$node['parent_id']} ");

                return $this->upChangeStatusDisabled($node['parent_id']);
            }
        }

        return true;
    }

    /**
     * 往下联动.
     *
     * @param $id
     *
     * @return bool
     */
    private function downChangeStatusDisabled($id)
    {
        $nodes = $this->getbyParentId($id);
        if (!empty($nodes)) {
            $this->dealTypeGradModel->updateBy(array('status' => self::DISABLED), " parent_id = {$id} ");
            foreach ($nodes as $node) {
                $this->downChangeStatusDisabled($node['id']);
            }
        } else {
            return true;
        }
    }

    /**
     * 获取产品级别信息.
     *
     * @param  $name
     *
     * @return array
     */
    public function getAllBySubName($name, $data = array())
    {
        $node = $this->findByName($name);
        if (!empty($node)) {
            if (self::DISABLED == $node['status']) {
                return array();
            }
            if (empty($data['score'])) {
                $data['score'] = $node['score'];
            }
            $data['level'.$node['layer']] = $node['name'];
            if (!empty($node['parent_id'])) {
                $parentNode = $this->getbyId($node['parent_id']);

                return $this->getAllBySubName($parentNode['name'], $data);
            } else {
                return $data;
            }
        }

        return $data;
    }

    /**
     * 获取产品级别信息 走缓存.
     *
     * @param string $name
     */
    public function getAllBySubNameCache($name)
    {
        return \SiteApp::init()->dataCache->call(
            new DealTypeGradeService(),
                 'getAllBySubName',
                 array($name),
                60
        );
    }

    /**
     * 触发使用.
     *
     * @param intval $id
     *
     * @return Ambigous <boolean, resource>
     */
    private function updateUsed()
    {
        return $this->dealTypeGradModel->updateBy(array('is_use' => 1), 'status = '.self::ENABLED.' AND is_use = 0');
    }

    public function setRadioFactor($radioFactor, $id)
    {
        return $this->dealTypeGradModel->updateBy(array('radio_factor' => floatval($radioFactor)), 'id = '.intval($id));
    }

    /**
     * 获取第三级产品名称.
     */
    public function getThirdLayerGradeList()
    {
        $layer = 3;

        return $this->dealTypeGradModel->getListByLayer($layer);
    }

    /**
     * 获取产品名称
     */
    public function getGradeList($layer = 1) {
        return $this->dealTypeGradModel->getListByLayer($layer);
    }


    /**
     * 获取name数组下所有的三级名称
     */
    public function getSubThirdGradeByNameArray($nameArray) {
        $data = [];
        $gradeList = $this->dealTypeGradModel->getByNameArray($nameArray);
        if (empty($gradeList)) {
            return $data;
        }
        $idArray = [];
        foreach ($gradeList as $grade) {
            if ($grade['layer'] == 3) {
                $data[] = $grade;
            } else {
                $idArray[] = $grade['id'];
            }
        }
        if (!empty($idArray)) {
            $data = array_merge(
                $data,
                $this->getSubThirdGradeByIdArray($idArray)
            );
        }
        $data = super_unique($data, 'id');
        return $data;
    }

    /**
     * 获取id数组下所有三级名称
     */
    public function getSubThirdGradeByIdArray($idArray, $times = 1) {
        $data = [];
        //防止无限递归，最多两次
        if ($times > 2) {
            return $data;
        }
        $subGrade = $this->dealTypeGradModel->getbyParentIdArray($idArray);
        if (empty($subGrade)) {
            return $data;
        }
        $newIdArray = [];
        foreach ($subGrade as $sub) {
            if ($sub['layer'] == 3) {
                $data[] = $sub;
            } else {
                $newIdArray[] = $sub['id'];
            }
        }
        if (!empty($newIdArray)) {
            $data = array_merge(
                $data,
                $this->getSubThirdGradeByIdArray($newIdArray, 2)
            );
        }
        $data = super_unique($data, 'id');
        return $data;

    }

    /**
     * 通过产品名称获取id
     * @param string $name
     */
    public function findIdByName($name)
    {
        return $this->dealTypeGradModel->findIdByName($name);
    }

    /**
     * 获取所有P2p的二级分类.
     *
     * @param string $name
     * @param string $sortCond 排序条件
     *
     * @return array
     */
    public function getAllSecondLayersByName($name, $sortCond = '')
    {
        return $this->dealTypeGradModel->getAllSecondLayersByName($name, $sortCond);
    }

    /**
     * 获取所有级别名称和分数,根据二级分类和三级分类.
     */
    public function getAllLevelByName($level2, $level3)
    {
        if (empty($level2) || empty($level3)) {
            return [];
        }
        $redisCache = \SiteApp::init()->dataCache->getRedisInstance();
        $res = $redisCache->get(self::PRODUCT_HASH.md5($level2.$level3));
        $resArr = json_decode($res, true);
        if (!empty($resArr)) {
            Logger::info('DealGradeTypeLevelInfoCache:'.$res);

            return $resArr;
        }
        $res = $this->dealTypeGradModel->getAllLevelByName($level2, $level3);
        if ($res) {
            $res['score'] = (0 != floatval($res['score3'])) ?
                $res['score3'] : ((0 != floatval($res['score2'])) ?
                    $res['score2'] : $res['score1']);
        }
        try {
            $redisCache->setex(self::PRODUCT_HASH.md5($level2.$level3), 120, json_encode($res));
        } catch (\Exception $e) {
            Logger::error('DealGradeTypeLevelInfoError:'.$e->getMessage());
        }
        Logger::info('DealGradeTypeLevelInfo:'.json_encode($res));

        return $res;
    }

    /*
     *通过层名获取所有三级分类
     */
    public function getAllThirdLayersByName($name){
        $node = $this->findByName($name);
        if(empty($node)){
            return array();
        }

        if($node['layer'] == 3){
            return array($node->getRow());
        }


        if($node['layer'] == 2){
           return $this->getbyParentId($node['id']);
        }

        if($node['layer'] == 1){
            return $this->dealTypeGradModel->getThirdLevelByFirstLevelId($node['id']);
        }

        return array();
    }
}
