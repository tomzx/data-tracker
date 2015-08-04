$(function() {
	$('select').select2({

	});

	$(document).ready(function() {
		$('table').DataTable();
	} );

	Number.prototype.round = function(places) {
		return +(Math.round(this + "e+" + places) + "e-" + places);
	};

	Highcharts.setOptions({
		global: {
			timezoneOffset: (new Date().getTimezoneOffset())
		}
	});

	var graphHandlers = {};

	graphHandlers['all'] = function(chartOptions) {
		var chart = $('.all');
		var defaults = {
			title: {
				text: 'Data log',
			},
			legend: {
				layout: 'vertical',
				align: 'right',
				verticalAlign: 'middle',
				borderWidth: 0
			},
			credits: {
				enabled: false
			},
		};

		//if (chart !== null && chart.highcharts()) {
		//	var highcharts = chart.highcharts();
		//	//defaults = highcharts.options;
		//	//for (var i = 0; i < chartOptions.series.length; i++) {
		//	//	highcharts.series[i].setData(chartOptions.series[i].data, false, false);
		//	//}
		//	// TODO: Refresh chart
		//
		//	chartOptions = $.extend(true, defaults, highcharts.options, chartOptions);
		//} else {
		//	chartOptions = $.extend(true, defaults, chartOptions);
		//	//chartOptions = $.extend(true, defaults, chartOptions);
		//	//
		//	//chart.highcharts(chartOptions);
		//}

		chartOptions = $.extend(true, defaults, chartOptions);

		chart.highcharts(chartOptions);
	};

	var refreshGraphs = function() {
		$('[data-url]').each(function() {
			var $this = $(this),
				url = $this.data('url');

			$.ajax({
				url: url,
				data: {
					from: $('#from').val(),
					to: $('#to').val(),
					format: $('#format').val(),
					normalize: $('#normalize').val(),
				}
			}).done(function(data) {
				if (typeof graphHandlers[url] !== 'undefined') {
					graphHandlers[url](data);
				}
			});
		});
	};

	refreshGraphs();

	$('#from, #to, #format').change(function() {
		refreshGraphs();
	});
});
