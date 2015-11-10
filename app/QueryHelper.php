<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

class QueryHelper {
	/**
	 * @param \Illuminate\Database\Query\Builder $query
	 * @param int                                $from
	 * @param int                                $to
	 * @return $this|\Illuminate\Database\Query\Builder
	 */
	public static function whereFromTo(Builder $query, $from, $to)
	{
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

	/**
	 * @param \Illuminate\Database\Query\Builder $table
	 * @param array                              $data
	 * @param int                                $batchSize
	 */
	public static function batchInsert(Builder $table, array $data, $batchSize = 100)
	{
		$table->getConnection()->transaction(function () use ($table, $data, $batchSize) {
			// Batch in group of 250 entries to prevent "Too many SQL variables" SQL error
			$batchCount = ceil(count($data) / $batchSize);
			for ($i = 0; $i < $batchCount; ++$i) {
				$insertedData = array_slice($data, $i * $batchSize, $batchSize);

				$table->insert($insertedData);
			}
		});
	}
}
