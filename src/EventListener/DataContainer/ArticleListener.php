<?php

/**
 * Password protect bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\PasswordProtectBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\System;
use Symfony\Contracts\Translation\TranslatorInterface;


class ArticleListener {

    /**
     * @var Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;


    public function __construct( TranslatorInterface $translator ) {

        $this->translator = $translator;
    }


    /**
     * Adds our option for this kind of password protection
     *
     * @Callback(table="tl_article", target="fields.groups.options", priority=100)
     */
    public function groupsOptions(): array {

        $oListener = System::importStatic('contao.listener.data_container.member_groups');
        $options = $oListener->__invoke();
        $options[-2] = $this->translator->trans('MSC.password_protected', [], 'contao_default');

        return $options;
    }
}
