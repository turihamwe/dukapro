@extends('layouts.base')

@section('body')
@if(user_ui_theme() === 'modern')
    @include('layouts.partials.admin-modern-shell')
@else
    @include('layouts.partials.admin-plain-shell')
@endif
@endsection
