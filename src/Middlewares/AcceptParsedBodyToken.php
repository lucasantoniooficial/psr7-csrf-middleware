<?php

namespace Schnittstabil\Psr7\Csrf\Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use function Schnittstabil\Get\getValue;
use Schnittstabil\Psr7\Csrf\RequestAttributesTrait;

/**
 * Middleware for accepting CSRF tokens sent by request bodies, e.g. POST.
 */
class AcceptParsedBodyToken
{
    use RequestAttributesTrait;

    /**
     * Used to validate tokens.
     *
     * @var callable
     */
    protected $tokenValidator;

    /**
     * Used to _get_ the token.
     *
     * @var string|int|mixed[] a `Schnittstabil\Get\getValue` path
     */
    protected $path;

    /**
     * Create new AcceptParsedBodyToken middleware.
     *
     * @see https://github.com/schnittstabil/get Documentation of `Schnittstabil\Get\getValue`
     *
     * @param callable           $tokenValidator Used to validate tokens
     * @param string|int|mixed[] $path           a `Schnittstabil\Get\getValue` path
     */
    public function __construct(callable $tokenValidator, $path = 'X-XSRF-TOKEN')
    {
        $this->tokenValidator = $tokenValidator;
        $this->path = $path;
    }

    /**
     * Invoke middleware.
     *
     * @param ServerRequestInterface $request  request object
     * @param ResponseInterface      $response response object
     * @param callable               $next     next middleware
     *
     * @return ResponseInterface response object
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $token = getValue($this->path, $request->getParsedBody(), null);

        if ($token === null) {
            return $next($request, $response);
        }

        $tokenViolations = call_user_func($this->tokenValidator, $token);

        if (count($tokenViolations) === 0) {
            return $next($request->withAttribute(self::$isValidAttribute, true), $response);
        }

        $violations = $request->getAttribute(self::$violationsAttribute, []);
        $violations = array_merge($violations, $tokenViolations);

        return $next($request->withAttribute(self::$violationsAttribute, $violations), $response);
    }
}
