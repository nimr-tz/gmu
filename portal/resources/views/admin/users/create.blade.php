@extends('layouts.app')

{{-- Adding a person, from the owner's "GMU Admin" design. --}}

@section('title', $sso ? 'Add a person' : 'Create an account')
@section('context', 'Administration · People and roles')
@section('topbar-colour', 'bg-foreground')

@section('content')
@include('admin.users.partials.form', ['user' => null, 'sso' => $sso ?? app(\App\Services\Auth\NreimsOAuth::class)->enabled()])
@endsection
