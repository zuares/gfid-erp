<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function testSample($gTpl, $outPath) {
    $pdf = new \setasign\Fpdi\Fpdi();
    $pdf->AddPage('P', [100, 150]);
    $tplPath = storage_path('app/public/templates/greetings/template_' . $gTpl . '.png');
    $m = 4;
    $pdf->Image($tplPath, $m, $m, 100 - ($m * 2), 150 - ($m * 2));
    file_put_contents($outPath, $pdf->Output('S'));
}

testSample('1', '/tmp/sample1.pdf');
testSample('2', '/tmp/sample2.pdf');
testSample('3', '/tmp/sample3.pdf');
echo "Done";
