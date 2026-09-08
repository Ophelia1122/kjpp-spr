<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Sebelum ada Auth, "/" langsung return 200 (halaman dashboard publik).
     * Sekarang "/" dilindungi middleware 'auth', jadi guest akan di-redirect
     * (302) ke halaman login — itu perilaku yang BENAR, bukan bug. Test
     * lama yang expect 200 langsung sudah tidak relevan lagi.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
