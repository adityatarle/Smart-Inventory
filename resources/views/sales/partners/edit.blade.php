@extends('layouts.app')
@section('title', 'Edit Sales Partner')
@section('heading', 'Edit Sales Partner')
@section('content')
@include('sales.partners._form', ['action' => route('sales-partners.update', $salesPartner), 'salesPartner' => $salesPartner, 'method' => 'PUT'])
@endsection
