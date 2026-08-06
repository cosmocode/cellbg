<?php

namespace dokuwiki\plugin\cellbg\test;

use DokuWikiTest;

/**
 * Syntax tests for the cellbg plugin
 *
 * @group plugin_cellbg
 * @group plugins
 */
class SyntaxTest extends DokuWikiTest
{
    protected $pluginsEnabled = ['cellbg'];

    /**
     * Render wiki text to XHTML
     *
     * @param string $wiki wiki text to render
     * @param string|null $syntax syntax flavour to parse under, null for the configured one
     * @return string the rendered XHTML
     */
    protected function render($wiki, $syntax = null)
    {
        $info = [];
        return p_render('xhtml', p_get_instructions($wiki, $syntax), $info);
    }

    /**
     * Pick the opening tags of all table cells out of rendered XHTML
     *
     * @param string $xhtml rendered XHTML
     * @return string[] the td and th tags in document order
     */
    protected function cells($xhtml)
    {
        preg_match_all('/<t[dh]\b[^>]*>/', $xhtml, $match);
        return $match[0];
    }

    /**
     * The style attribute a cell of the given color has to carry
     *
     * @param string $color the expected color
     * @return string the expected style attribute
     */
    protected function style($color)
    {
        return 'style="--plugin-cellbg:' . $color . ';"';
    }

    /**
     * Parse wiki text and collect the instructions contributed by this plugin
     *
     * @param string $wiki wiki text to parse
     * @return array[] the arguments of every cellbg instruction in document order
     */
    protected function cellbgInstructions($wiki)
    {
        $found = [];
        foreach (p_get_instructions($wiki) as $instruction) {
            if ($instruction[0] === 'plugin' && $instruction[1][0] === 'cellbg') {
                $found[] = $instruction[1];
            }
        }
        return $found;
    }

    /**
     * The accepted ways of naming a color
     *
     * @return array[] syntax to use and background color it has to produce
     */
    public static function provideColors()
    {
        return [
            'color name' => ['@red:', 'red'],
            'three digit hex' => ['@#abc:', '#abc'],
            'six digit hex' => ['@#6495ed:', '#6495ed'],
            'rgb triplet' => ['@rgb(0,255,0):', 'rgb(0,255,0)'],
            'rgb percentages' => ['@rgb(10%,20%,30%):', 'rgb(10%,20%,30%)'],
            'omitted color' => ['@:', 'yellow'],
        ];
    }

    /**
     * Every accepted color notation becomes the background of the cell it opens
     *
     * @dataProvider provideColors
     * @param string $syntax the color syntax to use
     * @param string $color the background color it has to produce
     */
    public function testColors($syntax, $color)
    {
        $cells = $this->cells($this->render("|  {$syntax}content  |  plain  |\n"));

        $this->assertStringContainsString($this->style($color), $cells[0]);
        $this->assertStringNotContainsString('--plugin-cellbg', $cells[1]);
    }

    /**
     * Syntax that resembles a color without being a valid one
     *
     * @return array[] syntax that must not be treated as a color
     */
    public static function provideNonColors()
    {
        return [
            'leading digits' => ['@123:'],
            'short hex' => ['@#12:'],
            'hash only' => ['@#:'],
            'incomplete rgb' => ['@rgb(0,255):'],
            'unclosed rgb' => ['@rgb(0,255,0:'],
        ];
    }

    /**
     * An unrecognized color leaves the cell uncolored and stays visible as text
     *
     * @dataProvider provideNonColors
     * @param string $syntax the syntax that must not be treated as a color
     */
    public function testNonColors($syntax)
    {
        $xhtml = $this->render("|  {$syntax}content  |  plain  |\n");

        $this->assertStringNotContainsString('--plugin-cellbg', $xhtml);
        $this->assertStringContainsString($syntax . 'content', $xhtml);
    }

    /**
     * Colors are applied to header cells as well
     */
    public function testHeaderCell()
    {
        $cells = $this->cells($this->render("^  @red:content  ^  plain  ^\n|  a  |  b  |\n"));

        $this->assertStringStartsWith('<th', $cells[0]);
        $this->assertStringContainsString($this->style('red'), $cells[0]);
    }

    /**
     * Colors work in Markdown tables too
     *
     * The header cells prove the table was parsed as Markdown, as the same text also forms
     * a valid DokuWiki table, one that consists of plain cells only.
     */
    public function testMarkdownTable()
    {
        $wiki = "| head | plain |\n| --- | --- |\n| @red:content | plain |\n";
        $cells = $this->cells($this->render($wiki, 'md+dw'));

        $this->assertStringStartsWith('<th', $cells[0]);
        $this->assertStringContainsString($this->style('red'), $cells[2]);
        $this->assertStringNotContainsString('--plugin-cellbg', $cells[3]);
    }

    /**
     * Colors survive other cell formatting
     */
    public function testCombinedWithOtherFormatting()
    {
        $cells = $this->cells($this->render("|  @red:**bold**  ||  plain  |\n"));

        $this->assertStringContainsString('colspan="2"', $cells[0]);
        $this->assertStringContainsString($this->style('red'), $cells[0]);
    }

    /**
     * The color only counts when it opens the cell
     */
    public function testNotAtCellStart()
    {
        $xhtml = $this->render("|  text @red:content  |  plain  |\n");

        $this->assertStringNotContainsString('--plugin-cellbg', $xhtml);
        $this->assertStringContainsString('@red:content', $xhtml);
    }

    /**
     * A color attached to text is not recognized, so it cannot swallow that text
     */
    public function testColorMustFollowDelimiter()
    {
        $this->assertSame([], $this->cellbgInstructions("|  Price@usd:5  |  plain  |\n"));
        $this->assertStringContainsString(
            'Price@usd:5',
            $this->render("|  Price@usd:5  |  plain  |\n")
        );
    }

    /**
     * The syntax is only recognized inside tables, so it cannot interfere anywhere else
     */
    public function testRecognizedInTablesOnly()
    {
        $this->assertCount(1, $this->cellbgInstructions("|  @red:content  |  plain  |\n"));
        $this->assertSame([], $this->cellbgInstructions("Cost @red: is high | really\n"));
        $this->assertSame([], $this->cellbgInstructions("  * @red:item | more\n"));
    }

    /**
     * The ways two colors can be written into the same cell
     *
     * @return array[] the two colors as written
     */
    public static function provideRepeatedColors()
    {
        return [
            'written back to back' => ['@red:@lime:'],
            'separated by one space' => ['@red: @lime:'],
            'separated by two spaces' => ['@red:  @lime:'],
        ];
    }

    /**
     * Only the first color of a cell counts, any further one stays visible as text
     *
     * @dataProvider provideRepeatedColors
     * @param string $syntax the two colors as written
     */
    public function testRepeatedColors($syntax)
    {
        $xhtml = $this->render("|  {$syntax}content  |  plain  |\n");
        $cells = $this->cells($xhtml);

        $this->assertSame(1, substr_count($cells[0], '--plugin-cellbg'));
        $this->assertStringContainsString($this->style('red'), $cells[0]);
        $this->assertStringContainsString('@lime:content', $xhtml);
    }
}
