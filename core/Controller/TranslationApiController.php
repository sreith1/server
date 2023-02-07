<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


namespace OC\Core\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\Translation\ITranslationManager;
use OCP\Translation\ITranslationProvider;

class TranslationApiController extends \OCP\AppFramework\OCSController {

	private ITranslationManager $provider;

	public function __construct($appName, IRequest $request, ITranslationManager $provider) {
		parent::__construct($appName, $request);

		$this->provider = $provider;
	}

	public function languages(): DataResponse {
		return new DataResponse([
			'languages' => $this->provider->getLanguages()
		]);
	}

	public function translate(string $text, string $fromLanguage, string $toLanguage): DataResponse {
		return new DataResponse([
			'text' => $this->provider->translate($text, $fromLanguage, $toLanguage)
		]);
	}

}
