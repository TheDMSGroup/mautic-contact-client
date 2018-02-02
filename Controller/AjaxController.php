<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\MauticContactClientBundle\Integration\ClientIntegration;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    protected function getApiPayloadTestAction(Request $request)
    {
        // Get the API payload to test.
        $apiPayload = html_entity_decode(InputHelper::clean($request->request->get('apiPayload')));

        // default to empty
        $dataArray = [
            'html'    => '',
            'success' => 0,
        ];

        if (!empty($apiPayload)) {

            /** @var ClientIntegration $clientIntegration */
            $clientIntegration = $this->get('mautic.contactclient.integration');


            $result = $clientIntegration->sendTest($apiPayload);

            // get the network type form
//             $form = $this->get('form.factory')->create($type, [], ['label' => false, 'csrf_protection' => false]);

//            $html = $this->renderView(
//                'MauticSocialBundle:FormTheme:'.$type.'_widget.html.php',
//                ['form' => $form->createView()]
//            );

            $html = $clientIntegration->getLogsYAML();

            $dataArray['html']    = $html;
            $dataArray['success'] = $result['valid'];
            $dataArray['payload'] = $result['payload'];
        }

        return $this->sendJsonResponse($dataArray);
    }
}
