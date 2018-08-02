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

        $files = $this->getModel('contactclient')->getFiles($contactClient);

        $fileCount = count($files);
        $orderBy = [
            $session->get('mautic.contactclient.' . $contactClient->getId() . '.files.orderby', 'date_added'),
            $session->get('mautic.contactclient.' . $contactClient->getId() . '.files.orderbydir', 'DESC'),
        ];

        $limit = $session->get('mautic.contactclient.' . $contactClient->getId() . '.files.limit', 25);

        return [
            'files'    => $files,
            'order'    => $orderBy,
            'total'    => $fileCount,
            'page'     => $page,
            'limit'    => $limit,
            'maxPages' => ceil($fileCount / $limit),
        ];
    }


    /**
     * @return array
     *
     * @throws \Exception
     */
    private function getDateParams()
    {
        $params    = [];
        $lastMonth = new \DateTime();
        $lastMonth->sub(new \DateInterval('P30D'));
        $today = new \DateTime();

        $from = new \DateTime(
            $this->request->getSession()
                ->get('mautic.dashboard.date.from', $lastMonth->format('Y-m-d 00:00:00'))
        );

        $to = new \DateTime(
            $this->request->getSession()
                ->get('mautic.dashboard.date.to', $today->format('Y-m-d H:i:s'))
        );

        $params['fromDate'] = $from;

        $params['toDate'] = $to;

        return $params;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function parseLogJSONBlob($data)
    {
        $json = json_decode($data['logs'], true);
        unset($data['logs']);
        $rows                      = [];
        $data['request_format']    = '';
        $data['request_method']    = '';
        $data['request_headers']   = '';
        $data['request_body']      = '';
        $data['request_duration']  = '';
        $data['response_status']   = '';
        $data['response_headers']  = '';
        $data['response_body_raw'] = '';
        $data['response_format']   = '';
        $data['valid']             = '';
        if (isset($json['operations'])) {
            foreach ($json['operations'] as $id => $operation) {
                if (is_numeric($id)) {
                    $row                    = $data;
                    $row['request_format']  = isset($operation['request']['format']) ? $operation['request']['format'] : '';
                    $row['request_method']  = isset($operation['request']['method']) ? $operation['request']['method'] : '';
                    $row['request_headers'] = '';
                    $row['request_body']    = '';
                    if (isset($operation['request']['options'])) {
                        if (isset($operation['request']['options']['headers'])) {
                            $row['request_headers'] = implode('; ', $operation['request']['options']['headers']);
                            unset($operation['request']['options']['headers']);
                        }

                        $string = '';
                        foreach ($operation['request']['options'] as $key => $option) {
                            $string .= "$key: ".implode(',', $option).'; ';
                        }
                        $row['request_body'] = $string;
                    }
                    $row['request_duration']  = isset($operation['request']['duration']) ? $operation['request']['duration'] : '';
                    $row['response_status']   = isset($operation['response']['status']) ? $operation['response']['status'] : '';
                    $row['response_headers']  = isset($operation['response']['headers']) ? implode(
                        '; ',
                        $operation['response']['headers']
                    ) : '';
                    $row['response_body_raw'] = isset($operation['response']['bodyRaw']) ? $operation['response']['bodyRaw'] : '';
                    $row['response_format']   = isset($operation['response']['format']) ? $operation['response']['format'] : '';
                    $row['valid']             = isset($operation['valid']) ? $operation['valid'] : '';

                    $rows[$id] = $row;
                }
            }
        } else {
            $rows[0] = $data;
        }

        return $rows;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function parseLogYAMLBlob($data)
    {
        $yaml = Yaml::parse($data['logs']);
        unset($data['logs']);
        $rows                      = [];
        $data['request_format']    = '';
        $data['request_method']    = '';
        $data['request_headers']   = '';
        $data['request_body']      = '';
        $data['request_duration']  = '';
        $data['response_status']   = '';
        $data['response_headers']  = '';
        $data['response_body_raw'] = '';
        $data['response_format']   = '';
        $data['valid']             = '';
        if (isset($yaml['operations'])) {
            foreach ($yaml['operations'] as $id => $operation) {
                if (is_numeric($id)) {
                    $row                    = $data;
                    $row['request_format']  = isset($operation['request']['format']) ? $operation['request']['format'] : '';
                    $row['request_method']  = isset($operation['request']['method']) ? $operation['request']['method'] : '';
                    $row['request_headers'] = '';
                    $row['request_body']    = '';
                    if (isset($operation['request']['options'])) {
                        if (isset($operation['request']['options']['headers'])) {
                            $row['request_headers'] = implode('; ', $operation['request']['options']['headers']);
                            unset($operation['request']['options']['headers']);
                        }

                        $string = '';
                        foreach ($operation['request']['options'] as $key => $option) {
                            $string .= "$key: ".implode(',', $option).'; ';
                        }
                        $row['request_body'] = $string;
                    }
                    $row['request_duration']  = isset($operation['request']['duration']) ? $operation['request']['duration'] : '';
                    $row['response_status']   = isset($operation['response']['status']) ? $operation['response']['status'] : '';
                    $row['response_headers']  = isset($operation['response']['headers']) ? implode(
                        '; ',
                        $operation['response']['headers']
                    ) : '';
                    $row['response_body_raw'] = isset($operation['response']['bodyRaw']) ? $operation['response']['bodyRaw'] : '';
                    $row['response_format']   = isset($operation['response']['format']) ? $operation['response']['format'] : '';
                    $row['valid']             = isset($operation['valid']) ? $operation['valid'] : '';

                    $rows[$id] = $row;
                }
            }
        } else {
            $rows[0] = $data;
        }

        return $rows;
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
