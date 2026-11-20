<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>آزمایش رابط جدید فرصت‌ها | هم‌ره</title>
    @vite('resources/js/prototypes/opportunities.js')
</head>
<body>
    <div id="hm-opportunities" data-endpoint="{{ $dataUrl }}"></div>
</body>
</html>
