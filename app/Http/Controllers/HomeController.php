<?php

namespace App\Http\Controllers;

use Illuminate\Support\Arr;
use tomzx\DataTracker\Log;
use tomzx\DataTracker\LogService;
use tomzx\Mathematics\Structures\Sequence;
use View;

class HomeController extends Controller {
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
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
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
			'w-l'   => 'Day of week',
			'd'     => 'Day of month',
			'm-M'   => 'Month',
			'Y'     => 'Year',
			'Y-m-d H:i' => 'Year-Month-(Day) Hour:minute',
		];

		$defaultFormat = 'Y-m-d H:i';
		$currentFormat = isset($_GET['custom']) ? $_GET['custom'] : $defaultFormat;
		$currentLimit = isset($_GET['limit']) ? (int)$_GET['limit'] : 365;

		//	$logs = [];
		$logs = Log::all()->groupBy('key');
		$logs = array_map(function ($item) {
			return array_map('floatval', Arr::pluck($item, 'value'));
		}, $logs->toArray());

		$logs = array_map(function ($data) {
			$sequence = new Sequence($data);
			return [
				'count'              => $sequence->count(),
				'minimum'            => $sequence->minimum(),
				'average'            => $sequence->average(),
				'maximum'            => $sequence->maximum(),
				'median'             => $sequence->median(),
				'mode'               => $sequence->mode(),
				'range'              => $sequence->range(),
				'variance'           => $sequence->variance(),
				'standard_deviation' => $sequence->standardDeviation(),
			];
		}, $logs);

		return View::make('home', [
			'keys'          => $this->logService->getKeys(),
			'logs'          => $logs,
			'periods'       => $periods,
			'periodGroups'  => $periodGroups,
			'currentFormat' => $currentFormat,
			'currentLimit'  => $currentLimit,
		]);
	}
}
