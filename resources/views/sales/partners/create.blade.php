@extends('layouts.app')
@section('title', 'Add Sales Partner')
@section('heading', 'Add Sales Partner')
@section('content')
@include('sales.partners._form', ['action' => route('sales-partners.store'), 'salesPartner' => null])
@endsection
