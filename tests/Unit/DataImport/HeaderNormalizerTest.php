<?php

use App\Services\DataImport\HeaderNormalizer;

test('data import headers trim and collapse whitespace', function () {
    $normalizer = new HeaderNormalizer();

    expect($normalizer->normalize("  Extra Check \n Submitted  "))->toBe('Extra Check Submitted')
        ->and($normalizer->normalize("ALT\tFirst   Name"))->toBe('ALT First Name')
        ->and($normalizer->normalize(null))->toBe('');
});
