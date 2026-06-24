<?php

namespace App\Enums;

enum WalletTransactionCategory: string
{
    case ReturnRefund = 'return_refund';
    case Withdrawal = 'withdrawal';
    case WithdrawalRefund = 'withdrawal_refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::ReturnRefund => 'Return Refund',
            self::Withdrawal => 'Withdrawal',
            self::WithdrawalRefund => 'Withdrawal Refund',
            self::Adjustment => 'Adjustment',
        };
    }
}
