@extends('layouts.app')
@section('title', 'Modifier: '.$fishType->name)
@section('content')
@php $fishType = $fishType; @endphp
@include('fish-types.create')
@endsection
