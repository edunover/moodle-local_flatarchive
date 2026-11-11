<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_flatarchive\archive;

/**
 * Strict, bounded conversion of a documented TeX subset to passive MathML.
 *
 * Unknown commands and invalid syntax reject the entire formula. No macro
 * expansion, file access, HTML interpretation or external renderer is used.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class math_parser {
    /** @var string MathML namespace. */
    private const NS = 'http://www.w3.org/1998/Math/MathML';

    /** @var array Supported identifier commands. */
    private const LETTERS = [
        'alpha' => 'α', 'beta' => 'β', 'gamma' => 'γ', 'delta' => 'δ', 'epsilon' => 'ϵ',
        'varepsilon' => 'ε', 'zeta' => 'ζ', 'eta' => 'η', 'theta' => 'θ', 'vartheta' => 'ϑ',
        'iota' => 'ι', 'kappa' => 'κ', 'lambda' => 'λ', 'mu' => 'μ', 'nu' => 'ν', 'xi' => 'ξ',
        'pi' => 'π', 'varpi' => 'ϖ', 'rho' => 'ρ', 'varrho' => 'ϱ', 'sigma' => 'σ',
        'varsigma' => 'ς', 'tau' => 'τ', 'upsilon' => 'υ', 'phi' => 'ϕ', 'varphi' => 'φ',
        'chi' => 'χ', 'psi' => 'ψ', 'omega' => 'ω', 'Gamma' => 'Γ', 'Delta' => 'Δ',
        'Theta' => 'Θ', 'Lambda' => 'Λ', 'Xi' => 'Ξ', 'Pi' => 'Π', 'Sigma' => 'Σ',
        'Upsilon' => 'Υ', 'Phi' => 'Φ', 'Psi' => 'Ψ', 'Omega' => 'Ω', 'ell' => 'ℓ',
        'hbar' => 'ℏ', 'imath' => 'ı', 'jmath' => 'ȷ',
    ];

    /** @var array Supported operators and constants. */
    private const OPERATORS = [
        'times' => '×', 'cdot' => '⋅', 'div' => '÷', 'pm' => '±', 'mp' => '∓',
        'le' => '≤', 'leq' => '≤', 'ge' => '≥', 'geq' => '≥', 'ne' => '≠', 'neq' => '≠',
        'approx' => '≈', 'equiv' => '≡', 'sim' => '∼', 'simeq' => '≃', 'propto' => '∝',
        'in' => '∈', 'notin' => '∉', 'ni' => '∋', 'subset' => '⊂', 'supset' => '⊃',
        'subseteq' => '⊆', 'supseteq' => '⊇', 'cup' => '∪', 'cap' => '∩', 'setminus' => '∖',
        'land' => '∧', 'lor' => '∨', 'neg' => '¬', 'forall' => '∀', 'exists' => '∃',
        'to' => '→', 'rightarrow' => '→', 'leftarrow' => '←', 'leftrightarrow' => '↔',
        'Rightarrow' => '⇒', 'Leftarrow' => '⇐', 'Leftrightarrow' => '⇔', 'mapsto' => '↦',
        'infty' => '∞', 'partial' => '∂', 'nabla' => '∇', 'emptyset' => '∅',
        'sum' => '∑', 'prod' => '∏', 'int' => '∫', 'iint' => '∬', 'iiint' => '∭', 'oint' => '∮',
        'ldots' => '…', 'cdots' => '⋯', 'vdots' => '⋮', 'ddots' => '⋱', 'perp' => '⊥',
        'parallel' => '∥', 'angle' => '∠', 'degree' => '°',
    ];

    /** @var array Named functions rendered upright. */
    private const FUNCTIONS = ['sin', 'cos', 'tan', 'cot', 'sec', 'csc', 'arcsin', 'arccos', 'arctan',
        'sinh', 'cosh', 'tanh', 'log', 'ln', 'exp', 'lim', 'min', 'max', 'sup', 'inf', 'det', 'gcd'];

    /** @var array TeX tokens, including whitespace for text groups. */
    private array $tokens = [];

    /** @var int Next token. */
    private int $position = 0;

    /** @var int Nesting depth. */
    private int $depth = 0;

    /** @var \DOMDocument Target document. */
    private \DOMDocument $dom;

    /**
     * Bind generated elements to the document being archived.
     * @param \DOMDocument $dom Target document
     */
    private function __construct(\DOMDocument $dom) {
        $this->dom = $dom;
    }

    /**
     * Return a complete formula or null; never return partially interpreted TeX.
     * @param \DOMDocument $dom Target document
     * @param string $source Undelimited original formula
     * @param bool $block Display rather than inline math
     * @return \DOMElement|null Passive MathML, with its original TeX annotation
     */
    public static function convert(\DOMDocument $dom, string $source, bool $block): ?\DOMElement {
        if (trim($source) === '' || strlen($source) > 4096 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $source)) {
            return null;
        }
        $parser = new self($dom);
        preg_match_all('/\\\\[a-zA-Z]+|\\\\[^\r\n]|./us', $source, $matches);
        $parser->tokens = $matches[0];
        if (!$parser->tokens || count($parser->tokens) > 1024) {
            return null;
        }
        try {
            $body = $parser->expression();
            $math = $parser->element('math');
            $math->setAttribute('display', $block ? 'block' : 'inline');
            $math->setAttribute('class', 'flatarchive-math');
            $math->setAttribute('title', $source);
            $semantics = $parser->element('semantics', [$body]);
            $annotation = $parser->element('annotation', [], $source);
            $annotation->setAttribute('encoding', 'application/x-tex');
            $semantics->appendChild($annotation);
            $math->appendChild($semantics);
            return $math;
        } catch (\UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * Parse a sequence up to a mandatory closing token, if any.
     * @param string|null $end Closing token
     * @return \DOMElement Parsed row
     */
    private function expression(?string $end = null): \DOMElement {
        if (++$this->depth > 32) {
            throw new \UnexpectedValueException('Math nesting limit');
        }
        $row = $this->element('mrow');
        while (($token = $this->peek()) !== null && $token !== $end) {
            $base = $this->atom();
            $scripts = [];
            while (in_array($this->peek(), ['_', '^'], true)) {
                $kind = $this->take();
                if (isset($scripts[$kind])) {
                    throw new \UnexpectedValueException('Repeated math script');
                }
                $scripts[$kind] = $this->argument();
            }
            if (count($scripts) === 2) {
                $tag = $this->limits($base) ? 'munderover' : 'msubsup';
                $base = $this->element($tag, [$base, $scripts['_'], $scripts['^']]);
            } else if ($scripts) {
                $tag = isset($scripts['_']) ? 'msub' : 'msup';
                if ($this->limits($base)) {
                    $tag = isset($scripts['_']) ? 'munder' : 'mover';
                }
                $base = $this->element($tag, [$base, reset($scripts)]);
            }
            $row->appendChild($base);
        }
        if ($end !== null && $this->take() !== $end) {
            throw new \UnexpectedValueException('Unclosed math group');
        }
        $this->depth--;
        return $row;
    }

    /**
     * TeX operators whose limits move above/below in display style.
     * @param \DOMElement $base Operator
     * @return bool Whether to use under/over scripts
     */
    private function limits(\DOMElement $base): bool {
        if (
            in_array($base->localName, ['mi', 'mo'], true) &&
            in_array($base->textContent, ['∑', '∏', 'lim', 'min', 'max', 'sup', 'inf', 'det', 'gcd'], true)
        ) {
            $base->setAttribute('movablelimits', 'true');
            return true;
        }
        return false;
    }

    /**
     * Parse a command argument; one token or a braced group, as in TeX.
     * @return \DOMElement Argument
     */
    private function argument(): \DOMElement {
        if (++$this->depth > 32) {
            throw new \UnexpectedValueException('Math nesting limit');
        }
        $result = $this->atom(false);
        $this->depth--;
        return $result;
    }

    /**
     * Parse one atom, combining ordinary decimal digits only outside arguments.
     * @param bool $number Whether to join consecutive digits
     * @return \DOMElement Parsed atom
     */
    private function atom(bool $number = true): \DOMElement {
        $token = $this->take();
        if ($token === '{') {
            return $this->expression('}');
        }
        if ($token === null || in_array($token, ['}', '^', '_', '&', '#', '$', '%', '~'], true)) {
            throw new \UnexpectedValueException('Unexpected math token');
        }
        if ($token[0] === '\\') {
            return $this->command(substr($token, 1));
        }
        if (preg_match('/^[0-9]$/', $token)) {
            if ($number) {
                // Do not consume whitespace: TeX uses it to delimit an unbraced exponent.
                while (isset($this->tokens[$this->position]) && ctype_digit($this->tokens[$this->position])) {
                    $token .= $this->tokens[$this->position++];
                }
            }
            return $this->element('mn', [], $token);
        }
        if (preg_match('/^\p{L}$/u', $token)) {
            return $this->element('mi', [], $token);
        }
        if (preg_match('/^[+\-=<>(),.;:!|\[\]\/\x{2212}\x{00D7}\x{00F7}]$/u', $token)) {
            return $this->element('mo', [], $token === '-' ? '−' : $token);
        }
        throw new \UnexpectedValueException('Unsupported math character');
    }

    /**
     * Interpret only explicitly supported commands.
     * @param string $command Command without backslash
     * @return \DOMElement Parsed command
     */
    private function command(string $command): \DOMElement {
        if (isset(self::LETTERS[$command])) {
            return $this->element('mi', [], self::LETTERS[$command]);
        }
        if (isset(self::OPERATORS[$command])) {
            return $this->element('mo', [], self::OPERATORS[$command]);
        }
        if (in_array($command, self::FUNCTIONS, true)) {
            $withlimits = in_array($command, ['lim', 'min', 'max', 'sup', 'inf', 'det', 'gcd'], true);
            $node = $this->element($withlimits ? 'mo' : 'mi', [], $command);
            $node->setAttribute('mathvariant', 'normal');
            return $node;
        }
        if (in_array($command, ['frac', 'dfrac', 'tfrac'], true)) {
            $node = $this->element('mfrac', [$this->argument(), $this->argument()]);
            if ($command !== 'frac') {
                $node->setAttribute('displaystyle', $command === 'dfrac' ? 'true' : 'false');
            }
            return $node;
        }
        if ($command === 'sqrt') {
            $index = null;
            if ($this->peek() === '[') {
                $this->take();
                $index = $this->expression(']');
            }
            $base = $this->argument();
            return $this->element($index ? 'mroot' : 'msqrt', $index ? [$base, $index] : [$base]);
        }
        if ($command === 'left') {
            $left = $this->delimiter();
            $body = $this->expression('\\right');
            return $this->element('mrow', [$left, $body, $this->delimiter()]);
        }
        if ($command === 'mathrm') {
            $node = $this->argument();
            foreach ([$node, ...iterator_to_array($node->getElementsByTagName('mi'))] as $letter) {
                if ($letter->localName === 'mi') {
                    $letter->setAttribute('mathvariant', 'normal');
                }
            }
            return $node;
        }
        if ($command === 'text' || $command === 'operatorname') {
            if ($this->take() !== '{') {
                throw new \UnexpectedValueException('Expected text group');
            }
            $text = '';
            while (isset($this->tokens[$this->position]) && $this->tokens[$this->position] !== '}') {
                $token = $this->tokens[$this->position++];
                if ($token === '{' || $token[0] === '\\') {
                    throw new \UnexpectedValueException('Unsupported command inside math text');
                }
                $text .= $token;
            }
            if ($this->take() !== '}') {
                throw new \UnexpectedValueException('Unclosed text group');
            }
            $node = $this->element($command === 'text' ? 'mtext' : 'mi', [], $text);
            if ($command === 'operatorname') {
                $node->setAttribute('mathvariant', 'normal');
            }
            return $node;
        }
        $accents = ['hat' => '^', 'bar' => '¯', 'vec' => '→', 'dot' => '˙', 'ddot' => '¨'];
        if (isset($accents[$command])) {
            $node = $this->element('mover', [$this->argument(), $this->element('mo', [], $accents[$command])]);
            $node->setAttribute('accent', 'true');
            return $node;
        }
        if (in_array($command, ['{', '}', '%', '$', '#', '_'], true)) {
            return $this->element('mo', [], $command);
        }
        $spaces = [',' => '0.167em', ':' => '0.222em', ';' => '0.278em', ' ' => '0.333em',
            'quad' => '1em', 'qquad' => '2em', '!' => '-0.167em'];
        if (isset($spaces[$command])) {
            $node = $this->element('mspace');
            $node->setAttribute('width', $spaces[$command]);
            return $node;
        }
        throw new \UnexpectedValueException('Unsupported math command');
    }

    /**
     * Parse a scalable delimiter, including TeX's invisible dot delimiter.
     * @return \DOMElement Delimiter
     */
    private function delimiter(): \DOMElement {
        $token = $this->take();
        $delimiters = ['(' => '(', ')' => ')', '[' => '[', ']' => ']', '|' => '|', '.' => '',
            '\\{' => '{', '\\}' => '}', '\\langle' => '⟨', '\\rangle' => '⟩', '\\vert' => '|', '\\Vert' => '‖'];
        if (!isset($delimiters[$token ?? ''])) {
            throw new \UnexpectedValueException('Unsupported math delimiter');
        }
        $node = $this->element('mo', [], $delimiters[$token]);
        $node->setAttribute('stretchy', 'true');
        return $node;
    }

    /**
     * Look ahead, skipping insignificant TeX whitespace.
     * @return string|null Next token
     */
    private function peek(): ?string {
        while (isset($this->tokens[$this->position]) && preg_match('/^\s$/u', $this->tokens[$this->position])) {
            $this->position++;
        }
        return $this->tokens[$this->position] ?? null;
    }

    /**
     * Consume the next token.
     * @return string|null Consumed token
     */
    private function take(): ?string {
        $token = $this->peek();
        if ($token !== null) {
            $this->position++;
        }
        return $token;
    }

    /**
     * Construct safe MathML; source text is never treated as markup.
     * @param string $name Fixed MathML element name
     * @param array $children Child elements
     * @param string|null $text Literal token text
     * @return \DOMElement New element
     */
    private function element(string $name, array $children = [], ?string $text = null): \DOMElement {
        $node = $this->dom->createElementNS(self::NS, $name);
        if ($text !== null) {
            $node->appendChild($this->dom->createTextNode($text));
        }
        foreach ($children as $child) {
            $node->appendChild($child);
        }
        return $node;
    }
}
