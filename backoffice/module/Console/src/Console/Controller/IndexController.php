<?php

/**
 * Description of Index Controller
 *
 * @author tigran.tadevosyan
 */

namespace Console\Controller;

use Library\Controller\ConsoleBase;
use Zend\Text\Figlet\Figlet;
use Zend\View\Model\ConsoleModel;
use Zend\Console\Adapter as Console;
use Zend\Console\Console as ConsoleStatic;
use Zend\Console\Adapter\Virtual;
use Zend\Console\ColorInterface as Color;

/**
 * Class IndexController
 * @package Console\Controller
 */
class IndexController extends ConsoleBase
{
    public function usageAction()
    {
        // get run script name
        $script = basename($this->request->getScriptName());
        if(ConsoleStatic::isWindows()){
            $script .= '.bat';
        }else{
            $script = './'.$script;
        }

        $figletText = new Figlet();
        $figletText->setFont('/ginosi/backoffice/module/Console/data/fonts/doom.flf');
        $asciiGinosi = $figletText->render('        GINOSI');

        return <<<USAGE

 \e[0;37m----------------------------------------------------------\e[0m
$asciiGinosi\e[3;37m          ✡  Ginosi Backoffice Console (GBC)  ✡          \e[0m
 \e[0;37m----------------------------------------------------------\e[0m

 \e[0;37mUsage:\e[0m

    \e[0;36mginosole\e[0m \e[0;35mcurrency show\e[0m
    \e[0;36mginosole\e[0m \e[0;35mreservation-email send-ki --id=4651 -v\e[0m
    \e[0;36mginosole\e[0m \e[0;35m[ --usage | --help | -h ]\e[0m

 \e[0;37mParameters:\e[0m

    \e[1;33mcurrency show\e[0m                  \e[2;33m- show all currencies\e[0m
    \e[1;33mcurrency update\e[0m                \e[2;33m- update currencies\e[0m
    \e[1;33mcurrency check\e[0m                 \e[2;33m- currency been updated in the last 30 hours? (--nosend)\e[0m

    \e[1;33mcurrency update-currency-vault\e[0m \e[2;33m- grab current currency values and save\e[0m

    \e[1;33mreservation-email check-review\e[0m \e[2;33m- show review for sent (--id=BID|otherwise all)\e[0m
    \e[1;33mreservation-email send-review\e[0m  \e[2;33m- send the necessary reviews (--id=BID|otherwise all)\e[0m

    \e[1;33mreservation-email check-ki\e[0m     \e[2;33m- show key instructions for sent (--id=BID|otherwise all)\e[0m
    \e[1;33mreservation-email send-ki\e[0m      \e[2;33m- send the necessary key instructions (--id=BID|otherwise all) (-bo)\e[0m

    \e[1;33mreservation-email check-confirmation\e[0m    \e[2;33m- show info about booking (--id=BID|otherwise all)\e[0m
    \e[1;33mreservation-email send-guest\e[0m         \e[2;33m- send reservation mail to Customer --id=BID (-bo)\e[0m
    \e[1;33mreservation-email send-ginosi\e[0m        \e[2;33m- send reservation mail to Ginosi --id=BID (--ccp=yes) (-bo)\e[0m
    \e[1;33mreservation-email send-overbooking\e[0m        \e[2;33m- check if reservation is overbooking and send email with info --id=BID (--ccp=yes) (-bo)\e[0m
    \e[1;33mreservation-email send-ccca\e[0m   \e[2;33m- send CCCA link to a customer [--id=resId] [--ccca_id=CCCAID] [--email=customer email]\e[0m
    \e[1;33mreservation-email show-modification\e[0m              \e[2;33m- show modification booking --id=BID\e[0m
    \e[1;33mreservation-email send-update-payment-details-guest\e[0m         \e[2;33m- send link for input new CC info to Customer --id=BID\e[0m
    \e[1;33mreservation-email send-payment-details-updated-ginosi\e[0m      \e[2;33m- send new CC confirmation mail to Ginosi --id=BID --ccp=yes\e[0m
    \e[1;33mreservation-email send-modification-cancel\e[0m       \e[2;33m- send cancellation mail to Ginosi and/or Customer if(!over) --id=BID --ginosi --booker\e[0m
    \e[1;33mreservation-email send-modification-ginosi\e[0m       \e[2;33m- send modification mail to Ginosi --id=BID --shifted\e[0m
    \e[1;33mreservation-email send-receipt\e[0m            \e[2;33m- sends charges receipt to the customer\e[0m


    \e[1;33missues show\e[0m                    \e[2;33m- show all detected issues (--id=BID|otherwise all)\e[0m
    \e[1;33missues detect\e[0m                  \e[2;33m- detect issue for selected thicket --id=BID\e[0m
    \e[1;33missues force-resolve\e[0m           \e[2;33m- remove all issues for selected thicket --id=BID\e[0m

    \e[1;33mchm pullreservation\e[0m            \e[2;33m- get new reservations via Channel Manager\e[0m

    \e[1;33mavailability update-monthly\e[0m    \e[2;33m- update availability monthly\e[0m
    \e[1;33mavailability repair\e[0m            \e[2;33m- update availability by date range and/or rate_id [--date-from=DATE_FROM] [--date-to=DATE_TO] [--rate-id=RATE_ID]\e[0m
    \e[1;33mavailability update-monthly-apartel\e[0m    \e[2;33m- update availability monthly for apartel\e[0m

    \e[1;33mbooking firstcharge\e[0m            \e[2;33m- executes first charging for the reservations that are not in flexible period and do not have charge yet\e[0m
    \e[1;33mbooking clear-links\e[0m            \e[2;33m- clear expired edit links\e[0m
    \e[1;33mbooking check-reservation-balances\e[0m \e[2;33m- Calculate reservation balances and check whether the saved one is correct \e[0m

    \e[1;33muser send-login-details\e[0m        \e[2;33m- generate new password and send details to user email\e[0m
    \e[1;33muser calculate-vacation-days\e[0m  \e[2;33m- calculate vacation days for active employees\e[0m
    \e[1;33muser show\e[0m                      \e[2;33m- show user info [--id=USER ID]\e[0m
    \e[1;33muser update-schedule-inventory\e[0m \e[2;33m- update schedule inventory for active employees\e[0m

    \e[1;33mcrawler update\e[0m                 \e[2;33m- update ota connection status\e[0m
    \e[1;33mcrawler check\e[0m                  \e[2;33m- check ota connection status and update only last edit date [--product=apartment|apartel] [--identity=ID,ID] [--ota=ID,ID]\e[0m

    \e[1;33mapartment check-performance\e[0m    \e[2;33m- checks all selling apartments performance for last month, and if result is negative, add notification for those who have the appropriate role\e[0m
    \e[1;33mapartment documents-after-sixty-days-expiring \e[0m    \e[2;33m- loops through apartment documents and sends notification to group managers if document expiration date == 60 days\e[0m
    \e[1;33mapartment correct-apartment-reviews \e[0m    \e[2;33m- loops through all active apartments and corrects their average review score\e[0m

    \e[1;33mbuilding check-performance\e[0m     \e[2;33m- checks all buildings' selling apartments performance for last month, and if result is negative add notification for those who have the appropriate role\e[0m

    \e[1;33mcontact-us send\e[0m                \e[2;33m- send email from website contact us page [--name=VisitorName] [--email=VisitorEmail] [--remarks=RemarksAsString]\e[0m

    \e[1;33mdb safe-backup\e[0m                 \e[2;33m- create safe database backup\e[0m

    \e[1;33mtools\e[0m                          \e[2;33m- more tools (help included)\e[0m

    \e[1;33marrivals send-arrivals-mail\e[0m    \e[2;33m- Send email to concierge for new arrivals\e[0m

    \e[1;33mtask update-reservation-cleaning-tasks-for-2-days\e[0m    \e[2;33m- update cleaning tasks and set housekeeper entry and next guest keys\e[0m

    \e[1;33mparking extend-inventory\e[0m    \e[2;33m- Extend parking inventory\e[0m

    \e[1;33memail send-applicant-rejections\e[0m \e[2;33m- Send applicant rejection emails\e[0m

    \e[1;33mtools report-unused-files\e[0m \e[2;33m- Report all unused files on hard disk & lost files in database || type [ginosole tools] to see all available parameters\e[0m
    \e[1;33mtools optimize-tables\e[0m \e[2;33m- Run optimize tables statement for all database tables \e[0m

    \e[1;33minventory-synchronization execute-inventory\e[0m \e[2;33m- Apartment inventory synchronization [start|restart]\e[0m

    \e[1;33mapi-request delete-expired-request\e[0m \e[2;33m- Delete expired API request\e[0m

    \e[1;33mphpunit [--app=name]\e[0m           \e[2;33m- Run PHPUnit for Applications, available [website | backoffice | api]\e[0m

    \e[1;33m--id\e[0m                           \e[2;33m- Booking ID (not reservation number)\e[0m
    \e[1;33m--ccp\e[0m                          \e[2;33m- Credit Card Provided\e[0m
    \e[1;33m-bo\e[0m                            \e[2;33m- Flag 'from Backoffice' (force mode)\e[0m
    \e[1;33m--verbose/-v\e[0m                   \e[2;33m- Verbose mode\e[0m
    \e[1;33m--usage/--help\e[0m                 \e[2;33m- Display this help\e[0m


    \e[4;33mwaiting for more supplements...\e[0m


USAGE;
    }


    /**
     * Display the screensaver
     *
     * @return string
     */
    public function indexAction()
    {
        /**
         * Determine console dimensions
         */
        $console = $this->getConsole();
        $width   = $console->getWidth();
        $height  = $console->getHeight();

        /**
         * Bail out if Windows without Ansicon
         */
        if($console instanceof Virtual){
            return
                "I'm sorry, but Matrix does not work on stock Windows yet.\n".
                "To make it work, install ANSICON:\n ".
                "     https://github.com/adoxa/ansicon\n\n".
                "We are also working on supporting Windows without Ansicon.\n"
            ;
        }

        /**
         * Read options
         */
        /** @var $request \Zend\Console\Request */
        $speed          = (int)$this->request->getParam('speed',5);
        $intensity      = (float)$this->request->getParam('intensity',1);
        $maxLength      = min($intensity * ceil($height / 2), $height - 5);

        /**
         * Prepare state vars
         */
        $chars = str_split('0123456789XY$&@%**%QR#OGHNBM',1);
        $charEnd = count($chars)-1;
        $totalCount = $width * $intensity;

        /**
         * Init slugs
         */
        do{
            $slugs[] = array(
                'head'   => $chars[mt_rand(0, $charEnd)],
                'tail'   => array(),
                'length' => mt_rand(max(ceil($maxLength / 3),3), $maxLength),
                'y'      => 1,
                'x'      => mt_rand(1, $width),
                'delay'  => mt_rand(0, $totalCount),
            );
        }while(count($slugs) < $totalCount);


        /**
         * Clear screen
         */
        $console->hideCursor();
        $console->clear();

        /**
         * Main loop
         */
        do{
            foreach($slugs as &$slug){
                $y      = &$slug['y'];
                $x      = &$slug['x'];
                $length = &$slug['length'];
                $tail   = &$slug['tail'];
                $head   = &$slug['head'];
                $delay  = &$slug['delay'];

                /**
                 * Reset slug if tail reached the end of screen
                 */
                if($y - $length > $height){
                    $y      = 1;
                    $x      = mt_rand(1,$width);
                    $tail   = array();
                    $length = mt_rand(max(ceil($maxLength / 2),3), $maxLength);
                    $delay  = mt_rand(0, $totalCount);
                }

                /**
                 * Advance to next position each few iterations
                 */
                if(mt_rand(1,3)){
                    // do nothing if there is a delay planned
                    if($delay > 0){
                        $delay--;
                        continue;
                    }

                    // store tail
                    $tail[] = ''.$head;

                    // render head
                    if($y <= $height){
                        $console->writeAt($head,$x,$y, Color::WHITE);
                    }

                    // randomize head
                    $head = $chars[mt_rand(0,$charEnd)];

                    // trim tail
                    if(count($tail) > $length){
                        // remove element from tail
                        array_shift($tail);

                        if($y > $length){
                            // erase trimmed tail segment
                            $console->writeAt(' ',$x, $y - $length);
                        }
                    }

                    // render tail
                    $tailCount = count($tail);
                    $half = ceil( ($length-1) / 2) - 1;
                    for($tailY = 0; $tailY < $tailCount -1; $tailY++){
                        $char = $tail[$tailY];
                        $pos  = $y - $tailCount + 1 + $tailY;

                        if($pos > 0 && $pos <= $height) {
                            $color = ($tailY > $half) ? Color::LIGHT_GREEN : Color::GREEN;
                            $console->writeAt($char,$x,$pos, $color);
                        }
                    }

                    // advance position
                    $y++;
                }

            }
            usleep(200000 - ($speed * 20000));
            $console->showCursor();
        } while (true);
    }

    /**
     * @return Console
     */
    public function getConsole(){
        return $this->getServiceLocator()->get('console');
    }
}
