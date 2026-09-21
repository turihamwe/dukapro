@extends('errors.layout')

@section('title', 'Page not found — '.platform_brand('name'))

@section('badge')
    Not found
@endsection

@section('heading')
    This page is missing
@endsection

@section('message')
    Tap <strong>Go back</strong>.
@endsection
