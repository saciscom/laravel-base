<?php

namespace App\Http\Requests\Core;

class BaseRequest
{

    /**
     * Rules
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Attributes
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Messages
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
