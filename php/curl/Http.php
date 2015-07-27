<?php
class HttpClient{
	private $ch;
	private $header = array();
	function __construct($timeout = 40){
        #phpinfo();exit;
		$this->ch = curl_init();
		//访问直播室有问题
		//curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; CIBA; .NET CLR 2.0.50727)');//UA
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36');//UA
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);//超时
		//curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->ch, CURLOPT_ENCODING, 'UTF-8');
	}

	function __destruct(){
		curl_close($this->ch);
	}
	
	final public function setHeader(array $header){
		$this->header = array_merge($this->header,$header);
	}

	final public function setProxy($proxy='http://192.168.0.103:3128'){
		//curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
		//curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);//HTTP代理
		//curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);//Socks5代理
		curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
	}

	final public function setReferer($ref=''){
		if($ref != ''){
			curl_setopt($this->ch, CURLOPT_REFERER, $ref);//Referrer
		}
	}

	final public function setCookie($ck=''){
		if($ck != ''){
			curl_setopt($this->ch, CURLOPT_COOKIE, $ck);//Cookie
// 			@unlink($ck);
// 			curl_setopt($c, CURLOPT_COOKIEFILE, $ck);
// 			curl_setopt($c, CURLOPT_COOKIEJAR, $ck);
		}
	}
	/**
	 * 模拟GET
	 * @param string $url url地址
	 * @param bool $header	是否需要返回http头，默认不返回
	 * @param bool $nobody	是否不返回内容，默认返回
	 * @return mixed
	 */
	final public function get($url, $header=false, $nobody=false){
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POST, false);//POST
		curl_setopt($this->ch, CURLOPT_HEADER, $header);//返回Header
		curl_setopt($this->ch, CURLOPT_NOBODY, $nobody);//不需要内容	
		if (!empty($this->header)) {
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header);
		}
		return curl_exec($this->ch);
	}
	/**
	 * 模拟POST
	 * @param string $url	url地址
	 * @param array $data	表单参数
	 * @param bool $header	是否需要返回http头，默认不返回
	 * @param bool $nobody	是否不返回内容，默认返回
	 * @return mixed
	 */
	final public function post($url, $data=array(), $header=false, $nobody=false){
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_HEADER, $header);//返回Header
		curl_setopt($this->ch, CURLOPT_NOBODY, $nobody);//不需要内容
		curl_setopt($this->ch, CURLOPT_POST, true);//POST	
		if (!empty($this->header)) {
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header);
		}
		if (is_array($data)) {
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
		}else{
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
		}
		return curl_exec($this->ch);
	}

	/**
	 * 模拟PUT
	 * @param string $url	url地址
	 * @param array $data	表单参数
	 * @param bool $header	是否需要返回http头，默认不返回
	 * @param bool $nobody	是否不返回内容，默认返回
	 * @return mixed
	 */
	final public function put($url, $data=array(), $header=false, $nobody=false){
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_HEADER, $header);//返回Header
		curl_setopt($this->ch, CURLOPT_NOBODY, $nobody);//不需要内容
		//curl_setopt($this->ch, CURLOPT_PUT, true);//PUT
		$this->header[] = 'X-HTTP-Method-Override: PUT';
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header);
		if(is_array($data)){
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
		}else{
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
		}
		return curl_exec($this->ch);
	}

}