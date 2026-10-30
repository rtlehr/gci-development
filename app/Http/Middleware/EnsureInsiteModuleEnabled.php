<?php

namespace App\Http\Middleware;

use App\Core\Modules\ModuleCatalog;
use App\Core\Modules\ModuleDependencyResolver;
use Closure;
use InvalidArgumentException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Defense in depth for cached routes and configuration changes. */
final class EnsureInsiteModuleEnabled
{
    public function __construct(
        private readonly ModuleCatalog $catalog,
        private readonly ModuleDependencyResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next, string $id): Response
    {
        // Uninstalled or disabled modules are indistinguishable from missing routes.
        // A cached route can outlive a module selection. Fail closed even when
        // the selection is now invalid (such as a missing required dependency).
        try {
            if ($this->catalog->find($id) === null || ! in_array($id, $this->resolver->resolve(), true)) {
                abort(404);
            }
        } catch (InvalidArgumentException $exception) {
            abort(404);
        }

        return $next($request);
    }
}
