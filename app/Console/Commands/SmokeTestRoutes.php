<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SmokeTestRoutes extends Command
{
    protected $signature = 'routes:smoke
                            {--email= : Login as this user email}
                            {--guest : Only test public routes as guest}
                            {--failures-only : Show only failing routes}';

    protected $description = 'Smoke-test GET routes via the HTTP kernel and report server errors';

    protected int $passed = 0;

    protected int $skipped = 0;

    protected int $failed = 0;

    /** @var array<int, array<string, mixed>> */
    protected array $failures = [];

    public function handle(Kernel $kernel): int
    {
        $user = $this->resolveUser();

        if ($user) {
            $this->info('Authenticated as: ' . $user->email . ' (' . $user->role . ')');
        } else {
            $this->info('Running as guest (public routes only).');
        }

        $routes = collect(app('router')->getRoutes())
            ->filter(fn (Route $route) => in_array('GET', $route->methods(), true) || in_array('HEAD', $route->methods(), true))
            ->filter(fn (Route $route) => $this->shouldTestRoute($route))
            ->values();

        $this->line('Testing ' . $routes->count() . ' GET routes...');
        $bar = $this->output->createProgressBar($routes->count());
        $bar->start();

        foreach ($routes as $route) {
            $this->exerciseRoute($kernel, $route, $user);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Result', 'Count'],
            [
                ['Passed', $this->passed],
                ['Skipped (unresolved params)', $this->skipped],
                ['Failed', $this->failed],
            ]
        );

        if ($this->failures !== [] && ($this->failed > 0 || ! $this->option('failures-only'))) {
            $this->newLine();
            $this->error('Failures:');
            $this->table(['Route', 'URI', 'Status', 'Detail'], array_map(function ($row) {
                return [$row['name'], $row['uri'], $row['status'], $row['detail']];
            }, $this->failures));
        }

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function resolveUser(): ?User
    {
        if ($this->option('guest')) {
            return null;
        }

        $email = $this->option('email');

        if ($email) {
            return User::where('email', $email)->first();
        }

        return User::query()
            ->where('is_super_admin', false)
            ->whereNotNull('business_id')
            ->where('role', 'owner')
            ->where('is_active', true)
            ->first();
    }

    protected function shouldTestRoute(Route $route): bool
    {
        $uri = $route->uri();

        if (Str::startsWith($uri, ['_ignition', 'sanctum'])) {
            return false;
        }

        return true;
    }

    protected function exerciseRoute(Kernel $kernel, Route $route, ?User $user): void
    {
        $name = $route->getName();
        $uri = $route->uri();

        try {
            $parameters = $this->resolveParameters($route, $user);

            if ($parameters === null) {
                $this->skipped++;

                return;
            }

            $path = $name
                ? route($name, $parameters, false)
                : '/' . ltrim($route->uri(), '/');
            $request = Request::create($path, 'GET');
            $request->setUserResolver(fn () => $user);

            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            $status = $response->getStatusCode();

            if ($this->isSuccessStatus($status)) {
                $this->passed++;

                return;
            }

            $this->recordFailure($name, $uri, (string) $status, $this->summarizeBody($response));
        } catch (\Throwable $e) {
            $this->recordFailure($name ?? $uri, $uri, 'EXC', $e->getMessage());
        }
    }

    protected function isSuccessStatus(int $status): bool
    {
        return in_array($status, [
            Response::HTTP_OK,
            Response::HTTP_FOUND,
            Response::HTTP_SEE_OTHER,
            Response::HTTP_TEMPORARY_REDIRECT,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_UNAUTHORIZED,
        ], true);
    }

    protected function recordFailure(?string $name, string $uri, string $status, string $detail): void
    {
        $this->failed++;
        $this->failures[] = [
            'name' => $name ?? '—',
            'uri' => $uri,
            'status' => $status,
            'detail' => mb_substr(preg_replace('/\s+/', ' ', $detail), 0, 160),
        ];
    }

    protected function summarizeBody(Response $response): string
    {
        $content = $response->getContent() ?: '';

        if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
            return strip_tags($matches[1]);
        }

        if (Str::contains($content, 'SQLSTATE')) {
            if (preg_match('/SQLSTATE[^<]+/', $content, $matches)) {
                return $matches[0];
            }
        }

        return 'Unexpected HTTP response';
    }

    /**
     * @return array<string, mixed>|null Null when required parameters cannot be resolved.
     */
    protected function resolveParameters(Route $route, ?User $user): ?array
    {
        $parameters = [];
        $business = $user && $user->business ? $user->business : Business::query()->first();
        $product = $business
            ? Product::withoutGlobalScopes()->where('business_id', $business->id)->whereNull('parent_id')->first()
            : null;
        $customer = $business
            ? Customer::withoutGlobalScopes()->where('business_id', $business->id)->first()
            : null;
        $affiliate = Affiliate::query()->where('is_active', true)->first();

        foreach ($route->parameterNames() as $name) {
            $value = $this->resolveParameterValue($name, $business, $product, $customer, $affiliate, $user);

            if ($value === null) {
                return null;
            }

            $parameters[$name] = $value;
        }

        return $parameters;
    }

    protected function resolveParameterValue(
        string $name,
        ?Business $business,
        ?Product $product,
        ?Customer $customer,
        ?Affiliate $affiliate,
        ?User $user
    ) {
        switch ($name) {
            case 'business':
                return $business ? $business->slug : null;
            case 'product':
                return $product ? $product->getKey() : null;
            case 'customer':
                return $customer ? $customer->getKey() : null;
            case 'date':
                return now()->toDateString();
            case 'code':
                return $affiliate ? $affiliate->code : 'admin';
            case 'entity':
                return 'products';
            case 'record':
                return 1;
            case 'portal':
                return $business ? $business->portal_slug : null;
            case 'branch':
            case 'brand':
            case 'employee':
            case 'expense':
            case 'reconciliation':
            case 'attribute':
            case 'affiliate':
            case 'kitchenOrder':
            case 'restaurantTable':
            case 'waiter':
            case 'user':
                return 1;
            case 'reference':
                return 'test-reference';
            default:
                return null;
        }
    }
}
