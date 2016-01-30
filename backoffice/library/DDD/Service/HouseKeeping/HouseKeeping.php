<?php

namespace DDD\Service\HouseKeeping;

use DDD\Service\ServiceBase;

/**
 * Class HouseKeeping
 * @package DDD\Service\HouseKeeping
 *
 * @author Tigran Petrosyan
 */
class HouseKeeping extends ServiceBase
{
    const INCIDENT_REPORT_EVIDENCE_OF_SMOKING    = 1;
    const INCIDENT_REPORT_KEYS_WERE_NOT_RETURNED = 3;
    const INCIDENT_REPORT_DIRTY_HOUSE            = 4;
    const INCIDENT_REPORT_BROKEN_FURNITURE       = 5;
    const INCIDENT_REPORT_OTHER                  = 999;
}
