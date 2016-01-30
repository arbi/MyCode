<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\Http\Response;

class PhoneController extends WebsiteBase
{
    public function xmlAction()
    {
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $this->redirect()->toRoute('home');
        }

        $press9 = [
            "+13234645488", //CHS
            "+13234630229", //HS & CHS
            "+13234637046", //HV
            "+13234606024", //Rubix
            "+13234606862", //Rubix
            "+13238757245", //Seattle
            "+12066821474", //Seattle2
            "+12028984721", //VCP
            "+13104376059", // Fountain Park
            "+13104376078", // Fountain Park
            "+13104376057", // Fountain Park
            "+13104377159", // Fountain Park
            "+13104376172", // Fountain Park
            "+13104376175", // Fountain Park
            "+13104376790", // Fountain Park
            "+13104376058", // Fountain Park
            "+13103011762", // Accent
            "+13108232832", // Accent
            "+13108232833", // Accent

        ];

        $press9twice = [
            "+12132217960", //HollandBack
            "+12132217953", //HollandFront
        ];

        $press9SunsetGordon = [
            "+12025541092", // Sunset & Gorden
        ];

        if (isset($_POST['From']) && in_array($_POST['From'], $press9)) {
            $ID = 9;
        } elseif (isset($_POST['From']) && in_array($_POST['From'], $press9twice)) {
            $ID = '9wwww9';
        } elseif (isset($_POST['From']) && in_array($_POST['From'], $press9SunsetGordon)) {
            $ID = '9www#';
        } else {
            $ID = 6;
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<Response>
    <Play digits=\"wwww$ID\" />
</Response>
";

        $response = new Response();

        $response->getHeaders()->addHeaderLine('Content-Type', 'text/xml; charset=utf-8');
        $response->setContent($xml);

        return $response;
    }
}
