<?php
class AnalyseApk {
	function getInfo($apkname,$dir_uniq) {
		//echo $apkname."<br/>";
		if (! ini_get ( "display_errors" ))
			ini_set ( "display_errors", 1 );
		error_reporting ( E_ALL & ~ E_NOTICE );
		if (function_exists ( 'date_default_timezone_set' ))
			date_default_timezone_set ( "Asia/Shanghai" );
		header ( "Content-Type:text/html;charset=utf-8" );
		ini_set ( "max_execution_time", 100 );

		/*
		 * 准备工作
		 */
		$file = C('ApkUpDir').$apkname; // 文件名由英文及数据构成，不要是中文
		//echo $file;
		$oldAPK = ''; // 原apk文件
		$_config_product_apktool_count = 5; // 释放APK最大次数，如果有的APK提取不出来，可以增加访值尝试
		$dir = C('ApkPackage').$dir_uniq.'/'; // 表示apk文件在根目录
		
		if (file_exists ( $dir))
			$this->delDirAndFile ( $dir  ); // 删除释放目录
		
		$stringsXML_exists = false;
		
		for($i = 0; $i < 5 && ! $stringsXML_exists; $i ++) {
			$r=exec ( '/usr/local/java/jdk1.6.0_24/bin/java -jar /data/wwwroot/admin.2324.cn/Public/apktool.jar d -f ' . $file . ' ' . $dir  ); // 注释：解压完毕再往下执行
			//exec('unzip '.$file.' '.$dir . 'package');	//解压apk文件
			//exec('7z.exe x -y ' . C('ApkUpZipDir').$apkname . ' -o'. C('ApkUpUnZipDir'));
			//exec('7z.exe x -y ' . $file . ' -o'. $dir);
			//exec ( 'java -jar e:\Apktool\apktool.jar d -f ' . $file . ' ' . $dir); // 注释：解压完毕再往下执行
			$stringsXML_exists = file_exists ( $dir . 'res/values/strings.xml' );
		}
		$this->unzip($file,$dir,'meta-inf');
		// 检查AndroidManifest.xml文件是否存在，如果不存在，则不是合法的APK文件
		if (! file_exists ( $dir . 'AndroidManifest.xml' )) {
				return 'fail';
			//exit ( '不是合法的APK文件，请重新上传！' );
		}
		$AndroidManifestXML = file_get_contents ( $dir . 'AndroidManifest.xml' ); // 读取AndroidManifest.xml
		if (preg_match ( '/package=\"([^\"]*)\"/i', $AndroidManifestXML, $package ))
			$returnVal ['package'] = $package [1]; // 如果有包名，返回到数组
		if (preg_match ( '/versionCode=\"([^\"]*)\"/i', $AndroidManifestXML, $versionCode ))
			$returnVal ['versionCode'] = $versionCode [1]; // 如果有版本代码，返回到数组



		if (preg_match ( '/minSdkVersion=\"([^\"]*)\"/i', $AndroidManifestXML, $minSdkVersion ))
			$returnVal ['minSdkVersion'] = $minSdkVersion [1]; // 如果有最小版本号，返回到数组
		if (preg_match_all ( '/<uses-permission android:name=\"([^\"]*)" \/>/', $AndroidManifestXML, $a )){
			 $abc = $a[1];
			 foreach($abc as $item){
				 $returnVal ['permission'] = $returnVal ['permission'].','.$item;
			 }
		}
			
		if ($stringsXML_exists)
			$stringXML = file_get_contents ( $dir . 'res/values/strings.xml' ); // 如果有strings.xml则读取strings.xml文件
		if (preg_match ( '/versionName=\"([^\"]*)\"/i', $AndroidManifestXML, $ver ))
			$returnVal ['ver'] = $ver [1]; // 如果有版本号，返回到数组
				                                                                                                 
		if ($stringXML != '' && strstr ( $ver [1], '@' )) {
			if (preg_match ( '/^@string\/(.*)/i', $ver [1], $findVer )) {
				if (preg_match ( '/<string name=\"' . $findVer [1] . '\">([^<]*)<\/string>/', $stringXML, $a ))
					$returnVal ['ver'] = $a [1];
			}
		}
		// //////////////////////////////////////////
		if (preg_match ( '/<application[\s\S]*? android:icon="@drawable\/([^"]*)"/i', $AndroidManifestXML, $icon ))
			$returnVal ['thumbimg'] = $icon [1]; // 如果有图标，返回到数组
		if ($stringsXML_exists && preg_match ( '/<application[\s\S]*? android:label="@string\/([^"]*)"/i', $AndroidManifestXML, $label )) {
			if (preg_match ( '/<string name=\"' . $label [1] . '\">([^<]*)<\/string>/', $stringXML, $name )) {
				$returnVal ['name'] = $name [1]; // 如果有产品名称，返回到数组
				/**
				 * 百度：strings.xml
				 * 特殊情况1：<string name="app_name">" 掌上百度 "</string>
				 */
				$returnVal ['name'] = preg_replace ( '/\s|"/', '', $returnVal ['name'] );
			}
		}

		//获取.rsa文件 并且调用接口生成apkkey
		$rsafile = $this->searchRsaFile($dir.'META-INF/');
		$filecontent = file_get_contents($rsafile);
		$content = $this->do_post_request('http://192.168.162.38/applib/webcontent/interface/resolvecert.jsp',$filecontent);
		if(!empty($content)){
			$returnVal['apkkey']=@preg_replace("/^\s+(success\s+)|(\s+$)/",'',$content);
		}
		
		if ($oldAPK != '') { // 重新上传则删除原apk文件和icon.png图片
			if (file_exists ( $dir . $oldAPK ))
				unlink ( $dir . $oldAPK );
			if (file_exists ( $dir . 'icon.png' ))
				unlink ( $dir . 'icon.png' );
		}
		
		// 遍历package/res目录下的目录[drawable|drawable-hdpi|drawable-nodpi|drawable-ldpi|drawable-mdpi]
		// 系统取icon尺寸最大的图标
		$tmpArr [0] = 0;
		$tmpArr [1] = 0;
		$tmpArr [2] = 'drawable';
		$dirs = opendir ( $dir . 'res' );
		while ( ($file = readdir ( $dirs )) ) {
			preg_match ( '/(drawable(-.*?dpi)?)/i', $file, $drawable_folder );
			$iconPath = $dir . 'res/' . $drawable_folder [1] . '/' . $returnVal ['thumbimg'] . '.png';
			if (file_exists ( $iconPath )) {
				$iconInfo = getimagesize ( $iconPath );
				if ($iconInfo [0] > $tmpArr [0] && $iconInfo [1] > $tmpArr [1]) {
					$tmpArr [0] = $iconInfo [0];
					$tmpArr [1] = $iconInfo [1];
					$tmpArr [2] = $drawable_folder [1];
				}
			}
		}
		$returnVal['iconpath']= 'res/' .$tmpArr [2].'/'.$returnVal ['thumbimg'] . '.png';
		closedir ( $dirs );
		return $returnVal;
	}
	function delDirAndFile($dirName) {
		if ($handle = opendir ( $dirName )) {
			while ( false !== ($item = readdir ( $handle )) ) {
				if ($item != "." && $item != "..") {
					if (is_dir ( "$dirName/$item" )) {
						$this->delDirAndFile ( "$dirName/$item" );
					} else {
						if (! unlink ( "$dirName/$item" ))
							return false;
					}
				}
			}
			closedir ( $handle );
			if (rmdir ( $dirName ))
				return true;
		}
	}
	//在指定文件夹以及子文件夹内查找.rsa文件，返回该文件路径
	function searchRsaFile($dirName){
		if($handle=opendir($dirName)){
			while(false!==($item=readdir($handle))){
				if($item!='.' && $item!='..'){
					if(is_dir("$dirName/$item")){
						$this->searchRsaFile("$dirName/$item",$fileName);
					}else{
						//if($item==$fileName){
						if(@preg_match('/(\\cert\.rsa)|(\.rsa)|(\.dsa)/',strtolower($item))){
							closedir($handle);
							return "$dirName/$item";
						}
					}
				}
			}
			closedir($handle);
		}
	}
	//抛出post流
	function do_post_request($url, $fileContents){ 
		$params = array(
        'http' => array
		   (
			   'method' => 'POST',
			   'header'=>"Content-Type: text/xml\r\n".'Content-Length:'.strlen($fileContents)."\r\n"."TimeOut:10000\r\n"."UserAgent:3g-Spider(3g.cn)\r\n",
			   'content' => $fileContents
		   )
		);
		//print_r($params);
		$ctx = @stream_context_create($params);
		$fp = fopen($url, 'rb', false, $ctx);
		//$response = stream_get_contents($fp); 
		$response = file_get_contents($url,$false,$ctx);
		return $response;
	} 
	//解压指定关键词的文件
	function unzip($zipfile,$savepath,$kw){
		//echo '-----123-----<br/>';
		$zip = zip_open($zipfile);
		// var_dump($zip);
		if(!is_dir($savepath)){
			//echo '-------'.$savepath.'--------<br/>';
			@mkdir($savepath,0777);
		}
		while($zip_icerik = zip_read($zip)){
			$zip_dosya = zip_entry_name($zip_icerik);
			if(false === strpos(strtolower($zip_dosya),strtolower($kw))){
				continue;
			}else{
				zip_entry_open($zip, $zip_icerik, "r");
				//echo $zip_dosya."---------<br/>";
				$dirs = explode('/',dirname($zip_dosya));
				$dirpass=$savepath;
				foreach($dirs as $item){
					if(!$item)
						continue;
					else{
						$dirpass.=$item.'/';
						if(!is_dir($dirpass)){
							@mkdir($dirpass,0777);
						}
					}
				}
				$hedef_yol = $savepath.$zip_dosya;
				@touch($hedef_yol);
				$yeni_dosya = @fopen($hedef_yol, 'w+');
				@fwrite($yeni_dosya ,zip_entry_read($zip_icerik,zip_entry_filesize($zip_icerik)));
				@fclose($yeni_dosya); 
				zip_entry_close($zip_icerik);
			}
		}
	}
}
?>