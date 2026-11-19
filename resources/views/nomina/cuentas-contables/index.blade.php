@extends('layouts.app')

@section('title', 'Cuentas contables de nómina')

@section('content')
    <div class="container">
        <x-page-header title="Cuentas contables de nómina" icon="journal-text"
            subtitle="Cuenta de cada concepto en la contabilización de la nómina (débito: devengados; crédito: deducciones y neto a pagar)." />

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @include('nomina.partials.errores-formulario')

        <form method="POST" action="{{ route('nomina.cuentas-contables.update') }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>CONCEPTO</th>
                            <th>NATURALEZA</th>
                            <th style="width: 14rem;">No. CUENTA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cuentas as $cuenta)
                            <tr>
                                <td class="fw-semibold">{{ $cuenta->nombre }}</td>
                                <td>
                                    <span class="badge {{ $cuenta->naturaleza === 'debito' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                        {{ $cuenta->naturaleza === 'debito' ? 'Débito' : 'Crédito' }}
                                    </span>
                                </td>
                                <td>
                                    <input type="text" inputmode="numeric" name="cuentas[{{ $cuenta->id }}]" class="form-control"
                                        value="{{ old('cuentas.'.$cuenta->id, $cuenta->cuenta) }}" maxlength="20"
                                        placeholder="{{ $cuenta->concepto === 'bancos' ? 'Cuenta de bancos' : '' }}" aria-label="Cuenta de {{ $cuenta->nombre }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <span class="small text-muted">Cargadas de la hoja CONTABILIZACION. La de bancos no venía en la plantilla: digítela.</span>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> Guardar</button>
            </div>
        </form>
    </div>
@endsection
