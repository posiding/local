<?php
/*$time  = microtime();
$fd	   = fopen("./1.txt","a+");
		fwrite($fd,$time."\r\n");
		fclose($fd);*/	
$charge = 100;
$arr  = array(10,100,200,500,1000,2000);
$rate = array(0.1,0.12,0.15,0.2,0.25,0.3);
if(in_array($charge,$arr)){
	echo '返利率是:'.$rate[$arr[array_flip($arr)]];

}else{
	array_push($arr,$charge);
	sort($arr);
	$index = -1;
	array_walk($arr,funtion($val,$key) use(&index,$charge){
		if($val == $charge) {
			$index = $key;	
			}
	});
	echo '返利率是:'.$rate[$index];
}