<?php

namespace tomzx\DataTracker;

use DB;
use Illuminate\Support\Arr;

class LogService {
	/**
	 * @return array
	 */
	public function getKeys()
	{
		$data = DB::table('log')->select('key')->groupBy('key')->get();
		return Arr::pluck($data, 'key');
	}
}
