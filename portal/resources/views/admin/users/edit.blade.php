@extends('layouts.app')

{{-- Editing an account, from the owner's "GMU Admin" design. --}}

@section('title', 'Edit account')
@section('context', 'Administration · People and roles')
@section('topbar-colour', 'bg-foreground')

@section('content')
@include('admin.users.partials.form', ['user' => $user, 'sso' => $sso ?? app(\App\Services\Auth\NreimsOAuth::class)->enabled()])
@endsection
