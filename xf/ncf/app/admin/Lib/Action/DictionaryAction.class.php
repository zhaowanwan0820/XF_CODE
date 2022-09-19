<?php
class DictionaryAction extends CommonAction {
    // 首页
    public function index() {
        $model  = new DictionaryModel();
        $result = array();
        $count = $model->get_dict_count();
        if ($count > 0) {
            $p = new Page($count, 0);
            $result = $model->get_dictionary($p->firstRow, $p->listRows);
            foreach ($result as $k => $v) {
                $result[$k]['value'] = implode(" ; ", $v['value']);
            }
            $this->assign("page", $p->show());
        }

        $this->assign("list", $result);
        $this->display();
    }

    // 新增
    public function add() {
        $this->assign("act", "insert");
        $this->display("edit");
    }

    // 修改
    public function edit() {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : false;
        if (!$id) {
            $this->error(L("INVALID_OPERATION"));
        }

        $model  = new DictionaryModel();
        $result = $model->get_dictionary_by_id($id);
        $this->assign("dict", $result);
        $this->assign("act", "update");
        $this->display();
    }

    // 添加
    public function insert() {
        $key   = empty($_POST['key']) ? "" : $_POST['key'];
        $note  = empty($_POST['note']) ? "" : addslashes($_POST['note']);
        $value = $_POST['value'];
        $desc  = $_POST['desc'];

        if (!$key) {
            $this->error("字典键不可为空");
        }

        $model   = new DictionaryModel();
        $arr_val = array();
        foreach ($value as $k => $v) {
            if ($v) {
                $arr_val[] = array(
                    "value" => addslashes($v),
                    "desc" => addslashes($desc[$k]),
                );
            }
        }
        if (!$model->insert_dictionary($key, $arr_val, $note)) {
            $this->error(L("INSERT_FAILED") . " " . $model->get_err());
        } else {
            $this->success(L("INSERT_SUCCESS"));
        }
    }

    // 更新
    public function update() {
        $id    = isset($_POST['id']) ? intval($_POST['id']) : false;
        $key   = empty($_POST['key']) ? "" : addslashes($_POST['key']);
        $note  = empty($_POST['note']) ? "" : addslashes($_POST['note']);
        $value = $_POST['value'];
        $desc = $_POST['desc'];

        if (!$id) {
            $this->error(L("INVALID_OPERATION"));
        }
        if (!$key) {
            $this->error("字典键不可为空");
        }

        $model   = new DictionaryModel();
        $arr_val = array();
        foreach ($value as $k => $v) {
            if ($v) {
                $arr_val[] = array(
                    "value" => addslashes($v),
                    "desc" => addslashes($desc[$k]),
                );
            }
        }
        if (!$model->update_dictionary($id, $key, $arr_val, $note)) {
            $this->error(L("UPDATE_FAILED") . " " . $model->get_err());
        } else {
            $this->success(L("UPDATE_SUCCESS"));
        }
    }

    // 更新缓存
    public function flush() {
        $ajax = intval($_REQUEST['ajax']);
        $model = new DictionaryModel();
        if (!$model->flush()) {
            $this->error("操作失败", $ajax);
        } else {
            $this->success("更新成功", $ajax);
        }
    }

    // 批量删除
    public function foreverdelete() {
        $ajax = intval($_REQUEST['ajax']);
        $id   = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
        if (!$id) {
            $this->error(L("INVALID_OPERATION"), $ajax);
        }

        $model = new DictionaryModel();
        $id    = is_array($id) ? $id : array($id);
        if (!$model->delete_dictionary_by_ids($id)) {
            $this->error(l("FOREVER_DELETE_FAILED") . " " . $model->get_err(), $ajax);
        } else {
            $this->success(L("FOREVER_DELETE_SUCCESS"), $ajax);
        }
    }

}

?>