<?php

namespace App\Http\Controllers;

use App\QueryHelper;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Arr;
use Input;
use Response;
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
		$timestamp = Carbon::createFromTimestampUTC(Input::get('_timestamp', $now));

		$data = Input::except(['_timestamp']);

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
		$data = Input::all();

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
		$from = Input::get('from');
		$to = Input::get('to');
		$order = strtolower(Input::get('order', 'asc'));
		$limit = Input::get('limit');
		$keyed = Input::get('keyed', false);

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
		$from = Input::get('from');
		$to = Input::get('to');

		$query = DB::table('log')
			->select('log.*')
			->groupBy('timestamp')
			->groupBy('key');

		$data = QueryHelper::whereFromTo($query, $from, $to)->get();

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

		foreach ($processedData as $group => $formattedDates) {
			$entry = [
				'name' => $group,
				'data' => [],
			];
			ksort($formattedDates);
			foreach ($formattedDates as $formattedDate => $values) {
				$xAxisValue = $lineChart ? (int)strtotime($formattedDate) * 1000 : $formattedDate; // TODO: Support U as format
				$entry['data'][] = [$xAxisValue, (float)(new Sequence($values))->average()];
			}
			$output['series'][] = $entry;
		}

		return Response::json($output);
	}
}
