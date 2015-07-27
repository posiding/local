	console.log(getRandomArray('12'));
	function getRandom(n){
		return Math.floor(Math.random()*n);
	}
	function getRandomArray(l){
		var rArray = new Array();
		var baseRand = getRandom(l-1);
		var cArray = new Array();
		for(var m=0;m<l;m++){
			cArray.push(m);
		}
		rArray.push(baseRand);
		cArray[baseRand] = -1;
		for(var i=0;i<7;i++){
			var k = l-2-i;
			var newOne = getRandom(k);
			for(var x in cArray){
				var g = 0;
				if(x<newOne){
					g++;
				}
				if(x == newOne){
					var newG = newOne+g;
					if(cArray[newG] != -1){
						rArray.push(newG);
						cArray[newG]=-1;
					}else{
						var t =1;
						while(cArray[newG+t] == -1){
							++t;
						}
						rArray.push(newG+t);
						cArray[newG+t]=-1;		
					}
				}
			}	
		}
			return rArray;
	}

	function inArray(needle,array,bool){  

		if(typeof needle=="string"||typeof needle=="number"){ 

			for(var i in array){ 

				if(needle===array[i]){ 

					if(bool){ 
						return i; 
					} 
					return true; 
				} 
			} 
			return false;  
		}  
	}	