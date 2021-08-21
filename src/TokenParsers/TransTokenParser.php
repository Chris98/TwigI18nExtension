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

namespace TwigI18n\TokenParsers;

class TransTokenParser extends \Twig\TokenParser\AbstractTokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

		$translation = null;
		$domain = null;
        $count = null;
        $plural = null;
        $notes = null;

		/* Always expect a domain */
		$stream->expect(\Twig\Token::NAME_TYPE, 'from');
		$domain = $this->parser->getExpressionParser()->parseExpression();

        if (!$stream->test(\Twig\Token::BLOCK_END_TYPE))
		{
            $translation = $this->parser->getExpressionParser()->parseExpression();
        }
		else
		{
			$stream->expect(\Twig\Token::BLOCK_END_TYPE);
			$translation = $this->parser->subparse([$this, 'decideForFork']);
            $next = $stream->next()->getValue();

            if ($next === 'plural')
			{
				$count = $this->parser->getExpressionParser()->parseExpression();
				$stream->expect(\Twig\Token::BLOCK_END_TYPE);
				$plural = $this->parser->subparse([$this, 'decideForFork']);

				if ($stream->next()->getValue() === 'notes')
				{
					$stream->expect(\Twig\Token::BLOCK_END_TYPE);
					$notes = $this->parser->subparse([$this, 'decideForEnd'], true);
				}
			}
			elseif ($next === 'notes')
			{
				$stream->expect(\Twig\Token::BLOCK_END_TYPE);
				$notes = $this->parser->subparse([$this, 'decideForEnd'], true);
            }
        }

        $stream->expect(\Twig\Token::BLOCK_END_TYPE);

        $this->checkTransString($translation, $lineno);

        return new \TwigI18n\Nodes\TransNode($translation, $plural, $count, $notes, $domain, $lineno, $this->getTag());
    }

    /**
     * @return bool
     */
    public function decideForFork(\Twig\Token $token)
    {
        return $token->test(['plural', 'notes', 'endtrans']);
    }

    /**
     * @return bool
     */
    public function decideForEnd(\Twig\Token $token)
    {
        return $token->test('endtrans');
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'trans';
    }

    /**
     * @return void
     *
     * @throws SyntaxError
     */
    protected function checkTransString(\Twig\Node\Node $body, int $lineno)
    {
        foreach ($body as $i => $node)
		{
			if ($node instanceof \Twig\Node\TextNode || ($node instanceof \Twig\Node\PrintNode && $node->getNode('expr') instanceof \Twig\Node\Expression\NameExpression) || $node instanceof \Twig\Node\PrintNode && $node->getNode('expr') instanceof \Twig\Node\Expression\FilterExpression)
			{
				continue;
			}

			throw new \Twig\Error\SyntaxError('The text to be translated with "trans" can only contain references to simple variables', $lineno);
        }
    }
}