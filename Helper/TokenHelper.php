<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Helper;

use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class TokenHelper.
 */
class TokenHelper
{
    private $regex = '{contactclient=(.*?)}';

    /**
     * @var ContactClientModel
     */
    protected $model;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * TokenHelper constructor.
     *
     * @param FormModel $model
     */
    public function __construct(ContactClientModel $model, RouterInterface $router)
    {
        $this->router = $router;
        $this->model  = $model;
    }

    /**
     * @param $content
     *
     * @return array
     */
    public function findContactClientTokens($content)
    {
        $regex = '/'.$this->regex.'/i';

        preg_match_all($regex, $content, $matches);

        $tokens = [];

        if (count($matches[0])) {
            foreach ($matches[1] as $k => $id) {
                $token = '{contactclient='.$id.'}';
                $contactclient = $this->model->getEntity($id);
                if ($contactclient !== null
                    && (
                        $contactclient->isPublished()
                        || $this->security->hasEntityAccess(
                            'plugin:contactclient:items:viewown',
                            'plugin:contactclient:items:viewother',
                            $contactclient->getCreatedBy()
                        )
                    )
                ) {
                    $script = '<script src="'.$this->router->generate('mautic_contactclient_generate', ['id' => $id], true)
                        .'" type="text/javascript" charset="utf-8" async="async"></script>';
                    $tokens[$token] = $script;
                } else {
                    $tokens[$token] = '';
                }
            }
        }

        return $tokens;
    }
}
