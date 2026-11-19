<?php

use YourStoryz\Vendor\YourStoryz\PhpSdk\YourStoryz;

it('loads the prefixed SDK', function () {
    expect(new YourStoryz('x'))->toBeInstanceOf(YourStoryz::class);
});
