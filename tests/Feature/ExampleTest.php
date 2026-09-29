<?php

test('halaman awal dialihkan ke login bila belum masuk', function () {
    $this->get('/')->assertRedirect(route('login'));
});
