/**
 * Created by admin on 2016/8/13.
 */

		// 百度地图API功能
	function MblogDotccMap(keyword) {
		var map = new BMap.Map('map');
		//以钦州初始化城市...
//	map.centerAndZoom("深圳",15);
		var point = new BMap.Point(114.066112, 22.548515); // 创建点坐标，
		var marker = new BMap.Marker(point);        // 创建标注
		map.addOverlay(marker);                     // 将标注添加到地图中
		map.centerAndZoom(point, 18); // 初始化地图，设置中心点坐标和地图级别
		map.addControl(new BMap.NavigationControl()); //添加缩放控件
		map.addControl(new BMap.ScaleControl()); //添加比例尺控件
		map.enableScrollWheelZoom(); // 启用滚轮放大缩小。
//		map.enableKeyboard(); // 启用键盘操作。
		var overlays = [];
		//回调获得覆盖物信息
		var overlaycomplete = function (e) {
			clearbefore();
			overlays.push(e.overlay);
			var result = "";
			if (e.drawingMode == BMAP_DRAWING_MARKER) {
				$("#lat").val(e.overlay.getPosition().lat);
				$("#lng").val(e.overlay.getPosition().lng);
				result += ' 坐标：' + e.overlay.getPosition().lng + ',' + e.overlay.getPosition().lat;
			}
			console.log(result);
		};
		//实例化鼠标绘制工具
		var drawingManager = new BMapLib.DrawingManager(map, {
			isOpen: true, //是否开启绘制模式
			enableDrawingTool: false,
			setDrawingMode: BMAP_DRAWING_MARKER//画点
		});

		//添加鼠标绘制工具监听事件，用于获取绘制结果
		drawingManager.addEventListener('overlaycomplete', overlaycomplete);
		function clearbefore() {
			for (var i = 0; i < overlays.length; i++) {
				map.removeOverlay(overlays[i]);
			}
		}
		//搜索
		var local = new BMap.LocalSearch(map, {
			renderOptions: {
				map: map,
				panel: "results_info"
			}
		});

		map.panBy(point);
		local.search(keyword);
		local.getResults()
		local.setSearchCompleteCallback(function(searchResult) {
			var poi = searchResult.getPoi(0);
			$("#lng").val(poi.point.lng);
			$("#lat").val(poi.point.lat);
		});
	}
