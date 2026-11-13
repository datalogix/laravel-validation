<?php

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/lang',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withPreparedSets(deadCode: true, codeQuality: true, earlyReturn: true)
    ->withSkip([SafeDeclareStrictTypesRector::class]);
