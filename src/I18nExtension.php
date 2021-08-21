<?php
/*
 * This file is part of Twig.
 *
 * (c) 2010-2019 Fabien Potencier
 * (c) 2019 phpMyAdmin contributors
 * (c) 2021 Christopher Marshall contributor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TwigI18n;

final class I18nExtension extends \Twig\Extension\AbstractExtension
{
	/**
	 * Add the Token Parsers to the extension
	 */
    public function getTokenParsers()
    {
        return [new \TwigI18n\TokenParsers\TransTokenParser()];
    }

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'i18n';
	}

	/**
	 * Add the filters
	 */
	public function getFilters()
	{
		return [
			new \Twig\TwigFilter('trans', '\SHN\I18n::t'), /* Note, the filter does not handle plurals */
		];
	}
}