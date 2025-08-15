<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Models\Satpam;
use App\Http\Controllers\Auth\LoginController;
use PHPUnit\Framework\Attributes\Test;

class LoginControllerTest extends TestCase
{
    #[Test]
    public function login_admin_redirect_ke_dashboard_admin_dengan_data_db_sudah_ada(): void
    {
        $user = Satpam::where('username', 'admin')->first();
        if (!$user) {
            $this->markTestSkipped('User admin tidak ditemukan di database test.');
        }

        $request = Request::create('/login', 'POST', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $controller = new LoginController();
        $response   = $controller->login($request);

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(route('dashboard.admin'), $response->getTargetUrl());
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function login_admin_gagal_password_salah(): void
    {
        $user = Satpam::where('username', 'admin')->first();
        if (!$user) {
            $this->markTestSkipped('User admin tidak ditemukan di database test.');
        }

        $request = Request::create('/login', 'POST', [
            'username' => 'admin',
            'password' => 'password_yang_salah',
        ]);

        $controller = new LoginController();
        $response   = $controller->login($request);

        // harus redirect back dengan errors
        $this->assertTrue($response->isRedirect());
        $this->assertTrue($response->getSession()->has('errors'));
        $this->assertGuest();
    }

    #[Test]
    public function login_kepala_satpam_berhasil_redirect_ke_dashboard_kepala(): void
    {
        $user = Satpam::where('username', 'marvelgantengpol')->where('role', 'Kepala Satpam')->first();
        if (!$user) {
            $this->markTestSkipped('User kepala satpam (marvelgantengpol) tidak ditemukan di database test.');
        }

        $request = Request::create('/login', 'POST', [
            'username' => 'marvelgantengpol',
            'password' => 'marvel123',
        ]);

        $controller = new LoginController();
        $response   = $controller->login($request);

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(route('dashboard.kepala'), $response->getTargetUrl());
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function login_kepala_satpam_gagal_password_salah(): void
    {
        $user = Satpam::where('username', 'marvelgantengpol')->where('role', 'Kepala Satpam')->first();
        if (!$user) {
            $this->markTestSkipped('User kepala satpam (marvelgantengpol) tidak ditemukan di database test.');
        }

        $request = Request::create('/login', 'POST', [
            'username' => 'marvelgantengpol',
            'password' => 'password_salah',
        ]);

        $controller = new LoginController();
        $response   = $controller->login($request);

        $this->assertTrue($response->isRedirect());
        $this->assertTrue($response->getSession()->has('errors'));
        $this->assertGuest();
    }

    #[Test]
    public function login_satpam_berhasil_redirect_ke_dashboard_satpam(): void
    {
        $user = Satpam::where('username', 'noel')->where('role', 'Satpam')->first();
        if (!$user) {
            $this->markTestSkipped('User satpam (noel) tidak ditemukan di database test.');
        }

        $request = Request::create('/login', 'POST', [
            'username' => 'noel',
            'password' => 'noelgay123',
        ]);

        $controller = new LoginController();
        $response   = $controller->login($request);

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(route('dashboard.satpam'), $response->getTargetUrl());
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function login_satpam_gagal_password_salah(): void
    {
        $user = Satpam::where('username', 'noel')->where('role', 'Satpam')->first();
        if (!$user) {
            $this->markTestSkipped('User satpam (noel) tidak ditemukan di database test.');
        }

        $request = Request::create('/login', 'POST', [
            'username' => 'noel',
            'password' => 'password_salah',
        ]);

        $controller = new LoginController();
        $response   = $controller->login($request);

        $this->assertTrue($response->isRedirect());
        $this->assertTrue($response->getSession()->has('errors'));
        $this->assertGuest();
    }
}
