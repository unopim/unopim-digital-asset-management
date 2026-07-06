<?php

it('prints the installed DAM version from the changelog', function () {
    $this->artisan('dam:version')
        ->expectsOutputToContain('3.0')
        ->assertSuccessful();
});
