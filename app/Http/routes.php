<?php

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

Route::get('/', function() {
	return View::make('home');
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
	$timestamp = Carbon::parse(Input::get('_timestamp', 'now'));
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

	$insertedData = [];
	foreach ($data as $metricData) {
		$timestamp = Carbon::parse(Arr::get($metricData, '_timestamp', 'now'));
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
	$from = $from ? Carbon::parse($from) : null;
	$to = Input::get('to');
	$to = $to ? Carbon::parse($to) : null;

	$query = DB::table('log')
		->select('log.*')
		->where('log.key', '=', $key)
		->groupBy('timestamp');

	whereFromTo($query, $from, $to);

	$data = $query->get();

	return array_map(function($datum) {
		return [(int)$datum->timestamp, (double)$datum->value];
	}, $data);
});

function whereFromTo($query, $from, $to) {
	if ($from && $to) {
		return $query->whereBetween('log.timestamp', [$from->timestamp, $to->timestamp]);
	} else if ($from) {
		return $query->where('log.timestamp', '>', $from->timestamp);
	} else if ($to) {
		return $query->where('log.timestamp', '<', $to->timestamp);
	}
}

Route::get('all', function() {
	$data = DB::table('log')
		->select('log.*')
		->groupBy('timestamp')
		->groupBy('key')
		->get();

	$data = (new Collection($data))->groupBy('key');

	$output = [];

	foreach ($data as $group => $items) {
		$entry = [
			'name' => $group,
			'data' => [],
		];
		foreach ($items as $item) {
			$entry['data'][] = [(int)$item->timestamp*1000, (float)$item->value];
		}
		$output[] = $entry;
	}

	return Response::json($output);
});