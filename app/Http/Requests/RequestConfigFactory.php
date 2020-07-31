<?php


namespace App\Http\Requests;

use App\Http\Requests\Core\BaseRequest;

class RequestConfigFactory
{
    // Request route names
    protected $namingRoute = [
        'auth.register' => UserStoreRequest::class,
    ];

    private $routeName;

    /**
     * RequestConfigFactory constructor.
     *
     * @param $routeName string route name
     */
    public function __construct($routeName)
    {
        $this->routeName = $routeName;
    }

    /**
     * Get config instance
     *
     * @return mixed|null
     */
    public function getConfig()
    {
        if (isset($this->namingRoute[$this->routeName])) {
            $routeClass = $this->namingRoute[$this->routeName];
            return (new $routeClass);
        }

        return new BaseRequest();
    }
}
