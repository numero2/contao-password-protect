<?php

/**
 * Password protect bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */

namespace numero2\PasswordProtectBundle\Security\Voter;

use Contao\CoreBundle\Security\Voter\MemberGroupVoter;
use Contao\FrontendUser;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class PasswordProtectVoter extends Voter {


    /**
     * @var Contao\CoreBundle\Security\Voter\MemberGroupVoter
     */
    private $memberGroupVoter;

    /**
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;


    public function __construct( MemberGroupVoter $memberGroupVoter, RequestStack $requestStack ) {

        $this->memberGroupVoter = $memberGroupVoter;
        $this->requestStack = $requestStack;
    }


    /**
     * @param mixed $attribute
     * @param mixed $subject
     */
    protected function supports( $attribute, $subject ): bool {

        return $this->memberGroupVoter->supports($attribute, $subject); // REVIEW
    }


    /**
     * @param mixed $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute( $attribute, $subject, TokenInterface $token ): bool {

        $request = $this->requestStack->getCurrentRequest();
        $pageId = $request->attributes->get('pageModel')->id;
        $ppLoggedIn = $request->getSession()->get("pp_login_".$pageId) === true;

        // Filter non-numeric values
        $subject = array_filter((array) $subject, static fn ($val) => (string) (int) $val === (string) $val);

        if( $ppLoggedIn ) {
            $index = array_search('-1', $subject);
            if( $index !== false ) {
                unset($subject[$index]);
            }
        }

        if( empty($subject) ) {
            return false;
        }

        if( $this->memberGroupVoter->voteOnAttribute($attribute, $subject, $token) ) {
            return true;
        }

        $user = $token->getUser();

        if( !$user instanceof FrontendUser && in_array(-2, array_map('intval', $subject), true) ) {
            if( $ppLoggedIn ) {
                return true;
            }
        }

        return false;
    }
}
