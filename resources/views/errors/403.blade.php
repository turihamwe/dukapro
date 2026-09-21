@extends('errors.layout')

@section('title', 'Access not available — '.platform_brand('name'))

@section('badge_class', 'badge badge-warn')
@section('badge')
    No access
@endsection

@section('heading')
    You can&rsquo;t open this page
@endsection

@section('message')
    Ask your boss, or renew your plan. Tap <strong>Go back</strong>.
@endsection
