{{-- resources/views/errors/403.blade.php --}}
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>403 — Akses Ditolak</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    {{-- redirect segera ke root --}}
    <meta http-equiv="refresh" content="0;url={{ url('/') }}">
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial;
            margin: 0;
            padding: 48px;
            text-align: center;
            background: #f3f4f6;
            color: #111
        }

        a {
            color: #2563eb;
            text-decoration: none
        }
    </style>
    <script>
        window.location.replace(@json(url('/')))
    </script>
</head>

<body>
    <h1>403 — Akses Ditolak</h1>
    <p>Anda akan diarahkan ke halaman utama. Jika tidak, klik <a href="{{ url('/') }}">kembali ke beranda</a>.</p>
</body>

</html>
