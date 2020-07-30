<?php

namespace App\Http\Requests;

use App\Http\Requests\Core\BaseRequest;

/**
 * Class UserStoreRequest
 *
 * @package App\Modules\V1\User\Requests
 */
class UserStoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required|between:8,16'
        ];
    }
}
