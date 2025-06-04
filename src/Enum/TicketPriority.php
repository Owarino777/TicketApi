<?php
namespace App\Enum;

enum TicketPriority: string
{
    case LOW = 'basse';
    case NORMAL = 'normale';
    case HIGH = 'haute';
}
