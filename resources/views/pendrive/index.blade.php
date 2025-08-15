@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Pendrive Virtual</h2>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Subir Archivo</div>
        <div class="card-body">
            <form action="{{ route('pendrive.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="file">Seleccionar Archivo (Máx: 100MB)</label>
                    <input type="file" name="file" class="form-control-file" id="file" required>
                </div>
                <button type="submit" class="btn btn-primary">Subir</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Archivos Almacenados</div>
        <div class="card-body">
            <ul class="list-group">
                @forelse ($files as $file)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ basename($file) }}
                        <a href="{{ route('pendrive.download', ['filename' => basename($file)]) }}" class="btn btn-secondary btn-sm">Descargar</a>
                    </li>
                @empty
                    <li class="list-group-item">No hay archivos almacenados.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection