<?php

namespace core\service;

use core\dao\AttachmentModel;

class AttachmentService extends BaseService {

    public function getAttachment($id) {
        return AttachmentModel::instance()->find($id, '*', true);
    }

/*
 * 根据附件id  将附件从vfs上读取出来并生成一个新文件 并返回文件路径
 * @param int $att_id
 * @return string $file
 */
function getAttrVfs($att_id) {
    //$service = new \core\service\AttachmentService();
    $att = $this->getAttachment($att_id);
    if(count($att)) {
        $att = $att->getRow();
        $file_name = $att['filename'];
        $content = \libs\vfs\Vfs::read($att['attachment']);
        $base_path = APP_ROOT_PATH.'runtime';
        $file = sprintf("%s/%s", $base_path, $file_name);
        $fp = fopen($file,'w+');
        if($fp) {
            fwrite($fp,$content);
            fclose($fp);
        }else{
            return '';
        }

        return $file;
    }
}
}
