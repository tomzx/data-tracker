<?php

namespace App\Http\Controllers;

use App\QueryHelper;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use tomzx\DataTracker\LogService;
use tomzx\Mathematics\Structures\Sequence;

class LogController extends Controller {
	/**
	 * @var \tomzx\DataTracker\LogService
	 */
	protected $logService;

	/**
	 * @param \tomzx\DataTracker\LogService $logService
	 */
	public function __construct(LogService $logService)
	{
		$this->logService = $logService;
	}

	/**
	 * @return array
	 */
	public function index()
	{
		return $this->logService->getKeys();
	}

	// Accepts
	// {
	// 	"_timestamp": "now",
	// 	"a": 6,
	// 	"b": 2,
	// 	"c": 12
	// }
	/**
	 * @return array
	 */
	public function store()
	{
		$now = time();
		$timestamp = Carbon::createFromTimestampUTC(Request::input('_timestamp', $now));

		$data = Request::except(['_timestamp']);

		$insertedData = [];
		foreach ($data as $key => $value) {
			$insertedData[] = [
				'timestamp' => $timestamp->timestamp,
				'key'       => (string)$key,
				'value'     => (double)$value,
			];
		}

		QueryHelper::batchInsert(DB::table('log'), $insertedData);

		return ['timestamp' => $timestamp->timestamp];
	}

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
	/**
	 * @return array
	 */
	public function storeBulk()
	{
		$data = Request::all();

		$now = time();
		$insertedData = [];
		foreach ($data as $metricData) {
			$timestamp = Carbon::createFromTimestampUTC(Arr::get($metricData, '_timestamp', $now));
			$metrics = Arr::except($metricData, ['_timestamp']);
			foreach ($metrics as $key => $value) {
				$insertedData[] = [
					'timestamp' => $timestamp->timestamp,
					'key'       => (string)$key,
					'value'     => (double)$value,
				];
			}
		}

		QueryHelper::batchInsert(DB::table('log'), $insertedData);

		return ['status' => 'ok', 'inserted' => count($insertedData)];
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function show($key)
	{
		$from = Request::input('from');
		$to = Request::input('to');
		$order = strtolower(Request::input('order', 'asc'));
		$limit = Request::input('limit');
		$keyed = Request::input('keyed', false);

		if ( ! in_array($order, ['asc', 'desc'])) {
			$order = 'asc';
		}

		$query = DB::table('log')
			->select('log.*')
			->where('log.key', '=', $key)
			->groupBy('timestamp')
			->orderBy('timestamp', $order);

		if ($limit) {
			$query->limit((int)$limit);
		}

		$data = QueryHelper::whereFromTo($query, $from, $to)->get();

		if ($keyed) {
			return array_map(function ($datum) {
				return [(int)$datum->timestamp, (double)$datum->value];
			}, $data);
		} else {
			return array_map(function ($datum) {
				return (double)$datum->value;
			}, $data);
		}
	}

	/**
	 * @return string
	 */
	public function all()
	{
		$from = Request::input('from');
		$to = Request::input('to');

		$query = DB::table('log')
			->select('log.*')
			->groupBy('timestamp')
			->groupBy('key');

		$data = QueryHelper::whereFromTo($query, $from, $to)->get();

		$dateFormat = Request::input('format', 'Y-m-d');
		$lineChart = substr($dateFormat, 0, 3) === 'Y-m' || substr($dateFormat, 0, 1) === 'U';

		$processedData = [];
		foreach ($data as $datum) {
			$key = $datum->key;
			$formattedDate = date($dateFormat, $datum->timestamp);
			$processedData[$key][$formattedDate][] = (float)$datum->value;
		}

		$categories = DB::table('log')
			->distinct()
			->orderBy('key')
			->pluck('key');

		$output = [
			'chart'  => [
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

		$series = [];
		foreach ($categories as $category) {
			$series[$category] = [
				'name' => $category,
				'data' => [],
			];
		}

		foreach ($processedData as $group => $formattedDates) {
			$data = [];
			ksort($formattedDates);
			foreach ($formattedDates as $formattedDate => $values) {
				$xAxisValue = $lineChart ? (int)strtotime($formattedDate) * 1000 : $formattedDate; // TODO: Support U as format
				$data[] = [$xAxisValue, (float)(new Sequence($values))->average()];
			}
			$series[$group]['data'] = $data;
		}

		$output['series'] = array_values($series);

		return Response::json($output);
	}
}
