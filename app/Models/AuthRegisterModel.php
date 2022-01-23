<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthRegisterModel extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'document_number',
        'password'
    ];
}
