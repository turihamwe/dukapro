@extends(error_page_layout())

@section('title', 'Something went wrong — '.platform_brand('name'))

@section('badge_class', 'inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700')
@section('badge')
    Try again
@endsection

@section('heading')
    Something failed
@endsection

@section('message')
    Your sales are safe. Tap <strong>Go back</strong>, then try again.
@endsection
