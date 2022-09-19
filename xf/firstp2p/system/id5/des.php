<?php
class DES {
	var $key;
	var $iv; //偏移量	
	
	/**
	 * 支持自定义是否支持iv偏移量
	 */
	function DES($key, $iv = 0) {
		$this->key = $key;
		if ($iv == 0) {
			$this->iv = $key;
		} else {
			$this->iv = $iv;
		}
	}
	
	//加密
	function encrypt($str) {
		$size = mcrypt_get_block_size ( MCRYPT_DES, MCRYPT_MODE_CBC );
		$str = $this->pkcs5Pad ( $str, $size );
		// 支持不启用偏移量
		if($this->nonIv) {
			$data = mcrypt_cbc ( MCRYPT_DES, $this->key, $str, MCRYPT_ENCRYPT);
		} else {
			$data = mcrypt_cbc ( MCRYPT_DES, $this->key, $str, MCRYPT_ENCRYPT, $this->iv );
		}
		
		//$data=strtoupper(bin2hex($data)); //返回大写十六进制字符串
		return base64_encode ( $data );
	}
	
	function encrypt3DES($input) {
	  $size = mcrypt_get_block_size('tripledes', 'ecb');    
              $input = $this->pkcs5Pad($input, $size);          
              $key = $this->key;         
              $td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$iv = pack('H*','0000000000000000');
              mcrypt_generic_init($td, $key, $iv);
              $data = mcrypt_generic($td, $input);          
              mcrypt_generic_deinit($td);      
              mcrypt_module_close($td);         
              $data = base64_encode($data);         
              return $data;     
	}
	
	//加密
	function newEncrypt($input) {
		  $size = mcrypt_get_block_size('des', 'ecb');    
                $input = $this->pkcs5Pad($input, $size);          
                $key = $this->key;         
                $td = mcrypt_module_open('des', '', 'ecb', '');
                $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);      
		 // $iv = pack('H*','0000000000000000');
                mcrypt_generic_init($td, $key, $iv);
                $data = mcrypt_generic($td, $input);          
                mcrypt_generic_deinit($td);      
                mcrypt_module_close($td);         
                $data = base64_encode($data);         
                return $data;     
	}
	
	//解密
	function decrypt($str) {
		$str = base64_decode ( $str );
		//$strBin = $this->hex2bin( strtolower($str));
		$str = mcrypt_cbc ( MCRYPT_DES, $this->key, $str, MCRYPT_DECRYPT, $this->iv );
		$str = $this->pkcs5Unpad ( $str );
		return $str;
	}
        
	function decrypt3DES($encrypted) {
		$encrypted = base64_decode($encrypted);       
		$key =$this->key;          
		$data = mcrypt_decrypt('tripledes', $key, $encrypted, 'ecb');    
		return $data;    
	}
		
	function newDecrypt($encrypted)
        {
            
            $encrypted = base64_decode($encrypted);       
            $key =$this->key;          
            $td = mcrypt_module_open('des','','ecb','');   
            //使用MCRYPT_DES算法,cbc模式                
            $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);            
            $ks = mcrypt_enc_get_key_size($td);               
            @mcrypt_generic_init($td, $key, $iv);         
            //初始处理                
            $decrypted = mdecrypt_generic($td, $encrypted);         
            //解密              
            mcrypt_generic_deinit($td);         
            //结束            
            mcrypt_module_close($td);                 
            $y=$this->pkcs5Unpad($decrypted);          
            return $y;    
        }
        
	function hex2bin($hexData) {
		$binData = "";
		for($i = 0; $i < strlen ( $hexData ); $i= 2) {
			$binData .= chr ( hexdec ( substr ( $hexData, $i, 2 ) ) );
		}
		return $binData;
	}
	
	function pkcs5Pad($text, $blocksize) {
		$pad = $blocksize - (strlen ( $text ) % $blocksize);
		return $text . str_repeat ( chr ( $pad ), $pad );
	}
	
	function pkcs5Unpad($text) {
		$pad = ord ( $text {strlen ( $text ) - 1} );
		if ($pad > strlen ( $text ))
			return false;
		if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)
			return false;
		return substr ( $text, 0, - 1 * $pad );
	}
}