<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - iPart Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Sans+Thai+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Noto Sans"', '"Noto Sans Thai Looped"',
                               'ui-sans-serif', 'system-ui', '-apple-system',
                               '"Segoe UI"', 'Roboto', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <style>
        body { line-height: 1.6; }
        html { font-family: 'Noto Sans', 'Noto Sans Thai Looped', ui-sans-serif, system-ui, sans-serif; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800">
<div class="max-w-3xl mx-auto px-6 py-12">
    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline mb-8"><i class="fas fa-arrow-left"></i> กลับหน้าแรก</a>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 sm:p-10">
        @yield('content')
    </div>
    <p class="text-center text-xs text-gray-400 mt-8">© {{ date('Y') }} iPart Store. All rights reserved.</p>
</div>
</body>
</html>
