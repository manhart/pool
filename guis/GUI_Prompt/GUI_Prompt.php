<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

namespace pool\guis\GUI_Prompt;

use GUI_Module;
use pool\classes\Core\Input\Filter\DataType;

class GUI_Prompt extends GUI_Module
{
    /**
     * @var array<string, string> $templates files (templates) to be loaded, usually used with $this->Template->setVar(...) in the prepare function.
     *     Defined as an associated array [handle => tplFile].
     */
    protected array $templates = [
        'stdout' => 'tpl_prompt.html'
    ];

    protected array $inputFilter = [
        'label'       => [DataType::ALPHANUMERIC, 'Ihre Eingabe'], // label for input
        'confirm'     => [DataType::ALPHANUMERIC, 'OK'], //Label confirmation button
        'cancel'      => [DataType::ALPHANUMERIC, 'Abbrechen'], // label cancel button
        'class'       => [DataType::ALPHANUMERIC, ''],
        'buttonClass' => [DataType::ALPHANUMERIC, ''],
        'inputClass'  => [DataType::ALPHANUMERIC, ''],
    ];

    /**
     * Prepare the template.
     */
    protected function prepare(): void
    {
        $this->Template->setVars([
            'class'       => $this->Input->getVar('class'),
            'buttonClass' => $this->Input->getVar('buttonClass'),
            'inputClass'  => $this->Input->getVar('inputClass'),
            'label'       => $this->Input->getVar('label'),
            'confirm'     => $this->Input->getVar('confirm'),
            'cancel'      => $this->Input->getVar('cancel'),
            'name'        => $this->getName()
        ]);
    }
}