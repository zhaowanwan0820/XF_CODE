<?php
/**
 * DealTypeGradeAction.class.php.
 *
 * 产品等级分类管理
 *
 * @date 2017-2-16
 *
 * @author wangzhen3 <wangzhen3@ucfgroup.com>
 */
use core\service\DealTypeGradeService;
use core\service\DealProjectService;

class DealTypeGradeAction extends CommonAction
{
    private $dealTypeGradeService;

    public static $errors = array(
        0 => '成功',
        1 => '分类名称不能为空',
        2 => '分类名称重复',
        3 => '目前分类只支持三级分类',
        4 => '节点不存在',
        5 => '系统错误',
        6 => '参数错误',
        7 => '产品风险评分可为空或须大于0',
        8 => '分类启用过不能删除',
        9 => '停用此分类将造成上级分类停用',
        10 => '此分类下无已启用的%d级分类',
        11 => '此分类下有子级分类不能删除',
        12 => '已被第三方系统传入的项目成功使用，不能删除',
        13 => '已被第三方系统传入的项目成功使用，名称不能被编辑',
        14 => '此分类下面有下级分类，名称不能被编辑',
        15 => '新建一二级分类，不能直接被启用',
        16 => '上级菜单是停用状态，所以该分类不能被直接启用',
    );

    public function __construct()
    {
        parent::__construct();
        $this->dealTypeGradeService = new DealTypeGradeService();
    }

    /**
     * 分类操作界面.
     */
    public function index()
    {
        $tree = $this->getTree();
        $this->assign('tree', json_encode($tree));
        $this->display();
    }

    /**
     * 保存.
     */
    public function save()
    {
        $old_data = '';
        $data = $this->checkParam();
        if (0 != $data['code']) {
            $this->error(self::$errors[$data['code']]);
            exit;
        } else {
            if ($data['data']['id'] != 0) {
                $node = $this->dealTypeGradeService->getbyId($data['data']['id']);
                $old_data = $node->getRow();
                if (3 == $node['layer']) {
                    //已经被应用的不能能编辑
                    if ($this->getIsUseByProject($node['name'])) {
                        if (($data['data']['name'] != $old_data['name'])) {
                            $this->error(self::$errors[13]);
                            exit;
                        }
                    }
                } else {
                    $types = $this->dealTypeGradeService->getbyParentId($data['data']['id']);
                    if (!empty($types) && ($data['data']['name'] != $old_data['name'])) {
                        $this->error(self::$errors[14]);
                        exit;
                    }
                }
            }
            $result = $this->dealTypeGradeService->save($data['data']);
            if (!$result) {
                $this->error(self::$errors[5]);
                exit;
            }
            save_log('产品分类'.($old_data ? '更新成功' : '添加成功'), 1, $old_data, json_encode($data['data']));
        }
        $this->ajaxReturn('');
    }

    /**
     * 添加子级分类.
     */
    public function addChild()
    {
        $id = intval($_GET['id']);
        $this->assign('parent_id', $id);
        $this->getTreePath($id);
        $this->display('add');
    }

    /**
     * 添加子级分类.
     */
    public function addBrother()
    {
        $id = intval($_GET['id']);
        $node = $this->dealTypeGradeService->getbyId($id);
        $this->getTreePath($node['parent_id']);
        $this->assign('parent_id', $node['parent_id']);
        $this->display('add');
    }

    /**
     * 编辑分类.
     */
    public function edit()
    {
        $id = intval($_GET['id']);
        $node = $this->dealTypeGradeService->getbyId($id);
        $this->getTreePath($id);
        $this->assign('node', $node);
        $this->display();
    }

    /**
     * 删除分类.
     */
    public function del()
    {
        $id = intval($_POST['id']);
        $node = $this->dealTypeGradeService->getbyId($id);
        if (empty($node)) {
            $this->error(self::$errors[6]);
            exit;
        }
        //未被启用才能删除
        /*   if($this ->getIsUseByProject($node['name'])){
               $this->error(self::$errors[8]);exit;
           }*/

        //有子类不能被删除
        $subNodes = $this->dealTypeGradeService->getbyParentId($id);
        if (!empty($subNodes)) {
            $this->error(self::$errors[11]);
            exit;
        }
        //已经被应用的不能能删除
        if ($this->getIsUseByProject($node['name'])) {
            $this->error(self::$errors[12]);
            exit;
        }
        $result = $this->dealTypeGradeService->del($id);
        if (!$result) {
            $this->error(self::$errors[5]);
            exit;
        }
        $this->ajaxReturn('');
    }

