<?php

/**
 * Password protect bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


$GLOBALS['TL_DCA']['tl_module']['palettes']['pp_login'] = '{title_legend},name,headline,type;{config_legend},pp_password,jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';


$GLOBALS['TL_DCA']['tl_module']['fields']['pp_password'] = [
    'inputType'             => 'password'
,   'eval'                  => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'']
,   'sql'                   => "varchar(64) NOT NULL default ''"
];
