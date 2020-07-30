<?php

namespace App\Http\Repositories;

use App\Models\User;
use Prettus\Repository\Eloquent\BaseRepository;

class UserRepository extends BaseRepository
{
    /**
     * Define model
     *
     * @return string
     */
    public function model()
    {
        return User::class;
    }
}
