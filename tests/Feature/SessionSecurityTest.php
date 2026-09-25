<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\PreventBackHistory;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only disposable in-memory tables; never migrate or access the project DB.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
            'session.driver' => 'array',
        ]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $tables = [
            'users' => ['username', 'email', 'password', 'role', 'status'],
            'teachers' => ['user_id', 'firstname', 'lastname', 'school_name'],
            'activity_logs' => ['user_id', 'role', 'action', 'module', 'description', 'ip_address', 'user_agent'],
        ];
        foreach ($tables as $table => $columns) {
            DB::connection()->getSchemaBuilder()->create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $column) {
                    $t->string($column)->nullable();
                }
                $t->timestamps();
            });
        }
    }

    public static function roles(): array
    {
        return ['teacher' => ['teacher'], 'student' => ['student'], 'admin' => ['admin']];
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => 'security_'.$role,
            'password' => Hash::make('secret123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function assertNoCache(TestResponse $response): void
    {
        foreach (['no-store', 'no-cache', 'must-revalidate'] as $directive) {
            $this->assertTrue($response->headers->hasCacheControlDirective($directive), $directive);
        }
        $this->assertSame('0', (string) $response->headers->getCacheControlDirective('max-age'));
        $response->assertHeader('Pragma', 'no-cache')->assertHeader('Expires', '0');
    }

    public function test_login_form_is_not_cached_and_has_bfcache_safeguard(): void
    {
        $response = $this->get(route('login'))->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee("window.addEventListener('pageshow'", false);
        $this->assertNoCache($response);
    }

    #[DataProvider('roles')]
    public function test_login_rotates_session_and_returning_to_login_redirects_by_role(string $role): void
    {
        $user = $this->user($role);
        $this->get(route('login'))->assertOk();
        $oldId = session()->getId();
        $oldToken = session()->token();

        $response = $this->post(route('login.post'), [
            'username' => $user->username, 'password' => 'secret123',
        ])->assertRedirect(route($role.'.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->assertNoCache($response);

        // Re-resolve the guard so redirects depend on session authentication.
        $this->withCookie(session()->getName(), session()->getId());
        Auth::forgetGuards();
        $this->assertNoCache($this->get(route('login'))->assertRedirect(route($role.'.dashboard')));
        $this->post(route('login.post'), ['username' => 'another', 'password' => 'wrong'])
            ->assertRedirect(route($role.'.dashboard'));
        $this->get(route('register'))->assertRedirect(route($role.'.dashboard'));
        $this->post(route('register.post'), [])->assertRedirect(route($role.'.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('roles')]
    public function test_logout_destroys_session_and_old_dashboard_requests_redirect(string $role): void
    {
        $user = $this->user($role);
        $this->post(route('login.post'), [
            'username' => $user->username, 'password' => 'secret123',
        ])->assertRedirect(route($role.'.dashboard'));
        $this->withSession(['private_marker' => 'must disappear']);
        $oldId = session()->getId();
        $oldToken = session()->token();

        $this->withCookie(session()->getName(), $oldId);
        $response = $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->assertFalse(session()->has('private_marker'));
        $this->assertEmpty(session()->getHandler()->read($oldId));
        $this->assertNoCache($response);

        $this->withCookie(session()->getName(), session()->getId());
        Auth::forgetGuards();
        // Back revalidation, direct URL entry, and refresh all hit the same guard.
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->assertNoCache($this->get(route($role.'.dashboard'))->assertRedirect(route('login')));
        }
        // Replaying the retired session ID must not restore authentication.
        session()->flush();
        $this->withCookie(session()->getName(), $oldId);
        Auth::forgetGuards();
        $this->get(route($role.'.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_registration_also_rotates_the_auto_login_session(): void
    {
        $this->get(route('register'))->assertOk();
        $oldId = session()->getId();
        $oldToken = session()->token();
        $this->post(route('register.post'), [
            'firstname' => 'Test', 'lastname' => 'Teacher', 'school_name' => 'Test School',
            'email' => 'security@example.test', 'username' => 'registered_teacher',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticated();
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
    }

    public function test_invalid_credentials_never_authenticate(): void
    {
        $user = $this->user('teacher');
        $this->from(route('login'))->post(route('login.post'), [
            'username' => $user->username, 'password' => 'incorrect',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->get(route('teacher.dashboard'))->assertRedirect(route('login'));
    }

    public function test_every_role_route_requires_authentication_and_cannot_be_cached(): void
    {
        $this->get(route('login'))->assertOk();
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (! preg_match('/^(teacher|student|admin)\//', $route->uri(), $match)) {
                continue;
            }
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, $route->uri());
            $this->assertContains('role:'.$match[1], $middleware, $route->uri());
            $this->assertContains(PreventBackHistory::class, app('router')->gatherRouteMiddleware($route));
            $uri = '/'.preg_replace('/\{[^}]+\}/', '999999', $route->uri());
            $this->assertNoCache($this->call($route->methods()[0], $uri)->assertRedirect(route('login')));
            $checked++;
        }
        $this->assertGreaterThan(50, $checked);
    }

    public static function roleAccess(): array
    {
        return [
            ['student', 'teacher', 403], ['student', 'admin', 403],
            ['teacher', 'admin', 403], ['teacher', 'student', 403],
            ['admin', 'student', 403],
            ['teacher', 'teacher', 200], ['student', 'student', 200],
            ['admin', 'admin', 200], ['admin', 'teacher', 200],
        ];
    }

    #[DataProvider('roleAccess')]
    public function test_role_access_and_authenticated_cache_headers(string $role, string $target, int $status): void
    {
        // Exercise the actual route and middleware without unrelated analytics queries.
        if ($status === 200) {
            $controller = $target === 'admin' ? AdminController::class : DashboardController::class;
            $method = $target === 'admin' ? 'dashboard' : $target.'Dashboard';
            $this->mock($controller)->shouldReceive($method)->once()->andReturn(response('Protected dashboard'));
        }
        $this->actingAs($this->user($role));
        $this->assertNoCache($this->get(route($target.'.dashboard'))->assertStatus($status));
    }

    public function test_logout_requires_post_and_a_valid_csrf_token(): void
    {
        $user = $this->user('teacher');
        $this->actingAs($user);
        $this->get(route('logout'))->assertStatus(405);
        $this->assertAuthenticatedAs($user);

        // Laravel skips CSRF in tests; enable its real check for this scenario.
        $this->app->instance('env', 'local');
        $this->post(route('logout'))->assertStatus(419);
        $this->assertAuthenticatedAs($user);
        $this->withSession(['_token' => 'valid-test-token']);
        $this->post(route('logout'), ['_token' => 'valid-test-token'])->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
