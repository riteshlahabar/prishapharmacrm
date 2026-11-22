<?php

namespace App\Modules\Visits\Enums;

/**
 * Why the MR called. An enum rather than free text: the whole point of the
 * visit report is counting purposes, and typed-in strings do not group.
 * Adding a case here is all a new purpose needs.
 */
enum VisitPurpose: string
{
    case Introduction = 'introduction';
    case ProductDetailing = 'product_detailing';
    case SampleDrop = 'sample_drop';
    case OrderFollowUp = 'order_follow_up';
    case PaymentFollowUp = 'payment_follow_up';
    case Courtesy = 'courtesy';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Introduction => 'Introduction',
            self::ProductDetailing => 'Product detailing',
            self::SampleDrop => 'Sample drop',
            self::OrderFollowUp => 'Order follow-up',
            self::PaymentFollowUp => 'Payment follow-up',
            self::Courtesy => 'Courtesy call',
            self::Other => 'Other',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
