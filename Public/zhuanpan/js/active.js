(function(){
	document.body.addEventListener('touchstart', function(){},false); 
	var a = document.getElementsByTagName('a');
	for(var i = 0; i < a.length; i++) {
	    a[i].addEventListener('touchstart',function(){},false);
    }
})();