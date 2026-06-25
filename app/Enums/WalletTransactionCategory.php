<?php

namespace App\Enums;

enum WalletTransactionCategory: string
{
    case ReturnRefund = 'return_refund';
    case OrderReturnDeduction = 'order_return_deduction';
    case Withdrawal = 'withdrawal';
    case WithdrawalRefund = 'withdrawal_refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::ReturnRefund => 'Return Refund',
            self::OrderReturnDeduction => 'Order Return Deduction',
            self::Withdrawal => 'Withdrawal',
            self::WithdrawalRefund => 'Withdrawal Refund',
            self::Adjustment => 'Adjustment',
        };
    }
}