    /**
     * ajax验证参数有效性.
     */
    public function check()
    {
        $data = $this->checkParam();
        if (0 == $data['code'] && !empty($data['data']['id'])) {
            if ($data['data']['status'] == dealTypeGradeService::DISABLED) {
                if (!empty($data['data']['parent_id'])) {
                    $count = $this->dealTypeGradeService->getEnableCountByParentIdExceptId($data['data']['parent_id'], $data['data']['id']);
                    if (0 == $count) {
                        $data['code'] = 9;
                    }
                }
            } else {
                if ($data['data']['layer'] < 3) {
                    $count = $this->dealTypeGradeService->getEnableCountByParentIdExceptId($data['data']['id']);
                    if (0 == $count) {
                        $data['code'] = 10;
                    }
                }
            }
        }

        $data['msg'] = sprintf(self::$errors[$data['code']], $data['data']['layer'] + 1);

        echo json_encode($data);
        exit;
    }

    private function getTree()
    {
        $tree = array();
        $list = $this->dealTypeGradeService->getDealTypeGradeList();
        foreach ($list as $key => $val) {
            $tree[$key]['id'] = $val['id'];
            $tree[$key]['pId'] = $val['parent_id'];
            $tree[$key]['name'] = $val['name'];
            $tree[$key]['t'] = $val['name'];
            $tree[$key]['layer'] = $val['layer'];
            $tree[$key]['radioFactor'] = $val['radio_factor'];
            if (1 == $val['is_use']) {
                if (dealTypeGradeService::DISABLED == $val['status']) {
                    $tree[$key]['font'] = array('background-color' => '#808080', 'color' => '#000000');
                }
            } else {
                $tree[$key]['font'] = array('background-color' => '#808080', 'color' => '#FFFFFF');
            }
        }

        return $tree;
    }

    /**
     * 校验参数.
     *
     * @return array
     */
    private function checkParam()
    {
        $data = array('code' => 0, 'data' => array());
        $id = intval($_POST['id']);
        $parent_id = intval($_POST['parent_id']);
        $name = addslashes(trim($_POST['name']));
        $status = intval($_POST['status']);
        $score = floatval($_POST['score']);
        $limitation = intval($_POST['limitation']);

        //分类名称不能为空
        if (empty($name)) {
            $data['code'] = 1;

            return $data;
        }

        //产品风险评分可为空或须大于0
        if ($score < 0) {
            $data['code'] = 7;

            return $data;
        }

        //分类名称不能重复
        $node = $this->dealTypeGradeService->getbyNameExceptId($name, $id, $parent_id);
        if (!empty($node)) {
            $data['code'] = 2;

            return $data;
        }

        if (0 != $parent_id) {
            //分类只能添加三级以上
            $node = $this->dealTypeGradeService->getbyId($parent_id);
            if ($node['layer'] >= 3) {
                $data['code'] = 3;

                return $data;
            }
            $data['data']['parent_id'] = strval($parent_id);
            $data['data']['layer'] = strval($node['layer'] + 1);
        } else {
            $data['data']['layer'] = '1';
        }

        if (0 != $id) {
            $data['data']['id'] = strval($id);
        } else {
            if ($data['data']['layer'] < 3) {
                if (dealTypeGradeService::ENABLED == $status) {
                    $data['code'] = 15;
                }
            }
        }

        $data['data']['name'] = strval($name);
        $data['data']['status'] = strval($status);
        $data['data']['score'] = strval($score);
        $data['data']['limitation'] = strval($limitation);

        return $data;
    }

    private function getTreePath($id)
    {
        $path = $this->dealTypeGradeService->getGradePath($id);
        $this->assign('path', $path);
    }

    private function getIsUseByProject($name)
    {
        $deal_project_service = new DealProjectService();
        $isUserByProject = $deal_project_service->isUselProductMix3($name);
        if ($isUserByProject) {
            return true;
        } else {
            return false;
        }
    }

    public function indexRadioFactor()
    {
        $tree = $this->getTree();
        $this->assign('tree', json_encode($tree));
        $this->display();
    }

    //设置返点比例
    public function setRadioFactor()
    {
        $id = intval($_REQUEST['id']);
        $radio = floatval($_REQUEST['radio']);
        $oldNode = $this->dealTypeGradeService->getbyId($id);
        $result = $this->dealTypeGradeService->setRadioFactor($radio, $id);
        if ($result) {
            $node = $this->dealTypeGradeService->getbyId($id);
            save_log("产品分类[{$node['name']}-{$id}]修改返点比例系数", 1, $oldNode['radio_factor'],$node['radio_factor']);
            $this->ajaxReturn($node['radio_factor']);
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 编辑分类.
     */
    public function editRadioFactor()
    {
        $id = intval($_REQUEST['id']);
        $node = $this->dealTypeGradeService->getbyId($id);
        $this->getTreePath($id);
        $this->assign('node', $node);
        $this->display();
    }

    public function getRadioFactor(){
        $id = intval($_REQUEST['id']);
        $node = $this->dealTypeGradeService->getbyId($id);
        $this->ajaxReturn($node['radio_factor']);
    }

}
