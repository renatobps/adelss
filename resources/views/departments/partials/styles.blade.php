@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/css/departments.css') }}?v={{ filemtime(public_path('css/css/departments.css')) }}">
    @endpush
@endonce
