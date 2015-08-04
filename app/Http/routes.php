<?php

use Carbon\Carbon;
use Illuminate\Support\Arr;
use tomzx\DataTracker\Log;
use tomzx\Mathematics\Structures\Sequence;

$periods = [
	7    => 'One week',
	14   => 'Two weeks',
	30   => 'One month',
	90   => '3 months',
	180  => '6 months',
	365  => 'Last year',
	9999 => 'All times'
];

$periodGroups = [
	'w-l' => 'Day of week',
	'd' => 'Day of month',
	'm-M' => 'Month',
	'Y' => 'Year',
	'Y-m-d' => 'Year-Month-(Day)',
];

$defaultFormat = 'Y-m-d';
$currentFormat = isset($_GET['custom']) ? $_GET['custom'] : $defaultFormat;
$currentLimit = isset($_GET['limit']) ? (int)$_GET['limit'] : 365;

Route::get('/', function() use ($periods, $periodGroups, $currentFormat, $currentLimit) {
//	$logs = [];
	$logs = Log::all()->groupBy('key');
	$logs = array_map(function($item) {
		return array_map('floatval', array_pluck($item, 'value'));
	}, $logs->toArray());

	$logs = array_map(function($data) {
		$sequence = new Sequence($data);
		return [
			'count' => $sequence->count(),
			'minimum' => $sequence->minimum(),
			'average' => $sequence->average(),
			'maximum' => $sequence->maximum(),
			'median' => $sequence->median(),
			'mode' => $sequence->mode(),
			'range' => $sequence->range(),
			'variance' => $sequence->variance(),
			'standard_deviation' => $sequence->standardDeviation(),
		];
	}, $logs);

	return View::make('home', [
		'keys' => getKeys(),
		'logs' => $logs,
		'periods' => $periods,
		'periodGroups' => $periodGroups,
		'currentFormat' => $currentFormat,
		'currentLimit' => $currentLimit,
	]);
});

Route::get('logs', function() {
	return getKeys();
});

function getKeys() {
	$data = DB::table('log')->select('key')->groupBy('key')->get();
	return Arr::pluck($data, 'key');
}

// Accepts
// {
// 	"_timestamp": "now",
// 	"a": 6,
// 	"b": 2,
// 	"c": 12
// }
Route::post('logs', function() {
	$now = time();
	$timestamp = Carbon::createFromTimestampUTC(Input::get('_timestamp', $now));

	$data = Input::except(['_timestamp']);

	$insertedData = [];
	foreach ($data as $key => $value) {
		$insertedData[] = [
			'timestamp' => $timestamp->timestamp,
			'key' => (string)$key,
			'value' => (double)$value,
		];
	}

	DB::table('log')->insert($insertedData);

	return ['timestamp' => $timestamp->timestamp];
});

// Accepts
// [
// 	{
// 		"_timestamp": "now",
// 		"a": 6,
// 		"b": 2,
// 		"c": 12
// 	},
// 	{
// 		"_timestamp": "tomorrow",
// 		"d": 6,
// 		"e": 2,
// 		"f": 12
// 	}
// ]
Route::post('logs/bulk', function() {
	$data = Input::all();

	$now = time();
	$insertedData = [];
	foreach ($data as $metricData) {
		$timestamp = Carbon::createFromTimestampUTC(Arr::get($metricData, '_timestamp', $now));
		$metrics = Arr::except($metricData, ['_timestamp']);
		foreach ($metrics as $key => $value) {
			$insertedData[] = [
				'timestamp' => $timestamp->timestamp,
				'key' => (string)$key,
				'value' => (double)$value,
			];
		}
	}

	DB::table('log')->insert($insertedData);

	return ['status' => 'ok', 'inserted' => count($insertedData)];
});

Route::get('logs/{key}', function($key) {
	$from = Input::get('from');
	$to = Input::get('to');

	$query = DB::table('log')
		->select('log.*')
		->where('log.key', '=', $key)
		->groupBy('timestamp');

	$data = whereFromTo($query, $from, $to)->get();

//	return array_map(function($datum) {
//		return [(int)$datum->timestamp, (double)$datum->value];
//	}, $data);
	return array_map(function($datum) {
		return (double)$datum->value;
	}, $data);
});

function whereFromTo($query, $from, $to) {
	$from = $from ? Carbon::parse($from) : null;
	$to = $to ? Carbon::parse($to) : null;
	if ($from && $to) {
		return $query->whereBetween('log.timestamp', [$from->timestamp, $to->timestamp]);
	} else if ($from) {
		return $query->where('log.timestamp', '>', $from->timestamp);
	} else if ($to) {
		return $query->where('log.timestamp', '<', $to->timestamp);
	}

	return $query;
}

Route::get('all', function() {
	$from = Input::get('from');
	$to = Input::get('to');

	$query = DB::table('log')
		->select('log.*')
		->groupBy('timestamp')
		->groupBy('key');

	$data = whereFromTo($query, $from, $to)->get();

	$dateFormat = Input::get('format', 'Y-m-d');
	$lineChart = substr($dateFormat, 0, 3) === 'Y-m' || substr($dateFormat, 0, 1) === 'U';

	$categories = [];
	$processedData = [];
	foreach ($data as $datum) {
		$key = $datum->key;
		$formattedDate = date($dateFormat, $datum->timestamp);
		$categories[$formattedDate] = null;
		$processedData[$key][$formattedDate][] = (float)$datum->value;
	}

	$categories = array_keys($categories);
	sort($categories);

	$output = [
		'chart' => [
			'type' => $lineChart ? 'line' : 'column',
		],
		'series' => []
	];

	if ($lineChart) {
		$output += [
				'xAxis' => [
					'type' => 'datetime',
			],
		];
	} else {
		$output += [
			'xAxis' => [
				'categories' => $categories,
			],
		];
	}

	foreach ($processedData as $group => $formattedDates) {
		$entry = [
			'name' => $group,
			'data' => [],
		];
		ksort($formattedDates);
		foreach ($formattedDates as $formattedDate => $values) {
			$xAxisValue = $lineChart ? (int)strtotime($formattedDate)*1000 : $formattedDate; // TODO: Support U as format
			$entry['data'][] = [$xAxisValue, (float)(new Sequence($values))->average()];
		}
		$output['series'][] = $entry;
	}

	return Response::json($output);
});
