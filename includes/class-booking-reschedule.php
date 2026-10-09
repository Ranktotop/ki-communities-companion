<?php
/**
 * Button beim Verschieben eines Termins (FluentBooking).
 *
 * Hat ein Termintyp einen eigenen Button-Text (z. B. „Quickcheck buchen“), zeigt FluentBooking ihn auch auf der Seite
 * zum Verschieben, wo eigentlich „Verschieben bestätigen“ stehen sollte. FluentBooking setzt für diesen Fall selbst
 * den Text „Confirm Reschedule“, der eigene Button-Text überdeckt ihn aber. Beim Verschieben wird der eigene Text daher
 * durch FluentBookings eigenen, übersetzten Text ersetzt (Filter fluent_booking/public_event_vars, nach FluentBooking).
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Booking_Reschedule
{
    public static function init()
    {
        add_filter('fluent_booking/public_event_vars', [__CLASS__, 'button_text'], 20, 1);
    }

    public static function button_text($vars)
    {
        if (!is_array($vars) || ($vars['rescheduling'] ?? '') !== 'yes') {
            return $vars;
        }

        $text = __('Confirm Reschedule', 'fluent-booking');
        if (!empty($vars['slot']['settings']['submit_button_text'])) {
            $vars['slot']['settings']['submit_button_text'] = $text;
        }
        if (!empty($vars['settings']['submit_button_text'])) {
            $vars['settings']['submit_button_text'] = $text;
        }

        return $vars;
    }
}
