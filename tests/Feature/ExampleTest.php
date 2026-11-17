<?php

test('guests visiting the home page are sent to the login page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});
