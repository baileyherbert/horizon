<?php

namespace Horizon\Http\Middleware;

use Horizon\Http\Middleware;
use Horizon\Http\Request;
use Horizon\Http\Response;
use Horizon\Http\Cookie\TokenMismatchException;

class VerifyCsrfToken extends Middleware
{

    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() == 'POST' || $request->getMethod() == 'PUT' || $request->getMethod() == 'DELETE') {
            $token = $request->session()->csrf();
            $posted = $this->getPostedToken();

            if ($posted !== $token) {
                throw new TokenMismatchException();
            }
        }
    }

    /**
     * Gets the token sent by the user in the current request, checking both posted input and headers.
     *
     * @return string|null
     */
    private function getPostedToken()
    {
        $post = $this->getRequest()->input('_token');
        $header = $this->getRequest()->header('x-csrf-token');

        if (!is_null($post)) return $post;
        return $header;
    }

}
