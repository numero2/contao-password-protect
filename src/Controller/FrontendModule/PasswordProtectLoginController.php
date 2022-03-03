<?php

/**
 * Password protect bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\PasswordProtectBundle\Controller\FrontendModule;

use Contao\BackendUser;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @FrontendModule("pp_login",
 *   category="miscellaneous",
 *   template="mod_pp_login",
 * )
 */
class PasswordProtectLoginController extends AbstractFrontendModuleController {


    /**
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory
     */
    private $passwordHasherFactory;

    /**
     * @var Contao\CoreBundle\Csrf\ContaoCsrfTokenManager
     */
    private $csrfTokenManager;

    /**
     * @var Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;


    public function __construct( RequestStack $requestStack, PasswordHasherFactory $passwordHasherFactory, ContaoCsrfTokenManager $csrfTokenManager, TranslatorInterface $translator ) {

        $this->requestStack = $requestStack;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->translator = $translator;
    }


    /**
     * {@inheritdoc}
     */
    protected function getResponse( Template $template, ModuleModel $model, Request $request ): ?Response {

        $oPage = $this->getPageModel();

        $jumpToPage = $model->jumpTo?PageModel::findOneById($model->jumpTo):null;
        $jumpToPage = $jumpToPage?:$oPage;

        $doNotSubmit = false;
        $template->action = $jumpToPage->getAbsoluteUrl();
        $template->formSubmit = 'pp_login_'.$model->id;

        $aDCA = [];
        $aDCA['FORM_SUBMIT'] = [
            'inputType' => 'hidden'
        ,   'default' => $template->formSubmit
        ];
        $aDCA['REQUEST_TOKEN'] = [
            'inputType' => 'hidden'
        ,   'default' => $this->csrfTokenManager->getDefaultTokenValue()
        ];
        $aDCA['password'] = [
            'label' => $this->translator->trans('MSC.password_protected_login.password', [], 'contao_default')
        ,   'inputType' => 'text'
        ,   'eval' => ['hideInput'=>true, 'placeholder'=>$this->translator->trans('MSC.password_protected_login.password', [], 'contao_default')]
        ];
        $aDCA['submit'] = [
            'label' => $this->translator->trans('MSC.password_protected_login.submit', [], 'contao_default')
        ,   'inputType' => 'submit'
        ];

        $aValues = [];
        $aWidgets = [];

        foreach( $aDCA as $key => $aField ) {

            $strClass = $GLOBALS['TL_FFL'][$aField['inputType']];
            if( !class_exists($strClass) ) {
                continue;
            }

            $oField = new $strClass($strClass::getAttributesFromDca($aField, $key));

            // set template manually as it won't be processed by getAttributesFromDca
            if( !empty($aField['template']) ) {
                $oField->template = $aField['template'];
            }

            if( Input::post('FORM_SUBMIT') === $template->formSubmit ) {

                $oField->validate();

                if( $key === 'password' ) {
                    $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(BackendUser::class);

                    if( empty($oField->value) || !$passwordHasher->verify($model->pp_password, $oField->value) ) {
                        $oField->addError($this->translator->trans('MSC.password_protected_login.invalid_password', [], 'contao_default'));
                    }
                }

                if( $oField->hasErrors() ) {
                    $doNotSubmit = true;
                } else {
                    $aValues[$key] = $oField->value;
                }

            } else {

                // set value
                if( array_key_exists('default', $aField) ) {
                    $oField->value = $aField['default'];
                }
            }
            $aWidgets[$key] = $oField->parse();
        }

        if( Input::post('FORM_SUBMIT') == $template->formSubmit && !$doNotSubmit ) {

            if( !empty($aValues['password']) ) {

                $request = $this->requestStack->getCurrentRequest();

                $pageId = $jumpToPage->id;
                $request->getSession()->set("pp_login_".$pageId, true);

                return new RedirectResponse($jumpToPage->getAbsoluteUrl());
            }
        }

        $template->widgets = $aWidgets;

        return $template->getResponse();
    }
}
