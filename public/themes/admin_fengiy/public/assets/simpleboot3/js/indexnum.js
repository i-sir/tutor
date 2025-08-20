function getTime(nS) {
	var date = new Date(nS); //获取一个时间对象
	let y = date.getFullYear(); // 获取完整的年份(4位,1970)
	let m = date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() +
		1; // 获取月份(0-11,0代表1月,用的时候记得加上1)
	let d = date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate(); // 获取日(1-31)
	return m + '-' + d;
}

let daylist = [];
for (var j = 0; j <= 30; j++) {
	let thirtyDays = new Date(new Date().toLocaleDateString()).getTime() - j * 3600 * 24 * 1000;
	thirtyDays = getTime(thirtyDays)
	daylist.push(thirtyDays)
}
daylist.sort(function(a, b) {
	return a > b ? 1 : -1;
})
// var today = getTime(new Date(new Date().toLocaleDateString())); //今天




// 订单数据————指定图表的配置项和数据
function orderChart(orderData, amountData) {
	// 基于准备好的dom，初始化echarts实例
	var orderChart = echarts.init(document.getElementById('order'));
	option = {
		title: {
			text: '订单量趋势'
		},
		tooltip: {
			trigger: 'axis'
		},
		xAxis: {
			type: 'category',
			// boundaryGap: false,
			boundaryGap: ["0.5", "0"],
			data: daylist,
			axisLabel: {
				interval: 0,
				rotate: 45, //倾斜度 -90 ⾄ 90 默认为0
				fontSize: 10,
				margin: 10
			},
		},
		yAxis: {
			type: 'value'
		},
		grid: {
			x: 30,
			y: 60,
			x2: 5,
			y2: 30
		},
		series: [{
			name: '订单金额',
			type: 'bar',
			color: ['#1A9AED'],
			barWidth: 20, //柱图宽度
			data: orderData,
		},
			{
				name: '订单数',
				type: 'line',
				color: ['#5DDAA1'],
				data: amountData,
			}
		]
	};
	// 使用刚指定的配置项和数据显示图表。
	orderChart.setOption(option);
}




// 用户数据————指定图表的配置项和数据
function userChart(userData) {
	// 基于准备好的dom，初始化echarts实例
	var userChart = echarts.init(document.getElementById('member'));
	option = {
		title: {
			text: '入库趋势图'
		},
		tooltip: {
			trigger: 'axis'
		},
		xAxis: {
			type: 'category',
			boundaryGap: ["0.5", "0"],
			data: daylist,
			axisLabel: {
				interval: 0,
				rotate: 45, //倾斜度 -90 ⾄ 90 默认为0
				fontSize: 10,
				margin: 10
			},
		},
		yAxis: {
			type: 'value'
		},
		grid: {
			x: 30,
			y: 60,
			x2: 5,
			y2: 30
		},
		series: [{
			name: '数量',
			type: 'line',
			color: ['#279EED'],
			smooth: 0.6,
			symbol: 'none',
			data: userData,
			areaStyle: {
				color: {
					type: 'linear',
					x: 0,
					y: 0,
					x2: 0,
					y2: 1,
					colorStops: [{
						offset: 0,
						color: 'rgba(39,158,237, 0.5)' // 0% 处的颜色
					}, {
						offset: 1,
						color: 'rgba(39,158,237, 0)' // 100% 处的颜色
					}],
					global: false // 缺省为 false
				}
			}
		}]
	};
	// 使用刚指定的配置项和数据显示图表。
	userChart.setOption(option);
}




// 入库数据————指定图表的配置项和数据
function enterChart(userData) {
	// 基于准备好的dom，初始化echarts实例
	var enterChart = echarts.init(document.getElementById('enter'));
	option = {
		title: {
			text: '入库趋势图'
		},
		tooltip: {
			trigger: 'axis'
		},
		xAxis: {
			type: 'category',
			boundaryGap: ["0.5", "0"],
			data: daylist,
			axisLabel: {
				interval: 0,
				rotate: 45, //倾斜度 -90 ⾄ 90 默认为0
				fontSize: 10,
				margin: 10
			},
		},
		yAxis: {
			type: 'value'
		},
		grid: {
			x: 30,
			y: 60,
			x2: 5,
			y2: 30
		},
		series: [{
			name: '入库数量',
			type: 'line',
			color: ['#279EED'],
			smooth: 0.6,
			symbol: 'none',
			data: userData,
			areaStyle: {
				color: {
					type: 'linear',
					x: 0,
					y: 0,
					x2: 0,
					y2: 1,
					colorStops: [{
						offset: 0,
						color: 'rgba(39,158,237, 0.5)' // 0% 处的颜色
					}, {
						offset: 1,
						color: 'rgba(39,158,237, 0)' // 100% 处的颜色
					}],
					global: false // 缺省为 false
				}
			}
		}]
	};
	// 使用刚指定的配置项和数据显示图表。
	enterChart.setOption(option);
}




// 出库数据————指定图表的配置项和数据
function outChart(userData) {
	// 基于准备好的dom，初始化echarts实例
	var outChart = echarts.init(document.getElementById('out'));
	option = {
		title: {
			text: '出库趋势图'
		},
		tooltip: {
			trigger: 'axis'
		},
		xAxis: {
			type: 'category',
			boundaryGap: ["0.5", "0"],
			data: daylist,
			axisLabel: {
				interval: 0,
				rotate: 45, //倾斜度 -90 ⾄ 90 默认为0
				fontSize: 10,
				margin: 10
			},
		},
		yAxis: {
			type: 'value'
		},
		grid: {
			x: 30,
			y: 60,
			x2: 5,
			y2: 30
		},
		series: [{
			name: '入库数量',
			type: 'line',
			color: ['#279EED'],
			smooth: 0.6,
			symbol: 'none',
			data: userData,
			areaStyle: {
				color: {
					type: 'linear',
					x: 0,
					y: 0,
					x2: 0,
					y2: 1,
					colorStops: [{
						offset: 0,
						color: 'rgba(39,158,237, 0.5)' // 0% 处的颜色
					}, {
						offset: 1,
						color: 'rgba(39,158,237, 0)' // 100% 处的颜色
					}],
					global: false // 缺省为 false
				}
			}
		}]
	};
	// 使用刚指定的配置项和数据显示图表。
	outChart.setOption(option);
}
