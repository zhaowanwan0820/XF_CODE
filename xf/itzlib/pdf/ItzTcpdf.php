<?php

include_once( WWW_DIR .'/thirdlib/tcpdf/tcpdf.php' );

class ItzTcpdf extends TCPDF {
    
    //新增的签名id
    protected $sig_obj_id_add = 0;
    //多个新增数字签名的数组
    protected $sig_obj_id_add_array = array();
    //新增的签名
    protected $signature_data_add = array();
    //新增的签名 临时存储 在_atr_incremental_updates里面执行
    protected $signature_data_add_tmp = array();
    //新增的签名区域
    protected $signature_appearance_add = array();
    //新增的签名区域 在_atr_incremental_updates里面执行
    protected $signature_appearance_add_tmp = array();
    //增量更新 标志位
    protected $incremental_updates_flag = 0;
    //增量更新 标志位 用于重写函数的标志位
    protected $incremental_updates_flag_tmp = 0;
    //新增的签名标志位
    protected $incremental_updates_sign_flag = 0;
    //增量更新 签名标志位 _getannotsrefs _putpages等函数使用,在_atr_incremental_updates函数中置位
    protected $incremental_updates_sign_flag_tmp = 0;
    //增量更新 第n个签名
    protected $incremental_sign_index =0;
    
    //上一个文档的 objid_catalog
    protected $pre_objid_catalog = 0;
    //上一个文档的 objid_info
    protected $pre_objid_info = 0;    
    //上一个文档的最后的n
    protected $pre_doc_n = 0; 
    //上一个文档page中的content值
    protected $pre_page_content_n = 0;
    //上一个prev
    protected $pre_prev = 0;
    //增量更新之前的文档的page
    protected $pre_page ;
    //上一个xmlobj
    protected $pre_xmlobj=0;
    
