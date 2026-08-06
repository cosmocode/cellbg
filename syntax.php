<?php

use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\Parsing\Handler;

/**
 * cellbg Plugin: Allows user-defined colored cells in tables
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     dr4Ke <dr4ke@dr4ke.net>
 * @link       http://git.dr4ke.net/?p=dr4Ke/forks/dokuwiki-cells-bg.git
 *
 * Derived from the highlight plugin from : http://www.dokuwiki.org/plugin:highlight
 * and : http://www.staddle.net/wiki/plugins/highlight
 */
class syntax_plugin_cellbg extends SyntaxPlugin
{
    /**
     * Parser modes the syntax is available in
     *
     * The DokuWiki and the Markdown table mode both emit the same cell markup.
     *
     * @var string[]
     */
    protected const TABLE_MODES = ['table', 'gfm_table'];

    /**
     * Matches the color part of the syntax: a color name, a three or six digit hex value
     * or an rgb triplet
     *
     * This doubles as validation, so an unrecognized color stays literal text rather than
     * painting the cell. Groups have to be non-capturing, as the lexer treats a bare
     * parenthesis as a literal character.
     *
     * @var string
     */
    protected const COLOR_PATTERN =
        '[a-zA-Z]+' .
        '|#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?' .
        '|rgb\((?:[0-9]{1,3}%?,){2}[0-9]{1,3}%?\)';

    /**
     * Color applied when the syntax omits one
     *
     * @var string
     */
    protected const DEFAULT_COLOR = 'yellow';

    /**
     * Matches a cell opener at the very end of the render buffer that has no background yet
     *
     * Requiring the opener to be uncolored makes the first color of a cell the one that
     * counts; every further color in that cell stays literal text.
     *
     * @var string
     */
    protected const CELL_PATTERN = '/(<t[dh]\b(?![^<>]*background-color)[^<>]*)>(\s*)$/';

    /**
     * Number of trailing bytes of the render buffer that are searched for a cell opener
     *
     * The opener has to be the last thing written, so only the tail can ever match. Keeping
     * the search bounded avoids rescanning the whole document once per colored cell. Any
     * value comfortably above the length of a cell tag will do.
     *
     * @var int
     */
    protected const TAIL_LENGTH = 512;

    /** @inheritdoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritdoc */
    public function getSort()
    {
        return 200;
    }

    /**
     * @inheritdoc
     *
     * The syntax is only connected to the table modes, where a cell delimiter is guaranteed
     * to precede it. The lookbehind has to stay fixed length: all patterns of a mode are
     * compiled into a single regex, so one that needs a newer PCRE than the server provides
     * takes down the whole mode.
     */
    public function connectTo($mode)
    {
        if (!in_array($mode, self::TABLE_MODES, true)) return;

        $this->Lexer->addSpecialPattern(
            '(?<=[\t |^])@(?:' . self::COLOR_PATTERN . ')?:',
            $mode,
            'plugin_cellbg'
        );
    }

    /**
     * @inheritdoc
     *
     * Strips the markers around the color, leaving the color itself for the renderer.
     *
     * @return string the color as written, empty when the syntax omits it
     */
    public function handle($match, $state, $pos, Handler $handler)
    {
        return substr($match, 1, -1);
    }

    /**
     * @inheritdoc
     *
     * Patches the background into the cell opener the renderer has just written. That opener
     * only sits at the end of the buffer when the syntax opens a cell that has no color yet;
     * in every other case the original text is emitted so nothing is lost.
     *
     * @param string $data the color as written, empty when the syntax omits it
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode != 'xhtml') return false;

        $color = $data === '' ? self::DEFAULT_COLOR : $data;
        $offset = max(0, strlen($renderer->doc) - self::TAIL_LENGTH);
        $tail = preg_replace(
            self::CELL_PATTERN,
            '$1 style="background-color:' . hsc($color) . ';">$2',
            substr($renderer->doc, $offset),
            1,
            $count
        );

        if ($count) {
            $renderer->doc = substr($renderer->doc, 0, $offset) . $tail;
        } else {
            $renderer->doc .= '@' . $data . ':';
        }

        return true;
    }
}
