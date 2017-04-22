$(document).ready(function(){
	var doc=document;
	var deviceWidth=doc.documentElement.clientWidth;
	var setFontSize=function(){
		deviceWidth=doc.documentElement.clientWidth;
		if(deviceWidth>=480){
			deviceWidth=480;
		};
		doc.documentElement.style.fontSize=(deviceWidth*10/320)+'px';
	};
	setFontSize();
	$(window).bind('resize',function(){
		setFontSize();
	});
});