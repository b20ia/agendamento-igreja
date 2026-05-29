@extends('layouts.main')

@section('titulo', 'Admin | 20 Anos EJC SPSP')

@section('content')
    <section class="admin-auth">
        <div class="admin-auth-panel">
            <span class="tag">Admin</span>
            <h1>Área administrativa</h1>

            <form method="POST" action="{{ route('admin.authenticate') }}" class="form-agendamento">
                @csrf

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-confirmar">Entrar</button>
                </div>
            </form>
        </div>
    </section>
@endsection
