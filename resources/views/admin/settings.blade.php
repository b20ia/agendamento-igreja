@extends('layouts.main')

@section('titulo', 'Configurações | Admin')

@section('content')
    <section class="admin-header">
        <div>
            <span class="tag">Admin</span>
            <h1>Configurações</h1>
        </div>
    </section>

    @include('admin.partials.nav')

    <section class="admin-settings">
        <div class="admin-auth-panel">
            @if (session('status'))
                <div class="mensagem-feedback sucesso">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.settings.update') }}" class="form-agendamento">
                @csrf

                <div class="form-group">
                    <label for="whatsapp_admin_phone">Telefone para notificações</label>
                    <input
                        type="tel"
                        id="whatsapp_admin_phone"
                        name="whatsapp_admin_phone"
                        class="form-input"
                        value="{{ old('whatsapp_admin_phone', $settings['whatsapp_admin_phone']) }}"
                        placeholder="Ex: 5585999999999"
                    >
                    @error('whatsapp_admin_phone')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="admin-setting-note">
                    Este número recebe os avisos de novas inscrições e cancelamentos pelo WhatsApp.
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-confirmar">Salvar configurações</button>
                </div>
            </form>
        </div>
    </section>
@endsection
