<?php

use Carbon\Carbon;
use Illuminate\Support\Arr;

Route::get('/', function() {
	$data = DB::table('log')->select('key')->groupBy('key')->get();
	return Arr::pluck($data, 'key');
});

Route::post('/', function() {
	$timestamp = Carbon::parse(Input::get('timestamp'));
	$data = Input::except(['timestamp']);

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

Route::get('/{key}', function($key) {
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

function whereFromTo($query, $from, $to)
{
	if ($from && $to) {
		return $query->whereBetween('log.timestamp', [$from->timestamp, $to->timestamp]);
	} else if ($from) {
		return $query->where('log.timestamp', '>', $from->timestamp);
	} else if ($to) {
		return $query->where('log.timestamp', '<', $to->timestamp);
	}
}