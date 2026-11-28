@extends('laravel-usp-theme::master')

@section('styles')
    @parent
    {{--
        O tema da USP usa Bootstrap 4.6. Estas regras dão suporte às classes
        do Bootstrap 5 usadas nas views (gap-*, ms-/me-*, g-*, fw-*, form-select...).
    --}}
    <style>
        .gap-0 { gap: 0 !important; } .gap-1 { gap: .25rem !important; } .gap-2 { gap: .5rem !important; }
        .gap-3 { gap: 1rem !important; } .gap-4 { gap: 1.5rem !important; } .gap-5 { gap: 3rem !important; }
        .ms-1 { margin-left: .25rem !important; } .ms-2 { margin-left: .5rem !important; } .ms-3 { margin-left: 1rem !important; } .ms-auto { margin-left: auto !important; }
        .me-1 { margin-right: .25rem !important; } .me-2 { margin-right: .5rem !important; } .me-3 { margin-right: 1rem !important; } .me-auto { margin-right: auto !important; }
        .ps-4 { padding-left: 1.5rem !important; } .pe-4 { padding-right: 1.5rem !important; }
        .text-end { text-align: right !important; } .text-start { text-align: left !important; }
        .fw-normal { font-weight: 400 !important; } .fw-medium { font-weight: 500 !important; }
        .fw-semibold { font-weight: 600 !important; } .fw-bold { font-weight: 700 !important; }
        .bg-body-tertiary { background-color: #f8f9fa !important; }
        .text-warning-emphasis { color: #664d03 !important; }

        /* Gutters de .row (g-*) */
        .row.g-2 { margin: -.25rem; } .row.g-2 > * { padding: .25rem; }
        .row.g-3 { margin: -.5rem; }  .row.g-3 > * { padding: .5rem; }
        .row.g-4 { margin: -.75rem; } .row.g-4 > * { padding: .75rem; }

        /* col-xxl-* (>= 1400px) */
        @media (min-width: 1400px) {
            .col-xxl-3 { flex: 0 0 25%; max-width: 25%; }
            .col-xxl-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        }

        /* form-select (BS5) com a aparência do custom-select (BS4) */
        .form-select {
            display: block; width: 100%; height: calc(1.5em + .75rem + 2px); padding: .375rem 1.75rem .375rem .75rem;
            font-size: 1rem; line-height: 1.5; color: #495057; vertical-align: middle; background: #fff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='4' height='5' viewBox='0 0 4 5'%3e%3cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3e%3c/svg%3e") right .75rem center/8px 10px no-repeat;
            border: 1px solid #ced4da; border-radius: .25rem; appearance: none; -webkit-appearance: none;
        }
        .form-select:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 .2rem rgba(0,123,255,.25); }
        .form-select-sm { height: calc(1.5em + .5rem + 2px); padding-top: .25rem; padding-bottom: .25rem; padding-left: .5rem; font-size: .875rem; }
        .form-select:disabled { background-color: #e9ecef; }

        /* Checkbox solto (fora de .form-check): no BS4 ele fica absoluto e desalinhado */
        input.form-check-input.solto {
            position: static; margin: 0; width: 1.05rem; height: 1.05rem; vertical-align: middle; cursor: pointer; accent-color: #0d6efd;
        }
    </style>
@endsection

@section('flash')
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <div class="flash-message">
    @foreach (['danger', 'warning', 'success', 'info'] as $msg)
        @if(Session::has('alert-' . $msg))
        <p class="alert alert-{{ $msg }}">{{ Session::get('alert-' . $msg) }}
            <a href="#" class="close" data-dismiss="alert" aria-label="fechar">&times;</a>
        </p>
        @endif
    @endforeach
    </div>
@endsection
