<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    protected $forceJsonResponse = true;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Attributes
     *
     * @return array
     */
    public function attributes()
    {
        return $this->getRequestConfig()->attributes();
    }

    /**
     * Messages
     *
     * @return array
     */
    public function messages()
    {
        return $this->getRequestConfig()->messages();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return $this->getRequestConfig()->rules();
    }

    /**
     * Get config of Request
     *
     * @return mixed|null
     */
    private function getRequestConfig()
    {
        $routeName = $this->route()->getName();
        return (new RequestConfigFactory($routeName))->getConfig();
    }
}
