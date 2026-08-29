@extends('layouts.print')

@section('title', 'EOD Report — ' . $reconciliation->reconciliation_date->format('M j, Y'))

@section('content')
@include('reconciliation.partials.report-body', ['reconciliation' => $reconciliation, 'report' => $report, 'business' => $reconciliation->business])
@endsection

@push('scripts')
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endpush
