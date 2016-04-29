<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Data Tracker</title>
	<style>
		.graph {
			/*position: absolute;*/
			width: 100%;
			/*height: 100%;*/
		}

		.fixed {
			/*position: fixed;*/
			/*left: 0;*/
			/*top: 0;*/
			/*z-index: 1000;*/
			padding: 10px;
		}
	</style>
	<link rel="stylesheet" href="{{asset('assets/css/bootstrap.min.css')}}">
	<link rel="stylesheet" href="{{asset('assets/css/bootstrap-theme.min.css')}}">
	<link rel="stylesheet" href="{{asset('assets/css/select2.min.css')}}">
	<link rel="stylesheet" href="{{asset('assets/css/jquery.dataTables.min.css')}}">
</head>
<body>
	<div class="container-fluid">
		{!! Form::open(['class' => 'form-inline fixed']) !!}
			<div class="form-group">
				<label for="from" class="sr-only">From</label>
				<input type="text" name="from" id="from" class="form-control" placeholder="from" value="-30 days"/>
			</div>
			<div class="form-group">
				<label for="to" class="sr-only">To</label>
				<input type="text"  name="to" id="to" class="form-control" placeholder="to" value="now"/>
			</div>
			<div class="form-group">
				<label for="format" class="sr-only">Format</label>
				<input type="text"  name="format" id="format" class="form-control" placeholder="format" value="Y-m-d H:i"/>
			</div>
			<div class="form-group">
				<select name="keys" id="keys" class="form-control" multiple="true">
					@foreach ($keys as $key)
					<option value="{{ $key }}">{{ $key }}</option>
					@endforeach
				</select>
			</div>
			<div class="form-group">
				<label for="normalize">
					<input type="checkbox" name="normalize" id="normalize"/> Normalize
				</label>
			</div>
		{!! Form::close() !!}
		<div class="row">
			<div class="graph all" data-url="all"></div>
		</div>

		<div class="row">
			<div class="pull-left">
				<div class="btn-group">
					@foreach ($periodGroups as $format => $label)
					<button class="btn btn-sm {{ $format == $currentFormat ? 'btn-primary' : 'btn-default' }} btn-format" name="custom" value="{{ $format }}">{{ $label }}</button>
					@endforeach
				</div>
			</div>

			<div class="pull-right">
				<div class="btn-group">
					@foreach ($periods as $period => $label)
					<button class="btn btn-sm {{ $period == $currentLimit ? 'btn-primary' : 'btn-default' }} btn-period" name="limit" value="{{ $period }}">{{ $label }}</button>
					@endforeach
				</div>
			</div>
		</div>

		<div class="row" style="padding-top: 25px;">
			<table class="table">
				<thead>
					<tr>
						<th>Metric</th>
						<th>Count</th>
						<th>Minimum</th>
						<th>Average</th>
						<th>Maximum</th>
						<th>Median</th>
						<th>Mode</th>
						<th>Range</th>
						<th>Variance</th>
						<th>Standard deviation</th>
					</tr>
				</thead>

				<tbody>
					@foreach ($logs as $metric => $values)
					<tr>
						<td>{{ $metric }}</td>
						<td>{{ $values['count'] }}</td>
						<td>{{ $values['minimum'] }}</td>
						<td>{{ round($values['average'], 2) }}</td>
						<td>{{ $values['maximum'] }}</td>
						<td>{{ $values['median'] }}</td>
						<td>{{ $values['mode'] }}</td>
						<td>{{ $values['range'] }}</td>
						<td>{{ round($values['variance'], 2) }}</td>
						<td>{{ round($values['standard_deviation'], 2) }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>

	<script src="{{asset('assets/js/jquery-2.1.3.js')}}"></script>
	<script src="{{asset('assets/js/bootstrap.min.js')}}"></script>
	<script src="{{asset('assets/js/lodash-3.1.0.js')}}"></script>
	<script src="{{asset('assets/js/highcharts.js')}}"></script>
	<script src="{{asset('assets/js/select2.full.min.js')}}"></script>
	<script src="{{asset('assets/js/jquery.dataTables.min.js')}}"></script>
	<script src="{{asset('assets/js/app.js')}}"></script>
</body>
</html>
