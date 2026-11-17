<?php

namespace App;

enum UserType: string
{
    case Admin = 'admin';
    case Cashier = 'cashier';
    case Encoder = 'encoder';
}
