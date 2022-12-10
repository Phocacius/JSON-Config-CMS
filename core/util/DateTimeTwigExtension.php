<?php

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateTimeTwigExtension extends AbstractExtension {

    public function getFilters() {
        return [
            new TwigFilter('format_datetime', [$this, 'formatDatetime']),
        ];
    }

    public function formatDatetime($receiver, string $pattern, string $locale): string {
        $date = date_parse($receiver);
        $months = [
            "Januar",
            "Februar",
            "März",
            "April",
            "Mai",
            "Juni",
            "Juli",
            "August",
            "September",
            "Oktober",
            "November",
            "Dezember"
        ];
        return $date["day"].". ".$months[$date["month"]-1]." ".$date["year"];
    }

}