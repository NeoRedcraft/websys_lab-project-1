<?php
$urls = ['/', '/signin', '/signup', '/dashboard'];
foreach ($urls as $p) {
    $u = 'http://localhost:8000' . $p;
    $c = @file_get_contents($u);
    echo $u . ' -> ' . ($c === false ? 'FAIL' : 'OK') . PHP_EOL;
}
