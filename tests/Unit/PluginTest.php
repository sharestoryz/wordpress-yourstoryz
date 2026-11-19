<?php

use Brain\Monkey\Actions;
use YourStoryz\Plugin;

it('registers itself on plugins_loaded', function () {
    Actions\expectAdded('plugins_loaded')->once();

    (new Plugin)->boot();
});
