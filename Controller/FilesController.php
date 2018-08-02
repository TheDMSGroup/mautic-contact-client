<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Yaml\Yaml;

/**
 * Class TimelineController.
 */
class FilesController extends AbstractFormController
{
    use ContactClientAccessTrait;
    use ContactClientDetailsTrait;

    public function indexAction(Request $request, $objectId, $page = 1)
    {
        if (empty($objectId)) {
            return $this->accessDenied();
        }

        $contactClient = $this->checkContactClientAccess($objectId, 'view');
        if ($contactClient instanceof Response) {
            return $contactClient;
        }

        $session = $this->get('session');

        if ('POST' === $request->getMethod()) {
            if ($request->query->has('orderby')) {
                $session->set('mautic.contactclient.' . $contactClient->getId() . '.files.orderby', $request->query->get('orderby'));
            }
            if ($request->query->has('orderbydir')) {
                $session->set('mautic.contactclient.' . $contactClient->getId() . '.files.orderbydir', $request->query->get('orderbydir'));
            }
            if ($request->query->has('limit')) {
                $session->set('mautic.contactclient.' . $contactClient->getId() . '.files.limit', $request->query->get('limit'));
            }
        }

        return $this->getFiles($contactClient, null, null, $page);
    }

    /**
     * Downloads a file securely.
     *
     * @param null $contactClientId
     * @param null $fileId
     *
     * @return array|\MauticPlugin\MauticContactClientBundle\Entity\ContactClient|BinaryFileResponse|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     */
    public function fileAction($contactClientId = null, $fileId = null)
    {
        if (empty($contactClientId) || empty($fileId)) {
            return $this->accessDenied();
        }

        $contactClient = $this->checkContactClientAccess($contactClientId, 'view');
        if ($contactClient instanceof Response) {
            return $contactClient;
        }

        $file = $this->checkContactClientFileAccess($fileId, 'view');
        if ($file instanceof Response) {
            return $file;
        }

        if (!$file || !$file->getLocation() || !file_exists($file->getLocation())) {
            return $this->accessDenied();
        }

        $response = new BinaryFileResponse($file->getLocation());
        $response->setPrivate();
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }
}
