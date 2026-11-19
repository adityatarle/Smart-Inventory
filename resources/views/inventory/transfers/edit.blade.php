@extends('layouts.app')
@section('title', 'Edit '.$transfer->document_number)
@section('heading', 'Edit '.$transfer->document_number)
@section('subheading', 'Draft transfer')
@section('breadcrumb', 'SmartStock / Inventory / Transfers / Edit')

@section('content')
@include('inventory.transfers._form', [
    'action' => route('stock-transfers.update', $transfer),
    'method' => 'PUT',
    'transfer' => $transfer,
])
@endsection
