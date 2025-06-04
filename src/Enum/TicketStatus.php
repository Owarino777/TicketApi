<?php
namespace App\Enum;

enum TicketStatus: string
{
    case PENDING = 'pending';
    case WAITING = 'waiting';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
}
