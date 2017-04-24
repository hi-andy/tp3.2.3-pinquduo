function lottery(data){

	var user_items = $('.joining-group .item');
	var len=user_items.length;
	var angle_sum=360;
	var angle=angle_sum/len;
	var turns=14;//旋转周期/圈数
	var turnOn=true;


	//玩家信息布局
	var wrap = $('#joining-group');
	function player_layout(){
		var angle_step = 360/len;
		var start_angle = 0;
		var width = wrap.width();
		var oy,ox,r; 
		oy = ox = r = width/2;
		var item_width = user_items.eq(0).width();
		var item_height = user_items.eq(0).height();
		user_items.each(function(i){
			user_items.eq(i).css({
				'left':(ox+r*Math.sin((180-i*angle_step)*Math.PI/180)-item_width/2)/width*100+'%',
				'top':(oy+r*Math.cos((180-i*angle_step)*Math.PI/180)-item_height/2)/width*100+'%',
			});
			user_items.eq(i).css({
				'-webkit-transform': "rotate("+start_angle+"deg)",
				'transform': "rotate("+start_angle+"deg)"
			});
			start_angle += angle_step;
		});
	};
	player_layout();



	//扇形背景
	(function(){
		var cv = $('#fan-bg');
		var ctx = cv[0].getContext('2d');
		var width = cv.attr('width');
		var height = cv.attr('height');
		//原点坐标,半径
		var ox = width/2;
		var oy = height/2;
		var r = width/2;

		var draw_fan = function(x,y,r,start_angle,end_angle,fillColor){
		    ctx.save();
		    ctx.beginPath();
		    ctx.moveTo(x,y);
		    ctx.arc(x,y,r,start_angle*Math.PI/180,end_angle*Math.PI/180,false);
		    ctx.closePath();
		    ctx.fillStyle = fillColor; 
		    ctx.fill();
		    ctx.restore();
		};

		var length = len;
		var angle_step = 360/len;
		var end_angle = angle_step-90-angle_step/2;

		var fillColors = ['#fcdd4f','#fbbf3c'];
		
		//开始绘制
		for(var i = 0;i < length;i++){
			var start_angle = angle_step*i-90-angle_step/2;
			var fill_color = fillColors[i%2];
			draw_fan(ox,oy,r,start_angle,end_angle,fill_color);
			end_angle += angle_step;
		}
	})();


	//弹窗提示
	var dialog_timer = null;
	var dialog = $('#winner-dialog');
	function winner_dialog(winner_index,winner_name){
		if(dialog_timer){
			clearTimeout(dialog_timer);
		};
		dialog.show();
		dialog.find('em').html(winner_name);
		dialog_timer = setTimeout(function(){
			dialog.hide();
			if(n>=winner_num-1){
				is_draw = true;
	  			return;
	  		};
	  		n+=1;
	  		var winner_name = winners[n].username;
			plate(n,winner_name);
		},3000);
	};
	//中奖榜单推送
	var ranking_group = $('#ranking');
	function ranking_push(winner_name){
		var div = document.createElement('div');
		$(div).addClass('item');
		$(div).html('恭喜'+'“'+winner_name+'”'+'抽中了'+'<em>免单一次奖</em>')
		$(div).appendTo(ranking_group);
		setTimeout(function(){
			$(div).addClass('active');
		},500);
	};
	/*
	plate parameters:中奖名单索引，中奖回馈信息(索引按顺时针方向，从user_items下标为0开始)
	*/
  	function plate(index,intro){	
  		var endAngle=angle_sum*turns-index*angle;
      	move('#fan')
        .rotate(endAngle)
		.ease('cubic-bezier(.42, 0, 0.25, 1)')
        .duration('10s')
        .end(function(){
            //alert('恭喜'+intro+'中奖啦！');
            winner_dialog(index,intro);
            ranking_push(intro);
            move('#fan').rotate(angle_sum-index*angle).duration('0').end();
            //turnOn=true;
        }); 
  	};

  	//获取数据
  	var data_ = data;
  	var winner_num = data_.winner_num;
  	var winners = data_.winners;
  	var is_draw = data_.is_draw;
  	var n = 0;


  	//点击抽奖
  	move('#start').el.addEventListener('click',function(){
  		if(is_draw){
  			dialog.find('p').html('已经抽过奖了哦！');
  			dialog.show();
			dialog_timer = setTimeout(function(){
				dialog.hide();
			},3000);
  			return;
  		};
  		if(!turnOn){
  			return;
  		};
  		turnOn=false;
  		var index=winners[n]['order'];
      	plate(index,winners[n].username);
    });
};