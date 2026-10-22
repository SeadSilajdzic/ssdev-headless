<?php

namespace Ssdev\Headless\Features;

enum Feature: string
{
    case TwoFactor = 'two-factor';
    case Roles = 'roles';
    case Users = 'users';
    case Teams = 'teams';
}
