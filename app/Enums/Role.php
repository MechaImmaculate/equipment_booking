<?php
namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case ResourceManager = 'resource_manager';
    case Employee = 'employee';
}