    //郑铮添加，临时标志位，用于判断是章盖在同一位置还是不同位置
    private $zzflag;
    
    
    //构造函数
    public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
    }
    
    public function set_incremental_updates(){
        if($this->incremental_updates_flag != true) {
            $this->incremental_updates_flag = true;
        }
    }
    /**
     * $signature_data_add_tmp 临时保存增量的签名数据
     * @public
     * @author kuangjun
     * @since (2013-10-15)
     */
    public function addSignatureFlag($signing_cert='', $private_key='', $private_key_password='', $extracerts='', $cert_type=2, $info=array()) {
        $this->incremental_updates_sign_flag = TRUE;
        $this->signature_data_add_tmp[] = array(
            'signing_cert' => $signing_cert,
            'private_key' => $private_key,
            'private_key_password' => $private_key_password,
            'extracerts' => $extracerts,
            'cert_type' => $cert_type,
            'info' => $info,
        );
    }
     /**
     * signature_appearance_addarray 保存增量的签名区域
     * @public
     * @author kuangjun
     * @since (2013-10-15)
     */
    public function addSignatureAppearanceFlag($x=0, $y=0, $w=0, $h=0, $page=-1, $name='') {
        $this->signature_appearance_add_tmp[] = array(
            'x'=>$x,
            'y'=>$y,
            'w'=>$w,
            'h'=>$h,
            'page'=>$page,
            'name'=>$name,
        ) ;
    }
    
    
     /**
     * 新增的签名
     * @public
     * @author Nicola Asuni
     * @since 4.6.005 (2009-04-24)
     */
    public function _atr_setSignature($signature) {
        extract($signature);
        ++$this->n;
        $this->sig_obj_id_add = $this->n; // signature widget
        $this->sig_obj_id_add_array[] = $this->sig_obj_id_add;
        //++$this->n; // signature object ($this->sig_obj_id + 1)
        $this->signature_data_add = array();
        if (strlen($signing_cert) == 0) {
            $this->Error('Please provide a certificate file and password!');
        }
        if (strlen($private_key) == 0) {
            $private_key = $signing_cert;
        }
        $this->signature_data_add['signcert'] = $signing_cert;
        $this->signature_data_add['privkey'] = $private_key;
        $this->signature_data_add['password'] = $private_key_password;
        $this->signature_data_add['extracerts'] = $extracerts;
        $this->signature_data_add['cert_type'] = $cert_type;
        $this->signature_data_add['info'] = $info;
    }

   
    /**
     * 新增签名的区域
     * @public
     * @author kuangjun
     * @since 5.3.011 (2010-06-17)
     */
    public function _atr_setSignatureAppearance($signature_appearance) {
        extract($signature_appearance);
        ++$this->n;
        $this->signature_appearance_add = array('objid' => $this->n) + $this->getSignatureAppearanceArray($x, $y, $w, $h, $page, $name);
    }
    /**
     * Unset all class variables except the following critical variables.
     * @param $destroyall (boolean) if true destroys all class variables, otherwise preserves critical variables.
     * @param $preserve_objcopy (boolean) if true preserves the objcopy variable
     * @public
     * @since 4.5.016 (2009-02-24)
     */
    public function _destroy($destroyall=false, $preserve_objcopy=false) {
        if($this->incremental_updates_flag){return;} //如果有
        if ($destroyall AND isset($this->diskcache) AND $this->diskcache AND (!$preserve_objcopy) AND (!TCPDF_STATIC::empty_string($this->buffer))) {
            // remove buffer file from cache
            unlink($this->buffer);
        }
        if ($destroyall AND isset($this->cached_files) AND !empty($this->cached_files)) {
            // remove cached files
            foreach ($this->cached_files as $cachefile) {
                if (is_file($cachefile)) {
                    unlink($cachefile);
                }
            }
            unset($this->cached_files);
        }
        foreach (array_keys(get_object_vars($this)) as $val) {
            if ($destroyall OR (
                ($val != 'internal_encoding')
                AND ($val != 'state')
                AND ($val != 'bufferlen')
                AND ($val != 'buffer')
                AND ($val != 'diskcache')
                AND ($val != 'cached_files')
                AND ($val != 'sign')
                AND ($val != 'signature_data')
                AND ($val != 'signature_max_length')
                AND ($val != 'signature_data_addarray') //add by kuangjun
                AND ($val != 'signature_appearance_addarray') //add by kuangjun
                AND ($val != 'incremental_updates_sign_flag') //add by kuangjun
                AND ($val != 'incremental_updates_flag') //add by kuangjun
                AND ($val != 'byterange_string')
                )) {
                if ((!$preserve_objcopy OR ($val != 'objcopy')) AND isset($this->$val)) {
                    unset($this->$val);
                }
            }
        }
    }
        
    
    public function _atr_incremental_updates(){
        if($this->incremental_updates_flag ==false){
            return;
        }
        $this->incremental_updates_flag_tmp = true;
        
        if($this->incremental_updates_sign_flag){
            $this->incremental_updates_sign_flag_tmp = 1;
            if(count($this->signature_data_add_tmp)>0 ){
                if(count($this->signature_data_add_tmp)!=count($this->signature_appearance_add_tmp)){
                    throw new Exception("signature num should equal to signature_arrearance num", 1);
                }
                
                $bufferTmp = $this->buffer; 
                foreach($this->signature_data_add_tmp as $key=>&$signature_data){            
                    $this->pre_doc_n = $this->n;
                    $this->incremental_sign_index = $key;
                    // remove last newline
                    if($this->sign) $this->_out("");
                    $this->_atr_setSignature($signature_data);
                    $this->_atr_setSignatureAppearance($this->signature_appearance_add_tmp[$key]);
                    
                    $this->_atr_putpages(); //只需要增量的更新有签名的page
                    $this->_putannotsobjs();
                    
                    // widget annotation for signature
                    $out = $this->_getobj($this->sig_obj_id_add )."\n";
                    $out .= '<< /Type /Annot';
                    $out .= ' /Subtype /Widget';
                    $out .= ' /Rect ['.$this->signature_appearance_add['rect'].']';
                    $out .= ' /P '.$this->page_obj_id[($this->signature_appearance_add['page'])].' 0 R'; // link to signature appearance page
                    $out .= ' /F 4';
                    $out .= ' /FT /Sig';
                    $out .= ' /T '.$this->_textstring($this->signature_appearance_add['name'], $this->sig_obj_id_add );
                    $out .= ' /Ff 0';
                    $out .= ' /V '.($this->sig_obj_id_add  + 1).' 0 R';
                    $out .= ' >>';
                    $out .= "\n".'endobj';
                    $this->_out($out);
                    $this->_art_putsignature(); //添加上签名的原始源码片段
                    
                    //enddoc里的内容摘出
                    $this->_putcatalog();
                    
                    // Cross-ref
                    $o = strlen($this->buffer);
                    // XREF section
                    $this->_out('xref');
                    $this->_out('0 1');
                    $this->_out('0000000000 65535 f ');
                    $this->_out($this->page_obj_id[$this->signature_appearance_add['page']]." 1");
                    $this->_out(sprintf('%010d 00000 n ',$this->offsets[$this->page_obj_id[$this->signature_appearance_add['page']]]));
                    $this->_out($this->pre_objid_catalog." 1");
                    $this->_out(sprintf('%010d 00000 n ',$this->offsets[$this->pre_objid_catalog]));
                    $this->_out(($this->pre_doc_n+1).' '.($this->n-$this->pre_doc_n));
                    $freegen = ($this->n + 2);
                    for ($i=$this->pre_doc_n+1; $i <= $this->n; ++$i) {
                        if (!isset($this->offsets[$i]) AND ($i > 1)) {
                            $this->_out(sprintf('0000000000 %05d f ', $freegen));
                            ++$freegen;
                        } else {
                            $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
                        }
                    }
                    
                    // TRAILER
                    $out = 'trailer'."\n";
                    $out .= '<<';
                    $out .= ' /Size '.($this->n + 1);
                    $out .= ' /Root '.$this->pre_objid_catalog.' 0 R';
                    $out .= ' /Info '.$this->pre_objid_info.' 0 R';
                    $out .= ' /Prev '.$this->pre_prev;
                    if ($this->encrypted) {
                        $out .= ' /Encrypt '.$this->encryptdata['objid'].' 0 R';
                    }
                    $out .= ' /ID [ <'.$this->file_id.'> <'.$this->file_id.'> ]';
                    $out .= ' >>';
                    $this->_out($out);
                    $this->_out('startxref');
                    $this->_out($o);
                    $this->pre_prev = $o; 
                    $this->_out('%%EOF');
                    $this->state = 3; // end-of-doc
                    if ($this->diskcache) {
                        // remove temporary files used for images
                        foreach ($this->imagekeys as $key) {
                            // remove temporary files
                            unlink($this->images[$key]);
                        }
                        foreach ($this->fontkeys as $key) {
                            // remove temporary files
                            unlink($this->fonts[$key]);
                        }
                    }
                    //var_dump($this->buffer);die;
                    $this->buffer = $this->_atr_sign($bufferTmp);//对源码片段进行签名
                    $this->bufferlen = strlen($this->buffer);
                }
                
                
            }
        }
        $this->incremental_updates_flag =0;
        $this->_destroy();
    }
    
    /**
     * Add certification signature (DocMDP or UR3)
     * You can set only one signature type
     * @protected
     * @author Nicola Asuni
     * @since 4.6.008 (2009-05-07)
     */
    protected function _art_putsignature() {
        if (!isset($this->signature_data_add['cert_type'])) {
            return;
        }
        $sigobjid = ($this->sig_obj_id_add + 1);
        
        $out = $this->_getobj($sigobjid)."\n";
        $out .= '<< /Type /Sig';
        $out .= ' /Filter /Adobe.PPKLite';
        $out .= ' /SubFilter /adbe.pkcs7.detached';
        $out .= ' '.TCPDF_STATIC::$byterange_string;
        $out .= ' /Contents<'.str_repeat('0', $this->signature_max_length).'>';
        $out .= ' /Reference ['; // array of signature reference dictionaries
        $out .= ' << /Type /SigRef';
        if ($this->signature_data_add['cert_type'] > 0) {
            $out .= ' /TransformMethod /DocMDP';
            $out .= ' /TransformParams <<';
            $out .= ' /Type /TransformParams';
            $out .= ' /P '.$this->signature_data_add['cert_type'];
            $out .= ' /V /1.2';
        } else {
            $out .= ' /TransformMethod /UR3';
            $out .= ' /TransformParams <<';
            $out .= ' /Type /TransformParams';
            $out .= ' /V /2.2';
            if (!TCPDF_STATIC::empty_string($this->ur['document'])) {
                $out .= ' /Document['.$this->ur['document'].']';
            }
            if (!TCPDF_STATIC::empty_string($this->ur['form'])) {
                $out .= ' /Form['.$this->ur['form'].']';
            }
            if (!TCPDF_STATIC::empty_string($this->ur['signature'])) {
                $out .= ' /Signature['.$this->ur['signature'].']';
            }
            if (!TCPDF_STATIC::empty_string($this->ur['annots'])) {
                $out .= ' /Annots['.$this->ur['annots'].']';
            }
            if (!TCPDF_STATIC::empty_string($this->ur['ef'])) {
                $out .= ' /EF['.$this->ur['ef'].']';
            }
            if (!TCPDF_STATIC::empty_string($this->ur['formex'])) {
                $out .= ' /FormEX['.$this->ur['formex'].']';
            }
        }
        $out .= ' >>'; // close TransformParams
        // optional digest data (values must be calculated and replaced later)
        //$out .= ' /Data ********** 0 R';
        //$out .= ' /DigestMethod/MD5';
        //$out .= ' /DigestLocation[********** 34]';
        //$out .= ' /DigestValue<********************************>';
        $out .= ' >>';
        $out .= ' ]'; // end of reference
        if (isset($this->signature_data_add['info']['Name']) AND !TCPDF_STATIC::empty_string($this->signature_data_add['info']['Name'])) {
            $out .= ' /Name '.$this->_textstring($this->signature_data_add['info']['Name'], $sigobjid);
        }
        if (isset($this->signature_data_add['info']['Location']) AND !TCPDF_STATIC::empty_string($this->signature_data_add['info']['Location'])) {
            $out .= ' /Location '.$this->_textstring($this->signature_data_add['info']['Location'], $sigobjid);
        }
        if (isset($this->signature_data_add['info']['Reason']) AND !TCPDF_STATIC::empty_string($this->signature_data_add['info']['Reason'])) {
            $out .= ' /Reason '.$this->_textstring($this->signature_data_add['info']['Reason'], $sigobjid);
        }
        if (isset($this->signature_data_add['info']['ContactInfo']) AND !TCPDF_STATIC::empty_string($this->signature_data_add['info']['ContactInfo'])) {
            $out .= ' /ContactInfo '.$this->_textstring($this->signature_data_add['info']['ContactInfo'], $sigobjid);
        }
        $out .= ' /M '.$this->_datestring($sigobjid, $this->doc_modification_timestamp);
        $out .= ' >>';
        $out .= "\n".'endobj';
        $this->_out($out);
    }
    
    /**
     * 从output函数中抽出 签名完成后替换掉content的000内容
     * 
     * */
    private function _atr_sign($bufferTmp){
        // *** apply digital signature to the document ***
        // get the document content
        $pdfdoc = $this->getBuffer();
        // remove last newline
        $pdfdoc = substr($pdfdoc, 0, -1);
        // Remove the original buffer
        if (isset($this->diskcache) AND $this->diskcache) {
            // remove buffer file from cache
            unlink($this->buffer);
        }
        unset($this->buffer);
        // remove filler space
        $byterange_string_len = strlen(TCPDF_STATIC::$byterange_string);
        // define the ByteRange
        $byte_range = array();
        $byte_range[0] = 0;
        $byte_range[1] = strpos($pdfdoc, TCPDF_STATIC::$byterange_string) + $byterange_string_len + 10;
        $byte_range[2] = $byte_range[1] + $this->signature_max_length + 2;
        $byte_range[3] = strlen($pdfdoc) - $byte_range[2];
        $pdfdoc = substr($pdfdoc, 0, $byte_range[1]).substr($pdfdoc, $byte_range[2]);
        // replace the ByteRange
        $byterange = sprintf('/ByteRange[0 %u %u %u]', $byte_range[1], $byte_range[2], $byte_range[3]);
        $byterange .= str_repeat(' ', ($byterange_string_len - strlen($byterange)));
        $pdfdoc = str_replace(TCPDF_STATIC::$byterange_string, $byterange, $pdfdoc);
        // write the document to a temporary folder
        $tempdoc = TCPDF_STATIC::getObjFilename('tmppdf');
        $f = fopen($tempdoc, 'wb');
        if (!$f) {
            $this->Error('Unable to create temporary file: '.$tempdoc);
        }
        $pdfdoc_length = strlen($pdfdoc);
        fwrite($f, $pdfdoc, $pdfdoc_length);
        fclose($f);
        // get digital signature via openssl library
        $tempsign = TCPDF_STATIC::getObjFilename('tmpsig');
        if (empty($this->signature_data['extracerts'])) {
            openssl_pkcs7_sign($tempdoc, $tempsign, $this->signature_data_add['signcert'], array($this->signature_data_add['privkey'], $this->signature_data_add['password']), array(), PKCS7_BINARY | PKCS7_DETACHED);
        } else {
            openssl_pkcs7_sign($tempdoc, $tempsign, $this->signature_data_add['signcert'], array($this->signature_data_add['privkey'], $this->signature_data_add['password']), array(), PKCS7_BINARY | PKCS7_DETACHED, $this->signature_data['extracerts']);
        }
        unlink($tempdoc);
        // read signature
        $signature = file_get_contents($tempsign);
        unlink($tempsign);
        // extract signature
        $signature = substr($signature, $pdfdoc_length);
        $signature = substr($signature, (strpos($signature, "%%EOF\n\n------") + 13));
        $tmparr = explode("\n\n", $signature);
        $signature = $tmparr[1];
        unset($tmparr);
        // decode signature
        $signature = base64_decode(trim($signature));
        // convert signature to hex
        $signature = current(unpack('H*', $signature));
        $signature = str_pad($signature, $this->signature_max_length, '0');
        // disable disk caching
        $this->diskcache = false;
        // Add signature to the document
        return substr($pdfdoc, 0, $byte_range[1]).'<'.$signature.'>'.substr($pdfdoc, $byte_range[1]);
            
    }

    
    
    /**
     * 新增函数,没有重写putpages
     * 不许要输出所有的页面，只需要输出签名所在的页面
     * Output pages (and replace page number aliases).
     * @protected
     */
    protected function _atr_putpages() {
        $n = $this->signature_appearance_add['page'];//需要更新的页面，签名所在页面
        
        $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
        // get internal aliases for page numbers
        $pnalias = $this->getAllInternalPageNumberAliases();
        $num_pages = $this->numpages;
        $ptpa = TCPDF_STATIC::formatPageNumber(($this->starting_page_number + $num_pages - 1));
        $ptpu = TCPDF_FONTS::UTF8ToUTF16BE($ptpa, false, $this->isunicode, $this->CurrentFont);
        $ptp_num_chars = $this->GetNumChars($ptpa);
        $pagegroupnum = 0;
        $groupnum = 0;
        $ptgu = 1;
        $ptga = 1;
        $ptg_num_chars = 1;
         
        // get current page
        $temppage = $this->getPageBuffer($n);
        $pagelen = strlen($temppage);
        // set replacements for total pages number
        $pnpa = TCPDF_STATIC::formatPageNumber(($this->starting_page_number + $n - 1));
        $pnpu = TCPDF_FONTS::UTF8ToUTF16BE($pnpa, false, $this->isunicode, $this->CurrentFont);
        $pnp_num_chars = $this->GetNumChars($pnpa);
        $pdiff = 0; // difference used for right shift alignment of page numbers
        $gdiff = 0; // difference used for right shift alignment of page group numbers
        if (!empty($this->pagegroups)) { 
            if (isset($this->newpagegroup[$n])) {
                $pagegroupnum = 0;
                ++$groupnum;
                $ptga = TCPDF_STATIC::formatPageNumber($this->pagegroups[$groupnum]);
                $ptgu = TCPDF_FONTS::UTF8ToUTF16BE($ptga, false, $this->isunicode, $this->CurrentFont);
                $ptg_num_chars = $this->GetNumChars($ptga);
            }
            ++$pagegroupnum;
            $pnga = TCPDF_STATIC::formatPageNumber($pagegroupnum);
            $pngu = TCPDF_FONTS::UTF8ToUTF16BE($pnga, false, $this->isunicode, $this->CurrentFont);
            $png_num_chars = $this->GetNumChars($pnga);
            // replace page numbers
            $replace = array();
            $replace[] = array($ptgu, $ptg_num_chars, 9, $pnalias[2]['u']);
            $replace[] = array($ptga, $ptg_num_chars, 7, $pnalias[2]['a']);
            $replace[] = array($pngu, $png_num_chars, 9, $pnalias[3]['u']);
            $replace[] = array($pnga, $png_num_chars, 7, $pnalias[3]['a']);
            list($temppage, $gdiff) = TCPDF_STATIC::replacePageNumAliases($temppage, $replace, $gdiff);
        }
        // replace page numbers
        $replace = array();
        $replace[] = array($ptpu, $ptp_num_chars, 9, $pnalias[0]['u']);
        $replace[] = array($ptpa, $ptp_num_chars, 7, $pnalias[0]['a']);
        $replace[] = array($pnpu, $pnp_num_chars, 9, $pnalias[1]['u']);
        $replace[] = array($pnpa, $pnp_num_chars, 7, $pnalias[1]['a']);
        list($temppage, $pdiff) = TCPDF_STATIC::replacePageNumAliases($temppage, $replace, $pdiff);
        // replace right shift alias
        $temppage = $this->replaceRightShiftPageNumAliases($temppage, $pnalias[4], max($pdiff, $gdiff));
        // replace EPS marker
        $temppage = str_replace($this->epsmarker, '', $temppage);
        //Page
        $this->_out($this->_getobj($this->page_obj_id[$n]));    //输出需要更新的页面    
        
        $out = '<<';
        $out .= ' /Type /Page';
        $out .= ' /Parent 1 0 R';
        $out .= ' /LastModified '.$this->_datestring(0, $this->doc_modification_timestamp);
        $out .= ' /Resources 2 0 R';
        foreach ($this->page_boxes as $box) {
            $out .= ' /'.$box;
            $out .= sprintf(' [%F %F %F %F]', $this->pagedim[$n][$box]['llx'], $this->pagedim[$n][$box]['lly'], $this->pagedim[$n][$box]['urx'], $this->pagedim[$n][$box]['ury']);
        }
        if (isset($this->pagedim[$n]['BoxColorInfo']) AND !empty($this->pagedim[$n]['BoxColorInfo'])) {
            $out .= ' /BoxColorInfo <<';
            foreach ($this->page_boxes as $box) {
                if (isset($this->pagedim[$n]['BoxColorInfo'][$box])) {
                    $out .= ' /'.$box.' <<';
                    if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['C'])) {
                        $color = $this->pagedim[$n]['BoxColorInfo'][$box]['C'];
                        $out .= ' /C [';
                        $out .= sprintf(' %F %F %F', ($color[0] / 255), ($color[1] / 255), ($color[2] / 255));
                        $out .= ' ]';
                    }
                    if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['W'])) {
                        $out .= ' /W '.($this->pagedim[$n]['BoxColorInfo'][$box]['W'] * $this->k);
                    }
                    if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['S'])) {
                        $out .= ' /S /'.$this->pagedim[$n]['BoxColorInfo'][$box]['S'];
                    }
                    if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['D'])) {
                        $dashes = $this->pagedim[$n]['BoxColorInfo'][$box]['D'];
                        $out .= ' /D [';
                        foreach ($dashes as $dash) {
                            $out .= sprintf(' %F', ($dash * $this->k));
                        }
                        $out .= ' ]';
                    }
                    $out .= ' >>';
                }
            }
            $out .= ' >>';
        }
        $out .= ' /Contents '.($this->page_obj_id[$n] + 1).' 0 R';
        $out .= ' /Rotate '.$this->pagedim[$n]['Rotate'];
        if (!$this->pdfa_mode) {
            $out .= ' /Group << /Type /Group /S /Transparency /CS /DeviceRGB >>';
        }
        if (isset($this->pagedim[$n]['trans']) AND !empty($this->pagedim[$n]['trans'])) {
            // page transitions
            if (isset($this->pagedim[$n]['trans']['Dur'])) {
                $out .= ' /Dur '.$this->pagedim[$n]['trans']['Dur'];
            }
            $out .= ' /Trans <<';
            $out .= ' /Type /Trans';
            if (isset($this->pagedim[$n]['trans']['S'])) {
                $out .= ' /S /'.$this->pagedim[$n]['trans']['S'];
            }
            if (isset($this->pagedim[$n]['trans']['D'])) {
                $out .= ' /D '.$this->pagedim[$n]['trans']['D'];
            }
            if (isset($this->pagedim[$n]['trans']['Dm'])) {
                $out .= ' /Dm /'.$this->pagedim[$n]['trans']['Dm'];
            }
            if (isset($this->pagedim[$n]['trans']['M'])) {
                $out .= ' /M /'.$this->pagedim[$n]['trans']['M'];
            }
            if (isset($this->pagedim[$n]['trans']['Di'])) {
                $out .= ' /Di '.$this->pagedim[$n]['trans']['Di'];
            }
            if (isset($this->pagedim[$n]['trans']['SS'])) {
                $out .= ' /SS '.$this->pagedim[$n]['trans']['SS'];
            }
            if (isset($this->pagedim[$n]['trans']['B'])) {
                $out .= ' /B '.$this->pagedim[$n]['trans']['B'];
            }
            $out .= ' >>';
        }

        $out .= $this->_getannotsrefs($n);
        $out .= ' /PZ '.$this->pagedim[$n]['PZ'];
        $out .= ' >>';
        $out .= "\n".'endobj';
        $this->_out($out);
        //Page content
        if ($this->diskcache) {
            // remove temporary files
            unlink($this->pages[$n]);
        }
                
    }
        
     /**
     * 重写函数，增加新增签名区域的annot引用
     * @param $n (int) page number
     * @return string
     * @protected
     * @author Nicola Asuni
     * @since 5.0.010 (2010-05-17)
     */
    protected function _getannotsrefs($n) {
        if (!(isset($this->PageAnnots[$n]) OR ($this->sign AND isset($this->signature_data['cert_type'])))) {
            return '';
        }
        $out = ' /Annots [';
        if (isset($this->PageAnnots[$n])) {
            foreach ($this->PageAnnots[$n] as $key => $val) {
                if (!in_array($val['n'], $this->radio_groups)) {
                    $out .= ' '.$val['n'].' 0 R';
                }
            }
            // add radiobutton groups
            if (isset($this->radiobutton_groups[$n])) {
                foreach ($this->radiobutton_groups[$n] as $key => $data) {
                    if (isset($data['n'])) {
                        $out .= ' '.$data['n'].' 0 R';
                    }
                }
            }
        }
        if ($this->sign AND ($n == $this->signature_appearance['page']) AND isset($this->signature_data['cert_type'])) {
            // set reference for signature object
            $out .= ' '.$this->sig_obj_id.' 0 R';
        }
        ///add by kuangjun
        if($this->incremental_updates_sign_flag_tmp ){  
                foreach($this->sig_obj_id_add_array as $key=>$value){
                    //郑铮修改20150215
                    if($this->zzflag==true){//章在同一页上
                        if($key<=$this->incremental_sign_index){
                            $out .= ' '.$value.' 0 R';
                        }
                    }else{//章在不同页上
                        if($key==$this->incremental_sign_index){
                            $out .= ' '.$value.' 0 R';
                        }
                    }
                    //
                }
        }///end of add
        if (!empty($this->empty_signature_appearance)) {
            foreach ($this->empty_signature_appearance as $esa) {
                if ($esa['page'] == $n) {
                    // set reference for empty signature objects
                    $out .= ' '.$esa['objid'].' 0 R';
                }
            }
        }
        $out .= ' ]';
        return $out;
    }

    /**
     * Output annotations objects for all pages.
     * !!! THIS METHOD IS NOT YET COMPLETED !!!
     * See section 12.5 of PDF 32000_2008 reference.
     * @protected
     * @author Nicola Asuni
     * @since 4.0.018 (2008-08-06)
     */
    protected function _putannotsobjs() {
        // reset object counter
        for ($n=1; $n <= $this->numpages; ++$n) {
            if (isset($this->PageAnnots[$n])) {
                // set page annotations
                foreach ($this->PageAnnots[$n] as $key => $pl) {
                    $annot_obj_id = $this->PageAnnots[$n][$key]['n'];
                    ///add by kuangjun
                    if($this->incremental_updates_flag_tmp){
                        if($annot_obj_id<$this->pre_doc_n){
                            continue;
                        }
                    }///end of add
                    // create annotation object for grouping radiobuttons
                    if (isset($this->radiobutton_groups[$n][$pl['txt']]) AND is_array($this->radiobutton_groups[$n][$pl['txt']])) {
                        $radio_button_obj_id = $this->radiobutton_groups[$n][$pl['txt']]['n'];
                        $annots = '<<';
                        $annots .= ' /Type /Annot';
                        $annots .= ' /Subtype /Widget';
                        $annots .= ' /Rect [0 0 0 0]';
                        if ($this->radiobutton_groups[$n][$pl['txt']]['#readonly#']) {
                            // read only
                            $annots .= ' /F 68';
                            $annots .= ' /Ff 49153';
                        } else {
                            $annots .= ' /F 4'; // default print for PDF/A
                            $annots .= ' /Ff 49152';
                        }
                        $annots .= ' /T '.$this->_datastring($pl['txt'], $radio_button_obj_id);
                        if (isset($pl['opt']['tu']) AND is_string($pl['opt']['tu'])) {
                            $annots .= ' /TU '.$this->_datastring($pl['opt']['tu'], $radio_button_obj_id);
                        }
                        $annots .= ' /FT /Btn';
                        $annots .= ' /Kids [';
                        $defval = '';
                        foreach ($this->radiobutton_groups[$n][$pl['txt']] as $key => $data) {
                            if (isset($data['kid'])) {
                                $annots .= ' '.$data['kid'].' 0 R';
                                if ($data['def'] !== 'Off') {
                                    $defval = $data['def'];
                                }
                            }
                        }
                        $annots .= ' ]';
                        if (!empty($defval)) {
                            $annots .= ' /V /'.$defval;
                        }
                        $annots .= ' >>';
                        $this->_out($this->_getobj($radio_button_obj_id)."\n".$annots."\n".'endobj');
                        $this->form_obj_id[] = $radio_button_obj_id;
                        // store object id to be used on Parent entry of Kids
                        $this->radiobutton_groups[$n][$pl['txt']] = $radio_button_obj_id;
                    }
                    $formfield = false;
                    $pl['opt'] = array_change_key_case($pl['opt'], CASE_LOWER);
                    $a = $pl['x'] * $this->k;
                    $b = $this->pagedim[$n]['h'] - (($pl['y'] + $pl['h']) * $this->k);
                    $c = $pl['w'] * $this->k;
                    $d = $pl['h'] * $this->k;
                    $rect = sprintf('%F %F %F %F', $a, $b, $a+$c, $b+$d);
                    // create new annotation object
                    $annots = '<</Type /Annot';
                    $annots .= ' /Subtype /'.$pl['opt']['subtype'];
                    $annots .= ' /Rect ['.$rect.']';
                    $ft = array('Btn', 'Tx', 'Ch', 'Sig');
                    if (isset($pl['opt']['ft']) AND in_array($pl['opt']['ft'], $ft)) {
                        $annots .= ' /FT /'.$pl['opt']['ft'];
                        $formfield = true;
                    }
                    $annots .= ' /Contents '.$this->_textstring($pl['txt'], $annot_obj_id);
                    $annots .= ' /P '.$this->page_obj_id[$n].' 0 R';
                    $annots .= ' /NM '.$this->_datastring(sprintf('%04u-%04u', $n, $key), $annot_obj_id);
                    $annots .= ' /M '.$this->_datestring($annot_obj_id, $this->doc_modification_timestamp);
                    if (isset($pl['opt']['f'])) {
                        $fval = 0;
                        if (is_array($pl['opt']['f'])) {
                            foreach ($pl['opt']['f'] as $f) {
                                switch (strtolower($f)) {
                                    case 'invisible': {
                                        $fval += 1 << 0;
                                        break;
                                    }
                                    case 'hidden': {
                                        $fval += 1 << 1;
                                        break;
                                    }
                                    case 'print': {
                                        $fval += 1 << 2;
                                        break;
                                    }
                                    case 'nozoom': {
                                        $fval += 1 << 3;
                                        break;
                                    }
                                    case 'norotate': {
                                        $fval += 1 << 4;
                                        break;
                                    }
                                    case 'noview': {
                                        $fval += 1 << 5;
                                        break;
                                    }
                                    case 'readonly': {
                                        $fval += 1 << 6;
                                        break;
                                    }
                                    case 'locked': {
                                        $fval += 1 << 8;
                                        break;
                                    }
                                    case 'togglenoview': {
                                        $fval += 1 << 9;
                                        break;
                                    }
                                    case 'lockedcontents': {
                                        $fval += 1 << 10;
                                        break;
                                    }
                                    default: {
                                        break;
                                    }
                                }
                            }
                        } else {
                            $fval = intval($pl['opt']['f']);
                        }
                    } else {
                        $fval = 4;
                    }
                    if ($this->pdfa_mode) {
                        // force print flag for PDF/A mode
                        $fval |= 4;
                    }
                    $annots .= ' /F '.intval($fval);
                    if (isset($pl['opt']['as']) AND is_string($pl['opt']['as'])) {
                        $annots .= ' /AS /'.$pl['opt']['as'];
                    }
                    if (isset($pl['opt']['ap'])) {
                        // appearance stream
                        $annots .= ' /AP <<';
                        if (is_array($pl['opt']['ap'])) {
                            foreach ($pl['opt']['ap'] as $apmode => $apdef) {
                                // $apmode can be: n = normal; r = rollover; d = down;
                                $annots .= ' /'.strtoupper($apmode);
                                if (is_array($apdef)) {
                                    $annots .= ' <<';
                                    foreach ($apdef as $apstate => $stream) {
                                        // reference to XObject that define the appearance for this mode-state
                                        $apsobjid = $this->_putAPXObject($c, $d, $stream);
                                        $annots .= ' /'.$apstate.' '.$apsobjid.' 0 R';
                                    }
                                    $annots .= ' >>';
                                } else {
                                    // reference to XObject that define the appearance for this mode
                                    $apsobjid = $this->_putAPXObject($c, $d, $apdef);
                                    $annots .= ' '.$apsobjid.' 0 R';
                                }
                            }
                        } else {
                            $annots .= $pl['opt']['ap'];
                        }
                        $annots .= ' >>';
                    }
                    if (isset($pl['opt']['bs']) AND (is_array($pl['opt']['bs']))) {
                        $annots .= ' /BS <<';
                        $annots .= ' /Type /Border';
                        if (isset($pl['opt']['bs']['w'])) {
                            $annots .= ' /W '.intval($pl['opt']['bs']['w']);
                        }
                        $bstyles = array('S', 'D', 'B', 'I', 'U');
                        if (isset($pl['opt']['bs']['s']) AND in_array($pl['opt']['bs']['s'], $bstyles)) {
                            $annots .= ' /S /'.$pl['opt']['bs']['s'];
                        }
                        if (isset($pl['opt']['bs']['d']) AND (is_array($pl['opt']['bs']['d']))) {
                            $annots .= ' /D [';
                            foreach ($pl['opt']['bs']['d'] as $cord) {
                                $annots .= ' '.intval($cord);
                            }
                            $annots .= ']';
                        }
                        $annots .= ' >>';
                    } else {
                        $annots .= ' /Border [';
                        if (isset($pl['opt']['border']) AND (count($pl['opt']['border']) >= 3)) {
                            $annots .= intval($pl['opt']['border'][0]).' ';
                            $annots .= intval($pl['opt']['border'][1]).' ';
                            $annots .= intval($pl['opt']['border'][2]);
                            if (isset($pl['opt']['border'][3]) AND is_array($pl['opt']['border'][3])) {
                                $annots .= ' [';
                                foreach ($pl['opt']['border'][3] as $dash) {
                                    $annots .= intval($dash).' ';
                                }
                                $annots .= ']';
                            }
                        } else {
                            $annots .= '0 0 0';
                        }
                        $annots .= ']';
                    }
                    if (isset($pl['opt']['be']) AND (is_array($pl['opt']['be']))) {
                        $annots .= ' /BE <<';
                        $bstyles = array('S', 'C');
                        if (isset($pl['opt']['be']['s']) AND in_array($pl['opt']['be']['s'], $bstyles)) {
                            $annots .= ' /S /'.$pl['opt']['bs']['s'];
                        } else {
                            $annots .= ' /S /S';
                        }
                        if (isset($pl['opt']['be']['i']) AND ($pl['opt']['be']['i'] >= 0) AND ($pl['opt']['be']['i'] <= 2)) {
                            $annots .= ' /I '.sprintf(' %F', $pl['opt']['be']['i']);
                        }
                        $annots .= '>>';
                    }
                    if (isset($pl['opt']['c']) AND (is_array($pl['opt']['c'])) AND !empty($pl['opt']['c'])) {
                        $annots .= ' /C '.TCPDF_COLORS::getColorStringFromArray($pl['opt']['c']);
                    }
                    //$annots .= ' /StructParent ';
                    //$annots .= ' /OC ';
                    $markups = array('text', 'freetext', 'line', 'square', 'circle', 'polygon', 'polyline', 'highlight', 'underline', 'squiggly', 'strikeout', 'stamp', 'caret', 'ink', 'fileattachment', 'sound');
                    if (in_array(strtolower($pl['opt']['subtype']), $markups)) {
                        // this is a markup type
                        if (isset($pl['opt']['t']) AND is_string($pl['opt']['t'])) {
                            $annots .= ' /T '.$this->_textstring($pl['opt']['t'], $annot_obj_id);
                        }
                        //$annots .= ' /Popup ';
                        if (isset($pl['opt']['ca'])) {
                            $annots .= ' /CA '.sprintf('%F', floatval($pl['opt']['ca']));
                        }
                        if (isset($pl['opt']['rc'])) {
                            $annots .= ' /RC '.$this->_textstring($pl['opt']['rc'], $annot_obj_id);
                        }
                        $annots .= ' /CreationDate '.$this->_datestring($annot_obj_id, $this->doc_creation_timestamp);
                        //$annots .= ' /IRT ';
                        if (isset($pl['opt']['subj'])) {
                            $annots .= ' /Subj '.$this->_textstring($pl['opt']['subj'], $annot_obj_id);
                        }
                        //$annots .= ' /RT ';
                        //$annots .= ' /IT ';
                        //$annots .= ' /ExData ';
                    }
                    $lineendings = array('Square', 'Circle', 'Diamond', 'OpenArrow', 'ClosedArrow', 'None', 'Butt', 'ROpenArrow', 'RClosedArrow', 'Slash');
                    // Annotation types
                    switch (strtolower($pl['opt']['subtype'])) {
                        case 'text': {
                            if (isset($pl['opt']['open'])) {
                                $annots .= ' /Open '. (strtolower($pl['opt']['open']) == 'true' ? 'true' : 'false');
                            }
                            $iconsapp = array('Comment', 'Help', 'Insert', 'Key', 'NewParagraph', 'Note', 'Paragraph');
                            if (isset($pl['opt']['name']) AND in_array($pl['opt']['name'], $iconsapp)) {
                                $annots .= ' /Name /'.$pl['opt']['name'];
                            } else {
                                $annots .= ' /Name /Note';
                            }
                            $statemodels = array('Marked', 'Review');
                            if (isset($pl['opt']['statemodel']) AND in_array($pl['opt']['statemodel'], $statemodels)) {
                                $annots .= ' /StateModel /'.$pl['opt']['statemodel'];
                            } else {
                                $pl['opt']['statemodel'] = 'Marked';
                                $annots .= ' /StateModel /'.$pl['opt']['statemodel'];
                            }
                            if ($pl['opt']['statemodel'] == 'Marked') {
                                $states = array('Accepted', 'Unmarked');
                            } else {
                                $states = array('Accepted', 'Rejected', 'Cancelled', 'Completed', 'None');
                            }
                            if (isset($pl['opt']['state']) AND in_array($pl['opt']['state'], $states)) {
                                $annots .= ' /State /'.$pl['opt']['state'];
                            } else {
                                if ($pl['opt']['statemodel'] == 'Marked') {
                                    $annots .= ' /State /Unmarked';
                                } else {
                                    $annots .= ' /State /None';
                                }
                            }
                            break;
                        }
                        case 'link': {
                            if (is_string($pl['txt'])) {
                                if ($pl['txt'][0] == '#') {
                                    // internal destination
                                    $annots .= ' /Dest /'.TCPDF_STATIC::encodeNameObject(substr($pl['txt'], 1));
                                } elseif ($pl['txt'][0] == '%') {
                                    // embedded PDF file
                                    $filename = basename(substr($pl['txt'], 1));
                                    $annots .= ' /A << /S /GoToE /D [0 /Fit] /NewWindow true /T << /R /C /P '.($n - 1).' /A '.$this->embeddedfiles[$filename]['a'].' >> >>';
                                } elseif ($pl['txt'][0] == '*') {
                                    // embedded generic file
                                    $filename = basename(substr($pl['txt'], 1));
                                    $jsa = 'var D=event.target.doc;var MyData=D.dataObjects;for (var i in MyData) if (MyData[i].path=="'.$filename.'") D.exportDataObject( { cName : MyData[i].name, nLaunch : 2});';
                                    $annots .= ' /A << /S /JavaScript /JS '.$this->_textstring($jsa, $annot_obj_id).'>>';
                                } else {
                                    // external URI link
                                    $annots .= ' /A <</S /URI /URI '.$this->_datastring($this->unhtmlentities($pl['txt']), $annot_obj_id).'>>';
                                }
                            } elseif (isset($this->links[$pl['txt']])) {
                                // internal link ID
                                $l = $this->links[$pl['txt']];
                                if (isset($this->page_obj_id[($l[0])])) {
                                    $annots .= sprintf(' /Dest [%u 0 R /XYZ 0 %F null]', $this->page_obj_id[($l[0])], ($this->pagedim[$l[0]]['h'] - ($l[1] * $this->k)));
                                }
                            }
                            $hmodes = array('N', 'I', 'O', 'P');
                            if (isset($pl['opt']['h']) AND in_array($pl['opt']['h'], $hmodes)) {
                                $annots .= ' /H /'.$pl['opt']['h'];
                            } else {
                                $annots .= ' /H /I';
                            }
                            //$annots .= ' /PA ';
                            //$annots .= ' /Quadpoints ';
                            break;
                        }
                        case 'freetext': {
                            if (isset($pl['opt']['da']) AND !empty($pl['opt']['da'])) {
                                $annots .= ' /DA ('.$pl['opt']['da'].')';
                            }
                            if (isset($pl['opt']['q']) AND ($pl['opt']['q'] >= 0) AND ($pl['opt']['q'] <= 2)) {
                                $annots .= ' /Q '.intval($pl['opt']['q']);
                            }
                            if (isset($pl['opt']['rc'])) {
                                $annots .= ' /RC '.$this->_textstring($pl['opt']['rc'], $annot_obj_id);
                            }
                            if (isset($pl['opt']['ds'])) {
                                $annots .= ' /DS '.$this->_textstring($pl['opt']['ds'], $annot_obj_id);
                            }
                            if (isset($pl['opt']['cl']) AND is_array($pl['opt']['cl'])) {
                                $annots .= ' /CL [';
                                foreach ($pl['opt']['cl'] as $cl) {
                                    $annots .= sprintf('%F ', $cl * $this->k);
                                }
                                $annots .= ']';
                            }
                            $tfit = array('FreeText', 'FreeTextCallout', 'FreeTextTypeWriter');
                            if (isset($pl['opt']['it']) AND in_array($pl['opt']['it'], $tfit)) {
                                $annots .= ' /IT /'.$pl['opt']['it'];
                            }
                            if (isset($pl['opt']['rd']) AND is_array($pl['opt']['rd'])) {
                                $l = $pl['opt']['rd'][0] * $this->k;
                                $r = $pl['opt']['rd'][1] * $this->k;
                                $t = $pl['opt']['rd'][2] * $this->k;
                                $b = $pl['opt']['rd'][3] * $this->k;
                                $annots .= ' /RD ['.sprintf('%F %F %F %F', $l, $r, $t, $b).']';
                            }
                            if (isset($pl['opt']['le']) AND in_array($pl['opt']['le'], $lineendings)) {
                                $annots .= ' /LE /'.$pl['opt']['le'];
                            }
                            break;
                        }
                        case 'fileattachment': {
                            if ($this->pdfa_mode) {
                                // embedded files are not allowed in PDF/A mode
                                break;
                            }
                            if (!isset($pl['opt']['fs'])) {
                                break;
                            }
                            $filename = basename($pl['opt']['fs']);
                            if (isset($this->embeddedfiles[$filename]['f'])) {
                                $annots .= ' /FS '.$this->embeddedfiles[$filename]['f'].' 0 R';
                                $iconsapp = array('Graph', 'Paperclip', 'PushPin', 'Tag');
                                if (isset($pl['opt']['name']) AND in_array($pl['opt']['name'], $iconsapp)) {
                                    $annots .= ' /Name /'.$pl['opt']['name'];
                                } else {
                                    $annots .= ' /Name /PushPin';
                                }
                                // index (zero-based) of the annotation in the Annots array of this page
                                $this->embeddedfiles[$filename]['a'] = $key;
                            }
                            break;
                        }
                        case 'sound': {
                            if (!isset($pl['opt']['fs'])) {
                                break;
                            }
                            $filename = basename($pl['opt']['fs']);
                            if (isset($this->embeddedfiles[$filename]['f'])) {
                                // ... TO BE COMPLETED ...
                                // /R /C /B /E /CO /CP
                                $annots .= ' /Sound '.$this->embeddedfiles[$filename]['f'].' 0 R';
                                $iconsapp = array('Speaker', 'Mic');
                                if (isset($pl['opt']['name']) AND in_array($pl['opt']['name'], $iconsapp)) {
                                    $annots .= ' /Name /'.$pl['opt']['name'];
                                } else {
                                    $annots .= ' /Name /Speaker';
                                }
                            }
                            break;
                        }
                        case 'widget': {
                            $hmode = array('N', 'I', 'O', 'P', 'T');
                            if (isset($pl['opt']['h']) AND in_array($pl['opt']['h'], $hmode)) {
                                $annots .= ' /H /'.$pl['opt']['h'];
                            }
                            if (isset($pl['opt']['mk']) AND (is_array($pl['opt']['mk'])) AND !empty($pl['opt']['mk'])) {
                                $annots .= ' /MK <<';
                                if (isset($pl['opt']['mk']['r'])) {
                                    $annots .= ' /R '.$pl['opt']['mk']['r'];
                                }
                                if (isset($pl['opt']['mk']['bc']) AND (is_array($pl['opt']['mk']['bc']))) {
                                    $annots .= ' /BC '.TCPDF_COLORS::getColorStringFromArray($pl['opt']['mk']['bc']);
                                }
                                if (isset($pl['opt']['mk']['bg']) AND (is_array($pl['opt']['mk']['bg']))) {
                                    $annots .= ' /BG '.TCPDF_COLORS::getColorStringFromArray($pl['opt']['mk']['bg']);
                                }
                                if (isset($pl['opt']['mk']['ca'])) {
                                    $annots .= ' /CA '.$pl['opt']['mk']['ca'];
                                }
                                if (isset($pl['opt']['mk']['rc'])) {
                                    $annots .= ' /RC '.$pl['opt']['mk']['rc'];
                                }
                                if (isset($pl['opt']['mk']['ac'])) {
                                    $annots .= ' /AC '.$pl['opt']['mk']['ac'];
                                }
                                if (isset($pl['opt']['mk']['i'])) {
                                    $info = $this->getImageBuffer($pl['opt']['mk']['i']);
                                    if ($info !== false) {
                                        $annots .= ' /I '.$info['n'].' 0 R';
                                    }
                                }
                                if (isset($pl['opt']['mk']['ri'])) {
                                    $info = $this->getImageBuffer($pl['opt']['mk']['ri']);
                                    if ($info !== false) {
                                        $annots .= ' /RI '.$info['n'].' 0 R';
                                    }
                                }
                                if (isset($pl['opt']['mk']['ix'])) {
                                    $info = $this->getImageBuffer($pl['opt']['mk']['ix']);
                                    if ($info !== false) {
                                        $annots .= ' /IX '.$info['n'].' 0 R';
                                    }
                                }
                                if (isset($pl['opt']['mk']['if']) AND (is_array($pl['opt']['mk']['if'])) AND !empty($pl['opt']['mk']['if'])) {
                                    $annots .= ' /IF <<';
                                    $if_sw = array('A', 'B', 'S', 'N');
                                    if (isset($pl['opt']['mk']['if']['sw']) AND in_array($pl['opt']['mk']['if']['sw'], $if_sw)) {
                                        $annots .= ' /SW /'.$pl['opt']['mk']['if']['sw'];
                                    }
                                    $if_s = array('A', 'P');
                                    if (isset($pl['opt']['mk']['if']['s']) AND in_array($pl['opt']['mk']['if']['s'], $if_s)) {
                                        $annots .= ' /S /'.$pl['opt']['mk']['if']['s'];
                                    }
                                    if (isset($pl['opt']['mk']['if']['a']) AND (is_array($pl['opt']['mk']['if']['a'])) AND !empty($pl['opt']['mk']['if']['a'])) {
                                        $annots .= sprintf(' /A [%F %F]', $pl['opt']['mk']['if']['a'][0], $pl['opt']['mk']['if']['a'][1]);
                                    }
                                    if (isset($pl['opt']['mk']['if']['fb']) AND ($pl['opt']['mk']['if']['fb'])) {
                                        $annots .= ' /FB true';
                                    }
                                    $annots .= '>>';
                                }
                                if (isset($pl['opt']['mk']['tp']) AND ($pl['opt']['mk']['tp'] >= 0) AND ($pl['opt']['mk']['tp'] <= 6)) {
                                    $annots .= ' /TP '.intval($pl['opt']['mk']['tp']);
                                }
                                $annots .= '>>';
                            } // end MK
                            // --- Entries for field dictionaries ---
                            if (isset($this->radiobutton_groups[$n][$pl['txt']])) {
                                // set parent
                                $annots .= ' /Parent '.$this->radiobutton_groups[$n][$pl['txt']].' 0 R';
                            }
                            if (isset($pl['opt']['t']) AND is_string($pl['opt']['t'])) {
                                $annots .= ' /T '.$this->_datastring($pl['opt']['t'], $annot_obj_id);
                            }
                            if (isset($pl['opt']['tu']) AND is_string($pl['opt']['tu'])) {
                                $annots .= ' /TU '.$this->_datastring($pl['opt']['tu'], $annot_obj_id);
                            }
                            if (isset($pl['opt']['tm']) AND is_string($pl['opt']['tm'])) {
                                $annots .= ' /TM '.$this->_datastring($pl['opt']['tm'], $annot_obj_id);
                            }
                            if (isset($pl['opt']['ff'])) {
                                if (is_array($pl['opt']['ff'])) {
                                    // array of bit settings
                                    $flag = 0;
                                    foreach($pl['opt']['ff'] as $val) {
                                        $flag += 1 << ($val - 1);
                                    }
                                } else {
                                    $flag = intval($pl['opt']['ff']);
                                }
                                $annots .= ' /Ff '.$flag;
                            }
                            if (isset($pl['opt']['maxlen'])) {
                                $annots .= ' /MaxLen '.intval($pl['opt']['maxlen']);
                            }
                            if (isset($pl['opt']['v'])) {
                                $annots .= ' /V';
                                if (is_array($pl['opt']['v'])) {
                                    foreach ($pl['opt']['v'] AS $optval) {
                                        if (is_float($optval)) {
                                            $optval = sprintf('%F', $optval);
                                        }
                                        $annots .= ' '.$optval;
                                    }
                                } else {
                                    $annots .= ' '.$this->_textstring($pl['opt']['v'], $annot_obj_id);
                                }
                            }
                            if (isset($pl['opt']['dv'])) {
                                $annots .= ' /DV';
                                if (is_array($pl['opt']['dv'])) {
                                    foreach ($pl['opt']['dv'] AS $optval) {
                                        if (is_float($optval)) {
                                            $optval = sprintf('%F', $optval);
                                        }
                                        $annots .= ' '.$optval;
                                    }
                                } else {
                                    $annots .= ' '.$this->_textstring($pl['opt']['dv'], $annot_obj_id);
                                }
                            }
                            if (isset($pl['opt']['rv'])) {
                                $annots .= ' /RV';
                                if (is_array($pl['opt']['rv'])) {
                                    foreach ($pl['opt']['rv'] AS $optval) {
                                        if (is_float($optval)) {
                                            $optval = sprintf('%F', $optval);
                                        }
                                        $annots .= ' '.$optval;
                                    }
                                } else {
                                    $annots .= ' '.$this->_textstring($pl['opt']['rv'], $annot_obj_id);
                                }
                            }
                            if (isset($pl['opt']['a']) AND !empty($pl['opt']['a'])) {
                                $annots .= ' /A << '.$pl['opt']['a'].' >>';
                            }
                            if (isset($pl['opt']['aa']) AND !empty($pl['opt']['aa'])) {
                                $annots .= ' /AA << '.$pl['opt']['aa'].' >>';
                            }
                            if (isset($pl['opt']['da']) AND !empty($pl['opt']['da'])) {
                                $annots .= ' /DA ('.$pl['opt']['da'].')';
                            }
                            if (isset($pl['opt']['q']) AND ($pl['opt']['q'] >= 0) AND ($pl['opt']['q'] <= 2)) {
                                $annots .= ' /Q '.intval($pl['opt']['q']);
                            }
                            if (isset($pl['opt']['opt']) AND (is_array($pl['opt']['opt'])) AND !empty($pl['opt']['opt'])) {
                                $annots .= ' /Opt [';
                                foreach($pl['opt']['opt'] AS $copt) {
                                    if (is_array($copt)) {
                                        $annots .= ' ['.$this->_textstring($copt[0], $annot_obj_id).' '.$this->_textstring($copt[1], $annot_obj_id).']';
                                    } else {
                                        $annots .= ' '.$this->_textstring($copt, $annot_obj_id);
                                    }
                                }
                                $annots .= ']';
                            }
                            if (isset($pl['opt']['ti'])) {
                                $annots .= ' /TI '.intval($pl['opt']['ti']);
                            }
                            if (isset($pl['opt']['i']) AND (is_array($pl['opt']['i'])) AND !empty($pl['opt']['i'])) {
                                $annots .= ' /I [';
                                foreach($pl['opt']['i'] AS $copt) {
                                    $annots .= intval($copt).' ';
                                }
                                $annots .= ']';
                            }
                            break;
                        }
                        // case 'line':
                        // case 'square':
                        // case 'circle':
                        // case 'polygon':
                        // case 'polyline':
                        // case 'highlight':
                        // case 'underline':
                        // case 'squiggly':
                        // case 'strikeout':
                        // case 'stamp':
                        // case 'caret':
                        // case 'ink':
                        // case 'popup':
                        // case 'movie':
                        // case 'screen':
                        // case 'printermark':
                        // case 'trapnet':
                        // case 'watermark':
                        // case '3d':
                        default:
                            break;
                    }
                    $annots .= '>>';
                    // create new annotation object
                    $this->_out($this->_getobj($annot_obj_id)."\n".$annots."\n".'endobj');
                    if ($formfield AND !isset($this->radiobutton_groups[$n][$pl['txt']])) {
                        // store reference of form object
                        $this->form_obj_id[] = $annot_obj_id;
                    }
                }
            }
        } // end for each page
    }

    /**
     * Output Catalog.
     * @return int object id
     * @protected
     */
    protected function _putcatalog() {
        // put XMP
        ///$xmpobj = $this->_putXMP();
        ///edit by kuangjun
        if(!$this->incremental_updates_sign_flag_tmp){
            $xmpobj = $this->_putXMP();
        }
        ///end of edit 
        // if required, add standard sRGB_IEC61966-2.1 blackscaled ICC colour profile
        if ($this->pdfa_mode OR $this->force_srgb) {
            $iccobj = $this->_newobj();
            $icc = file_get_contents(dirname(__FILE__).'/include/sRGB.icc');
            $filter = '';
            if ($this->compress) {
                $filter = ' /Filter /FlateDecode';
                $icc = gzcompress($icc);
            }
            $icc = $this->_getrawstream($icc);
            $this->_out('<</N 3 '.$filter.'/Length '.strlen($icc).'>> stream'."\n".$icc."\n".'endstream'."\n".'endobj');
        }
        // start catalog
        ///$oid = $this->_newobj();
        ///edit by kuangjun 
        if($this->incremental_updates_sign_flag_tmp){
            $oid = $this->pre_objid_catalog;
            $this->_out($this->_getobj($oid));
        }else{
            $oid = $this->_newobj();
        }
        ///end of edit
        $out = '<< /Type /Catalog';
        $out .= ' /Version /'.$this->PDFVersion;
        //$out .= ' /Extensions <<>>';
        $out .= ' /Pages 1 0 R';
        //$out .= ' /PageLabels ' //...;
        $out .= ' /Names <<';
        if ((!$this->pdfa_mode) AND !empty($this->n_js)) {
            $out .= ' /JavaScript '.$this->n_js;
        }
        if (!empty($this->efnames)) {
            $out .= ' /EmbeddedFiles <</Names [';
            foreach ($this->efnames AS $fn => $fref) {
                $out .= ' '.$this->_datastring($fn).' '.$fref;
            }
            $out .= ' ]>>';
        }
        $out .= ' >>';
        if (!empty($this->dests)) {
            $out .= ' /Dests '.($this->n_dests).' 0 R';
        }
        $out .= $this->_putviewerpreferences();
        if (isset($this->LayoutMode) AND (!TCPDF_STATIC::empty_string($this->LayoutMode))) {
            $out .= ' /PageLayout /'.$this->LayoutMode;
        }
        if (isset($this->PageMode) AND (!TCPDF_STATIC::empty_string($this->PageMode))) {
            $out .= ' /PageMode /'.$this->PageMode;
        }
        if (count($this->outlines) > 0) {
            $out .= ' /Outlines '.$this->OutlineRoot.' 0 R';
            $out .= ' /PageMode /UseOutlines';
        }
        //$out .= ' /Threads []';
        if ($this->ZoomMode == 'fullpage') {
            $out .= ' /OpenAction ['.$this->page_obj_id[1].' 0 R /Fit]';
        } elseif ($this->ZoomMode == 'fullwidth') {
            $out .= ' /OpenAction ['.$this->page_obj_id[1].' 0 R /FitH null]';
        } elseif ($this->ZoomMode == 'real') {
            $out .= ' /OpenAction ['.$this->page_obj_id[1].' 0 R /XYZ null null 1]';
        } elseif (!is_string($this->ZoomMode)) {
            $out .= sprintf(' /OpenAction ['.$this->page_obj_id[1].' 0 R /XYZ null null %F]', ($this->ZoomMode / 100));
        }
        //$out .= ' /AA <<>>';
        //$out .= ' /URI <<>>';
        ///$out .= ' /Metadata '.$xmpobj.' 0 R';
        ///edit by kuangjun
        if($this->incremental_updates_sign_flag_tmp){
            $out .= ' /Metadata '.$this->pre_xmlobj .' 0 R';
        }else{
            $out .= ' /Metadata '.$xmpobj.' 0 R';
            $this->pre_xmlobj  = $xmpobj;
        }
        ///end of edit
        //$out .= ' /StructTreeRoot <<>>';
        //$out .= ' /MarkInfo <<>>';
        if (isset($this->l['a_meta_language'])) {
            $out .= ' /Lang '.$this->_textstring($this->l['a_meta_language'], $oid);
        }
        //$out .= ' /SpiderInfo <<>>';
        // set OutputIntent to sRGB IEC61966-2.1 if required
        if ($this->pdfa_mode OR $this->force_srgb) {
            $out .= ' /OutputIntents [<<';
            $out .= ' /Type /OutputIntent';
            $out .= ' /S /GTS_PDFA1';
            $out .= ' /OutputCondition '.$this->_textstring('sRGB IEC61966-2.1', $oid);
            $out .= ' /OutputConditionIdentifier '.$this->_textstring('sRGB IEC61966-2.1', $oid);
            $out .= ' /RegistryName '.$this->_textstring('http://www.color.org', $oid);
            $out .= ' /Info '.$this->_textstring('sRGB IEC61966-2.1', $oid);
            $out .= ' /DestOutputProfile '.$iccobj.' 0 R';
            $out .= ' >>]';
        }
        //$out .= ' /PieceInfo <<>>';
        if (!empty($this->pdflayers)) {
            $lyrobjs = '';
            $lyrobjs_print = '';
            $lyrobjs_view = '';
            foreach ($this->pdflayers as $layer) {
                $lyrobjs .= ' '.$layer['objid'].' 0 R';
                if ($layer['print']) {
                    $lyrobjs_print .= ' '.$layer['objid'].' 0 R';
                }
                if ($layer['view']) {
                    $lyrobjs_view .= ' '.$layer['objid'].' 0 R';
                }
            }
            $out .= ' /OCProperties << /OCGs ['.$lyrobjs.']';
            $out .= ' /D <<';
            $out .= ' /Name '.$this->_textstring('Layers', $oid);
            $out .= ' /Creator '.$this->_textstring('TCPDF', $oid);
            $out .= ' /BaseState /ON';
            $out .= ' /ON ['.$lyrobjs_print.']';
            $out .= ' /OFF ['.$lyrobjs_view.']';
            $out .= ' /Intent /View';
            $out .= ' /AS [';
            $out .= ' << /Event /Print /OCGs ['.$lyrobjs.'] /Category [/Print] >>';
            $out .= ' << /Event /View /OCGs ['.$lyrobjs.'] /Category [/View] >>';
            $out .= ' ]';
            $out .= ' /Order ['.$lyrobjs.']';
            $out .= ' /ListMode /AllPages';
            //$out .= ' /RBGroups ['..']';
            //$out .= ' /Locked ['..']';
            $out .= ' >>';
            $out .= ' >>';
        }
        // AcroForm
        if (!empty($this->form_obj_id)
            OR ($this->sign AND isset($this->signature_data['cert_type']))
            OR !empty($this->empty_signature_appearance)
            OR ($this->incremental_updates_sign_flag_tmp) //add by kuangjun
            ) {
            $out .= ' /AcroForm <<';
            $objrefs = '';
            if ($this->sign AND isset($this->signature_data['cert_type'])) {
                // set reference for signature object
                $objrefs .= $this->sig_obj_id.' 0 R';
            }
            ///add by kuangjun
            if($this->incremental_updates_sign_flag_tmp){
                foreach($this->sig_obj_id_add_array as $key=>$value){
                    if($key<=$this->incremental_sign_index){
                        if(isset($this->signature_data_add['cert_type'])){
                            $objrefs .= ' '.$value.' 0 R';
                        }
                    }
                }
            }
            ///end of add
            if (!empty($this->empty_signature_appearance)) {
                foreach ($this->empty_signature_appearance as $esa) {
                    // set reference for empty signature objects
                    $objrefs .= ' '.$esa['objid'].' 0 R';
                }
            }
            if (!empty($this->form_obj_id)) {
                foreach($this->form_obj_id as $objid) {
                    $objrefs .= ' '.$objid.' 0 R';
                }
            }
            $out .= ' /Fields ['.$objrefs.']';
            // It's better to turn off this value and set the appearance stream for each annotation (/AP) to avoid conflicts with signature fields.
            $out .= ' /NeedAppearances false';
            ///if ($this->sign AND isset($this->signature_data['cert_type'])) {
            ///    if ($this->signature_data['cert_type'] > 0) {
            ///        $out .= ' /SigFlags 3';
            ///    } else {
            ///        $out .= ' /SigFlags 1';
            ///    }
            ///}
            ///edit by kuangjun
            if($this->incremental_updates_sign_flag_tmp){
                if ($this->signature_data_add['cert_type'] > 0) {
                    $out .= ' /SigFlags 3';
                } else {
                    $out .= ' /SigFlags 1';
                }
            }else{
                if ($this->sign AND isset($this->signature_data['cert_type'])) {
                    if ($this->signature_data['cert_type'] > 0) {
                        $out .= ' /SigFlags 3';
                    } else {
                        $out .= ' /SigFlags 1';
                    }
                }
            }
            //$out .= ' /CO ';
            if (isset($this->annotation_fonts) AND !empty($this->annotation_fonts)) {
                $out .= ' /DR <<';
                $out .= ' /Font <<';
                foreach ($this->annotation_fonts as $fontkey => $fontid) {
                    $out .= ' /F'.$fontid.' '.$this->font_obj_ids[$fontkey].' 0 R';
                }
                $out .= ' >> >>';
            }
            $font = $this->getFontBuffer('helvetica');
            $out .= ' /DA (/F'.$font['i'].' 0 Tf 0 g)';
            $out .= ' /Q '.(($this->rtl)?'2':'0');
            //$out .= ' /XFA ';
            $out .= ' >>';
            // signatures
            ///if ($this->sign AND isset($this->signature_data['cert_type'])) {
            ///    if ($this->signature_data['cert_type'] > 0) {
            ///        $out .= ' /Perms << /DocMDP '.($this->sig_obj_id + 1).' 0 R >>';
            ///    } else {
            ///        $out .= ' /Perms << /UR3 '.($this->sig_obj_id + 1).' 0 R >>';
            ///    }
            ///}
            ///edit by kuangjun 
            if($this->incremental_updates_sign_flag_tmp){
                $out .= ' /Perms <<';
                if ($this->sign AND isset($this->signature_data['cert_type'])) {
                    if ($this->signature_data['cert_type'] > 0) {
                        $out .= ' /DocMDP '.($this->sig_obj_id + 1).' 0 R';
                    } else {
                        $out .= ' /UR3 '.($this->sig_obj_id + 1).' 0 R';
                    }
                    // if ($this->signature_data_add['cert_type'] > 0) {
                       //$out .= ' /DocMDP '.($this->sig_obj_id_add + 1).' 0 R';
                    // } else {
                       //$out .= ' /UR3 '.($this->sig_obj_id_add + 1).' 0 R';
                    // }
                }else{
                    if ($this->signature_data_add['cert_type'] > 0) {
                       $out .= ' /DocMDP '.($this->sig_obj_id_add + 1).' 0 R';
                    } else {
                       $out .= ' /UR3 '.($this->sig_obj_id_add + 1).' 0 R';
                    }
                }
                $out.=' >>';
            }else{
                if ($this->sign AND isset($this->signature_data['cert_type'])) {
                    if ($this->signature_data['cert_type'] > 0) {
                        $out .= ' /Perms << /DocMDP '.($this->sig_obj_id + 1).' 0 R >>';
                    } else {
                        $out .= ' /Perms << /UR3 '.($this->sig_obj_id + 1).' 0 R >>';
                    }
                }
            }
            ///end of edit
        }
        //$out .= ' /Legal <<>>';
        //$out .= ' /Requirements []';
        //$out .= ' /Collection <<>>';
        //$out .= ' /NeedsRendering true';
        $out .= ' >>';
        $out .= "\n".'endobj';
        $this->_out($out);
        return $oid;
    }
    /**
     * 重写enddoc函数，增加一行，获取原始未签名之前的bufferlength 赋值给pre_prev
     * 
     **/
    protected function _enddoc() {
        if (isset($this->CurrentFont['fontkey']) AND isset($this->CurrentFont['subsetchars'])) {
            // save subset chars of the previous font
            $this->setFontSubBuffer($this->CurrentFont['fontkey'], 'subsetchars', $this->CurrentFont['subsetchars']);
        }
        $this->state = 1;
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        // empty signature fields
        if (!empty($this->empty_signature_appearance)) {
            foreach ($this->empty_signature_appearance as $key => $esa) {
                // widget annotation for empty signature
                $out = $this->_getobj($esa['objid'])."\n";
                $out .= '<< /Type /Annot';
                $out .= ' /Subtype /Widget';
                $out .= ' /Rect ['.$esa['rect'].']';
                $out .= ' /P '.$this->page_obj_id[($esa['page'])].' 0 R'; // link to signature appearance page
                $out .= ' /F 4';
                $out .= ' /FT /Sig';
                $signame = $esa['name'].sprintf(' [%03d]', ($key + 1));
                $out .= ' /T '.$this->_textstring($signame, $esa['objid']);
                $out .= ' /Ff 0';
                $out .= ' >>';
                $out .= "\n".'endobj';
                $this->_out($out);
            }
        }
        // Signature
        if ($this->sign AND isset($this->signature_data['cert_type'])) {
            // widget annotation for signature
            $out = $this->_getobj($this->sig_obj_id)."\n";
            $out .= '<< /Type /Annot';
            $out .= ' /Subtype /Widget';
            $out .= ' /Rect ['.$this->signature_appearance['rect'].']';
            $out .= ' /P '.$this->page_obj_id[($this->signature_appearance['page'])].' 0 R'; // link to signature appearance page
            $out .= ' /F 4';
            $out .= ' /FT /Sig';
            $out .= ' /T '.$this->_textstring($this->signature_appearance['name'], $this->sig_obj_id);
            $out .= ' /Ff 0';
            $out .= ' /V '.($this->sig_obj_id + 1).' 0 R';
            $out .= ' >>';
            $out .= "\n".'endobj';
            $this->_out($out);
            // signature
            $this->_putsignature();
        }
        // Info
        $objid_info = $this->_putinfo();
        // Catalog
        $objid_catalog = $this->_putcatalog();
        // Cross-ref
        $o = $this->bufferlen;
        $this->pre_prev = $o; ///add bj kj
        // XREF section
        $this->_out('xref');
        $this->_out('0 '.($this->n + 1));
        $this->_out('0000000000 65535 f ');
        $freegen = ($this->n + 2);
        
        for ($i=1; $i <= $this->n; ++$i) {
            if (!isset($this->offsets[$i]) AND ($i > 1)) {
                $this->_out(sprintf('0000000000 %05d f ', $freegen));
                ++$freegen;
            } else {
                $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
            }
        }
        // TRAILER
        $out = 'trailer'."\n";
        $out .= '<<';
        $out .= ' /Size '.($this->n + 1);
        $out .= ' /Root '.$objid_catalog.' 0 R'; 
        ///add by kuangjun
        $this->pre_objid_catalog = $objid_catalog;
        ///end of add
        $out .= ' /Info '.$objid_info.' 0 R';
        ///add by kuangjun
        $this->pre_objid_info = $objid_info;
        ///end of add
        if ($this->encrypted) {
            $out .= ' /Encrypt '.$this->encryptdata['objid'].' 0 R';
        }
        $out .= ' /ID [ <'.$this->file_id.'> <'.$this->file_id.'> ]';
        $out .= ' >>';
        $this->_out($out);
        $this->_out('startxref');
        $this->_out($o);
        $this->_out('%%EOF');
        $this->state = 3; // end-of-doc
        if ($this->diskcache) {
            // remove temporary files used for images
            foreach ($this->imagekeys as $key) {
                // remove temporary files
                unlink($this->images[$key]);
            }
            foreach ($this->fontkeys as $key) {
                // remove temporary files
                unlink($this->fonts[$key]);
            }
        }
        
    }

    
    /**
     * 重写output方法
     * Send the document to a given destination: string, local file or browser.
     * In the last case, the plug-in may be used (if present) or a download ("Save as" dialog box) may be forced.<br />
     * The method first calls Close() if necessary to terminate the document.
     * @param $name (string) The name of the file when saved. Note that special characters are removed and blanks characters are replaced with the underscore character.
     * @param $dest (string) Destination where to send the document. It can take one of the following values:<ul><li>I: send the file inline to the browser (default). The plug-in is used if available. The name given by name is used when one selects the "Save as" option on the link generating the PDF.</li><li>D: send to the browser and force a file download with the name given by name.</li><li>F: save to a local server file with the name given by name.</li><li>S: return the document as a string (name is ignored).</li><li>FI: equivalent to F + I option</li><li>FD: equivalent to F + D option</li><li>E: return the document as base64 mime multi-part email attachment (RFC 2045)</li></ul>
     * @public
     * @since 1.0
     * @see Close()
     */
    public function Output($name='doc.pdf', $dest='I',$zzflag=true) {
        //临时添加--郑铮
            $this->zzflag=$zzflag;
            $zz_tmp_filename=$name;
        //
        
        //Output PDF to some destination
        //Finish document if necessary
        if ($this->state < 3) {
            $this->Close();
        }
        //Normalize parameters
        if (is_bool($dest)) {
            $dest = $dest ? 'D' : 'F';
        }
        $dest = strtoupper($dest);
        if ($dest{0} != 'F') {
            $name = preg_replace('/[\s]+/', '_', $name);
            $name = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $name);
        }
        if ($this->sign) {
            // *** apply digital signature to the document ***
            // get the document content
            $pdfdoc = $this->getBuffer();
            // remove last newline
            $pdfdoc = substr($pdfdoc, 0, -1);
            // Remove the original buffer
            if (isset($this->diskcache) AND $this->diskcache) {
                // remove buffer file from cache
                unlink($this->buffer);
            }
            unset($this->buffer);
            // remove filler space
            $byterange_string_len = strlen(TCPDF_STATIC::$byterange_string);
            // define the ByteRange
            $byte_range = array();
            $byte_range[0] = 0;
            $byte_range[1] = strpos($pdfdoc, TCPDF_STATIC::$byterange_string) + $byterange_string_len + 10;
            $byte_range[2] = $byte_range[1] + $this->signature_max_length + 2;
            $byte_range[3] = strlen($pdfdoc) - $byte_range[2];
            $pdfdoc = substr($pdfdoc, 0, $byte_range[1]).substr($pdfdoc, $byte_range[2]);
            // replace the ByteRange
            $byterange = sprintf('/ByteRange[0 %u %u %u]', $byte_range[1], $byte_range[2], $byte_range[3]);
            $byterange .= str_repeat(' ', ($byterange_string_len - strlen($byterange)));
            $pdfdoc = str_replace(TCPDF_STATIC::$byterange_string, $byterange, $pdfdoc);
            // write the document to a temporary folder
            $tempdoc = TCPDF_STATIC::getObjFilename('tmppdf');
            $f = fopen($tempdoc, 'wb');
            if (!$f) {
                $this->Error('Unable to create temporary file: '.$tempdoc);
            }
            $pdfdoc_length = strlen($pdfdoc);
            fwrite($f, $pdfdoc, $pdfdoc_length);
            fclose($f);
            // get digital signature via openssl library
            $tempsign = TCPDF_STATIC::getObjFilename('tmpsig');
            if (empty($this->signature_data['extracerts'])) {
                openssl_pkcs7_sign($tempdoc, $tempsign, $this->signature_data['signcert'], array($this->signature_data['privkey'], $this->signature_data['password']), array(), PKCS7_BINARY | PKCS7_DETACHED);
            } else {
                openssl_pkcs7_sign($tempdoc, $tempsign, $this->signature_data['signcert'], array($this->signature_data['privkey'], $this->signature_data['password']), array(), PKCS7_BINARY | PKCS7_DETACHED, $this->signature_data['extracerts']);
            }
            unlink($tempdoc);
            // read signature
            $signature = file_get_contents($tempsign);
            unlink($tempsign);
            // extract signature
            $signature = substr($signature, $pdfdoc_length);
            $signature = substr($signature, (strpos($signature, "%%EOF\n\n------") + 13));
            $tmparr = explode("\n\n", $signature);
            $signature = $tmparr[1];
            unset($tmparr);
            // decode signature
            $signature = base64_decode(trim($signature));
            // convert signature to hex
            $signature = current(unpack('H*', $signature));
            $signature = str_pad($signature, $this->signature_max_length, '0');
            // disable disk caching
            $this->diskcache = false;
            // Add signature to the document
            $this->buffer = substr($pdfdoc, 0, $byte_range[1]).'<'.$signature.'>'.substr($pdfdoc, $byte_range[1]);
            $this->bufferlen = strlen($this->buffer);
            //echo substr($this->buffer, $byte_range[3],40);die;
        }
        ///add by kuangjun
        if ($this->incremental_updates_flag) {
            $this->_atr_incremental_updates();
        }
        ///end of add
        
        switch($dest) {
            case 'I': {
                // Send PDF to the standard output
                if (ob_get_contents()) {
                    $this->Error('Some data has already been output, can\'t send PDF file');
                }
                if (php_sapi_name() != 'cli') {
                    // send output to a browser
                    header('Content-Type: application/pdf');
                    if (headers_sent()) {
                        $this->Error('Some data has already been output to browser, can\'t send PDF file');
                    }
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                    header('Pragma: public');
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                    header('Content-Disposition: inline; filename="'.basename($name).'"');
                    TCPDF_STATIC::sendOutputData($this->getBuffer(), $this->bufferlen);
                } else {
                    echo $this->getBuffer();
                }
                break;
            }
            case 'D': {
                // download PDF as file
                if (ob_get_contents()) {
                    $this->Error('Some data has already been output, can\'t send PDF file');
                }
                header('Content-Description: File Transfer');
                if (headers_sent()) {
                    $this->Error('Some data has already been output to browser, can\'t send PDF file');
                }
                header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                header('Pragma: public');
                header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                // force download dialog
                if (strpos(php_sapi_name(), 'cgi') === false) {
                    header('Content-Type: application/force-download');
                    header('Content-Type: application/octet-stream', false);
                    header('Content-Type: application/download', false);
                    header('Content-Type: application/pdf', false);
                } else {
                    header('Content-Type: application/pdf');
                }
                // use the Content-Disposition header to supply a recommended filename
                header('Content-Disposition: attachment; filename="'.basename($name).'"');
                header('Content-Transfer-Encoding: binary');
                TCPDF_STATIC::sendOutputData($this->getBuffer(), $this->bufferlen);
                break;
            }
            case 'F':
            case 'FI':
            case 'ZS':{//郑铮临时添加，用于生成落地文件，而不输出
            /*
                $f = fopen($name, 'wb');
                if (!$f) {
                    $this->Error('Unable to create output file: '.$name);
                }
                fwrite($f, $this->getBuffer(), $this->bufferlen);
                fclose($f);
             */
             #var_dump($zz_tmp_filename);die;
            file_put_contents($zz_tmp_filename, $this->getBuffer());
            break;
            }
            case 'FD': {
                // save PDF to a local file
                if ($this->diskcache) {
                    copy($this->buffer, $name);
                } else {
                    $f = fopen($name, 'wb');
                    if (!$f) {
                        $this->Error('Unable to create output file: '.$name);
                    }
                    fwrite($f, $this->getBuffer(), $this->bufferlen);
                    fclose($f);
                }
                if ($dest == 'FI') {
                    // send headers to browser
                    header('Content-Type: application/pdf');
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                    header('Pragma: public');
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                    header('Content-Disposition: inline; filename="'.basename($name).'"');
                    TCPDF_STATIC::sendOutputData(file_get_contents($name), filesize($name));
                } elseif ($dest == 'FD') {
                    // send headers to browser
                    if (ob_get_contents()) {
                        $this->Error('Some data has already been output, can\'t send PDF file');
                    }
                    header('Content-Description: File Transfer');
                    if (headers_sent()) {
                        $this->Error('Some data has already been output to browser, can\'t send PDF file');
                    }
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    header('Pragma: public');
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                    // force download dialog
                    if (strpos(php_sapi_name(), 'cgi') === false) {
                        header('Content-Type: application/force-download');
                        header('Content-Type: application/octet-stream', false);
                        header('Content-Type: application/download', false);
                        header('Content-Type: application/pdf', false);
                    } else {
                        header('Content-Type: application/pdf');
                    }
                    // use the Content-Disposition header to supply a recommended filename
                    header('Content-Disposition: attachment; filename="'.basename($name).'"');
                    header('Content-Transfer-Encoding: binary');
                    TCPDF_STATIC::sendOutputData(file_get_contents($name), filesize($name));
                }
                break;
            }
            case 'E': {
                // return PDF as base64 mime multi-part email attachment (RFC 2045)
                $retval = 'Content-Type: application/pdf;'."\r\n";
                $retval .= ' name="'.$name.'"'."\r\n";
                $retval .= 'Content-Transfer-Encoding: base64'."\r\n";
                $retval .= 'Content-Disposition: attachment;'."\r\n";
                $retval .= ' filename="'.$name.'"'."\r\n\r\n";
                $retval .= chunk_split(base64_encode($this->getBuffer()), 76, "\r\n");
                return $retval;
            }
            case 'S': {
                // returns PDF as a string
                return $this->getBuffer();
            }
            default: {
                $this->Error('Incorrect output destination: '.$dest);
            }
        }
        return '';
    }

    /**
     * This method is used to render the page header.
     * It is automatically called by AddPage() and could be overwritten in your own inherited class.
     * @public
     */
    public function Header() {
        if ($this->header_xobjid < 0) {
            // start a new XObject Template
            $this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);
            $headerfont = $this->getHeaderFont();
            $headerdata = $this->getHeaderData();
            $this->y = $this->header_margin;
            if ($this->rtl) {
                $this->x = $this->w - $this->original_rMargin;
            } else {
                $this->x = $this->original_lMargin;
            }
            if (($headerdata['logo']) AND ($headerdata['logo'] != K_BLANK_IMAGE)) {
                $imgtype = TCPDF_IMAGES::getImageFileType(K_PATH_IMAGES.$headerdata['logo']);
                if (($imgtype == 'eps') OR ($imgtype == 'ai')) {
                    $this->ImageEps(K_PATH_IMAGES.$headerdata['logo'], '', '', $headerdata['logo_width']);
                } elseif ($imgtype == 'svg') {
                    $this->ImageSVG(K_PATH_IMAGES.$headerdata['logo'], '', '', $headerdata['logo_width']);
                } else {
                    $this->Image(K_PATH_IMAGES.$headerdata['logo'], '', '', $headerdata['logo_width']);
                }
                $imgy = $this->getImageRBY();
            } else {
                $imgy = $this->y;
            }
            $cell_height = round(($this->cell_height_ratio * $headerfont[2]) / $this->k, 2);
            // set starting margin for text data cell
            if ($this->getRTL()) {
                $header_x = $this->original_rMargin + ($headerdata['logo_width'] * 1.1);
            } else {
                $header_x = $this->original_lMargin + ($headerdata['logo_width'] * 1.1);
            }
            $cw = $this->w - $this->original_lMargin - $this->original_rMargin - ($headerdata['logo_width'] * 1.1);
            $this->SetTextColorArray($this->header_text_color);
            // header title
            $this->SetFont($headerfont[0], 'B', $headerfont[2] + 1);
            $this->SetX($header_x);
            $this->Cell($cw, $cell_height, $headerdata['title'], 0, 1, '', 0, '', 0);
            // header string
            $this->SetFont($headerfont[0], $headerfont[1], $headerfont[2]);
            $this->SetX($header_x);
            $this->MultiCell($cw, $cell_height, $headerdata['string'], 0, 'R', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
            // print an ending header line
            $this->SetLineStyle(array('width' => 0.85 / $this->k, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $headerdata['line_color']));
            $this->SetY((2.835 / $this->k) + max($imgy, $this->y));
            if ($this->rtl) {
                $this->SetX($this->original_rMargin);
            } else {
                $this->SetX($this->original_lMargin);
            }
            $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', '', 0, 'C');
            $this->endTemplate();
        }
        // print header template
        $x = 0;
        $dx = 0;
        if (!$this->header_xobj_autoreset AND $this->booklet AND (($this->page % 2) == 0)) {
            // adjust margins for booklet mode
            $dx = ($this->original_lMargin - $this->original_rMargin);
        }
        if ($this->rtl) {
            $x = $this->w + $dx;
        } else {
            $x = 0 + $dx;
        }
        $this->printTemplate($this->header_xobjid, $x, 0, 0, 0, '', '', false);
        if ($this->header_xobj_autoreset) {
            // reset header xobject template at each page
            $this->header_xobjid = -1;
        }
    }
    
    /**
     * This method is used to render the page footer.
     * It is automatically called by AddPage() and could be overwritten in your own inherited class.
     * @public
     */
    public function Footer() {
        $cur_y = $this->y;
        $this->SetTextColorArray($this->footer_text_color);
        //set style for cell border
        $line_width = (0.85 / $this->k);
        $this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footer_line_color));
        //print document barcode
        $barcode = $this->getBarcode();
        if (!empty($barcode)) {
            $this->Ln($line_width);
            $barcode_width = round(($this->w - $this->original_lMargin - $this->original_rMargin) / 3);
            $style = array(
                    'position' => $this->rtl?'R':'L',
                    'align' => $this->rtl?'R':'L',
                    'stretch' => false,
                    'fitwidth' => true,
                    'cellfitalign' => '',
                    'border' => false,
                    'padding' => 0,
                    'fgcolor' => array(0,0,0),
                    'bgcolor' => false,
                    'text' => false
            );
            $this->write1DBarcode($barcode, 'C128', '', $cur_y + $line_width, '', (($this->footer_margin / 3) - $line_width), 0.3, $style, '');
        }
        $w_page = isset($this->l['w_page']) ? $this->l['w_page'].' ' : '';
        if (empty($this->pagegroups)) {
            $pagenumtxt = $w_page.$this->getAliasNumPage().' / '.$this->getAliasNbPages();
        } else {
            $pagenumtxt = $w_page.$this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
        }
        $this->SetY($cur_y);
        //Print page number
        $this->Cell($this->w - $this->original_lMargin, 0, $pagenumtxt, '', 0, 'C');
    }

    /**
     * Returns the PDF string code to print a cell (rectangular area) with optional borders, background color and character string. The upper-left corner of the cell corresponds to the current position. The text can be aligned or centered. After the call, the current position moves to the right or to the next line. It is possible to put a link on the text.<br />
     * If automatic page breaking is enabled and the cell goes beyond the limit, a page break is done before outputting.
     * @param $w (float) Cell width. If 0, the cell extends up to the right margin.
     * @param $h (float) Cell height. Default value: 0.
     * @param $txt (string) String to print. Default value: empty string.
     * @param $border (mixed) Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)))
     * @param $ln (int) Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right (or left for RTL languages)</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
     * @param $align (string) Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align (default value)</li><li>C: center</li><li>R: right align</li><li>J: justify</li></ul>
     * @param $fill (boolean) Indicates if the cell background must be painted (true) or transparent (false).
     * @param $link (mixed) URL or identifier returned by AddLink().
     * @param $stretch (int) font stretch mode: <ul><li>0 = disabled</li><li>1 = horizontal scaling only if text is larger than cell width</li><li>2 = forced horizontal scaling to fit cell width</li><li>3 = character spacing only if text is larger than cell width</li><li>4 = forced character spacing to fit cell width</li></ul> General font stretching and scaling values will be preserved when possible.
     * @param $ignore_min_height (boolean) if true ignore automatic minimum height value.
     * @param $calign (string) cell vertical alignment relative to the specified Y value. Possible values are:<ul><li>T : cell top</li><li>C : center</li><li>B : cell bottom</li><li>A : font top</li><li>L : font baseline</li><li>D : font bottom</li></ul>
     * @param $valign (string) text vertical alignment inside the cell. Possible values are:<ul><li>T : top</li><li>M : middle</li><li>B : bottom</li></ul>
     * @return string containing cell code
     * @protected
     * @since 1.0
     * @see Cell()
     */
    protected function getCellCode($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M') {
        // replace 'NO-BREAK SPACE' (U+00A0) character with a simple space
        $txt = str_replace(TCPDF_FONTS::unichr(160, $this->isunicode), ' ', $txt);
        $prev_cell_margin = $this->cell_margin;
        $prev_cell_padding = $this->cell_padding;
        $txt = TCPDF_STATIC::removeSHY($txt, $this->isunicode);
        $rs = ''; //string to be returned
        $this->adjustCellPadding($border);
        if (!$ignore_min_height) {
            $min_cell_height = ($this->FontSize * $this->cell_height_ratio) + $this->cell_padding['T'] + $this->cell_padding['B'];
            if ($h < $min_cell_height) {
                $h = $min_cell_height;
            }
        }
        $k = $this->k;
        // check page for no-write regions and adapt page margins if necessary
        list($this->x, $this->y) = $this->checkPageRegions($h, $this->x, $this->y);
        if ($this->rtl) {
            $x = $this->x - $this->cell_margin['R'];
        } else {
            $x = $this->x + $this->cell_margin['L'];
        }
        $y = $this->y + $this->cell_margin['T'];
        $prev_font_stretching = $this->font_stretching;
        $prev_font_spacing = $this->font_spacing;
        // cell vertical alignment
        switch ($calign) {
            case 'A': {
                // font top
                switch ($valign) {
                    case 'T': {
                        // top
                        $y -= $this->cell_padding['T'];
                        break;
                    }
                    case 'B': {
                        // bottom
                        $y -= ($h - $this->cell_padding['B'] - $this->FontAscent - $this->FontDescent);
                        break;
                    }
                    default:
                    case 'C':
                    case 'M': {
                        // center
                        $y -= (($h - $this->FontAscent - $this->FontDescent) / 2);
                        break;
                    }
                }
                break;
            }
            case 'L': {
                // font baseline
                switch ($valign) {
                    case 'T': {
                        // top
                        $y -= ($this->cell_padding['T'] + $this->FontAscent);
                        break;
                    }
                    case 'B': {
                        // bottom
                        $y -= ($h - $this->cell_padding['B'] - $this->FontDescent);
                        break;
                    }
                    default:
                    case 'C':
                    case 'M': {
                        // center
                        $y -= (($h + $this->FontAscent - $this->FontDescent) / 2);
                        break;
                    }
                }
                break;
            }
            case 'D': {
                // font bottom
                switch ($valign) {
                    case 'T': {
                        // top
                        $y -= ($this->cell_padding['T'] + $this->FontAscent + $this->FontDescent);
                        break;
                    }
                    case 'B': {
                        // bottom
                        $y -= ($h - $this->cell_padding['B']);
                        break;
                    }
                    default:
                    case 'C':
                    case 'M': {
                        // center
                        $y -= (($h + $this->FontAscent + $this->FontDescent) / 2);
                        break;
                    }
                }
                break;
            }
            case 'B': {
                // cell bottom
                $y -= $h;
                break;
            }
            case 'C':
            case 'M': {
                // cell center
                $y -= ($h / 2);
                break;
            }
            default:
            case 'T': {
                // cell top
                break;
            }
        }
        // text vertical alignment
        switch ($valign) {
            case 'T': {
                // top
                $yt = $y + $this->cell_padding['T'];
                break;
            }
            case 'B': {
                // bottom
                $yt = $y + $h - $this->cell_padding['B'] - $this->FontAscent - $this->FontDescent;
                break;
            }
            default:
            case 'C':
            case 'M': {
                // center
                $yt = $y + (($h - $this->FontAscent - $this->FontDescent) / 2);
                break;
            }
        }
        $basefonty = $yt + $this->FontAscent;
        if (TCPDF_STATIC::empty_string($w) OR ($w <= 0)) {
            if ($this->rtl) {
                $w = $x - $this->lMargin;
            } else {
                $w = $this->w - $this->rMargin - $x;
            }
        }
        $s = '';
        // fill and borders
        if (is_string($border) AND (strlen($border) == 4)) {
            // full border
            $border = 1;
        }
        if ($fill OR ($border == 1)) {
            if ($fill) {
                $op = ($border == 1) ? 'B' : 'f';
            } else {
                $op = 'S';
            }
            if ($this->rtl) {
                $xk = (($x - $w) * $k);
            } else {
                $xk = ($x * $k);
            }
            $s .= sprintf('%F %F %F %F re %s ', $xk, (($this->h - $y) * $k), ($w * $k), (-$h * $k), $op);
        }
        // draw borders
        $s .= $this->getCellBorder($x, $y, $w, $h, $border);
        if ($txt != '') {
            $txt2 = $txt;
            if ($this->isunicode) {
                if (($this->CurrentFont['type'] == 'core') OR ($this->CurrentFont['type'] == 'TrueType') OR ($this->CurrentFont['type'] == 'Type1')) {
                    $txt2 = TCPDF_FONTS::UTF8ToLatin1($txt2, $this->isunicode, $this->CurrentFont);
                } else {
                    $unicode = TCPDF_FONTS::UTF8StringToArray($txt, $this->isunicode, $this->CurrentFont); // array of UTF-8 unicode values
                    $unicode = TCPDF_FONTS::utf8Bidi($unicode, '', $this->tmprtl, $this->isunicode, $this->CurrentFont);
                    // replace thai chars (if any)
                    if (defined('K_THAI_TOPCHARS') AND (K_THAI_TOPCHARS == true)) {
                        // number of chars
                        $numchars = count($unicode);
                        // po pla, for far, for fan
                        $longtail = array(0x0e1b, 0x0e1d, 0x0e1f);
                        // do chada, to patak
                        $lowtail = array(0x0e0e, 0x0e0f);
                        // mai hun arkad, sara i, sara ii, sara ue, sara uee
                        $upvowel = array(0x0e31, 0x0e34, 0x0e35, 0x0e36, 0x0e37);
                        // mai ek, mai tho, mai tri, mai chattawa, karan
                        $tonemark = array(0x0e48, 0x0e49, 0x0e4a, 0x0e4b, 0x0e4c);
                        // sara u, sara uu, pinthu
                        $lowvowel = array(0x0e38, 0x0e39, 0x0e3a);
                        $output = array();
                        for ($i = 0; $i < $numchars; $i++) {
                            if (($unicode[$i] >= 0x0e00) && ($unicode[$i] <= 0x0e5b)) {
                                $ch0 = $unicode[$i];
                                $ch1 = ($i > 0) ? $unicode[($i - 1)] : 0;
                                $ch2 = ($i > 1) ? $unicode[($i - 2)] : 0;
                                $chn = ($i < ($numchars - 1)) ? $unicode[($i + 1)] : 0;
                                if (in_array($ch0, $tonemark)) {
                                    if ($chn == 0x0e33) {
                                        // sara um
                                        if (in_array($ch1, $longtail)) {
                                            // tonemark at upper left
                                            $output[] = $this->replaceChar($ch0, (0xf713 + $ch0 - 0x0e48));
                                        } else {
                                            // tonemark at upper right (normal position)
                                            $output[] = $ch0;
                                        }
                                    } elseif (in_array($ch1, $longtail) OR (in_array($ch2, $longtail) AND in_array($ch1, $lowvowel))) {
                                        // tonemark at lower left
                                        $output[] = $this->replaceChar($ch0, (0xf705 + $ch0 - 0x0e48));
                                    } elseif (in_array($ch1, $upvowel)) {
                                        if (in_array($ch2, $longtail)) {
                                            // tonemark at upper left
                                            $output[] = $this->replaceChar($ch0, (0xf713 + $ch0 - 0x0e48));
                                        } else {
                                            // tonemark at upper right (normal position)
                                            $output[] = $ch0;
                                        }
                                    } else {
                                        // tonemark at lower right
                                        $output[] = $this->replaceChar($ch0, (0xf70a + $ch0 - 0x0e48));
                                    }
                                } elseif (($ch0 == 0x0e33) AND (in_array($ch1, $longtail) OR (in_array($ch2, $longtail) AND in_array($ch1, $tonemark)))) {
                                    // add lower left nikhahit and sara aa
                                    if ($this->isCharDefined(0xf711) AND $this->isCharDefined(0x0e32)) {
                                        $output[] = 0xf711;
                                        $this->CurrentFont['subsetchars'][0xf711] = true;
                                        $output[] = 0x0e32;
                                        $this->CurrentFont['subsetchars'][0x0e32] = true;
                                    } else {
                                        $output[] = $ch0;
                                    }
                                } elseif (in_array($ch1, $longtail)) {
                                    if ($ch0 == 0x0e31) {
                                        // lower left mai hun arkad
                                        $output[] = $this->replaceChar($ch0, 0xf710);
                                    } elseif (in_array($ch0, $upvowel)) {
                                        // lower left
                                        $output[] = $this->replaceChar($ch0, (0xf701 + $ch0 - 0x0e34));
                                    } elseif ($ch0 == 0x0e47) {
                                        // lower left mai tai koo
                                        $output[] = $this->replaceChar($ch0, 0xf712);
                                    } else {
                                        // normal character
                                        $output[] = $ch0;
                                    }
                                } elseif (in_array($ch1, $lowtail) AND in_array($ch0, $lowvowel)) {
                                    // lower vowel
                                    $output[] = $this->replaceChar($ch0, (0xf718 + $ch0 - 0x0e38));
                                } elseif (($ch0 == 0x0e0d) AND in_array($chn, $lowvowel)) {
                                    // yo ying without lower part
                                    $output[] = $this->replaceChar($ch0, 0xf70f);
                                } elseif (($ch0 == 0x0e10) AND in_array($chn, $lowvowel)) {
                                    // tho santan without lower part
                                    $output[] = $this->replaceChar($ch0, 0xf700);
                                } else {
                                    $output[] = $ch0;
                                }
                            } else {
                                // non-thai character
                                $output[] = $unicode[$i];
                            }
                        }
                        $unicode = $output;
                        // update font subsetchars
                        $this->setFontSubBuffer($this->CurrentFont['fontkey'], 'subsetchars', $this->CurrentFont['subsetchars']);
                    } // end of K_THAI_TOPCHARS
                    $txt2 = TCPDF_FONTS::arrUTF8ToUTF16BE($unicode, false);
                }
            }
            $txt2 = TCPDF_STATIC::_escape($txt2);
            // get current text width (considering general font stretching and spacing)
            $txwidth = $this->GetStringWidth($txt);
            $width = $txwidth;
            // check for stretch mode
            if ($stretch > 0) {
                // calculate ratio between cell width and text width
                if ($width <= 0) {
                    $ratio = 1;
                } else {
                    $ratio = (($w - $this->cell_padding['L'] - $this->cell_padding['R']) / $width);
                }
                // check if stretching is required
                if (($ratio < 1) OR (($ratio > 1) AND (($stretch % 2) == 0))) {
                    // the text will be stretched to fit cell width
                    if ($stretch > 2) {
                        // set new character spacing
                        $this->font_spacing += ($w - $this->cell_padding['L'] - $this->cell_padding['R'] - $width) / (max(($this->GetNumChars($txt) - 1), 1) * ($this->font_stretching / 100));
                    } else {
                        // set new horizontal stretching
                        $this->font_stretching *= $ratio;
                    }
                    // recalculate text width (the text fills the entire cell)
                    $width = $w - $this->cell_padding['L'] - $this->cell_padding['R'];
                    // reset alignment
                    $align = '';
                }
            }
            if ($this->font_stretching != 100) {
                // apply font stretching
                $rs .= sprintf('BT %F Tz ET ', $this->font_stretching);
            }
            if ($this->font_spacing != 0) {
                // increase/decrease font spacing
                $rs .= sprintf('BT %F Tc ET ', ($this->font_spacing * $this->k));
            }
            if ($this->ColorFlag AND ($this->textrendermode < 4)) {
                $s .= 'q '.$this->TextColor.' ';
            }
            // rendering mode
            $s .= sprintf('BT %d Tr %F w ET ', $this->textrendermode, ($this->textstrokewidth * $this->k));
            // count number of spaces
            $ns = substr_count($txt, chr(32));
            // Justification
            $spacewidth = 0;
            if (($align == 'J') AND ($ns > 0)) {
                if ($this->isUnicodeFont()) {
                    // get string width without spaces
                    $width = $this->GetStringWidth(str_replace(' ', '', $txt));
                    // calculate average space width
                    $spacewidth = -1000 * ($w - $width - $this->cell_padding['L'] - $this->cell_padding['R']) / ($ns?$ns:1) / $this->FontSize;
                    if ($this->font_stretching != 100) {
                        // word spacing is affected by stretching
                        $spacewidth /= ($this->font_stretching / 100);
                    }
                    // set word position to be used with TJ operator
                    $txt2 = str_replace(chr(0).chr(32), ') '.sprintf('%F', $spacewidth).' (', $txt2);
                    $unicode_justification = true;
                } else {
                    // get string width
                    $width = $txwidth;
                    // new space width
                    $spacewidth = (($w - $width - $this->cell_padding['L'] - $this->cell_padding['R']) / ($ns?$ns:1)) * $this->k;
                    if ($this->font_stretching != 100) {
                        // word spacing (Tw) is affected by stretching
                        $spacewidth /= ($this->font_stretching / 100);
                    }
                    // set word spacing
                    $rs .= sprintf('BT %F Tw ET ', $spacewidth);
                }
                $width = $w - $this->cell_padding['L'] - $this->cell_padding['R'];
            }
            // replace carriage return characters
            $txt2 = str_replace("\r", ' ', $txt2);
            switch ($align) {
                case 'C': {
                    $dx = ($w - $width) / 2;
                    break;
                }
                case 'R': {
                    if ($this->rtl) {
                        $dx = $this->cell_padding['R'];
                    } else {
                        $dx = $w - $width - $this->cell_padding['R'];
                    }
                    break;
                }
                case 'L': {
                    if ($this->rtl) {
                        $dx = $w - $width - $this->cell_padding['L'];
                    } else {
                        $dx = $this->cell_padding['L'];
                    }
                    break;
                }
                case 'J':
                default: {
                    if ($this->rtl) {
                        $dx = $this->cell_padding['R'];
                    } else {
                        $dx = $this->cell_padding['L'];
                    }
                    break;
                }
            }
            if ($this->rtl) {
                $xdx = $x - $dx - $width;
            } else {
                $xdx = $x + $dx;
            }
            $xdk = $xdx * $k;
            // print text
            $s .= sprintf('BT %F %F Td [(%s)] TJ ET', $xdk, (($this->h - $basefonty) * $k), $txt2);
            if (isset($uniblock)) {
                // print overlapping characters as separate string
                $xshift = 0; // horizontal shift
                $ty = (($this->h - $basefonty + (0.2 * $this->FontSize)) * $k);
                $spw = (($w - $txwidth - $this->cell_padding['L'] - $this->cell_padding['R']) / ($ns?$ns:1));
                foreach ($uniblock as $uk => $uniarr) {
                    if (($uk % 2) == 0) {
                        // x space to skip
                        if ($spacewidth != 0) {
                            // justification shift
                            $xshift += (count(array_keys($uniarr, 32)) * $spw);
                        }
                        $xshift += $this->GetArrStringWidth($uniarr); // + shift justification
                    } else {
                        // character to print
                        $topchr = TCPDF_FONTS::arrUTF8ToUTF16BE($uniarr, false);
                        $topchr = TCPDF_STATIC::_escape($topchr);
                        $s .= sprintf(' BT %F %F Td [(%s)] TJ ET', ($xdk + ($xshift * $k)), $ty, $topchr);
                    }
                }
            }
            if ($this->underline) {
                $s .= ' '.$this->_dounderlinew($xdx, $basefonty + 1, $width);
            }
            if ($this->linethrough) {
                $s .= ' '.$this->_dolinethroughw($xdx, $basefonty, $width);
            }
            if ($this->overline) {
                $s .= ' '.$this->_dooverlinew($xdx, $basefonty, $width);
            }
            if ($this->ColorFlag AND ($this->textrendermode < 4)) {
                $s .= ' Q';
            }
            if ($link) {
                $this->Link($xdx, $yt, $width, ($this->FontAscent + $this->FontDescent), $link, $ns);
            }
        }
        // output cell
        if ($s) {
            // output cell
            $rs .= $s;
            if ($this->font_spacing != 0) {
                // reset font spacing mode
                $rs .= ' BT 0 Tc ET';
            }
            if ($this->font_stretching != 100) {
                // reset font stretching mode
                $rs .= ' BT 100 Tz ET';
            }
        }
        // reset word spacing
        if (!$this->isUnicodeFont() AND ($align == 'J')) {
            $rs .= ' BT 0 Tw ET';
        }
        // reset stretching and spacing
        $this->font_stretching = $prev_font_stretching;
        $this->font_spacing = $prev_font_spacing;
        $this->lasth = $h;
        if ($ln > 0) {
            //Go to the beginning of the next line
            $this->y = $y + $h + $this->cell_margin['B'];
            if ($ln == 1) {
                if ($this->rtl) {
                    $this->x = $this->w - $this->rMargin;
                } else {
                    $this->x = $this->lMargin;
                }
            }
        } else {
            // go left or right by case
            if ($this->rtl) {
                $this->x = $x - $w - $this->cell_margin['L'];
            } else {
                $this->x = $x + $w + $this->cell_margin['R'];
            }
        }
        $gstyles = ''.$this->linestyleWidth.' '.$this->linestyleCap.' '.$this->linestyleJoin.' '.$this->linestyleDash.' '.$this->DrawColor.' '.$this->FillColor."\n";
        $rs = $gstyles.$rs;
        $this->cell_padding = $prev_cell_padding;
        $this->cell_margin = $prev_cell_margin;
        return $rs;
    }
    
} // END OF TCPDF CLASS
