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

	var viewing = null;
	var KEY_UP = 0;
	var KEY_DOWN = 1;
	var ctrlKey = KEY_UP;
	window.onkeyup = function(event) {
		if ( ! event.ctrlKey) {
			ctrlKey = KEY_UP;
		}
	};

	window.onkeydown = function(event) {
		if (event.ctrlKey) {
			ctrlKey = KEY_DOWN;
		}
	};

	var graphHandlers = {};

	graphHandlers['all'] = function(chartOptions) {
		var chart = $('.all');
		var defaults = {
			title: {
				text: 'Data log',
			},
			plotOptions: {
				series: {
					events: {
						legendItemClick: function() {
							var currentSeries = this;
							if (ctrlKey === KEY_DOWN) {
								// If the selected series is different than the previously viewed series, we will
								// show only the newly selected series
								var isDifferent = viewing !== this;
								// Show all series if the series we were viewing is different than the one selected
								var showAll = ! isDifferent;
								// If we were showing all series, then it means we're not viewing any particular series
								viewing = showAll ? null : this;

								_.each(this.chart.series, function(series) {
									series.setVisible(showAll);
								});
								// Always make selected visible
								this.setVisible(true);
								return false;
							} else {
								// We're not viewing any item in particular now
								viewing = null;
							}
						},
					},
				},
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

		chartOptions = $.extend(true, defaults, chartOptions);

		updateChart(chart, chartOptions);
	};

	var updateChart = function(chart, data) {
		if(chart !== null && chart.highcharts()) {
			var highcharts = chart.highcharts();
			for(var i = 0; i < data.series.length; i++) {
				// TODO(tom@tomrochette.com): This still can break if new series are added while looking at the chart
				highcharts.series[i].setData(data.series[i].data, false, false);
			}
			highcharts.xAxis[0].setCategories(data.series.categories, true, true);
		} else {
			chart.highcharts(data);
		}
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

	$('.btn-format').click(function() {
		var $this = $(this);
		$('.btn-format').removeClass('btn-primary').addClass('btn-default');
		$this.removeClass('btn-default').addClass('btn-primary');
		$('#format').val($this.attr('value')).trigger('change');
	});

	$('.btn-period').click(function() {
		var $this = $(this);
		$('.btn-period').removeClass('btn-primary').addClass('btn-default');
		$this.removeClass('btn-default').addClass('btn-primary');
		$('#from').val('-' + $this.attr('value') + ' days');
		$('#to').val('now');
		$('#from').trigger('change');
	});
});
