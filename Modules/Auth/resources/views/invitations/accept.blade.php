@extends('auth::invitations.layout')

@section('title', __('auth::invitations.accept_title'))

@section('content')
    <h1>{{ __('auth::invitations.accept_title') }}</h1>
    <p>{{ __('auth::invitations.accept_intro', ['store' => $invitation->store->name]) }}</p>

    <form method="POST" action="{{ route('invitations.accept', $token) }}">
        @csrf
        <label for="password">{{ __('auth::invitations.password') }}</label>
        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
        @error('password') <p class="error">{{ $message }}</p> @enderror

        <label for="password_confirmation">{{ __('auth::invitations.password_confirmation') }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">

        <button type="submit">{{ __('auth::invitations.submit') }}</button>
    </form>
@endsection
