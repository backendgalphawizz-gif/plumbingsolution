<?php

namespace App\Enums;

enum OtpType: string
{
    case Login = 'login';
    case Register = 'register';
    case VendorLogin = 'vendor_login';
    case VendorRegister = 'vendor_register';
    case ProviderLogin = 'provider_login';
    case ProviderRegister = 'provider_register';

    public function isVendor(): bool
    {
        return in_array($this, [self::VendorLogin, self::VendorRegister], true);
    }

    public function isProvider(): bool
    {
        return in_array($this, [self::ProviderLogin, self::ProviderRegister], true);
    }

    public function isUser(): bool
    {
        return in_array($this, [self::Login, self::Register], true);
    }

    public function isLogin(): bool
    {
        return in_array($this, [self::Login, self::VendorLogin, self::ProviderLogin], true);
    }

    public function isRegister(): bool
    {
        return in_array($this, [self::Register, self::VendorRegister, self::ProviderRegister], true);
    }
}
