@extends('layouts.print')

@section('title', 'EOD Report — ' . $reconciliation->reconciliation_date->format('M j, Y'))

@push('print-actions')
    <x-document-share-toolbar
        layout="inline"
        :show-print="false"
        :whats-app-href="$whatsAppUrl"
        :whats-app-message="$shareMessage"
        :default-phone="$bossPhone ?? null"
        :show-phone-input="true"
        :email-href="$emailShareUrl"
    />
@endpush

@section('content')
@include('reconciliation.partials.report-body', ['reconciliation' => $reconciliation, 'report' => $report, 'business' => $reconciliation->business, 'shortages' => $shortages ?? collect()])
@endsection

@push('scripts')
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endpush
