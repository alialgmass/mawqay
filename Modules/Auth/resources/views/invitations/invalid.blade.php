@extends('auth::invitations.layout')

@section('title', __('auth::invitations.invalid_title'))

@section('content')
    <h1>{{ __('auth::invitations.invalid_title') }}</h1>
    <p>{{ __('auth::invitations.invalid_body') }}</p>
@endsection
