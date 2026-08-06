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


    /** @inheritdoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('@#?[0-9a-zA-Z]*:(?=[^\n]*\|[[:space:]]*\n)', $mode, 'plugin_cellbg');
    }


    /** @inheritdoc */
    public function handle($match, $state, $pos, Handler $handler)
    {
        preg_match("/@([^:]*)/", $match, $color); // get the color
        if ($this->isValid($color[1])) return [$color[1], $match];

        return ["yellow", $match];
    }

    /** @inheritdoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode != 'xhtml') return false;
        [$color, $text] = $data;

        if (preg_match('/(<td[^<>]*)>[[:space:]]*$/', $renderer->doc) != 0) {
            $renderer->doc = preg_replace('/(<td[^<>]*)>[[:space:]]*$/', '\1 bgcolor=' . $color . '>', $renderer->doc);
        } else {
            $renderer->doc .= $text;
        }

        return true;
    }

    /**
     * validate color value $c
     * this is cut price validation - only to ensure the basic format is correct and there is nothing harmful
     * three basic formats  "colorname", "#fff[fff]", "rgb(255[%],255[%],255[%])"
     *
     * @param string $c color value to validate
     * @return bool true if valid, false otherwise
     */
    protected function isValid($c)
    {

        $c = trim($c);

        $pattern = "/
            (^[a-zA-Z]+$)|                                #colorname - not verified
            (^\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$)|        #colorvalue
            (^rgb\(([0-9]{1,3}%?,){2}[0-9]{1,3}%?\)$)     #rgb triplet
            /x";

        return (preg_match($pattern, $c));
    }
}
