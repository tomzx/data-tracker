<?php

namespace tomzx\DataTracker;

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
