<?php
/* -------------------------------------------------------------------------------------
* 	ID:						Id: class.zanox_api.php
* 	zuletzt geaendert von:	Author: danielsiekiera
* 	Datum:					Date: 21.12.16
*
* 	BigClick GmbH & Co.KG
* 	http://www.big-click.com
*
* 	Copyright (c) 2016 BigClick GmbH & Co.KG
* ----------------------------------------------------------------------------------- 


require_once 'class.zanox_api.php';

try {
    $zanoxDeepLink = new ZanoxDeepLink('LOGIN', 'PASSWORD', 'ADSPACE', 'ADVERTISER');
    echo $zanoxDeepLink->getDeeplink('PRODUCT_URL');

} catch (Exception $e) {
    echo 'Error : ',  $e->getMessage(), "\n";
}

Login:	partner@bmvbw.de
Pass:	PW_zanox2011
ADSPACE: 2161978
ltur ADVERTISER: 3398

*/


class ZanoxDeepLink{
    var $dataConnection;
    var $dataDeepLink;
    var $redirectUrl;
    var $cookieFile;

    function __construct($login, $password, $zanoxAdspace, $zanoxAdvertiser = '') {
        if(is_null($login) || $login == ''){
            throw new Exception('Login can not be null or empty');
        }
        if(is_null($password) || $password == ''){
            throw new Exception('Password can not be null or empty');
        }
        if(is_null($zanoxAdspace) || $zanoxAdspace == ''){
            throw new Exception('Adspace can not be null or empty');
        }

        $this->dataConnection = array(
            /*'loginForm.loginViaUserAndPassword' => 'true',
            'loginForm.userName' => $login,
            'loginForm.password' => $password
            */
            'email' => $login,
			'password' => $password,
			'Login' => ''
        );
        
        
        $this->cookieFile = ABS_PATH_FILE_CACHE."cookieZanoxDeeplink".rand(99, 999999999);

        $this->dataDeepLink = array(
            'sLanguage' => '2',
            'network' => 'zanox',
            'm4n_zone_id' => '',
            'm4n_username' => '',
            'm4n_password' => '',
            'zanox_adspaces' => $zanoxAdspace,
            'zx_advertiser' => $zanoxAdvertiser,
            'zanox_zpar0' => '',
            'zanox_zpar1' => '',
            'url' => '',
            'submit' => 'Deeplink erzeugen'
        );
    }

    public function getDeepLink($url){
        $this->dataDeepLink[url] = $url;

        $postConnection = $this->preparePostFields($this->dataConnection);
        $postDeeplink = $this->preparePostFields($this->dataDeepLink);

        $this->connection($postConnection);
        $this->getToken();
        //echo "got token";
        $deeplink = $this->parseDeeplink($postDeeplink);

		// remove cookie
		unlink($this->cookieFile);

        return $deeplink;
    }

    private function preparePostFields($array) {
        $params = array();

        foreach ($array as $key => $value) {
            $params[] = $key . '=' . urlencode($value);
        }

        return implode('&', $params);
    }

    private function connection($data){
        $ch = curl_init();
        $url = "https://marketplace.zanox.com/login?appid=A5B83584B42A666E5309";
        //$url = "https://auth.zanox.com/connect/login?appid=A5B83584B42A666E5309";
        //$url = "https://auth.zanox.com/login?appid=A5B83584B42A666E5309";
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);            
		curl_setopt($ch, CURLOPT_HEADER, 1);
        
        ob_start();      // prevent any output
        $tmp = curl_exec($ch); // execute the curl command
        $header = curl_getinfo($ch);
        $this->redirectUrl = $header['redirect_url'];
        $html = ob_get_clean();  // stop preventing output
        curl_close ($ch);
        
        unset($ch);

        if($this->redirectUrl == ""){
            throw new Exception("connection: ". $this->getError($tmp));
        }
    }

    private function getToken(){
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);            
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_URL, $this->redirectUrl);
		curl_setopt($ch, CURLOPT_HEADER, 1);

        ob_start();      // prevent any output
        curl_exec ($ch);            
		$header = curl_getinfo($ch);
        $html = ob_get_clean();
        curl_close ($ch);
        
        if (isset($header['redirect_url'])){
            // get next token
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);            
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
	        curl_setopt($ch, CURLOPT_URL, $header['redirect_url']);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			
			ob_start();      // prevent any output
			curl_exec ($ch);            
			$header = curl_getinfo($ch);
	        $html = ob_get_clean();
            curl_close ($ch);
        }            

    }

    private function parseDeeplink($postDeeplink){
        $ch = curl_init();
        //print_r($postDeeplink);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);            
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_URL,"http://toolbox.zanox.com/deeplink/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postDeeplink);

        $buf = curl_exec ($ch);
		$header = curl_getinfo($ch);
        curl_close ($ch);

        $doc = new DOMDocument();
        @$doc->loadHTML($buf);
        $xpath = new DOMXPath($doc);

        //Check if error
        $errors = $xpath->query('//div[@class="error"]');
        if($errors->length > 0){
            throw new Exception("Got error ".$errors->item(0)->textContent);
        }

        //Return the deeplink
        $elements = $xpath->query('//input[@id="result_url"]/@value');
        return $elements->item(0)->textContent;
    }

    private function getError($buf){
        $doc = new DOMDocument();
        $doc->loadHTML($buf);
        $xpath = new DOMXPath($doc);
        $elements = $xpath->query('//div[@class="pageErrorMessage"]');

        return $elements->item(0)->textContent;
    }
}