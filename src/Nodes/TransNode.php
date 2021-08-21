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

namespace TwigI18n\Nodes;

class TransNode extends \Twig\Node\Node
{
	public function __construct(\Twig\Node\Node $translation, ?\Twig\Node\Node $plural, ?\Twig\Node\Expression\AbstractExpression $count, ?\Twig\Node\Node $notes, ?\Twig\Node\Node $domain = null, int $lineno = 0, ?string $tag = null)
    {
		$nodes = ['translation' => $translation];

		/* Do we have a domain? */
		if ($domain !== null)
		{
			$nodes['domain'] = $domain;
		}

		/* Counting number for plural messages? */
		if ($count !== null)
		{
			$nodes['count'] = $count;
		}

		/* Do we have a plural? */
		if ($plural !== null)
		{
			$nodes['plural'] = $plural;
		}

		/* Do we have notes? */
		if ($notes !== null)
		{
			$nodes['notes'] = $notes;
		}

        parent::__construct($nodes, [], $lineno, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        [$singular, $args] = $this->compileString($this->getNode('translation'));

		[$domain, $vars] = $this->compileString($this->getNode('domain'));

        if ($this->hasNode('plural'))
		{
			[$plural, $args1] = $this->compileString($this->getNode('plural'));

            $args = array_merge($args, $args1);
        }

		if ($this->hasNode('notes'))
		{
			$comment = trim($this->getNode('notes')->getAttribute('data'));

			/* We want a single line comment here */
            $message = str_replace(["\n", "\r"], ' ', $message);
            $compiler->write('// Translation Notes: '.$notes."\n");
        }

		/* Singular messages */
		if (!$this->hasNode('plural'))
		{
			$compiler
                ->write('echo \SHN\I18n::t(')
				->subcompile($singular)
				->raw(', ')
				->subcompile($domain);
		}
		else /* We must be using a plural */
		{
			$compiler
                ->write('echo \SHN\I18n::t2(')
				->raw('array(')
				->subcompile($singular)
				->raw(', ')
				->subcompile($plural)
				->raw('), ')
				->subcompile($domain)
				->raw(', abs(')
				->subcompile($this->hasNode('count') ? $this->getNode('count') : null)
				->raw(')');
		}

		/* Do we have any variables? */
		if ($args)
		{
			$compiler->raw(', array(');

            foreach ($args as $arg)
			{
				if ($arg->getAttribute('name') === 'count')
				{
					$compiler
                        ->string('%count%')
                        ->raw(' => abs(')
                        ->subcompile($this->hasNode('count') ? $this->getNode('count') : null)
						->raw('), ');
                }
				else
				{
					$compiler
                        ->string('%'.$arg->getAttribute('name').'%');

					if ($arg->hasAttribute('raw'))
					{
						$compiler
							->raw(' => ')
							->subcompile($arg)
							->raw(', ');
					}
					else
					{
						$compiler
							->raw(' => twig_escape_filter($this->env, ')
							->subcompile($arg)
							->raw('), ');
					}
                }
            }

			$compiler->raw(')');
		}

		$compiler->raw(");\n");
    }

    /**
     * Compile a translation string
     */
    protected function compileString(\Twig\Node\Node $translation): array
    {
		if ($translation instanceof \Twig\Node\Expression\NameExpression || $translation instanceof \Twig\Node\Expression\ConstantExpression || $translation instanceof \Twig\Node\Expression\TempNameExpression)
		{
			return [$translation, []];
		}

        $vars = [];
        if (count($translation))
		{
			$msg = '';

			foreach ($translation as $node)
			{
				if ($node instanceof \Twig\Node\PrintNode)
				{
					$n = $node->getNode('expr');
					if (!$n instanceof \Twig\Node\Expression\FilterExpression)
					{
						$isRaw = true;
					}
					else
					{
						$isRaw = false;
					}

					while ($n instanceof \Twig\Node\Expression\FilterExpression)
					{
						$n = $n->getNode('node');
					}

					while ($n instanceof \Twig\Node\CheckToStringNode)
					{
						$n = $n->getNode('expr');
					}

                    $msg .= sprintf('%%%s%%', $n->getAttribute('name'));

					$expr = new \Twig\Node\Expression\NameExpression($n->getAttribute('name'), $n->getTemplateLine());
					if ($isRaw)
					{
						$expr->setAttribute('raw', true);
					}

                    $vars[] = $expr;
                }
				else
				{
					$msg .= $node->getAttribute('data');
                }
            }
        }
		else
		{
			$msg = $translation->getAttribute('data');
        }

        return [new \Twig\Node\Node([new \Twig\Node\Expression\ConstantExpression(trim($msg), $translation->getTemplateLine())]), $vars];
    }
}