<?php

       
        
        class Mkpdf
        {
                
                /*
                        生成pdf文件
                        $file  pdf文件完整路径 /xxxx/sss/file.pdf
                        $con pdf文件内容
                */
                function mk($file, $con)
                {
                         // create new PDF document
                        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        
                        // set document information
                        #$pdf->SetCreator(PDF_CREATOR);
                        #$pdf->SetAuthor('Nicola Asuni');
                        #$pdf->SetTitle('TCPDF Example 038');
                        #$pdf->SetSubject('TCPDF Tutorial');
                        #$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
                        
                        // set default header data
                        #$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 038', PDF_HEADER_STRING);
                        
                        // set header and footer fonts
                        #$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
                        #$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
                        
                        // set default monospaced font
                        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                        
                        //set margins
                        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
                        
                        //set auto page breaks
                        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                        
                        //set image scale factor
                        #$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                        
                        // set some language-dependent strings (optional)
                        if (file_exists(dirname(__FILE__).'/lang/eng.php')) {
                        	require_once(dirname(__FILE__).'/lang/eng.php');
                        	$pdf->setLanguageArray($l);
                        }
                        
                        // ---------------------------------------------------------
                        
                        $fontSimSun = $pdf->addTTFfont(  dirname(__FILE__) . '/simsun.ttf', 'TrueTypeUnicode', '', 32);
                        $fontSimHei = $pdf->addTTFfont(  dirname(__FILE__) . '/simhei.ttf', 'TrueTypeUnicode', '', 32);
                        $fontZhongsong = $pdf->addTTFfont(  dirname(__FILE__) . '/stzhongs.ttf', 'TrueTypeUnicode', '', 32);
                        
                        // add a page
                        $pdf->AddPage();

                        $pdf->SetFont($fontSimSun, '', 12);
                        $pdf->setHtmlVSpace(array('p' => array(0 => array('h' => '', 'n' => 2), 1 => array('h' => 1.3, 'n' => 1))));
                        
                        #$txt = iconv("gb2312//TRANSLIT", 'utf-8',  $con);
                        # $con = str_replace(array('<br>','<br />','<br/>'), "\n", $con);
                        #$con = str_replace("	",  ' ', $con);
                         $con = str_replace("'",  '"', $con); // 单引号换成双引号
                         #$con = str_replace("</span>",  '"', $con); 
                         // 删除所有宽高标签，设置有问题
                         $con = preg_replace('/width=".*?"/is', '', $con); 
                         $con = preg_replace('/height=".*?"/is', '', $con); 
                        #$con = strip_tags($con);
                        #echo $con;
                        $pdf->writeHTML($con, true, false, true, false, '');
                        

                        // ---------------------------------------------------------
                        
                        //Close and output PDF document
                        $pdf->Output($file, 'F');
                }
                
                
                /*  把doc文件名转换成pdf 
                    返回pdf文件名
                */
                function ext($filename)
                {
                        /*
                       $info = pathinfo($filename);
                       return $info['dirname'] .  '/' . $info['filename'] . '.pdf';
                        */
                       
                       return str_replace('.doc', ".pdf",  $filename);
                }
                
        }
        
?>