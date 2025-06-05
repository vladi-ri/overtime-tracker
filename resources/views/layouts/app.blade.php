<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Overtime Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Vladislav Riemer">
    <meta name="description" content="A simple Laravel application to track daily working hours and overtime.">
    <meta name="keywords" content="Laravel, Overtime Tracker, Time Management, Work Hours">

    <!-- Font Awesome 4.7.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ URL::asset('css/app.css') }}">
    <style>
        body { background: #f5f5f5; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
    </style>
</head>
<body>
    @yield('content')
    <!-- Bootstrap JS Bundle CDN (optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>