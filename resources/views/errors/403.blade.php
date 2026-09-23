@extends(error_page_layout())

@section('title', 'Access not available — '.platform_brand('name'))

@section('badge_class', 'inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800')
@section('badge')
    No access
@endsection

@section('heading')
    You can&rsquo;t open this page
@endsection

@section('message')
    Ask your boss, or renew your plan. Tap <strong>Go back</strong>.
@endsection
