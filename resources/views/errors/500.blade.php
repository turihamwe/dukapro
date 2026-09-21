@extends('errors.layout')

@section('title', 'Something went wrong — '.platform_brand('name'))

@section('badge_class', 'badge badge-muted')
@section('badge')
    Try again
@endsection

@section('heading')
    Something failed
@endsection

@section('message')
    Your sales are safe. Tap <strong>Go back</strong>, then try again.
@endsection
