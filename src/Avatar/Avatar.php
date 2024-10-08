<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Tool Bundle.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-tool-bundle
 */

namespace Markocupic\SacEventToolBundle\Avatar;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\MemberModel;
use Contao\UserModel;
use Symfony\Component\Filesystem\Path;

class Avatar
{
    private Adapter $filesModelAdapter;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly string $projectDir,
        private readonly string $sacevtAvatarFemale,
        private readonly string $sacevtAvatarMale,
    ) {
        $this->filesModelAdapter = $this->framework->getAdapter(FilesModel::class);
    }

    /**
     * @todo Provide an avatar if gender === 'other'
     */
    public function getAvatarResourcePath(MemberModel|UserModel|null $userModel, $blnAbsolute = false): string
    {
        if (!empty($userModel->avatar)) {
            $objFiles = $this->filesModelAdapter->findByUuid($userModel->avatar);

            if (null !== $objFiles) {
                if (is_file($this->projectDir.'/'.$objFiles->path)) {
                    return $blnAbsolute ? Path::makeAbsolute($objFiles->path, $this->projectDir) : $objFiles->path;
                }
            }
        }

        if (!empty($userModel) && 'female' === $userModel->gender) {
            return $blnAbsolute ? Path::makeAbsolute($this->sacevtAvatarFemale, $this->projectDir) : $this->sacevtAvatarFemale;
        }

        return $blnAbsolute ? Path::makeAbsolute($this->sacevtAvatarMale, $this->projectDir) : $this->sacevtAvatarMale;
    }
}
