window.addEventListener('load',function(){
	var doc=document;
	var deviceWidth=doc.documentElement.clientWidth;
	var setFontSize=function(){
		deviceWidth=doc.documentElement.clientWidth;
		if(deviceWidth>=480){
			deviceWidth=480;
		};
		doc.documentElement.style.fontSize=Math.floor(deviceWidth*12/320)+'px';
	};
	setFontSize();
	window.addEventListener('resize',setFontSize);
});