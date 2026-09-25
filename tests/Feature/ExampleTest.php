<?php

test('the root url describes the api', function () {
    $this->get('/')
        ->assertOk()
        ->assertJsonStructure(['name', 'api']);
});
