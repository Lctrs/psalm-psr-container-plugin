<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Ergebnis\License\Holder;
use Ergebnis\License\Range;
use Ergebnis\License\Type\MIT;
use Ergebnis\License\Url;
use Ergebnis\License\Year;

$license = MIT::markdown(
    __DIR__ . '/LICENSE.md',
    Range::since(
        Year::fromString('2020'),
        new DateTimeZone('UTC')
    ),
    Holder::fromString('Jérôme Parmentier'),
    Url::fromString('https://github.com/Lctrs/psalm-psr-container-plugin')
);

$license->save();
