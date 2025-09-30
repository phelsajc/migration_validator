<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;

class UsersModel extends Eloquent implements AuthenticatableContract
//class UsersModel extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $connection = 'mongodb';
    protected $collection = 'users'; // optional: set your collection name
    protected $fillable = ['name', 'lastname', 'loginid']; // Fields you want to make mass-assignable
}
