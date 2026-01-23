<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>飞猫选品</title>
    @routess
    @vite(['resources/js/app.js'])
</head>
<body>
<div id="app"></div>
</body>
</html>
