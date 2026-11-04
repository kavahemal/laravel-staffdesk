<?php

use function Pest\Laravel\get;

it('redirects the home page to login', function () {
    get('/')->assertRedirect('/login');
});
